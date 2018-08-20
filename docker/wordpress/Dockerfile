FROM wordpress:latest

COPY install_composer /usr/local/bin/

RUN apt-get update && apt-get install -y \
		wget\
		git\
		less\
		mysql-server\
	--no-install-recommends && rm -r /var/lib/apt/lists/*

RUN bash install_composer

RUN composer global require 10up/wpsnapshots:dev-feature/create

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
	&& mv wp-cli.phar /usr/local/bin/wp \
	&& chmod 0755 /usr/local/bin/wp
