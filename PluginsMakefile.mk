# Create a file named "Makefile" in the root directory your plugin and add
# the following lines:
# PLUGIN_DIR = my_plugin_directory
# USE_COMPOSER = true|false (if your plugin require php dependencies)
# USE_NPM = true|false (if your plugin require js dependencies)
# include ../../PluginsMakefile.mk

SHELL=bash

COMPOSE = docker compose

PHP = $(COMPOSE) exec app
PLUGIN = $(COMPOSE) exec -w /var/www/glpi/plugins/$(PLUGIN_DIR) app
DB = $(COMPOSE) exec db
CONSOLE = $(PHP) bin/console

##
##This Makefile is used for *local development* only.
##Production or deployment should be handled following GLPI's documentation.
##

##—— General ———————————————————————————————————————————————————————————————————
.DEFAULT_GOAL := help
help: ## Show this help message
	@grep -E -h '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-25s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help

bash: ## Start a shell inside the php container, in the plugin directory
	@$(PLUGIN) bash
.PHONY: bash

##—— Plugin actions ————————————————————————————————————————————————————————————
plugin-install: ## Install the plugin
	@$(CONSOLE) plugin:install $(PLUGIN_DIR) -u glpi
.PHONY: plugin-install

plugin-uninstall: ## Uninstall the plugin
	@$(CONSOLE) plugin:uninstall $(PLUGIN_DIR)
.PHONY: plugin-uninstall

plugin-enable: ## Enable the plugin
	@$(CONSOLE) plugin:enable $(PLUGIN_DIR)
.PHONY: plugin-enable

plugin-disable: ## Disable the plugin
	@$(CONSOLE) plugin:disable $(PLUGIN_DIR)
.PHONY: plugin-disable

plugin-test-setup: ## Setup the plugin for tests
	@$(CONSOLE) plugin:install --env=testing $(PLUGIN_DIR) -u glpi --force
	@$(CONSOLE) plugin:enable --env=testing $(PLUGIN_DIR)
.PHONY: plugin-test-setup

plugin-licence-headers-check: ## Verify that the licence headers is present all files
	@$(PLUGIN) vendor/bin/licence-headers-check
.PHONY: plugin-licence-headers-check

plugin-licence-headers-fix: ## Add the missing licence headers in all files
	@$(PLUGIN) vendor/bin/licence-headers-check --fix
.PHONY: plugin-licence-headers-fix

##—— Dependencies ——————————————————————————————————————————————————————————————
vendor: ## Install dependencies
ifeq ($(USE_COMPOSER),true)
	@$(PLUGIN) composer install
endif
ifeq ($(USE_NPM),true)
	@$(PLUGIN) npm install --dev
endif
.PHONY: vendor

composer: ## Run a composer command, example: make composer c='require mypackage/package'
	@$(eval c ?=)
	@$(PLUGIN) composer $(c)
.PHONY: composer

## —— Testing and static analysis ——————————————————————————————————————————————
phpunit: ## Run phpunits tests, example: make phpunit c='phpunit/functional/Glpi/MySpecificTest.php'
	@$(eval c ?=)
	@$(PHP) php vendor/bin/phpunit -c /var/www/glpi/plugins/$(PLUGIN_DIR)/phpunit.xml $(c)
.PHONY: phpunit

phpstan: ## Run phpstan
	@$(eval c ?=)
	@$(PLUGIN) php vendor/bin/phpstan --memory-limit=1G $(c)
.PHONY: phpstan
# TODO: maybe we should reuse the binary from the main dir like phpunit?

## —— Coding standards —————————————————————————————————————————————————————————
phpcsfixer-check: ## Check for php coding standards issues
	@$(PLUGIN) vendor/bin/php-cs-fixer check --diff -vvv
.PHONY: phpcsfixer-check
# TODO: maybe we should reuse the binary from the main dir like phpunit?

phpcsfixer-fix: ## Fix php coding standards issues
	@$(PLUGIN) vendor/bin/php-cs-fixer fix
.PHONY: phpcsfixer-fix
# TODO: maybe we should reuse the binary from the main dir like phpunit?
