<?php

// Require autoload file to use ( namespace and use ) keywords without include files
require_once __DIR__ . '/../vendor/autoload.php';

// Get WebSocket Class
use Valerian\ChatApp\Server\WebSocket;

// Run WebSocket Server
WebSocket::start();

?>