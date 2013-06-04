<?php
/**
 * @copyright   Copyright (c) 2013 W1lla (http://www.tmrankings.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $: 0.1a
 * @author      $Author: $: Willem 'W1lla' van den Munckhof
 * @date        $Date: $: 2013-06-04
 */
 
require '/libraries/autoload.php'; // Manialive Autoload Library 


echo('<meta http-equiv="refresh" content="3">'); // AutoRefresh every 3 seconds... Don't worry :P It uses for now about the 14-15 MB.
use DedicatedApi\Connection; // Connection is used for Factory to start the connection like a servercontroller
use ManiaLive\Utilities\Logger; // Logger is for manialive-debug.log


$host = '127.0.0.1'; // Host IP
$port = 5000; // Host IP Port
$timeout = 5; // Timeout time is 5 microseconds
$user = 'SuperAdmin'; // SuperAdmin UserName
$password = 'SuperAdmin'; // SuperAdmin Password
$data = Connection::factory($host, $port, $timeout, $user, $password); // Connection Factory line to get the Connection ready for the Controller/Logger

$data->enableCallbacks(true); // Enable Callbacks
$data->executeCallbacks(); // Execute Callbacks 
usleep(5000000); // usleep for 5 secs due to the Callbacks logging.
foreach($data->executeCallbacks() as $call) // Checks foreach Callback returned but for now it searches for the ModeScriptCallback
		{
		if($call[0] == 'ManiaPlanet.ModeScriptCallback'){ // Callbacks sent from the Gamemodes
		onModeScriptCallback($call[1][0],$call[1][1]);
		}
		if($call[0] == 'ManiaPlanet.ModeScriptCallbackArray'){ // Callbacks sent from the Gamemodes
		onModeScriptCallback($call[1][0],$call[1][1][0]);
		}
		}
				
