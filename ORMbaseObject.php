<?php



/**
 * Abstract base class for all ORM-related objects (including proxies, relations, metadata, master table and others)
 *
 * This file should reside into the "application/core" directory
 *
 * @package     MisionMilagro
 * @author      Gorka G LLona <gorka@desarrolladores.logicos.org> <gllona@gmail.com>
 * @copyright   Copyright (c) 2014, Gorka G LLona
 * @license     GNU GPL license
 * @link        http://desarrollos.logicos.org/mision-milagro
 * @version     Version 1.0.0 beta 1
 * @since       Version 1.0.0 beta 1
 * @see         _docs subdirectory for phpdocs of the project
 */
abstract class ORMbaseObject extends BaseObject
{



    static function init ()
    {
        parent::init();
    }



    function __construct ()
    {
        parent::__construct();
    }



    function __destroy ()
    {
        parent::__construct();
    }



}