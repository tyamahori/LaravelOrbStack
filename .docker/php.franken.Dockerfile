FROM golang:1.25.5-bookworm@sha256:09f53deea14d4019922334afe6258b7b776afc1d57952be2012f2c8c4076db05 AS go
FROM composer:2.9.2@sha256:8b4d59fde3bd505c5fef70c9f8d5c05e92af811fed037dad12869b373925ed31 AS composer
FROM mlocati/php-extension-installer:2.9.23@sha256:067d6f813699f809edaee06b60a3fedcbfb424ceeb2cc5492806ce71ad249db7 AS basephpextensioninstaller
FROM dunglas/frankenphp:php8.5.0-trixie@sha256:85eb3d7f012c6404c516cc60152e9ccfeac9c84ec5db9f234df8000373eae5ce AS frankenphp

FROM go AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.45.5

FROM go AS runn
RUN go install github.com/k1LoW/runn/cmd/runn@v1.1.2

FROM go AS mysqldef
RUN go install github.com/sqldef/sqldef/cmd/mysqldef@v3.8.13

FROM go AS psqldef
RUN go install github.com/sqldef/sqldef/cmd/psqldef@v3.8.13

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
