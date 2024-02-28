FROM php:8.3-apache

ARG USER_ID
ARG GROUP_ID
ARG USER_NAME

ENV APACHE_RUN_USER=${USER_NAME} \
    APACHE_RUN_GROUP=${USER_NAME}

# ローカルユーザーをベースに新しくユーザー作成する
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /var/www/html \
    && mkdir -p /project/composer \
    && chown ${USER_NAME}:${USER_NAME} /project/composer

ENV COMPOSER_HOME=/project/composer \
    PATH=$COMPOSER_HOME/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes \
    APACHE_LOG_DIR=/var/log/apache2

COPY --from=mlocati/php-extension-installer:2.2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# パッケージインストール
RUN apt-get update \
    && apt-get install -yq git vim dnsutils iputils-ping iproute2 default-mysql-client unzip \
    && install-php-extensions redis gd opcache intl zip bcmath pdo_mysql pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Xdebugを使えるようにしている
# 不要な場合は削除して良い
RUN install-php-extensions xdebug

RUN a2enmod rewrite headers

USER ${USER_NAME}
