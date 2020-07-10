<?php

session_start();

require_once('../bootstrap.php');

define('MAX_NAME_LEN', 17);

$data = json_decode(file_get_contents('php://input'), true);
$pname = $data['playerName'];
$roomId = $data['roomId'];
$response = new stdClass();

try {
    $validatedPlayerName = validatePlayerName($pname);
    setPlayerNameSessionAndCookie($validatedPlayerName);
    $response->result = "ok";
    $response->location = "./game.html";
} catch (Exception $e) {
    $response->result = "error";
    $response->error = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
exit(0);

function validatePlayerName($name) {
    $pname = trim(Util::urldecodeUTF8($name));
    if (get_magic_quotes_gpc()) {
        $pname = stripslashes($pname);
    }
    $htmlPlayerName = htmlspecialchars($pname);
    $error = false;
    if (strlen($pname) > MAX_NAME_LEN) {
        $error = "'$htmlPlayerName' is too long.  Enter a name that is no more than " . MAX_NAME_LEN . " characters.";
        throw new Exception($error);
    } else if (strlen($pname) < 1) {
        $error = "You must enter a name to play.";
        throw new Exception($error);
    }
}

function setPlayerNameSessionAndCookie($name) {
    $_SESSION['player_name'] = $name;
    $onemonth = 60 * 60 * 24 * 31 + time();
    setCookie('player_name', $name, $onemonth, "/");
}

function joinGame($gid) {
    $wtfConfig = Configuration::getInstance();
    $wtfGameState = GameState::getInstance();
    $wtfChat = Chat::getInstance();

    define("MAX_PLAYERS", $wtfConfig->get('game.max_players')); // Max players per room
    define("MAX_TOTAL_PLAYERS", $wtfConfig->get('game.max_total_players'));

    if (isset($_SESSION['player_id']))
        $pid = $_SESSION['player_id'];
    else
        $pid = null;
    if (isset($_SESSION['player_name'])) {
        $pname = $_SESSION['player_name'];
        $escapedPlayerName = htmlspecialchars($pname, ENT_QUOTES, 'UTF-8');
    } else {
        header("Location: login.php");
        exit;
    }

    if (isset($_GET['gid'])) {
        $gid = $_GET['gid'];
        if ($wtfGameState->roomExists($gid)) {

            // Load game data
            $game = $wtfGameState->getRoom($gid);
            // Load player data
            if ($pid == null)
                $p = false;
            else
                $p = $wtfGameState->getPlayer($pid); // Get the player with this pid.  If successfully, the player is already in the table.

            if ($game['password'] !== null &&
                    (isset($_SESSION['password']) == false || $_SESSION['password'] != $game['password'])) {
                header("Location: password.php?gid=$gid");
                exit;
            }

            // Check if player is already in the room
            if ($pid != null && $wtfGameState->isPlayerInRoom($pid, $gid)) {
                displayGamePage($game);
                exit;

                // Check if room is full.
            } elseif ($wtfGameState->countPlayersInRoom($gid) >= MAX_PLAYERS) {
                echo "This room is full.  Choose another one or create one of your own. ";
                echo '<a href="./">Back</a>';
                exit;
            } else if ($wtfGameState->getTotalPlayers() >= MAX_TOTAL_PLAYERS) {
                echo "Words That Follow currently only supports a maximum of "
                . MAX_TOTAL_PLAYERS . " players and, too bad for you, you are "
                . "lucky number " . (MAX_TOTAL_PLAYERS + 1) . ".  "
                . "Try again in a little while.<br />"
                . '<a href="./">Back</a>';
                exit;
            } elseif ($p) {
                $wtfGameState->joinRoom($pid, $gid); // If the player exists,  set their room_id to this one.
                $wtfChat->addChat($gid, "<b>$escapedPlayerName has joined the game.</b>", $pname);
            } else {
                // Create the player since they don't already exist
                $pid = $wtfGameState->createPlayer($pname, $gid);
                $wtfChat->addChat($gid, "<b>$escapedPlayerName has joined the game.</b>", $pname);
            }
            // Display the game
            displayGamePage($game);
        } else {
            // Go to room selection page
            header("Location: ./");
            exit;
        }
    }
}
