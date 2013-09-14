<?php

namespace ManiaLivePlugins\Shootmania\Elite\JsonCallbacks;

use ManiaLivePlugins\Shootmania\Elite\Classes\RpcObject;

class EndMatch extends RpcObject {

    /** var integer */
    public $timestamp = 0;

    /** var integer */
    public $matchNumber = 0;

    /** var integer */
    public $matchWinnerClan = 0;

    /** var integer */
    public $clan1MapScore = 0;

    /** var integer */
    public $clan2MapScore = 0;

}

