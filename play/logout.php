<?php

session_start();
require_once('../bootstrap.php');

$wtfGameState = GameState::getInstance();

if (isset($_SESSION['player_id']))
    $wtfGameState->removePlayer($_SESSION['player_id']);

header("Location: ./");
?>