#!/bin/bash
set -euxo pipefail

USER_NAME=$(whoami)
USER_ID=$(id -u)
GROUP_ID=$(id -g)

case "$1" in

"stan")
    # 指定したPHPファイルをPHPStanにかけるコマンド
     USER_NAME="$USER_NAME" \
     USER_ID="$USER_ID" \
     GROUP_ID="$GROUP_ID" \
     docker compose -f .docker/local/compose.yaml exec -i php-app ./vendor/bin/phpstan analyse -c phpstan.neon "${@:2}" --memory-limit=1G
    ;;

"fixer")
    # save時にphp cs fixerをコンテナ内部で動かすコマンド
     USER_NAME="$USER_NAME" \
     USER_ID="$USER_ID" \
     GROUP_ID="$GROUP_ID" \
     docker compose -f .docker/local/compose.yaml exec -i php-app ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --path-mode=intersection "${@:2}"
    ;;

esac
