<?php



/**
 * Abstract base class for all trackable ORM objects: application objects and proxies
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
abstract class ORMtrackable extends ORMbaseObject
{



    ////////////////////////////////
    // CLASS AND INSTANCE ATTRIBUTES
    ////////////////////////////////



    /**
     * @var int
     */
    protected $orm_id              = null;
    /**
     * @var string
     */
    protected $orm_touched_at      = null;
    /**
     * @var bool
     */
    protected $orm_initialized     = false;   // only initialized objects (that calls this class' constructor) are allowed to do things
    /**
     * @var bool
     */
    protected $orm_cloned          = false;   // will be true for cloned objects and those can't be saved in DB
    /**
     * @var bool
     */
    protected $orm_autosave_at_end = false;   // if true (set at instance time) the object will be saved when destroyed



    ////////////////////////////////////////////////
    // INSTANCE ATTRIBUTES AND DERIVED INFO ACCESORS
    ////////////////////////////////////////////////



    /**
     * @return int
     */
    public final function orm_id () { return $this->orm_id; }



    /**
     * @return bool
     */
    public final function orm_is_valid ()
    {
        if ( !( $this->orm_initialized && ! $this->orm_cloned ) )
            return false;
        return true;
    }



}