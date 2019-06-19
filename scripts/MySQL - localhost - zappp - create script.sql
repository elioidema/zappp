	  CREATE TABLE `sessions` (
	  `id` varchar(100) NOT NULL,
	  `data` mediumtext NOT NULL,
	  `timestamp` int(255) NOT NULL,
	  PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;