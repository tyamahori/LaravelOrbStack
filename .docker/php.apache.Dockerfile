FROM golang:1.25.2-bookworm@sha256:42d8e9dea06f23d0bfc908826455213ee7f3ed48c43e287a422064220c501be9 AS go
FROM composer:2.8.12@sha256:3ca62a8176c743eebef305ac2b93094a733dd5a34b5c1e3d3cf6cbbbd0792649 AS composer
FROM mlocati/php-extension-installer:2.9.13@sha256:f07adf63f4458e6f8d2774b62a34dde7990ef57d9c2cad21d13df61885475350 AS basephpextensioninstaller
FROM php:8.4.13-apache@sha256:30cf37c4a2680da1f7c00e044eb515dda1a875f4a553384a1289bf5e80acd40b AS apachephp

FROM go AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.45.4

FROM go AS runn
RUN go install github.com/k1LoW/runn/cmd/runn@v0.139.0

FROM go AS mysqldef
RUN go install github.com/sqldef/sqldef/cmd/mysqldef@v3.1.15

FROM go AS psqldef
RUN go install github.com/sqldef/sqldef/cmd/psqldef@v3.1.15

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
