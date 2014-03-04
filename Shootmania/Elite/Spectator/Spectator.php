<?php

/**
  Name: Willem 'W1lla' van den Munckhof
  Date: 15/11/2013
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
    /** @var Log */
    private $logger;
 
    /** @var $playerIDs[$login] = number */
    private $playerIDs = array();
 
    function onInit() {
        $this->setVersion('1.0.2');
		
        $this->logger = new Log($this->storage->serverLogin);
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
	}
	
	 function getServerCurrentMatch($serverLogin) {
	 $CurrentMatchid = $this->db->execute(
                        'SELECT id FROM matches ' .
                        'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = ' . $this->db->quote($serverLogin) .
                        'order by id desc')->fetchSingleValue();
	$this->logger->Normal($CurrentMatchid);					
        return $this->db->execute(
                        'SELECT id FROM matches ' .
                        'where MatchEnd = "0000-00-00 00:00:00" and `matchServerLogin` = ' . $this->db->quote($serverLogin) .
                        'order by id desc')->fetchSingleValue();
    }
	
	public function onModeScriptCallback($event, $json) {
	$this->logger->Callbacks($event);
	$this->logger->Callbacks($json);
        switch ($event) {
			case 'EndTurn':
                $this->onXmlRpcEliteSpectatorEndTurn(new JsonCallbacks\EndTurn($json));
                break;
    }	
    }
	
	function onTick() {
        
        if($this->tickCounter % 3 == 0){
		$this->MatchNumber = $this->getServerCurrentMatch($this->storage->serverLogin);
		 if (empty($this->SpecTarget->login) || $this->SpecTarget->login == $this->storage->server)
                return;
				 if($this->SpecPlayer->spectator){
		 if (empty($this->SpecTarget->login) || $this->SpecTarget->login == $this->storage->server)
                return;
        $this->AtkPlayer = $this->getPlayerId($this->SpecTarget->login);
		$queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces
FROM player_maps
JOIN matches ON player_maps.match_id = matches.id
WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryCurrentMatchAtkPlayerStats);
		$this->db->execute($queryCurrentMatchAtkPlayerStats);
		
		$AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		if ($AtkRoundsObject == NULL){
		$this->AtkRounds = 0;
		}
		else 
		{
		$this->AtkRounds = $AtkRoundsObject;
		}
		
		$AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		if ($AtkSuccesObject == NULL){
		$this->AtkSucces = 0;
		}
		else 
		{
		$this->AtkSucces = $AtkSuccesObject;
		}
		
		$QueryRocketHits = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 2)) LIMIT 1";
		//$this->logger->Debug($QueryRocketHits);
		$this->db->execute($QueryRocketHits);
		
		$RocketHitsQuery = $this->db->execute($QueryRocketHits)->fetchObject()->hits;
		if ($RocketHitsQuery == NULL){
		$this->RocketHits = 0;
		}
		else 
		{
		$this->RocketHits = $RocketHitsQuery;
		}
		
		$LaserAccQuery = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 1)) LIMIT 1";
		//$this->logger->Debug($LaserAccQuery);
		$this->db->execute($LaserAccQuery);
		
		$LaserAccRatio = $this->db->execute($LaserAccQuery)->fetchObject()->ratio;
		if ($LaserAccRatio == NULL){
		$this->LaserAcc = 0;
		}
		else 
		{
		$this->LaserAcc = $LaserAccRatio;
		}
		$laserHitDist = "SELECT MAX(HitDist) as HitDist FROM `hits` AS `Hit` WHERE `shooter_player_login` = " . $this->db->quote($this->AtkPlayer) . " AND `weaponid` = 1";
		$this->db->execute($laserHitDist);
		
		$LongestLaserObject = $this->db->execute($laserHitDist)->fetchObject()->HitDist;
		if ($LongestLaserObject == NULL){
		$this->LongestLaser = 0;
		}
		else 
		{
		$this->LongestLaser = $LongestLaserObject;
		}
		
		$queryCurrentMatchAtkPlayerStatsCaptures = "SELECT SUM( player_maps.captures ) AS Captures
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryCurrentMatchAtkPlayerStatsCaptures);
		$this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures);
		
		$AtkCaptureObject = $this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures)->fetchObject()->Captures;
		if ($AtkCaptureObject == NULL){
		$this->AtkCapture = 0;
		}
		else 
		{
		$this->AtkCapture = $AtkCaptureObject;
		}
		
		$QueryShots_HitsAtkPlayer = "SELECT SUM( player_maps.shots ) AS shots, SUM( player_maps.hits ) AS hits
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";

		//$this->logger->Debug($QueryShots_HitsAtkPlayer);
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
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryAtkHitRatio);
		$this->db->execute($queryAtkHitRatio);
		
	
		$HitRatioObject = $this->db->execute($queryAtkHitRatio)->fetchObject()->HitRatio;
		if ($HitRatioObject == NULL){
		$this->HitRatio = 0;
		}
		else 
		{
		$this->HitRatio =  number_format($HitRatioObject, 2, ',', '');
		}
		$this->Team = ($this->SpecTarget->teamId + 1);
		$this->AtkPlayerLogin = $this->SpecTarget->login;
		
		$this->ShowWidget($this->SpecPlayer->login, $this->SpecTarget->login, $this->AtkRounds, $this->AtkSucces, $this->AtkCapture, $this->RocketHits, $this->LaserAcc, $this->LongestLaser, $this->Team, $this->AtkPlayerLogin);
            $this->tickCounter++;
					}
        }else{
            $this->tickCounter = 0;
		}
            }
	
		
		public function onPlayerInfoChanged($playerInfo){
		$player = \ManiaPlanet\DedicatedServer\Structures\Player::fromArray($playerInfo);
		//var_dump($player);
		 $this->SpecPlayer = $player;
		 if ($this->SpecPlayer->playerId == 0)
            return;
		 if($player->spectator == true && $player->pureSpectator == true){
		 $SpecTarget = $this->getPlayerObjectById($player->currentTargetId);
		 if (empty($SpecTarget->login) || $SpecTarget->login == $this->storage->server || empty($SpecTarget))
                return;
		 $this->SpecTarget = $SpecTarget;
		 $this->MatchNumber = $this->getServerCurrentMatch($this->storage->serverLogin);
        $this->AtkPlayer = $this->getPlayerId($this->SpecTarget->login);
		$queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces
FROM player_maps
JOIN matches ON player_maps.match_id = matches.id
WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryCurrentMatchAtkPlayerStats);
		$this->db->execute($queryCurrentMatchAtkPlayerStats);
		
		$AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		if ($AtkRoundsObject == NULL){
		$this->AtkRounds = 0;
		}
		else 
		{
		$this->AtkRounds = $AtkRoundsObject;
		}
		
		$AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		if ($AtkSuccesObject == NULL){
		$this->AtkSucces = 0;
		}
		else 
		{
		$this->AtkSucces = $AtkSuccesObject;
		}
		
		$QueryRocketHits = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 2)) LIMIT 1";
		//$this->logger->Debug($QueryRocketHits);
		$this->db->execute($QueryRocketHits);
		
		$RocketHitsQuery = $this->db->execute($QueryRocketHits)->fetchObject()->hits;
		if ($RocketHitsQuery == NULL){
		$this->RocketHits = 0;
		}
		else 
		{
		$this->RocketHits = $RocketHitsQuery;
		}
		
		$LaserAccQuery = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 1)) LIMIT 1";
		//$this->logger->Debug($LaserAccQuery);
		$this->db->execute($LaserAccQuery);
		
		$LaserAccRatio = $this->db->execute($LaserAccQuery)->fetchObject()->ratio;
		if ($LaserAccRatio == NULL){
		$this->LaserAcc = 0;
		}
		else 
		{
		$this->LaserAcc = $LaserAccRatio;
		}
		$laserHitDist = "SELECT MAX(HitDist) as HitDist FROM `hits` AS `Hit` WHERE `shooter_player_login` = " . $this->db->quote($this->AtkPlayer) . " AND `weaponid` = 1";
		$this->db->execute($laserHitDist);
		
		$LongestLaserObject = $this->db->execute($laserHitDist)->fetchObject()->HitDist;
		if ($LongestLaserObject == NULL){
		$this->LongestLaser = 0;
		}
		else 
		{
		$this->LongestLaser = $LongestLaserObject;
		}
		
		$queryCurrentMatchAtkPlayerStatsCaptures = "SELECT SUM( player_maps.captures ) AS Captures
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryCurrentMatchAtkPlayerStatsCaptures);
		$this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures);
		
		$AtkCaptureObject = $this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures)->fetchObject()->Captures;
		if ($AtkCaptureObject == NULL){
		$this->AtkCapture = 0;
		}
		else 
		{
		$this->AtkCapture = $AtkCaptureObject;
		}
		
		$QueryShots_HitsAtkPlayer = "SELECT SUM( player_maps.shots ) AS shots, SUM( player_maps.hits ) AS hits
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";

		//$this->logger->Debug($QueryShots_HitsAtkPlayer);
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
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryAtkHitRatio);
		$this->db->execute($queryAtkHitRatio);
		
	
		$HitRatioObject = $this->db->execute($queryAtkHitRatio)->fetchObject()->HitRatio;
		if ($HitRatioObject == NULL){
		$this->HitRatio = 0;
		}
		else 
		{
		$this->HitRatio =  number_format($HitRatioObject, 2, ',', '');
		}
		$this->Team = ($this->SpecTarget->teamId + 1);
		$this->AtkPlayerLogin = $this->SpecTarget->login;
		
		
		$this->ShowWidget($player->login, $this->SpecTarget->login, $this->AtkRounds, $this->AtkSucces, $this->AtkCapture, $this->RocketHits, $this->LaserAcc, $this->LongestLaser, $this->Team, $this->AtkPlayerLogin);
		}
		else
		{
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
	
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer,array $entries)
	{
	if ($answer == "41")
	{
	 $this->MatchNumber = $this->getServerCurrentMatch($this->storage->serverLogin);
        $this->AtkPlayer = $this->getPlayerId($this->SpecTarget->login);
		$queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces
FROM player_maps
JOIN matches ON player_maps.match_id = matches.id
WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryCurrentMatchAtkPlayerStats);
		$this->db->execute($queryCurrentMatchAtkPlayerStats);
		
		$AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		if ($AtkRoundsObject == NULL){
		$this->AtkRounds = 0;
		}
		else 
		{
		$this->AtkRounds = $AtkRoundsObject;
		}
		
		$AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		if ($AtkSuccesObject == NULL){
		$this->AtkSucces = 0;
		}
		else 
		{
		$this->AtkSucces = $AtkSuccesObject;
		}
		
		$QueryRocketHits = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 2)) LIMIT 1";
		//$this->logger->Debug($QueryRocketHits);
		$this->db->execute($QueryRocketHits);
		
		$RocketHitsQuery = $this->db->execute($QueryRocketHits)->fetchObject()->hits;
		if ($RocketHitsQuery == NULL){
		$this->RocketHits = 0;
		}
		else 
		{
		$this->RocketHits = $RocketHitsQuery;
		}
		
		$LaserAccQuery = "SELECT (SUM(hits)/SUM(shots)*100) as ratio, SUM(shots) as shots, SUM(hits) as hits FROM `shots` AS `Shot` WHERE ((`Shot`.`player_id` = " . $this->db->quote($this->AtkPlayer) . ") AND (`Shot`.`weapon_id` = 1)) LIMIT 1";
		//$this->logger->Debug($LaserAccQuery);
		$this->db->execute($LaserAccQuery);
		
		$LaserAccRatio = $this->db->execute($LaserAccQuery)->fetchObject()->ratio;
		if ($LaserAccRatio == NULL){
		$this->LaserAcc = 0;
		}
		else 
		{
		$this->LaserAcc = $LaserAccRatio;
		}
		$laserHitDist = "SELECT MAX(HitDist) as HitDist FROM `hits` AS `Hit` WHERE `shooter_player_login` = " . $this->db->quote($this->AtkPlayer) . " AND `weaponid` = 1";
		$this->db->execute($laserHitDist);
		
		$LongestLaserObject = $this->db->execute($laserHitDist)->fetchObject()->HitDist;
		if ($LongestLaserObject == NULL){
		$this->LongestLaser = 0;
		}
		else 
		{
		$this->LongestLaser = $LongestLaserObject;
		}
		
		$queryCurrentMatchAtkPlayerStatsCaptures = "SELECT SUM( player_maps.captures ) AS Captures
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryCurrentMatchAtkPlayerStatsCaptures);
		$this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures);
		
		$AtkCaptureObject = $this->db->execute($queryCurrentMatchAtkPlayerStatsCaptures)->fetchObject()->Captures;
		if ($AtkCaptureObject == NULL){
		$this->AtkCapture = 0;
		}
		else 
		{
		$this->AtkCapture = $AtkCaptureObject;
		}
		
		$QueryShots_HitsAtkPlayer = "SELECT SUM( player_maps.shots ) AS shots, SUM( player_maps.hits ) AS hits
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";

		//$this->logger->Debug($QueryShots_HitsAtkPlayer);
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
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		//$this->logger->Debug($queryAtkHitRatio);
		$this->db->execute($queryAtkHitRatio);
		
	
		$HitRatioObject = $this->db->execute($queryAtkHitRatio)->fetchObject()->HitRatio;
		if ($HitRatioObject == NULL){
		$this->HitRatio = 0;
		}
		else 
		{
		$this->HitRatio =  number_format($HitRatioObject, 2, ',', '');
		}
		$this->Team = ($this->SpecTarget->teamId + 1);
		$this->AtkPlayerLogin = $this->SpecTarget->login;
		
		
	$this->ShowWidget($login, $this->SpecTarget->login, $this->AtkRounds, $this->AtkSucces, $this->AtkCapture, $this->RocketHits, $this->LaserAcc, $this->LongestLaser, $this->Team, $this->AtkPlayerLogin);
	}
	}
	
	function ShowWidget($login, $AttackPlayerNick, $RoundsAtk, $RoundsSuccess, $CaptureAtk, $RocketHits, $LaserAcc, $LongestLaser, $TeamNr, $AttackPlayerLogin){
		$blue = $this->connection->getTeamInfo(1);
        $red = $this->connection->getTeamInfo(2);
		$xml = '<manialinks>';
                $xml .= '<manialink version="1" background="1" navigable3d="0" id="AtkSpecDetails">';
                $xml .= '<frame id="AtkSpecDetails">';
                $xml .= '<quad image="file://Media/Manialinks/Common/Lobbies/header.png" posn="-82.5 -62 -1" sizen="165 32"/>'; // MainWindow
                $xml .= '<label posn="-77 -70 -1" sizen="30.33" textsize="2.75" style="TextRaceMessage" text="Laser: '.$LongestLaser.' m"/>';
                $xml .= '<label posn="-50 -70 -1" sizen="30.33" textsize="2.75" style="TextRaceMessage" text="Captures: '.$CaptureAtk.'"/>';
                $xml .= '<label posn="54 -70 -1" sizen="30.33" textsize="2.75" style="TextRaceMessage" text="Rocket: '.$RocketHits.'"/>';
                $xml .= '<label posn="27 -70 -1" sizen="30.33" textsize="2.75" style="TextRaceMessage" text="Rail: '.$LaserAcc.' %"/>';
                $xml .= '<label posn="-15 -65 -1" sizen="30.33" textsize="2.75" style="TextRaceMessage" text="AtkWins: '.$RoundsSuccess.' / '.$RoundsAtk.'"/>';
				//if ($login == 'w1lla'){
				//$xml .= '<quad posn="-10.5 -40 0.5" sizen="7 7" style="Icons128x128_1" substyle="Default" id="Refresh_Data" action="41"/>';
				//}
                if ($TeamNr == 1){
                if ($blue->clubLinkUrl) {
				//$xml .= '<quad image="'.$this->Clublink($blue->clubLinkUrl).'" posn="50 -20 0.5" sizen="15 15" />';   
				}
                else{
                //$xml .= '<quad posn="50 -20 0.5" sizen="15 15" style="Emblems" substyle="#1" />';
                }
                }
                if ($TeamNr == 2){
                if ($red->clubLinkUrl) {
				//$xml .= '<quad image="'.$this->Clublink($red->clubLinkUrl).'" posn="50 -20 0.5" sizen="15 15" />';   
				}
                else{
                //$xml .= '<quad posn="50 -20 0.5" sizen="15 15" style="Emblems" substyle="#2" />';
                }
                }


                $xml .= '</frame>';
                $xml .= '<script><!--
    main () {
        declare FrameRules  <=> Page.GetFirstChild("AtkSpecDetails");
        declare ShowRules = True;

        while(True) {
            yield;

            if (ShowRules) {
                FrameRules.Show();
            } else {
                FrameRules.Hide();
            }

            foreach (Event in PendingEvents) {
                switch (Event.Type) {

                    case CMlEvent::Type::KeyPress:
                    {
                        if (Event.CharPressed == "2818048") ShowRules = !ShowRules; // F7
                    }
                }
            }
        }
    }
--></script>'; // F7 Keypress
                $xml .= '</manialink>';
                $xml .= '</manialinks>';
        $this->connection->sendDisplayManialinkPage($login, $xml, 0, true, true);
                }
	
	function Clublink($URL){
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

	$ch      = curl_init($URL);
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
	 if($player->spectator == true && $player->pureSpectator == true){
        $this->connection->sendHideManialinkPage($player->login, $xml, 0, true, true);
        }
		else
		{
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
            $this->logger->Debug($q);
            return $this->db->execute($q)->fetchObject()->id;
        }
    }
	
 }
?>
