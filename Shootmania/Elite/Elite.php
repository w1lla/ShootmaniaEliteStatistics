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
protected $Mapscore_blue;
protected $Mapscore_red;
protected $WarmUpAllReady;
protected $PlayerID;
protected $MapNumber;
protected $Roundscore_blue;
protected $Roundscore_red;

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
		
				if(!$this->db->tableExists('Shots')) {
			$q = "CREATE TABLE IF NOT EXISTS `Shots` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(11) NOT NULL,
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `weapon_id` int(11) NOT NULL,
  `shots` int(11) NOT NULL DEFAULT '0',
  `hits` int(11) NOT NULL DEFAULT '0',
  `eliminations` int(11) NOT NULL DEFAULT '0',
  `matchServerLogin` VARCHAR(250) NOT NULL,
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
   `matchServerLogin` VARCHAR(250) NOT NULL,
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
		
		if(!$this->db->tableExists('player_maps')) {
			$q = "CREATE TABLE IF NOT EXISTS `player_maps` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `team_id` mediumint(9) NOT NULL DEFAULT '0',
  `kills` mediumint(9) NOT NULL DEFAULT '0',
  `shots` mediumint(9) NOT NULL DEFAULT '0',
  `nearmisses` mediumint(9) NOT NULL DEFAULT '0',
  `hits` mediumint(9) NOT NULL DEFAULT '0',
  `deaths` mediumint(9) NOT NULL DEFAULT '0',
  `captures` mediumint(9) NOT NULL DEFAULT '0',
   `matchServerLogin` VARCHAR(250) NOT NULL,
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
  `matchServerLogin` VARCHAR(250) NOT NULL,
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
  `MapStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `MapEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `AtkId` mediumint(9) DEFAULT '0',
  `AllReady` boolean default '0',
  `NextMap` boolean default '0',
  `matchServerLogin` VARCHAR(250) NOT NULL,
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

		
        $this->connection->restartMap();
		
		$this->enableDedicatedEvents(ServerEvent::ON_MODE_SCRIPT_CALLBACK);
		$this->enableDedicatedEvents(ServerEvent::ON_VOTE_UPDATED);
		
	foreach($this->storage->players as $player) {
			$this->onPlayerConnect($player->login, false);
	}

	}
	public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam){
	if ($cmdName == "NextMap"){
		$querymapEnd = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	$this->db->execute($querymapEnd);
	Console::println($querymapEnd);
	}
	}
	
	public function onModeScriptCallback($param1, $param2)
	{
		switch ($param1)
		{
			case 'BeginMatch':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteMatchStart($parameter);
			break;
			case 'LibXmlRpc_LoadingMap';
			$this->LoadingMapNumber($param2);
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
			case 'EndMatch':
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

        $q = "SELECT * FROM `maps`;";
		Console::println($q);
        $query = $this->db->query($q);

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
        Console::println($q);
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
		$q =  "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($player->login).";";
		Console::println($q);
		$execute = $this->db->execute($q);
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
			$this->db->execute($q);
			Console::println($q);
		} else {
			$q = "UPDATE `players`
				  SET `nickname` = ".$this->db->quote($player->nickName).",
				      `nation` = ".$this->db->quote(str_replace('World|', '', $player->path)).",
				      `updatedate` = '".date('Y-m-d H:i:s')."'
				  WHERE `login` = ".$this->db->quote($player->login)."";
			$this->db->execute($q);
			Console::println($q);
		}
		
	if(isset($this->MatchNumber, $this->MapNumber))
	{
	foreach ($this->storage->players as $login => $player){
	$teamId = $player->teamId+1;
	$q = "Select id from players where `login` = ".($this->db->quote($player->login));
	Console::println($q);
	$PlayerID = $this->db->execute($q)->fetchObject();
	$this->PlayerID = $PlayerID->id;
	$playermapinfo = "SELECT * FROM `player_maps` WHERE `player_id` = '".$this->PlayerID."' and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
	Console::println($playermapinfo);
	$pmiexecute = $this->db->execute($playermapinfo);

	if($pmiexecute->recordCount() == 0) {
	$pmi = "INSERT INTO `player_maps` (
					`player_id`,
					`match_map_id`,
					`team_id`
				  ) VALUES (
					'".$this->PlayerID."',
					'".$this->MapNumber."',
					'".$teamId."'
				  )";
		//var_dump($pmi);
		Console::println($pmi);
		$this->db->execute($pmi);
	}
	}
	}
	else{
	}
	
	}
	
	function LoadingMapNumber($content){
	//$this->MapNumber = $content[0];
	}
	
	function onXmlRpcEliteBeginWarmUp($content)
	{
	if($content->AllReady == false){
	$map = $this->connection->getCurrentMapInfo();
	$q = "UPDATE `match_maps`
				  SET `AllReady` = '0'
				  WHERE `match_id` = ".$this->MatchNumber." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($q);			  
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
				  WHERE `match_id` = ".$this->MatchNumber." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($q);		
	$this->db->execute($q);
	}
	else{
	}
	}
	
	function onXmlRpcEliteMapStart($content)
	{

	$map = $this->connection->getCurrentMapInfo();
	$mapmatch = "SELECT * FROM `match_maps` WHERE `match_id` = ".$this->MatchNumber." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($mapmatch);		
	$mapmatchexecute = $this->db->execute($mapmatch);
	if($mapmatchexecute->recordCount() == 0) {
		$qmapmatch = "INSERT INTO `match_maps` (
						`match_id`,
						`map_uid`,
						`Roundscore_blue`,
						`Roundscore_red`,
						`MapStart`,
						`matchServerLogin`
					  ) VALUES (
						".$this->MatchNumber.",
						".$this->db->quote($map->uId).",
						'0',
						'0',
						'".date('Y-m-d H:i:s')."',
						".$this->db->quote($this->storage->serverLogin)."
					  )";
		Console::println($qmapmatch);
		$this->db->execute($qmapmatch);
		$this->MapNumber = $this->db->insertID();
	} else {
	}
	
	
	

	foreach ($this->storage->players as $login => $player){
		$teamId = $player->teamId+1;
		$q = "Select id from players where `login` = ".($this->db->quote($player->login));
		Console::println($q);
		$PlayerID = $this->db->execute($q)->fetchObject();
		$this->PlayerID = $PlayerID->id;
		$playermapinfo = "SELECT * FROM `player_maps` WHERE `player_id` = '".$this->PlayerID."' and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
		Console::println($playermapinfo);
		//var_dump($playermapinfo);
		$pmiexecute = $this->db->execute($playermapinfo);

		if($pmiexecute->recordCount() == 0) {
			$pmi = "INSERT INTO `player_maps` (
						`player_id`,
						`match_map_id`,
						`team_id`,
						`matchServerLogin`
					  ) VALUES (
						'".$this->PlayerID."',
						'".$this->MapNumber."',
						'".$teamId."',
						".$this->db->quote($this->storage->serverLogin)."
					  )";
			//var_dump($pmi);
			Console::println($pmi);
			$this->db->execute($pmi);
		}
		}

	}
	
	function onXmlRpcEliteMatchStart($content)
	{
	$this->MatchNumber = $content->MatchNumber;
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
	Console::println($Blueteaminfo);
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
		Console::println($qbluematch);
		$this->db->execute($qbluematch);
		$this->BlueId = $this->db->insertID();
	}else{
		$Blueteaminfo = "SELECT id FROM `teams` WHERE `teamName` = ".$this->db->quote($Blue).";";
		Console::println($Blueteaminfo);
		$BlueTeam = $this->db->execute($Blueteaminfo)->fetchObject();
		$this->BlueId = $BlueTeam->id;
	}
	
	$Redteaminfo = "SELECT * FROM `teams` WHERE `teamName` = ".$this->db->quote($Red).";";
	Console::println($Redteaminfo);
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
		Console::println($qb);			
		$this->db->execute($qb);
		$this->RedId = $this->db->insertID();
	}else{
		$Redteaminfo = "SELECT id FROM `teams` WHERE `teamName` = ".$this->db->quote($Red).";";
		Console::println($Redteaminfo);
		$RedTeam = $this->db->execute($Redteaminfo)->fetchObject();
		$this->RedId = $RedTeam->id;
	}

	$qmatch = "SELECT * FROM `matches` WHERE `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($qmatch);
	$execute = $this->db->execute($qmatch);
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
						`MatchStart`,
						`matchServerLogin`
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
						'".date('Y-m-d H:i:s')."',
						".$this->db->quote($this->storage->serverLogin)."
					  )";
		Console::println($qmatch);
		$this->db->execute($qmatch);
		$this->MatchNumber = $this->db->insertID();
		} else {
	}
	
	


	}
	
	//Xml RPC events
	function onXmlRpcEliteBeginTurn($content)
	{
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnNumber = $content->TurnNumber;
	$this->TurnNumber = $TurnNumber;
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$q = "Select id from teams where `teamName` =".($this->db->quote($AttackClan));
	Console::println($q);
	$AtkId = $this->db->execute($q)->fetchObject();
	$AttackClan = $AtkId->id;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	$q = "Select id from teams where `teamName` =".($this->db->quote($DefClan));
	Console::println($q);
	$DefId = $this->db->execute($q)->fetchObject();
	$DefClan = $DefId->id;	
	//AtkQuery
	$mapbt = $this->connection->getCurrentMapInfo();
	////var_dump($mapbt);
	foreach ($this->storage->players as $login => $player){
	$player_id_query = "Select id from players where `login` = ".($this->db->quote($player->login));
	Console::println($player_id_query);
	$PlayerID = $this->db->execute($player_id_query)->fetchObject();
	$shots_table = "SELECT * FROM `Shots` WHERE `match_map_id` = '".$this->MapNumber."' and `round_id` = '".$TurnNumber."' and `player_id` = '".$PlayerID->id."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
	Console::println($shots_table);
	$execute = $this->db->execute($shots_table);
	if($execute->recordCount() == 0) {
	$shots_insert = "INSERT INTO `Shots` (
					`match_map_id`,
					`round_id`,
					`player_id`,
					`weapon_id`,
					`shots`,
					`hits`,
					`eliminations`,
					`matchServerLogin`
				  ) VALUES (
					".$this->MapNumber.",
					".$TurnNumber.",
					".$PlayerID->id.",
					'0',
					'0',
					'0',
					'0',
					".$this->db->quote($this->storage->serverLogin)."
				  )";
				  Console::println($shots_insert);
		$this->db->execute($shots_insert);
	} else {
	}
	}
	
	$q = "SELECT * FROM `match_details` WHERE `map_uid` = ".$this->db->quote($mapbt->uId)." and `team_id` = ".$this->db->quote($AttackClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
	Console::println($q);
	$execute = $this->db->execute($q);
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
					`defenceWinEliminate`,
					`matchServerLogin`
				  ) VALUES (
					".$this->MatchNumber.",
					".$this->db->quote($AttackClan).",
					".$this->db->quote($mapbt->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					".$this->db->quote($this->storage->serverLogin)."
				  )";
				  Console::println($q);
		$this->db->execute($q);
	} else {
	}
		//DefQuery
	$q = "SELECT * FROM `match_details` WHERE `map_uid` = ".$this->db->quote($mapbt->uId)." and `team_id` = ".$this->db->quote($DefClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
	Console::println($q);
	//var_dump($q);
	$execute = $this->db->execute($q);
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
					`defenceWinEliminate`,
					`matchServerLogin`
				  ) VALUES (
					".$this->MatchNumber.",
					".$this->db->quote($DefClan).",
					".$this->db->quote($mapbt->uId).",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					".$this->db->quote($this->storage->serverLogin)."
				  )";
				  Console::println($q);
		$this->db->execute($q);
	} else {
		}
	$q = "Select id from players where `login` =".($this->db->quote($content->AttackingPlayer->Login));
	Console::println($q);
	$qmapid = $this->db->execute($q)->fetchObject();
	$mapmatchAtk = "UPDATE `match_maps`
				  SET `AtkId` = '".($qmapid->id)."'
				  WHERE `match_id` = ".$this->MatchNumber." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($mapmatchAtk);	  
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
	$this->Mapscore_blue = $Clan1MapScore;
	$this->Mapscore_red = $Clan2MapScore;
	$this->Roundscrore_blue = $Clan1RoundScore;
	$this->RoundScore_red = $Clan2RoundScore;
	$mapmatchAtk = "UPDATE `match_maps`
				  SET `AtkId` = 0,
				  `turnNumber` = '".($this->TurnNumber)."',
				  `Roundscore_blue` = ".$Clan1RoundScore.",
				  `Roundscore_red` = ".$Clan2RoundScore."
				  WHERE `match_id` = ".$this->MatchNumber." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
			Console::println($mapmatchAtk);
	$this->db->execute($mapmatchAtk);
	$mapbt = $this->connection->getCurrentMapInfo();
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$q = "Select id from teams where `teamName` =".($this->db->quote($AttackClan));
	Console::println($q);
	$AtkId = $this->db->execute($q)->fetchObject();
	$AttackClan = $AtkId->id;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	$q = "Select id from teams where `teamName` =".($this->db->quote($DefClan));
	Console::println($q);
	$DefId = $this->db->execute($q)->fetchObject();
	$DefClan = $DefId->id;	
	
	$q = "SELECT * FROM `match_details` WHERE `team_id` = ".$this->db->quote($AttackClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($q);
	$attacks = $this->db->execute($q)->fetchObject();
	$qatk = "UPDATE `match_details`
				  SET `attack` = '".($attacks->attack+1)."'
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($qatk);
	$this->db->execute($qatk);
	
	if ($WinType == 'Capture'){
	$qcapture = "UPDATE `match_details`
				  SET `capture` = '".($attacks->capture+1)."'
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  Console::println($qcapture);
	$this->db->execute($qcapture);
	}
	
		if ($WinType == 'DefenseEliminated'){
	$qawe = "UPDATE `match_details`
				  SET `attackWinEliminate` = '".($attacks->attackWinEliminate+1)."'
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
			Console::println($qawe);	  
	$this->db->execute($qawe);
	}
	
	$q = "SELECT * FROM `match_details` WHERE `team_id` = ".$this->db->quote($DefClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($q);
	$defenses = $this->db->execute($q)->fetchObject();
	$qdef = "UPDATE `match_details`
				  SET `defence` = '".($defenses->defence+1)."'
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  Console::println($qdef);
		$this->db->execute($qdef);
		
	if ($WinType == 'TimeLimit'){
	$qtl = "UPDATE `match_details`
				  SET `timeOver` = '".($defenses->timeOver+1)."'
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  Console::println($qtl);
	$this->db->execute($qtl);
	}
	
		if ($WinType == 'AttackEliminated'){
	$qde = "UPDATE `match_details`
				  SET `defenceWinEliminate` = '".($defenses->defenceWinEliminate+1)."'
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  Console::println($qde);
	$this->db->execute($qde);
	}
	
	// RoundScore Blue
	$qrsb = "UPDATE `matches`
	set Roundscore_blue = ".$Clan1RoundScore." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($qrsb);
	$this->db->execute($qrsb);
	// RoundScore Red
	$qrsr = "UPDATE `matches`
	set Roundscore_red = ".$Clan2RoundScore." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($qrsr);
	$this->db->execute($qrsr);
	
	//MapScore Blue
	$qmsb = "UPDATE `matches`
	set Mapscore_blue = ".$Clan1MapScore." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($qmsb);
	$this->db->execute($qmsb);
	//MapScore Red
	$qmsr = "UPDATE `matches`
	set Mapscore_red = ".$Clan2MapScore." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($qmsr);
	$this->db->execute($qmsr);
	
	//MatchScore Blue
	$qmmsb = "UPDATE `matches`
	set Matchscore_blue = ".$Clan1MapScore." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($qmmsb);
	$this->db->execute($qmmsb);
	//MatchScore Red
	$qmmsr = "UPDATE `matches`
	set Matchscore_red = ".$Clan2MapScore." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($qmmsr);
	$this->db->execute($qmmsr);
	
	$TurnRound = "UPDATE `matches` set turnNumber = ".$TurnNumber." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($TurnRound);
	$this->db->execute($TurnRound);
	}
	
	function onXmlRpcEliteArmorEmpty($content)
	{
	
	if(!isset($this->TurnNumber))
		$this->TurnNumber = 0;
		$weaponNum = $content->Event->WeaponNum;
	
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
			   Console::println($q);
		$this->db->execute($q);

		// update kill/death statistics
		$q = "SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'";
		Console::println($q);
		$shooterinfo = $this->db->execute($q)->fetchObject();
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$kills = $this->db->execute($q)->fetchObject();
		$q = "UPDATE `player_maps` SET `kills` = '".($kills->kills+1)."' WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$this->db->execute($q);
		
		$q = "SELECT * FROM `shots` WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `round_id` = '".($this->TurnNumber)."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$eliminations_query = $this->db->execute($q)->fetchObject();
		
		$eliminations_table = "UPDATE `shots` SET `eliminations` = '".($eliminations_query->eliminations+1)."', `weapon_id` = '".$weaponNum."' WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `round_id` = '".($this->TurnNumber)."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($eliminations_table);
		$this->db->execute($eliminations_table);
		
		$q = "SELECT * FROM `players` WHERE `login` = '".$content->Event->Victim->Login."'";
		Console::println($q);
		$victiminfo = $this->db->execute($q)->fetchObject();
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = '".$victiminfo->id."' and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$deaths = $this->db->execute($q)->fetchObject();

		$q = "UPDATE `player_maps` SET `deaths` = '".($deaths->deaths+1)."' WHERE `player_id` = '".$victiminfo->id."'  and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		$this->db->execute($q);
		Console::println($q);

		Console::println('['.date('H:i:s').'] [ShootMania] [Elite] '.$content->Event->Victim->Login.' was killed by '.$content->Event->Shooter->Login);

	}
	
	function onXmlRpcEliteShoot($content)
	{
		if(!isset($this->TurnNumber))
		$this->TurnNumber = 0;
		$weaponNum = $content->Event->WeaponNum;
		
		$q = "SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'";
		Console::println($q);
		$shooterinfo = $this->db->execute($q)->fetchObject();
		
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$shots = $this->db->execute($q)->fetchObject();
		 
		$q = "UPDATE `player_maps` SET `shots` = '".($shots->shots+1)."' WHERE `player_id` = '".$shooterinfo->id."'  and `match_map_id` = ".$this->MapNumber." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$this->db->execute($q);
		
		$q = "SELECT * FROM `shots` WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `round_id` = '".($this->TurnNumber)."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$shots_query = $this->db->execute($q)->fetchObject();
		
		$shots_table = "UPDATE `shots` SET `shots` = '".($shots_query->shots+1)."', `weapon_id` = '".$weaponNum."' WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `round_id` = '".($this->TurnNumber)."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($shots_table);
		$this->db->execute($shots_table);
	}
	
	function onXmlRpcEliteHit($content)
	{
			if(!isset($this->TurnNumber))
		$this->TurnNumber = 0;
		$weaponNum = $content->Event->WeaponNum;
		
		$q = "SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'";
		Console::println($q);
		$shooterinfo = $this->db->execute($q)->fetchObject();
		
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."'";
		Console::println($q);
		$hits = $this->db->execute($q)->fetchObject();
		 
		$q = "UPDATE `player_maps` SET `hits` = '".($hits->hits+1)."' WHERE `player_id` = '".$shooterinfo->id."'  and `match_map_id` = ".$this->MapNumber." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$this->db->execute($q);
		
		$q = "SELECT * FROM `shots` WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `round_id` = '".($this->TurnNumber)."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$hits_query = $this->db->execute($q)->fetchObject();
		
		$hits_table = "UPDATE `shots` SET `hits` = '".($hits_query->hits+1)."', `weapon_id` = '".$weaponNum."' WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `round_id` = '".($this->TurnNumber)."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($hits_table);
		$this->db->execute($hits_table);
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
			   Console::println($qCap);
		$this->db->execute($qCap);

		// update capture statistics
		$q = "SELECT * FROM `players` WHERE `login` = '".$content->Event->Player->Login."'";
		Console::println($q);
		$info = $this->db->execute($q)->fetchObject();
		
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = '".$info->id."' and `match_map_id` = '".$this->MapNumber."'  and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$captures = $this->db->execute($q)->fetchObject();
		
		$q = "UPDATE `player_maps` SET `captures` = '".($captures->captures+1)."' WHERE `player_id` = '".$info->id."' and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		Console::println($q);
		$this->db->execute($q);
	}
	
	function onXmlRpcEliteNearMiss($content)
	{
	//var_dump($content->Event->MissDist);
	$weaponNum = $content->Event->WeaponNum;
	$WeaponName = $this->getWeaponName($weaponNum);
	$MissDist = $content->Event->MissDist;
	$map = $this->connection->getCurrentMapInfo();
	
	$q = "SELECT * FROM `players` WHERE `login` = '".$content->Event->Shooter->Login."'";
	Console::println($q);
	$shooterinfo = $this->db->execute($q)->fetchObject();
	
	$q = "SELECT * FROM `player_maps` WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = '".$this->MapNumber."' and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($q);
	$nearmisses = $this->db->execute($q)->fetchObject();
	

	
	$q = "UPDATE `player_maps` SET `nearmisses` = '".($nearmisses->nearmisses+1)."' WHERE `player_id` = '".$shooterinfo->id."' and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($q);
	$this->db->execute($q);
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
					".$this->MapNumber.",
					'".$map->uId."',
					'".$MissDist."',
					'".$content->Event->Shooter->Login."',
					'".$weaponNum."',
					'".$WeaponName."'
				  )";
				   Console::println($qnearmiss);
				$this->db->execute($qnearmiss);		  
		} else {
			$dbnearmiss = "UPDATE `nearmisses` SET `nearMissDist` = '".$MissDist."', `weaponid` = '".$weaponNum."', `weaponname` = '".$WeaponName."' WHERE `player_login` = '".$content->Event->Shooter->Login."' and `match_id` = ".$this->db->quote($this->MatchNumber)."";
			 Console::println($dbnearmiss);
			
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
	SET `MapEnd` = '".date('Y-m-d H:i:s')."', 
	 `RoundScore_blue` = '".$this->Roundscore_blue."',
	 `RoundScore_red` = '".$this->Roundscore_red."',
	 `TurnNumber` = '".$this->TurnNumber."',
	 `AllReady` = '0'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 Console::println($querymapEnd);
	$this->db->execute($querymapEnd);
	}
	
	function onXmlRpcEliteEndMatch($content)
	{
	$MapWin = $this->connection->getModeScriptSettings();
	print_r("Nb Map to win: ") . var_dump($MapWin['S_MapWin']);
	if($this->Mapscore_red == $MapWin OR $this->Mapscore_blue == $MapWin['S_MapWin']){
	$queryMapWinSettingsEnd = "UPDATE `matches` SET `MatchEnd` = '".date('Y-m-d H:i:s')."'  where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	Console::println($queryMapWinSettingsEnd);
	$this->db->execute($queryMapWinSettingsEnd);
	}
	else{
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