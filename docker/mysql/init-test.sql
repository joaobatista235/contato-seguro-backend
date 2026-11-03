CREATE DATABASE IF NOT EXISTS backend_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs;
CREATE USER IF NOT EXISTS 'backend_test'@'%' IDENTIFIED BY 'backend_test_dev';
GRANT ALL PRIVILEGES ON backend_test.* TO 'backend_test'@'%';
