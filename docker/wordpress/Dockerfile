FROM 10up/phpfpm

RUN apt-get update && apt-get install -y \
		mysql-server\
	--no-install-recommends && rm -r /var/lib/apt/lists/*

RUN composer global require 10up/wpsnapshots:dev-feature/create
