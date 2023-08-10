SHELL=/bin/bash -euxo pipefail
.DEFAULT_GOAL := help

USER_NAME := $(shell whoami)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)
PROJECT_NAME := laravelorbstack

COMPOSE_BASE_COMMAND := \
  COMPOSE_PROJECT_NAME=$(PROJECT_NAME) \
  USER_ID=$(USER_ID) \
  GROUP_ID=$(GROUP_ID) \
  USER_NAME=$(USER_NAME) \
  docker compose -f ./.docker/local/compose.yaml

.PHONY: help
help: # @see https://postd.cc/auto-documented-makefile/
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: init
init: delete-all rm-vendor ## プロジェクトのすべてを削除してから、セットアップする
	$(COMPOSE_BASE_COMMAND) up -d
	$(COMPOSE_BASE_COMMAND) run --rm minio-bucket
	$(COMPOSE_BASE_COMMAND) exec -it php-app composer install
	$(COMPOSE_BASE_COMMAND) exec -it php-app php artisan optimize:clear
	$(COMPOSE_BASE_COMMAND) exec -it php-app php artisan migrate:refresh --seed

.PHONY: up
up: down ## docker compose up
	$(COMPOSE_BASE_COMMAND) up -d
	$(COMPOSE_BASE_COMMAND) ps -a

.PHONY: down
down: ## docker compose down
	$(COMPOSE_BASE_COMMAND) down

.PHONY: clean-up
clean-up: delete-volume ## volumeを削除して docker compose up
	$(COMPOSE_BASE_COMMAND) up -d
	$(COMPOSE_BASE_COMMAND) ps -a

.PHONY: delete-all
delete-all: ## docker compose down -v --rmi all --remove-orphans
	$(COMPOSE_BASE_COMMAND) down -v --rmi all --remove-orphans

.PHONY: delete-volume
delete-volume: ## docker compose down -v
	$(COMPOSE_BASE_COMMAND) down -v

.PHONY: rm-vendor
rm-vendor: ## rm -rf ./vendor
	rm -rf ./vendor

.PHONY: ps
ps: ## docker compose ps
	$(COMPOSE_BASE_COMMAND) ps -a

.PHONY: logs-all
logs-all: ## docker compose logs -f
	$(COMPOSE_BASE_COMMAND) logs -f

.PHONY: logs-php-app
logs-php-app: ## docker compose logs php-app -f
	$(COMPOSE_BASE_COMMAND) logs php-app -f

.PHONY: logs-php-worker
logs-php-worker: ## docker compose logs php-worker -f
	$(COMPOSE_BASE_COMMAND) logs php-worker -f

.PHONY: logs-php-batch
logs-php-batch: ## docker compose logs php-batch -f
	$(COMPOSE_BASE_COMMAND) logs php-batch -f

.PHONY: minio-bucket
minio-bucket: ## create bucket for minio
	$(COMPOSE_BASE_COMMAND) run --rm minio-bucket

.PHONY: exec-php-app-as-user
exec-php-app-as-user: ## APP PHPのコンテナに通常ユーザーとして入る
	$(COMPOSE_BASE_COMMAND) exec -it php-app bash

.PHONY: exec-php-app-as-root
exec-php-app-as-root: ## APP PHPのコンテナにrootユーザーとして入る
	$(COMPOSE_BASE_COMMAND) exec -u root -it php-app bash

.PHONY: exec-php-worker-as-user
exec-php-worker-as-user: ## WORKER PHPのコンテナに通常ユーザーとして入る
	$(COMPOSE_BASE_COMMAND) exec -it php-worker bash

.PHONY: exec-php-worker-as-root
exec-php-worker-as-root: ## WORKER PHPのコンテナにrootユーザーとして入る
	$(COMPOSE_BASE_COMMAND) exec -u root -it php-worker bash

.PHONY: exec-php-batch-as-user
exec-php-batch-as-user: ## BATCH PHPのコンテナに通常ユーザーとして入る
	$(COMPOSE_BASE_COMMAND) exec -it php-batch bash

.PHONY: exec-php-batch-as-root
exec-php-batch-as-root: ## BATCH PHPのコンテナにrootユーザーとして入る
	$(COMPOSE_BASE_COMMAND) exec -u root -it php-batch bash
