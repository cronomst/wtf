<?php
session_start();
require_once('../bootstrap.php');

$wtfGameState = GameState::getInstance();

if (isset($_GET['gid']) == false && isset($_POST['gid']) == false) {
    abort();
} else {

    $gid = $_GET['gid'] ? $_GET['gid'] : $_POST['gid'];
    $game = $wtfGameState->getRoom($gid);
    if ($game) {
        // Check if the password was posted.  If not, just show the password entry page.
        if (isset($_POST['password'])) {
            // Set the password field for the session
            $_SESSION['password'] = $_POST['password'];
            // Relocate to game
            header("Location: game.php?gid=$gid");
            exit;
        } else {
            // Invalid password
            abort();
        }
    } else {
        abort();
    }
}

function abort() {
    header('Location: ./');
    exit;
}
