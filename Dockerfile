FROM php:7.4-apache

MAINTAINER Tom Hansen "tomh@uwm.edu"

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd

RUN ln -fs /usr/share/zoneinfo/America/Chicago /etc/localtime
RUN dpkg-reconfigure --frontend noninteractive tzdata
COPY . /var/www/html/signage

RUN /var/www/html/signage/mkphptz.sh
RUN echo '[mysql]' > /root/.my.cnf && echo 'host=waterdata.glwi.uwm.edu' >> /root/.my.cnf

