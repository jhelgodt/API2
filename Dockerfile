FROM richarvey/nginx-php-fpm:latest

COPY . . 

ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV RUN_IP_HEADER 1

RUN apk update

RUN apk add --no-cache npm

RUN npm install

RUN npm run build