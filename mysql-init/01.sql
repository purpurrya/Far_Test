# create databases
CREATE DATABASE IF NOT EXISTS `microservice`;
CREATE DATABASE IF NOT EXISTS `microservice-test`;

# create root user and grant rights
CREATE USER 'root'@'localhost' IDENTIFIED BY 'local';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%';