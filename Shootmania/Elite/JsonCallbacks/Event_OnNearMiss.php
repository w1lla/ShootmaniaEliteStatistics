<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

class Event_OnNearMiss extends \DedicatedApi\Structures\AbstractStructure {

    /** @var string */
    public $type = "";

    /** @var integer */
    public $damage = 0;

    /** @var integer */
    public $weaponNumber = 0;

    /** @var float */
    public $missDist = 0;

    /** @var Player */
    public $shooter;
    
    /** @var Player */
    public $victim;
    
    
}