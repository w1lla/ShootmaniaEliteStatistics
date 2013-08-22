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
use ManiaLive\Utilities\Validation;

class Elite extends \ManiaLive\PluginHandler\Plugin {

protected $MatchNumber;
protected $TurnNumber;
protected $Roundscore_blue;
protected $Roundscore_red;
protected $WarmUpAllReady;
protected $PlayerID;

	function onInit() {
		$this->setVersion('0.0.1');
	}

	function onLoad() {
	
			$admins = AdminGroup::get();
		
		$cmd = $this->registerChatCommand('extendWu', 'WarmUp_Extend', 1, true, $admins);
		$cmd->help = 'Extends WarmUp In Ellte with x milliseconds.';
		
		$cmd = $this->registerChatCommand('endWu', 'WarmUp_Stop', 0, true, $admins);
		$cmd->help = 'ends WarmUp in Elite.';
		
		$this->enableDatabase();
		$this->enableDedicatedEvents();
		if(!$this->db->tableExists('captures')) {
			$q = "CREATE TABLE IF NOT EXISTS `captures` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `player_login` varchar(60) NOT NULL,
  `map_uid` varchar(60) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
		if(!$this->db->tableExists('kills')) {
			$q = "CREATE TABLE IF NOT EXISTS `kills` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `player_victim` varchar(60) NOT NULL,
  `player_shooter` varchar(60) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `map_uid` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('match_details')) {
			$q = "CREATE TABLE IF NOT EXISTS `match_details` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `team_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_uid` varchar(60) NOT NULL,
  `attack` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `defence` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `capture` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `timeOver` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `attackWinEliminate` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `defenceWinEliminate` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('players')) {
			$q = "CREATE TABLE IF NOT EXISTS `players` (
   `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `nation` varchar(50) NOT NULL,
  `updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
		if(!$this->db->tableExists('players_map')) {
			$q = "CREATE TABLE IF NOT EXISTS `players_map` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `team_id` mediumint(9) NOT NULL DEFAULT '0',
  `kills` mediumint(9) NOT NULL DEFAULT '0',
  `shots` mediumint(9) NOT NULL DEFAULT '0',
  `nearmisses` mediumint(9) NOT NULL DEFAULT '0',
  `hits` mediumint(9) NOT NULL DEFAULT '0',
  `deaths` mediumint(9) NOT NULL DEFAULT '0',
  `captures` mediumint(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('maps')) {
			$q = "CREATE TABLE IF NOT EXISTS `maps` (
  `id` MEDIUMINT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                    `uid` VARCHAR( 27 ) NOT NULL ,
                                    `name` VARCHAR( 100 ) NOT NULL ,
									`author` VARCHAR( 30 ) NOT NULL
) CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = MYISAM ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('matches')) {
			$q = "CREATE TABLE IF NOT EXISTS `matches` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `MatchName` varchar(50) NOT NULL DEFAULT '',
  `teamBlue` mediumint(9) NOT NULL DEFAULT '0',
  `teamRed` mediumint(9) NOT NULL DEFAULT '0',
  `map_uid` varchar(60) NOT NULL,
  `turnNumber` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Mapscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Matchscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `Mapscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `Matchscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `MatchStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `MatchEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}

		if(!$this->db->tableExists('match_maps')) {
			$q = "CREATE TABLE IF NOT EXISTS `match_maps` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_uid` varchar(60) NOT NULL,
  `turnNumber` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_blue` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_red` mediumint(9) NOT NULL DEFAULT '0',
  `MatchStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `MatchEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `AtkId` mediumint(9) DEFAULT '0',
  `AllReady` boolean default '0',
  `NextMap` boolean default '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('nearmisses')) {
			$q = "CREATE TABLE IF NOT EXISTS `nearmisses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_uid` varchar(60) NOT NULL,
  `nearMissDist` float default '0',
  `player_login` varchar(50) NOT NULL,
  `weaponid` int(11) NOT NULL,
  `weaponname` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}

				if(!$this->db->tableExists('teams')) {
			$q = "CREATE TABLE IF NOT EXISTS `teams` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `teamName` varchar(50) NOT NULL DEFAULT '',
  `team_EmblemUrl` varchar(255) NOT NULL,
  `team_ZonePath` varchar(50) NOT NULL,
  `team_RGB` varchar(50) NOT NULL,
PRIMARY KEY (`id`)
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
		
        $this->connection->restartMap();
		
		$this->enableDedicatedEvents(ServerEvent::ON_MODE_SCRIPT_CALLBACK);
		$this->enableDedicatedEvents(ServerEvent::ON_VOTE_UPDATED);

	}
	public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam){
	var_dump($cmdName);
	if ($cmdName == "NextMap"){
		$querymapEnd = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($querymapEnd);
	}
	}
	
	public function onModeScriptCallback($param1, $param2)
	{
		switch ($param1)
		{
			case 'LibXmlRpc_BeginMatch':
			$this->onXmlRpcEliteMatchStart($param2);
			break;
			case 'BeginMatch':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteMatchStart($parameter);
			break;
			case 'BeginMap':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteMapStart($parameter);
			break;
			case 'BeginWarmup':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteBeginWarmUp($parameter);
			break;
			case 'EndWarmup':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteEndWarmUp($parameter);
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
			$this->onXmlRpcEliteEndMap($parameter);
			break;
		}
	}
	
	function updateServerChallenges() {
        //get server challenges
        $serverChallenges = $this->storage->maps;
        //get database challenges

        $g = "SELECT * FROM `maps`;";
        $query = $this->db->query($g);

        $databaseUid = array();
        //get database uid's of tracks.
        while ($data = $query->fetchStdObject()) {
            $databaseUid[$data->uid] = $data->uid;
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
        $q = "INSERT INTO `maps` (`uid`,
                                    `name`,
									`author`
                                    )
                                VALUES (" . $this->db->quote($data->uId) . ",
                                " . $this->db->quote($data->name) . ",
                                " . $this->db->quote($data->author) . "
                                )";
        $this->db->query($q);
    }
	
	function WarmUp_Extend($login, $amount)
	{
	try
		{
			Validation::int($amount, 0);
		}
		catch(\Exception $e)
		{
			$this->connection->chatSendServerMessage('$F00The Amount must be in milliseconds', $login, true);
			return;
		}
	$this->connection->triggerModeScriptEvent('WarmUp_Extend',$amount);
	}
	
	function WarmUp_Stop($login)
	{
		$this->connection->triggerModeScriptEvent('WarmUp_Stop','');
	}
		
	function onPlayerConnect($login, $isSpectator) {
		$player = $this->storage->getPlayerObject($login);
		$this->insertPlayer($player);
	}
	
	function insertPlayer($player) {
		$g =  "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($player->login).";";
		$execute = $this->db->execute($g);
		if($execute->recordCount() == 0) {
			$q = "INSERT INTO `players` (
					`login`,
					`nickname`,
					`nation`,
					`updatedate`
				  ) VALUES (
					".$this->db->quote($player->login).",
					".$this->db->quote($player->nickName).",
					".$this->db->quote(str_replace('World|', '', $player->path)).",
					'".date('Y-m-d H:i:s')."'
				  )";
		} else {
			$q = "UPDATE `players`
				  SET `nickname` = ".$this->db->quote($player->nickName).",
				      `nation` = ".$this->db->quote(str_replace('World|', '', $player->path)).",
				      `updatedate` = '".date('Y-m-d H:i:s')."'
				  WHERE `login` = ".$this->db->quote($player->login)."";
				 ////var_dump($q);
		}
		$this->db->execute($q);
	}
	
	function onXmlRpcEliteBeginWarmUp($content)
	{
	if($content->AllReady == false){
	$map = $this->connection->getCurrentMapInfo();
	$q = "UPDATE `match_maps`
				  SET `AllReady` = '0'
				  WHERE `match_id` = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($map->uId)."";
	$this->db->execute($q);
	}
	else{
	}
	}
	
	function onXmlRpcEliteEndWarmUp($content)
	{
	if($content->AllReady == true){
	$map = $this->connection->getCurrentMapInfo();
	$q = "UPDATE `match_maps`
				  SET `AllReady` = '1'
				  WHERE `match_id` = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($map->uId)."";
	$this->db->execute($q);
	}
	else{
	}
	}
	
	function onXmlRpcEliteMapStart($content)
	{
		if(isset($this->MatchNumber))
	{
	$map = $this->connection->getCurrentMapInfo();
		$mapmatch = "SELECT * FROM `match_maps` WHERE `match_id` = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($map->uId)."";
		$mapmatchexecute = $this->db->execute($mapmatch);
		if($mapmatchexecute->recordCount() == 0) {
		$qmapmatch = "INSERT INTO `match_maps` (
						`match_id`,
						`map_uid`,
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
	}
	}
	
	function onXmlRpcEliteMatchStart($content)
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
	
	$Blueteaminfo = "SELECT * FROM `teams` WHERE `teamName` = ".$this->db->quote($Blue).";";
	$execute = $this->db->execute($Blueteaminfo);

	if($execute->recordCount() == 0) {
	$qbluematch = "INSERT INTO `teams` (
					`teamName`,
					`team_EmblemUrl`,
					`team_ZonePath`,
					`team_RGB`
				  ) VALUES (
					".$this->db->quote($Blue).",
					".$this->db->quote($BlueEmblemUrl).",
					".$this->db->quote($BlueZonePath).",
					".$this->db->quote($BlueRGB)."
				  )";
		$this->db->execute($qbluematch);
		$this->BlueId = $this->db->insertID();
	}else{
		$Blueteaminfo = "SELECT id FROM `teams` WHERE `teamName` = ".$this->db->quote($Blue).";";
		$BlueTeam = $this->db->execute($Blueteaminfo)->fetchObject();
		$this->BlueId = $BlueTeam->id;
	}
	
	$Redteaminfo = "SELECT * FROM `teams` WHERE `teamName` = ".$this->db->quote($Red).";";
	$executes = $this->db->execute($Redteaminfo);
	if($executes->recordCount() == 0) {
	$qb = "INSERT INTO `teams` (
					`teamName`,
					`team_EmblemUrl`,
					`team_ZonePath`,
					`team_RGB`
				  ) VALUES (
					".$this->db->quote($Red).",
					".$this->db->quote($RedEmblemUrl).",
					".$this->db->quote($RedZonePath).",
					".$this->db->quote($RedRGB)."
					)";
		$this->db->execute($qb);
		$this->RedId = $this->db->insertID();
	}else{
		$Redteaminfo = "SELECT id FROM `teams` WHERE `teamName` = ".$this->db->quote($Red).";";
		$RedTeam = $this->db->execute($Redteaminfo)->fetchObject();
		$this->RedId = $RedTeam->id;
	}
	
	
	if(isset($this->MatchNumber))
	{
		$gmatch = "SELECT * FROM `matches` WHERE `id` = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($map->uId)."";
		$execute = $this->db->execute($gmatch);
		if($execute->recordCount() == 0) {
		$qmatch = "INSERT INTO `matches` (
						`MatchName`,
						`teamBlue`,
						`teamRed`,
						`map_uid`,
						`Roundscore_blue`,
						`Mapscore_blue`,
						`Matchscore_blue`,
						`Roundscore_red`,
						`Mapscore_red`,
						`Matchscore_red`,
						`MatchStart`
					  ) VALUES (
						".$this->db->quote($MatchName).",
						".$this->db->quote($this->BlueId).",
						".$this->db->quote($this->RedId).",
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
	
	}else{
	
		$qmatch = "INSERT INTO `matches` (
						`MatchName`,
						`teamBlue`,
						`teamRed`,
						`map_uid`,
						`Roundscore_blue`,
						`Mapscore_blue`,
						`Matchscore_blue`,
						`Roundscore_red`,
						`Mapscore_red`,
						`Matchscore_red`,
						`MatchStart`
					  ) VALUES (
						".$this->db->quote($MatchName).",
						".$this->db->quote($this->BlueId).",
						".$this->db->quote($this->RedId).",
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
		
	}
	
	if(isset($this->MatchNumber))
	{
	foreach ($this->storage->players as $login => $player){
	$teamId = $player->teamId+1;
	$PlayerID = $this->db->execute("Select id from players where `login` = ".($this->db->quote($player->login))."")->fetchObject();
	$this->PlayerID = $PlayerID->id;
	$playermapinfo = "SELECT * FROM `players_map` WHERE `player_id` = '".$this->PlayerID."' and `match_id` = '".$this->MatchNumber."';";
	//var_dump($playermapinfo);
	$pmiexecute = $this->db->execute($playermapinfo);

	if($pmiexecute->recordCount() == 0) {
	$pmi = "INSERT INTO `players_map` (
					`player_id`,
					`match_id`,
					`team_id`
				  ) VALUES (
					'".$this->PlayerID."',
					'".$this->MatchNumber."',
					'".$teamId."'
				  )";
		//var_dump($pmi);
		$this->db->execute($pmi);
	}
	}
	}
	else{
	}


	}
	
	//Xml RPC events
	function onXmlRpcEliteBeginTurn($content)
	{
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnNumber = $content->TurnNumber;
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$AtkId = $this->db->execute("Select id from teams where `teamName` =".($this->db->quote($AttackClan))."")->fetchObject();
	$AttackClan = $AtkId->id;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	$DefId = $this->db->execute("Select id from teams where `teamName` =".($this->db->quote($DefClan))."")->fetchObject();
	$DefClan = $DefId->id;	
	//AtkQuery
	$mapbt = $this->connection->getCurrentMapInfo();
	////var_dump($mapbt);
	
	
	$g = "SELECT * FROM `match_details` WHERE `map_uid` = ".$this->db->quote($mapbt->uId)." and `team_id` = ".$this->db->quote($AttackClan).";";
	$execute = $this->db->execute($g);
	if($execute->recordCount() == 0) {
	$q = "INSERT INTO `match_details` (
					`match_id`,
					`team_id`,
					`map_uid`,
					`attack`,
					`defence`,
					`capture`,
					`timeOver`,
					`attackWinEliminate`,
					`defenceWinEliminate`
				  ) VALUES (
					".$this->MatchNumber.",
					".$this->db->quote($AttackClan).",
					".$this->db->quote($mapbt->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0'
				  )";
		$this->db->execute($q);
	} else {
	}
		//DefQuery
	$g = "SELECT * FROM `match_details` WHERE `map_uid` = ".$this->db->quote($mapbt->uId)." and `team_id` = ".$this->db->quote($DefClan).";";
	//var_dump($g);
	$execute = $this->db->execute($g);
	if($execute->recordCount() == 0) {
	$q = "INSERT INTO `match_details` (
					`match_id`,
					`team_id`,
					`map_uid`,
					`attack`,
					`defence`,
					`capture`,
					`timeOver`,
					`attackWinEliminate`,
					`defenceWinEliminate`
				  ) VALUES (
					".$this->MatchNumber.",
					".$this->db->quote($DefClan).",
					".$this->db->quote($mapbt->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0'
				  )";
		$this->db->execute($q);
	} else {
		}
	$qmapid = $this->db->execute("Select id from players where `login` =".($this->db->quote($content->AttackingPlayer->Login))."")->fetchObject();
	$mapmatchAtk = "UPDATE `match_maps`
				  SET `AtkId` = '".($qmapid->id)."'
				  WHERE `match_id` = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
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
	$this->Roundscore_blue = $Clan1RoundScore;
	$this->Roundscore_red = $Clan2RoundScore;
	$mapmatchAtk = "UPDATE `match_maps`
				  SET `AtkId` = 0
				  WHERE `match_id` = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($mapmatchAtk);
	$mapbt = $this->connection->getCurrentMapInfo();
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$AtkId = $this->db->execute("Select id from teams where `teamName` =".($this->db->quote($AttackClan))."")->fetchObject();
	$AttackClan = $AtkId->id;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	$DefId = $this->db->execute("Select id from teams where `teamName` =".($this->db->quote($DefClan))."")->fetchObject();
	$DefClan = $DefId->id;	
	
	
	$attacks = $this->db->execute("SELECT * FROM `match_details` WHERE `team_id` = ".$this->db->quote($AttackClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."")->fetchObject();
	$qatk = "UPDATE `match_details`
				  SET `attack` = '".($attacks->attack+1)."'
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qatk);
	
	if ($WinType == 'Capture'){
	$qcapture = "UPDATE `match_details`
				  SET `capture` = '".($attacks->capture+1)."'
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qcapture);
	}
	
		if ($WinType == 'DefenseEliminated'){
	$qawe = "UPDATE `match_details`
				  SET `attackWinEliminate` = '".($attacks->attackWinEliminate+1)."'
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."";		  
	$this->db->execute($qawe);
	}
	
	$defenses = $this->db->execute("SELECT * FROM `match_details` WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."")->fetchObject();
	$qdef = "UPDATE `match_details`
				  SET `defence` = '".($defenses->defence+1)."'
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
		$this->db->execute($qdef);
		
	if ($WinType == 'TimeLimit'){
	$qtl = "UPDATE `match_details`
				  SET `timeOver` = '".($defenses->timeOver+1)."'
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qtl);
	}
	
		if ($WinType == 'AttackEliminated'){
	$qde = "UPDATE `match_details`
				  SET `defenceWinEliminate` = '".($defenses->defenceWinEliminate+1)."'
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qde);
	}
	
	// RoundScore Blue
	$qrsb = "UPDATE `matches`
	set Roundscore_blue = ".$Clan1RoundScore." where id = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qrsb);
	// RoundScore Red
	$qrsr = "UPDATE `matches`
	set Roundscore_red = ".$Clan2RoundScore." where id = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qrsr);
	
	//MapScore Blue
	$qmsb = "UPDATE `matches`
	set Mapscore_blue = ".$Clan1MapScore." where id = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qmsb);
	//MapScore Red
	$qmsr = "UPDATE `matches`
	set Mapscore_red = ".$Clan2MapScore." where id = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qmsr);
	
	//MatchScore Blue
	$qmmsb = "UPDATE `matches`
	set Matchscore_blue = ".$Clan1MapScore." where id = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qmmsb);
	//MatchScore Red
	$qmmsr = "UPDATE `matches`
	set Matchscore_red = ".$Clan2MapScore." where id = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($qmmsr);
	
	$TurnRound = "UPDATE `matches` set turnNumber = ".$TurnNumber." where id = ".$this->MatchNumber." and `map_uid` = ".$this->db->quote($mapbt->uId)."";
	$this->db->execute($TurnRound);
	}
	
	function onXmlRpcEliteArmorEmpty($content)
	{
	$map = $this->connection->getCurrentMapInfo();
		// Insert kill into the database
		$q = "INSERT INTO `kills` (
				`match_id`,
				`player_victim`,
				`player_shooter`,
				`time`,
				`map_uid`
			  ) VALUES (
			  ".$this->MatchNumber.",
			    '".$content->Event->Victim->Login."',
			    '".$content->Event->Shooter->Login."',
			    '".date('Y-m-d H:i:s')."',
			    '".$map->uId."'
			  )";
		$this->db->execute($q);

		// update kill/death statistics
		
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'")->fetchObject();
		$kills = $this->db->execute("SELECT * FROM `players_map` WHERE `player_id` = '".$shooterinfo->id."' and `match_id` = '".$this->MatchNumber."'")->fetchObject();
		$this->db->execute("UPDATE `players_map` SET `kills` = '".($kills->kills+1)."' WHERE `player_id` = '".$shooterinfo->id."' and `match_id` = '".$this->MatchNumber."'");

		$victiminfo = $this->db->execute("SELECT * FROM `players` WHERE `login` = '".$content->Event->Victim->Login."'")->fetchObject();
		$deaths = $this->db->execute("SELECT * FROM `players_map` WHERE `player_id` = '".$victiminfo->id."' and `match_id` = '".$this->MatchNumber."'")->fetchObject();
		$this->db->execute("UPDATE `players_map` SET `deaths` = '".($deaths->deaths+1)."' WHERE `player_id` = '".$victiminfo->id."'  and `match_id` = '".$this->MatchNumber."'");

		Console::println('['.date('H:i:s').'] [ShootMania] [Elite] '.$content->Event->Victim->Login.' was killed by '.$content->Event->Shooter->Login);

	}
	
	function onXmlRpcEliteShoot($content)
	{
	
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'")->fetchObject();
		$shots = $this->db->execute("SELECT * FROM `players_map` WHERE `player_id` = '".$shooterinfo->id."' and `match_id` = '".$this->MatchNumber."'")->fetchObject();
		$this->db->execute("UPDATE `players_map` SET `shots` = '".($shots->shots+1)."' WHERE `player_id` = '".$shooterinfo->id."'  and `match_id` = ".$this->MatchNumber."");
	}
	
	function onXmlRpcEliteHit($content)
	{
		$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'")->fetchObject();
		$hits = $this->db->execute("SELECT * FROM `players_map` WHERE `player_id` = '".$shooterinfo->id."' and `match_id` = '".$this->MatchNumber."'")->fetchObject();
		$this->db->execute("UPDATE `players_map` SET `hits` = '".($hits->hits+1)."' WHERE `player_id` = '".$shooterinfo->id."'  and `match_id` = ".$this->MatchNumber."");
	}
	
	function onXmlRpcEliteCapture($content)
	{
	$map = $this->connection->getCurrentMapInfo();
	
		$qCap = "INSERT INTO `captures` (
				`match_id`,
				`player_login`,
				`map_uid`,
				`time`
			  ) VALUES (
			  ".$this->MatchNumber.",
			    '".$content->Event->Player->Login."',
			    '".$map->uId."',
			    '".date('Y-m-d H:i:s')."'
			  )";
		$this->db->execute($qCap);

		// update capture statistics
		$info = $this->db->execute("SELECT * FROM `players` WHERE `login` = '".$content->Event->Player->Login."'")->fetchObject();
		$captures = $this->db->execute("SELECT * FROM `players_map` WHERE `player_id` = '".$info->id."' and `match_id` = '".$this->MatchNumber."'")->fetchObject();
		$this->db->execute("UPDATE `players_map` SET `captures` = '".($captures->captures+1)."' WHERE `player_id` = '".$info->id."' and `match_id` = ".$this->db->quote($this->MatchNumber)."");
	}
	
	function onXmlRpcEliteNearMiss($content)
	{
	//var_dump($content->Event->MissDist);
	$weaponNum = $content->Event->WeaponNum;
	$WeaponName = $this->getWeaponName($weaponNum);
	$MissDist = $content->Event->MissDist;
	$map = $this->connection->getCurrentMapInfo();
	
	$shooterinfo = $this->db->execute("SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'")->fetchObject();
	$nearmisses = $this->db->execute("SELECT * FROM `players_map` WHERE `player_id` = '".$info->id."' and `match_id` = '".$this->MatchNumber."'")->fetchObject();
	$this->db->execute("UPDATE `players` SET `nearmisses` = '".($nearmisses->nearmiss+1)."' WHERE `player_id` = '".$shooterinfo->id."' and `match_id` = ".$this->db->quote($this->MatchNumber)."");
	
	$dbnearmissget = "SELECT * FROM `nearmisses` WHERE `player_login` = ".$this->db->quote($content->Event->Shooter->Login)." and `match_id` = ".$this->db->quote($this->MatchNumber).";";
	
	$nearmissexecute = $this->db->execute($dbnearmissget);
		if($nearmissexecute->recordCount() == 0) {
			$qnearmiss = "INSERT INTO `nearmisses` (
					`match_id`,
					`map_uid`,
					`nearMissDist`,
					`player_login`,
					`weaponid`,
					`weaponname`
				  ) VALUES (
					".$this->MatchNumber.",
					'".$map->uId."',
					'".$MissDist."',
					'".$content->Event->Shooter->Login."',
					'".$weaponNum."',
					'".$WeaponName."'
				  )";
				$this->db->execute($qnearmiss);		  
		} else {
			$dbnearmiss = "UPDATE `nearmisses` SET `nearMissDist` = '".$MissDist."', `weaponid` = '".$weaponNum."', `weaponname` = '".$WeaponName."' WHERE `player_login` = '".$content->Event->Shooter->Login."' and `match_id` = ".$this->db->quote($this->MatchNumber)."";
			
	$this->db->execute($dbnearmiss);
		}
	}
	
	function onXmlRpcEliteEndMap($content)
	{
		if(!isset($this->Roundscore_blue))
		$this->Roundscore_blue = 0;
	if(!isset($this->Roundscore_red))
		$this->Roundscore_red = 0;
	if(!isset($this->TurnNumber))
		$this->TurnNumber = 0;		
		
	$querymapEnd = "UPDATE `match_maps`
	SET `MatchEnd` = '".date('Y-m-d H:i:s')."', 
	 `RoundScore_blue` = '".$this->Roundscore_blue."',
	 `RoundScore_red` = '".$this->Roundscore_red."',
	 `TurnNumber` = '".$this->TurnNumber."',
	 `AllReady` = '0'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($querymapEnd);
	}
	
	function onXmlRpcEliteEndMatch($content)
	{
	$MapWin = $this->connection->getModeScriptSettings();
	print_r("Nb Map to win: ") . var_dump($MapWin['S_MapWin']);
	if($this->Roundscore_blue == $MapWin OR $this->Roundscore_red == $MapWin['S_MapWin']){
	$queryEnd = "UPDATE `matches` SET `MatchEnd` = '".date('Y-m-d H:i:s')."'  where `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($this->storage->currentMap->uId)."";
	$this->db->execute($queryEnd);
	}
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