FROM golang:1.25.4-bookworm@sha256:e17419604b6d1f9bc245694425f0ec9b1b53685c80850900a376fb10cb0f70cb AS go
FROM composer:2.9.1@sha256:7384cf9fa70b710af02c9f40bec6e44472e07138efa5ab3428a058087c0d2724 AS composer
FROM mlocati/php-extension-installer:2.9.18@sha256:4ac3eefdb28ddbc07611c31dfe5353eb7f5e072c0d653bdd915903afa984dc89 AS basephpextensioninstaller
FROM php:8.5.0-apache@sha256:74dcead709e8cf098764b35553f66ed249e57b232aa76bdb0aa7bc0795045ad9 AS apachephp

FROM go AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.45.5

FROM go AS runn
RUN go install github.com/k1LoW/runn/cmd/runn@v1.0.0

FROM go AS mysqldef
RUN go install github.com/sqldef/sqldef/cmd/mysqldef@v3.6.6

FROM go AS psqldef
RUN go install github.com/sqldef/sqldef/cmd/psqldef@v3.6.6

FROM apachephp AS basebuild
COPY --from=basephpextensioninstaller /usr/bin/install-php-extensions /usr/local/bin/install-php-extensions
RUN apt-get update \
    && apt-get install -yq git postgresql unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir /composer \
    && a2enmod rewrite headers ssl mpm_prefork && a2dismod status info
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
    && apt-get install -yq \
      dnsutils \
      iproute2 \
      iputils-ping \
      vim \
    && groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /composer \
    && chown ${USER_NAME}:${USER_NAME} /var/www/html \
    && install -d -o ${USER_NAME} -g ${USER_NAME} /config/psysh \
    && install-php-extensions xdebug
ENV APACHE_RUN_USER=${USER_NAME} \
    APACHE_RUN_GROUP=${USER_NAME} \
    APACHE_LOG_DIR=/var/www/html/storage/logs
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=runn /go/bin/runn /usr/bin/runn
USER ${USER_NAME}

FROM basebuild AS flyio
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /var/www/html \
    && chown ${USER_NAME}:${USER_NAME} /composer
ENV APACHE_LOG_DIR=/var/log/apache2
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/ports.conf /etc/apache2/ports.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/common/security.conf /etc/apache2/conf-available/security.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
USER ${USER_NAME}
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear
