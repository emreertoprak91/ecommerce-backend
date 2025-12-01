-- MySQL Initialization Script
-- This script runs when the container starts for the first time

-- Create additional databases if needed
-- CREATE DATABASE IF NOT EXISTS ecommerce_test;

-- Grant privileges
GRANT ALL PRIVILEGES ON ecommerce.* TO 'ecommerce_user'@'%';
GRANT ALL PRIVILEGES ON ecommerce_test.* TO 'ecommerce_user'@'%';
FLUSH PRIVILEGES;

-- Show databases
SHOW DATABASES;
