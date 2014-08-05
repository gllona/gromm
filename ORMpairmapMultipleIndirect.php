<?php



/**
 * Class for ORM proxy of type MULTIPLE_INDIRECT
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
class ORMpairmapMultipleIndirect extends ORMpairmap
{



    static function init ()
    {
        parent::init();
    }

    public function __construct ( $classname, $fieldname, $orm_id, $full_field_spec )
    {
        parent::__construct( $classname, $fieldname, $orm_id, $full_field_spec );
    }



    ///////////////////////////////////////////////////////////////////////
    // MANAGERS FOR REFERENCES STRUCTURE AS PARTICULARIZED BY EACH SUBCLASS
    ///////////////////////////////////////////////////////////////////////

    /**
     * @return array
     */
    protected function get_linktable ()
    {
        $res = array ();
        foreach ( $this->references as $id => $object_and_attribs ) {
            $res[ $id ] = $object_and_attribs[0];
        }
        return $res;
    }

    /**
     * @param int $id
     * @param ORMobject $object
     * @param null|array $relation_attribs
     */
    protected function set_linktable_pointer ( $id, $object, $relation_attribs = null )
    {
        if ( $relation_attribs === null ) {
            $relation_attribs = array ();
        }
        $this->references[ $id ] = array( $object, $relation_attribs );
    }

    /**
     * @param int $id
     */
    protected function unset_linktable_entry ( $id )
    {
        if ( isset( $this->references[ $id ] ) ) {
            unset( $this->references[ $id ] );
        }
    }

    /**
     * @param array $row
     * @return ORMobject
     */
    protected function get_pointer_from_references_row ( $row )
    {
        return $row[0];
    }

    /**
     * @param array $row
     * @return null|array
     */
    protected function get_relation_attribs_from_references_row ( $row )
    {
        return $row[1];
    }

    /**
     * @param ORMobject $object
     * @param null|array $attribs
     * @return array
     */
    protected function make_references_row ( $object, $attribs = null )
    {
        return array( $object, $attribs );
    }

    /**
     * @param array $row
     * @param null|ORMobject $pointer
     */
    protected function set_pointer_in_references_row ( & $row, $pointer )
    {
        $row[0] = $pointer;
    }

    /**
     * @param array $row
     * @param null|array $attribs
     */
    protected function set_relation_attribs_in_references_row ( & $row, $attribs )
    {
        $row[1] = $attribs;
    }



    ////////////////
    // FETCH METHODS
    ////////////////

    /**
     * @return bool
     */
    protected function instance_fetch_delegate ()
    {
        return $this->fetch_id_or_ids();
    }

    /**
     * @return bool
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    private function fetch_id_or_ids ()
    {
        // generate the coumpound query and fetch the database for the ID
        $own_tablename             = ORMmd::table_name( $this->get_classname() );
        $mediator_tablename        = ORMmd::table_name( ORMmd::get_fieldspecs_classname  ( $this->fieldspecs_owntable_forward ) );
        $mediator_counterfieldname = ORMmd::get_fieldspecs_backlink   ( $this->fieldspecs_owntable_forward );
        $mediator_forwardfieldname = ORMmd::get_fieldspecs_forwardlink( $this->fieldspecs_owntable_forward );
        $target_tablename          = ORMmd::table_name( ORMmd::get_fieldspecs_classname  ( $this->fieldspecs_mediatortable_forward ) );
        $where_part                = ORMconfiguration::DELETE_HARD ? "1=1" : $mediator_tablename . "." . ORMconfiguration::DELETE_COMMON_FIELD . "=TRUE";
        $records = self::$ci->db->select( "$mediator_tablename.*" )
                                ->from( $mediator_tablename )
                                ->join( $own_tablename   ,    "$own_tablename.id = $mediator_tablename.$mediator_counterfieldname" )
                                ->join( $target_tablename, "$target_tablename.id = $mediator_tablename.$mediator_forwardfieldname" )
                                ->where( "$own_tablename.id = $this->orm_id" )
                                ->where( $where_part )
                                ->get()->result_array();
        if ( $records === null ) {
            throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" );
        }
        self::$ci->db->flush_cache();
        // reference filling and backlinking
        $own_fieldname = $this->get_relname();
        $this->references = array();
        foreach ( $records as $record ) {
            $target_id = $record[ "$mediator_tablename.$mediator_forwardfieldname" ];
            $attribs = array ();
            foreach ( $record as $fieldname => $value ) {
                if ( $fieldname == ORMconfiguration::RECORDS_TABLE_DB_PK )
                    continue;
                if ( $fieldname == "$mediator_tablename.$mediator_counterfieldname" || $fieldname == "$mediator_tablename.$mediator_forwardfieldname" )
                    continue;
                $attribs[ $fieldname ] = $value;
            }
            $this->resolve_backlinks_factorized( $own_fieldname, $target_id, false, $attribs );
        }
        // return
        return true;
    }



    ///////////////
    // SAVE METHODS
    ///////////////

    /**
     * @return bool
     * @throws ORMexception
     */
    protected function instance_save_delegate ()
    {
        // get the needed data for the ormObject where the relation value should be saved
        $own_tablename             = ORMmd::table_name( $this->get_classname() );
        $mediator_tablename        = ORMmd::table_name( ORMmd::get_fieldspecs_classname  ( $this->fieldspecs_owntable_forward ) );
        $mediator_counterfieldname = ORMmd::get_fieldspecs_backlink   ( $this->fieldspecs_owntable_forward );
        $mediator_forwardfieldname = ORMmd::get_fieldspecs_forwardlink( $this->fieldspecs_owntable_forward );
        $target_tablename          = ORMmd::table_name( ORMmd::get_fieldspecs_classname  ( $this->fieldspecs_mediatortable_forward ) );
        // get the existent relation pairs from the DB
        $where_part = ORMconfiguration::DELETE_HARD ? "1=1" : $mediator_tablename . "." . ORMconfiguration::DELETE_COMMON_FIELD . " = false";
        $records = self::$ci->db->select( "$mediator_tablename.*" )
                                ->from( $mediator_tablename )
                                ->join( $own_tablename   ,    "$own_tablename.id = $mediator_tablename.$mediator_counterfieldname" )
                                ->join( $target_tablename, "$target_tablename.id = $mediator_tablename.$mediator_forwardfieldname" )
                                ->where( "$own_tablename.id = $this->orm_id" )
                                ->where( $where_part )
                                ->get()->result_array();
        if ( $records === null ) {
            throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" );
        }
        // determine the pairs that should be changed (deleted and reinserted in the DB due to attributes changes, or inserted as new)
        $records_indexed = self::array2map( $records, "$mediator_tablename.$mediator_forwardfieldname" );
        $unmatched_pairs = array();
        $matched_pairs   = array();
        foreach ( $records_indexed as $target_id => $record ) {
            if ( ! isset( $this->references[ $target_id ] ) ) {
                $unmatched_pairs[ $record[ $mediator_tablename . "." . ORMconfiguration::RECORDS_TABLE_DB_PK ] ] = $record[ "$mediator_tablename.$mediator_forwardfieldname" ];
                continue;
            }
            $attribs = $this->references[ $target_id ][1];
            if ( $attribs === null ) {
                // by policy: if null has been assigned to heap-attribs, it means that the pair should be saved with the default values for relation attributes
                $matched_pairs[ $record[ $mediator_tablename . "." . ORMconfiguration::RECORDS_TABLE_DB_PK ] ] = $record[ "$mediator_tablename.$mediator_forwardfieldname" ];
            }
            foreach ( $record as $fieldname => $db_value ) {
                if ( $fieldname == ORMconfiguration::RECORDS_TABLE_DB_PK )
                    continue;
                if ( $fieldname == "$mediator_tablename.$mediator_counterfieldname" || $fieldname == "$mediator_tablename.$mediator_forwardfieldname" )
                    continue;
                if ( ! isset( $attribs[ $fieldname ] ) ) {
                    throw new ORMexception( "fieldname $fieldname not found in pairmap references for orm_id $this->orm_id, relname " . $this->get_relname() . " when trying to save the multiple indirect pairmap", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( $attribs[ $fieldname ] == $db_value )
                    continue;
                // add the found pair to pairs and index by the DB-PK value
                $matched_pairs[ $record[ $mediator_tablename . "." . ORMconfiguration::RECORDS_TABLE_DB_PK ] ] = $record[ "$mediator_tablename.$mediator_forwardfieldname" ];
                break;
            }
        }
        $all_pairs = array_merge( $unmatched_pairs, $matched_pairs );
        // delete the previously determined pairs of this N:N pairmap
        if ( count( $all_pairs ) > 0 ) {
            $where_pks = ORMconfiguration::RECORDS_TABLE_DB_PK . " IN ( " . implode( ', ', array_keys( $all_pairs ) ) . ") ";
            if ( ORMmd::is_hard_delete( $mediator_tablename ) ) {
                $res = self::$ci->db->where( $where_pks )
                                    ->delete( $mediator_tablename );
            }
            else {   // soft delete
                $res = self::$ci->db->where( $where_pks )
                                    ->update( $mediator_tablename, array( ORMconfiguration::DELETE_COMMON_FIELD, true ) );
            }
            if ( $res === null ) {
                throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" );
            }
        }
        // now insert the proper pairs (matched_pairs)
        foreach ( array_values( $matched_pairs ) as $target_id ) {
            $attribs = $this->references[ $target_id ];
            if ( isset( $attribs[ ORMconfiguration::RECORDS_TABLE_DB_PK ] ) ) {
                unset( $attribs[ ORMconfiguration::RECORDS_TABLE_DB_PK ] );
            }
            $attribs[ $mediator_counterfieldname ] = $this->orm_id;
            $attribs[ $mediator_forwardfieldname ] = $target_id;
            $res = self::$ci->db->insert( $mediator_tablename, $attribs );
            if ( $res === null ) {
                throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" );
            }
        }
        // return
        self::$ci->db->flush_cache();
        return true;
    }



    /////////////////////
    // SUSTANTIVE GETTERS
    /////////////////////

    /**
     * @return int|int[]
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    public function get_id_or_ids ()
    {
        // (re)fetch if needed or configurated as evey time
        if ( ! is_array( $this->references ) || ORMconfiguration::MT_SAFE_MODE_FOR_PAIRMAPS ) {
            $this->fetch_id_or_ids();
        }
        // ID is OK; return it
        $ids = array_keys( $this->references );
        return $ids;
    }

    /**
     * @param null|int|int[]|array|array[] $filters
     * @param bool $return_without_relation_attribs
     * @return array|array[]|ORMobject|ORMobject[]
     * @throws ORMexception
     */
    public function get_objects_wowo_attribs ( $filters = null, $return_without_relation_attribs = false )
    {
        return $this->get_objects_wowo_attribs_factorized( $filters, $return_without_relation_attribs );
    }



    /////////////////////
    // SUSTANTIVE SETTERS
    /////////////////////



    /**
     * If array[] is passed, should be: ( ( id-or-object, attrib-map-or-null ), ... )
     * @param int|int[]|ORMobject|ORMobject[]|array[] $argument
     * @throws ORMexception
     */
    public function set_wowo_attribs ( $argument )
    {
        $this->set_wowo_attribs_factorized( $argument );
    }



}
