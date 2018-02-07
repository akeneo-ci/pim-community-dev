FROM composer as composer
FROM selenium/standalone-firefox:2.53.1-beryllium as selenium
FROM node:8 as nodejs

FROM php:7.1-fpm

ENV MYSQL_MAJOR 5.7
ENV MYSQL_VERSION 5.7.21-1debian8

RUN set -ex; \
	export GNUPGHOME="$(mktemp -d)"; \
	key='A4A9406876FCBD3C456770C88C718D3B5072E1F5'; \
	gpg --keyserver ha.pool.sks-keyservers.net --recv-keys "$key"; \
	gpg --export "$key" > /etc/apt/trusted.gpg.d/mysql.gpg; \
	rm -r "$GNUPGHOME"; \
	apt-key list > /dev/null

RUN echo "deb http://http.debian.net/debian jessie-backports main" > /etc/apt/sources.list.d/jessie-backports.list
RUN echo "deb http://repo.mysql.com/apt/debian/ jessie mysql-${MYSQL_MAJOR}" > /etc/apt/sources.list.d/mysql.list

RUN { \
		echo mysql-community-server mysql-community-server/data-dir select ''; \
		echo mysql-community-server mysql-community-server/root-pass password ''; \
		echo mysql-community-server mysql-community-server/re-root-pass password ''; \
		echo mysql-community-server mysql-community-server/remove-test-db select false; \
	} | debconf-set-selections
  
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=selenium /usr/bin/firefox /usr/bin/firefox
COPY --from=selenium /usr/bin/geckodriver /usr/bin/geckodriver
COPY --from=nodejs /usr/local/bin/node /usr/local/bin/node
COPY --from=nodejs /opt/yarn /opt/yarn

RUN ln -s /opt/yarn/bin/yarn /usr/local/bin/yarn
RUN ln -s /opt/yarn/bin/yarn /usr/local/bin/yarnpkg

RUN groupadd -r php && useradd -r -g php php

RUN apt-get update && apt-get install -y \
  apache2 \
  g++ \
  git \
  imagemagick \
  libcurl4-gnutls-dev \
  libicu-dev \
  libmagickwand-dev \
  libmcrypt-dev \
  libpng-dev \
  libxml2-dev \
  mysql-client \
  mysql-server="${MYSQL_VERSION}" \
  zlib1g-dev \
  && apt-get install -y -t jessie-backports openjdk-8-jdk

RUN curl -SLO "https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-5.5.3.deb" \
  && dpkg -i elasticsearch-5.5.3.deb

RUN docker-php-ext-install \
  bcmath \
  curl \
  exif \
  gd \
  intl \
  mbstring \
  mcrypt \
  opcache \
  pdo_mysql \
  soap \
  xml \
  zip

RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install imagick && docker-php-ext-enable imagick

COPY docker/php.ini /usr/local/etc/php/
COPY docker/vhost.conf /etc/apache2/sites-available/pim.conf

ADD . /var/www/pim

WORKDIR /var/www/pim

RUN cp app/config/parameters_test.yml.dist app/config/parameters_test.yml \
  && sed -i "s#database_host: .*#database_host: 127.0.0.1#g" app/config/parameters_test.yml \
  && sed -i "s#index_hosts: .*#index_hosts: 'elastic:changeme@127.0.0.1:9200'#g" app/config/parameters_test.yml

RUN composer update --ansi --optimize-autoloader --no-interaction --no-progress --prefer-dist --ignore-platform-reqs --no-suggest \
  && bin/console --ansi assets:install \
  && bin/console --ansi pim:installer:dump-require-paths \
  && yarn install --no-progress \
  && yarn run webpack

RUN /etc/init.d/mysql start \
  && /etc/init.d/elasticsearch start \
  && mysql -e "CREATE DATABASE IF NOT EXISTS \`akeneo_pim\` ;" \
  && mysql -e "CREATE USER 'akeneo_pim'@'%' IDENTIFIED BY 'akeneo_pim';" \
  && mysql -e "GRANT ALL ON \`akeneo_pim\`.* TO 'akeneo_pim'@'%' ;" \
  && mysql -e "FLUSH PRIVILEGES;" \
  && bin/console --env=test pim:install --force

RUN a2enmod rewrite \
  && a2enmod proxy \
  && a2enmod proxy_fcgi \
  && a2dissite 000-default \
  && a2ensite pim \
  && chown -R www-data:www-data /var/www/pim/var /var/www/pim/web 

CMD /etc/init.d/mysql start \
  && /etc/init.d/elasticsearch start \
  && php-fpm -D \
  && /etc/init.d/apache2 restart \
  && sleep infinity