ARG WEBSERVICE_VERSION=v1
ARG REGISTRY_HOST=hub.elconfidencial.clo

FROM ${REGISTRY_HOST}/webservice-backend:${WEBSERVICE_VERSION}-local AS builder
ADD --chown=www-data:www-data --chmod=755 ./ ./
RUN composer install \
    && rm -rf .env

FROM ${REGISTRY_HOST}/webservice-backend:${WEBSERVICE_VERSION}-local AS builder_prod
ADD --chown=www-data:www-data --chmod=755 ./ ./
RUN sed -i 's/APP_ENV=dev/APP_ENV=prod/' .env.dist \
    && composer install --no-dev --classmap-authoritative --no-progress --no-interaction --no-suggest --no-scripts \
    && php ./bin/console cache:warmup --env=prod --no-interaction --no-debug \
    && rm -rf .env

FROM ${REGISTRY_HOST}/webservice-backend:${WEBSERVICE_VERSION}-local AS local
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG COMPOSER_AUTH_TOKEN=xxxx
USER root
RUN deluser www-data  \
    && adduser -u ${USER_ID} -D -h /var/www -s /bin/ash www-data \
    && addgroup -g 33 ecdev  \
    && adduser www-data ecdev \
    && chown -R www-data:ecdev /var/www \
    && chmod -R g+wr /var/run/php \
    && chown -R www-data:www-data /var/log/newrelic

RUN sed -i 's/listen.group\s*=\s*www-data/listen.group = ecdev/' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/opcache.jit=true/opcache.jit=false/' /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN sed -i 's/opcache.preload=\/var\/www\/service\/config\/preload.php/;opcache.preload=\/var\/www\/service\/config\/preload.php/' /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
USER www-data
RUN composer config --global --auth "http-basic.repo.packagist.com" token ${COMPOSER_AUTH_TOKEN} \
    && composer config --global repositories.private-packagist composer https://repo.packagist.com/el-confidencial/ \
    && composer config --global repositories.packagist.org false
WORKDIR /var/www/service

FROM ${REGISTRY_HOST}/webservice-backend:${WEBSERVICE_VERSION}-local AS ci-ephimeral
COPY --chown=www-data:www-data --from=builder /var/www/service /var/www/service

FROM ci-ephimeral AS ci
ENTRYPOINT []

FROM ${REGISTRY_HOST}/webservice-backend:${WEBSERVICE_VERSION} AS k8s
RUN mkdir -p /var/www/envs/app \
    && mkdir -p /var/www/envs/sm \
    && ln -sf /var/www/envs/app/.env /var/www/service/.env.local \
    && ln -sf /var/www/envs/sm/.env /var/www/service/.env
COPY docker/config/newrelic.ini /usr/local/etc/php/conf.d/21-newrelic.ini


FROM ${REGISTRY_HOST}/webservice-backend:${WEBSERVICE_VERSION} AS k8s-dev
RUN mkdir -p /var/www/envs/app \
    && mkdir -p /var/www/envs/sm \
    && ln -sf /var/www/envs/app/.env /var/www/service/.env.local \
    && ln -sf /var/www/envs/sm/.env /var/www/service/.env
COPY docker/config/newrelic.ini /usr/local/etc/php/conf.d/21-newrelic.ini

FROM ci-ephimeral AS ephemereal
RUN mv .env.dist .env


FROM k8s-dev AS integration
USER root
RUN sed -i 's/newrelic.appname = "snaapi"/newrelic.appname = "DEV PHP"/' /usr/local/etc/php/conf.d/21-newrelic.ini
USER www-data
COPY --chown=www-data:www-data --from=builder /var/www/service /var/www/service
# RUN rm -rf .env.dist

FROM k8s AS prod
ARG BRANCH_EXECUTE=""
USER root
RUN if echo "$BRANCH_EXECUTE" | grep -Eq '^(master|v[0-9]+\.[0-9]+\.[0-9]+)$'; then \
        sed -i 's/newrelic.appname = "snaapi"/newrelic.appname = "PRO PHP snaapi"/' /usr/local/etc/php/conf.d/21-newrelic.ini; \
    else \
        sed -i 's/newrelic.appname = "snaapi"/newrelic.appname = "STA PHP"/' /usr/local/etc/php/conf.d/21-newrelic.ini; \
    fi
USER  www-data
COPY --chown=www-data:www-data --from=builder_prod /var/www/service /var/www/service
COPY --chown=www-data:www-data --from=builder_prod /var/www/cache /var/www/cache

RUN rm -rf .php-cs-fixer.cache \
           .php-cs-fixer.dist.php \
           .phpunit.result.cache \
           .phpunit.cache \
           ./var/ \
           ./phpunit.xml.dist \
           ./.web-server-pid \
           .dockerignore \
           ./docker \
           ./release.config.js \
           sonar-project.properties \
           phpstan.neon \
           infection.json.dist \
           infection.log \
           .env.dist \
           ./symfony.lock \
           ./composer.lock \
