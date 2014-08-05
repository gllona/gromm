<?php



/**
 * Encloses the MT and its behaviour
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
class ORMmt extends ORMbaseObject
{
    /** @var ORMmtRow[] */ static private $mt = array();

    static function init () {}

    function __construct () { throw new ORMexception("ORMmtRow creation is not allowed (should be used as a static structure)"); }

    // tries to get the id from the pointer; if possible (>0) the row will be saved in MT; if not, the row won't be saved and row->id will be null
    static public function build_new_row ( $classname = null, $relname = null, $db_created_at = null, $db_modified_at = null, $db_deleted_at = null, $pointer = null, $touch = true ) {
        /** @var ORMobject $ptr */ $ptr = $pointer;
        $id = $ptr !== null && $ptr->orm_id !== null ? $ptr->orm_id : 0;
        $row = new ORMmtRow( $classname, $id, $relname ); self::$mt[ $row->index() ] = $row;
        self::set_db_created_at ( $id, $db_created_at  );
        self::set_db_modified_at( $id, $db_modified_at );
        self::set_db_deleted_at ( $id, $db_deleted_at  );
        self::set_pointer       ( $id, $ptr            );
        if ( $ptr !== null && $touch ) { self::touch( $id, $relname ); }
        if ( $id == 0 ) { unset( self::$mt[ $row->index() ] ); $ptr->orm_id = null; } else { self::save_row( $row ); }
        return $row;
    }

    static public function save_row_by_index ( $id, $relname = null ) {
        if ( ! is_int($id) ) { throw new ORMexception("invalid id type"); }
        if ( $relname !== null && ! is_string($relname) ) { throw new ORMexception("invalid relname type"); }
        $row = self::fetch_row( $id, $relname ); if ( $row !== null ) { self::save_row( $row ); }
    }

    // save the row in the MT; tries to get the id from the pointer and the row; if possible, will do an update, else will do an insert; in both cases the heap-id's will be updated
    static public function save_row ( $row ) {
        /** @var ORMmtRow $rrow */ $rrow = $row;
        if ( ! $rrow instanceof ORMmtRow ) { throw new ORMexception("invalid row type"); }
        if ( $rrow->id === null && $rrow->pointer !== null && $rrow->pointer->orm_id !== null ) { $id = $rrow->id = $rrow->pointer->orm_id; } else { $id = $rrow->id; }
        if ( $rrow->type() === ORMmtRow::OBJECT ) {
            $attribs = array ( "classname" => $rrow->classname, null, "created_at" => $rrow->db_created_at, "modified_at" => $rrow->db_modified_at, "deleted_at" => $rrow->db_deleted_at );
            if ( $id === null ) {
                $rrow->db_created_at = self::microtime(); $attribs["created_at"] = $rrow->db_created_at;
                $res = self::$ci->db->insert(ORMconfiguration::RECORDS_TABLE, $attribs);
                if ( $res === null ) { throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" ); }
                if ( self::$ci->db->affected_rows() != 1 ) { throw new ORMnotifierException( "master table save (insert) for OBJECT of class $rrow->classname didn't complete", ORMnotifierException::MASTER_TABLE_SAVE_FAILED ); }
                $id = $rrow->id = self::$ci->db->insert_id();
                self::$ci->db->flush_cache();
                $rrow->id = $id; if ( $rrow->pointer !== null ) { $rrow->pointer->orm_id = $id; }
            } else {
                $rrow->db_modified_at = self::microtime(); $attribs["modified_at"] = $rrow->db_modified_at;
                $res = self::$ci->db->where("id", $id)->where("relname", NULL)->update(ORMconfiguration::RECORDS_TABLE, $attribs);
                if ( $res === null ) { throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" ); }
                if ( self::$ci->db->affected_rows() == 0 ) { throw new ORMnotifierException( "master table save (update) for OBJECT of class $rrow->classname, id = $id didn't complete", ORMnotifierException::MASTER_TABLE_SAVE_FAILED ); }
                self::$ci->db->flush_cache();
            }
        } else {   // PAIRMAP
            $attribs = array ( "classname" => $rrow->classname, "relname" => $rrow->relname, "created_at" => $rrow->db_created_at, "modified_at" => $rrow->db_modified_at, "deleted_at" => $rrow->db_deleted_at );
            if ( $id === null ) { throw new ORMnotifierException( "can't save a PAIRMAP in master table when id is null", ORMnotifierException::MASTER_TABLE_SAVE_FAILED ); }
            else {
                $rrow->db_modified_at = self::microtime(); $attribs["modified_at"] = $rrow->db_modified_at;
                $res = self::$ci->db->where("id", $id)->where("relname", $rrow->relname)->update(ORMconfiguration::RECORDS_TABLE, $attribs);
                if ( $res === null ) { throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" ); }
                if ( self::$ci->db->affected_rows() == 0 ) { throw new ORMnotifierException( "master table save (update) for PAIRMAP $rrow->relname of class $rrow->classname, id = $id didn't complete", ORMnotifierException::MASTER_TABLE_SAVE_FAILED ); }
                self::$ci->db->flush_cache();
            }
        }
    }

    // will do a re-fetch if index is already in MT; will return null if the index is not in MT
    static public function fetch_row ( $id, $relname = null ) {
        if ( ! is_int($id) || ( $relname !== null && ! is_string($relname) ) ) { throw new ORMexception("invalid id or relname type"); }
        $ptr = isset( self::$mt[ ORMmtRow::build_index($id, null) ] ) ? self::$mt[ ORMmtRow::build_index($id, null) ]->pointer : null;
        if ( $relname === null ) {   // OBJECT
            $rs = self::$ci->db->select("*")->from(ORMconfiguration::RECORDS_TABLE)->where("id", $id)->where("type", ORMmtRow::OBJECT)->limit(1)->get()->result();
            self::$ci->db->flush_cache();
            if ( $rs === null ) { throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" ); }
            if ( count($rs) == 0 ) { return null; } $rs0 = $rs[0];
            $row = self::build_new_row( $rs0->classname, null, $rs0->db_created_at, $rs0->db_modified_at, $rs0->db_deleted_at, $ptr, false );
        } else {   // RELATION
            $rs = self::$ci->db->select("*")->from(ORMconfiguration::RECORDS_TABLE)->where("id", $id)->where("relname", $relname)->where("type", ORMmtRow::PAIRMAP)->limit(1)->get()->result();
            self::$ci->db->flush_cache();
            if ( $rs === null ) { throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" ); }
            if ( count($rs) == 0 ) { return null; } $rs0 = $rs[0];
            $row = self::build_new_row( $rs0->classname, $rs0->relname, $rs0->db_created_at, $rs0->db_modified_at, $rs0->db_deleted_at, $ptr, false );
        }
        self::$mt[ $row->index() ] = $row;
        return $row;
    }

    // if this not throws an ORMnotifierException, then the row is valid and match the "safe mode" policies for objects and pairmaps
    static public function row_toll ( $id, $relname = null, $force_reload = true ) {
        if ( ! is_int($id) || ( $relname !== null && ! is_string($relname) ) ) { throw new ORMexception("invalid id $id or relname $relname type"); }
        $type = self::get_type( $id, $relname ); $classname = self::get_classname( $id ); $now = self::microtime();
        if ( $force_reload || ! self::is_set_row( $id, $relname ) || $type == ORMmtRow::OBJECT && ORMconfiguration::MT_SAFE_MODE_FOR_OBJECTS || $type == ORMmtRow::PAIRMAP && ORMconfiguration::MT_SAFE_MODE_FOR_PAIRMAPS ) { self::fetch_row( $id, $relname ); }
        if ( $type == ORMmtRow::PAIRMAP && ! in_array( $relname, ORMmd::get_all_fields_names( $classname, true ) ) ) { throw new ORMnotifierException( "fieldname $relname not found in class $classname for object id = $id", ORMnotifierException::NO_OR_BAD_METADATA_FOUND ); }
        if ( self::get_db_deleted_at( $id, $relname ) !== null ) { throw new ORMnotifierException( "object id $id, relname $relname was deleted; can't crud on it", ORMnotifierException::DELETED_LATER ); }
        if ( ORMmt::get_db_modified_at( $id, $relname ) > $now ) { throw new ORMnotifierException( "object id $id, relname $relname was modified; can't crud on it", ORMnotifierException::MODIFIED_LATER ); }
    }

    // will fetch the ORMobject or ORMpairmap if not loaded in the MT (will refetch if force_reload is true); returns null if not a valid ORMtrackable
    // will never throw ORMnotifierException
    static public function follow_pointer ( $id, $relname = null, $force_reload = false ) {
        if ( ! is_int($id) || ( $relname !== null && ! is_string($relname) ) ) { throw new ORMexception("invalid id $id or relname $relname type"); }
        try { self::row_toll( $id, $relname ); } catch (ORMnotifierException $ex) { return null; }
        $ptr = self::get_pointer( $id, $relname ); if ( ! $force_reload && $ptr !== null ) { return $ptr; }
        $type = self::get_type( $id, $relname ); if ( $type == ORMmtRow::OBJECT ) { try { $ptr = ORMobject::orm_instance_fetch( $id ); } catch (ORMnotifierException $ex) { return null; } self::set_pointer( $id, null, $ptr ); return $ptr; }
        try { $ptr = ORMpairmap::instance_fetch( $id, $relname ); } catch (ORMnotifierException $ex) { return null; } self::set_pointer( $id, null, $ptr ); return $ptr;
    }

    // tools
    static public function is_set_row                ( $id, $relname = null )                 { return isset( self::$mt[ ORMmtRow::build_index($id, $relname) ] ); }
    static public function unset_row                 ( $id, $relname = null )                 { if ( self::is_set_row( $id, $relname ) ) { unset( self::$mt[ self::get_index( $id, $relname ) ] ); } }
    static public function get_all_rows              ( $type )                                { $res = array(); foreach ( self::$mt as $row ) { if ( self::get_type( $row->id, $row->relname ) == $type ) { $res[ self::get_index( $row->id, $row->relname ) ] = $row; } } return $res; }
    static public function get_object_pairmaps_rows  ( $id )                                  { if ( ! self::is_set_row( $id ) ) { return null; } $res = array(); foreach ( self::$mt as $row ) { if ( $row->id == $id && self::get_type( $row->id, $row->relname ) == ORMmtRow::PAIRMAP ) { $res[ self::get_index( $row->id, $row->relname ) ] = $row; } } return $res; }
    static public function follow_pointer_as_pairmap ( $id, $relname, $force_reload = false ) { /** @var ORMpairmap $ptr */ $ptr = self::follow_pointer_as_pairmap( $id, $relname, $force_reload ); return $ptr; }
    static public function follow_pointer_as_object  ( $id,           $force_reload = false ) { /** @var ORMobject  $ptr */ $ptr = self::follow_pointer_as_pairmap( $id, null,     $force_reload ); return $ptr; }

    // getters - basic
    static public function get_row   ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::$mt[ ORMmtRow::build_index($id, null) ] : null; }

    // getters - derived
    static public function get_type  ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->type()          : null; }
    static public function get_index ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->index()         : null; }

    // getters - members
    static public function get_classname          ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->classname       : null; }
    static public function get_id                 ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->id              : null; }
    static public function get_relname            ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->relname         : null; }   // same as "return $relname"
    static public function get_db_created_at      ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->db_created_at   : null; }
    static public function get_db_modified_at     ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->db_modified_at  : null; }
    static public function get_db_deleted_at      ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->db_deleted_at   : null; }
    static public function get_touched_at         ( $id, $relname = null ) { return self::get_pointer($id, $relname) !== null ? self::get_pointer($id, $relname)->orm_touched_at : null; }
    static public function get_pointer            ( $id, $relname = null ) { return self::is_set_row($id, $relname) ? self::get_row($id, $relname)->pointer         : null; }
    static public function get_pointer_as_pairmap ( $id, $relname        ) { if ( self::get_type($id, $relname) == ORMmtRow::PAIRMAP ) { $ptr = self::get_pointer($id, $relname); /** @var ORMpairmap $ptr */ return $ptr; } return null; }
    static public function get_pointer_as_object  ( $id                  ) { if ( self::get_type($id, null    ) == ORMmtRow::OBJECT  ) { $ptr = self::get_pointer($id, null    ); /** @var ORMobject  $ptr */ return $ptr; } return null; }

    // setters
    static public function set_pointer ( $id, $relname = null, $object = null, $force = false ) {
        /** @var ORMobject $obj */ $obj = $object;
        if ( ! self::is_set_row($id, $relname) || $obj !== null && ! $obj instanceof ORMtrackable ) { throw new ORMexception("invalid index(id+relname) or object/pointer"); }
        $go = $force || $obj === null ? null : self::get_row($id, $relname)->type();
        if ( $go == ORMmtRow::OBJECT && $id !== $obj->orm_id ) { throw new ORMexception("mismatched index'es between MT and object"); }
        elseif ( $go == ORMmtRow::PAIRMAP ) { try { ORMmd::get_field_specs( self::get_row($id, $relname)->classname, $relname ); } catch (ORMnotifierException $ex) { throw new ORMexception("non-congruent metadata for MT relation"); } }
        self::get_row( $id, $relname )->pointer = $obj;
    }
    static public function set_db_created_at ( $id, $relname = null, $microtime = "NOW()" ) {
        if ( $microtime == "NOW()" ) { $microtime = self::microtime(); }
        if ( ! self::is_set_row( $id, $relname ) || ! self::is_microtime($microtime) ) { throw new ORMexception("invalid index or microtime"); }
        self::get_row( $id, $relname )->db_created_at= $microtime;
    }
    static public function set_db_modified_at ( $id, $relname = null, $microtime = "NOW()" ) {
        if ( $microtime == "NOW()" ) { $microtime = self::microtime(); }
        if ( ! self::is_set_row( $id, $relname ) || ! self::is_microtime($microtime) ) { throw new ORMexception("invalid index or microtime"); }
        self::get_row( $id, $relname )->db_modified_at= $microtime;
    }
    static public function set_db_deleted_at ( $id, $relname = null, $microtime = "NOW()" ) {
        if ( $microtime == "NOW()" ) { $microtime = self::microtime(); }
        if ( ! self::is_set_row( $id, $relname ) || ! self::is_microtime($microtime) ) { throw new ORMexception("invalid index or microtime"); }
        self::get_row( $id, $relname )->db_deleted_at= $microtime;
    }
    static public function touch ( $id, $relname ) {
        if ( ! self::is_set_row( $id, $relname ) || self::get_row( $id, $relname )->pointer === null ) { throw new ORMexception("invalid index or not-set pointer"); }
        self::get_row( $id, $relname )->pointer->orm_touched_at = self::microtime();
    }
}
