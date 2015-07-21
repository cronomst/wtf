<?php

class RandomImage {

    protected static function getFlickrInstance() {
        return new Flickr();
    }

    public static function getRandomImage($excluding = false) {
        // Get files from gameimages directory
        $files = array();
        $handle = opendir('./gameimages');
        if ($handle) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && substr($file, 0, 1) != ".") {
                    $files[] = $file;
                }
            }
            closedir($handle);
        }
        // Get Flicker image count
        $flickr = self::getFlickrInstance();
        
        $flickr_total = $flickr->getPhotoTotal();

        // If we happen to pick the filename specified in $excluding, pick again.
        do {
            $selected = rand(0, $flickr_total + count($files) - 1); // -1 because max index is count($files)-1
            if ($selected < count($files)) {
                // Select from local images
                $img_file = $files[$selected];
            } else {
                // Select from Flickr
                $img_file = "flickr:" . $flickr->getPhoto($selected - count($files) + 1);
            }
        } while (count($files) > 1 && $excluding !== false && $excluding == $img_file);

        return $img_file;
    }

}