<?php
// CAPTION GALLERY INDEX
require_once('../play/lib/dbheader.php');
require_once('../play/lib/featuredcaps.php');

function writePage() {
    if (isset($_GET['p']))
        $page = $_GET['p'];
    else
        $page = 0;

    $result = getFeaturedCaptionPage($page);

    while ($row = mysql_fetch_assoc($result)) {
        $id = $row['id'];
        $image = "../play/gameimages/" . $row['image'];
        $caption = $row['caption'];
        $name = $row['name'];
        $viewlink = "view.php?id=$id";

        echo "<div><a href=\"$viewlink\"><img src=\"$image\" alt=\"\" /></a>";
        echo "<p>$caption (<i>$name</i>)</p></div>";
    }

    $total_pages = getFeaturedCaptionPageCount();
    echo "<p> Page: ";
    for ($i = 0; $i < $total_pages; $i++) {
        if ($i == $page)
            echo ($i + 1) . " ";
        else
            echo "<a href=\"./?p=$i\">" . ($i + 1) . "</a> ";
    }
    echo "</p>";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title>Words That Follow - Caption Gallery</title>
        <link rel="stylesheet" href="../index.css" type="text/css" />
    </head>
    <body>
        <div id="container">
            <div id="header">
                <div id="logo">
                    <a href="../">
                        <img src="../logo.gif" alt="Words That Follow" />
                    </a>
                </div>
            </div>
            <div id="main">
                <?php writePage(); ?>
            </div>
        </div>
    </body>
</html>