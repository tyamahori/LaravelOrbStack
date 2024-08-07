FROM golang:1.22.4 AS task
RUN go install github.com/go-task/task/v3/cmd/task@v3.37.2

FROM golang:1.22.4 AS purl
RUN go install github.com/catatsuy/purl@v0.0.5

FROM golang:1.22.4 AS runn
RUN go install github.com/k1LoW/runn/cmd/runn@v0.112.3

FROM golang:1.22.4 AS mysqldef
RUN go install github.com/sqldef/sqldef/cmd/mysqldef@v0.17.11

FROM golang:1.22.4 AS psqldef
RUN go install github.com/sqldef/sqldef/cmd/psqldef@v0.17.11

FROM php:8.3.10-apache AS commonphp
ARG USER_ID
ARG GROUP_ID
ARG USER_NAME
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /var/www/html \
    && mkdir /composer \
    && chown ${USER_NAME}:${USER_NAME} /composer
ENV COMPOSER_HOME=/composer \
    PATH=$COMPOSER_HOME/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes \
    APACHE_RUN_USER=${USER_NAME} \
    APACHE_RUN_GROUP=${USER_NAME}
ARG PHP_EXTTENSION_INSTALLER_VERSION=2.2.16
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/${PHP_EXTTENSION_INSTALLER_VERSION}/install-php-extensions /usr/local/bin/
RUN apt-get update \
    && apt-get install -yq git vim dnsutils iputils-ping iproute2 default-mysql-client postgresql unzip \
    && install-php-extensions redis gd opcache intl zip bcmath pdo_mysql pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers

FROM commonphp AS local
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=purl /go/bin/purl /usr/bin/purl
COPY --from=runn /go/bin/runn /usr/bin/runn
COPY --from=mysqldef /go/bin/mysqldef /usr/bin/mysqldef
COPY --from=psqldef /go/bin/psqldef /usr/bin/psqldef
ENV APACHE_LOG_DIR=/var/www/html/storage/logs
RUN install-php-extensions xdebug @composer-2.7.7
USER ${USER_NAME}

FROM commonphp AS develop
USER ${USER_NAME}
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=purl /go/bin/purl /usr/bin/purl
COPY --from=runn /go/bin/runn /usr/bin/runn
COPY --from=mysqldef /go/bin/mysqldef /usr/bin/mysqldef
COPY --from=psqldef /go/bin/psqldef /usr/bin/psqldef
RUN install-php-extensions @composer-2.7.7 && \
    composer install && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear

FROM commonphp AS prod
ENV APACHE_LOG_DIR=/var/log/apache2
USER ${USER_NAME}
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/prod/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=psqldef /go/bin/psqldef /usr/bin/psqldef
RUN install-php-extensions @composer-2.7.7 && \
    composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear

FROM commonphp AS flyio
ENV APACHE_LOG_DIR=/var/log/apache2
USER ${USER_NAME}
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/ports.conf /etc/apache2/ports.conf
COPY --chown=${USER_NAME}:${USER_NAME} .docker/flyio/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
COPY --from=task /go/bin/task /usr/bin/task
COPY --from=psqldef /go/bin/psqldef /usr/bin/psqldef
RUN install-php-extensions @composer-2.7.7 && \
    composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist && \
    composer dump-autoload && \
    php artisan clear-compiled && \
    php artisan optimize:clear
EXPOSE 8080
