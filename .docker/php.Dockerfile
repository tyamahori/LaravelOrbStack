FROM golang:1.24.3-bookworm AS go
FROM composer:2.8.9 AS composer
FROM mlocati/php-extension-installer:2.7.34 AS basephpextensioninstaller
FROM dunglas/frankenphp:php8.4.8 AS frankenphp
FROM php:8.4.8-apache AS apachephp

FROM go AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.43.3

FROM go AS purl
RUN go install github.com/catatsuy/purl@v0.0.6

FROM go AS runn
RUN go install github.com/k1LoW/runn/cmd/runn@v0.130.2

FROM go AS mysqldef
RUN go install github.com/sqldef/sqldef/cmd/mysqldef@v1.0.6

FROM go AS psqldef
RUN go install github.com/sqldef/sqldef/cmd/psqldef@v1.0.6

FROM frankenphp AS basesetupfrankenphp
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
RUN apt-get update \
    && apt-get install -yq git postgresql unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && mkdir /composer \
    && chown ${USER_NAME}:${USER_NAME} /composer \
    && chown ${USER_NAME}:${USER_NAME} /app \
    && chown ${USER_NAME}:${USER_NAME} /data/caddy \
    && chown ${USER_NAME}:${USER_NAME} /config/caddy \
    && install -d -o ${USER_NAME} -g ${USER_NAME} /etc/caddy \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp
ENV COMPOSER_HOME=/composer \
    PATH=/composer/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN install-php-extensions redis gd opcache intl zip bcmath pdo_pgsql pgsql

FROM basesetupfrankenphp AS frankenphplocal
RUN apt-get update \
    && apt-get install -yq dnsutils iproute2 iputils-ping vim  \
    && install-php-extensions xdebug \
    && apt-get clean  \
    && rm -rf /var/lib/apt/lists/*
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=runn /go/bin/runn /usr/bin/runn
USER ${USER_NAME}

FROM apachephp AS commonphp
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
RUN apt-get update \
    && apt-get install -yq git postgresql unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /var/www/html \
    && mkdir /composer \
    && chown ${USER_NAME}:${USER_NAME} /composer \
    && a2enmod rewrite headers ssl mpm_prefork && a2dismod status info
ENV COMPOSER_HOME=/composer \
    PATH=/composer/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes \
    APACHE_RUN_USER=${USER_NAME} \
    APACHE_RUN_GROUP=${USER_NAME}
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=basephpextensioninstaller /usr/bin/install-php-extensions /usr/local/bin/install-php-extensions
RUN install-php-extensions redis gd opcache intl zip bcmath pdo_pgsql pgsql

FROM commonphp AS local
ENV APACHE_LOG_DIR=/var/www/html/storage/logs
RUN apt-get update \
    && apt-get install -yq dnsutils iproute2 iputils-ping vim  \
    && install-php-extensions xdebug \
    && apt-get clean  \
    && rm -rf /var/lib/apt/lists/*
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=runn /go/bin/runn /usr/bin/runn
USER ${USER_NAME}

FROM commonphp AS ci
RUN install-php-extensions xdebug
USER ${USER_NAME}

FROM commonphp AS develop
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/conmon/security.conf /etc/apache2/conf-available/security.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/php.ini /usr/local/etc/php/php.ini
USER ${USER_NAME}
RUN composer install && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear

FROM commonphp AS prod
ENV APACHE_LOG_DIR=/var/log/apache2
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/conmon/security.conf /etc/apache2/conf-available/security.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/php.ini /usr/local/etc/php/php.ini
USER ${USER_NAME}
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear

FROM commonphp AS flyio
ENV APACHE_LOG_DIR=/var/log/apache2
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/ports.conf /etc/apache2/ports.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/common/security.conf /etc/apache2/conf-available/security.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/php.ini /usr/local/etc/php/php.ini
USER ${USER_NAME}
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear

FROM basesetupfrankenphp AS frankenphpflyio
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=${USER_NAME}:${USER_NAME} . /app/
USER ${USER_NAME}
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && php artisan clear-compiled && php artisan optimize:clear
