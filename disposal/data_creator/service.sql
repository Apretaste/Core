CREATE TABLE `apretaste`.`service` ( `name` VARCHAR(50) NOT NULL , `description` VARCHAR(1000) NOT NULL , `usage` TEXT NOT NULL , `creator_email` VARCHAR(50) NOT NULL , `insertion_date` TIMESTAMP NOT NULL , `category` ENUM('listing','social_network') NOT NULL , `subservices` VARCHAR(250) NOT NULL , `deploy_key` VARCHAR(32) NOT NULL , PRIMARY KEY (`name`) ) ENGINE = InnoDB;