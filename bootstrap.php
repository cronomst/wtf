<?php

bootstrap();

/**
 * Words That Follow bootstrap 
 */
function bootstrap() {

    /*
     * Set up autoloader
     */
    $includePath = get_include_path();
    $includePath .= PATH_SEPARATOR . dirname(__FILE__)
            . PATH_SEPARATOR . dirname(__FILE__) . $ds . '/play'
            . PATH_SEPARATOR . dirname(__FILE__) . $ds . '/play/lib'
            . PATH_SEPARATOR . dirname(__FILE__) . $ds . '/play/lib/Models';
            
    set_include_path($includePath);
    spl_autoload_register('_autoload');

    /*
     * Initalize configuration from config.ini based on the environment
     */
    $config = parse_ini_file(dirname(__FILE__) . '/_private/config.ini', true);
    $environment = getenv('WTF_ENV') ? getenv('WTF_ENV') : 'production';

    Configuration::createConfig($config, $environment);
    
}

function _autoload($class)
{
    include $class . '.php';
}
