<?php

class Util {

    /**
     * Performs a UTF-8 compatible URL decode on the given string
     * 
     * @param string $str
     * @return string
     */
    public static function urldecodeUTF8($str) {
        $str = preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($str));
        return html_entity_decode($str, null, 'UTF-8');
    }
}

?>