CREATE TABLE IF NOT EXISTS `countries` (
    `id` int(11) NOT NULL PRIMARY KEY,
    `phone` int(5) NOT NULL,
    `code` char(2) NOT NULL,
    `name` varchar(80) NOT NULL,
    `symbol` varchar(10) DEFAULT NULL,
    `capital` varchar(80) DEFAULT NULL,
    `currency` varchar(3) DEFAULT NULL,
    `continent` varchar(30) DEFAULT NULL,
    `continent_code` varchar(2) DEFAULT NULL
);
