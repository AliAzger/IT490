CREATE TABLE `group_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `added_by` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `group_events_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groupstab` (`id`)
)
