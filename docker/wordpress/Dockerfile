FROM 10up/phpfpm

RUN apt-get update && apt-get install -y \
		mysql-server\
		nginx\
	--no-install-recommends && rm -r /var/lib/apt/lists/*

RUN composer global require 10up/wpsnapshots:dev-master

COPY config/default.conf /etc/nginx/sites-available/default

CMD nginx && php-fpm
