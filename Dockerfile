# Use a PHP base image
FROM php:8.1-cli

# Install necessary dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    curl \
    git \
    && docker-php-ext-install mysqli

# Set the working directory
WORKDIR /var/www/html

# Clone the repository
RUN git clone https://github.com/Chlakhna/kobo02.git .

# Install Composer and dependencies (if your project requires it)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
# RUN composer install # Uncomment if you have a composer.json file

# Set environment variables (optional)
# ENV VARIABLE_NAME=value

# Run your PHP script
CMD ["php", "database02.php"]
