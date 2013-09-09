<?php
/**
Name: Willem 'W1lla' van den Munckhof
Date: Unknown but before ESWC
Project Name: eXpansion project www.exp-tm.team.com

What to do:

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
protected $RoundScore_blue;
protected $RoundScore_red;
protected $BlueScoreMatch;
protected $RedScoreMatch;
protected $BlueMapScore;
protected $RedMapScore;

	function onInit() {
		$this->setVersion('0.0.1');
	}

	function onLoad() {
	
		$admins = AdminGroup::get();
		
		$cmd = $this->registerChatCommand('extendWu', 'WarmUp_Extend', 0, true);
		$cmd->help = 'Extends WarmUp In Elite by Callvote.';
		
		$cmd = $this->registerChatCommand('endWu', 'WarmUp_Stop', 0, true);
		$cmd->help = 'ends WarmUp in Elite by Callvote.';
		
		$cmd = $this->registerChatCommand('pause', 'pause', 0, true);
		$cmd->help = 'Pauses match in Elite by Callvote.';
		
		$this->enableDatabase();
		$this->enableDedicatedEvents();
		
		if(!$this->db->tableExists('captures')) {
			$q = "CREATE TABLE IF NOT EXISTS `captures` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `player_login` varchar(60) NOT NULL,
  `map_uid` varchar(60) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('shots')) {
			$q = "CREATE TABLE IF NOT EXISTS `shots` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(11) NOT NULL,
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `weapon_id` int(11) NOT NULL,
  `shots` int(11) NOT NULL DEFAULT '0',
  `hits` int(11) NOT NULL DEFAULT '0',
  `counterhits` mediumint(9) NOT NULL DEFAULT '0',
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
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('match_details')) {
			$q = "CREATE TABLE IF NOT EXISTS `match_details` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `team_id` varchar(50) NOT NULL DEFAULT '',
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
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `team_id` varchar(50) NOT NULL DEFAULT '',
  `kills` mediumint(9) NOT NULL DEFAULT '0',
  `shots` mediumint(9) NOT NULL DEFAULT '0',
  `nearmisses` mediumint(9) NOT NULL DEFAULT '0',
  `hits` mediumint(9) NOT NULL DEFAULT '0',
  `counterhits` mediumint(9) NOT NULL DEFAULT '0',
  `deaths` mediumint(9) NOT NULL DEFAULT '0',
  `captures` mediumint(9) NOT NULL DEFAULT '0',
  `atkrounds` mediumint(9) NOT NULL DEFAULT '0',
  `atkSucces` mediumint(9) NOT NULL DEFAULT '0',
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
  `teamBlue` varchar(50) NOT NULL DEFAULT '',
  `teamBlue_emblem` varchar(250) NOT NULL DEFAULT '',
  `teamBlue_RGB` varchar(50) NOT NULL DEFAULT '',
  `teamRed` varchar(50) NOT NULL DEFAULT '',
  `teamRed_emblem` varchar(250) NOT NULL DEFAULT '',
  `teamRed_RGB` varchar(50) NOT NULL DEFAULT '',
  `Matchscore_blue` mediumint(9) NOT NULL DEFAULT '0',
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
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_uid` varchar(60) NOT NULL,
  `nearMissDist` float default '0',
  `player_login` varchar(50) NOT NULL,
  `weaponid` int(11) NOT NULL,
  `weaponname` varchar(45) DEFAULT NULL,
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
			if(!$this->db->tableExists('hits')) {
			$q = "CREATE TABLE IF NOT EXISTS `hits` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_uid` varchar(60) NOT NULL,
  `HitDist` float default '0',
  `shooter_player_login` varchar(50) NOT NULL,
  `victim_player_login` varchar(50) NOT NULL,
  `weaponid` int(11) NOT NULL,
  `weaponname` varchar(45) DEFAULT NULL,
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
				if(!$this->db->tableExists('clublinks')) {
			$q = "CREATE TABLE IF NOT EXISTS `clublinks` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `Clublink_Name` varchar(255) NOT NULL DEFAULT '',
  `Clublink_EmblemUrl` varchar(255) DEFAULT NULL,
  `Clublink_ZonePath` varchar(50) NOT NULL,
  `Clublink_Primary_RGB` varchar(6) NOT NULL,
  `Clublink_Secondary_RGB` varchar(6) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
		$this->db->execute($q);
		}
		
		
		$this->updateServerChallenges();

		$this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));
		
		$this->connection->setCallVoteRatiosEx(false, array(
			new \DedicatedApi\Structures\VoteRatio('SetModeScriptSettingsAndCommands', -1.)
			));
		
		Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Core v' . $this->getVersion());
		$this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [Shootmania] Elite Stats$fa0!');

		$match = $this->getServerCurrentMatch($this->storage->serverLogin);
		if ($match)
		{
			//var_dump($match);
			$this->updateMatchState($match);
		}
		
		//Restart map to initialize script
		$this->connection->executeMulticall(); // Flush calls
        $this->connection->restartMap();
		
		$this->enableDedicatedEvents(ServerEvent::ON_MODE_SCRIPT_CALLBACK);
		$this->enableDedicatedEvents(ServerEvent::ON_VOTE_UPDATED);
		
	foreach($this->storage->players as $player) {
			$this->onPlayerConnect($player->login, false);
	}

	}
	
	/*Chat messages*/
	
	function WarmUp_Extend($login, $amount)
	{
		 $vote = new \DedicatedApi\Structures\Vote();
         $vote->cmdName = 'Echo';
         $vote->cmdParam = array('Set WarmUp Extend', 'map_warmup_extend');
         $this->connection->callVote($vote, 0.5, 0, 1);
	}
	
	function WarmUp_Stop($login)
	{
		 $vote = new \DedicatedApi\Structures\Vote();
         $vote->cmdName = 'Echo';
         $vote->cmdParam = array('Set Warmup Stop', 'map_warmup_end');
         $this->connection->callVote($vote, 0.5, 0, 1);
	}
	
	function pause($login)
	{
		 $vote = new \DedicatedApi\Structures\Vote();
         $vote->cmdName = 'Echo';
         $vote->cmdParam = array('Set Map to Pause', 'map_pause');
         $this->connection->callVote($vote, 0.5, 0, 1);
	}
	
	/*Callbacks and Methods  */
	
	public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam){
	if ($stateName == "VotePassed")
	{
	if ($cmdName == "NextMap"){
	$map = $this->connection->getCurrentMapInfo();
		$queryNextMap = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	$this->db->execute($queryNextMap);
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($queryNextMap);
	}
	if ($cmdName == "SetModeScriptSettingsAndCommands"){
	$map = $this->connection->getCurrentMapInfo();
		$querySMSSAC = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	$this->db->execute($querySMSSAC);
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($querySMSSAC);
	}
	if ($cmdName == "JumpToMapIndex"){
	$map = $this->connection->getCurrentMapInfo();
		$queryJTMI = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	$this->db->execute($queryJTMI);
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($queryJTMI);
	}
	}
	}
	
	function onEcho($internal, $public){
	$param2 = (bool)true;
	switch ($internal)	{
			case "map_pause":
				$getdata = $this->connection->sendModeScriptCommands(array("Command_ForceWarmUp" => $param2));
				break;
				case "map_warmup_extend":
			try
		{
			Validation::int(6000, 0);
		}
		catch(\Exception $e)
		{
			return;
		}	
			$this->connection->triggerModeScriptEvent('WarmUp_Extend', '6000');
				break;
			case "map_warmup_end":
				$this->connection->triggerModeScriptEvent('WarmUp_Stop','');
				break;
	}
	}
	
	function getServerCurrentMatch($serverLogin){
	return $this->db->execute(
				'SELECT id FROM matches '.
				'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = '.$this->db->quote($this->storage->serverLogin).
				'order by id desc')->fetchSingleValue();

	}
	
	function updateMatchState($matchId){
	$state = date('Y-m-d H:i:s');
	$matches_update = "UPDATE matches SET `MatchEnd` = '".$state."' WHERE id = ".$matchId."";
	$this->db->execute($matches_update);
	$match_maps_update = "UPDATE match_maps SET `MapEnd` = '".$state."' WHERE match_id = ".$matchId."";
	$this->db->execute($match_maps_update);
	}
	
	public function onModeScriptCallback($param1, $param2)
	{
		switch ($param1)
		{
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
			case 'EndMatch':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteEndMatch($parameter);
			break;
			case 'EndMap':
			$parameter = json_decode($param2);
			$this->onXmlRpcEliteEndMap($parameter);
			break;
			case 'LibXmlRpc_Scores':
					$this->BlueScoreMatch = $param2[0];
					$this->RedScoreMatch = $param2[1];
					$this->BlueMapScore = $param2[2];
					$this->RedMapScore = $param2[3];
			break;
		}
	}
	
	function updateServerChallenges() {
        //get server challenges
        $serverChallenges = $this->storage->maps;
        //get database challenges

        $q = "SELECT * FROM `maps`;";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
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
        \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->query($q);
    }
	
	function onPlayerConnect($login, $isSpectator) {
		$player = $this->storage->getPlayerObject($login);
		$this->insertPlayer($player);
	}
	
	function insertPlayer($player) {
	$zone = explode("|",$player->path);
	if ($zone[0] == ""){
	$zone[2] = "World";
	}
		$q =  "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($player->login).";";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
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
					".$this->db->quote($zone[2]).",
					'".date('Y-m-d H:i:s')."'
				  )";
			$this->db->execute($q);
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		} else {
			$q = "UPDATE `players`
				  SET `nickname` = ".$this->db->quote($player->nickName).",
				      `nation` = ".$this->db->quote($zone[2]).",
				      `updatedate` = '".date('Y-m-d H:i:s')."'
				  WHERE `login` = ".$this->db->quote($player->login)."";
			$this->db->execute($q);
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		}
	
	}
	
	function onXmlRpcEliteBeginWarmUp($content)
	{
	if($content->AllReady == (bool) false){
	$map = $this->connection->getCurrentMapInfo();
	$q = "UPDATE `match_maps`
				  SET `AllReady` = '0'
				  WHERE `match_id` =".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);			  
	$this->db->execute($q);
	}
	else{
	}
	}
	
	function onXmlRpcEliteEndWarmUp($content)
	{
	if($content->AllReady == (bool) true){
	$map = $this->connection->getCurrentMapInfo();
	$q = "UPDATE `match_maps`
				  SET `AllReady` = '1'
				  WHERE `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);		
	$this->db->execute($q);
	}
	else{
	}
	}
	
	function onXmlRpcEliteMapStart($content)
	{

	$map = $this->connection->getCurrentMapInfo();
	$mapmatch = "SELECT * FROM `match_maps` WHERE `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `map_uid` = ".$this->db->quote($map->uId)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($mapmatch);		
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
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmapmatch);
		$this->db->execute($qmapmatch);
		$this->MapNumber = $this->db->insertID();
	} else {	
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
	
		$qmatch = "INSERT INTO `matches` (
						`MatchName`,
						`teamBlue`,
						`teamBlue_emblem`,
						`teamBlue_RGB`,
						`teamRed`,
						`teamRed_emblem`,
						`teamRed_RGB`,
						`Matchscore_blue`,
						`Matchscore_red`,
						`MatchStart`,
						`matchServerLogin`
					  ) VALUES (
						".$this->db->quote($MatchName).",
						".$this->db->quote($Blue).",
						".$this->db->quote($BlueEmblemUrl).",
						".$this->db->quote($BlueRGB).",
						".$this->db->quote($Red).",
						".$this->db->quote($RedEmblemUrl).",
						".$this->db->quote($RedRGB).",
						'0',
						'0',
						'".date('Y-m-d H:i:s')."',
						".$this->db->quote($this->storage->serverLogin)."
					  )";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmatch);
		$this->db->execute($qmatch);
		$this->MatchNumber = $this->db->insertID();
	}
	
	//Xml RPC events
	function onXmlRpcEliteBeginTurn($content)
	{
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnNumber = $content->TurnNumber;
	$this->TurnNumber = $TurnNumber;
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	
	$Blue = $this->connection->getTeamInfo(1)->name;
	$Red = $this->connection->getTeamInfo(2)->name;	
	$BlueRGB = $this->connection->getTeamInfo(1)->rGB;
	$RedRGB = $this->connection->getTeamInfo(2)->rGB;
	$BlueEmblemUrl = $this->connection->getTeamInfo(1)->emblemUrl;
	$RedEmblemUrl = $this->connection->getTeamInfo(2)->emblemUrl;

	$qmmsr = "UPDATE `matches`
	set teamBlue = ".$this->db->quote($Blue)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);
	
	$qmmsr = "UPDATE `matches`
	set teamRed = ".$this->db->quote($Red)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);
	
	$qmmsr = "UPDATE `matches`
	set teamBlue_emblem = ".$this->db->quote($BlueEmblemUrl)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);
	
	$qmmsr = "UPDATE `matches`
	set teamRed_emblem = ".$this->db->quote($RedEmblemUrl)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);

	$qmmsr = "UPDATE `matches`
	set teamBlue_RGB = ".$this->db->quote($BlueRGB)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);
	
	$qmmsr = "UPDATE `matches`
	set teamRed_RGB = ".$this->db->quote($RedRGB)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);
	
	//AtkQuery
	$mapbt = $this->connection->getCurrentMapInfo();
	////var_dump($mapbt);
	foreach ($this->storage->players as $login => $player){
	$player_id_query = "Select id from players where `login` = ".$this->db->quote($player->login)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($player_id_query);
	$PlayerID = $this->db->execute($player_id_query)->fetchObject();
	$shots_table = "SELECT * FROM `shots` WHERE `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($TurnNumber)." and `player_id` = ".$this->db->quote($PlayerID->id)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($shots_table);
	$execute = $this->db->execute($shots_table);
	if($execute->recordCount() == 0) {
	$shots_insert = "INSERT INTO `shots` (
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
				  \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($shots_insert);
		$this->db->execute($shots_insert);
	} else {
	}
	}
	
	foreach ($this->storage->players as $login => $player){
		$teamId = $player->teamId+1;
		$q = "Select id from players where `login` = ".$this->db->quote($player->login)."";
		//var_dump($player);
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$PlayerID = $this->db->execute($q)->fetchObject();
		$this->PlayerID = $PlayerID->id;
		$playermapinfo = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($this->PlayerID)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($playermapinfo);
		//var_dump($playermapinfo);
		$pmiexecute = $this->db->execute($playermapinfo);

		$Blue = $this->connection->getTeamInfo(1)->name;
		$Red = $this->connection->getTeamInfo(2)->name;	
	
		if($player->teamId == 0)
			$teamPlayer = $Blue;
		else
			$teamPlayer = $Red;
			
		
		if($pmiexecute->recordCount() == 0) {
			$pmi = "INSERT INTO `player_maps` (
						`match_id`,
						`player_id`,
						`match_map_id`,
						`team_id`,
						`matchServerLogin`
					  ) VALUES (
					  ".$this->MatchNumber.",
						'".$this->PlayerID."',
						'".$this->MapNumber."',
						'".$teamPlayer."',
						".$this->db->quote($this->storage->serverLogin)."
					  )";
			//var_dump($pmi);
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($pmi);
			$this->db->execute($pmi);
		}
		}
	
	$q = "SELECT * FROM `match_details` WHERE `map_uid` = ".$this->db->quote($mapbt->uId)." and `team_id` = ".$this->db->quote($AttackClan)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
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
				  \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->execute($q);
	} else {
	}
		//DefQuery
	$q = "SELECT * FROM `match_details` WHERE `map_uid` = ".$this->db->quote($mapbt->uId)."  and `match_id` = ".$this->db->quote($this->MatchNumber)." and `team_id` = ".$this->db->quote($DefClan)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin).";";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
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
				  \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->execute($q);
	} else {
		}
	$q = "Select id from players where `login` = ".$this->db->quote($content->AttackingPlayer->Login)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$qmapid = $this->db->execute($q)->fetchObject();
	$mapmatchAtk = "UPDATE `match_maps`
				  SET `AtkId` = ".$this->db->quote($qmapid->id)."
				  WHERE `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($mapbt->uId)."  and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($mapmatchAtk);	  
	$this->db->execute($mapmatchAtk);
	$qatk = "Select id from players where `login` = ".$this->db->quote($content->AttackingPlayer->Login)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$qmplayer_mapsAtkRoundId = $this->db->execute($qatk)->fetchObject();
	$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($qmplayer_mapsAtkRoundId->id)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$Atker = $this->db->execute($q)->fetchObject();
	$q = "UPDATE `player_maps` SET `atkrounds` = ".$this->db->quote($Atker->atkrounds+1)." WHERE `player_id` = ".$this->db->quote($qmplayer_mapsAtkRoundId->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->MatchNumber."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$this->db->execute($q);
	
	/* Clublinks*/
	$ClublinkBlue = $this->connection->getTeamInfo(1)->clubLinkUrl;
	$ClublinkRed = $this->connection->getTeamInfo(2)->clubLinkUrl;
	//var_dump($ClublinkBlue);
	//var_dump($ClublinkRed);
	if($ClublinkBlue == NULL){
	//Console::println('No Clublink found for Team Blue');
	}
	else
	{
	$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
	$url = $ClublinkBlue;
	$xml = file_get_contents($url, true, $context);
	$xml = simplexml_load_string($xml);
	$name = $xml->name;
	$emblem_web = $xml->emblem_web;
	$zone = explode("|",$xml->zone);
	if ($zone[0] == ""){
	$zone[2] = "World";
	}
	$colorprimary = $xml->color['primary'];
	$colorsecondary = $xml->color['secondary'];
	$qcblnk =  "SELECT * FROM `clublinks` WHERE `Clublink_Name` = ".$this->db->quote($name).";";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qcblnk);
		$execute = $this->db->execute($qcblnk);
		if($execute->recordCount() == 0) {
			$qBlueClublink = "INSERT INTO `clublinks` (
					`Clublink_Name`,
					`Clublink_EmblemUrl`,
					`Clublink_ZonePath`,
					`Clublink_Primary_RGB`,
					`Clublink_Secondary_RGB`
				  ) VALUES (
					".$this->db->quote($name).",
					".$this->db->quote($emblem_web).",
					".$this->db->quote($zone[2]).",
					".$this->db->quote($colorprimary).",
					".$this->db->quote($colorsecondary)."
				  )";
			$this->db->execute($qBlueClublink);
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qBlueClublink);
		} else {
			$qBlueClublink = "UPDATE `clublinks`
				  SET `Clublink_Name` = ".$this->db->quote($name)."
				  WHERE `Clublink_Name` = ".$this->db->quote($player->login)."";
			$this->db->execute($qBlueClublink);
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qBlueClublink);
		}
	}
	if($ClublinkRed == NULL){
	//Console::println('No Clublink found for Team Red');
	}
	else
	{
	$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
	$url = $ClublinkRed;
	$xml = file_get_contents($url, true, $context);
	$xml = simplexml_load_string($xml);
	$name = $xml->name;
	$emblem_web = $xml->emblem_web;
	$zone = explode("|",$xml->zone);
	if ($zone[0] == ""){
	$zone[2] = "World";
	}
	$colorprimary = $xml->color['primary'];
	$colorsecondary = $xml->color['secondary'];
	$qcblnk =  "SELECT * FROM `clublinks` WHERE `Clublink_Name` = ".$this->db->quote($name).";";
		Console::println($qcblnk);
		$execute = $this->db->execute($qcblnk);
		if($execute->recordCount() == 0) {
			$qRedClublink = "INSERT INTO `clublinks` (
					`Clublink_Name`,
					`Clublink_EmblemUrl`,
					`Clublink_ZonePath`,
					`Clublink_Primary_RGB`,
					`Clublink_Secondary_RGB`
				  ) VALUES (
					".$this->db->quote($name).",
					".$this->db->quote($emblem_web).",
					".$this->db->quote($zone[2]).",
					".$this->db->quote($colorprimary).",
					".$this->db->quote($colorsecondary)."
				  )";
			$this->db->execute($qRedClublink);
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qRedClublink);
		} else {
			$qRedClublink = "UPDATE `clublinks`
				  SET `Clublink_Name` = ".$this->db->quote($name)."
				  WHERE `Clublink_Name` = ".$this->db->quote($player->login)."";
			$this->db->execute($qRedClublink);
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qRedClublink);
		}
	}
	}
	
	function onXmlRpcEliteEndTurn($content)
	{
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnWinnerClan = $content->TurnWinnerClan;
	$WinType = $content->WinType;
	$Clan1RoundScore = $content->Clan1RoundScore;
	$Clan2RoundScore = $content->Clan2RoundScore;
	$TurnNumber = $content->TurnNumber;
	$AttackingClan = $content->AttackingClan;
	$DefendingClan = $content->DefendingClan;
	$TurnNumber = $content->TurnNumber;
	$this->RoundScore_blue = $Clan1RoundScore;
	$this->RoundScore_red = $Clan2RoundScore;
	$map = $this->connection->getCurrentMapInfo();
	$mapmatchAtk = "UPDATE `match_maps`
				  SET `AtkId` = 0,
				  `turnNumber` = ".$this->db->quote($this->TurnNumber).",
				  `Roundscore_blue` = ".$Clan1RoundScore.",
				  `Roundscore_red` = ".$Clan2RoundScore."
				  WHERE `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($mapmatchAtk);
	$this->db->execute($mapmatchAtk);
	$mapbt = $this->connection->getCurrentMapInfo();
	$AttackClan = $this->connection->getTeamInfo($AttackingClan)->name;
	$DefClan = $this->connection->getTeamInfo($DefendingClan)->name;
	
	$q = "SELECT * FROM `match_details` WHERE `team_id` = ".$this->db->quote($AttackClan)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$attacks = $this->db->execute($q)->fetchObject();
	$qatk = "UPDATE `match_details`
				  SET `attack` = ".$this->db->quote($attacks->attack+1)."
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `map_uid` = ".$this->db->quote($map->uId)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qatk);
	$this->db->execute($qatk);
	
	if ($WinType == 'Capture'){
	$qcapture = "UPDATE `match_details`
				  SET `capture` = ".$this->db->quote($attacks->capture+1)."
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `map_uid` = ".$this->db->quote($map->uId)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qcapture);
	$this->db->execute($qcapture);
	}
	
		if ($WinType == 'DefenseEliminated'){
	$qawe = "UPDATE `match_details`
				  SET `attackWinEliminate` = ".$this->db->quote($attacks->attackWinEliminate+1)."
				  WHERE `team_id` = ".$this->db->quote($AttackClan)." and `map_uid` = ".$this->db->quote($map->uId)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
			\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qawe);	  
	$this->db->execute($qawe);
	$q = "Select id from players where `login` = ".($this->db->quote($content->AttackingPlayer->Login));
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$Attackerinfo = $this->db->execute($q)->fetchObject();
	
	$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($Attackerinfo->id)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$AtkSucces = $this->db->execute($q)->fetchObject();

	$q = "UPDATE `player_maps` SET `atkSucces` = ".$this->db->quote($AtkSucces->atkSucces+1)." WHERE `player_id` = ".$this->db->quote($Attackerinfo->id)."  and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
	$this->db->execute($q);
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	}
	
	$q = "SELECT * FROM `match_details` WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($map->uId)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$defenses = $this->db->execute($q)->fetchObject();
	$qdef = "UPDATE `match_details`
				  SET `defence` = ".$this->db->quote($defenses->defence+1)."
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($map->uId)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qdef);
		$this->db->execute($qdef);
		
	if ($WinType == 'TimeLimit'){
	$qtl = "UPDATE `match_details`
				  SET `timeOver` = ".$this->db->quote($defenses->timeOver+1)."
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($map->uId)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qtl);
	$this->db->execute($qtl);
	}
	
		if ($WinType == 'AttackEliminated'){
	$qde = "UPDATE `match_details`
				  SET `defenceWinEliminate` = ".$this->db->quote($defenses->defenceWinEliminate+1)."
				  WHERE `team_id` = ".$this->db->quote($DefClan)." and `map_uid` = ".$this->db->quote($map->uId)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
				  \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qde);
	$this->db->execute($qde);
	}
	
	
	/*
	Store CurrentReplays for a Turn.
	*/
	
	}
	
	function onXmlRpcEliteArmorEmpty($content)
	{
	if(!isset($this->TurnNumber))
		$this->TurnNumber = 0;
		$weaponNum = $content->Event->WeaponNum;
	$shooter = $content->Event->Shooter;
	$victim = $content->Event->Victim;
	if ($shooter == NULL){
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write('Player '.$victim->Login.' was killed in offzone.');
	}
	else
	{
	$map = $this->connection->getCurrentMapInfo();
		// Insert kill into the database
		$q = "INSERT INTO `kills` (
				`match_id`,
				`player_victim`,
				`player_shooter`,
				`time`,
				`map_uid`,
				`matchServerLogin`
			  ) VALUES (
			  ".$this->MatchNumber.",
			    '".$content->Event->Victim->Login."',
			    '".$content->Event->Shooter->Login."',
			    '".date('Y-m-d H:i:s')."',
			    '".$map->uId."',
				".$this->db->quote($this->storage->serverLogin)."
			  )";
			   \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->execute($q);
		// update kill/death statistics
		$q = "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($content->Event->Shooter->Login)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$shooterinfo = $this->db->execute($q)->fetchObject();
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$kills = $this->db->execute($q)->fetchObject();
		$q = "UPDATE `player_maps` SET `kills` = ".$this->db->quote($kills->kills+1)." WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->execute($q);
		
		$q = "SELECT * FROM `shots` WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$eliminations_query = $this->db->execute($q)->fetchObject();
		
		$eliminations_table = "UPDATE `shots` SET `eliminations` = ".$this->db->quote($eliminations_query->eliminations+1).", `weapon_id` = ".$this->db->quote($weaponNum)." WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($eliminations_table);
		$this->db->execute($eliminations_table);
		
		$q = "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($content->Event->Victim->Login)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$victiminfo = $this->db->execute($q)->fetchObject();
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($victiminfo->id)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$deaths = $this->db->execute($q)->fetchObject();

		$q = "UPDATE `player_maps` SET `deaths` = ".$this->db->quote($deaths->deaths+1)." WHERE `player_id` = ".$this->db->quote($victiminfo->id)."  and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		$this->db->execute($q);
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);

		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write('['.date('H:i:s').'] [ShootMania] [Elite] '.$content->Event->Victim->Login.' was killed by '.$content->Event->Shooter->Login);
	}
	}
	
	function onXmlRpcEliteShoot($content)
	{
		if(!isset($this->TurnNumber))
		$this->TurnNumber = 0;
		$weaponNum = $content->Event->WeaponNum;
		
		$q = "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($content->Event->Shooter->Login)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$shooterinfo = $this->db->execute($q)->fetchObject();
		
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_id` = ".$this->db->quote($this->MatchNumber)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$shots = $this->db->execute($q)->fetchObject();
		 
		$q = "UPDATE `player_maps` SET `shots` = ".$this->db->quote($shots->shots+1)." WHERE `player_id` = ".$this->db->quote($shooterinfo->id)."  and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->MatchNumber."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->execute($q);
		
		$q = "SELECT * FROM `shots` WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." ";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$shots_query = $this->db->execute($q)->fetchObject();
		
		$shots_table = "UPDATE `shots` SET `shots` = ".$this->db->quote($shots_query->shots+1).", `weapon_id` = ".$this->db->quote($weaponNum)." WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($shots_table);
		$this->db->execute($shots_table);
	}
	
	function onXmlRpcEliteHit($content)
	{
			if(!isset($this->TurnNumber))
		$this->TurnNumber = 0;
	$weaponNum = $content->Event->WeaponNum;
	$WeaponName = $this->getWeaponName($weaponNum);
	$HitDist = $content->Event->HitDist;
	$map = $this->connection->getCurrentMapInfo();
		
		$q = "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($content->Event->Shooter->Login)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$shooterinfo = $this->db->execute($q)->fetchObject();
		
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$hits = $this->db->execute($q)->fetchObject();
		 
		$q = "UPDATE `player_maps` SET `hits` = ".$this->db->quote($hits->hits+1)." WHERE `player_id` = ".$this->db->quote($shooterinfo->id)."  and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->execute($q);
		
		$q = "SELECT * FROM `shots` WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$hits_query = $this->db->execute($q)->fetchObject();
		
		$hits_table = "UPDATE `shots` SET `hits` = ".$this->db->quote($hits_query->hits+1).", `weapon_id` = ".$this->db->quote($weaponNum)." WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($hits_table);
		$this->db->execute($hits_table);
		
		$q1 = "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($content->Event->Victim->Login)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q1);
		$victiminfo = $this->db->execute($q1)->fetchObject();
		
		$q1 = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($victiminfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q1);
		$counterhits = $this->db->execute($q1)->fetchObject();
		 
		$q1 = "UPDATE `player_maps` SET `counterhits` = ".$this->db->quote($counterhits->counterhits+1)." WHERE `player_id` = ".$this->db->quote($victiminfo->id)."  and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q1);
		$this->db->execute($q1);
		
		$q1 = "SELECT * FROM `shots` WHERE `player_id` = ".$this->db->quote($victiminfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q1);
		$counterhits_query = $this->db->execute($q1)->fetchObject();
		
		$counterhits_table = "UPDATE `shots` SET `counterhits` = ".$this->db->quote($counterhits_query->counterhits+1).", `weapon_id` = ".$this->db->quote($weaponNum)." WHERE `player_id` = ".$this->db->quote($victiminfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `round_id` = ".$this->db->quote($this->TurnNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($counterhits_table);
		$this->db->execute($counterhits_table);
		
		$qhitdist = "INSERT INTO `hits` (
					`match_map_id`,
					`map_uid`,
					`HitDist`,
					`shooter_player_login`,
					`victim_player_login`,
					`weaponid`,
					`weaponname`,
					`matchServerLogin`
				  ) VALUES (
					".$this->MapNumber.",
					'".$map->uId."',
					'".$HitDist."',
					'".$content->Event->Shooter->Login."',
					'".$content->Event->Victim->Login."',
					'".$weaponNum."',
					'".$WeaponName."',
					".$this->db->quote($this->storage->serverLogin)."
				  )";
				   \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qhitdist);
				$this->db->execute($qhitdist);
	}
	
	function onXmlRpcEliteCapture($content)
	{
	$map = $this->connection->getCurrentMapInfo();
	
		$qCap = "INSERT INTO `captures` (
				`match_id`,
				`player_login`,
				`map_uid`,
				`time`,
				`matchServerLogin`
			  ) VALUES (
			  ".$this->MatchNumber.",
			    '".$content->Event->Player->Login."',
			    '".$map->uId."',
			    '".date('Y-m-d H:i:s')."',
				".$this->db->quote($this->storage->serverLogin)."
			  )";
			   \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qCap);
		$this->db->execute($qCap);

		// update capture statistics
		$q = "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($content->Event->Player->Login)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$info = $this->db->execute($q)->fetchObject();
		
		$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($info->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)."  and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$captures = $this->db->execute($q)->fetchObject();
		
		$q = "UPDATE `player_maps` SET `captures` = ".$this->db->quote($captures->captures+1)." WHERE `player_id` = ".$this->db->quote($info->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
		\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
		$this->db->execute($q);
	}
	
	function onXmlRpcEliteNearMiss($content)
	{
	//var_dump($content->Event->MissDist);
	$weaponNum = $content->Event->WeaponNum;
	$WeaponName = $this->getWeaponName($weaponNum);
	$MissDist = $content->Event->MissDist;
	$map = $this->connection->getCurrentMapInfo();
	
	$q = "SELECT * FROM `players` WHERE `login` = ".$this->db->quote($content->Event->Shooter->Login)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$shooterinfo = $this->db->execute($q)->fetchObject();
	
	$q = "SELECT * FROM `player_maps` WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$nearmisses = $this->db->execute($q)->fetchObject();
	

	
	$q = "UPDATE `player_maps` SET `nearmisses` = ".$this->db->quote($nearmisses->nearmisses+1)." WHERE `player_id` = ".$this->db->quote($shooterinfo->id)." and `match_map_id` = ".$this->db->quote($this->MapNumber)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `match_id` = ".$this->db->quote($this->MatchNumber)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($q);
	$this->db->execute($q);
			$qnearmiss = "INSERT INTO `nearmisses` (
					`match_map_id`,
					`map_uid`,
					`nearMissDist`,
					`player_login`,
					`weaponid`,
					`weaponname`,
					`matchServerLogin`
				  ) VALUES (
					".$this->MapNumber.",
					'".$map->uId."',
					'".$MissDist."',
					'".$content->Event->Shooter->Login."',
					'".$weaponNum."',
					'".$WeaponName."',
					".$this->db->quote($this->storage->serverLogin)."
				  )";
				   \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qnearmiss);
				$this->db->execute($qnearmiss);		  
	}
	
	function onXmlRpcEliteEndMap($content)
	{
	
	$map = $this->connection->getCurrentMapInfo();
	$querymapEnd = "UPDATE `match_maps`
	SET `MapEnd` = '".date('Y-m-d H:i:s')."', 
	 `RoundScore_blue` = '".$this->RoundScore_blue."',
	 `RoundScore_red` = '".$this->RoundScore_red."',
	 `TurnNumber` = '".$this->TurnNumber."',
	 `AllReady` = '0'
	 where `match_id` = ".$this->db->quote($this->MatchNumber)." and `map_uid` = ".$this->db->quote($map->uId)." and `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($querymapEnd);
	$this->db->execute($querymapEnd);
	
	$this->connection->triggerModeScriptEvent('LibXmlRpc_GetScores','');
	
		//MatchScore Blue
	$qmmsb = "UPDATE `matches`
	set Matchscore_blue = ".$this->db->quote($this->BlueScoreMatch)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsb);
	$this->db->execute($qmmsb);
	//MatchScore Red
	$qmmsr = "UPDATE `matches`
	set Matchscore_red = ".$this->db->quote($this->RedScoreMatch)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);
	
	}
	
	function onXmlRpcEliteEndMatch($content)
	{
	$MapWin = $this->connection->getModeScriptSettings();
	//print_r("Nb Map to win: ") . var_dump($MapWin['S_MapWin']);
	$queryMapWinSettingsEnd = "UPDATE `matches` SET `MatchEnd` = '".date('Y-m-d H:i:s')."'
	where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	\ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($queryMapWinSettingsEnd);
	$this->db->execute($queryMapWinSettingsEnd);
	
	$this->connection->triggerModeScriptEvent('LibXmlRpc_GetScores','');

		//MatchScore Blue
	$qmmsb = "UPDATE `matches`
	set Matchscore_blue = ".$this->db->quote($this->BlueScoreMatch)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsb);
	$this->db->execute($qmmsb);
	//MatchScore Red
	$qmmsr = "UPDATE `matches`
	set Matchscore_red = ".$this->db->quote($this->RedScoreMatch)." where `matchServerLogin` = ".$this->db->quote($this->storage->serverLogin)." and `id` = ".$this->db->quote($this->MatchNumber)."";
	 \ManiaLive\Utilities\Logger::getLog("ElitePlugin-'".$this->storage->serverLogin."'")->write($qmmsr);
	$this->db->execute($qmmsr);
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