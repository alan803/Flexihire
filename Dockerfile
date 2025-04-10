# Use official PHP image with CLI and built-in web server
FROM php:8.2-cli

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Expose the port required by Render (10000)
EXPOSE 10000

# Run PHP's built-in web server
CMD ["php", "-S", "0.0.0.0:10000"]
