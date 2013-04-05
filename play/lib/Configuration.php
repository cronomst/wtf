<?php

class Configuration {

    /**
     * Singleton instance of configuration
     * 
     * @var Configuration
     */
    protected static $_instance;
    
    /**
     * Configuration array
     * 
     * @var string[]
     */
    protected $_config;

    /**
     * Sets the configuration
     * 
     * @param string[] $configArray configuration array
     * @param string $environment environment name
     * @return Configuration
     */
    public static function createConfig($configArray, $environment) {
        $config = new Configuration();
        $config->_setConfig($configArray[$environment]);
        self::$_instance = $config;
        return $config;
    }
    
    /**
     * Get the configuration instance
     * 
     * @return Configuration
     */
    public static function getInstance()
    {
        return self::$_instance;
    }
    
    /**
     * Gets the config value for the given key.
     * 
     * @param string $key
     * @return string if the key exists in the configuration, otherwise false
     */
    public function get($key)
    {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        }
        
        return false;
    }
    
    /**
     * Sets the configuration array
     * 
     * @param string[] $configArray 
     */
    protected function _setConfig($configArray)
    {
        $this->_config = $configArray;
    }

}