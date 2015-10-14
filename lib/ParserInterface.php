<?php
/**
 * Created by PhpStorm.
 * User: Cibermag
 * Date: 04.09.2015
 * Time: 18:25
 */

namespace yii\multiparser;


interface  ParserInterface {

    public  function setup();

    public  function read();


}