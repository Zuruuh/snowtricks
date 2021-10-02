##
## Snowtricks

##
## -------
## Project
## -------
##

DOCKER_COMPOSE  = docker-compose

EXEC_PHP        = $(DOCKER_COMPOSE) exec -T php /entrypoint
EXEC_JS         = $(DOCKER_COMPOSE) exec -T node /entrypoint

SYMFONY         = $(EXEC_PHP) bin/console
COMPOSER        = $(EXEC_PHP) composer
YARN            = $(EXEC_JS) yarn

build:
	$(DOCKER_COMPOSE) pull --parallel --quiet --ignore-pull-failures 2> /dev/null
	$(DOCKER_COMPOSE) build --pull

kill:
	$(DOCKER_COMPOSE) kill
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

install: ## Install and start the project
install: build docker-start assets db

reset: ## Stop and start a fresh install of the project
reset: kill install

docker-start:
	$(DOCKER_COMPOSE) up -d --remove-orphans --no-recreate

docker-stop:
	$(DOCKER_COMPOSE) stop

##
## -----
## Utils
## -----
##

db: ## Setup local database and load fake data
db: .env vendor
	-$(SYMFONY) doctrine:database:drop --if-exists --force
	-$(SYMFONY) doctrine:database:create --if-exists --force
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(SYMFONY) doctrine:fixtures:load --no-interaction --purge-with-truncate

migration: ## Create a new doctrine migration
migration: vendor
	$(SYMFONY) doctrine:migrations:diff

db-validate-schema: ## Validate the database schema
db-validate-schema: .env vendor
	$(SYMFONY) doctrine:schema:validate

assets: ## Compile assets using Webpack Encore
assets: node_modules
	$(YARN) run dev

watch: ## Run Webpack Encore in watch mode
watch: node_modules
	$(YARN) run watch

composer.lock: composer.json
	$(COMPOSER) update

vendor: composer.lock
	$(COMPOSER) install

yarn.lock: package.json
	$(YARN) upgrade

node_modules: yarn.lock
	$(YARN) install

.env: .env.local
	@if [ -f .env]; \
	then\
		echo "The .env.local file has changed. Please check your .env file (this message will be displayed only once !)";\
		touch .env;\
		exit 1;\
	else\
		echo cp .env.local .env;\
		cp .env.local .env;\
	fi

.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## ----------------------------

.PHONY: help
.PHONY: install
.PHONY: db migration assets watch