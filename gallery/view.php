<?php
require_once('../play/lib/dbheader.php');
require_once('../play/lib/featuredcaps.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $info = getFeaturedCaptionInfo($id);
    if ($info != null)
        displayCaption($info);
    else {
        // TODO: Display error saying the caption could not be found.
        echo "Cannot find caption ID $id.";
    }
}

function displayCaption($info) {
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
        <head>
            <title>Words That Follow - Player Caption by <?php echo $info['name']; ?></title>
            <!-- <link rel="stylesheet" href="index.css" type="text/css" /> -->
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
            <style type="text/css">
                body { text-align: center; }
                img { border: 1px solid black; }
            </style>
        </head>
        <body>
            <img src="../play/gameimages/<?php echo $info['image']; ?>" alt="Captioned image" />
            <p><?php echo $info['caption'] . ' (' . $info['name'] . ')'; ?></p>
        </body>
    </html>

    <?php
}
?>