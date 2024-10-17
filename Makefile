SHELL=bash

COMPOSE = docker compose

PHP = $(COMPOSE) exec app
CONSOLE = $(PHP) bin/console

# Helper variables
_TITLE := "\033[32m[%s]\033[0m %s\n" # Green text
_ERROR := "\033[31m[%s]\033[0m %s\n" # Red text

##
## This Makefile is used for *local development* only.
## Production or deployment should be handled following GLPI's documentation.
##

.DEFAULT_GOAL := help
help: ## Show this help message
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-25s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help

install: .env build start vendor db test-db ## Install the project
.PHONY: install

.env:
	@\
	if [ ! -f ".devcontainer/docker-compose.override.yaml" ]; then \
		printf $(_TITLE) "Project" "Creating \".devcontainer/docker-compose.override.yaml\" file for Docker Compose" ; \
		touch .devcontainer/docker-compose.override.yaml ; \
	fi ; \
	if [ ! -f ".env" ]; then \
		printf $(_TITLE) "Project" "Creating \".env\" file for Docker Compose" ; \
		touch .env ; \
	fi ; \
	if grep -q COMPOSE_FILE ".env"; then \
		printf $(_TITLE) "Project" "\".env\" file is already populated" ; \
	else \
		printf $(_TITLE) "Project" "Writing config to \".env\" file" ; \
		echo "COMPOSE_FILE=./.devcontainer/docker-compose.yaml:./.devcontainer/docker-compose.override.yaml" >> .env ; \
    fi

.PHONY: .env

build: ## Build the Docker images
	@printf $(_TITLE) "Project" "Pulling Docker images" \
	@$(COMPOSE) pull
	@$(COMPOSE) build
.PHONY: build

start: ## Start all containers
	@$(COMPOSE) up -d --remove-orphans
.PHONY: start

stop: ## Stop all containers
	@$(COMPOSE) stop
.PHONY: stop

restart: ## Restart the containers & the PHP server
	@$(MAKE) stop
	@$(MAKE) start
.PHONY: restart

vendor: ## Install Composer dependencies
	@$(CONSOLE) dependencies install
.PHONY: vendor

locales: ## Compile locales
	@$(CONSOLE) locales:compile
.PHONY: locales

kill: ## Stop and remove all containers
	@$(DOCKER_COMPOSE) kill
	@$(DOCKER_COMPOSE) down --volumes --remove-orphans
.PHONY: kill

reset: ## Reset and start a fresh install of the project
reset: kill install
.PHONY: reset

db: ## Install local development's database
	@$(CONSOLE) database:install \
		-r -f \
		--db-host=db \
		--db-port=3306 \
		--db-name=glpi \
		--db-user=root \
		--db-password=glpi \
		--no-interaction \
		--no-telemetry
.PHONY: db

test-db: ## Install automated testing's database
	@$(CONSOLE) database:install \
		-r -f \
		--db-host=db \
		--db-port=3306 \
		--db-name=glpi_test \
		--db-user=root \
		--db-password=glpi \
		--no-interaction \
		--no-telemetry \
		--env=testing
.PHONY: test-db

cc: ## Clear the cache
	@$(CONSOLE) cache:clear
.PHONY: cc
