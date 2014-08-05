<?php



/**
 * A row from the ORM MT (Master Table)
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
final class ORMmtRow extends ORMmt
{
    const OBJECT  = 1;
    const PAIRMAP = 2;

    /** @var string       */ public $classname      = null;
    /** @var int          */ public $id             = null;
    /** @var string       */ public $relname        = null;
    /** @var string       */ public $db_created_at  = null;
    /** @var string       */ public $db_modified_at = null;
    /** @var string       */ public $db_deleted_at  = null;
    /** @var ORMtrackable */ public $pointer        = null;

    static function init () {}

    // "index": for objects: int "$id" // for relations: string "$id|$relationname"
    function __construct ( $classname_or_pointer, $id = null, $relname = null ) {
        if ( $classname_or_pointer instanceof ORMtrackable ) { $classname = get_class($classname_or_pointer); } else { $classname = $classname_or_pointer; }
        $this->type = $relname === null ? self::OBJECT : self::PAIRMAP; $this->classname = $classname; $this->id = $id; $this->relname = $relname;
    }

    static function build_index ( $id, $relname ) { return $relname === null ? $id : $id . "|" . $relname; }
    static function get_id      ( $index )        { if ( is_int($index) ) { return $index; } else { list( $id,       ) = explode( "|", $index ); return $id;      } }
    static function get_relname ( $index )        { if ( is_int($index) ) { return null;   } else { list( , $relname ) = explode( "|", $index ); return $relname; } }
    public function index       ()                { return $this->type() === self::OBJECT ? $this->id : $this->id . "|" . $this->relname; }
    public function type        ()                { return $this->relname === null ? self::OBJECT : self::PAIRMAP; }
    public function merge_index ( $index )        { $this->id = self::get_id($index); $this->relname = self::get_relname($index); }
}
