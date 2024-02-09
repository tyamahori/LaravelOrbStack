FROM dunglas/frankenphp:sha-b845670-php8.3

ENV COMPOSER_HOME=/root/composer \
    PATH=$COMPOSER_HOME/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBCONF_NOWARNINGS=yes

# マルチステージビルドにてcomposerを導入する
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version=2.7.0
COPY --from=mlocati/php-extension-installer:2.2 /usr/bin/install-php-extensions /usr/local/bin/

# パッケージインストール
RUN apt-get update \
    && apt-get install -yq git vim dnsutils iputils-ping iproute2 default-mysql-client \
    && install-php-extensions redis gd opcache intl zip bcmath pdo_mysql pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Xdebugを使えるようにしている
# 不要な場合は削除して良い
RUN install-php-extensions xdebug && touch /tmp/xdebug.log && chmod 777 /tmp/xdebug.log

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
