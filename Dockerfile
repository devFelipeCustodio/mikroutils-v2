FROM shinsenter/php:8.3-fpm-nginx AS base
RUN phpaddmod sockets pdo_pgsql

FROM base AS development
RUN phpaddmod xdebug

FROM base AS production