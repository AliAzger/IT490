CREATE TABLE `venue_recommendations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `date_time` varchar(255) DEFAULT NULL,
  `url` text,
  `recommended_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
