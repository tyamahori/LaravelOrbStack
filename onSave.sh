#!/bin/bash
set -euxo pipefail

PROJECT_NAME=laravelorbstack
USER_NAME=$(whoami)
USER_ID=$(id -u)
GROUP_ID=$(id -g)

case "$1" in

"stan")
    # 指定したPHPファイルをPHPStanにかけるコマンド
    COMPOSE_PROJECT_NAME="$PROJECT_NAME"\
     USER_NAME="$USER_NAME" \
     USER_ID="$USER_ID" \
     GROUP_ID="$GROUP_ID" \
     docker compose -f .docker/local/compose.yaml exec -i php-app ./vendor/bin/phpstan analyse -c phpstan.neon "${@:2}"
    ;;

"fixer")
    # save時にphp cs fixerをコンテナ内部で動かすコマンド
    COMPOSE_PROJECT_NAME="$PROJECT_NAME"\
     USER_NAME="$USER_NAME" \
     USER_ID="$USER_ID" \
     GROUP_ID="$GROUP_ID" \
     docker compose -f .docker/local/compose.yaml exec -i php-app ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php "${@:2}"
    ;;

esac
