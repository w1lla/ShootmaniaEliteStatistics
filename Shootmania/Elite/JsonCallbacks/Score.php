<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

class Score extends \DedicatedApi\Structures\AbstractStructure {

    /** @var integer */
    public $atkPoints = 0;
    /** @var integer */
    public $defPoints = 0;
    /** @var integer */
    public $goalAverage = 0;   
}