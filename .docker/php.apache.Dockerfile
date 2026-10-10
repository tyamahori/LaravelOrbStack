ARG PHP_VERSION
ARG PHP_DIGEST
ARG COMPOSER_VERSION
ARG COMPOSER_DIGEST
FROM golang:1.27.2-bookworm@sha256:5cf287a799e6b94384bad13d16b14904c531f51ba65792237e122ce42b392f61 AS go
FROM composer:${COMPOSER_VERSION}@${COMPOSER_DIGEST} AS composer
FROM mlocati/php-extension-installer:2.12.0@sha256:1afade3e29cfc97362cf5885e5ac333bf2faab1146cb28ebbb59b17e68f87e88 AS basephpextensioninstaller
FROM php:${PHP_VERSION}-apache@${PHP_DIGEST} AS apachephp

FROM go AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.54.0

FROM go AS psqldef
RUN go install github.com/sqldef/sqldef/v3/cmd/psqldef@v3.11.26

FROM apachephp AS basebuild
COPY --from=basephpextensioninstaller /usr/bin/install-php-extensions /usr/local/bin/install-php-extensions
# DL3008: Debian security updates replace a pinned apt version and break the build; the base image is pinned by digest instead.
# hadolint ignore=DL3008
RUN apt-get update \
    && apt-get install -yq --no-install-recommends git postgresql-client unzip \
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
# With a digest, Docker ignores the tag; fail here if the digest no longer matches the versions.
ARG PHP_VERSION
ARG COMPOSER_VERSION
RUN case "$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')" in \
        "${PHP_VERSION}") ;; \
        *) echo "phpApacheDigest in composer.json is not PHP ${PHP_VERSION}" >&2; exit 1 ;; \
    esac \
    && case "$(composer --version)" in \
        "Composer version ${COMPOSER_VERSION} "*) ;; \
        *) echo "composerDigest in composer.json is not Composer ${COMPOSER_VERSION}" >&2; exit 1 ;; \
    esac

FROM basebuild AS local
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
# hadolint ignore=DL3008
RUN apt-get update \
    && apt-get install -yq --no-install-recommends \
      dnsutils \
      iproute2 \
      iputils-ping \
      less \
      openssh-client \
      vim \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -lom -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && install -d -o ${USER_NAME} -g ${USER_NAME} /config/psysh /composer /var/www/html \
    && install-php-extensions xdebug
ENV APACHE_RUN_USER=${USER_NAME} \
    APACHE_RUN_GROUP=${USER_NAME} \
    APACHE_LOG_DIR=/var/www/html/storage/logs
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=psqldef /go/bin/psqldef /usr/bin/psqldef
USER ${USER_NAME}

FROM basebuild AS flyio
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -lom -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
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
