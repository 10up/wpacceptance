FROM 10up/phpfpm

RUN apt-get update && apt-get install -y \
		mysql-server\
		nginx\
		libicu-dev\
	--no-install-recommends && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN composer config --global discard-changes true

RUN composer global require 10up/wpsnapshots:dev-master

RUN composer global require 10up/wpinstructions:dev-master

RUN rm /bin/sh && ln -s /bin/bash /bin/sh

ENV NVM_DIR /usr/local/nvm
RUN curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.33.1/install.sh | bash
ENV NODE_VERSION v8.12.0
RUN /bin/bash -c "source $NVM_DIR/nvm.sh && nvm install $NODE_VERSION && nvm use --delete-prefix $NODE_VERSION"

ENV NODE_PATH $NVM_DIR/versions/node/$NODE_VERSION/lib/node_modules
ENV PATH      $NVM_DIR/versions/node/$NODE_VERSION/bin:$PATH

COPY ./nginx/default /etc/nginx/sites-available/

CMD nginx && php-fpm
