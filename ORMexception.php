<?php

/**
 * Exception for ORM subsystem
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
class ORMexception extends ErrorException
{
    
    static private $echo_html = false;
    
    static public function echo_html ( $bool )
    {
        self::$echo_html = $bool;
    }
    
    public function __toString ( $additional_text = "" )
    {
        $res  = self::$echo_html ? "<div><hr/><b></b><br/><pre>\n" : "";
        $res .= parent::__toString() . "\n";
        $res .= $this->getMessage() . "\n";
        $res .= $additional_text == "" ? "" : $additional_text . "\n";
        $res .= "filename = " . $this->getFile() . " :: line = " . $this->getLine() . "\n";
        $res .= self::$echo_html ? "\n</pre><hr/></div>\n" : "";
        return $res;
    }

}

?>