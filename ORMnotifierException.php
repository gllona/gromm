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
class ORMnotifierException extends ORMexception
{
    
    const INFORMATIONAL                =  0;
    const NO_OR_BAD_METADATA_FOUND     =  1;
    const CLASS_NOT_FOUND              =  2;
    const OBJECT_NOT_FOUND             =  3;
    const MULTIPLE_OBJECTS_FOUND       =  4;
    const OBJECT_DELETED               =  5;
    const CONSTRUCTOR_FAILED           = 11;
    const ROLLBACK_FAILED              = 12;
    const NOT_WORKABLE                 = 13;
    const MODIFIED_LATER               = 21;
    const DELETED_LATER                = 22;
    const DELEGATE_CREATE_FAILED       = 31;
    const DELEGATE_FETCH_FAILED        = 32;
    const DELEGATE_SAVE_FAILED         = 33;
    const DELEGATE_DELETE_FAILED       = 34;
    const MASTER_TABLE_CREATE_FAILED   = 41;
    const MASTER_TABLE_FETCH_FAILED    = 42;
    const MASTER_TABLE_SAVE_FAILED     = 43;
    const MASTER_TABLE_DELETE_FAILED   = 44;
    const INCOMPATIBLE_RELATION_VALUES = 51;
    
    private function code2string ()
    {
        switch ( $this->getCode() ) {
            case self::INFORMATIONAL                : $str = "{informational message}"                                                   ; break;
            case self::NO_OR_BAD_METADATA_FOUND     : $str = "no metatada found for the given table"                                     ; break;
            case self::CLASS_NOT_FOUND              : $str = "no ORMobject subclass was found for the object's ORM master table record"  ; break;
            case self::OBJECT_NOT_FOUND             : $str = "object was not found in ORM master table"                                  ; break;
            case self::MULTIPLE_OBJECTS_FOUND       : $str = "multiple object with the same ID where found in the ORM master table"      ; break;
            case self::OBJECT_DELETED               : $str = "object is deleted"                                                         ; break;
            case self::CONSTRUCTOR_FAILED           : $str = "object constructor couldn't complete"                                      ; break;
            case self::ROLLBACK_FAILED              : $str = "couldn't rollback the ORM master table row for the object in the database" ; break;
            case self::NOT_WORKABLE                 : $str = "object is not workable for CRUD operations"                                ; break;
            case self::MODIFIED_LATER               : $str = "object was modified later and should be reloaded"                          ; break;
            case self::DELETED_LATER                : $str = "object was deleted later by another session"                               ; break;
            case self::DELEGATE_CREATE_FAILED       : $str = "delegate creation routine for the object failed"                           ; break;
            case self::DELEGATE_FETCH_FAILED        : $str = "delegate fetch routine for the object failed"                              ; break;
            case self::DELEGATE_SAVE_FAILED         : $str = "delegate save routine for the object failed"                               ; break;
            case self::DELEGATE_DELETE_FAILED       : $str = "delegate delete routine for the object failed"                             ; break;
            case self::MASTER_TABLE_CREATE_FAILED   : $str = "master table row creation failed"                                          ; break;
            case self::MASTER_TABLE_FETCH_FAILED    : $str = "master table row fetch for the object failed"                              ; break;
            case self::MASTER_TABLE_SAVE_FAILED     : $str = "master table row save for the object failed"                               ; break;
            case self::MASTER_TABLE_DELETE_FAILED   : $str = "master table row delete for the object failed"                             ; break;
            case self::INCOMPATIBLE_RELATION_VALUES : $str = "non-valid relation value(s) in proxy         "                             ; break;
            default                                 : $str = "NO_ORMEXCEPTION_MESSAGE_DEFINED";
        }
    }
    
    public function __toString ()
    {
        return parent::__toString( "notifying :: " . $this->code2string() );
    }

}

?>