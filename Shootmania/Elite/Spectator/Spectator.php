<?php
 
/**
  Name: Willem 'W1lla' van den Munckhof
  Date: 15/4/2014
  Project Name: ESWC Elite Statistics
  Fix from Reaby (F7 Callbacks)
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
 
namespace ManiaLivePlugins\Shootmania\Elite\Spectator;
 
use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Console;
use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Utilities\Validation;
use ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;
use ManiaLivePlugins\Shootmania\Elite\Classes\Log;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLive\Data\Event as PlayerEvent;
 
class Spectator extends \ManiaLive\PluginHandler\Plugin {
 
    /** @var integer */
    protected $MatchNumber = false;
 
    /** @var integer */
    private $AtkPlayer;
    private $AtkRounds;
    private $AtkSucces;
    private $AtkCapture;
    private $AtkShots;
    private $AtkHits;
    private $HitRatio;
    private $AttackPlayer;
    private $RocketHits;
    private $LaserAcc;
    private $LongestLaser;
    private $Team;
    private $AtkPlayerLogin;
    private $SpecTarget;
    private $tickCounter = 0;
    private $SpecPlayer;
    private $match_map_id;
    private $CurrentMapId;
    private $lastUpdate;
    private $forceUpdate = false;
    private $needUpdate = false;
    private $BeginTurnAtkPlayer;
    private $widgetVisible = array();
 
    /** @var Log */
    private $logger;
 
    /** @var $playerIDs[$login] = number */
    private $playerIDs = array();
 
    function onInit() {
	$this->setVersion('1.0.6.b');
 
	$this->logger = new Log('./logs/', Log::DEBUG, $this->storage->serverLogin);
    }
 
    function onLoad() {
	$this->enableDatabase();
	$this->enableDedicatedEvents();
	$this->enablePluginEvents();
	$this->enableStorageEvents(PlayerEvent::ON_PLAYER_CHANGE_SIDE);
    }
 
    function onReady() {
	Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Spectator Core v' . $this->getVersion());
	foreach ($this->storage->spectators as $player) {
	    $this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [Shootmania] Elite Spectator Stats$fa0!', $player->login);
	}
	$this->enableTickerEvent();
	$this->enableDedicatedEvents(ServerEvent::ON_MODE_SCRIPT_CALLBACK);
	$this->lastUpdate = time();
	$this->enableTickerEvent();
	$this->forceUpdate = true;
 
	// assign widget visible for spectators
	foreach ($this->storage->spectators as $login => $player) {
	    $this->widgetVisible[$login] = True;
	    // w1lla you should add displaying the widget for the login here :)
	}
    }
 
    function getServerCurrentMatch($serverLogin) {
	$CurrentMatchid = $this->db->execute(
			'SELECT id FROM matches ' .
			'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = ' . $this->db->quote($serverLogin) .
			'order by id desc')->fetchSingleValue();
	$this->logger->logDebug($CurrentMatchid);
	return $this->db->execute(
			'SELECT id FROM matches ' .
			'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = ' . $this->db->quote($serverLogin) .
			'order by id desc')->fetchSingleValue();
    }
 
    public function onModeScriptCallback($event, $json) {
	$this->logger->logInfo($event);
	$this->logger->logInfo($json);
	switch ($event) {
	    case 'BeginTurn':
		$this->onXmlRpcEliteBeginTurn(new JsonCallbacks\BeginTurn($json));
			foreach ($this->storage->spectators as $login => $player) {
	    $xml = '<manialinks>';
	    $xml .= '<manialink id="AtkSpecDetails">';
	    $xml .= '</manialink>';
	    $xml .= '</manialinks>';
	    $this->connection->sendHideManialinkPage($player->login, $xml, 0, true, true);
	}
		break;
	    case 'EndTurn':
		$this->onXmlRpcEliteSpectatorEndTurn(new JsonCallbacks\EndTurn($json));
			foreach ($this->storage->spectators as $login => $player) {
	    $xml = '<manialinks>';
	    $xml .= '<manialink id="AtkSpecDetails">';
	    $xml .= '</manialink>';
	    $xml .= '</manialinks>';
	    $this->connection->sendHideManialinkPage($player->login, $xml, 0, true, true);
	}
		break;
	}
    }
 
    function onXmlRpcEliteBeginTurn(JsonCallbacks\BeginTurn $content) {
 
	// Players and stuff
	if ($content->attackingPlayer == NULL) {
	    
	} else {
	    $this->BeginTurnAtkPlayer = $this->getPlayerId($content->attackingPlayer->login);
	}
    }
 
    function onTick() {
	if ((time() - $this->lastUpdate) > 0.5 && $this->needUpdate || $this->forceUpdate == true) {
	    $this->lastUpdate = time();
	    $this->forceUpdate = false;
	    $this->needUpdate = false;
	    $this->MatchNumber = $this->getServerCurrentMatch($this->storage->serverLogin);
	    //var_dump($this->BeginTurnAtkPlayer);
	    //var_dump($this->AtkPlayer);
	    if (empty($this->SpecTarget->login) || $this->SpecTarget->login === $this->storage->serverLogin || empty($this->BeginTurnAtkPlayer) || empty($this->AtkPlayer) || $this->SpecPlayer->currentTargetId === 255)
		return;
 
	    if ($this->SpecPlayer->spectator) {
		$this->AtkPlayer = $this->getPlayerId($this->SpecTarget->login);
		$this->CurrentMapId = $this->getMapid();
		$this->match_map_id = $this->getMatchMapId();
		if ($this->AtkPlayer == $this->BeginTurnAtkPlayer) {
		    $queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds - 1 ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
		    $this->logger->logDebug($queryCurrentMatchAtkPlayerStats);
		    $this->db->execute($queryCurrentMatchAtkPlayerStats);
 
		    $AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		    if ($AtkRoundsObject == NULL) {
			$this->AtkRounds = 0;
		    } else {
			$this->AtkRounds = $AtkRoundsObject;
		    }
 
		    $AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		    if ($AtkSuccesObject == NULL) {
			$this->AtkSucces = 0;
		    } else {
			$this->AtkSucces = $AtkSuccesObject;
		    }
		} else {
		    $queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
		    $this->logger->logDebug($queryCurrentMatchAtkPlayerStats);
		    $this->db->execute($queryCurrentMatchAtkPlayerStats);
 
		    $AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		    if ($AtkRoundsObject == NULL) {
			$this->AtkRounds = 0;
		    } else {
			$this->AtkRounds = $AtkRoundsObject;
		    }
 
		    $AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		    if ($AtkSuccesObject == NULL) {
			$this->AtkSucces = 0;
		    } else {
			$this->AtkSucces = $AtkSuccesObject;
		    }
		}
 
		$QueryRocketHits = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 2) AND (`Shot`.`match_map_id` = " . $this->db->quote($this->match_map_id) . ")) LIMIT 1";
		$this->logger->logDebug($QueryRocketHits);
		$this->db->execute($QueryRocketHits);
 
		$RocketHitsQuery = $this->db->execute($QueryRocketHits)->fetchObject()->hits;
		if ($RocketHitsQuery == NULL) {
		    $this->RocketHits = 0;
		} else {
		    $this->RocketHits = $RocketHitsQuery;
		}
 
		$LaserAccQuery = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 1) AND (`Shot`.`match_map_id` = " . $this->db->quote($this->match_map_id) . ")) LIMIT 1";
		$this->logger->logDebug($LaserAccQuery);
		$this->db->execute($LaserAccQuery);
 
		$LaserAccRatio = $this->db->execute($LaserAccQuery)->fetchObject()->ratio;
		if ($LaserAccRatio == NULL) {
		    $this->LaserAcc = 0;
		} else {
		    $this->LaserAcc = number_format($LaserAccRatio, 2, ',', '');
		}
		$laserHitDist = "SELECT MAX(HitDist) as HitDist FROM `hits` AS `Hit` WHERE `shooter_player_id` = " . $this->db->quote($this->AtkPlayer) . " AND `weaponid` = 1 AND `match_map_id` = " . $this->db->quote($this->match_map_id) . "";
		$this->db->execute($laserHitDist);
 
		$queryCurrentMatchAtkPlayerStatsCaptures = "SELECT SUM( player_maps.captures ) AS Captures
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
		$this->logger->logDebug($queryCurrentMatchAtkPlayerStatsCaptures);
		$this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures);
 
		$AtkCaptureObject = $this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures)->fetchObject()->Captures;
		if ($AtkCaptureObject == NULL) {
		    $this->AtkCapture = 0;
		} else {
		    $this->AtkCapture = $AtkCaptureObject;
		}
 
		$QueryShots_HitsAtkPlayer = "SELECT SUM( player_maps.shots ) AS shots, SUM( player_maps.hits ) AS hits
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
		$this->logger->logDebug($QueryShots_HitsAtkPlayer);
		$this->db->execute($QueryShots_HitsAtkPlayer);
 
		$AtkShotsObject = $this->db->execute($QueryShots_HitsAtkPlayer)->fetchObject()->shots;
		$this->AtkShots = $AtkShotsObject;
 
		$AtkHitsObject = $this->db->execute($QueryShots_HitsAtkPlayer)->fetchObject()->hits;
		$this->AtkHits = $AtkHitsObject;
 
		$queryAtkHitRatio = "SELECT (
		SUM( player_maps.hits ) / SUM( player_maps.shots ) * 100
		) AS HitRatio
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
		$this->logger->logDebug($queryAtkHitRatio);
		$this->db->execute($queryAtkHitRatio);
 
 
		$HitRatioObject = $this->db->execute($queryAtkHitRatio)->fetchObject()->HitRatio;
		if ($HitRatioObject == NULL) {
		    $this->HitRatio = 0;
		} else {
		    $this->HitRatio = $HitRatioObject;
		}
		$this->Team = ($this->SpecTarget->teamId + 1);
		$this->AtkPlayerLogin = $this->SpecTarget->login;
 
		$this->ShowWidget($this->SpecPlayer->login, $this->SpecTarget->login, $this->AtkRounds, $this->AtkSucces, $this->AtkCapture, $this->RocketHits, $this->LaserAcc, $this->Team, $this->AtkPlayerLogin);
	    } else {
		
	    }
	}
    }
 
    public function toggleWidgetForPlayer($login) {
	if (array_key_exists($login, $this->widgetVisible)) {
	    $this->widgetVisible[$login] = !$this->widgetVisible[$login];
	}
	if ($this->widgetVisible[$login] == true) {
	    $this->connection->chatSendServerMessage("[notice] Spectator widgets are now visible!", $login);
		$this->ShowWidget($login, $this->SpecTarget->login, $this->AtkRounds, $this->AtkSucces, $this->AtkCapture, $this->RocketHits, $this->LaserAcc, $this->Team, $this->AtkPlayerLogin);
	} else {
	    $this->connection->chatSendServerMessage("[notice] Spectator widgets are now hidden!", $login);
		$this->connection->sendHideManialinkPage();
	}
    }
 
    public function onPlayerInfoChanged($playerInfo) {
	$player = \ManiaPlanet\DedicatedServer\Structures\PlayerInfo::fromArray($playerInfo);
	echo $player->currentTargetId;
	
	if (!array_key_exists($player->login, $this->widgetVisible)) {
	    $this->widgetVisible[$player->login] = True;
	}
 
 
	
 
	if ($this->widgetVisible[$player->login] === false && $player->spectator == True) {
	    $this->connection->chatSendServerMessage("[notice] Spectator widgets are hidden, press F7 to enable.", $player->login);
	}
	
	$shortkey = \ManiaLive\Gui\Windows\Shortkey::Create($player->login);
	if ($player->spectator) {
	    $shortkey->removeCallback(\ManiaLive\Gui\Windows\Shortkey::F7); // to be sure no other callbacks are registered for this key...
	    $shortkey->addCallback(\ManiaLive\Gui\Windows\Shortkey::F7, array($this, "toggleWidgetForPlayer"));
	} else {
	    $shortkey->removeCallback(\ManiaLive\Gui\Windows\Shortkey::F7); // to be sure no other callbacks are registered for this key...
	}
	$shortkey->show($player->login);
	
	
	
	//var_dump($player);
	$this->SpecPlayer = $player;
	if ($this->SpecPlayer->currentTargetId == 0) {
	    $this->connection->sendHideManialinkPage();
	}
	foreach ($this->storage->spectators as $login => $player) {
	   $this->connection->sendHideManialinkPage();
	}
	if ($this->SpecPlayer->currentTargetId === 255) {
	   $this->connection->sendHideManialinkPage();
	}
	if ($player->spectator == true && $player->pureSpectator == true && $this->widgetVisible[$player->login]) {
	    $this->needUpdate = true;
	    $this->forceUpdate = true;
	    $SpecTarget = $this->getPlayerObjectById($player->currentTargetId);
	    if (empty($SpecTarget->login) || $SpecTarget->login == $this->storage->serverLogin || empty($SpecTarget))
		return;
	    $this->SpecTarget = $SpecTarget;
	    $this->MatchNumber = $this->getServerCurrentMatch($this->storage->serverLogin);
	    $this->AtkPlayer = $this->getPlayerId($this->SpecTarget->login);
	    $this->CurrentMapId = $this->getMapid();
	    $this->match_map_id = $this->getMatchMapId();
	    if ($this->AtkPlayer == $this->BeginTurnAtkPlayer) {
		$queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds - 1 ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
		$this->logger->logDebug($queryCurrentMatchAtkPlayerStats);
		$this->db->execute($queryCurrentMatchAtkPlayerStats);
 
		$AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		if ($AtkRoundsObject == NULL) {
		    $this->AtkRounds = 0;
		} else {
		    $this->AtkRounds = $AtkRoundsObject;
		}
 
		$AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		if ($AtkSuccesObject == NULL) {
		    $this->AtkSucces = 0;
		} else {
		    $this->AtkSucces = $AtkSuccesObject;
		}
	    } else {
		$queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
		$this->logger->logDebug($queryCurrentMatchAtkPlayerStats);
		$this->db->execute($queryCurrentMatchAtkPlayerStats);
 
		$AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		if ($AtkRoundsObject == NULL) {
		    $this->AtkRounds = 0;
		} else {
		    $this->AtkRounds = $AtkRoundsObject;
		}
 
		$AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		if ($AtkSuccesObject == NULL) {
		    $this->AtkSucces = 0;
		} else {
		    $this->AtkSucces = $AtkSuccesObject;
		}
	    }
 
	    $QueryRocketHits = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 2) AND (`Shot`.`match_map_id` = " . $this->db->quote($this->match_map_id) . ")) LIMIT 1";
	    $this->logger->logDebug($QueryRocketHits);
	    $this->db->execute($QueryRocketHits);
 
	    $RocketHitsQuery = $this->db->execute($QueryRocketHits)->fetchObject()->hits;
	    if ($RocketHitsQuery == NULL) {
		$this->RocketHits = 0;
	    } else {
		$this->RocketHits = $RocketHitsQuery;
	    }
 
	    $LaserAccQuery = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 1) AND (`Shot`.`match_map_id` = " . $this->db->quote($this->match_map_id) . ")) LIMIT 1";
	    $this->logger->logDebug($LaserAccQuery);
	    $this->db->execute($LaserAccQuery);
 
	    $LaserAccRatio = $this->db->execute($LaserAccQuery)->fetchObject()->ratio;
	    if ($LaserAccRatio == NULL) {
		$this->LaserAcc = 0;
	    } else {
		$this->LaserAcc = number_format($LaserAccRatio, 2, ',', '');
	    }
	    $laserHitDist = "SELECT MAX(HitDist) as HitDist FROM `hits` AS `Hit` WHERE `shooter_player_id` = " . $this->db->quote($this->AtkPlayer) . " AND `weaponid` = 1 AND `match_map_id` = " . $this->db->quote($this->match_map_id) . "";
	    $this->db->execute($laserHitDist);
 
	    $queryCurrentMatchAtkPlayerStatsCaptures = "SELECT SUM( player_maps.captures ) AS Captures
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
	    $this->logger->logDebug($queryCurrentMatchAtkPlayerStatsCaptures);
	    $this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures);
 
	    $AtkCaptureObject = $this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures)->fetchObject()->Captures;
	    if ($AtkCaptureObject == NULL) {
		$this->AtkCapture = 0;
	    } else {
		$this->AtkCapture = $AtkCaptureObject;
	    }
 
	    $QueryShots_HitsAtkPlayer = "SELECT SUM( player_maps.shots ) AS shots, SUM( player_maps.hits ) AS hits
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
 
	    $this->logger->logDebug($QueryShots_HitsAtkPlayer);
	    $this->db->execute($QueryShots_HitsAtkPlayer);
 
	    $AtkShotsObject = $this->db->execute($QueryShots_HitsAtkPlayer)->fetchObject()->shots;
	    $this->AtkShots = $AtkShotsObject;
 
	    $AtkHitsObject = $this->db->execute($QueryShots_HitsAtkPlayer)->fetchObject()->hits;
	    $this->AtkHits = $AtkHitsObject;
 
	    $queryAtkHitRatio = "SELECT (
		SUM( player_maps.hits ) / SUM( player_maps.shots ) * 100
		) AS HitRatio
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "
		AND player_maps.match_map_id = " . $this->db->quote($this->match_map_id) . "";
	    $this->logger->logDebug($queryAtkHitRatio);
	    $this->db->execute($queryAtkHitRatio);
 
 
	    $HitRatioObject = $this->db->execute($queryAtkHitRatio)->fetchObject()->HitRatio;
	    if ($HitRatioObject == NULL) {
		$this->HitRatio = 0;
	    } else {
		$this->HitRatio = $HitRatioObject;
	    }
	    $this->Team = ($this->SpecTarget->teamId + 1);
	    $this->AtkPlayerLogin = $this->SpecTarget->login;
 
	    $this->ShowWidget($player->login, $this->SpecTarget->login, $this->AtkRounds, $this->AtkSucces, $this->AtkCapture, $this->RocketHits, $this->LaserAcc, $this->Team, $this->AtkPlayerLogin);
	} else {
	    $xml = '<manialinks>';
	    $xml .= '<manialink id="AtkSpecDetails">';
	    $xml .= '</manialink>';
	    $xml .= '</manialinks>';
	    $this->connection->sendHideManialinkPage($player->login, $xml, 0, true, true);
	}
    }
 
    public function getPlayerObjectById($id) {
	if (!is_numeric($id))
	    throw new Exception("player id is not numeric");
	foreach ($this->storage->players as $login => $player) {
	    if ($player->playerId == $id)
		return $player;
	}
	foreach ($this->storage->spectators as $login => $player) {
	    if ($player->playerId == $id)
		return $player;
	}
	return new \ManiaPlanet\DedicatedServer\Structures\Player();
    }
 
    function ShowWidget($login, $AttackPlayerNick, $RoundsAtk, $RoundsSuccess, $CaptureAtk, $RocketHits, $LaserAcc, $TeamNr, $AttackPlayerLogin) {
	// to be extra sure widget not sent 
	if (array_key_exists($login, $this->widgetVisible)) {
	    if ($this->widgetVisible[$login] === false)
		return;
	}
	$blue = $this->connection->getTeamInfo(1);
	$red = $this->connection->getTeamInfo(2);
	$xml = '<manialinks>';
	$xml .= '<manialink version="1" background="1" navigable3d="0" id="AtkSpecDetails">';
	$xml .= '<frame id="AtkSpecDetails" posn="5 1.5 1">';
	$xml .= '<quad posn="-83.5 -66.9 -0.1" sizen="4.04 49"  style="EnergyBar" substyle="BgText"  rot="270"/>';
	$xml .= '<quad posn="74 -63.1 -0.1" sizen="4.04 50"  style="EnergyBar" substyle="BgText"  rot="90"/>';
	$xml .= '<quad posn="70.8 -63.9 -0.5" sizen="4.04 28"  style="EnergyBar" substyle="BgText"  rot="1"/>';
	$xml .= '<quad posn="-83.8 -63.9 -0.5" sizen="4.04 28"  style="EnergyBar" substyle="BgText"  rot="1"/>';
	$xml .= '<quad style="EnergyBar" substyle="EnergyBar"  posn="-81.3 -69" sizen="50 7.6"/>';
	$xml .= '<quad style="EnergyBar" substyle="EnergyBar"  posn="21.3 -69" sizen="50 7.6"/>';
	$xml .= '<label posn="-76 -71 0" sizen="30 5" textsize="2" style="TextValueBig" text="Atk:" />';
	$xml .= '<label posn="-65 -71 0" sizen="30 5" textsize="2" style="TextButtonBig" text="' . $RoundsSuccess . ' / ' . $RoundsAtk . '" />';
	$xml .= '<label posn="-50 -71 0" sizen="30 5" textsize="2" style="TextValueBig" text="Cap:" />';
	$xml .= '<label posn="-39 -71 0" sizen="30 5" textsize="2" style="TextButtonBig" text="' . $CaptureAtk . '" />';
	$xml .= '<quad posn="24.3 -68.6 0" sizen="8 8" style="Icons64x64_2" substyle="LaserHit" />';
	$xml .= '<label posn="34.3 -70.7 0" sizen="13.2 5" textsize="2" style="TextButtonBig" text="' . $LaserAcc . ' %" />';
	$xml .= '<quad posn="47.3 -68.6 0" sizen="8 8" style="Icons64x64_2" substyle="RocketHit" />';
	$xml .= '<label posn="60 -70.7 0" sizen="13.2 5" textsize="2" style="TextButtonBig" text="' . $RocketHits . '" />';
	if ($TeamNr == 1) {
	    if ($blue->clubLinkUrl) {
		//$xml .= '<quad image="'.$this->Clublink($blue->clubLinkUrl).'" posn="50 -20 0.5" sizen="15 15" />';   
	    } else {
		//$xml .= '<quad posn="50 -20 0.5" sizen="15 15" style="Emblems" substyle="#1" />';
	    }
	}
	if ($TeamNr == 2) {
	    if ($red->clubLinkUrl) {
		//$xml .= '<quad image="'.$this->Clublink($red->clubLinkUrl).'" posn="50 -20 0.5" sizen="15 15" />';   
	    } else {
		//$xml .= '<quad posn="50 -20 0.5" sizen="15 15" style="Emblems" substyle="#2" />';
	    }
	}
 
 
	$xml .= '</frame>';
	$xml .= '</manialink>';
	$xml .= '</manialinks>';
	$this->connection->sendDisplayManialinkPage($login, $xml, 0, true, true);
    }
 
    function Clublink($URL) {
	$options = array(
	    CURLOPT_RETURNTRANSFER => true, // return web page
	    CURLOPT_HEADER => false, // don't return headers
	    CURLOPT_FOLLOWLOCATION => true, // follow redirects
	    CURLOPT_ENCODING => "", // handle compressed
	    CURLOPT_USERAGENT => "ShootManiaEliteStatistics", // who am i
	    CURLOPT_AUTOREFERER => true, // set referer on redirect
	    CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
	    CURLOPT_TIMEOUT => 120, // timeout on response
	    CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
	);
 
	$ch = curl_init($URL);
	curl_setopt_array($ch, $options);
	$content = curl_exec($ch);
	$err = curl_errno($ch);
	$errmsg = curl_error($ch);
	$header = curl_getinfo($ch);
	curl_close($ch);
 
	$header['errno'] = $err;
	$header['errmsg'] = $errmsg;
	$header['content'] = $content;
	$xml = simplexml_load_string($content);
 
	// incase the xml is malformed, bail out
	if ($xml === false)
	    Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Spectator: Clublink is malformed' . $xml);
 
	if ($xml->getName() != "club")
	    Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Spectator: Clublink name != club!');
 
	return $xml->emblem;
    }
 
    function onXmlRpcEliteSpectatorEndTurn(JsonCallbacks\EndTurn $content) {
 
	$xml = '<manialinks>';
	$xml .= '<manialink id="AtkSpecDetails">';
	$xml .= '</manialink>';
	$xml .= '</manialinks>';
	foreach ($this->storage->spectators as $login => $player) { // get players
	    if ($player->spectator == true && $player->pureSpectator == true) {
		$this->connection->sendHideManialinkPage($player->login, $xml, 0, true, true);
	    } else {
		$this->connection->sendHideManialinkPage($player->login, $xml, 0, true, true);
	    }
	}
    }
 
    /** get cached value of database player id */
    function getPlayerId($login) {
	if (isset($this->playerIDs[$login])) {
	    return $this->playerIDs[$login];
	} else {
	    $q = "SELECT id FROM `players` WHERE `login` = " . $this->db->quote($login) . "";
	    $this->logger->logDebug($q);
	    return $this->db->execute($q)->fetchObject()->id;
	}
    }
 
    function getMapid() {
	$q = "SELECT id FROM `maps` WHERE `uid` = " . $this->db->quote($this->storage->currentMap->uId) . "";
	$this->logger->logDebug($q);
	return $this->db->execute($q)->fetchObject()->id;
    }
 
    function getMatchMapId() {
	$q = "SELECT id FROM `match_maps` WHERE `match_id` = " . $this->db->quote($this->MatchNumber) . " ORDER BY id DESC LIMIT 1";
	return $this->db->execute($q)->fetchSingleValue();
    }
 
}
 
?>