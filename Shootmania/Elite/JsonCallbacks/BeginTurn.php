<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

use ManiaLivePlugins\Shootmania\Elite\Classes\RpcObject;

class BeginTurn extends RpcObject {

    /** var integer */
    public $timestamp = 0;

    /** var integer */
    public $turnNumber = 0;

    /** var integer */
    public $startTime = 0;

    /** var integer */
    public $endTime = 0;

    /** var integer */
    public $poleTime = 0;

    /** var integer */
    public $attackingClan = 0;

    /** var integer */
    public $defendingClan = 0;

    /** @var Player */
    public $attackingPlayer = null;

    /** @var ScorePlayer[] */
    public $defendingPlayers = array();

}

