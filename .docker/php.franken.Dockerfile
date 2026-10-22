FROM golang:1.27.2-bookworm@sha256:5cf287a799e6b94384bad13d16b14904c531f51ba65792237e122ce42b392f61 AS go
FROM composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer
FROM mlocati/php-extension-installer:2.12.1@sha256:486d090fba21c04170262152c0ae63e7434d75e84fdd13450201c7fd54721b22 AS basephpextensioninstaller
FROM ghcr.io/php/pie:1.5.3-bin@sha256:9d3e6d9dafa5ae8cf034489260e206a5aa1b488b29c0ddaea9149826d9e32f52 AS pie
FROM dunglas/frankenphp:1.13.0-php8.5.11-trixie@sha256:4a8eebee6b3bed92d01ffa6ec5b1bc2cb9c2ab7eeb546027a3f7e6a52937ebbb AS frankenphp

FROM go AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.54.0

FROM go AS psqldef
RUN go install github.com/sqldef/sqldef/v3/cmd/psqldef@v3.11.26

FROM frankenphp AS basebuild
ENV COMPOSER_HOME=/composer \
    PATH=/composer/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes
# DL3008: Debian security updates replace a pinned apt version and break the build; the base image is pinned by digest instead.
# hadolint ignore=DL3008
RUN apt-get update \
    && apt-get install -yq --no-install-recommends git postgresql-client unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir /composer \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp
# The installers are bind-mounted so they never land in an image layer.
RUN --mount=type=bind,from=basephpextensioninstaller,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions gd opcache intl zip bcmath pdo_pgsql pgsql
# PECL extensions come from PIE so the build does not depend on pecl.php.net.
# lzf/zstd/lz4 keep the compression set install-php-extensions used to enable. PIE builds
# from the GitHub archive, which lacks the bundled liblzf submodule, so link the system one.
# hadolint ignore=DL3008
RUN --mount=type=bind,from=pie,source=/pie,target=/usr/local/bin/pie \
    apt-get update \
    && apt-get install -yq --no-install-recommends liblzf1 liblzf-dev libzstd-dev liblz4-dev \
    && pie install --no-cache phpredis/phpredis:6.3.0 \
      --enable-redis-lzf --with-liblzf=yes --enable-redis-zstd --enable-redis-lz4 --with-liblz4=yes \
    && apt-get purge -y --auto-remove liblzf-dev libzstd-dev liblz4-dev \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/bin/composer
# The FROM lines pin exact versions; composer.json's require.php and config.composerVersion
# (passed as build args) stay the source of truth for the PHP minor and the Composer version.
ARG PHP_VERSION
ARG COMPOSER_VERSION
RUN : "${PHP_VERSION:?pass --build-arg PHP_VERSION}" "${COMPOSER_VERSION:?pass --build-arg COMPOSER_VERSION}" \
    && case "$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')" in \
        "${PHP_VERSION}") ;; \
        *) echo "the frankenphp image in this Dockerfile is not PHP ${PHP_VERSION} (require.php)" >&2; exit 1 ;; \
    esac \
    && case "$(composer --version)" in \
        "Composer version ${COMPOSER_VERSION} "*) ;; \
        *) echo "the composer image in this Dockerfile is not Composer ${COMPOSER_VERSION} (config.composerVersion)" >&2; exit 1 ;; \
    esac

FROM basebuild AS local
# Before the user-specific ARGs, so xdebug's compile is cached independently of them.
RUN --mount=type=bind,from=pie,source=/pie,target=/usr/local/bin/pie \
    pie install --no-cache xdebug/xdebug:3.5.3
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
# hadolint ignore=DL3008
RUN apt-get update \
    && apt-get install -yq --no-install-recommends dnsutils iproute2 iputils-ping jq less openssh-client procps redis-tools vim \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -lom -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && install -d -o ${USER_NAME} -g ${USER_NAME} /composer /app /config/psysh /etc/caddy /data/caddy/pki
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=psqldef /go/bin/psqldef /usr/bin/psqldef
USER ${USER_NAME}

FROM basebuild AS flyio
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
      && useradd -lom -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
      && chown ${USER_NAME}:${USER_NAME} /composer \
      && chown ${USER_NAME}:${USER_NAME} /app \
      && chown ${USER_NAME}:${USER_NAME} /data/caddy \
      && chown ${USER_NAME}:${USER_NAME} /config/caddy \
      && install -d -o ${USER_NAME} -g ${USER_NAME} /etc/caddy \
      && install -d -o ${USER_NAME} -g ${USER_NAME} /config/psysh
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/php.ini /usr/local/etc/php/php.ini
USER ${USER_NAME}
# Dependencies before the source, so a code-only change reuses the vendor layer.
COPY --chown=${USER_NAME}:${USER_NAME} composer.json composer.lock /app/
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist --no-autoloader
COPY --chown=${USER_NAME}:${USER_NAME} . /app/
RUN composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear
