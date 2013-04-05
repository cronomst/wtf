<?php
// GAME LIST INDEX
session_start();

require_once('../bootstrap.php');

$dbAdapter = DbAdapter::getInstance();
$wtfGameState = GameState::getInstance();
$wtfConfig = Configuration::getInstance();

if (isset($_SESSION['player_name']) == false) {
    header("Location: login.php");
    exit;
}

// Set the default value for the "clean" checkbox
if (isset($_COOKIE['wtfsetting_clean']) && $_COOKIE['wtfsetting_clean'] != 0)
    $clean_checked = ' checked="checked"';
else
    $clean_checked = '';

// Take the opportunity to do some cleaning and get rid of idle rooms and players
cleanup($wtfGameState);

/* Clear out idle players and rooms */

function cleanup($wtfGameState) {
    $wtfGameState->removeInactivePlayers();
    $wtfGameState->removeInactiveRooms();
}

/* Used to check if the given time matches the stored caption time from the cookie so that we can select the previously choosen caption time limit */

function getSelectedCapTime($tm) {
    if (isset($_COOKIE['wtfsetting_caption_time']) && $tm == $_COOKIE['wtfsetting_caption_time'])
        return ' selected="selected"';
    else
        return '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title>Words That Follow - Choose a Game</title>
        <link rel="stylesheet" href="roomlist.css" type="text/css" />
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <script type="text/javascript">
            /* <![CDATA[ */
		
            function passwordChecked(chk) {
                var text = document.getElementById("password");
				
                if (chk.checked) {
                    text.disabled = false;
                    text.value = "";
                    text.focus();
                } else {
                    text.value = "No password";
                    text.disabled = true;
                }
            }
			
            function showPasswordDialog(gid) {
                var gid_input = document.getElementById("gid");
                var pwd_input = document.getElementById("pwd_password");
                gid_input.value = gid;
                pwd_input.value = "";
                document.getElementById("password_dialog").style.display="block";
                pwd_input.focus();
            }
            function hidePasswordDialog() {
                document.getElementById("password_dialog").style.display="none";
            }
		
            /* ]]> */
        </script>
    </head>
    <body <?php if (isset($_SESSION['use_fb'])) echo 'class="fb"'; ?>>
        <div id="main">
            <div id="header">
                <a href="../" id="logo">
                    <img src="../logo.gif" alt="Words That Follow" />
                </a>
            </div>
            <div id="create_game">
                <form action="game.php" method="post">
                    <fieldset>
                        <legend>Create a game</legend>
                        <p>
                            <label for="gname">Room name:</label>
                            <input name="gname" maxlength="45" id="gname" value="<?php echo htmlspecialchars($_SESSION['player_name'], ENT_QUOTES, 'UTF-8') . "'s game"; ?>"/><br />
                            <input type="checkbox" name="clean" value="1" title="If checked, some course language will be replaced with breakfast items"<?php echo $clean_checked; ?> />
                            <label for="clean" title="If checked, some course language will be replaced with breakfast items">Clean chat/captions</label><br />
                            <input type="checkbox" name="use_password" value="1" onchange="passwordChecked(this);" />
                            <label for="use_password">Password protected</label>
                            <input type="text" maxlength="45" id="password" name="password" value="No password" disabled="disabled" /><br />
                            <label for="caption_time">Caption time limit: </label>
                            <select name="caption_time">
                                <option value="60">1 minute</option>
                                <option value="45"<?php echo getSelectedCapTime(45); ?>>45 seconds</option>
                                <option value="30"<?php echo getSelectedCapTime(30); ?>>30 seconds</option>
                            </select>
                        </p>
                        <p>
                            <input type="submit" value="Create new game" />
                        </p>
                    </fieldset>
                </form>
            </div>
            <!-- Show room list here -->
            <fieldset>
                <legend>Join a game</legend>
                <ul id="room_list">
                    <?php
                    $rooms = $wtfGameState->getRooms();
                    $count = $dbAdapter->numRows($rooms);
                    if ($count == 0)
                        echo "<li>There are no games running.  You should create one.</li>";
                    while ($row = $dbAdapter->fetchAssoc($rooms)) {
                        $room_id = $row['room_id'];
                        $room_name = $row['name'];
                        $room_caption_time = $row['caption_time'];
                        $pcount = $wtfGameState->countPlayersInRoom($row['room_id']);
                        $class = "";
                        if ($row['clean'])
                            $class .= "clean ";
                        if ($row['password'])
                            $class .= "use_pw ";

                        $desc = "<span class=\"roomname\">$room_name</span> ($pcount/"
                                . $wtfConfig->get('game.max_players')
                                . ") <span class=\"timelimit\">$room_caption_time seconds</span>";

                        if ($pcount < $wtfConfig->get('game.max_players')) {
                            if ($row['password']) { // Password
                                ?>
                                <li class="<?php echo $class; ?>">
                                    <a href="#" onclick="showPasswordDialog(<?php echo $room_id; ?>)"><img src="join_btn.png" /></a>
                                <?php echo $desc; ?>
                                </li>
                                <?php
                            } else { // No password
                                ?>
                                <li class="<?php echo $class; ?>">
                                    <a href="game.php?gid=<?php echo $room_id; ?>"><img src="join_btn.png" /></a>
                                <?php echo $desc; ?>
                                </li>
                                <?php
                            }
                        } else { // Full game
                            ?>
                            <li class="full">FULL - <?php echo $desc; ?></li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </fieldset>
        </div>
        <div id="password_dialog">
            <form id="password_form" method="POST" action="password.php" autocomplete="off">
                <label for="password">Enter password:</label>
                <input name="gid" id="gid" type="hidden" />
                <input id="pwd_password" name="password" maxlength="45" autocomplete="off" />
                <input type="submit" value="Okay" />
                <button name="cancel_btn" onclick="hidePasswordDialog(); return false;">Cancel</button>
            </form>
        </div>
    </body>
</html>