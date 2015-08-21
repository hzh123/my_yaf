<?php
/**
 * This is API documentation for PhpStorm
 */

define('YAR_VERSION', 0);
define('YAR_OPT_PACKAGER', 1);
define('YAR_OPT_PERSISTENT', 2);
define('YAR_OPT_TIMEOUT', 4);
define('YAR_OPT_CONNECT_TIMEOUT', 8);

// Synchronous call
final class Yar_Client{
    public function __construct($server){}
    public function setOpt($opt, $value){}
}

// Concurrent call
final class Yar_Concurrent_Client{
    static public function call($uri, $method, $params=null, $callback=null, $opts=null){}
    static public function loop($callback=null, $errorCallback=null){}
}

// Server
final class Yar_Server{
    public function handle(){}
}