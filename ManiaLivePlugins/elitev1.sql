-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Machine: localhost
-- Genereertijd: 05 jun 2013 om 21:38
-- Serverversie: 5.5.20
-- PHP-Versie: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `elitev1`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `captures`
--

CREATE TABLE IF NOT EXISTS `captures` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` varchar(13) NOT NULL,
  PRIMARY KEY (`matchId`,`player`,`roundId`,`mapNum`),
  KEY `fk_Capture_Match1` (`matchId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Gegevens worden uitgevoerd voor tabel `captures`
--

INSERT INTO `captures` (`player`, `team`, `roundId`, `mapNum`, `mapName`, `matchId`) VALUES
('w1lla', '2', 1, 1, '$fff$sElite - $f20Fa$f31ce$f42To$f53Fa$f64ce', '1');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `eliminations`
--

CREATE TABLE IF NOT EXISTS `eliminations` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` varchar(13) NOT NULL,
  `eliminations` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player`,`matchId`,`roundId`,`mapNum`),
  KEY `fk_Deaths_Match1` (`matchId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `matches`
--

CREATE TABLE IF NOT EXISTS `matches` (
  `id` varchar(13) NOT NULL,
  `name` varchar(45) NOT NULL,
  `team1` varchar(60) NOT NULL,
  `team2` varchar(60) NOT NULL,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Gegevens worden uitgevoerd voor tabel `matches`
--

INSERT INTO `matches` (`id`, `name`, `team1`, `team2`, `startTime`, `endTime`) VALUES
('1', 'Red vs Blue', 'Red', 'Blue', 19839230, 0);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `nearmiss`
--

CREATE TABLE IF NOT EXISTS `nearmiss` (
  `id` int(11) NOT NULL,
  `player` varchar(60) DEFAULT NULL,
  `MissDist` varchar(60) DEFAULT NULL,
  `weaponId` int(11) NOT NULL,
  `matchId` varchar(13) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  PRIMARY KEY (`id`,`weaponId`,`mapNum`),
  KEY `fk_Shots_Weapon2` (`weaponId`),
  KEY `FK_Teams_Match1` (`matchId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `shots`
--

CREATE TABLE IF NOT EXISTS `shots` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `weaponId` int(11) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` varchar(13) NOT NULL,
  `shots` int(11) NOT NULL DEFAULT '0',
  `hits` int(11) NOT NULL DEFAULT '0',
  `eliminations` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player`,`weaponId`,`matchId`,`roundId`,`mapNum`),
  KEY `fk_Shots_Weapon1` (`weaponId`),
  KEY `fk_Shots_Match1` (`matchId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `teams`
--

CREATE TABLE IF NOT EXISTS `teams` (
  `team` varchar(50) NOT NULL DEFAULT '',
  `matchId` varchar(13) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `attack` int(10) NOT NULL DEFAULT '0',
  `defence` int(10) NOT NULL DEFAULT '0',
  `capture` int(10) NOT NULL DEFAULT '0',
  `timeOver` int(10) NOT NULL DEFAULT '0',
  `attackWinEliminate` int(10) NOT NULL DEFAULT '0',
  `defenceWinEliminate` int(10) NOT NULL DEFAULT '0',
  `endTime` int(11) NOT NULL,
  PRIMARY KEY (`team`,`matchId`,`mapNum`),
  KEY `FK_Teams_Match` (`matchId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `weapons`
--

CREATE TABLE IF NOT EXISTS `weapons` (
  `id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Beperkingen voor gedumpte tabellen
--

--
-- Beperkingen voor tabel `captures`
--
ALTER TABLE `captures`
  ADD CONSTRAINT `fk_Capture_Match1` FOREIGN KEY (`matchId`) REFERENCES `matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Beperkingen voor tabel `eliminations`
--
ALTER TABLE `eliminations`
  ADD CONSTRAINT `fk_Deaths_Match1` FOREIGN KEY (`matchId`) REFERENCES `matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Beperkingen voor tabel `nearmiss`
--
ALTER TABLE `nearmiss`
  ADD CONSTRAINT `FK_Teams_Match1` FOREIGN KEY (`matchId`) REFERENCES `matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Shots_Weapon2` FOREIGN KEY (`weaponId`) REFERENCES `weapons` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Beperkingen voor tabel `shots`
--
ALTER TABLE `shots`
  ADD CONSTRAINT `fk_Shots_Match1` FOREIGN KEY (`matchId`) REFERENCES `matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Shots_Weapon1` FOREIGN KEY (`weaponId`) REFERENCES `weapons` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Beperkingen voor tabel `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `FK_Teams_Match` FOREIGN KEY (`matchId`) REFERENCES `matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
