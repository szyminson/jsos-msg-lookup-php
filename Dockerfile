FROM  php:cli

RUN apt-get update && apt-get install -y zip unzip libc-client-dev libkrb5-dev libssl-dev libzip-dev cron \
    && rm -r /var/lib/apt/lists \
    && PHP_OPENSSL=yes docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap zip 

#install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

#add non-root user
#RUN useradd -ms /bin/bash deploy

ADD . /var/jml

WORKDIR /var/jml

RUN chmod +x /var/jml/docker-entrypoint.sh
ENTRYPOINT /var/jml/docker-entrypoint.sh

