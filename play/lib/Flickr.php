<?php

/**
 * Class to access Flickr API to get random photos 
 */
class Flickr
{
    /**
     * @var string URL for WTF group
     */
    protected $_wtfUrl;
    /**
     * @var string URL fot CT group
     */
    protected $_ctUrl;
    /**
     * @var int total number of images in WTF group
     */
    protected $_wtfTotal = 0;
    /**
     * @var int total number of images on CT group
     */
    protected $_ctTotal = 0;
    /**
     * @var string Flickr API key
     */
    protected $_apiKey;

    public function __construct()
    {
        $config = Configuration::getInstance();
        $this->_apiKey = urlencode($config->get('flickr.api_key'));

        $this->_wtfUrl = $this->_getGroupURL(urlencode($config->get('flickr.wtf_group_id')));
        $this->_ctUrl = $this->_getGroupURL(urlencode($config->get('flickr.ct_group_id')));
    }

    /**
     * Constructs the photo search URL for the given Flickr group ID
     * 
     * @param string $group_id
     * @return string url
     */
    protected function _getGroupURL($group_id)
    {
        $url = 'https://api.flickr.com/services/rest/?'
                . 'method=flickr.photos.search'
                . '&api_key=' . $this->_apiKey
                . '&group_id=' . $group_id;

        return $url;
    }

    /**
     *  Returns the total number of photos in the two Flickr groups
     */
    public function getPhotoTotal()
    {
        $this->_wtfTotal = $this->_getPhotoTotalFrom($this->_wtfUrl);
        $this->_ctTotal = $this->_getPhotoTotalFrom($this->_ctUrl);

        return $this->_wtfTotal + $this->_ctTotal;
    }

    /**
     * Returns the total number of photos in the given Flickr search URL
     * 
     * @param string $url
     * @return int 
     */
    protected function _getPhotoTotalFrom($url)
    {
        $MAX_RESULTS = 4000; // Hard limit set by Flickr
        $total = 0;
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $xml = curl_exec($ch);
            $doc = new SimpleXMLElement($xml);
            $total = $doc->photos[0]['total'];
            if ($total > $MAX_RESULTS)
                $total = $MAX_RESULTS;
        } catch (Exception $e) {
            // If this fails for any reason, just return a total of 0.
            $total = 0;
        }

        return $total;
    }

    /**
     * Returns the URL for a photo based on the given number.  The number also
     * determines which group the photo is chosen from.
     * 
     * @param int $num
     * @return string url of photo
     */
    public function getPhoto($num)
    {
        $photo = null;

        // Use previous totals if we already have them
        if ($this->_wtfTotal == 0 && $this->_ctTotal == 0) {
            $total_wtf = $this->_getPhotoTotalFrom($this->_wtfUrl);
            $total_ct = $this->_getPhotoTotalFrom($this->_ctUrl);
        } else {
            $total_wtf = $this->_wtfTotal;
            $total_ct = $this->_ctTotal;
        }

        if ($num > $total_wtf) {
            // Get from Caption This group
            $photo = $this->_getPhotoFrom($this->_ctUrl, $num - $total_wtf);
        } else {
            // Get from WTF group
            $photo = $this->_getPhotoFrom($this->_wtfUrl, $num - $total_ct);
        }

        return $photo;
    }

    /**
     * Gets the URL for the given photo number from the group specified by the
     * given URL.
     * 
     * @param string $url photo search URL
     * @param int $num photo ID number in this group
     * @return string url of photo 
     */
    protected function _getPhotoFrom($url, $num)
    {
        $ch = curl_init($url . "&per_page=1&page=$num");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $xml = curl_exec($ch);

        $photos = $this->_getPhotos($xml);
        return $photos[0];
    }

    /**
     * Given an XML response from photos.search, returns an array of URLs for the photos.
     * 
     * @param string $xml
     * @return string[]
     */
    protected function _getPhotos($xml)
    {
        $result = array();

        $doc = new SimpleXMLElement($xml);
        foreach ($doc->photos[0]->photo as $photo) {
            $farm_id = $photo['farm'];
            $server_id = $photo['server'];
            $id = $photo['id'];
            $secret = $photo['secret'];
            $result[] = "http://farm$farm_id.static.flickr.com/$server_id/" . $id . "_$secret" . ".jpg";
        }

        return $result;
    }

    /**
     * Converts the given long flickr URL into its short version
     * 
     * @param string $long_url
     * @return string short url
     */
    public function getShortURL($long_url)
    {
        $parts = split("/", $long_url);
        $last = count($parts) - 1;
        $id = split("_", $parts[$last]);
        $num = $id[0];
        $short_url = "http://flic.kr/p/" . $this->_baseEncode($num);
        return $short_url;
    }

    /**
     * Performs base64 encoding on the given number
     * 
     * @param int $num
     * @return string base64-encoded number
     */
    protected function _baseEncode($num)
    {
        $alphabet = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
        $base_count = strlen($alphabet);
        $encoded = '';
        while ($num >= $base_count) {
            $div = $num / $base_count;
            $mod = ($num - ($base_count * intval($div)));
            $encoded = $alphabet[$mod] . $encoded;
            $num = intval($div);
        }

        if ($num)
            $encoded = $alphabet[$num] . $encoded;

        return $encoded;
    }
}
