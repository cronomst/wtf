<?php

session_start();

require_once('../bootstrap.php');

define('MAX_NAME_LEN', 17);

if (isset($_POST['pname'])) {
    $pname = trim(Util::urldecodeUTF8($_POST['pname']));
    $fpname = htmlspecialchars($pname);
    $error = false;
    // TODO: Check for duplicate names so that someone can't use the name as another current player
    if (strlen($pname) > MAX_NAME_LEN) {
        $error = "'$fpname' is too long.  Enter a name that is no more than " . MAX_NAME_LEN . " characters.";
        $_SESSION['login_error'] = $error;
        header("Location: ../");
        exit;
    } else if (strlen($pname) < 1) {
        $error = "You must enter a name to play.";
        $_SESSION['login_error'] = $error;
        header("Location: ../");
        exit;
    }

    if ($error === false) {
        $_SESSION['player_name'] = $pname;
        // Store it in a cookie, too
        $onemonth = 60 * 60 * 24 * 31 + time();
        setCookie('player_name', $pname, $onemonth, "/");
        //writeLog($pname." joined.");
        header("Location: ./");
        exit;
    }
} else {
    // Session expired, so go back to the home page
    header("Location: ../");
    exit;
}

function writeLog($str) {
    $fh = fopen("_log.txt", "a");
    fwrite($fh, date(DATE_RSS) . " " . $str . "\n");
    fclose($fh);
}

?>