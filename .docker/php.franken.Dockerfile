FROM golang:1.25.3-bookworm@sha256:ee420c17fa013f71eca6b35c3547b854c838d4f26056a34eb6171bba5bf8ece4 AS go
FROM composer:2.8.12@sha256:23b68157c13e1bcc91908f5119da5537cf908983bb35bb2424f461ca5440d3e6 AS composer
FROM mlocati/php-extension-installer:2.9.13@sha256:f07adf63f4458e6f8d2774b62a34dde7990ef57d9c2cad21d13df61885475350 AS basephpextensioninstaller
FROM dunglas/frankenphp:php8.4.13@sha256:d656fa836a1f1e4b75a7838d817276e7c45b20c6714f6af336a6c7eb1638531b AS frankenphp

FROM go AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.45.4

FROM go AS runn
RUN go install github.com/k1LoW/runn/cmd/runn@v0.140.1

FROM go AS mysqldef
RUN go install github.com/sqldef/sqldef/cmd/mysqldef@v3.3.0

FROM go AS psqldef
RUN go install github.com/sqldef/sqldef/cmd/psqldef@v3.3.0

FROM frankenphp AS basebuild
RUN apt-get update \
    && apt-get install -yq git postgresql unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir /composer \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp
ENV COMPOSER_HOME=/composer \
    PATH=/composer/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes
RUN install-php-extensions redis gd opcache intl zip bcmath pdo_pgsql pgsql
COPY --from=composer /usr/bin/composer /usr/bin/composer

FROM basebuild AS local
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
RUN apt-get update \
    && apt-get install -yq dnsutils iproute2 iputils-ping vim \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /composer \
    && chown ${USER_NAME}:${USER_NAME} /app \
    && chown ${USER_NAME}:${USER_NAME} /data/caddy \
    && chown ${USER_NAME}:${USER_NAME} /config/caddy \
    && install -d -o ${USER_NAME} -g ${USER_NAME} /etc/caddy \
    && install -d -o ${USER_NAME} -g ${USER_NAME} /config/psysh \
    && install-php-extensions xdebug
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=runn /go/bin/runn /usr/bin/runn
USER ${USER_NAME}

FROM basebuild AS flyio
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
      && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
      && chown ${USER_NAME}:${USER_NAME} /composer \
      && chown ${USER_NAME}:${USER_NAME} /app \
      && chown ${USER_NAME}:${USER_NAME} /data/caddy \
      && chown ${USER_NAME}:${USER_NAME} /config/caddy \
      && install -d -o ${USER_NAME} -g ${USER_NAME} /etc/caddy \
      && install -d -o ${USER_NAME} -g ${USER_NAME} /config/psysh
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=${USER_NAME}:${USER_NAME} . /app/
USER ${USER_NAME}
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear
