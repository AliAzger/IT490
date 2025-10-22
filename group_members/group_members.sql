CREATE TABLE `group_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groupstab` (`id`)
)
