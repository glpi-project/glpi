# Create a file named "Makefile" in the root directory of your plugin and add
# the following line:
# include ../../PluginsMakefile.mk

# Shell to use
SHELL=bash

# Current plugin directory
PLUGIN_DIR = $(shell pwd | xargs basename)

# Check if composer and npm are used
USE_COMPOSER = $(shell test -f composer.json && echo true || echo false)
USE_NPM = $(shell test -f package.json && echo true || echo false)

# Docker commands
COMPOSE = docker compose
PHP = $(COMPOSE) exec app
PLUGIN = $(COMPOSE) exec -w /var/www/glpi/plugins/$(PLUGIN_DIR) app
DB = $(COMPOSE) exec db
CONSOLE = $(PHP) bin/console

# Check which binaries we need to use for some tools that can be suplied by
# either GLPI's core or the plugin itself.
PHPSTAN_BIN    = $(shell test -f vendor/bin/phpstan      && echo vendor/bin/phpstan      || echo ../../vendor/bin/phpstan)
PHPUNIT_BIN    = $(shell test -f vendor/bin/phpunit      && echo vendor/bin/phpunit      || echo ../../vendor/bin/phpunit)
RECTOR_BIN     = $(shell test -f vendor/bin/rector       && echo vendor/bin/rector       || echo ../../vendor/bin/rector)
PSALM_BIN      = $(shell test -f vendor/bin/psalm        && echo vendor/bin/psalm        || echo ../../vendor/bin/psalm)
PHPCSFIXER_BIN = $(shell test -f vendor/bin/php-cs-fixer && echo vendor/bin/php-cs-fixer || echo ../../vendor/bin/php-cs-fixer)
PARALLEL-LINT_BIN = $(shell test -f vendor/bin/parallel-lint && echo vendor/bin/parallel-lint || echo ../../vendor/bin/parallel-lint)

##
##This Makefile is used for *local development* only.
##Production or deployment should be handled following GLPI's documentation.
##

# helper: $(call run_if_exists,<file>,<target>)
define run_if_exists
  $(if $(wildcard $1), \
    (echo -e "\033[36mRunning $2...\033[m" && $(MAKE) --no-print-directory $2), \
    echo -e "\033[43mSkipping $2: $1 not found\033[m")
endef

# helper: $(call run_always,<target>)
define run_always
  (echo -e "\033[36mRunning $1...\033[m" && $(MAKE) --no-print-directory $1)
endef

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

locales-extract: ## Extract locales
	@$(PLUGIN) vendor/bin/extract-locales
.PHONY: locales-extract

locales-compile: ## Compile locales
	@$(PLUGIN) vendor/bin/plugin-release --compile-mo
.PHONY: locales-compile

##—— Licenses  —————————————————————————————————————————————————————————————————
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

npm: ## Run a npm command, example: make npm c='install mypackage/package'
	@$(eval c ?=)
	@$(PLUGIN) npm $(c)
.PHONY: npm

##—— Testing and static analysis ———————————————————————————————————————————————
test:  ## Run all our lints/tests/static analysis
	@$(call run_if_exists, tools/HEADER, license-headers-check)
	@$(call run_always, parallel-lint)
	@$(call run_if_exists, .php-cs-fixer.php, phpcsfixer-check)
	@$(call run_if_exists, rector.php, rector-check)
	@$(call run_if_exists, phpstan.neon, phpstan)
	@$(call run_if_exists, psalm.xml, psalm)
	@$(call run_if_exists, phpunit.xml, phpunit)
.PHONY: test

phpunit: ## Run phpunits tests, example: make phpunit c='tests/functional/Glpi/MySpecificTest.php'
	@$(eval c ?=)
	@$(PLUGIN) php $(PHPUNIT_BIN) $(c)
.PHONY: phpunit

phpstan: ## Run phpstan
	@$(eval c ?=)
	@$(PLUGIN) php $(PHPSTAN_BIN) --memory-limit=1G $(c)
.PHONY: phpstan

parallel-lint: ## Check php syntax with parallel-lint
	@$(eval c ?=.)
	$(PLUGIN) php $(PARALLEL-LINT_BIN) \
		--show-deprecated \
		--colors \
		--exclude ./lib/ \
		--exclude ./node_modules/ \
		--exclude ./vendor/ \
		$(c)
.PHONY: parallel-lint

psalm: ## Run psalm analysis
	@$(eval c ?=)
	@$(PLUGIN) php $(PSALM_BIN) $(c)
.PHONY: psalm

rector-check: ## Run rector with dry run
	@$(eval c ?=)
	@$(PLUGIN) php $(RECTOR_BIN) --dry-run $(c)
.PHONY: rector

rector-apply: ## Run rector
	@$(eval c ?=)
	@$(PLUGIN) php $(RECTOR_BIN) $(c)
.PHONY: rector-apply

##—— Coding standards ——————————————————————————————————————————————————————————
phpcsfixer-check: ## Check for php coding standards issues
	@$(PLUGIN) $(PHPCSFIXER_BIN) check --diff -vvv
.PHONY: phpcsfixer-check

phpcsfixer-fix: ## Fix php coding standards issues
	@$(PLUGIN) $(PHPCSFIXER_BIN) fix
.PHONY: phpcsfixer-fix
