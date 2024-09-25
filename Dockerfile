FROM shinsenter/php:8.3-fpm-nginx AS base
RUN phpaddmod sockets
COPY ./ /var/www/html/

FROM base AS development
RUN phpaddmod xdebug

FROM base AS production