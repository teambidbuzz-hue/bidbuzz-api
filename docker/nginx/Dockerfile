# Stage 1: Build assets
FROM debian AS builder

# Install Node.js and build tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Set working directory
WORKDIR /var/www

# Copy Laravel application code
COPY . /var/www

# Stage 2: Nginx production image
FROM nginx:alpine

ENV PHP_FPM_HOST=bidbuzz-php
ENV PHP_FPM_PORT=9000

# Copy custom Nginx configuration
# -----------------------------------------------------------
# The official Nginx image renders templates from /etc/nginx/templates
# at container startup, so Kubernetes can override the PHP upstream.
# -----------------------------------------------------------
COPY ./docker/nginx/nginx.conf /etc/nginx/templates/default.conf.template

# Copy Laravel's public assets from the builder stage
# -----------------------------------------------------------
# We only need the 'public' directory from our Laravel app.
# -----------------------------------------------------------
COPY --from=builder /var/www/public /var/www/public

# Set the working directory to the public folder
WORKDIR /var/www/public

# Expose port 80 and start Nginx
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
