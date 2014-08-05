<?php



/**
 * Abstract base class for all objects
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
abstract class BaseObject
{

    // Class autoloading settings
    //
    const CLASS_AUTOLOAD_DEFAULT_APPPATH = "./";      // with a trailing slash
    const CLASS_AUTOLOAD_DEFAULT_SUBDIR  = "core/";   // with a trailing slash



    // CodeIgniter environment flag
    //
    const CODEIGNITER_IMPLEMENTATION = true;

    // TODO make this work w/o CodeIgniter (2dev4release)
    //
    /**/// @var CI_Controller */   // commented out to avoid PhpStorm warnings
    static public $ci;

    

    /**
     * Class static initializer
     * 
     * Defines class autoloader
     * 
     * @access	public
     * @return	void
     */
    static function init ()
    {
        if ( ! defined("APPPATH") ) {
            define( "APPPATH", self::CLASS_AUTOLOAD_DEFAULT_APPPATH );
        }
        if ( ! defined("SUBDIR") ) {
            define( "SUBDIR", self::CLASS_AUTOLOAD_DEFAULT_SUBDIR);
        }
        // set class autoloader
        spl_autoload_register(
            function ($class) {
                $filename = APPPATH . SUBDIR . $class . '.php';
                include $filename;
            }
        );
        // get the main CI controller instance
        self::$ci = &get_instance();
    }



    /**
     * Class instance constructor
     * 
     * (nothing to do)
     * 
     * @access	public
     */
    function __construct ()
    {
        // parent::__construct();   // no parent class
    }



    /**
     * Class instance destructor
     *
     * (nothing to do)
     *
     * @access	public
     */
    function __destroy ()
    {
        // parent::__construct();   // no parent class
    }



    /**
     * @var int $offset
     * @return null|string
     */
    static protected function getCalleeClass ( $offset = 0 ) {
        $ex = new Exception();
        $st = $ex->getTrace();
        $t1 = $st[ 2 + $offset ];
        $cc = isset( $t1['class'] ) ? $t1['class'] : null;
        return $cc;
    }



    /**
     * @param   array[] $array
     * @param   int|string $key_column
     * @return  array[]
     * @throws  Exception
     */
    static public function array2map ( $array, $key_column = 0 )
    {
        if ( ! is_array($array) ) {
            throw new Exception("array2map: first argument should be array");
        }
        if ( ! is_int($key_column) && ! is_string($key_column) ) {
            throw new Exception("array2map: optional second argument should be integer or string");
        }
        $map = array();
        foreach ( $array as $row ) {
            if ( ! is_array($row) ) {
                throw new Exception("array2map: each supplied row should be an array");
            }
            if ( ! isset($row[$key_column]) ) {
                throw new Exception("array2map: key_column exceeds an row's column count");
            }
            $map[$row[$key_column]] = $row;
        }
        return $map;
    }



    /**
     * @return  string
     */
    static public function microtime ()
    {
        list( $millisecs, $secs ) = explode( ' ', microtime() );
        return $secs . substr( $millisecs, 1 );
    }



    static public function is_microtime ( $microtime )
    {
        return preg_match( '^[0-9]{10}\.[0-9]{8}$', $microtime ) === 1;
    }
    


    //////////////////////////
    // NOT USED IN THIS SYSTEM
    //////////////////////////



    static private $errstr = '';
    
    static public function orm_get_errstr ()
    {
        return self::$errstr;
    }
    
    static public function orm_reset_errstr ()
    {
        return self::orm_set_errstr( null );
    }
    
    // returns last errstr value
    //
    static public function orm_set_errstr ( $str, $chain = false, $chain_separator = "\n" )
    {
        $last_value = self::orm_get_errstr();
        if ( $chain )
            self::$errstr .= $chain_separator . $str;
        else
            self::$errstr = $str;
        return $last_value;
    }

    
    
}