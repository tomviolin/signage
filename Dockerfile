FROM php:7.4-apache

MAINTAINER Tom Hansen "tomh@uwm.edu"

COPY . /var/www/html/signage

RUN echo '[mysql]' > /root/.my.cnf
RUN echo 'host=waterdata.glwi.uwm.edu' >> /root/.my.cnf

RUN /var/www/html/signage/mkphptz.sh
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

