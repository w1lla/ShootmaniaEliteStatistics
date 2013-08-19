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
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;

class Elite extends \ManiaLive\PluginHandler\Plugin {

protected $MatchNumber;
protected $TurnNumber;

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
		
		if(!$this->db->tableExists('captures')) {
			$q = "CREATE TABLE IF NOT EXISTS `captures` (
  `capture_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `matchId` mediumint(9) NOT NULL DEFAULT '0',
  `capture_playerLogin` varchar(60) NOT NULL,
  `capture_mapUid` varchar(60) NOT NULL,
  `capture_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`capture_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
		if(!$this->db->tableExists('kills')) {
			$q = "CREATE TABLE IF NOT EXISTS `kills` (
  `kill_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `kill_matchId` mediumint(9) NOT NULL DEFAULT '0',
  `kill_victim` varchar(60) NOT NULL,
  `kill_shooter` varchar(60) NOT NULL,
  `kill_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `kill_mapUid` varchar(60) NOT NULL,
  PRIMARY KEY (`kill_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('match_details')) {
			$q = "CREATE TABLE IF NOT EXISTS `match_details` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `matchId` mediumint(9) NOT NULL DEFAULT '0',
  `team` varchar(50) NOT NULL DEFAULT '',
  `mapUid` varchar(60) NOT NULL,
  `attack` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `defence` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `capture` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `timeOver` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `attackWinEliminate` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `defenceWinEliminate` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('players')) {
			$q = "CREATE TABLE IF NOT EXISTS `players` (
  `player_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `player_matchId` mediumint(9) NOT NULL DEFAULT '0',
  `player_login` varchar(50) NOT NULL,
  `player_nickname` varchar(100) DEFAULT NULL,
  `player_nation` varchar(50) NOT NULL,
  `player_updatedat` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `player_teamName` varchar(100) DEFAULT NULL,
  `player_teamID` mediumint(9) NOT NULL DEFAULT '0',
  `player_kills` mediumint(9) NOT NULL DEFAULT '0',
  `player_shots` mediumint(9) NOT NULL DEFAULT '0',
  `player_nearmiss` mediumint(9) NOT NULL DEFAULT '0',
  `player_hits` mediumint(9) NOT NULL DEFAULT '0',
  `player_deaths` mediumint(9) NOT NULL DEFAULT '0',
  `player_captures` mediumint(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player_id`),
  UNIQUE KEY `player_login` (`player_login`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('elite_maps')) {
			$q = "CREATE TABLE IF NOT EXISTS `elite_maps` (
  `map_id` MEDIUMINT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                    `map_uid` VARCHAR( 27 ) NOT NULL ,
                                    `map_name` VARCHAR( 100 ) NOT NULL ,
									`map_author` VARCHAR( 30 ) NOT NULL
) CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = MYISAM ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('match')) {
			$q = "CREATE TABLE IF NOT EXISTS `match` (
  `matchId` INT NOT NULL AUTO_INCREMENT,
  `MatchName` varchar(50) NOT NULL DEFAULT '',
  `teamBlue` mediumint(9) NOT NULL DEFAULT '0',
  `teamRed` mediumint(9) NOT NULL DEFAULT '0',
  `mapUid` varchar(60) NOT NULL,
  `turnNumber` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Mapscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Matchscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `Mapscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `Matchscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `MatchStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `MatchEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`matchId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}

		if(!$this->db->tableExists('matchMap')) {
			$q = "CREATE TABLE IF NOT EXISTS `matchMap` (
  `matchMapId` INT NOT NULL AUTO_INCREMENT,
  `matchId` mediumint(9) NOT NULL DEFAULT '0',
  `mapUid` varchar(60) NOT NULL,
  `turnNumber` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `MatchStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `MatchEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `AtkId` mediumint(9),
  PRIMARY KEY (`matchMapId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}

				if(!$this->db->tableExists('teams')) {
			$q = "CREATE TABLE IF NOT EXISTS `teams` (
  `teamID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `teamMatchId` mediumint(9) NOT NULL DEFAULT '0',
  `teamName` varchar(50) NOT NULL DEFAULT '',
  `team_EmblemUrl` varchar(255) NOT NULL,
  `team_ZonePath` varchar(50) NOT NULL,
  `team_RGB` varchar(50) NOT NULL,
PRIMARY KEY (`teamID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
		$this->db->execute($q);
		}
		
		$this->updateServerChallenges();
				
		\ManiaLive\Event\Dispatcher::register(\ManiaLivePlugins\NadeoLive\XmlRpcScript\Event::getClass(), $this);
		
		$this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));
		
		Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Core v' . $this->getVersion());
		$this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [Shootmania] Elite Stats$fa0!');

			foreach($this->storage->players as $player) {
			$this->onPlayerConnect($player->login, false);
		}

		foreach($this->storage->spectators as $player) {
			$this->onPlayerConnect($player->login, false);
		}
		
		try {
        $this->connection->restartMap();
                } catch (\Exception $ex) {
                }
		
		$this->enableDedicatedEvents(ServerEvent::ON_MODE_SCRIPT_CALLBACK);
	}
	
	public function onModeScriptCallback($param1, $param2)
	{
		switch ($param1)
		{
			case 'LibXmlRpc_BeginMatch':
			//var_dump($param2);
				$this->onXmlRpcEliteMatchStart($param2);
				break;
			case 'BeginMatch':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteMatchStart($parameter);
				break;
			case 'BeginMap':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteMatchStart($parameter);
			break;
			case 'BeginWarmup':
			break;
			case 'EndWarmup':
			break;
			case 'BeginTurn':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteBeginTurn($parameter);
				break;
			case 'OnShoot':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteShoot($parameter);
				break;
			case 'OnHit':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteHit($parameter);
				break;
			case 'OnCapture':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteCapture($parameter);
				break;	
			case 'OnArmorEmpty':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteArmorEmpty($parameter);
				break;
			case 'OnNearMiss':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteNearMiss($parameter);
				break;
			case 'EndTurn':
			$parameter = json_decode($param2);
				$this->onXmlRpcEliteEndTurn($parameter);
				break;
			case 'LibXmlRpc_EndMatch':
			$this->onXmlRpcEliteEndMatch($param2);
			break;
			case 'EndMap':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteEndMatch($parameter);
			break;
		}
	}
	
	function updateServerChallenges() {
        //get server challenges
        $serverChallenges = $this->storage->maps;
        //get database challenges

        $g = "SELECT * FROM `elite_maps`;";
        $query = $this->db->query($g);

        $databaseUid = array();
        //get database uid's of tracks.
        while ($data = $query->fetchStdObject()) {
            $databaseUid[$data->map_uid] = $data->map_uid;
        }

        unset($data);
        $addCounter = 0;
        foreach ($serverChallenges as $data) {
            // check if database doesn't have the challenge already.
            if (!array_key_exists($data->uId, $databaseUid)) {
                $this->insertMap($data);
                $addCounter++;
            }
        }
    }
	
	public function insertMap($data) {

        $q = "INSERT INTO `elite_maps` (`map_uid`,
                                    `map_name`,
									`map_author`
                                    )
                                VALUES (" . $this->db->quote($data->uId) . ",
                                " . $this->db->quote($data->name) . ",
                                " . $this->db->quote($data->author) . "
                                )";
        $this->db->query($q);
    }

	function extendWarmup($login)
	{
		$this->connection->triggerModeScriptEvent('extendWarmup',''); // param1 is the first callback sent to the script, param2 can be anything.
	}
	
	function endWarmup($login)
	{
		$this->connection->triggerModeScriptEvent('endWarmup','');
	}
		
	function onPlayerConnect($login, $isSpectator) {
		$player = $this->storage->getPlayerObject($login);
		$this->insertPlayer($player);
	}
	
	function insertPlayer($player) {
		$g =  "SELECT * FROM `players` WHERE `player_login` = ".$this->db->quote($player->login)." and `player_matchId` = ".$this->db->quote($this->MatchNumber).";";
		//var_dump($g);
		$execute = $this->db->execute($g);
		$teamId = $player->teamId;
		$teamName = $this->connection->getTeamInfo($teamId+1)->name;
		if($execute->recordCount() == 0) {
			$q = "INSERT INTO `players` (
					`player_matchId`,
					`player_login`,
					`player_nickname`,
					`player_nation`,
					`player_updatedat`,
					`player_teamID`,
					`player_teamName`
				  ) VALUES (
					'0',
					".$this->db->quote($player->login).",
					".$this->db->quote($player->nickName).",
					".$this->db->quote(str_replace('World|', '', $player->path)).",
					'".date('Y-m-d H:i:s')."',
					".$this->db->quote($teamId+1).",
					".$this->db->quote($teamName)."
				  )";
		} else {
			$q = "UPDATE `players`
				  SET `player_nickname` = ".$this->db->quote($player->nickName).",
				      `player_nation` = ".$this->db->quote(str_replace('World|', '', $player->path)).",
				      `player_updatedat` = '".date('Y-m-d H:i:s')."'
				  WHERE `player_login` = ".$this->db->quote($player->login)."";
				 //var_dump($q);
		}

		$this->db->execute($q);
	}
	
	//Xml RPC events
	function onXmlRpcEliteBeginTurn($content)
	{
	foreach ($this->storage->players as $login => $player){
	$q = "UPDATE `players`
				  SET `player_nickname` = ".$this->db->quote($player->nickName).",
				      `player_nation` = ".$this->db->quote(str_replace('World|', '', $player->path)).",
				      `player_updatedat` = '".date('Y-m-d H:i:s')."',
					  `player_matchId` = '".$this->MatchNumber."'
				  WHERE `player_login` = ".$this->db->quote($player->login)."";
	$this->db->execute($q);
	}
	//var_dump($content);
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnNumber = $content->TurnNumber;
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	//$AttackClanRGB = $this->connection->getTeamInfo($AttackingClan)->rGB;
	//$DefClanRGB = $this->connection->getTeamInfo($DefendingClan)->rGB;
	//$AttackClanEmblemUrl = $this->connection->getTeamInfo($AttackingClan)->emblemUrl;
	//$DefClanEmblemUrl = $this->connection->getTeamInfo($DefendingClan)->emblemUrl;
	//$AttackClanZonePath = $this->connection->getTeamInfo($AttackingClan)->zonePath;
	//$DefClanZonePath = $this->connection->getTeamInfo($DefendingClan)->zonePath;
	//AtkQuery
	$g = "SELECT * FROM `match_details` WHERE `MapUid` = ".$this->db->quote($this->storage->currentMap->uId)." and `team` =".$this->db->quote($AttackClan).";";
	$execute = $this->db->execute($g);
	if($execute->recordCount() == 0) {
	$q = "INSERT INTO `match_details` (
					`matchId`,
					`team`,
					`mapUid`,
					`attack`,
					`defence`,
					`capture`,
					`timeOver`,
					`attackWinEliminate`,
					`defenceWinEliminate`
				  ) VALUES (
					".$this->MatchNumber.",
					".$this->db->quote($AttackClan).",
					".$this->db->quote($this->storage->currentMap->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0'
				  )";
	} else {
	}
	$this->db->execute($q);
		//DefQuery
	$g = "SELECT * FROM `match_details` WHERE `MapUid` = ".$this->db->quote($this->storage->currentMap->uId)." and `team` =".$this->db->quote($DefClan).";";
	$execute = $this->db->execute($g);
	if($execute->recordCount() == 0) {
	$q = "INSERT INTO `match_details` (
					`matchId`,
					`team`,
					`mapUid`,
					`attack`,
					`defence`,
					`capture`,
					`timeOver`,
					`attackWinEliminate`,
					`defenceWinEliminate`
				  ) VALUES (
					".$this->MatchNumber.",
					".$this->db->quote($DefClan).",
					".$this->db->quote($this->storage->currentMap->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0'
				  )";

	} else {
		}
		
		$this->db->execute($q);
	$qmapid = $this->db->execute("Select player_id from players where `player_login` =".($this->db->quote($content->AttackingPlayer->Login))."")->fetchObject();
	$mapmatchAtk = "UPDATE `matchmap`
				  SET `AtkId` = '".($qmapid->player_id)."'
				  WHERE `matchId` = ".$this->db->quote($this->MatchNumber)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($mapmatchAtk);
	}
	
	function onXmlRpcEliteEndTurn($content)
	{
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnWinnerClan = $content->TurnWinnerClan;
	$WinType = $content->WinType;
	$Clan1RoundScore = $content->Clan1RoundScore;
	$Clan2RoundScore = $content->Clan2RoundScore;
	$Clan1MapScore = $content->Clan1MapScore;
	$Clan2MapScore = $content->Clan2MapScore;
	$TurnNumber = $content->TurnNumber;
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnNumber = $content->TurnNumber;
	$this->TurnNumber = $TurnNumber;
	
	$mapmatchAtk = "UPDATE `matchmap`
				  SET `AtkId` = 'NULL'
				  WHERE `matchId` = ".$this->db->quote($this->MatchNumber)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($mapmatchAtk);
	
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	
	$attacks = $this->db->execute("SELECT * FROM `match_details` WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
	$qatk = "UPDATE `match_details`
				  SET `attack` = '".($attacks->attack+1)."'
				  WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qatk);
	
	if ($WinType == 'Capture'){
	$qcapture = "UPDATE `match_details`
				  SET `capture` = '".($attacks->capture+1)."'
				  WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qcapture);
	}
	
		if ($WinType == 'DefenseEliminated'){
	$qawe = "UPDATE `match_details`
				  SET `attackWinEliminate` = '".($attacks->attackWinEliminate+1)."'
				  WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";		  
	$this->db->execute($qawe);
	}
	
	$defenses = $this->db->execute("SELECT * FROM `match_details` WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
	$qdef = "UPDATE `match_details`
				  SET `defence` = '".($defenses->defence+1)."'
				  WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
		$this->db->execute($qdef);
		
	if ($WinType == 'TimeLimit'){
	$qtl = "UPDATE `match_details`
				  SET `timeOver` = '".($defenses->timeOver+1)."'
				  WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qtl);
	}
	
		if ($WinType == 'AttackEliminated'){
	$qde = "UPDATE `match_details`
				  SET `defenceWinEliminate` = '".($defenses->defenceWinEliminate+1)."'
				  WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qde);
	}
	
	// RoundScore Blue
	$qrsb = "UPDATE `match`
	set Roundscore_blue = ".$Clan1RoundScore." where matchId = ".$this->MatchNumber." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qrsb);
	// RoundScore Red
	$qrsr = "UPDATE `match`
	set Roundscore_red = ".$Clan2RoundScore." where matchId = ".$this->MatchNumber." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qrsr);
	
	//MapScore Blue
	$qmsb = "UPDATE `match`
	set Mapscore_blue = ".$Clan1MapScore." where matchId = ".$this->MatchNumber." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmsb);
	//MapScore Red
	$qmsr = "UPDATE `match`
	set Mapscore_red = ".$Clan2MapScore." where matchId = ".$this->MatchNumber." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmsr);
	
	//MatchScore Blue
	$qmmsb = "UPDATE `match`
	set Matchscore_blue = ".$Clan1MapScore." where matchId = ".$this->MatchNumber." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmmsb);
	//MatchScore Red
	$qmmsr = "UPDATE `match`
	set Matchscore_red = ".$Clan2MapScore." where matchId = ".$this->MatchNumber." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmmsr);
	
	$TurnRound = "UPDATE `match` set turnNumber = ".$TurnNumber." where matchId = ".$this->MatchNumber." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($TurnRound);
	}
	
	function onXmlRpcEliteArmorEmpty($content)
	{
	$map = $this->connection->getCurrentMapInfo();

		// Insert kill into the database
		$q = "INSERT INTO `kills` (
				`kill_matchId`,
				`kill_victim`,
				`kill_shooter`,
				`kill_time`,
				`kill_mapUid`
			  ) VALUES (
			  ".$this->MatchNumber.",
			    '".$content->Event->Victim->Login."',
			    '".$content->Event->Shooter->Login."',
			    '".date('Y-m-d H:i:s')."',
			    '".$map->uId."'
			  )";
		$this->db->execute($q);

		// update kill/death statistics
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."' and `player_matchId` = ".$this->db->quote($this->MatchNumber)."")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_kills` = '".($shooterinfo->player_kills+1)."' WHERE `player_login` = '".$content->Event->Shooter->Login."'");

		$victiminfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Victim->Login."'and `player_matchId` = ".$this->db->quote($this->MatchNumber)."")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_deaths` = '".($victiminfo->player_deaths+1)."' WHERE `player_login` = '".$content->Event->Victim->Login."'");

		Console::println('['.date('H:i:s').'] [ShootMania] [Elite] '.$content->Event->Victim->Login.' was killed by '.$content->Event->Shooter->Login);
	

	}
	
	function onXmlRpcEliteShoot($content)
	{
	
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."' and `player_matchId` = ".$this->db->quote($this->MatchNumber).";")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_shots` = '".($shooterinfo->player_shots+1)."' WHERE `player_login` = '".$content->Event->Shooter->Login."'");
		
	}
	
	function onXmlRpcEliteHit($content)
	{
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."' and `player_matchId` = ".$this->db->quote($this->MatchNumber)."")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_hits` = '".($shooterinfo->player_hits+1)."' WHERE `player_login` = '".$content->Event->Shooter->Login."'");
	
	}
	
	function onXmlRpcEliteMatchStart($content) //Not Working??
	{
	$Blue = $this->connection->getTeamInfo(1)->name;
	$Red = $this->connection->getTeamInfo(2)->name;
	$BlueRGB = $this->connection->getTeamInfo(1)->rGB;
	$RedRGB = $this->connection->getTeamInfo(2)->rGB;
	$BlueEmblemUrl = $this->connection->getTeamInfo(1)->emblemUrl;
	$RedEmblemUrl = $this->connection->getTeamInfo(2)->emblemUrl;
	$BlueZonePath = $this->connection->getTeamInfo(1)->zonePath;
	$RedZonePath = $this->connection->getTeamInfo(2)->zonePath;
	$MatchName = ''.$Blue.' vs '.$Red.'';
	
	$map = $this->connection->getCurrentMapInfo();
	$gmatch = "SELECT * FROM `match` WHERE `matchId` = ".$this->db->quote($this->MatchNumber)." and `mapUid` = ".$this->db->quote($map->uId)."";
	//var_dump($gmatch);
	$execute = $this->db->execute($gmatch);
	if($execute->recordCount() == 0) {
	$qmatch = "INSERT INTO `match` (
					`MatchName`,
					`teamBlue`,
					`teamRed`,
					`mapUid`,
					`Roundscore_blue`,
					`Mapscore_blue`,
					`Matchscore_blue`,
					`Roundscore_red`,
					`Mapscore_red`,
					`Matchscore_red`,
					`MatchStart`
				  ) VALUES (
					".$this->db->quote($MatchName).",
					'1',
					'2',
					".$this->db->quote($map->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					'".date('Y-m-d H:i:s')."'
				  )";
	$this->db->execute($qmatch);
	$this->MatchNumber = $this->db->insertID();
		} else {
	}
	
	$map = $this->connection->getCurrentMapInfo();
	$gmatch = "SELECT * FROM `match` WHERE `matchId` = ".$this->db->quote($this->MatchNumber)." and `mapUid` = ".$this->db->quote($map->uId)."";
	//var_dump($gmatch);
	$execute = $this->db->execute($gmatch);
	if($execute->recordCount() == 0) {
	$qmatch = "INSERT INTO `match` (
					`MatchName`,
					`teamBlue`,
					`teamRed`,
					`mapUid`,
					`Roundscore_blue`,
					`Mapscore_blue`,
					`Matchscore_blue`,
					`Roundscore_red`,
					`Mapscore_red`,
					`Matchscore_red`,
					`MatchStart`
				  ) VALUES (
					".$this->db->quote($MatchName).",
					'1',
					'2',
					".$this->db->quote($map->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					'".date('Y-m-d H:i:s')."'
				  )";
	$this->db->execute($qmatch);
	} else {
	}

	$mapmatch = "SELECT * FROM `matchmap` WHERE `matchId` = ".$this->db->quote($this->MatchNumber)." and `mapUid` = ".$this->db->quote($map->uId)."";
	//var_dump($gmatch);
	$mapmatchexecute = $this->db->execute($mapmatch);
	if($mapmatchexecute->recordCount() == 0) {
	$qmapmatch = "INSERT INTO `matchmap` (
					`matchId`,
					`mapUid`,
					`Roundscore_blue`,
					`Roundscore_red`,
					`MatchStart`
				  ) VALUES (
					".$this->MatchNumber.",
					".$this->db->quote($map->uId).",
					'0',
					'0',
					'".date('Y-m-d H:i:s')."'
				  )";
	$this->db->execute($qmapmatch);
	} else {
	}
	
	$Blueteaminfo = "SELECT * FROM `teams` WHERE `teamName` = ".$this->db->quote($Blue)." and `teamMatchId` = '1';";
	$execute = $this->db->execute($Blueteaminfo);
	if($execute->recordCount() == 0) {
	$qbluematch = "INSERT INTO `teams` (
					`teamMatchId`,
					`teamName`,
					`team_EmblemUrl`,
					`team_ZonePath`,
					`team_RGB`
				  ) VALUES (
					'1',
					".$this->db->quote($Blue).",
					".$this->db->quote($BlueEmblemUrl).",
					".$this->db->quote($BlueZonePath).",
					".$this->db->quote($BlueRGB)."
				  )";
		$this->db->execute($qbluematch);
	}
	
	$Redteaminfo = "SELECT * FROM `teams` WHERE `teamName` = ".$this->db->quote($Red)." and `teamMatchId` = '2';";
	$executes = $this->db->execute($Redteaminfo);
	if($executes->recordCount() == 0) {
	$qb = "INSERT INTO `teams` (
					`teamMatchId`,
					`teamName`,
					`team_EmblemUrl`,
					`team_ZonePath`,
					`team_RGB`
				  ) VALUES (
					'2',
					".$this->db->quote($Red).",
					".$this->db->quote($RedEmblemUrl).",
					".$this->db->quote($RedZonePath).",
					".$this->db->quote($RedRGB)."
					)";
		$this->db->execute($qb);
	}

	}
	
	function onXmlRpcEliteEndMatch($content) //Not Working??
	{
	$queryEnd = "UPDATE `match` SET `MatchEnd` = '".date('Y-m-d H:i:s')."' where `matchId` = ".$this->db->quote($this->MatchNumber)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($queryEnd);
	
	$querymapEnd = "UPDATE `matchmap` SET `MatchEnd` = '".date('Y-m-d H:i:s')."' where `matchId` = ".$this->db->quote($this->MatchNumber)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($querymapEnd);
	}
	
	function onXmlRpcEliteCapture($content)
	{
	$map = $this->connection->getCurrentMapInfo();
	
		$qCap = "INSERT INTO `captures` (
				`matchId`,
				`capture_playerLogin`,
				`capture_mapUid`,
				`capture_time`
			  ) VALUES (
			  ".$this->MatchNumber.",
			    '".$content->Event->Player->Login."',
			    '".$map->uId."',
			    '".date('Y-m-d H:i:s')."'
			  )";
		$this->db->execute($qCap);

		// update capture statistics
		$info = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Player->Login."' and `player_matchId` = ".$this->db->quote($this->MatchNumber)."")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_captures` = '".($info->player_captures+1)."' WHERE `player_login` = '".$content->Event->Player->Login."'");
	}
	
	function onXmlRpcEliteNearMiss($content)
	{
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."' and `player_matchId` = ".$this->db->quote($this->MatchNumber)."")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_nearmiss` = '".($shooterinfo->player_nearmiss+1)."' WHERE `player_login` = '".$content->Event->Shooter->Login."'");

	}
	
		protected function getWeaponName($num)
	{
		switch ($num)
		{
			case 1:
				return 'laser';
			case 2:
				return 'rocket';
			case 3:
				return 'nucleus';
			case 5:
				return 'arrow';
			default:
				return '';
		}
	}
	
	
}

?>