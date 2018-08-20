sed -i -e "s/wordpress:9000/$WP_HOST/" /etc/nginx/conf.d/default.conf
nginx -g "daemon off;"
