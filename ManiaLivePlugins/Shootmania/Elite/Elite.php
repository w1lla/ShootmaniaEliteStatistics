<?php
/**
Name: Willem 'W1lla' van den Munckhof
Date: Unknown but before ESWC
Project Name: eXpansion project www.exp-tm.team.com
What to do:

SQL DB's for the most callbacks of Elite;
Test everything first with mA lobby servers, elite/match servers.
Better explanation of code ???
Better calculation of players/distance nearmiss

**/
/**
 * ---------------------------------------------------------------------
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * You are allowed to change things or use this in other projects, as
 * long as you leave the information at the top (name, date, version,
 * website, package, author, copyright) and publish the code under
 * the GNU General Public License version 3.
 * ---------------------------------------------------------------------
 */

namespace ManiaLivePlugins\Shootmania\Elite;

use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Console;
use ManiaLive\Features\Admin\AdminGroup;

class Elite extends \ManiaLive\PluginHandler\Plugin {

	function onInit() {
		$this->setVersion('0.0.1');
	}

	function onLoad() {
	
			$admins = AdminGroup::get();
		
		$cmd = $this->registerChatCommand('extendWu', 'extendWarmup', 0, true, $admins);
		$cmd->help = 'Extends WarmUp In Ellte.';
		
		$cmd = $this->registerChatCommand('endWu', 'endWarmup', 0, true, $admins);
		$cmd->help = 'ends WarmUp in Elite.';
		
		$this->enableDatabase();
		$this->enableDedicatedEvents();
		

		
		Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Core v' . $this->getVersion());
		$this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff e$a00X$fffpansion Shootmania Stats$fa0!');
		
		
		if(!$this->db->tableExists('Matches')) {
			$q = "CREATE TABLE IF NOT EXISTS `Matches` (
  `id` varchar(13) NOT NULL,
  `name` varchar(45) NOT NULL,
  `team1` varchar(60) NOT NULL,
  `team2` varchar(60) NOT NULL,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;";
			$this->db->execute($q);
		Console::println('[' . date('H:i:s') . '] [Shootmania] Database Elite Matches Created');
		}

		if(!$this->db->tableExists('Weapons')) {
			$q = "CREATE TABLE IF NOT EXISTS `Weapons` (
  `id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;";
			$this->db->execute($q);
		Console::println('[' . date('H:i:s') . '] [Shootmania] Database Elite Weapons Created');
		}
		
	if(!$this->db->tableExists('Captures')) {
			$q = "CREATE TABLE IF NOT EXISTS `Captures` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` varchar(13) NOT NULL,
  PRIMARY KEY (`matchId`,`player`,`roundId`,`mapNum`),
  KEY `fk_Capture_Match1` (`matchId`),
  CONSTRAINT `fk_Capture_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$this->db->execute($q);
		Console::println('[' . date('H:i:s') . '] [Shootmania] Database Elite Captures Created');
		}
		
		if(!$this->db->tableExists('Eliminations')) {
			$q = "CREATE TABLE IF NOT EXISTS `Eliminations` (
  `player` varchar(60) NOT NULL,
  `team` varchar(60) NOT NULL,
  `roundId` int(11) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  `matchId` varchar(13) NOT NULL,
  `eliminations` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player`,`matchId`,`roundId`,`mapNum`),
  KEY `fk_Deaths_Match1` (`matchId`),
  CONSTRAINT `fk_Deaths_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$this->db->execute($q);
		Console::println('[' . date('H:i:s') . '] [Shootmania] Database Elite Eliminations Created');
		}

		if(!$this->db->tableExists('Shots')) {
			$q = "CREATE TABLE IF NOT EXISTS `Shots` (
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
  KEY `fk_Shots_Match1` (`matchId`),
  CONSTRAINT `fk_Shots_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Shots_Weapon1` FOREIGN KEY (`weaponId`) REFERENCES `Weapons` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$this->db->execute($q);
		Console::println('[' . date('H:i:s') . '] [Shootmania] Database Elite Shots Created');
		}

		if(!$this->db->tableExists('Teams')) {
			$q = "CREATE TABLE IF NOT EXISTS `Teams` (
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
  KEY `FK_Teams_Match` (`matchId`),
  CONSTRAINT `FK_Teams_Match` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$this->db->execute($q);
		Console::println('[' . date('H:i:s') . '] [Shootmania] Database Elite Teams Created');
		}

		if(!$this->db->tableExists('NearMiss')) {
			$q = "CREATE TABLE IF NOT EXISTS `NearMiss` (
  `id` int(11) NOT NULL,
  `player` varchar(60) DEFAULT NULL,
  `MissDist` varchar(60) DEFAULT NULL,
  `weaponId` int(11) NOT NULL,
  `matchId` varchar(13) NOT NULL,
  `mapNum` int(11) NOT NULL,
  `mapName` varchar(75) NOT NULL,
  PRIMARY KEY (`id`,`weaponId`,`mapNum`),
  KEY `fk_Shots_Weapon2` (`weaponId`),
  KEY `FK_Teams_Match1` (`matchId`),
  CONSTRAINT `FK_Teams_Match1` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Shots_Weapon2` FOREIGN KEY (`weaponId`) REFERENCES `Weapons` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$this->db->execute($q);
		Console::println('[' . date('H:i:s') . '] [Shootmania] Database Elite NearMiss Created');
		}			
		
		
	}

	function extendWarmup($login)
	{
		$this->connection->triggerModeScriptEvent('extendWarmup',''); // param1 is the first callback sent to the script, param2 can be anything.
	}
	
	function endWarmup($login)
	{
		$this->connection->triggerModeScriptEvent('endWarmup','');
	}
	
	function onModeScriptCallback($param1, $param2) {
		//Console::println('[' . date('H:i:s') . '] Script callback: '.$param1.', with parameter: '.$param2);
		switch($param1) {
			case 'LibXmlRpc_BeginMatch':
				return;
			case 'LibXmlRpc_BeginMap':
				return;
			case 'LibXmlRpc_BeginSubmatch':
				return;
			case 'LibXmlRpc_BeginRound':
				return;
			case 'LibXmlRpc_BeginTurn':
			$TurnNumber = $param2[0];
			$this->TurnNumber = $TurnNumber;
				return;
			case 'LibXmlRpc_EndTurn':
				return;	
			case 'LibXmlRpc_EndRound':
				return;	
			case 'LibXmlRpc_EndSubmatch':
				return;	
			case 'LibXmlRpc_EndMap':
				return;	
			case 'LibXmlRpc_EndMatch':
				return;	
			case 'LibXmlRpc_Rankings':
			//Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				return;		
			case 'LibXmlRpc_OnShoot':
				return;
			case 'LibXmlRpc_OnHit':
				return;
			case 'LibXmlRpc_OnNearMiss':
				return;
			case 'LibXmlRpc_OnArmorEmpty':
				return;
			case 'LibXmlRpc_OnCapture':
				return;	
			case 'LibXmlRpc_OnPlayerRequestRespawn':
				return;
			case 'Royal_UpdatePoints':
				return;	
			case 'Royal_SpawnPlayer':
				return;
			case 'TimeAttack_OnStart':
				return;
			case 'TimeAttack_OnCheckpoint':
				return;
			case 'TimeAttack_OnRestart':
				return;
			case 'Joust_OnReload':
				return;
			case 'Joust_SelectedPlayers':
				return;
			case 'Joust_RoundResult':
				return;
			case 'BeginMatch':
				$decode_param2 = json_decode($param2);
				$MatchNumber = $decode_param2->MatchNumber;
				$StartTime = $decode_param2->Timestamp;
				$BlueName = $this->connection->getTeamInfo(1)->name;
				$RedName = $this->connection->getTeamInfo(2)->name;
				$MatchName = ''.$RedName.' vs '.$BlueName.'';
				$BeginMatchQuery = "INSERT INTO  `matches` (
				`id` ,
				`name` ,
				`team1` ,
				`team2` ,
				`startTime` ,
				`endTime`
				)
				VALUES (
				".$MatchNumber.", '".$MatchName."', '".$RedName."', '".$BlueName."', ".$StartTime.", '0');";
				// Perform Query
				$this->db->execute($BeginMatchQuery);
				return;
			case 'BeginMap':
				$decode_param2 = json_decode($param2);
				$MapNum = $decode_param2->MapNumber;
				$map = $this->connection->getCurrentMapInfo();
				$MapName = $map->name;
				return;
			case 'BeginWarmup':
				$decode_param2 = json_decode($param2);
				return;
			case 'EndWarmup':
				$decode_param2 = json_decode($param2);
				return;
			case 'BeginSubmatch':// This callback is sent at the beginning of each submatch
				$decode_param2 = json_decode($param2);
				return;
			case 'BeginTurn':// This callback is sent at the beginning of each turn
				$decode_param2 = json_decode($param2);
				$TurnNumber = $decode_param2->TurnNumber;
				$AtkClan = $decode_param2->AttackingClan;
				$DefClan = $decode_param2->DefendingClan;
				$AtkPlayerLogin = $decode_param2->AttackingPlayer->Login;
				$AtkPlayerNickName = $decode_param2->AttackingPlayer->Name;
				return;
			case 'OnCapture':// This callback is sent when the attacker captured the pole
				$decode_param2 = json_decode($param2);
				$PlayerCapturedLogin = $decode_param2->Event->Player->Login;
				$PlayerCapturedNickname = $decode_param2->Event->Player->Name;
				$PlayerCapturedClan = $decode_param2->Event->Player->CurrentClan;
				var_dump($this->TurnNumber);
				$CaptureQuery = "INSERT INTO  `captures` (
				`player` ,
				`team` ,
				`roundId` ,
				`mapNum` ,
				`mapName` ,
				`matchId`
				)
				VALUES (
				'".$PlayerCapturedLogin."', '".$PlayerCapturedClan."', '".$TurnNumber."', '".$MapNum."', '".$MapName."', '".$MatchNumber."');";
				// Perform Query
				$result = mysql_query($CaptureQuery, $link);
				// Check result
				// This shows the actual query sent to MySQL, and the error. Useful for debugging.
				if (!$result) {
				$message  = 'Invalid query: ' . mysql_error() . "\n";
				$message .= 'Whole query: ' . $CaptureQuery;
				//Logger::getLog('EliteStats')->write($TurnNumber); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				}
				return;
			case 'OnHit':// This callback is sent when a player hit another player
				$decode_param2 = json_decode($param2);
				$HitDamage = $decode_param2->Event->Damage; // Damage (Which isnt used??)
				$HitWeapon = $decode_param2->Event->WeaponNum; // WeaponNum 1 = Laser 2 = Rocket 3 = Nucleus 5 = Arrow
				$HitMissDist = $decode_param2->Event->MissDist; // MissDist (apparently 0)
				$HitDist = $decode_param2->Event->HitDist; // HitDist * 100 cm // mm 2.34118
				$HitShooterLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$HitShooterNickName = $decode_param2->Event->Shooter->Name; // Shooter NickName
				$HitShooterCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan
				$HitVictimLogin = $decode_param2->Event->Victim->Login; // Victim Login
				$HitVictimName = $decode_param2->Event->Victim->Name; // Victim NickName
				$HitVictimCurrentClan = $decode_param2->Event->Victim->CurrentClan; // Victim CurrentClan
				return;	
			case 'OnArmorEmpty':// This callback is sent when a player reaches 0 armor (eliminated by another player, falling in an offzone)
				$decode_param2 = json_decode($param2);
				$ArmorEmptyWeaponNum = $decode_param2->Event->WeaponNum; // WeaponNum see sidenotes;
				$ArmorEmptyShooterLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$ArmorEmptyShooterNickName = $decode_param2->Event->Shooter->Name; // Shooter NickName
				$ArmorEmptyShooterCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan see sidenotes;
				$ArmorEmptyVictimLogin = $decode_param2->Event->Victim->Login; // Victim login
				$ArmorEmptyVictimName = $decode_param2->Event->Victim->Name; // Victim NickName
				$ArmorEmptyVictimCurrentClan = $decode_param2->Event->Victim->CurrentClan; // Victim CurrentClan see sidenotes;
				return;
			case 'OnPlayerRequestRespawn':// This callback is sent when a player requests a respawn.
				$decode_param2 = json_decode($param2);
				return;
			case 'OnShoot':// This callback is sent when a player shoots.
				$decode_param2 = json_decode($param2);
				$ShootWeaponNum = $decode_param2->Event->WeaponNum; // WeaponNum see sidenotes;
				$ShootLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$ShootNickName = $decode_param2->Event->Shooter->Name; // Shooter NickName
				$ShootCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan
				return;
			case 'OnNearMiss':// This callback is sent when the attacker shot a Laser near a defender without hitting him.
				$decode_param2 = json_decode($param2);
				$NearMissCM = $decode_param2->Event->MissDist; //$Distance * 100; // mm / cm // M
				$NearMissShooterLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$NearMissShooterNickName = $decode_param2->Event->Shooter->Name; // Shooter Name
				$NearMissShooterCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan
				Logger::getLog('EliteError')->write($NearMissCM); // Used for EliteErrors... (Bad code)
				return;
			case 'EndTurn':// This callback is sent at the end of each turn.
				$decode_param2 = json_decode($param2);
				$TurnNumber = $decode_param2->TurnNumber; // TurnNumber
				$TurnAttackingClan = $decode_param2->AttackingClan; // AttackClan see sidenotes;
				$TurnDefendingClan = $decode_param2->DefendingClan; // DefendClan see sidenotes;
				$TurnAttackPlayerLogin = $decode_param2->AttackingPlayer->Login; // AtkPlayer Login
				$TurnAttackPlayerNickName = $decode_param2->AttackingPlayer->Name; // AtkPlayer NickName
				$TurnAttackPlayerCurrentClan = $decode_param2->AttackingPlayer->CurrentClan; // AtkPlayer CurrentClan see sidenotes;
				$TurnWinnerClan = $decode_param2->TurnWinnerClan; // Winner of the Turn see sidenotes;
				$TurnWinType = $decode_param2->WinType; // WinType; See the script
				$Clan1RoundScore = $decode_param2->Clan1RoundScore; // Clan1RoundScore which is Blue Score
				$Clan2RoundScore = $decode_param2->Clan2RoundScore; // Clan2RoundScore which is Red Score
				$Clan1MapScore = $decode_param2->Clan1MapScore; // MapScore for Blue
				$Clan2MapScore = $decode_param2->Clan2MapScore; // MapScore for Red
				$PlayerBlue1Login = $decode_param2->ScoresTable[0]->Login; // Blue Player Login
				$PlayerBlue2Login = $decode_param2->ScoresTable[1]->Login; // Blue Player Login
				$PlayerBlue3Login = $decode_param2->ScoresTable[2]->Login; // Blue Player Login
				$PlayerRed1Login = $decode_param2->ScoresTable[3]->Login; // Red Player Login
				$PlayerRed2Login = $decode_param2->ScoresTable[4]->Login; // Red Player Login
				$PlayerRed3Login = $decode_param2->ScoresTable[5]->Login; // Red Player Login
				$PlayerBlue1CurrentClan = $decode_param2->ScoresTable[0]->CurrentClan; // Player Blue CurrentClan
				$PlayerBlue2CurrentClan = $decode_param2->ScoresTable[1]->CurrentClan; // Player Blue CurrentClan
				$PlayerBlue3CurrentClan = $decode_param2->ScoresTable[2]->CurrentClan; // Player Blue CurrentClan
				return;
			case 'EndMap'://This callback is sent at the end of each map.
				$decode_param2 = json_decode($param2);
				$MapNumber = $decode_param2->MapNumber; // MapNumber
				$MapWinnerClan = $decode_param2->MapWinnerClan; // WinnerClan see sidenotes;
				$Clan1MapScore = $decode_param2->Clan1MapScore; // Score of Blue on Map
				$Clan2MapScore = $decode_param2->Clan2MapScore; // Score of Red on Map
				return;
			case 'EndSubmatch'://This callback is sent at the end of each submatch.
				$decode_param2 = json_decode($param2);
				$SubmatchNumber = $decode_param2->SubmatchNumber; // SubmatchNumber
				return;
			case 'EndMatch'://This callback is sent at the end of each match.
				$decode_param2 = json_decode($param2);
				$MatchWinnerClan = $decode_param2->MatchWinnerClan; // Match winner Clan
				$Clan1MapScore = $decode_param2->Clan1MapScore;
				$Clan2MapScore = $decode_param2->Clan2MapScore;
				$TimeStamp = $decode_param2->TimeStamp;
				return;
				
		}
	}
}

?>