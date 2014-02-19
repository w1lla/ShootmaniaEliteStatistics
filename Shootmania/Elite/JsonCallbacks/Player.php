<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

class Player extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure {

    /** @var string */
    public $login = "";

    /** @var string */
    public $name = "";

    /** @var integer */
    public $currentClan = 0;

    /** @var integer */
    public $armor = 0;

    /** @var integer */
    public $armorMax = 0;

    /** @var bool */
    public $isTouchingGround = false;

    /** @var bool */
    public $isCapturing = false;

    /** @var bool */
    public $isInOffZone = false;

    /** @var Score */
    public $score = null;
    
    /** @var Array[][][] */
    public $position = array();
    
}