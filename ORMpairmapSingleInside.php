<?php



/**
 * Class for ORM proxy of type SINGLE_INSIDE
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
class ORMpairmapSingleInside extends ORMpairmap
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
        return $this->references;
    }

    /**
     * @param int $id
     * @param ORMobject $object
     * @param null|array $relation_attribs
     */
    protected function set_linktable_pointer ( $id, $object, $relation_attribs = null )
    {
        $this->references[ $id ] = $object;
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
        return $row;
    }

    /**
     * @param array $row
     * @return null|array
     * @throws ORMexception
     */
    protected function get_relation_attribs_from_references_row ( $row )
    {
        if ( $row || ! $row ) {   // avoid IDE "unused var" checking
            throw new ORMexception( "can't get attribs in references rows when relation type is not multiple indirect" );
        }
        return null;
    }

    /**
     * @param ORMobject $object
     * @param null|array $attribs
     * @return array
     */
    protected function make_references_row ( $object, $attribs = null )
    {
        if ( $attribs || ! $attribs ) {   // avoid IDE "unused var" checking
            return $object;
        }
        return null;
    }

    /**
     * @param array $row
     * @param null|ORMobject $pointer
     */
    protected function set_pointer_in_references_row ( & $row, $pointer )
    {
        $row = $pointer;
    }

    /**
     * @param array $row
     * @param null|array $attribs
     * @throws ORMexception
     */
    protected function set_relation_attribs_in_references_row ( & $row, $attribs )
    {
        if ( $row || ! $row || $attribs || ! $attribs ) {   // avoid IDE "unused var" checking
            throw new ORMexception( "can't set attribs in references rows when relation type is not multiple indirect" );
        }
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
        $tablename  = ORMmd::table_name( $this->get_classname() );
        $fieldname  = $this->get_relname();
        $where_part = ORMconfiguration::DELETE_HARD ? "1=1" : $tablename . "." . ORMconfiguration::DELETE_COMMON_FIELD . " = false";
        $records = self::$ci->db->select( $fieldname )
                                ->from( $tablename )
                                ->where( "id = $this->orm_id" )
                                ->where( $where_part )
                                ->get()->result_array();
        if ( $records === null ) {
            throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" );
        }
        self::$ci->db->flush_cache();
        // only ONE row should exist
        if ( count($records) != 1 ) {
            return false;
        }
        // reference filling and backlinking
        $this->references = array();
        $target_id = $records[0][ $fieldname ];
        $this->resolve_backlinks_factorized( $fieldname, $target_id, false );
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
        // get the needed data for the ormObject this pairmap is working for
        $tablename = ORMmd::table_name( $this->get_classname() );
        $fieldname = $this->get_relname();
        if ( count($this->references) != 1 ) {
            throw new ORMexception( "found that references array is not filled when executing instance_save_delegate" );
        }
        $target_id = array_keys( $this->references )[0];
        $res = self::$ci->db->where( "id = $this->orm_id" )
                            ->update( $tablename, array( $fieldname, $target_id ) );
        if ( $res === null ) {
            throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" );
        }
        self::$ci->db->flush_cache();
        // return
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
        $id = array_keys( $this->references )[0];
        return $id;
    }

    /**
     * @param null|int|int[]|array|array[] $filters
     * @param bool $return_without_relation_attribs
     * @return array|array[]|ORMobject|ORMobject[]
     * @throws ORMexception
     */
    public function get_objects_wowo_attribs ( $filters = null, $return_without_relation_attribs = false )
    {
        return $this->get_objects_wowo_attribs_factorized( $filters, false );
    }



    /////////////////////
    // SUSTANTIVE SETTERS
    /////////////////////



    /**
     * @param int|ORMobject $argument
     * @throws ORMexception
     */
    public function set_wowo_attribs ( $argument )
    {
        $this->set_wowo_attribs_factorized( $argument );
    }



}
