CREATE TABLE `attended_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `date_time` varchar(255) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `url` text,
  `city` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
)
