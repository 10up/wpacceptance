FROM 10up/phpfpm

RUN apt-get update && apt-get install -y \
		mysql-server\
		nginx\
	--no-install-recommends && rm -r /var/lib/apt/lists/*

RUN composer global require 10up/wpsnapshots:dev-master

RUN rm /bin/sh && ln -s /bin/bash /bin/sh

COPY config/default.conf /etc/nginx/sites-available/default

RUN wget -qO- https://raw.githubusercontent.com/creationix/nvm/v0.33.11/install.sh | bash

RUN source ~/.nvm/nvm.sh \
	&& nvm install node \
	&& nvm install 7 \
	&& nvm install 8 \
	&& nvm install-latest-npm

CMD nginx && php-fpm
