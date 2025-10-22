CREATE TABLE `saved_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `date_time` varchar(255) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `url` text,
  `city` varchar(255) DEFAULT NULL,
  `last_status` varchar(50) DEFAULT NULL,
  `user_id` int NOT NULL,
  `lowest_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
)
