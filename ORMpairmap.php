<?php



/**
 * Abstract base class for all ORM proxys
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
abstract class ORMpairmap extends ORMtrackable
{



    /////////////
    // PROPERTIES
    /////////////



    /**
     * @var string
     */
    private $relname;
    /**
     * @var string
     */
    private $classname;
    /**
     * @var array
     */
    protected $fieldspecs_owntable_forward;
    /**
     * @var array
     */
    protected $fieldspecs_mediatortable_back;
    /**
     * @var array
     */
    protected $fieldspecs_mediatortable_forward;
    /**
     * @var array
     */
    protected $fieldspecs_targettable_back;
    /**
     * @var array;
     */
    protected $references = null;   // indirect:map: ( ID => ( relation-attribs, ORMobject ) ) ; else:map: ( ID => ORMobject )



    //////////////////////////////////
    // GETTERS AND INFORMATION METHODS
    //////////////////////////////////



    /**
     * @return string
     */
    protected final function get_classname ()
    {
        return $this->classname;
    }



    /**
     * @return string
     */
    protected final function get_relname ()
    {
        return $this->relname;
    }



    /**
     * @param string $reltype
     * @return string
     */
    static public final function get_pairmap_classname_from_relationtype ( $reltype )
    {
        switch ( $reltype ) {
            case ORMconfiguration::ASSOC_SINGLE_INSIDE          : $name = "ORMpairmapSingleInside"        ; break;
            case ORMconfiguration::ASSOC_SINGLE_INSIDE_NULLABLE : $name = "ORMpairmapSingleInsideNullable"; break;
            case ORMconfiguration::ASSOC_SINGLE_OUTSIDE         : $name = "ORMpairmapSingleOutside"       ; break;
            case ORMconfiguration::ASSOC_MULTIPLE_DIRECT        : $name = "ORMpairmapMultipleDirect"      ; break;
            case ORMconfiguration::ASSOC_MULTIPLE_INDIRECT      : $name = "ORMpairmapMultipleIndirect"    ; break;
            default                                             : $name = null;
        }
        return $name;
    }



    ////////////////////////////////
    // PAIRMAP FACTORY AND DESTROYER
    ////////////////////////////////



    static function init ()
    {
        parent::init();
    }



    /**
     * @param string $classname
     * @param string $fieldname
     * @param int $source_object_id
     * @param array[] $full_field_spec
     * @param bool $autosave_at_end
     * @access	public
     */
    public function __construct ( $classname, $fieldname, $source_object_id, $full_field_spec, $autosave_at_end = ORMconfiguration::MT_SAFE_MODE_FOR_PAIRMAPS )
    {
        parent::__construct();
        $this->classname = $classname;
        $this->relname   = $fieldname;
        $this->orm_id    = $source_object_id;
        list ( $this->field_spec,
            $this->mediator_table_counterpart_field_spec,
            $this->mediator_table_linking_field_spec,
            $this->target_table_counterpart_field_spec ) = $full_field_spec;
        $this->orm_autosave_at_end = $autosave_at_end;
    }



    public function __destroy ()
    {
        parent::__destroy();
    }



    ///////////////////////////////////////////////////////////////////////////////////////////////
    // UTILITY METHODS FOR IMPLEMENTATION OF __GET, __CALL, __SET AND __UNSET AND THEIR DERIVATIVES
    ///////////////////////////////////////////////////////////////////////////////////////////////



    /**
     * @return array
     */
    abstract protected function get_linktable ();



    /**
     * @param int $id
     * @param ORMobject $object
     * @param null|array $relation_attribs
     */
    abstract protected function set_linktable_pointer ( $id, $object, $relation_attribs = null );



    /**
     * @param int $id
     */
    abstract protected function unset_linktable_entry ( $id );



    /**
     * @param array $row
     * @return ORMobject
     */
    abstract protected function get_pointer_from_references_row ( $row );



    /**
     * @param array $row
     * @return null|array
     */
    abstract protected function get_relation_attribs_from_references_row ( $row );



    /**
     * @param ORMobject $object
     * @param null|array $attribs
     * @return array
     */
    abstract protected function make_references_row ( $object, $attribs = null );



    /**
     * @param array $row
     * @param null|ORMobject $pointer
     */
    abstract protected function set_pointer_in_references_row ( & $row, $pointer );



    /**
     * @param array $row
     * @param null|array $attribs
     */
    abstract protected function set_relation_attribs_in_references_row ( & $row, $attribs );



    /**
     * @param string $fieldname
     * @param int $target_id
     * @param bool $target_id_can_be_null
     * @param null|array $pairmap_attribs
     * @param bool $raise_exception
     * @return bool
     * @throws ORMnotifierException
     */
    final protected function resolve_backlinks_factorized ( $fieldname, $target_id, $target_id_can_be_null, $pairmap_attribs = null, $raise_exception = true )
    {
        // check for validity of the target ID
        if ( $target_id === null && ! $target_id_can_be_null || $target_id !== null && ! is_int( $target_id ) ) {
            if ( $raise_exception ) {
                throw new ORMnotifierException( "found a non-valid ID for field " . $this->get_relname() . " in database for id = $this->orm_id", ORMnotifierException::INCOMPATIBLE_RELATION_VALUES );
            }
            return false;
        }
        if ( $target_id == ORMpairmapSingleInsideNullable::MAGIC_NULL_VALUE ) {
            $target_id = null;
        }
        // look for the object.counterfieldname of the object.fieldname passed in
        $field_specs = ORMmd::get_field_specs( $this->get_classname(), $fieldname );
        $counterfieldname = ORMmd::get_fieldspecs_backlink( $field_specs );
        // if passed ID is negative, then delete the counterpart enry, if any, and exit
        if ( is_int( $target_id ) && $target_id < 0 ) {
            // begin by deleting this pairmap reference to the target object
            $this->unset_linktable_entry( $target_id );
            // if the MT-row for the counterpart pairmap is not loaded OR if the MT-row has no pointer to the pairmap, do no more
            $target_pairmap_row = ORMmt::is_set_row( - $target_id, $counterfieldname ) ? ORMmt::get_row( - $target_id, $counterfieldname ) : null;
            if ( $target_pairmap_row === null ) {
                return true;
            }
            $counterpart_pairmap = ORMmt::get_pointer_as_pairmap( $target_pairmap_row->id, $target_pairmap_row->relname );
            if ( $counterpart_pairmap === null ) {
                return true;
            }
            // counterpart pairmap is loaded; now iterate, look for counterreferences and delete them
            $counterpart_linktable = $counterpart_pairmap->get_linktable();
            foreach ( array_keys( $counterpart_linktable ) as $id ) {
                if ( $id == $this->orm_id ) {
                    $counterpart_pairmap->unset_linktable_entry( $id );
                }
            }
            return true;
        }
        // ID is non-negative; so add the counterreferences
        // begin filling the references by inserting the ID (and optional attributes) with no associated pointer; this is the "minimal case"
        $this->set_linktable_pointer( $target_id, null, $pairmap_attribs );
        // if the target object is registered in the MT, assign this pairmap pointer and attribs to its object-pointer (that can be null of course)
        $target_object_row = ORMmt::is_set_row( $target_id ) ? ORMmt::get_row( $target_id ) : null;
        if ( $target_object_row !== null ) {
            $this->set_linktable_pointer( $target_id, ORMmt::get_pointer_as_object( $target_object_row->id ), $pairmap_attribs );
        }
        // if the MT-row for the counterpart pairmap is not loaded OR if the MT-row has no pointer to the pairmap, do no more
        $target_pairmap_row = ORMmt::is_set_row( $target_id, $counterfieldname ) ? ORMmt::get_row( $target_id, $counterfieldname ) : null;
        if ( $target_pairmap_row === null ) {
            return true;
        }
        $counterpart_pairmap = ORMmt::get_pointer_as_pairmap( $target_pairmap_row->id, $target_pairmap_row->relname );
        if ( $counterpart_pairmap === null ) {
            return true;
        }
        // if the object whose pairmap is this (strange case) is not loaded in heap or in MT, can do no more
        $this_pairmap_object = ORMmt::is_set_row( $this->orm_id ) ? ORMmt::get_pointer_as_object( $this->orm_id ) : null;
        if ( $this_pairmap_object === null ) {
            return true;
        }
        // counterpart pairmap is loaded; now iterate, look for counterreferences and assign their pointers back to this pairmap's object
        $counterpart_linktable = $counterpart_pairmap->get_linktable();
        foreach ( array_keys( $counterpart_linktable ) as $id ) {
            if ( $id == $this->orm_id ) {
                $counterpart_pairmap->set_linktable_pointer( $id, $this_pairmap_object, $pairmap_attribs );
            }
        }
        return true;
    }



    /**
     * Filter map can be:
     * (0) null :: no filter will be done
     * (1) int :: only the object with that ID will be returned as ORMobject, or null if non-existent
     * (2) int[] :: all the objects that match that IDs will be returned as ORMobject[]; any non-existent ID will have null as object
     * (3) (only for indirect relations) :: array( "id" => <id_spec> , "attrib1" => <attrib1_spec> , ... ) :: will filter for matching ALL of the filter specs; each "spec" can be a scalar value (can be null) or an array of scalars (can be nulls)
     *
     * @param null|int|int[]|array|array[] $filters
     * @param bool $return_without_relation_attribs
     * @return array|array[]|ORMobject|ORMobject[]
     * @throws ORMexception
     */
    public final function get_objects_wowo_attribs_factorized ( $filters = null, $return_without_relation_attribs = false )
    {
        // check for valid filters
        if ( $filters !== null && !( is_int($filters) || is_array($filters) ) ) {
            throw new ORMexception( "bad filters specification (only null, integer or array are valid)" );
        }
        if ( is_array( $filters ) && count( $filters ) == 0 ) {
            $filters = null;
        }
        if ( is_array( $filters ) ) {
            if ( array_keys( $filters )[0] == 0 ) {
                $all_are_int = array_reduce( $filters, function ( $carry, $item ) { return $carry && is_int($item); }, true );
                if ( ! $all_are_int ) {
                    throw new ORMexception( "bad filters specification (at least one element of array is non-integer)" );
                }
            }
            if ( ! $this instanceof ORMpairmapMultipleIndirect ) {
                foreach ( array_keys( $filters ) as $fieldname ) {
                    if ( $fieldname != "id") {
                        throw new ORMexception( "bad filters specification for non-indirect relation type (can filter only by ID field)" );
                    }
                }
            }
        }
        // first case: no filters
        if ( $filters === null ) {
            $filtered = $this->references;
        }
        else {
            // second case: filters as int or int[]; convert for third case
            if ( is_int( $filters ) ) {
                $filters = array( "id" => array( $filters ) );
            }
            elseif ( array_keys( $filters )[0] == 0 ) {
                $filters = array( "id" => $filters );
            }
            // third case: map-type filter spec; (a) filter by ID, if ID subset is passed
            if ( isset( $filters["id"] ) ) {
                $filtered = array ();
                foreach ( $filters["id"] as $id ) {
                    $filtered[ $id ] = in_array( $id, array_keys( $this->references ) ) ? $this->references[ $id ] : null;
                }
                $other_filters_present = count( $filters ) > 1;
            }
            else {
                $filtered = $this->references;
                $other_filters_present = true;
            }
            // (b) filter by others attributes, if any
            if ( $other_filters_present ) {
                foreach ( $filters as $fieldname => $values ) {
                    if ( $fieldname == "id" ) {
                        continue;
                    }
                    foreach ( $filtered as $id => $object_and_attribs ) {
                        $attribs = self::get_relation_attribs_from_references_row( $object_and_attribs );
                        if ( $attribs === null ) {
                            unset( $filtered[ $id ] );
                        }
                        if ( ! isset( $attribs[ $fieldname ] ) ) {
                            throw new ORMexception( "bad filters specification (at least one element of the map-type filter refers to an attribute not present in at least one weel-defined pair of this pairmap)" );
                        }
                        $value = $attribs[ $fieldname ];
                        if ( ! in_array( $value, $values ) ) {
                            unset( $filtered[ $id ] );
                        }
                    }
                }
            }
        }
        // package and return
        $res = array ();
        foreach ( $filtered as $target_id => $object_and_attribs ) {
            list ( $object, $attribs ) = $object_and_attribs;
            if ( $object === null ) {
                $object = ORMmt::follow_pointer_as_object( $target_id );   // TODO evaluate safe_mode_for_objects and force_reload
            }
            $res[ $target_id ] = $return_without_relation_attribs ? $object : self::make_references_row( $object, $attribs );
        }
        return $res;
    }



    /**
     * If array[] is passed, should be: ( ( id-or-object, attrib-map-or-null ), ... )
     * "$argument_has_integers_no_objects" is now unused, because in __set() it's impossible to specify the r-value type
     * @param int|int[]|ORMobject|ORMobject[]|array[] $argument
     * @param bool $argument_has_integers_no_objects if true, will check for integers; if false, will check for ORMobjects (UNUSED)
     * @throws ORMexception
     */
    final protected function set_wowo_attribs_factorized ( $argument, $argument_has_integers_no_objects = null )
    {
        $ignore_argument_has_integers_no_objects = true;
        // strongly check the passed argument structure and typing; assign flags indicating detected argument structure
        $argument_is_single   = null;
        $argument_has_ids     = null;
        $argument_has_attribs = null;
        if ( is_int( $argument ) || ( $argument === null || $argument == ORMpairmapSingleInsideNullable::MAGIC_NULL_VALUE ) && $this instanceof ORMpairmapSingleInsideNullable ) {
            $argument_is_single   = true;
            $argument_has_ids     = true;
            $argument_has_attribs = false;
        }
        elseif ( $argument instanceof ORMobject ) {
            if ( $argument === null && ! $this instanceof ORMpairmapSingleInsideNullable ) {
                throw new ORMexception( "passed object can't be null for not-nullable single relations" );
            }
            $argument_is_single   = true;
            $argument_has_ids     = false;
            $argument_has_attribs = false;
        }
        elseif ( ! is_array( $argument ) ) {
            throw new ORMexception( "argument should be integer, array of integers, ORMobject, array of ORMobjects, or array or pairs ( integer-or-ORMobject, attribs-map-or-null )" );
        }
        else {   // array
            if ( $ignore_argument_has_integers_no_objects ) {
                $argument_has_integers_no_objects = $argument_has_ids;
            }
            $argument_is_single = false;
            if ( count( $argument ) == 0 ) {
                throw new ORMexception( "passed array should have at least one element" );
            }
            if ( ! is_array( $argument[0] ) ) {
                $argument_has_attribs = false;
                if ( $argument_has_integers_no_objects ) {
                    if ( $this instanceof ORMpairmapSingleInsideNullable ) {
                        $all_are_int = array_reduce( $argument, function ( $carry, $item ) { return $carry && ( is_int( $item ) || $item === null || $item == ORMpairmapSingleInsideNullable::MAGIC_NULL_VALUE ); }, true );
                    }
                    else {
                        $all_are_int = array_reduce( $argument, function ( $carry, $item ) { return $carry && is_int( $item ); }, true );
                    }
                    if ( ! $all_are_int ) {
                        throw new ORMexception( "at least one non-integer (and non-null in case of nullable relations) element was found in the passed array" );
                    }
                    $argument_has_ids = true;
                }
                else {   // check for ORMobjects
                    $all_are_objects = array_reduce( $argument, function ( $carry, $item ) { return $carry && $item instanceof ORMobject; }, true );
                    if ( ! $all_are_objects ) {
                        throw new ORMexception( "at least one non-ORMobject (or null) element was found in the passed array" );
                    }
                    $argument_has_ids = false;
                }
            }
            else {   // attribs passed; will be ever multiple relations so it's not neccesary special checkings for singles, as before
                $argument_has_attribs = true;
                if ( $argument_has_integers_no_objects ) {
                    $all_are_int = array_reduce( $argument, function ( $carry, $item ) { return $carry && ( is_int( $item[0] ) || $item[0] === null || $item[0] == ORMpairmapSingleInsideNullable::MAGIC_NULL_VALUE ) && ( is_array( $item[1] ) || $item[1] === null ); }, true );
                    if ( ! $all_are_int ) {
                        throw new ORMexception( "at least one non-integer element was found in the passed array" );
                    }
                    $argument_has_ids = true;
                }
                else {   // check for ORMobjects
                    $all_are_objects = array_reduce( $argument, function ( $carry, $item ) { return $carry && $item[0] instanceof ORMobject && ( is_array( $item[1] ) || $item[1] === null ); }, true );
                    if ( ! $all_are_objects ) {
                        throw new ORMexception( "at least one non-ORMobject element was found in the passed array" );
                    }
                    $argument_has_ids = false;
                }
            }
        }
        if ( $argument_is_single === null || $argument_has_ids === null || $argument_has_attribs === null ) {
            throw new ORMexception( "invalid logic at block: at least one type flag is null" );
        }
        //$argument_is_multiple    = ! $argument_is_single;
        //$argument_has_objects    = ! $argument_has_ids;
        //$argument_has_no_attribs = ! $argument_has_attribs;
        // prepare handy closure-styled functions
        $f_remove_pairmap_entry = function ( $target_id ) {
            if ( $target_id === null && $this instanceof ORMpairmapSingleInsideNullable ) {
                $target_id = ORMpairmapSingleInsideNullable::MAGIC_NULL_VALUE;
            }
            if ( isset( $this->references[ $target_id ] ) ) {
                unset( $this->references[ $target_id ] );
            }
        };
        $f_set_pairmap_entry = function ( $target_id, ORMobject $object, $attribs = null ) use ( $f_remove_pairmap_entry ) {
            if ( $target_id === null && $this instanceof ORMpairmapSingleInsideNullable ) {
                $target_id = ORMpairmapSingleInsideNullable::MAGIC_NULL_VALUE;
            }
            $f_remove_pairmap_entry( $target_id );
            $row = $this->make_references_row( $object, $attribs );
            $this->references[ $target_id ] = $row;
        };
        // add new pairs or delete existing pairs; meanwhile resolve backlinks
        $own_fieldname = $this->get_relname();
        foreach ( $argument as $entry ) {
            if ( is_array( $entry ) ) {
                list ( $id_or_object, $attribs ) = $entry;
            }
            else {
                $id_or_object = $entry;
                $attribs      = null;
            }
            if ( $id_or_object instanceof ORMobject ) {
                $object = $id_or_object;
                $id     = $object->orm_id;
            }
            elseif ( $id_or_object !== null && $id_or_object < 0 ) {
                $id = $id_or_object;
                $f_remove_pairmap_entry( - $id );
                $this->resolve_backlinks_factorized( $own_fieldname, - $id, false, $attribs );
                continue;
            }
            else {
                $object = null;
                $id     = $id_or_object === null && $this instanceof ORMpairmapSingleInsideNullable ? ORMpairmapSingleInsideNullable::MAGIC_NULL_VALUE : $id_or_object;
            }
            if ( $id === null ) {
                throw new ORMexception( "null ID in/out of object can't be passed when relation type is not nullable" );
            }
            if ( isset( $this->references[ $id ] ) ) {
                $this->set_wowo_attribs_factorized( array( - $id ) );   // unset before setting, so all deleting-related backlinks are adjusted
            }
            $f_set_pairmap_entry( $id, $object, $attribs );
            $this->resolve_backlinks_factorized( $own_fieldname, $id, $this instanceof ORMpairmapSingleInsideNullable, $attribs );
        }
    }



    //////////
    // FETCHER
    //////////



    /**
     * Will do a refetch if the pairmap are already in heap
     *
     * @param int $id
     * @param string $relname
     * @return ORMpairmap
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    static final public function instance_fetch ( $id, $relname )
    {
        // it's supposed the only __get() of ORMobject call this method, and that the relname is a valid metadata attribute for the classname
        try {
            $row = ORMmt::fetch_row( $id, $relname );
            if ( $row === null ) {
                return null;
            }
            // create the pairmap instance, case by case
            $classname = ORMmt::get_classname( $id, $relname );
            $full_field_spec = ORMmd::get_field_specs( $classname, $relname );
            $reltype = $full_field_spec[0][1];
            /** @var ORMpairmap $pairmap_classname */ $pairmap_classname = ORMpairmap::get_pairmap_classname_from_relationtype( $reltype );
            if ( ! $pairmap_classname ) {
                throw new ORMnotifierException( "invalid relation type $reltype for field $relname of class $classname when creating pairmap for id = $id", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
            }
            /** @var ORMpairmap $pairmap */ $pairmap = new $pairmap_classname( $classname, $relname, $id, $full_field_spec );
            if ( $pairmap === null ) {
                throw new ORMnotifierException( "constructor in pairmap fetch factory method failed for id = $id, relname = $relname", ORMnotifierException::CONSTRUCTOR_FAILED );
            }
            if ( !( $pairmap instanceof ORMpairmap ) ) {
                throw new ORMexception( "instanced object id $id, relname $relname in pairmap fetch factory method is not an ORM pairmap" );
            }
            $res = $pairmap->instance_fetch_delegate();
            if ( ! $res ) {
                throw new ORMnotifierException( "delegate constructor failed for pairmap fetch factory method for id $id, relname $relname", ORMnotifierException::DELEGATE_FETCH_FAILED );
            }
        } catch (ORMexception $ex) {
            // TODO put here a breakpoint
            throw $ex;
        }
        // update ORM master heap-table
        ORMmt::set_pointer( $id, $relname, $pairmap );
        // guarantee that only objects created using factory method can use ORM features
        $pairmap->orm_initialized = true;
        // return the pairmap
        return $pairmap;
    }



    /**
     * @return bool
     */
    abstract protected function instance_fetch_delegate ();



    ////////
    // SAVER
    ////////



    /**
     * @param string $timestamp
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    final protected function instance_save ( $timestamp )
    {
        ORMmt::row_toll( $this->orm_id, $this->relname );
        // delegate the save of the pairmap's internals (id/ids list and relation DB attributes) to the proper subclass
        try {
            $res = $this->instance_save_delegate();
            if ( ! $res ) {
                throw new ORMnotifierException( "delegate saving for id $this->orm_id, relname $this->relname failed", ORMnotifierException::DELEGATE_SAVE_FAILED );
            }
        } catch (ORMnotifierException $ex) {
            // TODO log this case
            throw $ex;
        } catch (ORMexception $ex) {
            // TODO log this case
            throw $ex;
        }
        ORMmt::set_db_modified_at( $this->orm_id, $this->relname, $timestamp );
        ORMmt::save_row_by_index(  $this->orm_id, $this->relname );
    }



    // should be implemented in subclasses; the save() in the ORM master db-table was already done
    // should return true of false, indicating result of save
    //
    /**
     * @return bool
     * @throws ORMexception
     */
    abstract protected function instance_save_delegate ();



    //////////////////
    // CONTENT GETTERS
    //////////////////



    /**
     * @return int|int[]
     */
    abstract protected function get_id_or_ids ();

    /**
     * @return int
     */
    final public function get_id ()
    {
        $res = $this->get_id_or_ids();
        return is_int( $res ) ? $res : null;
    }

    /**
     * @return int[]
     */
    final public function get_ids ()
    {
        $res = $this->get_id_or_ids();
        return is_array( $res ) ? $res : null;
    }



    /**
     * @param null|string $filters_map
     * @param bool $return_without_relation_attribs
     * @return array|array[]|ORMobject|ORMobject[]
     */
    abstract protected function get_objects_wowo_attribs ( $filters_map = null, $return_without_relation_attribs = false );

    /**
     * @param null|string $filters_map
     * @return null|ORMobject
     */
    final public function get_object ( $filters_map = null )
    {
        $res = $this->get_objects_wowo_attribs( $filters_map, true );
        return is_array( $res ) ? null : $res;
    }

    /**
     * @param null|string $filters_map
     * @return null|ORMobject[]
     */
    final public function get_objects ( $filters_map = null )
    {
        $res = $this->get_objects_wowo_attribs( $filters_map, true );
        return ! is_array( $res ) ? null : $res;
    }

    /**
     * @param null|string $filters_map
     * @return null|array[]
     */
    final public function get_objects_with_attribs ( $filters_map = null )
    {
        $res = $this->get_objects_wowo_attribs( $filters_map, false );
        return ! is_array( $res[0] ) ? null : $res;
    }



    //////////////////
    // CONTENT SETTERS
    //////////////////



    /**
     * If array[] is passed, should be: ( ( id-or-object, attribs-map-or-null ), ... )
     * @param int|int[]|ORMobject|ORMobject[]|array[] $argument
     * @throws ORMexception
     */
    abstract protected function set_wowo_attribs ( $argument );

    /**
     * If array[] is passed, should be: ( ( id-or-object, attribs-map-or-null ), ... )
     * @param int|int[]|ORMobject|ORMobject[]|array[] $argument
     * @throws ORMexception
     */
    final public function set ( $argument )
    {
        $this->set_wowo_attribs( $argument );
    }



    ///////////////////
    // CONTENT UNSETTER
    ///////////////////



    /**
     * Can't use "unset" as method name; add a "t"
     * @throws ORMexception
     */
    final public function unsett ()
    {
        $ids_to_delete = array_map ( function ( $id ) { return - $id; }, $this->references );
        $this->set( $ids_to_delete );
    }



}
