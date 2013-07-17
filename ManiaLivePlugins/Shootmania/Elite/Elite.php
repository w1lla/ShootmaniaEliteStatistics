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
		
		$cmd = $this->registerChatCommand('weaponAdd', 'weaponAdd', 0, true, $admins);
		$cmd->help = 'Add Weapons to DB.';
		
		$this->enableDatabase();
		$this->enableDedicatedEvents();
		

		
		Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Core v' . $this->getVersion());
		$this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [Shootmania] Elite Stats$fa0!');
		
		
		if(!$this->db->tableExists('Matches')) {
			$q = "CREATE TABLE IF NOT EXISTS `Matches` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `team1` varchar(60) NOT NULL,
  `team2` varchar(60) NOT NULL,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8";
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
  `matchId` INT (13) NOT NULL,
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
  `matchId` INT(13) NOT NULL,
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
  `matchId` INT (13) NOT NULL,
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
  `matchId` INT (13) NOT NULL,
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
	
	function weaponAdd($login)
	{
		/**
		Adding WeaponsNum to Database
		**/
		$WeaponQuery = "INSERT INTO `Weapons` (`id`, `name`) VALUES (1, 'Rail'),(2, 'Rocket'),(3, 'Nucleus'),(5, 'Arrow');";
		$this->db->execute('BEGIN');
				try
				{				
				$this->db->execute($WeaponQuery);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
				return;
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
				$MatchName = ''.$BlueName.' vs '.$RedName.'';
				$this->MatchNumber = $MatchNumber;
				$BeginMatchQuery = "INSERT INTO  `matches` (
				`id` ,
				`name` ,
				`team1` ,
				`team2` ,
				`startTime` ,
				`endTime`
				)
				VALUES (
				'', " . $this->db->quote($MatchName) . ", " . $this->db->quote($BlueName) . ", " . $this->db->quote($RedName) . ", " . $StartTime . ", '0');";
				// Perform Query
				$this->db->execute('BEGIN');
				try
				{
				$this->db->execute($BeginMatchQuery);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
				
				return;
			case 'BeginMap':
				$decode_param2 = json_decode($param2);
				$MapNum = $decode_param2->MapNumber;
				$this->MapNum = $MapNum;
				$map = $this->connection->getCurrentMapInfo();
				$MapName = $map->name;
				$this->MapName = $MapName;
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
				$map = $this->connection->getCurrentMapInfo();
				$MapName = $map->name;
				$this->MapName = $MapName;
				$decode_param2 = json_decode($param2);
				$TurnNumber = $decode_param2->TurnNumber;
				$AtkClan = $decode_param2->AttackingClan;
				$DefClan = $decode_param2->DefendingClan;
				$AtkPlayerLogin = $decode_param2->AttackingPlayer->Login;
				$AtkPlayerNickName = $decode_param2->AttackingPlayer->Name;
				$this->TurnNumber = $TurnNumber;
				$BlueRGB = $this->connection->getTeamInfo(1)->rGB;
				$RedRGB = $this->connection->getTeamInfo(1)->rGB;
				$BlueemblemUrl = $this->connection->getTeamInfo(1)->emblemUrl;
				$RedemblemUrl = $this->connection->getTeamInfo(2)->emblemUrl;
				$BlueClubLink = $this->connection->getTeamInfo(1)->clubLinkUrl;
				$RedClubLink = $this->connection->getTeamInfo(2)->clubLinkUrl;
				$BlueName = $this->connection->getTeamInfo(1)->name;
				$RedName = $this->connection->getTeamInfo(2)->name;
				$Bluetest = $this->db->query("SELECT * FROM teams WHERE `team`= " . $this->db->quote($BlueName) . "  AND `mapName` = " . $this->db->quote($this->MapName) . " LIMIT 1;")->fetchObject();
				//echo $Bluetest;
				$Redtest = $this->db->query("SELECT * FROM teams WHERE `team`= " . $this->db->quote($RedName) . "  AND `mapName` = " . $this->db->quote($this->MapName) . " LIMIT 1;")->fetchObject();
				//echo $Redtest;
				//echo $TurnWinnerClan;
            if ($Bluetest === false) {
                $BlueENDTurnQuery = "INSERT INTO  `teams` (
				`team` ,
				`matchId` ,
				`mapNum` ,
				`mapName` ,
				`attack` ,
				`defence`,
				`capture`,
				`timeOver`,
				`attackWinEliminate`,
				`defenceWinEliminate`,
				`endTime`,
				`ClublinkUrl`,
				`TeamColour`
				)
				VALUES (" . $this->db->quote($BlueName) . ", " . $this->db->quote($this->MatchNumber) . ", " . $this->db->quote($this->MapNum) . ", " . $this->db->quote($this->MapName) . ", '0', '0', '0', '0', '0', '0', '0', " . $this->db->quote($BlueClubLink) . ", " . $this->db->quote($BlueRGB) . ");";
				// Perform Query
				$this->db->execute('BEGIN');
				try
				{				
				$this->db->execute($BlueENDTurnQuery);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
				}
				if ($Redtest === false) {
				$RedENDTurnQuery = "INSERT INTO  `teams` (
				`team` ,
				`matchId` ,
				`mapNum` ,
				`mapName` ,
				`attack` ,
				`defence`,
				`capture`,
				`timeOver`,
				`attackWinEliminate`,
				`defenceWinEliminate`,
				`endTime`,
				`ClublinkUrl`,
				`TeamColour`
				)
				VALUES (" . $this->db->quote($RedName) . ", " . $this->db->quote($this->MatchNumber) . ", " . $this->db->quote($this->MapNum) . ", " . $this->db->quote($this->MapName) . ", '0', '0', '0', '0', '0', '0', '0', " . $this->db->quote($RedClubLink) . ", " . $this->db->quote($RedRGB) . ");";
				// Perform Query
				$this->db->execute('BEGIN');
				try
				{				
				$this->db->execute($RedENDTurnQuery);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
				}
				return;
			case 'OnCapture':// This callback is sent when the attacker captured the pole
				$decode_param2 = json_decode($param2);
				$PlayerCapturedLogin = $decode_param2->Event->Player->Login;
				$PlayerCapturedNickname = $decode_param2->Event->Player->Name;
				$PlayerCapturedClan = $decode_param2->Event->Player->CurrentClan;
				$Captest = $this->db->query("SELECT * FROM captures WHERE `roundId`= " . $this->db->quote($this->TurnNumber) . "  AND `mapName` = " . $this->db->quote($this->MapName) . " LIMIT 1;")->fetchObject();
				 if ($Captest === false) {
				$CaptureQuery = "INSERT INTO  `captures` (
				`player` ,
				`team` ,
				`roundId` ,
				`mapNum` ,
				`mapName` ,
				`matchId`
				)
				VALUES (
				" . $this->db->quote($PlayerCapturedLogin) . ", " . $this->db->quote($PlayerCapturedClan) . ", " . $this->db->quote($this->TurnNumber) . ", " .$this->db->quote($this->MapNum) . ", " . $this->db->quote($this->MapName) . ", " . $this->db->quote($this->MatchNumber) . ");";
				// Perform Query
				$this->db->execute('BEGIN');
				try
				{				
				$this->db->execute($CaptureQuery);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
				} else {
				// Perform Query
				$CapUpdate = "Update captures set roundId = ".$this->db->quote($this->TurnNumber).", mapNum = ". $this->db->quote($this->MapNum)." WHERE `mapName` = " . $this->db->quote($this->MapName) . " AND `player` = ".$this->db->quote($PlayerCapturedLogin)."";			
				$this->db->execute('BEGIN');
				try
				{				
				$this->db->execute($CapUpdate);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
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
				return;
			case 'EndTurn':// This callback is sent at the end of each turn.
				$decode_param2 = json_decode($param2);
				$TurnNumber = $decode_param2->TurnNumber; // TurnNumber
				$EndTime = $decode_param2->Timestamp;
				$TurnAttackingClan = $decode_param2->AttackingClan; // AttackClan see sidenotes;
				$TurnDefendingClan = $decode_param2->DefendingClan; // DefendClan see sidenotes;
				$TurnWinnerClan = $decode_param2->TurnWinnerClan; // Winner of the Turn see sidenotes;
				$TurnWinType = $decode_param2->WinType; // WinType; See the script
				$Clan1RoundScore = $decode_param2->Clan1RoundScore; // Clan1RoundScore which is Blue Score
				$Clan2RoundScore = $decode_param2->Clan2RoundScore; // Clan2RoundScore which is Red Score
				$Clan1MapScore = $decode_param2->Clan1MapScore; // MapScore for Blue
				$Clan2MapScore = $decode_param2->Clan2MapScore; // MapScore for Red
				$BlueRGB = $this->connection->getTeamInfo(1)->rGB;
				$RedRGB = $this->connection->getTeamInfo(1)->rGB;
				$BlueemblemUrl = $this->connection->getTeamInfo(1)->emblemUrl;
				$RedemblemUrl = $this->connection->getTeamInfo(2)->emblemUrl;
				$BlueClubLink = $this->connection->getTeamInfo(1)->clubLinkUrl;
				$RedClubLink = $this->connection->getTeamInfo(2)->clubLinkUrl;
				$BlueName = $this->connection->getTeamInfo(1)->name;
				$RedName = $this->connection->getTeamInfo(2)->name;
				echo $TurnWinType;
				$WinAtkEl = '+1';
				if($TurnWinType == "DefenseEliminated"){
				$WinDefEl = '+1';
				}
				$WinAtkEl = '0';
				if($TurnWinType == "AttackEliminated"){
				$WinAtkEl = '+1';
				}
				$WinCap = '0';
				if($TurnWinType == "Capture"){
				$WinCap = '+1';
				}
				$WinTime = '0';
				if($TurnWinType == "TimeLimit"){
				$WinTime = '+1';
				}
				$AtkClan = '0';
				if ($TurnAttackingClan == "1"){
				$AtkClan = '+1';
				}
				$AtkClan = '0';
				if ($TurnAttackingClan == "2"){
				$AtkClan = '+1';
				}
				$DefClan = '0';
				if ($TurnDefendingClan == "1"){
				$DefClan = '+1';
				}
				$DefClan = '0';
				if ($TurnDefendingClan == "2"){
				$DefClan = '+1';
				}
				// Perform Query
				$BlueUpdate = "Update teams set attack = ".$this->db->quote($AtkClan).", defence = ".$this->db->quote($DefClan).", capture = ".$this->db->quote($WinCap).", timeOver = ". $this->db->quote($WinTime).",  attackWinEliminate = " . $this->db->quote($WinDefEl) . ", defenceWinEliminate = ".$this->db->quote($WinAtkEl).", endTime = " . $this->db->quote($EndTime) . " WHERE `team` = " . $this->db->quote($BlueName) . " AND `mapName` = ".$this->db->quote($this->MapName)."";
				echo $BlueUpdate;
				$this->db->execute('BEGIN');
				try
				{				
				$this->db->execute($BlueUpdate);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
			$RedUpdate = "Update teams set attack = ".$this->db->quote($AtkClan).", defence = ".$this->db->quote($DefClan).", capture = ".$this->db->quote($WinCap).", timeOver = ". $this->db->quote($WinTime).",  attackWinEliminate = " . $this->db->quote($WinDefEl) . ", defenceWinEliminate = ".$this->db->quote($WinAtkEl).", endTime = " . $this->db->quote($EndTime) . " WHERE `team` = " . $this->db->quote($RedName) . " AND `mapName` = ".$this->db->quote($this->MapName."");
			$this->db->execute('BEGIN');
				try
				{				
				$this->db->execute($RedUpdate);
				$this->db->execute('COMMIT');
				}
				catch(\Exception $e)
				{
				$this->db->execute('ROLLBACK');
				throw $e;
				}
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