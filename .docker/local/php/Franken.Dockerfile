FROM dunglas/frankenphp:sha-b845670-php8.3

ENV COMPOSER_HOME=/root/composer \
    PATH=$COMPOSER_HOME/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes

# パッケージインストール
RUN apt-get update \
    && apt-get -y install \
        vim \
        dnsutils \
        iputils-ping \
        iproute2 \
        git \
        unzip \
        less \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
    && pecl install redis && docker-php-ext-enable redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install gd opcache intl zip bcmath

# MySQLを使えるように対応する
# MySQLをつかなわい場合は個々の記述を削除して良い
RUN apt-get -y install default-mysql-client \
    && docker-php-ext-install pdo_mysql

# Posgresqlを使えるように。
# Posgresqlをつかなわい場合は個々の記述を削除して良い
RUN docker-php-ext-install pdo_pgsql pgsql

# Xdebugを使えるようにしている
# 不要な場合は削除して良い
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && touch /tmp/xdebug.log \
    && chmod 777 /tmp/xdebug.log

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

ARG USER_ID
ARG GROUP_ID
ARG USER_NAME

# ローカルユーザーをベースに新しくユーザー作成する
RUN groupadd -o -g ${GROUP_ID} ${USER_NAME} \
    && useradd -om -u ${USER_ID} -g ${GROUP_ID} ${USER_NAME} \
    && chown ${USER_NAME}:${USER_NAME} /app \
    && chown ${USER_NAME}:${USER_NAME} /data/caddy \
    && chown ${USER_NAME}:${USER_NAME} /config/caddy \
    && chown ${USER_NAME}:${USER_NAME} /etc/caddy

# マルチステージビルドにてcomposerを導入する
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer
