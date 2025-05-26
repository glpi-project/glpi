SHELL=bash

COMPOSE = docker compose

PHP = $(COMPOSE) exec app
PHP_ROOT = $(COMPOSE) exec --user=root app
DB = $(COMPOSE) exec db
CONSOLE = $(PHP) bin/console
INI_DIR = /usr/local/etc/php/custom_conf.d

# Helper variables
_TITLE := "\033[32m[%s]\033[0m %s\n" # Green text
_ERROR := "\033[31m[%s]\033[0m %s\n" # Red text

##
## This Makefile is used for *local development* only.
## Production or deployment should be handled following GLPI's documentation.
##

## —— General ——————————————————————————————————————————————————————————————————
.DEFAULT_GOAL := help
help: ## Show this help message
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-25s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help

install: init-override build up vendor db-install test-db-install ## Install the project
.PHONY: install

## —— Docker ———————————————————————————————————————————————————————————————————
init-override:
	@\
	if [ ! -f "./docker-compose.override.yaml" ]; then \
		printf $(_TITLE) "Project" "Creating \"./docker-compose.override.yaml\" file for Docker Compose" ; \
		touch ./docker-compose.override.yaml ; \
	fi ;
.PHONY: init-override

build: ## Build the Docker images
	@printf $(_TITLE) "Project" "Pulling Docker images" \
	@$(COMPOSE) pull
	@$(COMPOSE) build --no-cache
.PHONY: build

up: ## Start all containers
	@$(COMPOSE) up -d
.PHONY: start

down: ## Stop the containers
	@$(COMPOSE) down --remove-orphans
.PHONY: stop

kill: ## Stop the containers and remove the volumes (use with caution)
	@$(COMPOSE) kill
	@$(COMPOSE) down --volumes --remove-orphans
.PHONY: kill

bash: ## Start a shell inside the php container
	@$(PHP) bash
.PHONY: bash

sql: ## Enter the database cli
	@$(DB) mariadb -D glpi -u glpi -pglpi
.PHONY: sql

## —— GLPI commands ————————————————————————————————————————————————————————————
console: ## Run a console command, example: make console c='glpi:mycommand'
	@$(eval c ?=)
	@$(CONSOLE) $(c)
.PHONY: console

vendor: c=dependencies install ## Install dependencies
vendor: console
.PHONY: vendor

locales: c=locales:compile ## Compile locales
locales: console
.PHONY: locales

cc: c=cache:clear ## Clear the cache
cc: console
.PHONY: cc

## —— Database —————————————————————————————————————————————————————————————————
db-install: ## Install local development's database
	@$(CONSOLE) database:install \
		-r -f \
		--db-host=db \
		--db-port=3306 \
		--db-name=glpi \
		--db-user=root \
		--db-password=glpi \
		--no-interaction \
		--no-telemetry
.PHONY: db-install

db-update: ## Update local development's database
	@$(CONSOLE) database:update \
		-n \
		--allow-unstable \
		--force \
		--skip-db-checks
.PHONY: db-update

test-db-install: ## Install testing's database
	@$(CONSOLE) database:install \
		-r -f \
		--db-host=db \
		--db-port=3306 \
		--db-name=glpi_test \
		--db-user=root \
		--db-password=glpi \
		--no-interaction \
		--no-telemetry \
		--config-dir=./tests/config
.PHONY: test-db-install

test-db-update: ## Update testing's database
	@$(CONSOLE) database:update \
		-n \
		--allow-unstable \
		--force \
		--skip-db-checks
.PHONY: db-update

## —— Dependencies —————————————————————————————————————————————————————————————
composer: ## Run a composer command, example: make composer c='require mypackage/package'
	@$(eval c ?=)
	@$(PHP) composer $(c)
.PHONY: composer

npm: ## Run a npm command, example: make npm c='install mypackage/package'
	@$(eval c ?=)
	@$(PHP) npm $(c)
.PHONY: npm

## —— Testing and static analysis ——————————————————————————————————————————————
phpunit: ## Run phpunits tests, example: make phpunit c='phpunit/functional/Glpi/MySpecificTest.php'
	@$(eval c ?=)
	@$(PHP) php vendor/bin/phpunit $(c)
.PHONY: phpunit

phpstan: ## Run phpstan
	@$(PHP) php vendor/bin/phpstan --memory-limit=1G
.PHONY: phpstan

## —— Linters ——————————————————————————————————————————————————————————————————
lint: lint-php lint-scss lint-twig lint-js ## Run all linters
.PHONY: lint

lint-php: ## Run the php linter script
	@$(PHP) .github/actions/lint_php-lint.sh
.PHONY: lint-php

lint-scss: ## Run the scss linter script
	@$(PHP) .github/actions/lint_scss-lint.sh
.PHONY: lint-scss

lint-twig: ## Run the twig linter script
	@$(PHP) .github/actions/lint_twig-lint.sh
.PHONY: lint-twig

lint-js: ## Run the js linter script
	@$(PHP) .github/actions/lint_js-lint.sh
.PHONY: lint-js

## —— Xdebug ———————————————————————————————————————————————————————————————————
XDEBUG_FILE = xdebug-mode.ini

xdebug-off: ## Disable xdebug
	@$(PHP_ROOT) bash -c 'echo "xdebug.mode=off" > $(INI_DIR)/$(XDEBUG_FILE)'
	@$(PHP_ROOT) service apache2 reload
.PHONY: xdebug-off

xdebug-on: ## Enable xdebug
	@$(PHP_ROOT) bash -c 'echo "xdebug.mode=debug" > $(INI_DIR)/$(XDEBUG_FILE)'
	@$(PHP_ROOT) bash -c 'echo "xdebug.start_with_request=1" >> $(INI_DIR)/$(XDEBUG_FILE)'
	@$(PHP_ROOT) service apache2 reload
.PHONY: xdebug-on

xdebug-profile: ## Enable xdebug performance profiling
	@$(PHP_ROOT) bash -c 'echo "xdebug.mode=profile" > $(INI_DIR)/$(XDEBUG_FILE)'
	@$(PHP_ROOT) bash -c 'echo "xdebug.start_with_request=1" >> $(INI_DIR)/$(XDEBUG_FILE)'
	@$(PHP_ROOT) service apache2 reload
.PHONY: xdebug-profile
