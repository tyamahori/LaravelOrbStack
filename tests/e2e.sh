#!/usr/bin/env bash
# E2E through the *.local domains: host → balancer (nginx) → Apache / FrankenPHP →
# PostgreSQL (memo index), Redis (memo cache) and RustFS (memo documents). Fails when a
# domain does not resolve, the balancer routes it nowhere, the app builds URLs from the
# internal upstream name instead of the domain, or a memo does not round-trip a store.
# Plain HTTP so the same check runs on OrbStack, Docker Desktop and Linux alike.
set -euo pipefail

fail() {
    echo "e2e: $*" >&2
    exit 1
}

# request <expected status> <curl args...>: prints the body, fails on any other status.
request() {
    local expected=$1 response status
    shift
    response=$(curl -sS -w '\n%{http_code}' "$@") || fail "curl $* failed"
    status=${response##*$'\n'}
    [ "$status" = "$expected" ] || fail "$* answered ${status}, expected ${expected}: ${response%$'\n'*}"
    printf '%s' "${response%$'\n'*}"
}

for domain in apachephp.local frankenphp.local; do
    base="http://${domain}"
    # The PHP server may still be booting right after `up`; curl retries 5xx and refused
    # connections for up to a minute.
    request 200 --retry 30 --retry-delay 1 --retry-max-time 60 --retry-connrefused "${base}/" >/dev/null

    title="e2e ${domain} $(date +%s%N)"
    location=$(curl -sS -o /dev/null -w '%{http_code} %header{location}' \
        -H 'Accept: application/json' -H 'Content-Type: application/json' \
        --data "$(jq -n --arg title "$title" '{title: $title, body: "end to end"}')" \
        "${base}/api/memos")
    [ "${location%% *}" = 201 ] || fail "POST ${base}/api/memos answered ${location}"
    location=${location#* }
    [[ "$location" == "${base}/api/memos/"* ]] || fail "Location ${location} is not under ${base}"

    request 200 "$location" | jq -e --arg title "$title" '.title == $title' >/dev/null \
        || fail "GET ${location} did not return the posted title"
    # grep -q would exit early and SIGPIPE the producer, which pipefail reports as a failure.
    request 200 "${base}/memos" | grep -F "$title" >/dev/null || fail "${base}/memos does not list ${title}"

    # Editing reads the document from RustFS, never the cache (EditMemo), so a 200 proves
    # the document POST wrote is in RustFS; a missing one answers 404.
    request 200 -X PUT -H 'Accept: application/json' -H 'Content-Type: application/json' \
        --data "$(jq -n --arg title "$title" '{title: $title, body: "edited"}')" "$location" \
        | jq -e '.body == "edited"' >/dev/null || fail "PUT ${location} did not return the edited body"

    request 204 -X DELETE "$location" >/dev/null
    request 404 -H 'Accept: application/json' "$location" >/dev/null
done
