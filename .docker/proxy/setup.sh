#!/usr/bin/env bash
# One-time host setup for the Traefik ingress in compose.desktop.yaml (Docker Desktop, Docker
# Engine on Linux): a local CA and server certificate, /etc/hosts entries, and trust for the
# CA. Idempotent; reruns only fix what is missing. No CoreDNS zone: macOS hands *.local to
# mDNS rather than a unicast resolver, so /etc/hosts resolves the names.
set -euo pipefail
umask 077

# The PHP containers bind-mount the repository, so the CA key stays outside it.
# compose.desktop.yaml mounts the server certificate from the same path.
state="${XDG_STATE_HOME:-${HOME}/.local/state}/laravelorbstack-proxy"
names=(apachephp.local frankenphp.local mail.laravelorbstack.local storage.laravelorbstack.local postgresql.laravelorbstack.local)
marker='# laravelorbstack proxy'

fail() {
    echo "proxy setup: $*" >&2
    exit 1
}

command -v openssl >/dev/null || fail "openssl is required"

generate() {
    local tmp san='' i
    tmp="$(mktemp -d "${state}/.tmp.XXXXXX")"
    trap 'rm -rf -- "${tmp}"' RETURN
    for i in "${!names[@]}"; do san+="DNS.$((i + 1)) = ${names[i]}"$'\n'; done
    # Name constraints keep a leaked CA key from signing for any other domain.
    cat >"${tmp}/openssl.cnf" <<EOF
[req]
distinguished_name = subject
prompt = no
[subject]
CN = LaravelOrbStack Local Development CA
[ca]
basicConstraints = critical, CA:true
keyUsage = critical, keyCertSign, cRLSign
nameConstraints = critical, permitted;DNS:apachephp.local, permitted;DNS:frankenphp.local, permitted;DNS:laravelorbstack.local
subjectKeyIdentifier = hash
[leaf]
basicConstraints = critical, CA:false
keyUsage = critical, digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @names
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid,issuer
[names]
${san}
EOF
    openssl req -x509 -new -newkey rsa:4096 -nodes -sha256 -days 3650 -config "${tmp}/openssl.cnf" \
        -extensions ca -keyout "${tmp}/ca.key" -out "${tmp}/ca.crt" 2>/dev/null
    openssl req -new -newkey rsa:2048 -nodes -subj "/CN=${names[0]}" \
        -keyout "${tmp}/leaf.key" -out "${tmp}/leaf.csr" 2>/dev/null
    # 825 days: the longest validity macOS accepts for a server certificate.
    openssl x509 -req -sha256 -days 825 -in "${tmp}/leaf.csr" -CA "${tmp}/ca.crt" -CAkey "${tmp}/ca.key" \
        -CAcreateserial -extfile "${tmp}/openssl.cnf" -extensions leaf -out "${tmp}/leaf.crt" 2>/dev/null
    chmod 644 "${tmp}/ca.crt" "${tmp}/leaf.crt"
    mv "${tmp}/ca.key" "${tmp}/ca.crt" "${tmp}/leaf.key" "${tmp}/leaf.crt" "${state}/"
}

mkdir -p "${state}"
chmod 700 "${state}"
[ -e "${state}/ca.crt" ] || generate
# Chain, expiry and name constraints as a TLS client checks them, then every name in the SAN.
# Text match rather than -verify_hostname: macOS's LibreSSL has no such option.
openssl verify -CAfile "${state}/ca.crt" -purpose sslserver "${state}/leaf.crt" >/dev/null \
    || fail "${state}/leaf.crt does not verify; remove ${state} (and the trusted CA) and rerun"
san="$(openssl x509 -in "${state}/leaf.crt" -noout -text)"
for name in "${names[@]}"; do
    [[ "${san}" == *"DNS:${name}"* ]] \
        || fail "${state}/leaf.crt does not name ${name}; remove ${state} (and the trusted CA) and rerun"
done

if ! grep -qF "${marker}" /etc/hosts; then
    # ::1 too: without it macOS asks mDNS for the AAAA record of a *.local name and stalls.
    printf '%s\n127.0.0.1 %s\n::1 %s\n' "${marker}" "${names[*]}" "${names[*]}" | sudo tee -a /etc/hosts >/dev/null
fi

case "$(uname -s)" in
    Darwin)
        security verify-cert -c "${state}/ca.crt" >/dev/null 2>&1 \
            || sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain "${state}/ca.crt"
        ;;
    Linux)
        anchor=/usr/local/share/ca-certificates/laravelorbstack.crt
        command -v update-ca-certificates >/dev/null \
            || fail "trust ${state}/ca.crt in the system store by hand; this distribution has no update-ca-certificates"
        if ! cmp -s "${state}/ca.crt" "${anchor}"; then
            sudo install -m 644 "${state}/ca.crt" "${anchor}"
            sudo update-ca-certificates >/dev/null
        fi
        ;;
    *) fail "unsupported OS $(uname -s); trust ${state}/ca.crt by hand" ;;
esac
