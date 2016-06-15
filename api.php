<?php
spl_autoload_register('autoload');
function autoload($classname) {
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $classname . '.php');
}

$server = new Server();
$server->output($server->run());
