# TARGET: docker/server/Dockerfile
#
# {{generator}}
#
FROM php:8.1-apache


# braucht es hier nicht:
# RUN a2enmod rewrite

RUN apt-get update && apt-get install -y git unzip zip
# RUN apt-get install -y php-curl php-pdo_mysql php-mbstring php-xml php-zip php-xdebug

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions curl pdo_mysql mbstring xml zip

# for debugging
RUN install-php-extensions xdebug
