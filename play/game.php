<?php
session_start();

require_once('../bootstrap.php');

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
} else {
    // Create a new game
    if (isset($_POST['gname'])) {
        $rname = $_POST['gname'];
        if (get_magic_quotes_gpc())
            $rname = stripslashes($rname);

        // Any special room name validation should go here
        $rname = htmlspecialchars($rname);

        $is_clean = 0;
        if (isset($_POST['clean']) && $_POST['clean'] != 0) {
            $is_clean = 1;
        }
        
        $password = null; // Default to null

        if (isset($_POST['use_password']) && $_POST['use_password'] != 0) {
            $password = $_POST['password'];
            $_SESSION['password'] = $password;
        }

        // Get caption_time value.  Check each value so we're not directly passing it into the DB (otherwise people could just set whatever time they wanted)
        $caption_time = $_POST['caption_time'];
        if ($caption_time == 45)
            $cap_time = 45;
        else if ($caption_time == 30)
            $cap_time = 30;
        else
            $cap_time = 60; // Default to 60 is it is any other value
        $new_room = $wtfGameState->createRoom($rname, $is_clean, $password, $cap_time);

        // Store these settings in cookies so they are set by default for next time
        $onemonth = 60 * 60 * 24 * 31 + time();
        setCookie('wtfsetting_clean', $is_clean, $onemonth, '/');
        setCookie('wtfsetting_caption_time', $cap_time, $onemonth, '/');

        // Now that the room is created, relocate to the new URL
        header("Location: game.php?gid=$new_room");
        exit;
    } else {
        // This is if someone just goes to game.php without specifying a game ID
        // posting a game name to create a new one.
        header("Location: ./");
        exit;
    }
}

function displayGamePage($game) {

    // Set sound settings (these are set by the Javascript in wtf.js whenever the user clicks the sound toggle button).
    if (isset($_COOKIE['wtfsetting_sound']) && $_COOKIE['wtfsetting_sound'] != 0) {
        $sound_img = "audio-on.png";
        $sound_enabled = "true";
    } else {
        $sound_img = "audio-mute.png";
        $sound_enabled = "false";
    }
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
        <head>
            <title>Words That Follow</title>
            <link rel="stylesheet" href="wtf.css?1" type="text/css" />
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
            <meta http-equiv="Content-Language" content="en" />
            <script type="text/javascript" src="js/ajax.js"></script>
            <script type="text/javascript" src="js/wtf.js?4"></script>
            <script type="text/javascript" src="js/timer.js"></script>
        </head>
        <body onload="initGame()" <?php if (isset($_SESSION['use_fb'])) echo 'class="fb"'; ?>>
            <div id="game_header">
                <div>
                    <a href="logout.php">Leave Game</a>
                    <a href="mailto:kenneth@wordsthatfollow.com">Send suggestion/bug report</a>
                    <img src="<?php echo $sound_img; ?>" onclick="toggleSound(this)" width="24" height="24" title="Toggle sound" />
                </div>
            </div>
            <div id="wtf_container">
                <ul id="player_list">
                    <li><i>none</i></li>
                </ul>

                <div id="game_container">
                    <div id="game_state_pregame">
                        <div>
                            <p>Waiting for more players...</p>
                        </div>
                    </div>
                    <div id="game_state_intro">
                        <p>Intro</p>
                    </div>
                    <form id="game_state_caption" action="" onsubmit="setCaption(); return false;" autocomplete="off">
                        <div id="caption_pic"></div>
                        <div id="caption_rule"></div>
                        <div>
                            <label for="caption_text">Caption:</label>
                            <input id="caption_text" maxlength="256" autocomplete="off" />
                            <input type="button" id="caption_button" onclick="setCaption()" value="Okay" />
                        </div>
                        <div><img id="caption_load_img" src="loading.gif" alt="Sending caption..." /></div>
                    </form>
                    <div id="game_state_vote">
                        <p>Select the best caption for this picture.  If you don't vote, you don't score.</p>
                        <p id="vote_rule"></p>
                        <ul id="caption_list">
                            <li>No captions</li>
                        </ul>
                        <div><img id="vote_load_img" src="loading.gif" alt="Sending vote..." /></div>
                    </div>
                    <div id="game_state_results">
                        <h2>Results</h2>
                        <ul id="results_list">
                            <li></li>
                        </ul>
                    </div>
                    <div id="game_state_gameover">
                        <h2>Game Over</h2>
                        <div id="game_winners">
                        </div>
                    </div>
                    <div id="timer_container">
                    </div>

                    <div id="pic_container">
                    </div>
                </div>
            </div>
            <div id="chat_container" class="large">
                <div id="chat_header">
                    <span id="chat_title" onclick="toggleChatSize();" title="Click to toggle chat height">
                        WTF Chat - <?php echo $game['name']; ?>
                    </span>
                    <button onclick="toggleChatPosition(this);">&gt;</button>
                </div>
                <div id="chat_content">
                </div>
                <div id="chat_input">
                    <form id="chat_form" action="" onsubmit="postChat(); return false;" autocomplete="off">
                        <div>
                            <input type="text" id="chat_text" maxlength="220" autocomplete="off" />
                        </div>
                    </form>
                </div>
            </div>
            <div id="error_content">
                <p id="error_text"></p>			
                <div class="footer"><a id="error_link" href="./">[Okay]</a></div>
            </div>
            <div id="debug_content">
                <div id="debug_checkback"></div>
                <div id="debug_messages"></div>
            </div>
            <form>
                <input type="hidden" id="room_id" value="<?php echo $game['room_id']; ?>" />
            </form>
            <script type="text/javascript" src="http://www.java.com/js/deployJava.js"></script>
            <script type="text/javascript">
                <?php if ($sound_enabled == 'true') : ?>
                var sound_applet_loaded = true;
                initSound();
                <?php else : ?>
                var sound_applet_loaded = false;
                <?php endif ?>
            </script>
        </body>
    </html>
    <?php
}
?>