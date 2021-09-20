.PHONY: default up bg down test clean bash

DOCKER_COMPOSE = USERID=$(shell id -u) GID=$(shell id -g) docker compose --env-file=.env
DOCKER_COMPOSE_RUN = $(DOCKER_COMPOSE) run --rm --user $(shell id -u):$(shell id -g) app
DOCKER_COMPOSE_UP = $(DOCKER_COMPOSE) up --force-recreate --build

default: bg

up: .env vendor/autoload.php
	$(DOCKER_COMPOSE_UP)

bg: .env vendor/autoload.php
	$(DOCKER_COMPOSE_UP) -d

down:
	$(DOCKER_COMPOSE) down

test: vendor/bin/phpunit
	vendor/bin/phpunit .

clean: down
	$(DOCKER_COMPOSE) rm

bash:
	docker exec -it --user root b_app_1 bash

.env:
	touch .env

vendor/autoload.php:
	$(DOCKER_COMPOSE_RUN) composer install --no-dev --no-cache

vendor/bin/phpunit:
	$(DOCKER_COMPOSE_RUN) composer install --dev --no-cache
