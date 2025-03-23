ARG GO_DOCKER_IMAGE_VERSION=1.24.0-bookworm

FROM golang:${GO_DOCKER_IMAGE_VERSION} AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.41.0

FROM golang:${GO_DOCKER_IMAGE_VERSION} AS purl
RUN go install github.com/catatsuy/purl@v0.0.6

FROM golang:${GO_DOCKER_IMAGE_VERSION} AS runn
RUN go install github.com/k1LoW/runn/cmd/runn@v0.129.0

FROM golang:${GO_DOCKER_IMAGE_VERSION} AS mysqldef
RUN go install github.com/sqldef/sqldef/cmd/mysqldef@v1.0.5

FROM golang:${GO_DOCKER_IMAGE_VERSION} AS psqldef
RUN go install github.com/sqldef/sqldef/cmd/psqldef@v1.0.5

FROM php:8.4.5-apache AS commonphp

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
    && a2enmod rewrite headers ssl && a2dismod status && a2dismod info

ENV COMPOSER_HOME=/composer \
    PATH=/composer/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes \
    APACHE_RUN_USER=${USER_NAME} \
    APACHE_RUN_GROUP=${USER_NAME}

ARG PHP_EXTTENSION_INSTALLER_VERSION=2.7.24
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/${PHP_EXTTENSION_INSTALLER_VERSION}/install-php-extensions /usr/local/bin/
RUN install-php-extensions redis gd opcache intl zip bcmath pdo_pgsql pgsql

ARG COMPOSER_VERSION=2.8.5

FROM commonphp AS local
ENV APACHE_LOG_DIR=/var/www/html/storage/logs
RUN apt-get update \
    && apt-get install -yq dnsutils iproute2 iputils-ping vim  \
    && install-php-extensions xdebug \
    && apt-get clean  \
    && rm -rf /var/lib/apt/lists/*
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=runn /go/bin/runn /usr/bin/runn
RUN install-php-extensions @composer-${COMPOSER_VERSION}
USER ${USER_NAME}

FROM commonphp AS ci
RUN install-php-extensions xdebug @composer-${COMPOSER_VERSION}
USER ${USER_NAME}

FROM commonphp AS develop
RUN install-php-extensions @composer-${COMPOSER_VERSION}
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
RUN install-php-extensions @composer-${COMPOSER_VERSION}
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
RUN install-php-extensions @composer-${COMPOSER_VERSION}
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/ports.conf /etc/apache2/ports.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/conmon/security.conf /etc/apache2/conf-available/security.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/php.ini /usr/local/etc/php/php.ini
USER ${USER_NAME}
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear
EXPOSE 8080
