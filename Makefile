CONTAINER-NAME=snaapi-php
VAR_NAME_IS_DOCKER=CONTAINER_NAME
DOCKER=docker run --env-file .docker.env --rm -it --user www-data --entrypoint "" -v "./:/var/www/service" $(CONTAINER-NAME)
DOCKER_EXEC=docker exec -it --user www-data $(CONTAINER-NAME)
DOCKER_DOT_ENV = $(shell echo $(PWD)/.docker.env)
ORIGINAL_DOT_ENV ::= $(shell echo $(PWD)/docker/.env.dist)
$(eval USER_ID = $(shell id -u))
$(eval GROUP_ID = $(shell id -g))
ifeq ($(origin $(VAR_NAME_IS_DOCKER)), undefined)
    PHP=$(shell docker exec -it --user www-data $(CONTAINER-NAME) which php)
    COMPOSER=$(shell docker exec -it --user www-data $(CONTAINER-NAME) which composer)
else
		PHP=$(shell which php)
    COMPOSER=$(shell which composer)
endif

.PHONY: prueba audit tests test_cs test_yaml test_container test_unit test_stan cli install_vendor

tests: test_cs test_yaml test_container test_unit test_stan test_infection

audit:
	@if [ -z $${$(VAR_NAME_IS_DOCKER)+x} ]; then \
  		$(DOCKER_EXEC) $(COMPOSER) audit; \
  	else \
			$(COMPOSER) audit; \
  fi
test_unit:
	@echo execute TEST_UNIT;
	@if [ -z $${$(VAR_NAME_IS_DOCKER)+x} ]; then \
    	$(DOCKER_EXEC) ./bin/phpunit; \
    else \
    	./bin/phpunit; \
  fi
test_cs:
	@echo execute TEST_CS;
	@if [ -z $${$(VAR_NAME_IS_DOCKER)+x} ]; then \
    	$(DOCKER_EXEC) ./bin/php-cs-fixer fix; \
    else \
			./bin/php-cs-fixer fix; \
  fi
test_stan:
	@echo execute TEST_STAN;
	@if [ -z $${$(VAR_NAME_IS_DOCKER)+x} ]; then \
    	$(DOCKER_EXEC) ./bin/phpstan --memory-limit=256M analyze -c ./phpstan.neon; \
    else \
			./bin/phpstan --memory-limit=256M analyze -c ./phpstan.neon; \
  fi
test_yaml:
	@echo execute LINT_YAML;
	@if [ -z $${$(VAR_NAME_IS_DOCKER)+x} ]; then \
	  	$(DOCKER_EXEC) ./bin/console lint:yaml --parse-tags ./config; \
    else \
			./bin/console lint:yaml --parse-tags ./config; \
  fi
test_container:
	@echo execute LINT_CONTAINER;
	@if [ -z $${$(VAR_NAME_IS_DOCKER)+x} ]; then \
    	$(DOCKER_EXEC) ./bin/console lint:container; \
    else \
			./bin/console lint:container; \
  fi

test_infection:
	@echo execute TEST_INFECTION;
	@if [ -z $${$(VAR_NAME_IS_DOCKER)+x} ]; then \
    	$(DOCKER_EXEC) php -d disable_functions=  ./bin/infection --min-msi=86 --min-covered-msi=87 --threads=4; \
    else \
			php -d disable_functions= ./bin/infection --min-msi=86 --min-covered-msi=87 --threads=4; \
  fi

install: docker_composer_install tests

up: create_dot_env docker_up_with_dot_env docker_composer_install

create_dot_env: $(ORIGINAL_DOT_ENV)
	@if [ ! -e $(DOCKER_DOT_ENV) ]; then\
		echo Create DotEnv;\
		cp $(ORIGINAL_DOT_ENV) $(DOCKER_DOT_ENV);\
        echo "USER_ID="$(USER_ID) >> $(DOCKER_DOT_ENV);\
        echo "GROUP_ID="$(GROUP_ID) >> $(DOCKER_DOT_ENV);\
	fi

docker_up_with_dot_env:
	docker compose --env-file .docker.env up -d
docker_composer_install:
	$(DOCKER_EXEC) composer install
down:
	docker compose --env-file .docker.env down
build:
	docker compose --env-file .docker.env build
cli:
	$(DOCKER_EXEC) /bin/ash
cliroot:
	docker exec -it --user root $(CONTAINER-NAME) /bin/ash
blackfire:
	@if [ -z "$(URL)" ]; then \
		echo -e '\033[0;31m Usage: make blackfire URL=/path \033[0m'; \
		exit 1; \
	else  \
		docker exec -it blackfire blackfire curl -H Host:sports.elconfidencial.local sports-service:8080$(URL); \
	fi
