# 29 November 2025

# categories table
DROP TABLE IF EXISTS `terms`;
CREATE TABLE `terms` (
    `id`  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(250),
    PRIMARY KEY (`id`),
    UNIQUE KEY (`title`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# news table
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `term_id` INT(11) UNSIGNED NOT NULL,
  `crc` int UNSIGNED NOT NULL,
  `d` DATE NOT NULL,
  `t` TIME NOT NULL,
  `title` varchar(250) NOT NULL,
  `slug` varchar(250) NOT NULL,    
  `url` TEXT NULL DEFAULT NULL,
  `img` TEXT NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY (`term_id`),
   UNIQUE KEY (`crc`),
   KEY (`d`),
   KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;