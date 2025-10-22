all_events | CREATE TABLE `all_events` (
  `event_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `date_time` varchar(255) DEFAULT NULL,
  `url` text,
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`event_id`)
)
