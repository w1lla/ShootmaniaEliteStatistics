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

protected $MatchNumber;

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
		
				if(!$this->db->tableExists('match_main')) {
			$q = "CREATE TABLE IF NOT EXISTS `match_main` (
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
  `turnNumber`  mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore` mediumint(9) DEFAULT NULL,
  `Mapscore` mediumint(9) DEFAULT NULL,
  `Matchscore` mediumint(9) DEFAULT NULL,
  `Team_EmblemUrl` varchar(255) NOT NULL,
  `Team_ZonePath` varchar(50) NOT NULL,
  `Team_RGB` varchar(50) NOT NULL,
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
		$g =  "SELECT * FROM `players` WHERE `player_login` = ".$this->db->quote($player->login).";";
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
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnNumber = $content->TurnNumber;
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	$AttackClanRGB = $this->connection->getTeamInfo($AttackingClan)->rGB;
	$DefClanRGB = $this->connection->getTeamInfo($DefendingClan)->rGB;
	$AttackClanEmblemUrl = $this->connection->getTeamInfo($AttackingClan)->emblemUrl;
	$DefClanEmblemUrl = $this->connection->getTeamInfo($DefendingClan)->emblemUrl;
	$AttackClanZonePath = $this->connection->getTeamInfo($AttackingClan)->zonePath;
	$DefClanZonePath = $this->connection->getTeamInfo($DefendingClan)->zonePath;
	//AtkQuery
	$g = "SELECT * FROM `match_main` WHERE `MapUid` = ".$this->db->quote($this->storage->currentMap->uId)." and `team` =".$this->db->quote($AttackClan).";";
	$execute = $this->db->execute($g);
	if($execute->recordCount() == 0) {
	$q = "INSERT INTO `match_main` (
					`ID`,
					`matchId`,
					`team`,
					`mapUid`,
					`attack`,
					`defence`,
					`capture`,
					`timeOver`,
					`attackWinEliminate`,
					`defenceWinEliminate`,
					`turnNumber`,
					`Roundscore`,
					`Mapscore`,
					`Matchscore`,
					`Team_EmblemUrl`,
					`Team_ZonePath`,
					`Team_RGB`
				  ) VALUES (
					'NULL',
					".$this->MatchNumber.",
					".$this->db->quote($AttackClan).",
					".$this->db->quote($this->storage->currentMap->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					".$TurnNumber.",
					'0',
					'0',
					'0',
					".$this->db->quote($AttackClanEmblemUrl).",
					".$this->db->quote($AttackClanZonePath).",
					".$this->db->quote($AttackClanRGB)."
				  )";
	} else {
		$attack = $this->db->execute("SELECT attack FROM `match_main` WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
		$q = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber."
				  WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	}
	$this->db->execute($q);
		//DefQuery
	$g = "SELECT * FROM `match_main` WHERE `MapUid` = ".$this->db->quote($this->storage->currentMap->uId)." and `team` =".$this->db->quote($DefClan).";";
	$execute = $this->db->execute($g);
	if($execute->recordCount() == 0) {
	$q = "INSERT INTO `match_main` (
					`ID`,
					`matchId`,
					`team`,
					`mapUid`,
					`attack`,
					`defence`,
					`capture`,
					`timeOver`,
					`attackWinEliminate`,
					`defenceWinEliminate`,
					`turnNumber`,
					`Roundscore`,
					`Mapscore`,
					`Matchscore`,
					`Team_EmblemUrl`,
					`Team_ZonePath`,
					`Team_RGB`
				  ) VALUES (
					'NULL',
					".$this->MatchNumber.",
					".$this->db->quote($DefClan).",
					".$this->db->quote($this->storage->currentMap->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					".$TurnNumber.",
					'0',
					'0',
					'0',
					".$this->db->quote($DefClanEmblemUrl).",
					".$this->db->quote($DefClanZonePath).",
					".$this->db->quote($DefClanRGB)."
				  )";
	} else {
	$defense = $this->db->execute("SELECT defence FROM `match_main` WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
	$q = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber."
				  WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
		}
		$this->db->execute($q);
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
	
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	
	$Blue = $this->connection->getTeamInfo(1)->name;
	$Red = $this->connection->getTeamInfo(2)->name;
	
	$attack = $this->db->execute("SELECT attack FROM `match_main` WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
	
	$attacks = $this->db->execute("SELECT * FROM `match_main` WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
	$qatk = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber.",
				      `attack` = '".($attacks->attack+1)."'
				  WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qatk);
	
	if ($WinType == 'Capture'){
	$qcapture = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber.",
				      `capture` = '".($attacks->capture+1)."'
				  WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qcapture);
	}
	
	if ($WinType == 'DefenseEliminated'){
	$qde = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber.",
				      `defenceWinEliminate` = '".($attacks->defenceWinEliminate+1)."'
				  WHERE `team` = ".$this->db->quote($AttackClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qde);
	}
	
	$defense = $this->db->execute("SELECT defence FROM `match_main` WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
	
	$defenses = $this->db->execute("SELECT * FROM `match_main` WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."")->fetchObject();
	
	$qdef = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber.",
				      `defence` = '".($defenses->defence+1)."'
				  WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
		$this->db->execute($qdef);
		
	if ($WinType == 'TimeLimit'){
	$qtl = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber.",
				      `timeOver` = '".($defenses->timeOver+1)."'
				  WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qtl);
	}
	
	if ($WinType == 'AttackEliminated'){
	$qawe = "UPDATE `match_main`
				  SET `turnNumber` = ".$TurnNumber.",
				      `attackWinEliminate` = '".($defenses->attackWinEliminate+1)."'
				  WHERE `team` = ".$this->db->quote($DefClan)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qawe);
	}
	// RoundScore Blue
	$qrsb = "UPDATE `match_main`
	set Roundscore = ".$Clan1RoundScore." WHERE `team` = ".$this->db->quote($Blue)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qrsb);
	// RoundScore Red
	$qrsr = "UPDATE `match_main`
	set Roundscore = ".$Clan2RoundScore." WHERE `team` = ".$this->db->quote($Red)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qrsr);
	
	//MapScore Blue
	$qmsb = "UPDATE `match_main`
	set Mapscore = ".$Clan1MapScore." WHERE `team` = ".$this->db->quote($Red)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmsb);
	//MapScore Red
	$qmsr = "UPDATE `match_main`
	set Mapscore = ".$Clan2MapScore." WHERE `team` = ".$this->db->quote($Red)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmsr);
	
	//MatchScore Blue
	$qmmsb = "UPDATE `match_main`
	set Matchscore = ".$Clan1MapScore." WHERE `team` = ".$this->db->quote($Red)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmmsb);
	//MatchScore Red
	$qmmsr = "UPDATE `match_main`
	set Matchscore = ".$Clan2MapScore." WHERE `team` = ".$this->db->quote($Red)." and `mapUid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($qmmsr);
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
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."'")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_kills` = '".($shooterinfo->player_kills+1)."' WHERE `player_login` = '".$content->Event->Shooter->Login."'");

		$victiminfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Victim->Login."'")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_deaths` = '".($victiminfo->player_deaths+1)."' WHERE `player_login` = '".$content->Event->Victim->Login."'");

		Console::println('['.date('H:i:s').'] [ShootMania] [Elite] '.$content->Event->Victim->Login.' was killed by '.$content->Event->Shooter->Login);
	

	}
	
	function onXmlRpcEliteShoot($content)
	{
	
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."'")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_shots` = '".($shooterinfo->player_shots+1)."' WHERE `player_login` = '".$content->Event->Shooter->Login."'");
		
	}
	
	function onXmlRpcEliteHit($content)
	{
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."'")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_hits` = '".($shooterinfo->player_hits+1)."' WHERE `player_login` = '".$content->Event->Shooter->Login."'");
	
	}
	
	function onXmlRpcEliteMatchStart($content) //Not Working??
	{
	var_dump($content);
	$this->MatchNumber = $content->MatchNumber;
	}
	
	function onXmlRpcEliteCapture($content)
	{
	$map = $this->connection->getCurrentMapInfo();
	
		$q = "INSERT INTO `captures` (
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
		$this->db->execute($q);

		// update capture statistics
		$info = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Player->Login."'")->fetchObject();
		$this->db->execute("UPDATE `players` SET `player_captures` = '".($info->player_captures+1)."' WHERE `player_login` = '".$content->Event->Player->Login."'");
	}
	
	function onXmlRpcEliteNearMiss($content)
	{
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `player_login` = '".$content->Event->Shooter->Login."'")->fetchObject();
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