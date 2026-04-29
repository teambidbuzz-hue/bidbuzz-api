FROM nginx:alpine

ENV PHP_FPM_HOST=127.0.0.1
ENV PHP_FPM_PORT=9000
ENV FASTCGI_CONNECT_TIMEOUT=10s
ENV FASTCGI_SEND_TIMEOUT=120s
ENV FASTCGI_READ_TIMEOUT=120s

COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

WORKDIR /var/www

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
