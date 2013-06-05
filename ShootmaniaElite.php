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
$mysql_host = '127.0.0.1';
$mysql_user = 'root';
$mysql_pw = 'usbw';
$mysql_port = '3307';
$mysql_db = 'elitev1';
$link = mysql_connect($mysql_host, $mysql_user, $mysql_pw);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
// make foo the current db
$db_selected = mysql_select_db($mysql_db, $link);
if (!$db_selected) {
    die ('Can\'t use foo : ' . mysql_error());
}
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
		//var_dump($param1);
		 global $data, $link;
		switch($param1) {
			case 'LibXmlRpc_BeginMatch':
				return;
			case 'LibXmlRpc_BeginMap':
				return;
			case 'LibXmlRpc_BeginSubmatch':
				return;
			case 'LibXmlRpc_BeginRound':
				return;
			case 'LibXmlRpc_BeginTurn':
				return;
			case 'LibXmlRpc_EndTurn':
				return;	
			case 'LibXmlRpc_EndRound':
				return;	
			case 'LibXmlRpc_EndSubmatch':
				return;	
			case 'LibXmlRpc_EndMap':
				return;	
			case 'LibXmlRpc_EndMatch':
				return;	
			case 'LibXmlRpc_Rankings':
			//Logger::getLog('EliteStats')->write($param2); // Logger manialive-debug is used for the moment to give the Data or Array of the Callback
				return;		
			case 'LibXmlRpc_OnShoot':
				return;
			case 'LibXmlRpc_OnHit':
				return;
			case 'LibXmlRpc_OnNearMiss':
				return;
			case 'LibXmlRpc_OnArmorEmpty':
				return;
			case 'LibXmlRpc_OnCapture':
				return;	
			case 'LibXmlRpc_OnPlayerRequestRespawn':
				return;
			case 'Royal_UpdatePoints':
				return;	
			case 'Royal_SpawnPlayer':
				return;
			case 'TimeAttack_OnStart':
				return;
			case 'TimeAttack_OnCheckpoint':
				return;
			case 'TimeAttack_OnRestart':
				return;
			case 'Joust_OnReload':
				return;
			case 'Joust_SelectedPlayers':
				return;
			case 'Joust_RoundResult':
				return;
			case 'BeginMatch':
				$decode_param2 = json_decode($param2);
				$MatchNumber = $decode_param2->MatchNumber;
				$StartTime = $decode_param2->Timestamp;
				$BlueName = $data->getTeamInfo(1)->name;
				$RedName = $data->getTeamInfo(2)->name;
				$MatchName = ''.$RedName.' vs '.$BlueName.'';
				$BeginMatchQuery = "INSERT INTO  `matches` (
				`id` ,
				`name` ,
				`team1` ,
				`team2` ,
				`startTime` ,
				`endTime`
				)
				VALUES (
				".$MatchNumber.",  '".$MatchName."',  '".$RedName."',  '".$BlueName."', ".$StartTime." ,  '0');";
				// Perform Query
				$result = mysql_query($BeginMatchQuery, $link);
				// Check result
				// This shows the actual query sent to MySQL, and the error. Useful for debugging.
				if (!$result) {
				$message  = 'Invalid query: ' . mysql_error() . "\n";
				$message .= 'Whole query: ' . $BeginMatchQuery;
				Logger::getLog('EliteMySQLError')->write($message); // Used for MYSQL Issues but this is just for Primary issues....
				}
				return;
			case 'BeginMap':
				$decode_param2 = json_decode($param2);
				$MapNum = $decode_param2->MapNumber;
				$map = $data->getCurrentMapInfo();
				$MapName = $map->name;
				//Logger::getLog('EliteError')->write($MapName); // Used for EliteErrors... (Bad code)
				return;
			case 'BeginWarmup':
				$decode_param2 = json_decode($param2);
				return;
			case 'EndWarmup':
				$decode_param2 = json_decode($param2);
				return;
			case 'BeginSubmatch':// This callback is sent at the beginning of each submatch
				$decode_param2 = json_decode($param2);
				return;
			case 'BeginTurn':// This callback is sent at the beginning of each turn
				$decode_param2 = json_decode($param2);
				$TurnNumber = $decode_param2->TurnNumber;
				$AtkClan = $decode_param2->AttackingClan;
				$DefClan = $decode_param2->DefendingClan;
				$AtkPlayerLogin = $decode_param2->AttackingPlayer->Login;
				$AtkPlayerNickName = $decode_param2->AttackingPlayer->Name;
				//Logger::getLog('EliteError')->write($AtkPlayerNickName); // Used for EliteErrors... (Bad code)
				return;
			case 'OnCapture':// This callback is sent when the attacker captured the pole
				$decode_param2 = json_decode($param2);
				$PlayerCapturedLogin = $decode_param2->Event->Player->Login;
				$PlayerCapturedNickname = $decode_param2->Event->Player->Name;
				$PlayerCapturedClan = $decode_param2->Event->Player->CurrentClan;
				return;
			case 'OnHit':// This callback is sent when a player hit another player
				$decode_param2 = json_decode($param2);
				$HitDamage = $decode_param2->Event->Damage; // Damage (Which isnt used??)
				$HitWeapon = $decode_param2->Event->WeaponNum; // WeaponNum 1 = Laser 2 = Rocket 3 = Nucleus 5 = Arrow
				$HitMissDist = $decode_param2->Event->MissDist; // MissDist (apparently 0)
				$HitDist = $decode_param2->Event->HitDist; // HitDist * 100 cm // mm 2.34118
				$HitShooterLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$HitShooterNickName = $decode_param2->Event->Shooter->Name; // Shooter NickName
				$HitShooterCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan
				$HitVictimLogin = $decode_param2->Event->Victim->Login; // Victim Login
				$HitVictimName = $decode_param2->Event->Victim->Name; // Victim NickName
				$HitVictimCurrentClan = $decode_param2->Event->Victim->CurrentClan; // Victim CurrentClan
				return;	
			case 'OnArmorEmpty':// This callback is sent when a player reaches 0 armor (eliminated by another player, falling in an offzone)
				$decode_param2 = json_decode($param2);
				$ArmorEmptyWeaponNum = $decode_param2->Event->WeaponNum; // WeaponNum see sidenotes;
				$ArmorEmptyShooterLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$ArmorEmptyShooterNickName = $decode_param2->Event->Shooter->Name; // Shooter NickName
				$ArmorEmptyShooterCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan see sidenotes;
				$ArmorEmptyVictimLogin = $decode_param2->Event->Victim->Login; // Victim login
				$ArmorEmptyVictimName = $decode_param2->Event->Victim->Name; // Victim NickName
				$ArmorEmptyVictimCurrentClan = $decode_param2->Event->Victim->CurrentClan; // Victim CurrentClan see sidenotes;
				return;
			case 'OnPlayerRequestRespawn':// This callback is sent when a player requests a respawn.
				$decode_param2 = json_decode($param2);
				return;
			case 'OnShoot':// This callback is sent when a player shoots.
				$decode_param2 = json_decode($param2);
				$ShootWeaponNum = $decode_param2->Event->WeaponNum; // WeaponNum see sidenotes;
				$ShootLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$ShootNickName = $decode_param2->Event->Shooter->Name; // Shooter NickName
				$ShootCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan
				return;
			case 'OnNearMiss':// This callback is sent when the attacker shot a Laser near a defender without hitting him.
				$decode_param2 = json_decode($param2);
				$NearMissCM = $decode_param2->Event->MissDist; //$Distance * 100; // mm / cm // M
				$NearMissShooterLogin = $decode_param2->Event->Shooter->Login; // Shooter Login
				$NearMissShooterNickName = $decode_param2->Event->Shooter->Name; // Shooter Name
				$NearMissShooterCurrentClan = $decode_param2->Event->Shooter->CurrentClan; // Shooter CurrentClan
				Logger::getLog('EliteError')->write($NearMissCM); // Used for EliteErrors... (Bad code)
				return;
			case 'EndTurn':// This callback is sent at the end of each turn.
				$decode_param2 = json_decode($param2);
				$TurnNumber = $decode_param2->TurnNumber; // TurnNumber
				$TurnAttackingClan = $decode_param2->AttackingClan; // AttackClan see sidenotes;
				$TurnDefendingClan = $decode_param2->DefendingClan; // DefendClan see sidenotes;
				$TurnAttackPlayerLogin = $decode_param2->AttackingPlayer->Login; // AtkPlayer Login
				$TurnAttackPlayerNickName = $decode_param2->AttackingPlayer->Name; // AtkPlayer NickName
				$TurnAttackPlayerCurrentClan = $decode_param2->AttackingPlayer->CurrentClan; // AtkPlayer CurrentClan see sidenotes;
				$TurnWinnerClan = $decode_param2->TurnWinnerClan; // Winner of the Turn see sidenotes;
				$TurnWinType = $decode_param2->WinType; // WinType; See the script
				$Clan1RoundScore = $decode_param2->Clan1RoundScore; // Clan1RoundScore which is Blue Score
				$Clan2RoundScore = $decode_param2->Clan2RoundScore; // Clan2RoundScore which is Red Score
				$Clan1MapScore = $decode_param2->Clan1MapScore; // MapScore for Blue
				$Clan2MapScore = $decode_param2->Clan2MapScore; // MapScore for Red
				$PlayerBlue1Login = $decode_param2->ScoresTable[0]->Login; // Blue Player Login
				$PlayerBlue2Login = $decode_param2->ScoresTable[1]->Login; // Blue Player Login
				$PlayerBlue3Login = $decode_param2->ScoresTable[2]->Login; // Blue Player Login
				$PlayerRed1Login = $decode_param2->ScoresTable[3]->Login; // Red Player Login
				$PlayerRed2Login = $decode_param2->ScoresTable[4]->Login; // Red Player Login
				$PlayerRed3Login = $decode_param2->ScoresTable[5]->Login; // Red Player Login
				$PlayerBlue1CurrentClan = $decode_param2->ScoresTable[0]->CurrentClan; // Player Blue CurrentClan
				$PlayerBlue2CurrentClan = $decode_param2->ScoresTable[1]->CurrentClan; // Player Blue CurrentClan
				$PlayerBlue3CurrentClan = $decode_param2->ScoresTable[2]->CurrentClan; // Player Blue CurrentClan
				return;
			case 'EndMap'://This callback is sent at the end of each map.
				$decode_param2 = json_decode($param2);
				$MapNumber = $decode_param2->MapNumber; // MapNumber
				$MapWinnerClan = $decode_param2->MapWinnerClan; // WinnerClan see sidenotes;
				$Clan1MapScore = $decode_param2->Clan1MapScore; // Score of Blue on Map
				$Clan2MapScore = $decode_param2->Clan2MapScore; // Score of Red on Map
				return;
			case 'EndSubmatch'://This callback is sent at the end of each submatch.
				$decode_param2 = json_decode($param2);
				$SubmatchNumber = $decode_param2->SubmatchNumber; // SubmatchNumber
				return;
			case 'EndMatch'://This callback is sent at the end of each match.
				$decode_param2 = json_decode($param2);
				$MatchWinnerClan = $decode_param2->MatchWinnerClan; // Match winner Clan
				$Clan1MapScore = $decode_param2->Clan1MapScore;
				$Clan2MapScore = $decode_param2->Clan2MapScore;
				return;
				
		}
	}

				mysql_close($link);	
?>
