# Use the official PHP image with CLI and server support
FROM php:8.2-cli

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy all project files into the working directory
COPY . .

# Expose the port used by Render
EXPOSE 10000

# Run PHP built-in web server on Render's expected port
CMD ["php", "-S", "0.0.0.0:10000"]
