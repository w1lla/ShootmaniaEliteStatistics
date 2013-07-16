CREATE TABLE IF NOT EXISTS `Matches` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `team1` varchar(60) NOT NULL,
  `team2` varchar(60) NOT NULL,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8

CREATE TABLE IF NOT EXISTS `Weapons` (
  `id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `Weapons` (`id`, `name`) VALUES (1, 'Rail'),(2, 'Rocket'),(3, 'Nucleus'),(5, 'Arrow');

CREATE TABLE IF NOT EXISTS `Captures` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` INT (13) NOT NULL,
  PRIMARY KEY (`matchId`,`player`,`roundId`,`mapNum`),
  KEY `fk_Capture_Match1` (`matchId`),
  CONSTRAINT `fk_Capture_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `Eliminations` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` INT(13) NOT NULL,
  `eliminations` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player`,`matchId`,`roundId`,`mapNum`),
  KEY `fk_Deaths_Match1` (`matchId`),
  CONSTRAINT `fk_Deaths_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `Shots` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `weaponId` int(11) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` INT (13) NOT NULL,
  `shots` int(11) NOT NULL DEFAULT '0',
  `hits` int(11) NOT NULL DEFAULT '0',
  `eliminations` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player`,`weaponId`,`matchId`,`roundId`,`mapNum`),
  KEY `fk_Shots_Weapon1` (`weaponId`),
  KEY `fk_Shots_Match1` (`matchId`),
  CONSTRAINT `fk_Shots_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Shots_Weapon1` FOREIGN KEY (`weaponId`) REFERENCES `Weapons` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `Teams` (
  `team` varchar(50) NOT NULL DEFAULT '',
  `matchId` INT (13) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `attack` int(10) NOT NULL DEFAULT '0',
  `defence` int(10) NOT NULL DEFAULT '0',
  `capture` int(10) NOT NULL DEFAULT '0',
  `timeOver` int(10) NOT NULL DEFAULT '0',
  `attackWinEliminate` int(10) NOT NULL DEFAULT '0',
  `defenceWinEliminate` int(10) NOT NULL DEFAULT '0',
  `endTime` int(11) NOT NULL,
  `ClublinkUrl` varchar(100) NOT NULL,
  `TeamColour` varchar(100) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  PRIMARY KEY (`team`,`matchId`,`mapNum`),
  KEY `FK_Teams_Match` (`matchId`),
  CONSTRAINT `FK_Teams_Match` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `NearMiss` (
  `id` int(11) NOT NULL,
  `player` varchar(60) DEFAULT NULL,
  `MissDist` varchar(60) DEFAULT NULL,
  `weaponId` int(11) NOT NULL,
  `matchId` INT (13) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  PRIMARY KEY (`id`,`weaponId`,`mapNum`),
  KEY `fk_Shots_Weapon2` (`weaponId`),
  KEY `FK_Teams_Match1` (`matchId`),
  CONSTRAINT `FK_Teams_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Shots_Weapon2` FOREIGN KEY (`weaponId`) REFERENCES `Weapons` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;