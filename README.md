資料來源: https://data.nhi.gov.tw/resource/mask/maskdata.csv

maskUpdate.php 取得公開資料 -> 將縣市區鄉鎮資料寫入 county, district -> 將藥局口罩現況寫入 pharmacy

[MySQL Schema]

Database: mask
Table: county, district, pharmacy,

CREATE DATABASE `mask` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `county` (
    `county_id` INT(2) NOT NULL auto_increment,
    `name` VARCHAR(10)  NOT NULL,
    PRIMARY KEY(`county_id`)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE `district` (
    `district_id` INT(2) NOT NULL auto_increment,
    `county_id` INT(2) NOT NULL,
    `name` VARCHAR(10)  NOT NULL,
    PRIMARY KEY(`district_id`),
    FOREIGN KEY (`county_id`) REFERENCES county(`county_id`)
) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE `pharmacy` (
    `code` char(10) NOT NULL,
    `county_id` INT(2) NOT NULL,
    `district_id` INT(2) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `addr` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(14) DEFAULT NULL,
    `adult` TINYINT(1) DEFAULT 0,
    `kid` TINYINT(1) DEFAULT 0,
    `updated_at` DATETIME,
    PRIMARY KEY(`code`),
    FOREIGN KEY (`county_id`) REFERENCES county(`county_id`),
    FOREIGN KEY (`district_id`) REFERENCES district(`district_id`)
) ENGINE=InnoDB CHARACTER SET=utf8;
