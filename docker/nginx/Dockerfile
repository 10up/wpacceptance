FROM nginx

COPY default.conf /etc/nginx/conf.d/

CMD sed -i -e "s/wordpress:9000/$WP_HOST:9000/" /etc/nginx/conf.d/default.conf && nginx -g "daemon off;"
