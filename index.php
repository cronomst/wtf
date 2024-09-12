<?php
// HOME PAGE INDEX

session_start();

require_once('bootstrap.php');

$wtfConfig = Configuration::getInstance();
$wtfGameState = GameState::getInstance();

define("MAX_NAME_LEN", 17);

$player_count = $wtfGameState->getTotalPlayers();
$maxTotalPlayers = $wtfConfig->get('game.max_total_players');
$pname = '';
if (isset($_COOKIE['player_name'])) {
    $pname = $_COOKIE['player_name'];
    $escapedPlayerName = htmlspecialchars($pname, ENT_QUOTES, 'UTF-8');
} else {
    $escapedPlayerName = "";
}

$news = new News();

// Check if we're coming from Facebook (this will result in the body getting class="fb")
if (isset($_SESSION['use_fb']) || isset($_GET['use_fb']) && $_GET['use_fb']) {
    $_SESSION['use_fb'] = 1;
    $use_facebook = true;
} else
    $use_facebook = false;

// Check for login errors
if (isset($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
} else
    $login_error = "";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title>Words That Follow</title>
        <link rel="stylesheet" href="index.css" type="text/css" />
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
            <meta name="keywords" content="wtf,words,that,follow,multiplayer,caption,game,get,the,picture,gtp,captioning,side-quest,sidequest,mst3k,mystery,science,theater,3000" />
            <meta name="description" content="A real-time multiplayer photo captioning game.  Each round, you caption the picture within the time limit and then vote for the best.  The player with the most votes wins." />
            <meta name="Generator" content="Notepad++" />
            <meta name="author" content="Kenneth Shook" />
            <meta name="google-site-verification" content="UQdTbTgzSc1JCbikRZIp_q-93_Gr2mZhfNWFRs8HbUA" />
    </head>
    <body<?php if ($use_facebook) echo ' class="fb"'; ?>>
        <div id="container">
            <div id="header">
                <div id="logo">
                    <img src="logo.gif" alt="Words That Follow" />
                </div>
                <div id="player_count">
                    Players Online: <?php echo "$player_count / $maxTotalPlayers"; ?>
                </div>
            </div>
            <div id="main">
                <noscript>
                    <p>You MUST have Javascript enabled to use this site.</p>
                    <p>Javascript is current DISABLED for your browser.</p>
                </noscript>
                <div id="play_now">
                    <h3>Play Now</h3>
                    <form method="POST" action="./play/login.php">
                        <div>
                            <label for="pname">Choose a name:</label>
                            <input name="pname" maxlength="<?php echo MAX_NAME_LEN; ?>" value="<?php echo $escapedPlayerName; ?>" />
                            <input type="submit" value="Play" />
                            <div id="login_error"><?php echo $login_error; ?></div>
                        </div>
                    </form>
                </div>
                <div id="recent_news">
                    <h3>Recent Updates</h3>
                    <?php echo $news->getNews(0, 2) ?>
                </div>
                <div id="game_desc">
                    <h3>How to Play</h3>
                    <p>
                        Words That Follow is a multiplayer game of photo captioning.  Getting started is easy!
                        Choose a name for yourself and then create or join an existing game.  Once the game has 3
                        or more players, everyone will be shown a photograph.  You will have a limited amount of time
                        in which to come up with a creative, witty, or brain-meltingly terrible caption for the
                        photo.
                    </p>
                    <p>Next, you are shown a list of all the captions that the rest of the players wrote.
                        All you have to do is choose the best (or the one that hurts the least).  Make sure you
                        choose one, though!  If you don't vote, you can't get any points yourself.
                    </p>
                    <p>At the end of the round, all the votes are counted and you gain points for each
                        vote you received.  Initially, votes are worth 1 point each.  After 3 rounds, they
                        increase to 2 points.  In the final round, each vote is worth 3 points!
                    </p>
                    <p>In addition to the extra points in the final round, players are also presented with
                        a special rule that should be followed.  These can include writing a caption that sounds
                        like a Public Service Announcement or creating a caption that fits a given acronym.
                    </p>
                    <p>So, why are you still reading?  Start playing!</p>
                </div>
            </div>
            <div id="footer">
                Copyright &copy; 2011-2015 Kenneth Shook.  All images are property of their respective owners.
            </div>
        </div>
    </body>
</html>