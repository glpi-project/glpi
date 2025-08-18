# Create a file named "Makefile" in the root directory of your plugin and add
# the following lines:
#
# PLUGIN_DIR = my_plugin_directory
#
# These two options will change the behavior of the `make vendor` command.
# USE_COMPOSER = true
# USE_NPM = false
#
# These options will change the behavior of several lint/static analysis commands.
# By default, you do not need to change anything as these binaries are provided by
# GLPI's core.
# You only need to set it to true if your plugin load its own binary
# in its vendor directory for one of these tools.
# USE_LOCAL_PHPUNIT_BIN = false
# USE_LOCAL_PHPSTAN_BIN = false
# USE_LOCAL_PSALM_BIN = false
# USE_LOCAL_RECTOR_BIN = false
# USE_LOCAL_PHPCSFIXER_BIN = false
#
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
install: ## Install the plugin
	@$(CONSOLE) plugin:install $(PLUGIN_DIR) -u glpi
.PHONY: install

uninstall: ## Uninstall the plugin
	@$(CONSOLE) plugin:uninstall $(PLUGIN_DIR)
.PHONY: uninstall

enable: ## Enable the plugin
	@$(CONSOLE) plugin:enable $(PLUGIN_DIR)
.PHONY: enable

disable: ## Disable the plugin
	@$(CONSOLE) plugin:disable $(PLUGIN_DIR)
.PHONY: disable

test-setup: ## Setup the plugin for tests
	@$(CONSOLE) plugin:install --env=testing $(PLUGIN_DIR) -u glpi --force
	@$(CONSOLE) plugin:enable --env=testing $(PLUGIN_DIR)
.PHONY: test-setup

license-headers-check: ## Verify that the license headers is present all files
	@$(PLUGIN) vendor/bin/licence-headers-check
.PHONY: license-headers-check

license-headers-fix: ## Add the missing license headers in all files
	@$(PLUGIN) vendor/bin/licence-headers-check --fix
.PHONY: license-headers-fix

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
ifeq ($(USE_LOCAL_PHPUNIT_BIN),true)
	@$(PLUGIN) php vendor/bin/phpunit $(c)
else
	@$(PHP) php vendor/bin/phpunit -c /var/www/glpi/plugins/$(PLUGIN_DIR)/phpunit.xml $(c)
endif
.PHONY: phpunit

phpstan: ## Run phpstan
	@$(eval c ?=)
ifeq ($(USE_LOCAL_PHPSTAN_BIN),true)
	@$(PLUGIN) php vendor/bin/phpstan --memory-limit=1G $(c)
else
	@$(PHP) php vendor/bin/phpstan --memory-limit=1G analyze -c /var/www/glpi/plugins/$(PLUGIN_DIR)/phpstan.neon $(c)
endif
.PHONY: phpstan

psalm: ## Run psalm analysis
	@$(eval c ?=)
ifeq ($(USE_LOCAL_PSALM_BIN),true)
	@$(PLUGIN) php vendor/bin/psalm $(c) $(c)
else
	@$(PHP) php vendor/bin/psalm -c /var/www/glpi/plugins/$(PLUGIN_DIR)/psalm.xml $(c)
endif
.PHONY: psalm

rector-check: ## Run rector with dry run
	@$(eval c ?=)
ifeq ($(USE_LOCAL_RECTOR_BIN),true)
	@$(PLUGIN) php vendor/bin/rector --dry-run $(c)
else
	@$(PHP) php vendor/bin/rector --config /var/www/glpi/plugins/$(PLUGIN_DIR)/rector.php --dry-run $(c)
endif
.PHONY: rector

rector-apply: ## Run rector
	@$(eval c ?=)
ifeq ($(USE_LOCAL_RECTOR_BIN),true)
	@$(PLUGIN) php vendor/bin/rector $(c)
else
	@$(PHP) php vendor/bin/rector --config /var/www/glpi/plugins/$(PLUGIN_DIR)/rector.php $(c)
endif
.PHONY: rector-apply

## —— Coding standards —————————————————————————————————————————————————————————
phpcsfixer-check: ## Check for php coding standards issues
ifeq ($(USE_LOCAL_PHPCSFIXER_BIN),true)
	@$(PLUGIN) vendor/bin/php-cs-fixer check --diff -vvv
else
	@$(PHP) php vendor/bin/php-cs-fixer check --config /var/www/glpi/plugins/$(PLUGIN_DIR)/.php-cs-fixer.php --diff -vvv
endif
.PHONY: phpcsfixer-check

phpcsfixer-fix: ## Fix php coding standards issues
ifeq ($(USE_LOCAL_PHPCSFIXER_BIN),true)
	@$(PLUGIN) vendor/bin/php-cs-fixer fix
else
	@$(PHP) php vendor/bin/php-cs-fixer fix --config /var/www/glpi/plugins/$(PLUGIN_DIR)/.php-cs-fixer.php
endif
.PHONY: phpcsfixer-fix
