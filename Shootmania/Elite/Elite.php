<?php

/**
  Name: Willem 'W1lla' van den Munckhof
  Date: Unknown but before ESWC
  Project Name: eXpansion project www.exp-tm.team.com

  What to do:

 * */
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

class Elite extends \ManiaLive\PluginHandler\Plugin {

    /** @var integer */
    protected $MatchNumber = false;

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

    /** @var Log */
    private $logger;

    /** @var $playerIDs[$login] = number */
    private $playerIDs = array();

    function onInit() {
        $this->setVersion('0.0.1');
    }

    function onLoad() {
        $this->logger = new Log($this->storage->serverLogin);

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
  `round_id` int(3) NOT NULL,
  `player_login` varchar(60) NOT NULL,
  `map_uid` varchar(60) NOT NULL,
  `matchServerLogin` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`)
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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			$this->db->execute($q);
		}
		
		if(!$this->db->tableExists('kills')) {
			$q = "CREATE TABLE IF NOT EXISTS `kills` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `match_id` mediumint(9) NOT NULL DEFAULT '0',
  `round_id` int(3) NOT NULL,
  `player_victim` varchar(60) NOT NULL,
  `player_shooter` varchar(60) NOT NULL,
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
  `round_id` int(3) NOT NULL,
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
  `round_id` int(3) NOT NULL,
  `map_uid` varchar(60) NOT NULL,
  `HitDist` float default '0',
  `shooter_player_login` varchar(60) NOT NULL,
  `victim_player_login` varchar(60) NOT NULL,
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

        $this->connection->setModeScriptSettings(array('S_RestartMatchOnTeamChange' => true)); //Debug Way...
        //$this->connection->setModeScriptSettings(array('S_Mode' => 1)); //Debug Way...

        $this->connection->setCallVoteRatiosEx(false, array(
            new \DedicatedApi\Structures\VoteRatio('SetModeScriptSettingsAndCommands', -1.)
        ));

        Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Core v' . $this->getVersion());
        $this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [Shootmania] Elite Stats$fa0!');

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
    }

    /* Chat messages */

    function WarmUp_Extend($login, $amount) {
        $vote = new \DedicatedApi\Structures\Vote();
        $vote->cmdName = 'Echo';
        $vote->cmdParam = array('Set WarmUp Extend', 'map_warmup_extend');
        $this->connection->callVote($vote, 0.5, 0, 1);
    }

    function WarmUp_Stop($login) {
        $vote = new \DedicatedApi\Structures\Vote();
        $vote->cmdName = 'Echo';
        $vote->cmdParam = array('Set Warmup Stop', 'map_warmup_end');
        $this->connection->callVote($vote, 0.5, 0, 1);
    }

    function pause($login) {
        $vote = new \DedicatedApi\Structures\Vote();
        $vote->cmdName = 'Echo';
        $vote->cmdParam = array('Set Map to Pause', 'map_pause');
        $this->connection->callVote($vote, 0.5, 0, 1);
    }

    /* Callbacks and Methods  */

    public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {
        if ($stateName == "VotePassed") {
            if ($cmdName == "NextMap") {
                $queryNextMap = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = " . $this->db->quote($this->MatchNumber) . " and `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
                $this->db->execute($queryNextMap);
                $this->logger->write($queryNextMap);
            }
            if ($cmdName == "SetModeScriptSettingsAndCommands") {
                $querySMSSAC = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = " . $this->db->quote($this->MatchNumber) . " and `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
                $this->db->execute($querySMSSAC);
                $this->logger->write($querySMSSAC);
            }
            if ($cmdName == "JumpToMapIndex") {
                $queryJTMI = "UPDATE `match_maps`
	SET `NextMap` = '1'
	 where `match_id` = " . $this->db->quote($this->MatchNumber) . " and `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
                $this->db->execute($queryJTMI);
                $this->logger->write($queryJTMI);
            }
        }
    }

    function onEcho($internal, $public) {
        switch ($internal) {
            case "map_pause":
                $getdata = $this->connection->sendModeScriptCommands(array("Command_ForceWarmUp" => true));
                break;
            case "map_warmup_extend":
                try {
                    Validation::int(6000, 0);
                } catch (\Exception $e) {
                    return;
                }
                $this->connection->triggerModeScriptEvent('WarmUp_Extend', '6000');
                break;
            case "map_warmup_end":
                $this->connection->triggerModeScriptEvent('WarmUp_Stop', '');
                break;
        }
    }

    function getServerCurrentMatch($serverLogin) {
        return $this->db->execute(
                        'SELECT id FROM matches ' .
                        'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = ' . $this->db->quote($serverLogin) .
                        'order by id desc')->fetchSingleValue();
    }

    function updateMatchState($matchId) {
        $state = date('Y-m-d H:i:s');
        $matches_update = "UPDATE matches SET `MatchEnd` = '" . $state . "' WHERE id = " . intval($matchId) . "";
        $this->db->execute($matches_update);
        $match_maps_update = "UPDATE match_maps SET `MapEnd` = '" . $state . "' WHERE match_id = " . intval($matchId) . "";
        $this->db->execute($match_maps_update);
    }

    public function onModeScriptCallback($event, $json) {
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
                break;
            case 'OnHit':
                $this->onXmlRpcEliteHit(new JsonCallbacks\OnHit($json));
                break;
            case 'OnCapture':
                $this->onXmlRpcEliteCapture(new JsonCallbacks\OnCapture($json));
                break;
            case 'OnArmorEmpty':
                $this->onXmlRpcEliteArmorEmpty(new JsonCallbacks\OnArmorEmpty($json));
                break;
            case 'OnNearMiss':
                $this->onXmlRpcEliteNearMiss(new JsonCallbacks\OnNearMiss($json));
                break;
            case 'EndTurn':
                $this->onXmlRpcEliteEndTurn(new JsonCallbacks\EndTurn($json));
                break;
            case 'EndMatch':
                $this->onXmlRpcEliteEndMatch(new JsonCallbacks\EndMatch($json));
                break;
            case 'EndMap':
                $this->onXmlRpcEliteEndMap(new JsonCallbacks\EndMap($json));
                break;
            case 'LibXmlRpc_Scores':
                if ($this->MatchNumber) {
                    $this->BlueScoreMatch = $json[0];
                    $this->RedScoreMatch = $json[1];
                    $this->BlueMapScore = $json[2];
                    $this->RedMapScore = $json[3];
                    //MatchScore Blue
                    $qmmsb = "UPDATE `matches` SET `Matchscore_blue` = " . $this->db->quote($this->BlueScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->write($qmmsb);
                    $this->db->execute($qmmsb);
                    //MatchScore Red
                    $qmmsr = "UPDATE `matches` SET Matchscore_red = " . $this->db->quote($this->RedScoreMatch) . " WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
                    $this->logger->write($qmmsr);
                    $this->db->execute($qmmsr);
                }
                break;
        }
    }

    function updateServerChallenges() {
        //get server challenges
        $serverChallenges = $this->storage->maps;

        //get database challenges
        $q = "SELECT * FROM `maps`;";
        $query = $this->db->query($q);
        $this->logger->write($q);

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
        $q = "INSERT INTO `maps` (`uid`, `name`, `author` ) VALUES (" . $this->db->quote($data->uId) . "," . $this->db->quote($data->name) . "," . $this->db->quote($data->author) . ")";
        $this->logger->write($q);
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

    /** get cached value of database player id */
    function getPlayerId($login) {
        if (isset($this->playerIDs[$login])) {
            return $this->playerIDs[$login];
        } else {
            $q = "SELECT id FROM `players` WHERE `login` = " . $this->db->quote($login) . "";
            $this->logger->write($q);
            return $this->db->execute($q)->fetchObject()->id;
        }
    }

    function insertPlayer($player) {
        $zone = explode("|", $player->path);
        if ($zone[0] == "") {
            $zone[2] = "World";
        }
        $q = "SELECT * FROM `players` WHERE `login` = " . $this->db->quote($player->login) . ";";
        $this->logger->write($q);
        $execute = $this->db->execute($q);

        if ($execute->recordCount() == 0) {
            $q = "INSERT INTO `players` (
					`login`,
					`nickname`,
					`nation`,
					`updatedate`
				  ) VALUES (
					" . $this->db->quote($player->login) . ",
					" . $this->db->quote($player->nickName) . ",
					" . $this->db->quote($zone[2]) . ",
					'" . date('Y-m-d H:i:s') . "'
				  )";
            $this->db->execute($q);
            $this->playerIDs[$player->login] = $this->db->insertID();
            $this->logger->write($q);
        } else {
            $q = "UPDATE `players` SET `nickname` = " . $this->db->quote($player->nickName) . ",
				       `nation` = " . $this->db->quote($zone[2]) . ",
				       `updatedate` = '" . date('Y-m-d H:i:s') . "'
                                   WHERE `login` = " . $this->db->quote($player->login) . "";
            $this->db->execute($q);
            $this->logger->write($q);
            $this->playerIDs[$player->login] = $execute->fetchObject()->id;
        }
    }

    function onXmlRpcEliteBeginWarmUp(JsonCallbacks\BeginWarmup $content) {
        if ($content->allReady === false) {
            $q = "UPDATE `match_maps`
				  SET `AllReady` = '0'
				  WHERE `match_id` =" . $this->db->quote($this->MatchNumber) . " and 
                                        `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->write($q);
            $this->db->execute($q);
        } else {
            
        }
    }

    function onXmlRpcEliteEndWarmUp(JsonCallbacks\EndWarmup $content) {
        if ($content->allReady === true) {
            $q = "UPDATE `match_maps`
				  SET `AllReady` = '1'
				  WHERE `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                        `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->write($q);
            $this->db->execute($q);
        } else {
            
        }
    }
	
	function onXmlRpcEliteMatchStart(JsonCallbacks\BeginMatch $content) {
        $blue = $this->connection->getTeamInfo(1);
        $red = $this->connection->getTeamInfo(2);

        $MatchName = '' . $blue->name . ' vs ' . $red->name . '';

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
						" . $this->db->quote($MatchName) . ",
						" . $this->db->quote($blue->name) . ",
						" . $this->db->quote($blue->emblemUrl) . ",
						" . $this->db->quote($blue->rGB) . ",
						" . $this->db->quote($red->name) . ",
						" . $this->db->quote($red->emblemUrl) . ",
						" . $this->db->quote($red->rGB) . ",
						'0',
						'0',
						'" . date('Y-m-d H:i:s') . "',
						" . $this->db->quote($this->storage->serverLogin) . "
					  )";
        $this->logger->write($qmatch);
        $this->db->execute($qmatch);
        $this->MatchNumber = $this->db->insertID();
    }

    function onXmlRpcEliteMapStart(JsonCallbacks\BeginMap $content) {

        $map = $this->connection->getCurrentMapInfo();
        $mapmatch = "SELECT * FROM `match_maps` WHERE `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `map_uid` = " . $this->db->quote($map->uId) . "";
        $this->logger->write($mapmatch);
        $mapmatchexecute = $this->db->execute($mapmatch);
        if ($mapmatchexecute->recordCount() == 0) {
            $qmapmatch = "INSERT INTO `match_maps` (
						`match_id`,
						`map_uid`,
						`Roundscore_blue`,
						`Roundscore_red`,
						`MapStart`,
						`matchServerLogin`
					  ) VALUES (
						" . $this->MatchNumber . ",
						" . $this->db->quote($map->uId) . ",
						'0',
						'0',
						'" . date('Y-m-d H:i:s') . "',
						" . $this->db->quote($this->storage->serverLogin) . "
					  )";
            $this->logger->write($qmapmatch);
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
        
        $qmmsb = "UPDATE `matches`
	SET teamBlue = " . $this->db->quote($blue->name) . ",
            teamBlue_emblem = " . $this->db->quote($blue->emblemUrl) . ",
            teamBlue_RGB = " . $this->db->quote($blue->rGB) . " 
            WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($qmmsb);
        $this->db->execute($qmmsb);

        $qmmsr = "UPDATE `matches`
	SET teamRed = " . $this->db->quote($red->name) . ",
            teamRed_emblem = " . $this->db->quote($red->emblemUrl) . ",
            teamRed_RGB = " . $this->db->quote($red->rGB) . " 
            WHERE `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND `id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($qmmsr);
        $this->db->execute($qmmsr);


        foreach ($this->storage->players as $login => $player) {
            $shots_table = "SELECT * FROM `shots` WHERE `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($content->turnNumber) . " and `player_id` = " . $this->db->quote($this->getPlayerId($login)) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
            $this->logger->write($shots_table);
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
					" . $this->MapNumber . ",
					" . $content->turnNumber . ",
					" .  $this->getPlayerId($login). ",
					'0',
					'0',
					'0',
					'0',
					" . $this->db->quote($this->storage->serverLogin) . "
				  )";
                $this->logger->write($shots_insert);
                $this->db->execute($shots_insert);
            } else {
                
            }

            $playermapinfo = "SELECT * FROM `player_maps` WHERE `player_id` = " . $this->db->quote($this->getPlayerId($login)) . " AND `match_id` = " . $this->db->quote($this->MatchNumber) . " AND `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
            $this->logger->write($playermapinfo);

            $pmiexecute = $this->db->execute($playermapinfo);

            if ($pmiexecute->recordCount() == 0) {
                $pmi = "INSERT INTO `player_maps` (
						`match_id`,
						`player_id`,
						`match_map_id`,
						`team_id`,
						`matchServerLogin`
					  ) VALUES (
                                                 " . $this->MatchNumber . ",
						'" . $this->getPlayerId($login) . "',
						'" . $this->MapNumber . "',
						'" . $teams[($player->teamId + 1)]->name . "',
						" . $this->db->quote($this->storage->serverLogin) . "
					  )";
                //var_dump($pmi);
                $this->logger->write($pmi);
                $this->db->execute($pmi);
            }
        } //end foreach
       
        //Atk Queries and insertation        
        
        $attackingClan = $teams[$content->attackingClan];
        
        $q = "SELECT * FROM `match_details` WHERE `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and `team_id` = " . $this->db->quote($attackingClan->name) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
        $this->logger->write($q);
        $execute = $this->db->execute($q);
        if ($execute->recordCount() == 0) {
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
					" . $this->MatchNumber . ",
					" . $this->db->quote($attackingClan->name) . ",
					" . $this->db->quote($this->storage->currentMap->uId) . ",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					" . $this->db->quote($this->storage->serverLogin) . "
				  )";
            $this->logger->write($q);
            $this->db->execute($q);
        } else {
            
        }

        //DefQuery
         $defendingClan = $teams[$content->defendingClan];
        $q = "SELECT * FROM `match_details` WHERE 
                                            `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . "  AND 
                                            `match_id` = " . $this->db->quote($this->MatchNumber) . " AND
                                            `team_id` = " . $this->db->quote($defendingClan->name) . " AND 
                                            `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . ";";
        $this->logger->write($q);
        $execute = $this->db->execute($q);

        if ($execute->recordCount() == 0) {
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
					" . $this->MatchNumber . ",
					" . $this->db->quote($defendingClan->name) . ",
					" . $this->db->quote($this->storage->currentMap->uId) . ",
					'0',
					'0',
					'0',
					'0',
					'0',
					'0',
					" . $this->db->quote($this->storage->serverLogin) . "
				  )";
            $this->logger->write($q);
            $this->db->execute($q);
        } else {
            
        }

        // Players and stuff       
        $AtkPlayer = $this->getPlayerId($content->attackingPlayer->login);

        $mapmatchAtk = "UPDATE `match_maps` SET 
                                            `AtkId` = " . $this->db->quote($AtkPlayer) . "
                                            WHERE 
                                            `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                            `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and 
                                            `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($mapmatchAtk);
        $this->db->execute($mapmatchAtk);

        $q = "SELECT * FROM `player_maps` WHERE `player_id` = " . $this->db->quote($AtkPlayer) . " AND 
                                                `match_id` = " . $this->db->quote($this->MatchNumber) . " AND 
                                                `match_map_id` = " . $this->db->quote($this->MapNumber) . " AND 
                                                `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($q);
        $Atker = $this->db->execute($q)->fetchObject();

        $q = "UPDATE `player_maps` 
                                     SET `atkrounds` = " . $this->db->quote($Atker->atkrounds + 1) . " 
                                     WHERE 
                                    `player_id` = " . $this->db->quote($AtkPlayer) . " AND
                                    `match_map_id` = " . $this->db->quote($this->MapNumber) . " AND 
                                    `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " AND
                                    `match_id` = " . $this->MatchNumber . "";
        $this->logger->write($q);
        $this->db->execute($q);

        /* Clublinks */

        if ($blue->clubLinkUrl) {
            $this->updateClublink($blue->clubLinkUrl);
        }
        if ($red->clubLinkUrl) {
            $this->updateClublink($blue->clubLinkUrl);
        }
    }

    function updateClublink($url) {
        $context = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
        $data = file_get_contents($url, true, $context);
        $xml = simplexml_load_string($data);

        // incase the xml is malformed, bail out
        if ($xml === false)
            return;

        $zone = explode("|", $xml->zone);
        if ($zone[0] == "") {
            $zone[2] = "World";
        }

        $qcblnk = "SELECT * FROM `clublinks` WHERE `Clublink_Name` = " . $this->db->quote($xml->name) . ";";
        $this->logger->write($qcblnk);
        $execute = $this->db->execute($qcblnk);

        if ($execute->recordCount() == 0) {
            $qBlueClublink = "INSERT INTO `clublinks` (
					`Clublink_Name`,
					`Clublink_EmblemUrl`,
					`Clublink_ZonePath`,
					`Clublink_Primary_RGB`,
					`Clublink_Secondary_RGB`
				  ) VALUES (
					" . $this->db->quote($xml->name) . ",
					" . $this->db->quote($xml->emblem_web) . ",
					" . $this->db->quote($zone[2]) . ",
					" . $this->db->quote($xml->color['primary']) . ",
					" . $this->db->quote($xml->color['secondary']) . "
				  )";
            $this->db->execute($qBlueClublink);
            $this->logger->write($qBlueClublink);
        } else {
            /** @todo this doesn't make sense... please do review */
            /*
              $qBlueClublink = "UPDATE `clublinks`
              SET `Clublink_Name` = " . $this->db->quote($name) . "
              WHERE `Clublink_Name` = " . $this->db->quote($player->login) . "";
              $this->db->execute($qBlueClublink);
              $this->logger->write($qBlueClublink);
             */
        }
    }

    function onXmlRpcEliteEndTurn(JsonCallbacks\EndTurn $content) {
        $map = $this->storage->currentMap;
        $attackingClan = $this->connection->getTeamInfo($content->attackingClan);
        $defendingClan = $this->connection->getTeamInfo($content->defendingClan);

        $mapmatchAtk = "UPDATE `match_maps`
				  SET `AtkId` = 0,
				  `turnNumber` = " . $this->db->quote($content->turnNumber) . ",
				  `Roundscore_blue` = " . $this->db->quote($this->BlueScoreMatch) . ",
				  `Roundscore_red` = " . $this->db->quote($this->BlueScoreMatch) . "
				  WHERE `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                        `map_uid` = " . $this->db->quote($map->uId) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($mapmatchAtk);
        $this->db->execute($mapmatchAtk);

        $qatk = "UPDATE `match_details`
				  SET `attack` = attack + 1 
				  WHERE `team_id` = " . $this->db->quote($attackingClan->name) . " and `map_uid` = " . $this->db->quote($map->uId) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($qatk);
        $this->db->execute($qatk);

        if ($content->winType == 'Capture') {
            $qcapture = "UPDATE `match_details`
				  SET `capture` = capture + 1 WHERE `team_id` = " . $this->db->quote($attackingClan->name) . " and `map_uid` = " . $this->db->quote($map->uId) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->write($qcapture);
            $this->db->execute($qcapture);
        }

        if ($content->winType == 'DefenseEliminated') {
            $qawe = "UPDATE `match_details`
				  SET `attackWinEliminate` = attackWinEliminate + 1
				  WHERE `team_id` = " . $this->db->quote($attackingClan->name) . " and 
                                        `map_uid` = " . $this->db->quote($map->uId) . " and 
                                        `match_id` = " . $this->db->quote($this->MatchNumber) . " and 
                                        `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->write($qawe);
            $this->db->execute($qawe);

            $attackerId = $this->getPlayerId($content->attackingPlayer->login);

            $q = "UPDATE `player_maps` SET `atkSucces` =  atkSucces + 1 WHERE `player_id` = " . $this->db->quote($attackerId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
            $this->db->execute($q);
            $this->logger->write($q);
        }

        $qdef = "UPDATE `match_details`
				  SET `defence` = defence + 1
				  WHERE `team_id` = " . $this->db->quote($defendingClan->name) . " and `map_uid` = " . $this->db->quote($map->uId) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($qdef);
        $this->db->execute($qdef);

        if ($content->winType == 'TimeLimit') {
            $qtl = "UPDATE `match_details`
				  SET `timeOver` = timeOver + 1
				  WHERE `team_id` = " . $this->db->quote($defendingClan->name) . " and `map_uid` = " . $this->db->quote($map->uId) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->write($qtl);
            $this->db->execute($qtl);
        }

        if ($content->winType == 'AttackEliminated') {
            $qde = "UPDATE `match_details`
				  SET `defenceWinEliminate` = defenceWinEliminate + 1 
				  WHERE `team_id` = " . $this->db->quote($defendingClan->name) . " and `map_uid` = " . $this->db->quote($map->uId) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
            $this->logger->write($qde);
            $this->db->execute($qde);
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
            $this->logger->write('Player ' . $victim->login . ' was killed in offzone.');
            return;
        }

        // Insert kill into the database
        $q = "INSERT INTO `kills` (
				`match_id`,
				`round_id`,
				`player_victim`,
				`player_shooter`,
				`map_uid`,
				`matchServerLogin`
			  ) VALUES (
			  " . $this->MatchNumber . ",
			  " . $this->TurnNumber . ",
			    " . $this->db->quote($content->event->victim->login) . ",
			    " . $this->db->quote($content->event->shooter->login) . ",
			    " . $this->db->quote($this->storage->currentMap->uId) . ",
                            " . $this->db->quote($this->storage->serverLogin) . "
			  )";
        $this->logger->write($q);
        $this->db->execute($q);

        // update kill/death statistics
        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET `kills` = kills + 1  WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($q);
        $this->db->execute($q);

        $eliminations_table = "UPDATE `shots` SET `eliminations` = eliminations + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($eliminations_table);
        $this->db->execute($eliminations_table);

        $victimId = $this->getPlayerId($content->event->victim->login);

        $q = "UPDATE `player_maps` SET `deaths` = deaths + 1  WHERE `player_id` = " . $this->db->quote($victimId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->db->execute($q);
        $this->logger->write($q);

        $this->logger->write('[' . date('H:i:s') . '] [ShootMania] [Elite] ' . $content->event->victim->login . ' was killed by ' . $content->event->shooter->login);
    }

    function onXmlRpcEliteShoot(JsonCallbacks\OnShoot $content) {
        if (!isset($this->TurnNumber))
            $this->TurnNumber = 0;
			
        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET `shots` = shots + 1  WHERE `player_id` = " . $this->db->quote($shooterId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->MatchNumber . "";
        $this->logger->write($q);
        $this->db->execute($q);

        $shots_table = "UPDATE `shots` SET `shots` = shots + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($shots_table);
        $this->db->execute($shots_table);
    }

    function onXmlRpcEliteHit(JsonCallbacks\OnHit $content) {
        if (!isset($this->TurnNumber))
            $this->TurnNumber = 0;

        $map = $this->storage->currentMap;

// shooter info
        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET `hits` = hits + 1 WHERE `player_id` = " . $this->db->quote($shooterId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($q);
        $this->db->execute($q);

        $hits_table = "UPDATE `shots` SET `hits` = hits + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($shooterId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($hits_table);
        $this->db->execute($hits_table);
// victim info

        $victimId = $this->getPlayerId($content->event->victim->login);

        $q1 = "UPDATE `player_maps` SET `counterhits` = counterhits + 1 WHERE `player_id` = " . $this->db->quote($victimId) . "  and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($q1);
        $this->db->execute($q1);

        $counterhits_table = "UPDATE `shots` SET `counterhits` = counterhits + 1, `weapon_id` = " . $this->db->quote($content->event->weaponNum) . " WHERE `player_id` = " . $this->db->quote($victimId) . " and `match_map_id` = " . $this->db->quote($this->MapNumber) . " and `round_id` = " . $this->db->quote($this->TurnNumber) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($counterhits_table);
        $this->db->execute($counterhits_table);

// hitdistance
        $qhitdist = "INSERT INTO `hits` (
					`match_map_id`,
					`round_id`,
					`map_uid`,
					`HitDist`,
					`shooter_player_login`,
					`victim_player_login`,
					`weaponid`,
					`weaponname`,
					`matchServerLogin`
				  ) VALUES (
					" . $this->MapNumber . ",
					" . $this->TurnNumber . ",
					'" . $map->uId . "',
					'" . $content->event->hitDist . "',
					'" . $content->event->shooter->login . "',
					'" . $content->event->victim->login . "',
					'" . $content->event->weaponNum . "',
					'" . $this->getWeaponName($content->event->weaponNum) . "',
					" . $this->db->quote($this->storage->serverLogin) . "
				  )";
        $this->logger->write($qhitdist);
        $this->db->execute($qhitdist);
    }

    function onXmlRpcEliteCapture(JsonCallbacks\OnCapture $content) {
        $map = $this->storage->currentMap;

        $qCap = "INSERT INTO `captures` (
				`match_id`,
				`round_id`,
				`player_login`,
				`map_uid`,
				`matchServerLogin`
                                ) VALUES (
                                " . $this->MatchNumber . ",
								" . $this->TurnNumber . ",
                                '" . $content->event->player->login . "',
                                '" . $map->uId . "',
				" . $this->db->quote($this->storage->serverLogin) . "
			  )";
        $this->logger->write($qCap);
        $this->db->execute($qCap);

        // update capture statistics
        $playerId = $this->getPlayerId($content->event->player->login);

        $q = "UPDATE `player_maps` SET captures = captures + 1 
                                   WHERE `player_id` = " . $this->db->quote($playerId) . " and 
                                         `match_map_id` = " . $this->db->quote($this->MapNumber) . " and 
                                         `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and 
                                         `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($q);
        $this->db->execute($q);
    }

    function onXmlRpcEliteNearMiss(JsonCallbacks\OnNearMiss $content) {

        $shooterId = $this->getPlayerId($content->event->shooter->login);

        $q = "UPDATE `player_maps` SET nearmisses = nearmisses + 1 
                                   WHERE `player_id` = " . $this->db->quote($shooterId) . " and 
                                         `match_map_id` = " . $this->db->quote($this->MapNumber) . " and 
                                         `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and 
                                         `match_id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($q);
        $this->db->execute($q);

        $qnearmiss = "INSERT INTO `nearmisses` (
					`match_map_id`,
					`round_id`,
					`map_uid`,
					`nearMissDist`,
					`player_login`,
					`weaponid`,
					`weaponname`,
					`matchServerLogin`
				  ) VALUES (
					" . $this->MapNumber . ",
					" . $this->TurnNumber . ",
					'" . $this->storage->currentMap->uId . "',
					'" . $content->event->missDist . "',
					'" . $content->event->shooter->login . "',
					'" . $content->event->weaponNum . "',
					'" . $this->getWeaponName($content->event->weaponNum) . "',
					" . $this->db->quote($this->storage->serverLogin) . "
				  )";
        $this->logger->write($qnearmiss);
        $this->db->execute($qnearmiss);
    }

    function onXmlRpcEliteEndMap(JsonCallbacks\EndMap $content) {
        $querymapEnd = "UPDATE `match_maps`
	SET `MapEnd` = '" . date('Y-m-d H:i:s', $content->timestamp) . "', 
	 `Roundscore_blue` = " . $this->db->quote($content->clan1MapScore) . ",
	 `Roundscore_red` = " . $this->db->quote($content->clan2MapScore) . ",
	 `TurnNumber` = '" . $this->TurnNumber . "',
	 `AllReady` = '0'
	 where `match_id` = " . $this->db->quote($this->MatchNumber) . " and `map_uid` = " . $this->db->quote($this->storage->currentMap->uId) . " and `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . "";
        $this->logger->write($querymapEnd);
        $this->db->execute($querymapEnd);
    }

    function onXmlRpcEliteEndMatch(JsonCallbacks\EndMatch $content) {
        //$MapWin = $this->connection->getModeScriptSettings();
        //print_r("Nb Map to win: ") . var_dump($MapWin['S_MapWin']);

        $queryMapWinSettingsEnd = "UPDATE `matches` SET 
                                                    `MatchEnd` = '" . date('Y-m-d H:i:s', $content->timestamp) . "'
                                                     WHERE
                                                    `matchServerLogin` = " . $this->db->quote($this->storage->serverLogin) . " and 
                                                    `id` = " . $this->db->quote($this->MatchNumber) . "";
        $this->logger->write($queryMapWinSettingsEnd);
        $this->db->execute($queryMapWinSettingsEnd);
    }

    protected
            function getWeaponName($num) {
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