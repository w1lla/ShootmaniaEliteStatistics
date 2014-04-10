<?php

/**
  Name: Willem 'W1lla' van den Munckhof
  Date: 10-4-2014
  Version: 2 (GA2K14)
  Project Name: ESWC Elite Statistics

  What to do:

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
use ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;
use ManiaLivePlugins\Shootmania\Elite\Classes\Log;
use ManiaLib\Gui\Elements\Icons128x128_1;
use Maniaplanet\DedicatedServer\Structures;
use ManiaLivePlugins\Shootmania\Elite\Config;

class Elite extends \ManiaLive\PluginHandler\Plugin {

    /** @var integer */
    protected $MatchNumber = false;

	public $mapdirectory;
    /** @var integer */
    protected $TurnNumber;
    protected $Mapscore_blue;
    protected $Mapscore_red;
    protected $WarmUpAllReady;
    protected $MapNumber;
    protected $BlueScoreMatch;
    protected $RedScoreMatch;
    protected $BlueMapScore;
    protected $RedMapScore;
    protected $config;
    protected $competition_id;
    protected $ServerName;
    /** @var Log */
    private $logger;

    /** @var $playerIDs[$login] = number */
    private $playerIDs = array();

    function onInit() {
        $this->setVersion('1.0.5h');
    
        $this->logger = new Log($this->storage->serverLogin);
		$this->mapdirectory = $this->connection->getMapsDirectory();
  }
  
  function onLoad() {
        $admins = AdminGroup::get();
        $cmd = $this->registerChatCommand('extendWu', 'WarmUp_Extend', 0, true);
        $cmd->help = 'Extends WarmUp In Elite by Callvote.';

        $cmd = $this->registerChatCommand('endWu', 'WarmUp_Stop', 0, true);
        $cmd->help = 'ends WarmUp in Elite by Callvote.';

        $cmd = $this->registerChatCommand('pause', 'pause', 0, true);
        $cmd->help = 'Pauses match in Elite by Callvote.';
    
    $cmd = $this->registerChatCommand('newmatch', 'newmatch', 0, true, $admins);
    $cmd->isPublic = false;
        $cmd->help = 'Admin Starts a new Match.';
    
    $cmd = $this->registerChatCommand('bo5', 'bo5', 0, true, $admins);
    $cmd->isPublic = false;
        $cmd->help = 'Admin set mapWin to 3.';
    
    $cmd = $this->registerChatCommand('bo3', 'bo3', 0, true, $admins);
    $cmd->isPublic = false;
        $cmd->help = 'Admin set mapWin to 2';
    
    
		$this->config = Config::getInstance();
    			$this->competition_id = $this->config->competition_id;
     
        $this->enableDatabase();
        $this->enableDedicatedEvents();
    $this->enablePluginEvents();

        if(!$this->db->tableExists('captures')) {
      $q = "CREATE TABLE IF NOT EXISTS `captures` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(3) NOT NULL,
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_id` mediumint(9) NOT NULL DEFAULT '0',
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }
    
        if(!$this->db->tableExists('shots')) {
      $q = "CREATE TABLE IF NOT EXISTS `shots` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(3) NOT NULL,
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `weapon_id` int(11) NOT NULL,
  `shots` int(11) NOT NULL DEFAULT '0',
  `hits` int(11) NOT NULL DEFAULT '0',
  `counterhits` mediumint(9) NOT NULL DEFAULT '0',
  `eliminations` int(11) NOT NULL DEFAULT '0',
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }
    
    if(!$this->db->tableExists('kills')) {
      $q = "CREATE TABLE IF NOT EXISTS `kills` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(3) NOT NULL,
  `player_victim_id` mediumint(9) NOT NULL DEFAULT '0',
  `player_shooter_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_id` mediumint(9) NOT NULL DEFAULT '0',
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }
    
        if(!$this->db->tableExists('match_details')) {
      $q = "CREATE TABLE IF NOT EXISTS `match_details` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `team_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_id` mediumint(9) NOT NULL DEFAULT '0',
  `attack` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `defence` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `capture` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `timeOver` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `attackWinEliminate` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
  `defenceWinEliminate` MEDIUMINT( 9 ) NOT NULL DEFAULT '0',
   `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }
    
        if(!$this->db->tableExists('players')) {
      $q = "CREATE TABLE IF NOT EXISTS `players` (
   `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `nation` varchar(50) NOT NULL,
  `updatedate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }   
    
       if(!$this->db->tableExists('player_nicknames')) {
      $q = "CREATE TABLE IF NOT EXISTS `player_nicknames` (
 `player_id` mediumint(9) NOT NULL,  
 `nickname` varchar(100) DEFAULT NULL,
 `competition_id` INT(10) NOT NULL DEFAULT '1',
 INDEX id (player_id, competition_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
      $this->db->execute($q);
    }
    
    if(!$this->db->tableExists('player_maps')) {
      $q = "CREATE TABLE IF NOT EXISTS `player_maps` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `team_id` mediumint(9) NOT NULL DEFAULT '0',
  `kills` mediumint(9) NOT NULL DEFAULT '0',
  `shots` mediumint(9) NOT NULL DEFAULT '0',
  `nearmisses` mediumint(9) NOT NULL DEFAULT '0',
  `hits` mediumint(9) NOT NULL DEFAULT '0',
  `counterhits` mediumint(9) NOT NULL DEFAULT '0',
  `deaths` mediumint(9) NOT NULL DEFAULT '0',
  `captures` mediumint(9) NOT NULL DEFAULT '0',
  `atkrounds` mediumint(9) NOT NULL DEFAULT '0',
  `atkSucces` mediumint(9) NOT NULL DEFAULT '0',
  `timeOver` mediumint(9) NOT NULL DEFAULT '0',
  `elimination_3x` mediumint(9) NOT NULL DEFAULT '0',
   `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
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
  `teamBlue_emblem` varchar(250) NOT NULL DEFAULT '',
  `teamBlue_RGB` varchar(50) NOT NULL DEFAULT '',
  `teamRed`  mediumint(9) NOT NULL DEFAULT '0',
  `teamRed_emblem` varchar(250) NOT NULL DEFAULT '',
  `teamRed_RGB` varchar(50) NOT NULL DEFAULT '',
  `Matchscore_blue` INT(10) NOT NULL DEFAULT '0',
  `Matchscore_red` INT(10) NOT NULL DEFAULT '0',
  `MatchStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `MatchEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `matchServerLogin` VARCHAR(250) NOT NULL,
  `competition_id` INT(10) NOT NULL DEFAULT '1',
  `show` tinyint (1),
  `Replay` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }

    if(!$this->db->tableExists('match_maps')) {
      $q = "CREATE TABLE IF NOT EXISTS `match_maps` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `map_id` mediumint(9) NOT NULL DEFAULT '0',
  `turnNumber` mediumint(9) NOT NULL DEFAULT '0',
  `Roundscore_blue` INT(10) NOT NULL DEFAULT '0',
  `Roundscore_red` INT(10) NOT NULL DEFAULT '0',
  `MapStart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `MapEnd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `AtkId` mediumint(9) DEFAULT '0',
  `AllReady` boolean default '0',
  `NextMap` boolean default '0',
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }
    
        if(!$this->db->tableExists('nearmisses')) {
      $q = "CREATE TABLE IF NOT EXISTS `nearmisses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(3) NOT NULL,
  `map_id` mediumint(9) NOT NULL DEFAULT '0',
  `nearMissDist` REAL default '0',
  `player_id` mediumint(9) NOT NULL DEFAULT '0',
  `weaponid` int(11) NOT NULL,
  `weaponname` varchar(45) DEFAULT NULL,
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }
    
      if(!$this->db->tableExists('hits')) {
      $q = "CREATE TABLE IF NOT EXISTS `hits` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `match_map_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(3) NOT NULL,
  `map_id` mediumint(9) NOT NULL DEFAULT '0',
  `HitDist` REAL default '0',
  `shooter_player_id` mediumint(9) NOT NULL DEFAULT '0',
  `victim_player_id` mediumint(9) NOT NULL DEFAULT '0',
  `weaponid` int(11) NOT NULL,
  `weaponname` varchar(45) DEFAULT NULL,
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY(matchServerLogin)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
      $this->db->execute($q);
    }
    
        if(!$this->db->tableExists('clublinks')) {
      $q = "CREATE TABLE IF NOT EXISTS `clublinks` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `Clublink_Name` varchar(255) NOT NULL DEFAULT '',
  `Clublink_Name_Clean` varchar(255) NOT NULL DEFAULT '',
  `Clublink_EmblemUrl` varchar(255) DEFAULT NULL,
  `Clublink_ZonePath` varchar(50) NOT NULL,
  `Clublink_Primary_RGB` varchar(6) NOT NULL,
  `Clublink_Secondary_RGB` varchar(6) NOT NULL,
  `Clublink_URL` VARCHAR(250) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
    $this->db->execute($q);
    

  $q = "INSERT INTO `clublinks` (
  `id`, 
  `Clublink_Name`, 
  `Clublink_Name_Clean`, 
  `Clublink_EmblemUrl`, 
  `Clublink_ZonePath`, 
  `Clublink_Primary_RGB`, 
  `Clublink_Secondary_RGB`, 
  `Clublink_URL`) VALUES
  (1, 'Blue', 'Blue', NULL, 'France', '00F', 'F00', ''),
  (2, 'Red', 'Red', NULL, 'France', 'F00', '00F', '');";
  $this->db->execute($q);
    }
    
    if(!$this->db->tableExists('player_stats')) {
      $q = "CREATE TABLE IF NOT EXISTS `player_stats` (
  `competition_id` int(10) NOT NULL DEFAULT '1',
  `player_id` int(9) NOT NULL,
  `player_login` varchar(50) NOT NULL,
  `player_nickname` varchar(255) NOT NULL,
  `player_nation` varchar(100) NOT NULL,
  `team_id` mediumint(9) NOT NULL,
  `shots_laser` int(9) NOT NULL DEFAULT '0',
  `hits_laser` int(9) NOT NULL DEFAULT '0',
  `ratio_laser` decimal(5,2) NOT NULL,
  `shots_rockets` int(9) NOT NULL DEFAULT '0',
  `hits_rockets` int(9) NOT NULL DEFAULT '0',
  `ratio_rockets` decimal(5,2) NOT NULL,
  `shots_nucleus` int(9) NOT NULL DEFAULT '0',
  `hits_nucleus` int(9) NOT NULL DEFAULT '0',
  `ratio_nucleus` decimal(5,2) NOT NULL,
  `nb_atk` int(9) NOT NULL DEFAULT '0',
  `success_atk` int(9) NOT NULL DEFAULT '0',
  `ratio_atk` decimal(5,2) NOT NULL,
  `captures` int(9) NOT NULL DEFAULT '0',
  `hits_received` mediumint(9) NOT NULL DEFAULT '0',
  `ratio_hits` decimal(5,2) NOT NULL,
  `elimination_3x` int(9) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $this->db->execute($q);
    }
    
        if($this->isPluginLoaded('ManiaLivePlugins\Standard\Menubar\Menubar'))
      $this->onPluginLoaded('ManiaLivePlugins\Standard\Menubar\Menubar');
  }
      function onReady() {
        $this->updateServerChallenges();

        $this->connection->setModeScriptSettings(array('S_UseScriptCallbacks' => true));

        $this->connection->setModeScriptSettings(array('S_RestartMatchOnTeamChange' => true)); //Debug Way...
        $this->connection->setModeScriptSettings(array('S_UsePlayerClublinks' => true)); //Debug Way...
    $this->connection->setModeScriptSettings(array('S_Mode' => 1));
        $this->connection->setCallVoteRatios(array(array('Command' => 'SetModeScriptSettingsAndCommands', 'Ratio' => 0.4 )));
    
    Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Core v' . $this->getVersion());
    foreach ($this->storage->players as $player) {
        $this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [Shootmania] Elite Stats$fa0!', $player->login);
    }
    
        $match = $this->getServerCurrentMatch($this->storage->serverLogin);
        if ($match) {
            //var_dump($match);
            $this->updateMatchState($match);
        }

        //Restart map to initialize script
        $this->connection->executeMulticall(); // Flush calls
        $this->connection->restartMap();

        $this->enableDedicatedEvents(ServerEvent::ON_MODE_SCRIPT_CALLBACK);
        $this->enableDedicatedEvents(ServerEvent::ON_VOTE_UPDATED);

        foreach ($this->storage->players as $player) {
            $this->onPlayerConnect($player->login, false);
        }
    
    foreach ($this->storage->spectators as $player) {
            $this->onPlayerConnect($player->login, false);
        }
        
        $this->ServerName = $this->connection->getServerName();
        $this->connection->setServerTag('server_name', $this->ServerName, true);
        $this->connection->setServerName($this->ServerName);
          
    }
  
  function onPluginLoaded($pluginId)
  {
    if($pluginId == 'ManiaLivePlugins\Standard\Menubar\Menubar')
      $this->buildMenu();
  }
  
  function buildMenu()
  {
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'initMenu',
      Icons128x128_1::Custom);
    
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'Mode: Classic (3v3)',
      array($this, 'S_Mode0'),
      true);
    
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'Mode: Free ',
      array($this, 'S_Mode1'),
      true);
    
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'WarmUp Extend',
      array($this, 'WarmUp_Extend'),
      true);
      
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'WarmUp Stop',
      array($this, 'WarmUp_Stop'),
      true);

    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'Pause',
      array($this, 'pause'),
      true);

    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'New Match',
      array($this, 'newmatch'),
      true);
      
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'BO3',
      array($this, 'bo3'),
      true);
      
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'BO5',
      array($this, 'bo5'),
      true);
      
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'Reset Callvotes active',
      array($this, 'InitiateVotes'),
      true);
    
    $this->callPublicMethod('ManiaLivePlugins\Standard\Menubar\Menubar',
      'addButton',
      'Set Callvotes inactive',
      array($this, 'DeactivateVotes'),
      true);
  }

    /* Chat messages */

    function WarmUp_Extend($login) {
        $vote = new \Maniaplanet\DedicatedServer\Structures\Vote();
        $vote->cmdName = 'Echo';
        $vote->cmdParam = array('Set WarmUp Extend', 'map_warmup_extend');
        $this->connection->callVote($vote, 0.5, 0, 1);
    }

    function WarmUp_Stop($login) {
        $vote = new \Maniaplanet\DedicatedServer\Structures\Vote();
        $vote->cmdName = 'Echo';
        $vote->cmdParam = array('Set Warmup Stop', 'map_warmup_end');
        $this->connection->callVote($vote, 0.5, 0, 1);
    }

    function pause($login) {
        $vote = new \Maniaplanet\DedicatedServer\Structures\Vote();
        $vote->cmdName = 'Echo';
        $vote->cmdParam = array('Set Map to Pause', 'map_pause');
        $this->connection->callVote($vote, 0.5, 0, 1);
    }
  
  function S_Mode0($login) {
  $this->connection->setModeScriptSettings(array('S_Mode' => 0));
    }
  
  function S_Mode1($login) {
  $this->connection->setModeScriptSettings(array('S_Mode' => 1));
    }
  
  function InitiateVotes($login){
  $this->connection->setCallVoteRatiosEx(false, array(
            new Structures\VoteRatio('SetModeScriptSettingsAndCommands', 0.)
        ));
  }
  
  function DeactivateVotes($login){
  $this->connection->setCallVoteRatiosEx(false, array(
            new Structures\VoteRatio('SetModeScriptSettingsAndCommands', -1.)
        ));
  }
  
  function newmatch($login) {
        $match = $this->getServerCurrentMatch($this->storage->serverLogin);
        if ($match) {
            //var_dump($match);
            $this->updateMatchState($match);
        }

        //Restart map to initialize script
        $this->connection->executeMulticall(); // Flush calls
        $this->connection->restartMap();
    }
  
  function bo3($login) {
  $this->connection->setModeScriptSettings(array('S_MapWin' => 2));
    }
  
  function bo5($login) {
       $this->connection->setModeScriptSettings(array('S_MapWin' => 3));
    }

    /* Callbacks and Methods  */

    public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {
        if ($stateName == "VotePassed") {
            if ($cmdName == "SetNextMapIndex") {
                $queryNextMap = "UPDATE `match_maps`
  SET `NextMap` = '1'
   where `match_id` = " . $this->db->quote($this->MatchNumber) . " and `map_id` = " . $this->db->quote($this->getMapid()) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
                $this->db->execute($queryNextMap);
                $this->logger->Debug($queryNextMap);
            }
        }
    }

    function onEcho($internal, $public) {
			//var_dump($internal);
  			//var_dump($public);
        switch ($internal) {
            case "map_pause":
                $this->connection->sendModeScriptCommands(array("Command_ForceWarmUp" => true));
                break;
            case "map_warmup_extend":
                try {
                    Validation::int(60000, 0);
                } catch (\Exception $e) {
                    return;
                }
                $this->connection->triggerModeScriptEvent('WarmUp_Extend', '60000');
                break;
            case "map_warmup_end":
                $this->connection->triggerModeScriptEvent('WarmUp_Stop', '');
                break;
        }
    if ($public == "DrakoniaTest"){
    switch ($internal) {
            case "map_pause":
                $this->connection->sendModeScriptCommands(array("Command_ForceWarmUp" => true));
                break;
            case "map_warmup_extend":
                try {
                    Validation::int(60000, 0);
                } catch (\Exception $e) {
                    return;
                }
                $this->connection->triggerModeScriptEvent('WarmUp_Extend', '6000');
                break;
            case "map_warmup_end":
                $this->connection->triggerModeScriptEvent('WarmUp_Stop', '');
                break;
      case "newmatch":
                $this->newmatch($public);
                break;
      case "bo3":
                $this->bo3($public);
                break;
      case "bo5":
                $this->bo5($public);
                break;
        }
    }
    }

    function getServerCurrentMatch($serverLogin) {
   $CurrentMatchÏd = $this->db->execute(
                        'SELECT id FROM matches ' .
                        'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = ' . $this->db->quote($serverLogin) .
                        '')->fetchSingleValue();
  $this->logger->Normal($CurrentMatchÏd);         
        return $this->db->execute(
                        'SELECT id FROM matches ' .
                        'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = ' . $this->db->quote($serverLogin) .
                        '')->fetchSingleValue();
    }

    function updateMatchState($matchId) {
        $state = date('Y-m-d H:i:s');
        $matches_update = "UPDATE matches SET `MatchEnd` = " . $this->db->quote($state) . " WHERE id = " . intval($matchId) . "";
    $this->logger->Debug($matches_update);
        $this->db->execute($matches_update);
        $match_maps_update = "UPDATE match_maps SET `MapEnd` = " . $this->db->quote($state) . " WHERE match_id = " . intval($matchId) . "";
    $this->logger->Debug($match_maps_update);
        $this->db->execute($match_maps_update);
    }

    public function onModeScriptCallback($event, $json) {
  $this->logger->Callbacks($event);
  $this->logger->Callbacks($json);
        switch ($event) {
            case 'BeginMatch':
                $this->onXmlRpcEliteMatchStart(new JsonCallbacks\BeginMatch($json));
                break;
            case 'BeginMap':
                $this->onXmlRpcEliteMapStart(new JsonCallbacks\BeginMap($json));
                break;
            case 'BeginWarmup':
                $this->onXmlRpcEliteBeginWarmUp(new JsonCallbacks\BeginWarmup($json));
                break;
            case 'EndWarmup':
                $this->onXmlRpcEliteEndWarmUp(new JsonCallbacks\EndWarmup($json));
                break;
            case 'BeginTurn':
                $this->onXmlRpcEliteBeginTurn(new JsonCallbacks\BeginTurn($json));
                break;
            case 'OnShoot':
                $this->onXmlRpcEliteShoot(new JsonCallbacks\OnShoot($json));
        //var_dump($json);
                break;
            case 'OnHit':
                $this->onXmlRpcEliteHit(new JsonCallbacks\OnHit($json));
        //var_dump($json);
                break;
            case 'OnCapture':
                $this->onXmlRpcEliteCapture(new JsonCallbacks\OnCapture($json));
                break;
            case 'OnArmorEmpty':
                $this->onXmlRpcEliteArmorEmpty(new JsonCallbacks\OnArmorEmpty($json));
        //var_dump($json);
                break;
            case 'OnNearMiss':
                $this->onXmlRpcEliteNearMiss(new JsonCallbacks\OnNearMiss($json));
                break;
            case 'EndTurn':
          if ($this->MatchNumber) {
          $ClanMatchDataVariables = $this->connection->getModeScriptVariables();
                    $this->BlueMapScore = $ClanMatchDataVariables['Clan1MapPoints'];
                    $this->RedMapScore = $ClanMatchDataVariables['Clan2MapPoints'];
        }
                $this->onXmlRpcEliteEndTurn(new JsonCallbacks\EndTurn($json));
                break;
            case 'EndMatch':      
          if ($this->MatchNumber) {
          $ClanEndMatchDataVariables = $this->connection->getModeScriptVariables();
                    $this->BlueScoreMatch = $ClanEndMatchDataVariables['Clan1MatchPoints'];
                    $this->RedScoreMatch = $ClanEndMatchDataVariables['Clan2MatchPoints'];
        $qmmsb = "UPDATE `matches` SET `Matchscore_blue` = " . $this->db->quote($this->BlueScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->Debug($qmmsb);
                    $this->db->execute($qmmsb);
                 //MatchScore Red
                $qmmsr = "UPDATE `matches` SET Matchscore_red = " . $this->db->quote($this->RedScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->Debug($qmmsr);
                    $this->db->execute($qmmsr);
        }
                $this->onXmlRpcEliteEndMatch(new JsonCallbacks\EndMatch($json));
                break;
            case 'EndMap':
          if ($this->MatchNumber) {
          $ClanEndMapDataVariables = $this->connection->getModeScriptVariables();
                    $this->BlueScoreMatch = $ClanEndMapDataVariables['Clan1MatchPoints'];
                    $this->RedScoreMatch = $ClanEndMapDataVariables['Clan2MatchPoints'];
        $qmmsb = "UPDATE `matches` SET `Matchscore_blue` = " . $this->db->quote($this->BlueScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->Debug($qmmsb);
                    $this->db->execute($qmmsb);
                 //MatchScore Red
                $qmmsr = "UPDATE `matches` SET Matchscore_red = " . $this->db->quote($this->RedScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->Debug($qmmsr);
                    $this->db->execute($qmmsr);
        }
                $this->onXmlRpcEliteEndMap(new JsonCallbacks\EndMap($json));
                break;
      case 'LibXmlRpc_Scores':
        if($this->MatchNumber)
        {
          $this->BlueScoreMatch = $json[0];
          $this->RedScoreMatch = $json[1];
          $qmmsb = "UPDATE `matches` SET `Matchscore_blue` = " . $this->db->quote($this->BlueScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->Debug($qmmsb);
                    $this->db->execute($qmmsb);
                 //MatchScore Red
                $qmmsr = "UPDATE `matches` SET Matchscore_red = " . $this->db->quote($this->RedScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->Debug($qmmsr);
                    $this->db->execute($qmmsr);
        }
        break;
        }
    }

    function updateServerChallenges() {
		$mapImagestructure = './www/media/images/thumbnails/';
		if (!file_exists($mapImagestructure)) {
		$oldumask = umask(0);
		mkdir($mapImagestructure, 0777, true);
		chmod($mapImagestructure, 0777);
		umask($oldumask);
		}
        //get server challenges
        $serverChallenges = $this->storage->maps;
  
        //get database challenges
        $q = "SELECT * FROM `maps`;";
        $query = $this->db->query($q);
        $this->logger->Debug($q);

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
		
		$path = $this->mapdirectory.$data->fileName;
		$mapInfo = \ManiaLivePlugins\Shootmania\Elite\Classes\GbxReader\Map::read($path);
		//var_dump($mapInfo->thumbnail);
		if($mapInfo->thumbnail){
		imagejpeg($mapInfo->thumbnail, './www/media/images/thumbnails/'.$mapInfo->uid.'.jpg', 100);
		}
        $q = "INSERT INTO `maps` (`uid`, `name`, `author`) VALUES (" . $this->db->quote($data->uId) . "," . $this->db->quote($data->name) . "," . $this->db->quote($data->author) . ")";
        $this->logger->Debug($q);
        $this->db->query($q);
    }

    function onPlayerConnect($login, $isSpectator) {
        $player = $this->storage->getPlayerObject($login);
        $this->insertPlayer($player);
    }

    function onPlayerDisconnect($login, $reason = null) {
        if (isset($this->playerIDs[$login]))
            unset($this->playerIDs[$login]);
    }


  function insertPlayer($player) {
      $zone = explode("|", $player->path);
      if ($zone[0] == "") {
          $zone[2] = "World";
      }
      $q = "SELECT * FROM `players` WHERE `login` = " . $this->db->quote($player->login) . ";";
      $this->logger->Debug($q);
      $execute = $this->db->execute($q);

      if ($execute->recordCount() == 0) {
           $q = "INSERT INTO `players` (
            `login`,
            `nation`,
            `updatedate`
            ) VALUES (
            " . $this->db->quote($player->login) . ",
             " . $this->db->quote($zone[2]) . ",
            '" . date('Y-m-d H:i:s') . "'
            )";
          $this->db->execute($q);
          $this->playerIDs[$player->login] = $this->db->insertID();
          $this->logger->Debug($q);
          
          $qnick = "INSERT INTO `player_nicknames` ( 
          `player_id`,
          `nickname`,
          `competition_id`)
          VALUES (
          " . $this->db->quote($this->playerIDs[$player->login]) . ",
          " . $this->db->quote($player->nickName) . ",
          " . $this->db->quote($this->competition_id) . "
          )";
          $this->db->execute($qnick);
          $this->logger->debug($qnick);    
      } else {
          
          $q = "SELECT * FROM `players` WHERE `login` = " . $this->db->quote($player->login) . ";";
          $this->logger->Debug($q);
          $getplayerid = $this->db->execute($q)->fetchObject();
          $q = "SELECT * FROM `player_nicknames` WHERE `player_id` = " . $this->db->quote($getplayerid->id) . " and `competition_id` = " . $this->db->quote($this->competition_id) . ";";
          $this->logger->Debug($q);
          $executeids = $this->db->execute($q);

          if ($executeids->recordCount() == 0) {

            $qnick = "INSERT INTO `player_nicknames` ( 
            `player_id`,
            `nickname`,
            `competition_id`)
            VALUES (
            " . $this->db->quote($getplayerid->id) . ",
            " . $this->db->quote($player->nickName) . ",
            " . $this->db->quote($this->competition_id) . ");";
            $this->logger->debug($qnick);   
            $this->db->execute($qnick);

          }else{

            $qnick = "UPDATE `player_nicknames` SET `nickname` = " . $this->db->quote($player->nickName) . "
            WHERE `player_id` = " . $this->db->quote($getplayerid->id) . "
            AND `competition_id` = " . $this->db->quote($this->competition_id) . ";";
            $this->logger->debug($qnick);  
            $this->db->execute($qnick);
           }
      } 
  }

    function onXmlRpcEliteBeginWarmUp(JsonCallbacks\BeginWarmup $content) {
        if ($content->allReady === false) {
            $q = "UPDATE `match_maps`
          SET `AllReady` = '0'
          WHERE `match_id` =" . $this->db->quote($this->MatchNumber) . " and 
                                        `map_id` = " . $this->db->quote($this->getMapid()) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->Debug($q);
            $this->db->execute($q);
        } else {
            
        }
    }

    function onXmlRpcEliteEndWarmUp(JsonCallbacks\EndWarmup $content) {
        $blue = $this->connection->getTeamInfo(1);
        $red = $this->connection->getTeamInfo(2);


        
          
        if ($content->allReady === true) {
            $q = "UPDATE `match_maps`
          SET `AllReady` = '1'
          WHERE `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                        `map_id` = " . $this->db->quote($this->getMapid()) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->Debug($q);
            $this->db->execute($q);
        } else {
            
        }
    }
  
  function onXmlRpcEliteMatchStart(JsonCallbacks\BeginMatch $content) {
        $blue = $this->connection->getTeamInfo(1);
        $red = $this->connection->getTeamInfo(2);

        $MatchName = '' . $blue->name . ' vs ' . $red->name . '';
                            // set servername with clublinks....
                                                        
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
            `matchServerLogin`,
            `competition_id`
            ) VALUES (
            " . $this->db->quote($MatchName) . ",
            " . $this->db->quote($this->getTeamid($blue->name)) . ",
            " . $this->db->quote($blue->emblemUrl) . ",
            " . $this->db->quote($blue->rGB) . ",
            " . $this->db->quote($this->getTeamid($red->name)) . ",
            " . $this->db->quote($red->emblemUrl) . ",
            " . $this->db->quote($red->rGB) . ",
            '0',
            '0',
            '" . date('Y-m-d H:i:s') . "',
            " . $this->db->quote($this->storage->serverLogin) . ",
            " . $this->db->quote($this->competition_id) . "
            )";
        $this->logger->Debug($qmatch);
        $this->db->execute($qmatch);
        $this->MatchNumber = $this->db->insertID();
    }

    function onXmlRpcEliteMapStart(JsonCallbacks\BeginMap $content) {

        $mapmatch = "SELECT * FROM `match_maps` WHERE `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `map_id` = " . $this->db->quote($this->getMapid()) . "";
        $this->logger->Debug($mapmatch);
        $mapmatchexecute = $this->db->execute($mapmatch);
        if ($mapmatchexecute->recordCount() == 0) {
            $qmapmatch = "INSERT INTO `match_maps` (
            `match_id`,
            `map_id`,
            `Roundscore_blue`,
            `Roundscore_red`,
            `MapStart`,
            `matchServerLogin`
            ) VALUES (
            " . $this->db->quote($this->MatchNumber) . ",
            " . $this->db->quote($this->getMapid()) . ",
            '0',
            '0',
            '" . date('Y-m-d H:i:s') . "',
            " . $this->db->quote($this->storage->serverLogin) . "
            )";
            $this->logger->Debug($qmapmatch);
            $this->db->execute($qmapmatch);
            $this->MapNumber = $this->db->insertID();
        } else {
            
        }
    }

    //Xml RPC events

    function onXmlRpcEliteBeginTurn(JsonCallbacks\BeginTurn $content) {

        /** @var integer */
        $this->TurnNumber = $content->turnNumber;

        $blue = $this->connection->getTeamInfo(1);
        $red = $this->connection->getTeamInfo(2);
        
        $teams = array();
        $teams[1] = $blue;
        $teams[2] = $red;
  
          /* Clublinks */

          if ($this->TurnNumber == 1)
        {
            if ($blue->clubLinkUrl) {
              $this->updateClublink($blue->clubLinkUrl);
          }
          if ($red->clubLinkUrl) {
              $this->updateClublink($red->clubLinkUrl);
          }



          $qcblnk = "SELECT * FROM `clublinks` WHERE `Clublink_Name` = " . $this->db->quote($blue->name) . ";";
        $this->logger->Debug($qcblnk);
        $bluename = $this->db->execute($qcblnk)->fetchObject();

        $qcblnk = "SELECT * FROM `clublinks` WHERE `Clublink_Name` = " . $this->db->quote($red->name) . ";";
        $this->logger->Debug($qcblnk);
        $redname = $this->db->execute($qcblnk)->fetchObject();

        $MatchName = '' . $bluename->Clublink_Name_Clean . ' vs ' . $redname->Clublink_Name_Clean . '';
                            // set servername with clublinks....
                            
                            $this->connection->setServerTag('server_name', json_encode($MatchName), true);
                            
                            $this->connection->setServerName($MatchName);


        }
      
        $qmmsb = "UPDATE `matches`
  SET teamBlue = " . $this->db->quote($this->getTeamid($blue->name)) . ",
            teamBlue_emblem = " . $this->db->quote($blue->emblemUrl) . ",
            teamBlue_RGB = " . $this->db->quote($blue->rGB) . " 
            WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($qmmsb);
        $this->db->execute($qmmsb);

        $qmmsr = "UPDATE `matches`
  SET teamRed = " . $this->db->quote($this->getTeamid($red->name)) . ",
            teamRed_emblem = " . $this->db->quote($red->emblemUrl) . ",
            teamRed_RGB = " . $this->db->quote($red->rGB) . " 
            WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($qmmsr);
        $this->db->execute($qmmsr);


        foreach ($this->storage->players as $login => $player) {
            $shots_table = "SELECT * FROM `shots` WHERE `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($content->turnNumber) . " and `player_id` = " . $this->db->quote($this->getPlayerId($login)) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
            $this->logger->Debug($shots_table);
            $execute = $this->db->execute($shots_table);
            if ($execute->recordCount() == 0) {
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
          " . $this->db->quote($this->MapNumber) . ",
          " . $this->db->quote($content->turnNumber) . ",
          " .  $this->db->quote($this->getPlayerId($login)). ",
          '0',
          '0',
          '0',
          '0',
          " . $this->db->quote($this->storage->serverLogin) . "
          )";
                $this->logger->Debug($shots_insert);
                $this->db->execute($shots_insert);
            } else {
                
            }

            $playermapinfo = "SELECT * FROM `player_maps` WHERE `player_id` = " . $this->db->quote($this->getPlayerId($login)) . " AND `match_id` = " . $this->db->quote($this->MatchNumber) . " AND `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
            $this->logger->Debug($playermapinfo);

            $pmiexecute = $this->db->execute($playermapinfo);

            if ($pmiexecute->recordCount() == 0) {
                $pmi = "INSERT INTO `player_maps` (
            `match_id`,
            `player_id`,
            `match_map_id`,
            `team_id`,
            `matchServerLogin`
            ) VALUES (
                        " . $this->db->quote($this->MatchNumber) . ",
            " . $this->db->quote($this->getPlayerId($login)) . ",
            " . $this->db->quote($this->MapNumber) . ",
            " . $this->db->quote($this->getTeamid($teams[($player->teamId + 1)]->name)) . ",
            " . $this->db->quote($this->storage->serverLogin) . "
            )";
                //var_dump($pmi);
                $this->logger->Debug($pmi);
                $this->db->execute($pmi);
            }
        } //end foreach
       
        //Atk Queries and insertation        
        
        $attackingClan = $teams[$content->attackingClan];
        
        $q = "SELECT * FROM `match_details` WHERE `map_id` = " . $this->db->quote($this->getMapid()) . " and `team_id` = " . $this->db->quote($this->getTeamid($attackingClan->name)) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
        $this->logger->Debug($q);
        $execute = $this->db->execute($q);
        if ($execute->recordCount() == 0) {
            $q = "INSERT INTO `match_details` (
          `match_id`,
          `team_id`,
          `map_id`,
          `attack`,
          `defence`,
          `capture`,
          `timeOver`,
          `attackWinEliminate`,
          `defenceWinEliminate`,
          `matchServerLogin`
          ) VALUES (
          " . $this->db->quote($this->MatchNumber) . ",
          " . $this->db->quote($this->getTeamid($attackingClan->name)) . ",
          " . $this->db->quote($this->getMapid()) . ",
          '0',
          '0',
          '0',
          '0',
          '0',
          '0',
          " . $this->db->quote($this->storage->serverLogin) . "
          )";
            $this->logger->Debug($q);
            $this->db->execute($q);
        } else {
            
        }

        //DefQuery
         $defendingClan = $teams[$content->defendingClan];
        $q = "SELECT * FROM `match_details` WHERE 
                                            `map_id` = " . $this->db->quote($this->getMapid()) . "  AND 
                                            `match_id` = " . $this->db->quote($this->MatchNumber) . " AND
                                            `team_id` = " . $this->db->quote($this->getTeamid($defendingClan->name)) . " AND 
                                            `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
        $this->logger->Debug($q);
        $execute = $this->db->execute($q);

        if ($execute->recordCount() == 0) {
            $q = "INSERT INTO `match_details` (
          `match_id`,
          `team_id`,
          `map_id`,
          `attack`,
          `defence`,
          `capture`,
          `timeOver`,
          `attackWinEliminate`,
          `defenceWinEliminate`,
          `matchServerLogin`
          ) VALUES (
          " . $this->MatchNumber . ",
          " . $this->db->quote($this->getTeamid($defendingClan->name)) . ",
          " . $this->db->quote($this->getMapid()) . ",
          '0',
          '0',
          '0',
          '0',
          '0',
          '0',
          " . $this->db->quote($this->storage->serverLogin) . "
          )";
            $this->logger->Debug($q);
            $this->db->execute($q);
        } else {
            
        }

        // Players and stuff
    if ($content->attackingPlayer == NULL){
    }
    else
    {
        $AtkPlayer = $this->getPlayerId($content->attackingPlayer->login);
        $mapmatchAtk = "UPDATE `match_maps` SET 
                                            `AtkId` = " . $this->db->quote($AtkPlayer) . "
                                            WHERE 
                                            `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                            `map_id` = " . $this->db->quote($this->getMapid()) . " and 
                                            `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($mapmatchAtk);
        $this->db->execute($mapmatchAtk);

        $q = "SELECT * FROM `player_maps` WHERE `player_id` = " . $this->db->quote($AtkPlayer) . " AND 
                                                `match_id` = " . $this->db->quote($this->MatchNumber) . " AND 
                                                `match_map_id` = " . $this->db->quote($this->MapNumber) . " AND 
                                                `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($q);
        $Atker = $this->db->execute($q)->fetchObject();

        $q = "UPDATE `player_maps` 
                                     SET `atkrounds` = atkrounds + 1 
                                     WHERE 
                                    `player_id` = " . $this->db->quote($AtkPlayer) . " AND
                                    `match_map_id` = " . $this->db->quote($this->MapNumber) . " AND 
                                    `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND
                                    `match_id` = " . $this->MatchNumber . "";
        $this->logger->Debug($q);
        $this->db->execute($q);
    }
        /* Clublinks */

        if ($blue->clubLinkUrl) {
            $this->updateClublink($blue->clubLinkUrl);
        }
        if ($red->clubLinkUrl) {
            $this->updateClublink($red->clubLinkUrl);
        }
    }

    function updateClublink($url) {
          $options = array(
    CURLOPT_RETURNTRANSFER => true,     // return web page
    CURLOPT_HEADER         => false,    // don't return headers
    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
    CURLOPT_ENCODING       => "",       // handle compressed
    CURLOPT_USERAGENT      => "ShootManiaEliteStatistics", // who am i
    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
    CURLOPT_TIMEOUT        => 120,      // timeout on response
    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
  );

  $ch      = curl_init($url);
  curl_setopt_array($ch, $options);
  $content = curl_exec( $ch );
  $err     = curl_errno( $ch );
  $errmsg  = curl_error( $ch );
  $header  = curl_getinfo( $ch );
  curl_close( $ch );

  $header['errno']   = $err;
  $header['errmsg']  = $errmsg;
  $header['content'] = $content;
    $xml = simplexml_load_string($content);

        // incase the xml is malformed, bail out
        if ($xml === false)
            return;
    
    if ($xml->getName() != "club")
    return;
    
        $zone = explode("|", $xml->zone);
        if ($zone[0] == "") {
            $zone[2] = "World";
        }

        $qcblnk = "SELECT * FROM `clublinks` WHERE `Clublink_Name` = " . $this->db->quote($xml->name) . ";";
        $this->logger->Debug($qcblnk);
        $execute = $this->db->execute($qcblnk);
    $this->logger->Debug("Select Clublinks");

        if ($execute->recordCount() == 0) {
    $this->logger->Debug("Insert Clublinks");          
          $name = \ManiaLib\Utils\Formatting::stripStyles($xml->name);
          $name = preg_replace('/[^A-Za-z0-9 _\-\+\&]/','',$name);
            $qBlueClublink = "INSERT INTO `clublinks` (
          `Clublink_Name`,
          `Clublink_Name_Clean`,
          `Clublink_EmblemUrl`,
          `Clublink_ZonePath`,
          `Clublink_Primary_RGB`,
          `Clublink_Secondary_RGB`,
          `Clublink_URL`
          ) VALUES (
          " . $this->db->quote($xml->name) . ",
          " . $this->db->quote($name) . ",
          " . $this->db->quote($xml->emblem_web) . ",
          " . $this->db->quote($zone[2]) . ",
          " . $this->db->quote($xml->color['primary']) . ",
          " . $this->db->quote($xml->color['secondary']) . ",
          " . $this->db->quote($url) . "
          )";
            $this->db->execute($qBlueClublink);
            $this->logger->Debug($qBlueClublink);
        } else {
        }
    }

    function onXmlRpcEliteEndTurn(JsonCallbacks\EndTurn $content) {
  $message = 'Blue - Red: '.$this->BlueMapScore.' - '.$this->RedMapScore.'';  
  $this->logger->Console($message);
        $attackingClan = $this->connection->getTeamInfo($content->attackingClan);
        $defendingClan = $this->connection->getTeamInfo($content->defendingClan);

        $mapmatchAtk = "UPDATE `match_maps`
          SET `AtkId` = 0,
          `turnNumber` = " . $this->db->quote($content->turnNumber) . ",
          `Roundscore_blue` = " . $this->db->quote($this->BlueMapScore) . ",
          `Roundscore_red` = " . $this->db->quote($this->RedMapScore) . "
          WHERE `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                        `map_id` = " . $this->db->quote($this->getMapid()) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($mapmatchAtk);
        $this->db->execute($mapmatchAtk);

        $qatk = "UPDATE `match_details`
          SET `attack` = attack + 1 
          WHERE `team_id` = " . $this->db->quote($this->getTeamid($attackingClan->name)) . " and `map_id` = " . $this->db->quote($this->getMapid()) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($qatk);
        $this->db->execute($qatk);

        if ($content->winType == 'Capture') {
            $qcapture = "UPDATE `match_details`
          SET `capture` = capture + 1 WHERE `team_id` = " . $this->db->quote($this->getTeamid($attackingClan->name)) . " and `map_id` = " . $this->db->quote($this->getMapid()) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->Debug($qcapture);
            $this->db->execute($qcapture);
      
      $attackerId = $this->getPlayerId($content->attackingPlayer->login);
      
        $q = "UPDATE `player_maps` SET `atkSucces` =  atkSucces + 1 WHERE `player_id` = " . $this->db->quote($attackerId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
            $this->db->execute($q);
            $this->logger->Debug($q);
        }

        if ($content->winType == 'DefenseEliminated') {
            $qawe = "UPDATE `match_details`
          SET `attackWinEliminate` = attackWinEliminate + 1
          WHERE `team_id` = " . $this->db->quote($this->getTeamid($attackingClan->name)) . " and 
                                        `map_id` = " . $this->db->quote($this->getMapid()) . " and 
                                        `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->Debug($qawe);
            $this->db->execute($qawe);

            $attackerId = $this->getPlayerId($content->attackingPlayer->login);

            $q = "UPDATE `player_maps` SET `atkSucces` =  atkSucces + 1 WHERE `player_id` = " . $this->db->quote($attackerId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
            $this->db->execute($q);
            $this->logger->Debug($q);
        }

        $qdef = "UPDATE `match_details`
          SET `defence` = defence + 1
          WHERE `team_id` = " . $this->db->quote($this->getTeamid($defendingClan->name)) . " and `map_id` = " . $this->db->quote($this->getMapid()) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($qdef);
        $this->db->execute($qdef);

        if ($content->winType == 'TimeLimit') {
            $qtl = "UPDATE `match_details`
          SET `timeOver` = timeOver + 1
          WHERE `team_id` = " . $this->db->quote($this->getTeamid($defendingClan->name)) . " and `map_id` = " . $this->db->quote($this->getMapid()) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->Debug($qtl);
            $this->db->execute($qtl);

            $attackerId = $this->getPlayerId($content->attackingPlayer->login);

            $q = "UPDATE `player_maps` SET `timeOver` =  timeOver + 1 WHERE `player_id` = " . $this->db->quote($attackerId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
            $this->db->execute($q);
            $this->logger->Debug($q);
        }

        if ($content->winType == 'AttackEliminated') {
            $qde = "UPDATE `match_details`
          SET `defenceWinEliminate` = defenceWinEliminate + 1 
          WHERE `team_id` = " . $this->db->quote($this->getTeamid($defendingClan->name)) . " and `map_id` = " . $this->db->quote($this->getMapid()) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->Debug($qde);
            $this->db->execute($qde);
        }
    
    foreach($content->scoresTable as $shooters){
    $TurnHits = $shooters->turnHits;
    $playerlogin = $shooters->login;
    $total = count((array)$TurnHits);
    $PlayerIdEndTurn = $this->getPlayerId($playerlogin);
    $currentclan = $shooters->currentClan;
    if ($currentclan == $content->defendingClan){
    if ($total == (int)3){
    $qth = "UPDATE `player_maps`
    SET `elimination_3x` = elimination_3x + 1 
    WHERE `player_id` = " . $this->db->quote($PlayerIdEndTurn) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
            $this->logger->Debug($qth);
            $this->db->execute($qth);
    }
    }
    
    }
    
        /*
          Store CurrentReplays for a Turn.
         */
    }

    function onXmlRpcEliteArmorEmpty(JsonCallbacks\OnArmorEmpty $content) {
        if (!isset($this->TurnNumber))
            $this->TurnNumber = 0;

        $shooter = $content->event->shooter;
        $victim = $content->event->victim;

        if ($shooter == NULL) {
            $this->logger->Normal('Player ' . $victim->login . ' was killed in offzone.');
            return;
        }

        // Insert kill into the database
        $q = "INSERT INTO `kills` (
        `match_id`,
        `round_id`,
        `player_victim_id`,
        `player_shooter_id`,
        `map_id`,
        `matchServerLogin`
        ) VALUES (
        " . $this->db->quote($this->MatchNumber) . ",
        " . $this->db->quote($this->TurnNumber) . ",
          " . $this->db->quote($this->getPlayerId($content->event->victim->login)) . ",
          " . $this->db->quote($this->getPlayerId($content->event->shooter->login)) . ",
          " . $this->db->quote($this->getMapid()) . ",
                            " . $this->db->quote($this->storage->serverLogin) . "
        )";
        $this->logger->Debug($q);
        $this->db->execute($q);

        // update kill/death statistics
        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET `kills` = kills + 1  WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($q);
        $this->db->execute($q);

        $eliminations_table = "UPDATE `shots` SET `eliminations` = eliminations + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($eliminations_table);
        $this->db->execute($eliminations_table);

        $victimId = $this->getPlayerId($content->event->victim->login);

        $q = "UPDATE `player_maps` SET `deaths` = deaths + 1  WHERE `player_id` = " . $this->db->quote($victimId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->db->execute($q);
        $this->logger->Debug($q);

        $this->logger->Normal('[' . date('H:i:s') . '] [ShootMania] [Elite] ' . $content->event->victim->login . ' was killed by ' . $content->event->shooter->login);
    }

    function onXmlRpcEliteShoot(JsonCallbacks\OnShoot $content) {
  $message = ''.$content->event->shooter->login.' shot with '.$content->event->weaponNum.'';
  $this->logger->Normal($message);
        if (!isset($this->TurnNumber))
            $this->TurnNumber = 0;
      
        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET `shots` = shots + 1  WHERE `player_id` = " . $this->db->quote($shooterId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($q);
        $this->db->execute($q);

        $shots_table = "UPDATE `shots` SET `shots` = shots + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($shots_table);
        $this->db->execute($shots_table);
    }

    function onXmlRpcEliteHit(JsonCallbacks\OnHit $content) {
  
  $message = ''.$content->event->shooter->login.' hit '.$content->event->victim->login.' with '.$content->event->weaponNum.'';
  $this->logger->Normal($message);
        if (!isset($this->TurnNumber))
            $this->TurnNumber = 0;


// shooter info
        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET `hits` = hits + 1 WHERE `player_id` = " . $this->db->quote($shooterId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($q);
        $this->db->execute($q);

        $hits_table = "UPDATE `shots` SET `hits` = hits + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($hits_table);
        $this->db->execute($hits_table);
// victim info

        $victimId = $this->getPlayerId($content->event->victim->login);

        $q1 = "UPDATE `player_maps` SET `counterhits` = counterhits + 1 WHERE `player_id` = " . $this->db->quote($victimId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($q1);
        $this->db->execute($q1);

        $counterhits_table = "UPDATE `shots` SET `counterhits` = counterhits + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($victimId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($counterhits_table);
        $this->db->execute($counterhits_table);

// hitdistance
        $qhitdist = "INSERT INTO `hits` (
          `match_map_id`,
          `round_id`,
          `map_id`,
          `HitDist`,
          `shooter_player_id`,
          `victim_player_id`,
          `weaponid`,
          `weaponname`,
          `matchServerLogin`
          ) VALUES (
          " . $this->db->quote($this->MapNumber) . ",
          " . $this->db->quote($this->TurnNumber) . ",
          " . $this->db->quote($this->getMapid()) . ",
          " . $this->db->quote($content->event->hitDist) . ",
          " . $this->db->quote($this->getPlayerId($content->event->shooter->login)) . ",
          " . $this->db->quote($this->getPlayerId($content->event->victim->login)) . ",
          " . $this->db->quote($content->event->weaponNum) . ",
          " . $this->db->quote($this->getWeaponName($content->event->weaponNum)) . ",
          " . $this->db->quote($this->storage->serverLogin) . "
          )";
        $this->logger->Debug($qhitdist);
        $this->db->execute($qhitdist);
    }

    function onXmlRpcEliteCapture(JsonCallbacks\OnCapture $content) {
  $message = ''.$content->event->player->login.' captured the pole';
  $this->logger->Normal($message);
        $map = $this->storage->currentMap;

        $qCap = "INSERT INTO `captures` (
        `match_id`,
        `round_id`,
        `player_id`,
        `map_id`,
        `matchServerLogin`
                                ) VALUES (
                                " . $this->db->quote($this->MatchNumber) . ",
                " . $this->db->quote($this->TurnNumber) . ",
                                " . $this->db->quote($this->getPlayerId($content->event->player->login)) . ",
                                " . $this->db->quote($this->getMapid()) . ",
        " . $this->db->quote($this->storage->serverLogin) . "
        )";
        $this->logger->Debug($qCap);
        $this->db->execute($qCap);

        // update capture statistics
        $playerId = $this->getPlayerId($content->event->player->login);

        $q = "UPDATE `player_maps` SET captures = captures + 1 
                                   WHERE `player_id` = " . $this->db->quote($playerId) . " and 
                                         `match_map_id` = " . $this->db->quote($this->MapNumber) . " and 
                                         `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and 
                                         `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($q);
        $this->db->execute($q);
    }

    function onXmlRpcEliteNearMiss(JsonCallbacks\OnNearMiss $content) {
  $message = ''.$content->event->shooter->login.' did a nearmissdist of '.$content->event->missDist.' cm';
  $this->logger->Normal($message);
        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET nearmisses = nearmisses + 1 
                                   WHERE `player_id` = " . $this->db->quote($shooterId) . " and 
                                         `match_map_id` = " . $this->db->quote($this->MapNumber) . " and 
                                         `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and 
                                         `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($q);
        $this->db->execute($q);

        $qnearmiss = "INSERT INTO `nearmisses` (
          `match_map_id`,
          `round_id`,
          `map_id`,
          `nearMissDist`,
          `player_id`,
          `weaponid`,
          `weaponname`,
          `matchServerLogin`
          ) VALUES (
          " . $this->db->quote($this->MapNumber) . ",
          " . $this->db->quote($this->TurnNumber) . ",
          " . $this->db->quote($this->getMapid()) . ",
          " . $this->db->quote($content->event->missDist) . ",
          " . $this->db->quote($this->getPlayerId($content->event->shooter->login)) . ",
          " . $this->db->quote($content->event->weaponNum) . ",
          " . $this->db->quote($this->getWeaponName($content->event->weaponNum)) . ",
          " . $this->db->quote($this->storage->serverLogin) . "
          )";
        $this->logger->Debug($qnearmiss);
        $this->db->execute($qnearmiss);
    }

    function onXmlRpcEliteEndMap(JsonCallbacks\EndMap $content) {
    $message = 'Blue: '.$content->clan1MapScore.' - Red: '.$content->clan2MapScore.'';
  $this->logger->Normal($message);
        $querymapEnd = "UPDATE `match_maps`
  SET `MapEnd` = '" . date('Y-m-d H:i:s') . "',
   `TurnNumber` = " . $this->db->quote($this->TurnNumber) . ",
   `AllReady` = '0'
   where `match_id` = " . $this->db->quote($this->MatchNumber) . " and `map_id` = " . $this->db->quote($this->getMapid()) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->Debug($querymapEnd);
        $this->db->execute($querymapEnd);
    }

    function onXmlRpcEliteEndMatch(JsonCallbacks\EndMatch $content) {
        //$MapWin = $this->connection->getModeScriptSettings();
        //print_r("Nb Map to win: ") . var_dump($MapWin['S_MapWin']);

        $queryMapWinSettingsEnd = "UPDATE `matches` SET 
                                                    `MatchEnd` = '" . date('Y-m-d H:i:s') . "'
                                                     WHERE
                                                    `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and 
                                                    `id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($queryMapWinSettingsEnd);
        $this->db->execute($queryMapWinSettingsEnd);
          $dataDir = $this->connection->gameDataDirectory();
          $dataDir = str_replace('\\', '/', $dataDir);
          $file = $this->connection->getServername();
          $name = \ManiaLib\Utils\Formatting::stripStyles($file);
		  $challengeFile = $dataDir . "Replays/" . $name."/";
		  
		$mapReplaysStructure = $challengeFile;
		if (!file_exists($mapReplaysStructure)) {
		$oldumask = umask(0);
		mkdir($mapReplaysStructure, 0777, true);
		chmod($mapReplaysStructure, 0777);
		umask($oldumask);
		}

          $sourcefolder = "$challengeFile"; // Default: "./" 
          $zipfilename  = $dataDir. "ToUpload/" . $this->competition_id."/".$name."_".date('YmdHi').".zip"; // Default: "myarchive.zip"
          $zipfilename2  = $name."_".date('YmdHi').".zip"; // Default: "myarchive.zip"
          $timeout      = 5000; // Default: 5000

          // instantate an iterator (before creating the zip archive, just
          // in case the zip file is created inside the source folder)
          // and traverse the directory to get the file list.
          //$dirlist = new \RecursiveDirectoryIterator($sourcefolder);
         // $filelist = new \RecursiveIteratorIterator($dirlist);
          $filelist = new \RecursiveIteratorIterator(
              new \RecursiveDirectoryIterator($sourcefolder),
              \RecursiveIteratorIterator::LEAVES_ONLY
          );

          // set script timeout value 
          //ini_set('max_execution_time', $timeout);

          // instantate object
          $zip = new \ZipArchive();

          // create and open the archive 
          if ($zip->open("$zipfilename", \ZipArchive::CREATE) !== TRUE) {
              die ("Could not open archive");
          }

          // add each file in the file list to the archive
          foreach ($filelist as $key=>$value) {
              $new_filename = substr($key,strrpos($key,'/') + 1);
              $zip->addFile(realpath($key), $new_filename) or die ("ERROR: Could not add file: $key");
          }

          // close the archive
          $zip->close();
          echo "Archive ". $zipfilename2 . " created successfully.";
          
          $challengeFile = $dataDir . "Replays/" . $name;
          //Rename folder
          $newfolder = "$challengeFile"."_".date('YmdHi');
          rename($challengeFile,$newfolder);

        $queryMapWinSettingsEnd = "UPDATE `matches` SET 
                                  `Replay` = '" . $zipfilename2 . "'
                                   WHERE
                                  `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and 
                                  `id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->Debug($queryMapWinSettingsEnd);
        $this->db->execute($queryMapWinSettingsEnd);


    // set server back to old value.
		   $data = $this->connection->getServerTags();
	   if ($data[0]['Name'] == "server_name"){
	    $server_name_value = $data[0]['Value'];
        $this->connection->setServerName($server_name_value);
	   }
    }
  
  /*
  Self Helpers of This Fine Plugin
  */
  
  function getMapid(){
  $q = "SELECT id FROM `maps` WHERE `uid` = " . $this->db->quote($this->storage->currentMap->uId) . "";
            $this->logger->Debug($q);
            return $this->db->execute($q)->fetchObject()->id;
  }
  
  function getTeamid($teamname){
  $q = "SELECT id FROM `clublinks` WHERE `Clublink_Name` = " . $this->db->quote($teamname) . "";
            $this->logger->Debug($q);
            return $this->db->execute($q)->fetchObject()->id;
  }
  
      /** get cached value of database player id */
    function getPlayerId($login) {
        if (isset($this->playerIDs[$login])) {
            return $this->playerIDs[$login];
        } else {
            $q = "SELECT id FROM `players` WHERE `login` = " . $this->db->quote($login) . "";
            $this->logger->Debug($q);
            return $this->db->execute($q)->fetchObject()->id;
        }
    }
  
  
    protected function getWeaponName($num) {
        switch ($num) {
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