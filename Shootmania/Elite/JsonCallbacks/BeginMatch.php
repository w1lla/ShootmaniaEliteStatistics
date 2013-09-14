<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

use ManiaLivePlugins\Shootmania\Elite\Classes\RpcObject;

class BeginMatch extends RpcObject {

    /** var integer */
    public $timestamp = 0;

    /** var integer */
    public $matchNumber = 0;

}

