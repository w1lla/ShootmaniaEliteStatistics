<?php

/**
  Name: Willem 'W1lla' van den Munckhof
  Date: 12/11/2013
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
        $AtkPlayer = $this->getPlayerId($content->attackingPlayer->login);
		$queryCurrentMatchAtkPlayerStats = "SELECT SUM( player_maps.atkrounds ) AS atkrounds, SUM( player_maps.atkSucces ) AS atkSucces, (
SUM( player_maps.atkSucces ) / SUM( player_maps.atkrounds ) * 100
) AS AtkRatio
FROM player_maps
JOIN matches ON player_maps.match_id = matches.id
WHERE player_maps.player_id = " . $this->db->quote($AtkPlayer) . "
AND player_maps.match_id = " . $this->db->quote($this->MatchNumber) . "";
		$this->logger->Debug($queryCurrentMatchAtkPlayerStats);
		$this->db->execute($queryCurrentMatchAtkPlayerStats);
		
		$AtkRatioObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->AtkRatio;
		if ($AtkRatioObject == NULL){
		$AtkRatio = 0;
		}
		else 
		{
		$AtkRatio =  number_format($AtkRatioObject, 2, ',', '');
		}
		
		$AtkRoundsObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkrounds;
		if ($AtkRoundsObject == NULL){
		$AtkRounds = 0;
		}
		else 
		{
		$AtkRounds = $AtkRoundsObject;
		}
		
		$AtkSuccesObject = $this->db->execute($queryCurrentMatchAtkPlayerStats)->fetchObject()->atkSucces;
		if ($AtkSuccesObject == NULL){
		$AtkSucces = 0;
		}
		else 
		{
		$AtkSucces = $AtkSuccesObject;
		}
		$AttackPlayer = $content->attackingPlayer->name;
		$Team = $content->attackingPlayer->currentClan;
		
		$blue = $this->connection->getTeamInfo(1);
        $red = $this->connection->getTeamInfo(2);
		
		$xml  = '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= '<manialink version="1" background="1" navigable3d="0" id="AtkSpecDetails">';
		$xml .= '<frame posn="-60 -20 0">';
		$xml .= '<quad image="http://static.maniaplanet.com/manialinks/lobbies/background.png" posn="-100 0 0" sizen="50 40"/>'; // MainWindow
		$xml .= '<quad posn="-86 -4 0.5" sizen="4.5 4.5"style="Icons64x64_2" substyle="ServerNotice" />'; // Icon before Player
		$xml .= '<label posn="-81 -5 0.5" sizen="17.3 0" textsize="1" text="'.$content->attackingPlayer->name.'"/>'; // Player NickName
		$xml .= '<quad posn="-86 -14 0.5" sizen="4.5 4.5"style="Icons64x64_2" substyle="LaserHit" />'; // Icon before Rounds
		$xml .= '<label posn="-81 -15 0.5" sizen="17.3 0" textsize="1" text="AtkRounds: '.$AtkRounds.'"/>'; // Atk Rounds per Match
		$xml .= '<quad posn="-86 -19 0.5" sizen="4.5 4.5"style="Icons64x64_2" substyle="LaserElimination" />'; // Icon before AtkSucces
		$xml .= '<label posn="-81 -20 0.5" sizen="17.3 0" textsize="1" text="AtkSucces: '.$AtkSucces.'"/>';
		$xml .= '<quad posn="-86 -24 0.5" sizen="4.5 4.5"style="Icons64x64_2" substyle="UnknownHit" />'; // Icon before AtkSucces
		$xml .= '<label posn="-81 -25 0.5" sizen="17.3 0" textsize="1" text="AtkRatio: '.$AtkRatio.' %"/>';
		if ($Team == 1){
		if ($blue->clubLinkUrl) {
        $xml .= '<quad image="'.$this->Clublink($blue->clubLinkUrl).'" posn="-95 -16 0.5" sizen="7 7" />';   
        }
		else{
		$xml .= '<quad posn="-95 -16 0.5" sizen="7 7" style="Emblems" substyle="#1" />';
		}
		}
		if ($Team == 2){
		if ($red->clubLinkUrl) {
        $xml .= '<quad image="'.$this->Clublink($red->clubLinkUrl).'" posn="-95 -16 0.5" sizen="7 7" />';   
        }
		else{
		$xml .= '<quad posn="-95 -16 0.5" sizen="7 7" style="Emblems" substyle="#1" />';
		}
		}


		$xml .= '</frame>';
		$xml .= '</manialink>';
		
		foreach ($this->storage->spectators as $login => $player) { // get players
        $this->connection->sendDisplayManialinkPage($player->login, $xml, 0, true, true);
		$this->connection->forceSpectatorTarget($player->login, $content->attackingPlayer->login, 1, true);
        }
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