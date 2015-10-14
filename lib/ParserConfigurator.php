<?php
namespace yii\multiparser;

class ParserConfigurator
{

    protected static  $configuration;

    public static function getConfiguration($extension, $parameter)
    {
        self::setConfiguration();

        if (!isset( self::$configuration[$extension] )){
            throw new \ErrorException( "Parser do not maintain file with extension  {$extension}");
        }
        if (!isset( self::$configuration[$extension][$parameter] )){
            throw new \ErrorException( "Parser configurator do not have settings for {$parameter} parameter");
        }

        return self::$configuration[$extension][$parameter];
    }

 protected static function setConfiguration()
    {

       self::$configuration = require(__DIR__ . '/config.php');

    }



}