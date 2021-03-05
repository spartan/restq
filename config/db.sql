-- Adminer 4.7.6 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

drop table IF EXISTS `author`;
create TABLE `author` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `country_id` tinyint unsigned NOT NULL,
  `status` tinyint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  CONSTRAINT `author_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

insert into `author` (`id`, `first_name`, `last_name`, `birth_date`, `country_id`, `status`) VALUES
(1,	'Marijn',	'Haverbeke',	'1990-01-01',	1,	1),
(2,	'Addy',	'Osmani',	'1990-01-01',	1,	1),
(3,	'Axel',	'Rauschmayer',	'1990-01-01',	1,	1),
(4,	'Eric',	'Elliott',	'1990-01-01',	1,	1),
(5,	'Nicholas',	'Zakas',	'1990-01-01',	1,	1),
(6,	'Kyle',	'Simpson',	'1990-01-01',	1,	1),
(7,	'Richard',	'Silverman',	'1990-01-01',	1,	1);

drop table IF EXISTS `author_book`;
create TABLE `author_book` (
  `author_id` int unsigned NOT NULL,
  `book_id` int unsigned NOT NULL,
  PRIMARY KEY (`author_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `author_book_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `author` (`id`),
  CONSTRAINT `author_book_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `book` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

insert into `author_book` (`author_id`, `book_id`) VALUES
(1,	1),
(2,	2),
(3,	3),
(4,	4),
(5,	5),
(6,	6),
(7,	7);

drop table IF EXISTS `book`;
create TABLE `book` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `isbn13` char(13) NOT NULL,
  `release_year` int NOT NULL,
  `publisher_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn13` (`isbn13`),
  KEY `publisher_id` (`publisher_id`),
  CONSTRAINT `book_ibfk_1` FOREIGN KEY (`publisher_id`) REFERENCES `publisher` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

insert into `book` (`id`, `title`, `isbn13`, `release_year`, `publisher_id`) VALUES
(1,	'Eloquent JavaScript, Second Edition',	'9781593275846',	2014,	1),
(2,	'Learning JavaScript Design Patterns',	'9781449331818',	2012,	2),
(3,	'Speaking JavaScript',	'9781449365035',	2014,	2),
(4,	'Programming JavaScript Applications',	'9781491950296',	2014,	2),
(5,	'Understanding ECMAScript 6',	'9781593277574',	2016,	1),
(6,	'You Don\'t Know JS',	'9781491904244',	2015,	2),
(7,	'Git Pocket Guide',	'9781449325862',	2013,	2);

DROP TABLE IF EXISTS `country`;
CREATE TABLE `country` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `iso2` char(2) NOT NULL,
  `continent` char(2) NOT NULL,
  `currency` char(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `country` (`id`, `name`, `iso2`, `continent`, `currency`) VALUES
(1,	'United States',	'US',	'NA',	'USD');

DROP TABLE IF EXISTS `publisher`;
CREATE TABLE `publisher` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `country_id` tinyint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  CONSTRAINT `publisher_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `publisher` (`id`, `name`, `country_id`) VALUES
(1,	'No Starch Press',	1),
(2,	'O\'Reilly Media',	1);

-- 2020-05-08 14:39:21
