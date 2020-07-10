<?php

session_start();

require_once('../bootstrap.php');

$data = json_decode(file_get_contents('php://input'), true);
$gameName = $data['gameName'];
$captionTime = validateCaptionTime($data['captionTime']);
$isClean = validateIsClean($data['isClean']);
$password = $data['password'];

$newGameId = createNewGame($gameName, $captionTime, $isClean, $password);

header('Content-Type: application/json');
$response = new stdClass();
$response->result = "ok";
$response->gameId = $newGameId;
echo json_encode($response);

function createNewGame($gameName, $captionTime = 60, $isClean = 0, $password = null) {
    $wtfGameState = GameState::getInstance();

    if (get_magic_quotes_gpc()) {
        $gameName = stripslashes($gameName);
    }

    // Any special room name validation should go here
    $gameName = htmlspecialchars($gameName);

    $newGameId = $wtfGameState->createRoom($gameName, $isClean, $password, $captionTime);

    // Store these settings in cookies so they are set by default for next time
    //storeCookies($isClean, $captionTime);
    // Now that the room is created, relocate to the new URL
    return $newGameId;
}

function validateCaptionTime($captionTime)
{
    if ($captionTime == 30) {
        return 30;
    }
    if ($captionTime == 45) {
        return 45;
    }
    return 60;
}

function validateIsClean($isClean)
{
    if ($isClean) {
        return 1;
    }
    return 0;
}

function storeCookies($isClean, $captionTime) {
    $onemonth = 60 * 60 * 24 * 31 + time();
    setCookie('wtfsetting_clean', $isClean, $onemonth, '/');
    setCookie('wtfsetting_caption_time', $captionTime, $onemonth, '/');
}
