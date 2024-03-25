FROM golang:alpine as build

RUN go install github.com/go-task/task/v3/cmd/task@latest

FROM php:8.3-apache as base

ARG USER_ID
ARG GROUP_ID
ARG USER_NAME

# ローカルユーザーをベースに新しくユーザー作成する
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /var/www/html \
    && mkdir /composer \
    && chown ${USER_NAME}:${USER_NAME} /composer

ENV COMPOSER_HOME=/composer \
    PATH=$COMPOSER_HOME/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes \
    APACHE_LOG_DIR=/var/www/html/storage/logs \
    APACHE_RUN_USER=${USER_NAME} \
    APACHE_RUN_GROUP=${USER_NAME}

COPY --from=mlocati/php-extension-installer:2.2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=build /go/bin/task /usr/bin/task

# パッケージインストール
RUN apt-get update \
    && apt-get install -yq git vim dnsutils iputils-ping iproute2 default-mysql-client unzip \
    && install-php-extensions redis gd opcache intl zip bcmath pdo_mysql pdo_pgsql pgsql @composer-2 \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers

FROM base as local
RUN install-php-extensions xdebug
USER ${USER_NAME}

FROM base as ci
USER ${USER_NAME}
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
RUN composer install

FROM base as prod
USER ${USER_NAME}
COPY --chown=${USER_NAME}:${USER_NAME} . /var/www/html/
RUN composer install -q -n --no-ansi --no-dev --no-scripts --no-progress --prefer-dist