function onModeScriptCallback($param1, $param2) { //ModeScriptCallback is here Called with param1 the LibXmlRpc_Callback and Param2 as Number or data
		//Logger::getLog('EliteStats')->write($param2[0]); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
		 //var_dump($param1);
		switch($param1) {
			case 'LibXmlRpc_BeginMatch':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_BeginMatch', $param2[0]);//An array with the number of the match
				return;
			case 'LibXmlRpc_BeginMap':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_BeginMap', $param2[0]);//An array with the number of the map
				return;
			case 'LibXmlRpc_BeginSubmatch':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_BeginSubmatch', $param2[0]);//An array with the number of the submatch
				return;
			case 'LibXmlRpc_BeginRound':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2[0]); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_BeginRound', $param2);//An array with the number of the round
				return;
			case 'LibXmlRpc_BeginTurn':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2[0]); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_BeginTurn', $param2);//An array with the number of the turn
				return;
			case 'LibXmlRpc_EndTurn':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_EndTurn', $param2[0]);//An array with the number of the turn
				return;	
			case 'LibXmlRpc_EndRound':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_EndRound', $param2[0]);//An array with the number of the round
				return;	
			case 'LibXmlRpc_EndSubmatch':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_EndSubmatch', $param2[0]);//An array with the number of the submatch
				return;	
			case 'LibXmlRpc_EndMap':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_EndMap', $param2[0]);//An array with the number of the map
				return;	
			case 'LibXmlRpc_EndMatch':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_EndMatch', $param2[0]);//An array with the number of the match
				return;	
			case 'LibXmlRpc_Rankings':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_Rankings', $param2);//An array with a list of players with their scores
				return;		
			case 'LibXmlRpc_OnShoot':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_OnShoot', $param2);//An array with the login of the shooter and the number of the weapon used
				return;
			case 'LibXmlRpc_OnHit':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_OnHit', $param2);//An array with the login of the shooter, the login of the victim, the amount of damage, the weapon number and the shooter points (the +1, +2, etc displayed in game when you hit someone)
				return;
			case 'LibXmlRpc_OnNearMiss':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_OnNearMiss', $param2);//An array with the login of the shooter, the login of the victim, the weapon number and the distance of the miss
				return;
			case 'LibXmlRpc_OnArmorEmpty':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_OnArmorEmpty', $param2);//An array with the login of the shooter, the login of the victim, the amount of damage, the weapon number and the shooter points (the +1, +2, etc displayed in game when you hit someone)
				return;
			case 'LibXmlRpc_OnCapture':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_OnCapture', $param2);//An array with the login of the players who were on the pole plate when it was captured
				return;	
			case 'LibXmlRpc_OnPlayerRequestRespawn':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_LibXmlRpc_OnPlayerRequestRespawn', $param2);//An array with the login of the player requesting the respawn
				return;
			case 'Royal_UpdatePoints':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_Royal_UpdatePoints', $param2);//An array with the login of the player scoring the point, the type of points and the number of points The points can be of three types: Hit, Pole or Survival
				return;	
			case 'Royal_SpawnPlayer':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_Royal_SpawnPlayer', $param2);//Two type of spawn -> 0: normal, 1: early . The normal spawn is the first spawn of the player, while an early respawn is when a player respawn during a round before the pole is captured.
				return;
			case 'TimeAttack_OnStart':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_TimeAttack_OnStart', $param2);//An array with the login of the player
				return;
			case 'TimeAttack_OnCheckpoint':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_TimeAttack_OnCheckpoint', $param2);//An array with the login of the player and its time at the checkpoint time = ms
				return;
			case 'TimeAttack_OnRestart':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_TimeAttack_OnRestart', $param2);//An array with the login of the player and its time at the time of the restart This callback is sent when a player asks to respawn or is eliminated. The time is in milliseconds.
				return;
			case 'Joust_OnReload':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_Joust_OnReload', $param2);//An array with the login of the player reloading
				return;
			case 'Joust_SelectedPlayers':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_Joust_SelectedPlayers', $param2);//An array with the logins of the two players who'll play the round.
				return;
			case 'Joust_RoundResult':
			Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
			Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//('mode_Joust_RoundResult', $param2);//An array with the logins and score of each player of the round
				return;
			case 'BeginMatch':
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2);
				//('mode_Elite_BeginMatch', $decode_param2);//This callback is sent at the beginning of each match
				return;
			case 'BeginMap':
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->MapNumber);
				//('mode_Elite_BeginMap', $decode_param2);//This callback is sent at the beginning of each map
				return;
			case 'BeginWarmup':
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2);
				//('mode_Elite_BeginWarmUp', $decode_param2);//This callback is sent at the beginning of the warm up.
				return;
			case 'EndWarmup':
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2);
				//('mode_Elite_EndWarmUp', $decode_param2);//This callback is sent at the end of the warm up.
				return;
			case 'BeginSubmatch':// This callback is sent at the beginning of each submatch
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2);
				//('mode_Elite_BeginSubmatch', $decode_param2);
				return;

				case 'BeginTurn':// This callback is sent at the beginning of each turn
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_BeginTurn', $decode_param2);
				return;

				case 'OnCapture':// This callback is sent when the attacker captured the pole
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_OnCapture', $decode_param2);
				return;
				
				case 'OnHit':// This callback is sent when a player hit another player
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_OnHit', $decode_param2);
				return;
				
				case 'OnArmorEmpty':// This callback is sent when a player reaches 0 armor (eliminated by another player, falling in an offzone)
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_OnArmorEmpty', $decode_param2);
				return;

				case 'OnPlayerRequestRespawn':// This callback is sent when a player requests a respawn.
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_OnPlayerRequestRespawn', $decode_param2);
				return;
				
				case 'OnShoot':// This callback is sent when a player shoots.
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_OnShoot', $decode_param2);
				return;
				
				case 'OnNearMiss':// This callback is sent when the attacker shot a Laser near a defender without hitting him.
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_OnNearMiss', $decode_param2);
				return;
				
				case 'EndTurn':// This callback is sent at the end of each turn.
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_EndTurn', $decode_param2);
				return;
				
				case 'EndMap'://This callback is sent at the end of each map.
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_EndMap', $decode_param2);
				return;
				
				case 'EndSubmatch'://This callback is sent at the end of each submatch.
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_EndSubmatch', $decode_param2);
				return;
				
				case 'EndMatch'://This callback is sent at the end of each match.
				$decode_param2 = json_decode($param2);
				Logger::getLog('EliteStats')->write($param1); //Logger manialive-debug is used for the moment to give the LibXmlRpc_Callback first
				Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				//var_dump($decode_param2->DefendingClan);
				//('mode_Elite_EndMatch', $decode_param2);
				return;
				
		}
	}
	
	
?>