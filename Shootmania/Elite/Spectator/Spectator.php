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

class Spectator extends \ManiaLive\PluginHandler\Plugin {

    /** @var integer */
    protected $MatchNumber = false;
 
    /** @var integer */
	private $AtkPlayer;
	private $AtkRatio;
	private $AtkRounds;
	private $AtkSucces;
	private $AtkCapture;
	private $AtkShots;
	private $AtkHits;
	private $HitRatio;
	private $AttackPlayer;
	private $Team;
	private $AtkPlayerLogin;
	
    /** @var Log */
    private $logger;
 
    /** @var $playerIDs[$login] = number */
    private $playerIDs = array();
 
    function onInit() {
        $this->setVersion('0.1.0');
		
        $this->logger = new Log($this->storage->serverLogin);
	}
	
	function onLoad() {
	    $this->enableDatabase();
        $this->enableDedicatedEvents();
		$this->enablePluginEvents();
		$this->enableTickerEvent();
	}
	
	function onReady() {
	Console::println('[' . date('H:i:s') . '] [Shootmania] Elite Spectator Core v' . $this->getVersion());
		foreach ($this->storage->spectators as $player) {
        $this->connection->chatSendServerMessage('$fff» $fa0Welcome, this server uses $fff [Shootmania] Elite Spectator Stats$fa0!', $player->login);
		}
		
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
            case 'BeginTurn':
                $this->onXmlRpcEliteSpectatorBeginTurn(new JsonCallbacks\BeginTurn($json));
                break;
			case 'EndTurn':
                $this->onXmlRpcEliteSpectatorEndTurn(new JsonCallbacks\EndTurn($json));
                break;
    }	
    }
	
	    function onXmlRpcEliteSpectatorBeginTurn(JsonCallbacks\BeginTurn $content) {
		sleep(2);
		$this->MatchNumber = $this->getServerCurrentMatch($this->storage->serverLogin);
		 // Players and stuff
		if ($content->attackingPlayer == NULL){
		}
		else
		{
        $this->AtkPlayer = $this->getPlayerId($content->attackingPlayer->login);
		$queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces, (
SUM( player_maps.atkSucces ) / SUM( player_maps.atkrounds ) * 100
) AS AtkRatio
FROM player_maps
JOIN matches ON player_maps.match_id = matches.id
WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		$this->logger->Debug($queryCurrentMatchAtkPlayerStats);
		$this->db->execute($queryCurrentMatchAtkPlayerStats);
		
	
		$AtkRatioObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->AtkRatio;
		if ($AtkRatioObject == NULL){
		$this->AtkRatio = 0;
		}
		else 
		{
		$this->AtkRatio =  number_format($AtkRatioObject, 2, ',', '');
		}
		
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
		
		$queryCurrentMatchAtkPlayerStatsCaptures = "SELECT SUM( player_maps.captures ) AS Captures
		FROM player_maps
		JOIN matches ON player_maps.match_id = matches.id
		WHERE player_maps.player_id = " . $this->db->quote($this->AtkPlayer) . "
		AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		$this->logger->Debug($queryCurrentMatchAtkPlayerStatsCaptures);
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

		$this->logger->Debug($QueryShots_HitsAtkPlayer);
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
		$this->logger->Debug($queryAtkHitRatio);
		$this->db->execute($queryAtkHitRatio);
		
	
		$HitRatioObject = $this->db->execute($queryAtkHitRatio)->fetchObject()->HitRatio;
		if ($HitRatioObject == NULL){
		$this->HitRatio = 0;
		}
		else 
		{
		$this->HitRatio =  number_format($HitRatioObject, 2, ',', '');
		}
		
		$this->AttackPlayer = $content->attackingPlayer->name;
		$this->Team = $content->attackingPlayer->currentClan;
		$this->AtkPlayerLogin = $content->attackingPlayer->login;
		
		
		$this->ShowWidget($login = null, $this->AttackPlayer, $this->AtkRatio, $this->AtkRounds, $this->AtkSucces, $this->AtkCapture, $this->AtkShots, $this->AtkHits, $this->HitRatio, $this->Team, $this->AtkPlayerLogin);
		
		}
		}
	
	function ShowWidget($login = null, $AttackPlayerNick, $RatioAtk, $RoundsAtk, $RoundsSuccess, $CaptureAtk, $ShotsAtk, $HitsAtk, $RatioHit, $TeamNr, $AttackPlayerLogin){
	
		$blue = $this->connection->getTeamInfo(1);
        $red = $this->connection->getTeamInfo(2);
	$xml  = '<?xml version="1.0" encoding="utf-8"?>';
                $xml .= '<manialink version="1" background="1" navigable3d="0" id="AtkSpecDetails">';
                $xml .= '<frame posn="-60 -5 0" id="AtkSpecDetails">';
                $xml .= '<quad style="UiSMSpectatorScoreBig" substyle="PlayerSlotCenter" posn="-90 8 0" sizen="50 70"/>'; // MainWindow
                $xml .= '<quad posn="-80 -4 0.5" sizen="4.5 4.5" style="Icons64x64_1" substyle="TV" />';
                $xml .= '<label posn="-72 -5 0.5" sizen="100 0" textsize="1" text="'.$AttackPlayerNick.'"/>';
                $xml .= '<quad posn="-80 -10 0.5" sizen="4.5 4.5" style="BgRaceScore2" substyle="Points" />';
                $xml .= '<label posn="-72 -11 0.5" sizen="100 0" textsize="1" text="AtkRounds: '.$RoundsAtk.'"/>';
                $xml .= '<quad posn="-80 -16 0.5" sizen="4.5 4.5" style="BgRaceScore2" substyle="Podium" />';
                $xml .= '<label posn="-72 -17 0.5" sizen="100 0" textsize="1" text="AtkSucces: '.$RoundsSuccess.'"/>';
                $xml .= '<quad posn="-80 -22 0.5" sizen="4.5 4.5" style="ManiaplanetSystem" substyle="Statistics" />';
                $xml .= '<label posn="-72 -23 0.5" sizen="100 0" textsize="1" text="AtkRatio: '.$RatioAtk.' %"/>';
                $xml .= '<quad posn="-80 -28 0.5" sizen="4.5 4.5" style="Icons64x64_1" substyle="Finish" />';
                $xml .= '<label posn="-72 -29 0.5" sizen="100 0" textsize="1" text="Captures: '.$CaptureAtk.'"/>';
                $xml .= '<quad posn="-80 -34 0.5" sizen="4.5 4.5" style="Icons64x64_2" substyle="UnknownElimination" />';
                $xml .= '<label posn="-72 -35 0.5" sizen="100 0" textsize="1" text="Shots: '.$ShotsAtk.'"/>';
                $xml .= '<quad posn="-80 -40 0.5" sizen="4.5 4.5" style="Icons64x64_2" substyle="UnknownHit" />';
                $xml .= '<label posn="-72 -41 0.5" sizen="100 0" textsize="1" text="Hits: '.$HitsAtk.'"/>';
                $xml .= '<quad posn="-80 -46 0.5" sizen="4.5 4.5" style="Icons64x64_2" substyle="ServerNotice" />';
                $xml .= '<label posn="-72 -47 0.5" sizen="100 0" textsize="1" text="HitRatio: '.$RatioHit.' %"/>';
                if ($TeamNr == 1){
                if ($blue->clubLinkUrl) {
        $xml .= '<quad image="'.$this->Clublink($blue->clubLinkUrl).'" posn="-88 -26 0.5" sizen="7 7" />';   
        }
                else{
                $xml .= '<quad posn="-88 -26 0.5" sizen="7 7" style="Emblems" substyle="#1" />';
                }
                }
                if ($TeamNr== 2){
                if ($red->clubLinkUrl) {
        $xml .= '<quad image="'.$this->Clublink($red->clubLinkUrl).'" posn="-88 -26 0.5" sizen="7 7" />';   
        }
                else{
                $xml .= '<quad posn="-88 -26 0.5" sizen="7 7" style="Emblems" substyle="#1" />';
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
                    case CMlEvent::Type::MouseClick :
                    {
                        if (Event.ControlId == "FrameRules") ShowRules = !ShowRules;
                    }

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
                
                foreach ($this->storage->spectators as $login => $player) { // get players
        $this->connection->sendDisplayManialinkPage($player->login, $xml, 0, true, true);
        $this->connection->forceSpectatorTarget($player->login, $AttackPlayerLogin, 1, true);
        }
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
		
	return $xml->emblem_web;
	}
	
	function onXmlRpcEliteSpectatorEndTurn(JsonCallbacks\EndTurn $content) {
	
	$xml  = '<?xml version="1.0" encoding="UTF-8"?>';
  	$xml .= '<manialink id="AtkSpecDetails">';
  	$xml .= '</manialink>';
	$xml .= '</manialinks>';
	foreach ($this->storage->spectators as $login => $player) { // get players
        $this->connection->sendHideManialinkPage($player->login, $xml, 0, true, true);
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
