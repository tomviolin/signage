FROM php:7.4-apache

MAINTAINER Tom Hansen "tomh@uwm.edu"

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd

COPY ./mkphptz.sh .
RUN ln -fs /usr/share/zoneinfo/America/Chicago /etc/localtime ; \
    dpkg-reconfigure --frontend noninteractive tzdata ; \
    ls -Fla /; \
    pwd ; \
    ./mkphptz.sh

RUN echo '[mysql]' > /root/.my.cnf ; \
    echo 'host=waterdata.glwi.uwm.edu' >> /root/.my.cnf ; \
    echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf; \
    ln -s ../conf-available/servername.conf /etc/apache2/conf-enabled

COPY . /var/www/html/signage
