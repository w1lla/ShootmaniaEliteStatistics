<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

class ScorePlayer extends \DedicatedApi\Structures\AbstractStructure {

    /** @var string */
    public $login = "";

    /** @var integer */
    public $currentClan = 0;

    /** @var integer */
    public $atkPoints = 0;

    /** @var integer */
    public $defPoints = 0;

}

