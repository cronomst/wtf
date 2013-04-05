<?php

session_start();
require_once('../bootstrap.php');

if (isset($_GET['action']))
    doGetAction($_GET['action']);

/**
 * Perform given action
 * 
 * @param string $action 
 */
function doGetAction($action) {
    $wtfGameState = GameState::getInstance();
    $requestFields = array_merge($_GET, $_SESSION);
    $wtfResponse = new WTFResponse();

    $player = $wtfGameState->getPlayer($_SESSION['player_id']);
    
    if ($player === false) {
        $wtfResponse->error = 'You have been removed from this game or the '
                . 'connection to the server has been lost.  Try refreshing, '
                . 'or press Okay to return to the room selection page.';
    } else {
        $gameController = new GameController($requestFields, $wtfResponse);
        $gameController->invokeAction($action);
    }

    header("Content-Type: text/xml; charset=utf-8");
    echo $wtfResponse->toXML();
}
