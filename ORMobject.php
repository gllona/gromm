<?php



// el getter/setter de los todos los atributos (!"orm*") particulares de cada object; cada setter actualiza el modified_at (heap)
// 20 (real=19) chars en cada campo microsegundos en BD (realmente 19) // created_at, modified_at, deleted_at
// mejorar el constructor para que no reciba argumentos y se preserve la firma a las subclases
// operaciones de cada objeto:
// - create : OK, listo; con la excepción de lo mencionado abajo
// - fetch  : OK, listo
// - save   : OK, listo
// - update : pendiente, es sobre cada property --> implementar por medio de setters (ver arriba)
// como manejar caso de los objetos nuevos (VERIFICAR y contrastar con post-its verdes):
// - al crear se fija un atributo "delete_if_not_saved_explicitally"; esto ejecuta la correspondiente accion en el destructor de la clase (base)
// - implementar como con el constructor para asegurar llamada del destructor o metodo _delegate; ¿se puede?
// - al instanciar se hace lo mismo, aumentando las ventajas del ORM (no es necesario pero dado que se va a hacer, entonces implementar
// - el save_all no es afectado por este caso



/**
 * Abstract base class for all ORM-managed objects (excluding ORM-managers objects like proxies)
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
abstract class ORMobject extends ORMtrackable
{



    //////////////////////////////////////////////////////////////
    // INITIALIZER, CONSTRUCTOR AND FACTORY METHODS FOR INSTANCING
    //////////////////////////////////////////////////////////////



    static function init ()
    {
        parent::init();
    }



    /**
     * @param int|null $id
     * @param bool $autosave_at_end
     * @throws ORMnotifierException
     */
    function __construct ( $id, $autosave_at_end = ORMconfiguration::AUTOSAVE_ALL_OBJECTS )
    {
        parent::__construct();
        $this->orm_id              = $id;
        $this->orm_autosave_at_end = $autosave_at_end;
        // finalize constructor
        if ( ! $this->orm_constructor_delegate() ) {   // any child object should implement this method
            throw new ORMnotifierException( "delegated object constructor failed for id = $id", ORMnotifierException::CONSTRUCTOR_FAILED );
        }
    }



    // method for finishing the object construction
    // this method should be implemented by all the child classes
    // this method ONLY should unset ALL public, protected and private properties except the inherated ones
    // should return true if object construction was ok; false otherwise
    // ORMexception or ORMnotifierException can be thrown
    // specific logic for object constructor is done in the delegated methods of the factory methods
    //
    /**
     * @return bool
     */
    abstract protected function orm_constructor_delegate ();

    
    
    // factory method: the only correct way to create ORM objects
    // calls orm_finish_constructor()
    //
    /**
     * @param string $classname
     * @param bool $autosave_at_end
     * @return ORMobject
     * @throws Exception
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    static protected final function orm_instance_create ( $classname, $autosave_at_end = ORMconfiguration::AUTOSAVE_NEW_OBJECTS )
    {
        if ( ! is_string($classname) ) {
            throw new ORMexception( "argument must be string" );
        }
        if ( ! isset(ORMconfiguration::$md[$classname]) ) {
            throw new ORMnotifierException( "no metadata found for class $classname (class $classname)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        // check if the PHP class exists
        if ( ! class_exists($classname) ) {
            throw new ORMnotifierException( "class $classname doesn't exist", ORMnotifierException::CLASS_NOT_FOUND );
        }
        // create the object instance, delegating the creation of object's internals
        try {
            $object = new $classname( null, null, $autosave_at_end );   /** @var $object ORMobject */
            if ( $object === null ) {
                throw new ORMnotifierException( "constructor in create factory method failed for class $classname", ORMnotifierException::CONSTRUCTOR_FAILED );
            }
            if ( !( $object instanceof ORMobject ) ) {
                throw new ORMexception( "instanced class $classname in create factory method is not an ORM class" );
            }
            $res = $object->orm_instance_create_delegate();
            if ( ! $res ) {
                throw new ORMnotifierException( "delegate constructor failed in create factory method", ORMnotifierException::DELEGATE_CREATE_FAILED );
            }
        } catch (ORMexception $ex) {
            // LOG this
            throw $ex;
        }
        // update ORM master heap-table
        $row = ORMmt::build_new_row( $classname, null, null, null, null, $object, true );
        ORMmt::save_row( $row );
        // guarantee that only objects created using factory method can use ORM features
        $object->orm_initialized = true;
        // return the new instance
        return $object;
    }



    // should be implemented in subclasses; the insert in the ORM master db-table was already done
    // should return true of false, indicating result of creation; if it fails, the ORM record will be inutilized
    //
    /**
     * @return bool
     */
    abstract protected function orm_instance_create_delegate ();
    
    

    // is $load_if_null_pointer is false, there is no way to identify if a returned null value represents "no object" or "not loaded"
    //
    /**
     * @param int $id
     * @param bool $load_if_null_pointer
     * @param bool $autosave_at_end
     * @return null|ORMobject
     * @throws ORMexception
     */
    static final public function orm_instance_fetch ( $id, $load_if_null_pointer = true, $autosave_at_end = ORMconfiguration::AUTOSAVE_ALL_OBJECTS )
    {
        if ( ! is_int($id) ) {
            throw new ORMexception( "id should be integer" );
        }
        $objects = self::orm_instances_fetch( array( $id ), $load_if_null_pointer, $autosave_at_end );
        if ( $objects === null || ! isset($objects[$id]) || $objects[$id] === null ) {
            return null;
        }
        return $objects[$id];
    }



    /**
     * Will do a refetch if the object is already in heap
     *
     * @param int[] $ids
     * @param bool $autosave_at_end
     * @return ORMobject[] object map of fetched instances; any non-existent ID will have null as value
     * @throws Exception
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    static final public function orm_instances_fetch ( $ids, $autosave_at_end = ORMconfiguration::AUTOSAVE_ALL_OBJECTS )
    {
        if ( ! is_array($ids) ) {
            throw new ORMexception( "ids should be array of integers" );
        }
        $object_pool = array ();
        foreach ( $ids as $id ) {
            if ( ! is_int($id) ) {
                throw new ORMexception( "at least one element of ids is not integer" );
            }
            try {
                $row = ORMmt::fetch_row( $id );
                if ( $row === null ) {
                    $object_pool[$id] = null;
                    continue;
                }
                $classname = ORMmt::get_classname( $id );
                /** @var $object ORMobject */
                $object = new $classname( $id, $autosave_at_end );
                if ( $object === null ) {
                    throw new ORMnotifierException( "constructor in fetch factory method failed for id $id", ORMnotifierException::CONSTRUCTOR_FAILED );
                }
                if ( !( $object instanceof ORMobject ) ) {
                    throw new ORMexception( "instanced object id $id in fetch factory method is not an ORM object" );
                }
                $res = $object->orm_instance_fetch_delegate();
                if ( ! $res ) {
                    throw new ORMnotifierException( "delegate constructor failed for fetch factory method for id $id", ORMnotifierException::DELEGATE_FETCH_FAILED );
                }
            } catch (ORMexception $ex) {
                // TODO put here a breakpoint
                throw $ex;
            }
            // update ORM master heap-table
            ORMmt::set_pointer( $id, null, $object );
            // guarantee that only objects created using factory method can use ORM features
            $object->orm_initialized = true;
            // update the array to return
            $object_pool[$id] = $object;
        }
        return $object_pool;
    }



    // should be implemented in subclasses; the fetch() from the ORM master db-table was already done
    // should return true of false, indicating result of the operarion
    //
    /**
     * @return bool
     */
    abstract protected function orm_instance_fetch_delegate ();

    
    
    // when this method is called; it's sure that the pointer in the ORM master table is not set
    //
    /**
     * @throws Exception
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    function __destroy ()
    {
        parent::__destroy();
        // avoid invalid cases
        if ( ! $this->orm_is_valid() )
            return;
        // unset all relations depending of this object located in heap-MT
        /** @var ORMmtRow[] $rows */
        $rows = ORMmt::get_object_pairmaps_rows( $this->orm_id );
        foreach ( $rows as $row ) {
            ORMmt::unset_row( $row->id, $row->relname );
        }
        // avoid cases where autosave shouldn't happen
        if ( ! $this->orm_autosave_at_end )
            return;
        // do the autosave
        try {
            $this->orm_instance_save();
        }
        catch (ORMnotifierException $ex) {   // all ORM-specific exceptions must be trapped
            // TODO log this
            throw $ex;
        }
        catch (ORMexception $ex) {
            // TODO log this
            throw $ex;
        }
    }

    
    
    //////////
    // CLONING
    //////////
    
    
    
    protected function __clone ()
    {
        if ( ! $this->orm_is_valid() ) {
            throw new ORMnotifierException( "object id = $this->orm_id is not suitable for cloning", ORMnotifierException::NOT_WORKABLE );
        }
        $this->orm_cloned = true;
    }


    
    //////////////////////
    // OBJECT SAVE METHODS
    //////////////////////



    /**
     * @param bool $autosave_at_end
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    final protected function orm_instance_save ( $autosave_at_end = ORMconfiguration::AUTOSAVE_ALL_OBJECTS )
    {
        ORMmt::row_toll( $this->orm_id );
        // delegate the save of the object's internals to the proper subclass
        try {
            $res = $this->orm_instance_save_delegate();
            if ( ! $res ) {
                throw new ORMnotifierException( "delegate saving for id = $this->orm_id failed", ORMnotifierException::DELEGATE_SAVE_FAILED );
            }
        } catch (ORMnotifierException $ex) {
            // TODO log this case
            throw $ex;
        }
        $now = self::microtime();
        // save all the associated pairmaps
        $rows = ORMmt::get_object_pairmaps_rows( $this->orm_id );
        if ( $rows === null ) {
            return;
        }
        foreach ( $rows as $row ) {
            $pairmap = ORMmt::get_pointer_as_pairmap( $row->id, $row->relname );
            if ( $pairmap === null ) {
                continue;
            }
            $pairmap->instance_save( $now );
        }
        // save timestamp in the ORM master db-table
        ORMmt::set_db_modified_at( $this->orm_id, null, $now );
        ORMmt::save_row_by_index( $this->orm_id );
        // behaviour for the for the next save(); note that if this save() is done on a new object, the default behaviuor will change
        $this->orm_autosave_at_end = $autosave_at_end;
    }



    // should be implemented in subclasses; the save() in the ORM master db-table was already done
    // should return true of false, indicating result of save
    //
    /**
     * @return bool
     */
    abstract protected function orm_instance_save_delegate ();



    // saves all objects in DB
    //
    /**
     * @param int[] $except_these_ids
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    static public function orm_instances_save_all ( $except_these_ids = array() )
    {
        if ( ! is_array($except_these_ids) ) {
            throw new ORMexception( "invalid argument type" );
        }
        $rows = ORMmt::get_all_rows( ORMmtRow::OBJECT );
        foreach ( $rows as $id => $row ) {
            if ( in_array( $id, $except_these_ids ) ) {
                continue;
            }
            try {
                ORMmt::save_row( $row );
            } catch (ORMnotifierException $ex) {
                // TODO log this case
                throw $ex;
            }
        }
    }


    
    ////////////////////////
    // OBJECT DELETE METHODS
    ////////////////////////



    // in this system/approach, all classes that have subclasses are considered <<abstract>>
    // a deletion on a table that have children will left the children db-rows untouched but their upper classes rows' will be deleted
    // in the master heap-table, the pointer to the object will be preserved to add flexibility to the program logic
    //
    /**
     * @throws Exception
     * @throws ORMexception
     * @throws ORMnotifierException
     */
    final public function orm_instance_delete ()
    {
        ORMmt::row_toll( $this->orm_id );
        // delegate the delete-related actions of the object's internals to the proper subclass
        // the delegate procedure should NOT delete the object from the database; it's done in this block according with metadata specs
        // the delegate procedure should NOT delete the relationships managed by the metadata
        try {
            $res = $this->orm_instance_delete_delegate();
            if ( ! $res ) {
                throw new ORMnotifierException( "delegate deleting for id = $this->orm_id failed", ORMnotifierException::DELEGATE_DELETE_FAILED );
            }
        } catch (ORMnotifierException $ex) {
            // TODO log this case
            throw $ex;
        }
        // mark as deleted in the master db-table
        ORMmt::set_db_deleted_at( $this->orm_id );   // NOW()
        ORMmt::save_row_by_index( $this->orm_id );
        ORMmt::unset_row( $this->orm_id );
        // now delete the object from the database; go up from the record to the upper classes (tables); if there are
        // previously delete all relation-type fields (__unset() will deletion will take care of counterpart relations and upper classes)
        $classname = ORMmt::get_classname( $this->orm_id );
        $relnames = ORMmd::get_all_fields_names( $classname, true );
        foreach ( $relnames as $fieldname ) {
            // attributes defined in upper classes will be unset also
            // notes: in-heap upper classes are always abstract; in-db upper tables are always present
            unset( $this->$fieldname );
        }
        do {
            $tablename = ORMmd::table_name( $classname );
            // delete the class record in DB, according to soft/hard spec in DB
            if ( ORMmd::is_hard_delete( $classname ) ) {
                $res = self::$ci->db->where( "id = $this->orm_id" )
                                    ->delete( $tablename );
            }
            else {   // soft delete
                $res = self::$ci->db->where( "id = $this->orm_id" )
                                    ->update( $tablename, array( ORMconfiguration::DELETE_COMMON_FIELD, true ) );
            }
            if ( $res === null ) {
                throw new ORMexception( "SQL query error :: [ " . self::$ci->db->last_query() . " ]" );
            }
            if ( self::$ci->db->affected_rows() == 0 ) {
                // TODO log this case (SQL issue)
                throw new ORMnotifierException( "table $tablename delete for id = $this->orm_id didn't complete", ORMnotifierException::MASTER_TABLE_DELETE_FAILED );
            }
            self::$ci->db->flush_cache();
            // now repeat the loop with the parent class/table, if exists
            $classname = ORMmd::get_parent_class( $classname );
        } while ( $classname !== null );
    }



    // should be implemented in subclasses; the delete() in the ORM master db-table was already done
    // should return true of false, indicating result of save
    // see notes above
    //
    /**
     * @return bool
     */
    abstract protected function orm_instance_delete_delegate ();



    /**
     * @param int $id
     * @throws ORMnotifierException
     */
    static final public function orm_delete_by_id ( $id )
    {
        // get the object: load it from the DB if not yet in the master table
        $object = self::orm_instance_fetch( $id, true, false );   /** @var $object ORMobject */
        if ( $object === null ) {
            throw new ORMnotifierException( "id = $id object not found", ORMnotifierException::OBJECT_NOT_FOUND );
        }
        // transfer control to the instance method
        $object->orm_instance_delete();
    }


    
    ////////////////////////////////
    // OBJECT PROPERTIES GET METHODS
    ////////////////////////////////



    /**
     * Returns the ID(s), not the object(s)
     * @param string $fieldname
     * @return int|int[]
     * @throws Exception
     * @throws ORMnotifierException
     */
    public final function __get ( $fieldname )
    {
        $classname = get_class( $this );
        // check if the field is a ORM field; if not, derivate to the method that should get the dynamic non-ORM field
        try {
            $field_specs = ORMmd::get_field_specs( $classname, $fieldname );
        }
        catch (ORMnotifierException $ex) {
            return $this->orm___get_delegate( $fieldname  );
        }
        // return the ID or ID[] according to the relation cardinality
        $ptr = ORMmt::follow_pointer_as_pairmap( $this->orm_id, $fieldname );
        $relation_type = ORMmd::get_fieldspecs_relationtype( $field_specs );
        if ( in_array( $relation_type, array ( ORMconfiguration::ASSOC_SINGLE_INSIDE, ORMconfiguration::ASSOC_SINGLE_INSIDE_NULLABLE, ORMconfiguration::ASSOC_SINGLE_OUTSIDE ) ) ) {
            return $ptr->get_id();
        }
        else {
            return $ptr->get_ids();
        }
    }



    /**
     * @param string $fieldname
     * @return mixed
     * @throws Exception
     * @throws ORMnotifierException
     */
    abstract protected function orm___get_delegate ( $fieldname );



    /**
     * Returns the ORMobjects (with or without the relation attributes) according to the call syntax (see below)
     * @param string $fieldname
     * @param array $arguments: $filters-map = null(default)|int|int[]|(attrib-fieldname=>value)[] , $return_without_relation_attribs = false
     * @return ORMobject|ORMobject[]|array[]
     * @throws Exception
     * @throws ORMnotifierException
     */
    public final function __call ( $fieldname, $arguments )
    {
        $classname = get_class( $this );
        // check if the field is a ORM field; if not, derivate to the method that should get the dynamic non-ORM field
        try {
            $field_specs = ORMmd::get_field_specs( $classname, $fieldname );
        }
        catch (ORMnotifierException $ex) {
            return $this->orm___call_non_orm_attribute( $fieldname, $arguments );
        }
        // return the object, object[] or (object,atribs)[]
        $relation_type = ORMmd::get_fieldspecs_relationtype( $field_specs );
        $filters_map   = isset( $arguments[0] ) ? $arguments[0] : null;
        $ptr           = ORMmt::follow_pointer_as_pairmap( $this->orm_id, $fieldname );
        if ( $relation_type == ORMconfiguration::ASSOC_MULTIPLE_INDIRECT ) {
            $return_without_relation_attribs = ! isset( $arguments[1] ) ? true : ( $arguments[1] === true ? true : false );
            return $ptr->get_objects_with_attribs( $filters_map, $return_without_relation_attribs );
        }
        elseif ( $relation_type == ORMconfiguration::ASSOC_MULTIPLE_DIRECT ) {
            return $ptr->get_objects( $filters_map );
        }
        else {
            return $ptr->get_object( $filters_map );
        }
    }



    /**
     * @param string $fieldname
     * @param array $arguments
     * @return mixed
     * @throws Exception
     * @throws ORMnotifierException
     */
    abstract protected function orm___call_non_orm_attribute( $fieldname, $arguments );



    ///////////////////////////////////
    // OBJECT PROPERTIES UPDATE METHODS
    ///////////////////////////////////



    /**
     * @param string $fieldname
     * @param int|int[]|ORMobject|ORMobject[]|array[] $value
     * @throws Exception
     * @throws ORMnotifierException
     */
    public final function __set ( $fieldname , $value )
    {
        $classname = get_class( $this );
        // check if the field is a ORM field; if not, derivate to the method that should get the dynamic non-ORM field
        try {
            ORMmd::get_field_specs( $classname, $fieldname );   // ignore return value
        }
        catch (ORMnotifierException $ex) {
            $this->orm___set_non_orm_attribute( $fieldname, $value );
        }
        // set the object property
        $ptr = ORMmt::follow_pointer_as_pairmap( $this->orm_id, $fieldname );
        $ptr->set( $fieldname, $value );
    }



    /**
     * @param string $fieldname
     * @param mixed $arguments
     * @throws Exception
     * @throws ORMnotifierException
     */
    abstract protected function orm___set_non_orm_attribute( $fieldname, $arguments );



    /**
     * @param string $fieldname
     * @throws Exception
     * @throws ORMnotifierException
     */
    public final function __unset ( $fieldname )
    {
        $classname = get_class( $this );
        // check if the field is a ORM field; if not, derivate to the method that should get the dynamic non-ORM field
        try {
            ORMmd::get_field_specs( $classname, $fieldname );   // ignore return value
        }
        catch (ORMnotifierException $ex) {
            $this->orm___unset_non_orm_attribute( $fieldname );
        }
        // unset the object property
        $ptr = ORMmt::follow_pointer_as_pairmap( $this->orm_id, $fieldname );
        $ptr->unsett( $fieldname );
    }



    /**
     * @param string $fieldname
     * @throws Exception
     * @throws ORMnotifierException
     */
    abstract protected function orm___unset_non_orm_attribute( $fieldname );



    //////////////////////////////////
    // OBJECT PROPERTIES QUERY METHODS
    //////////////////////////////////



    /**
     * @param string $fieldname
     * @return bool
     */
    public final function __isset ( $fieldname )
    {
        $classname = get_class( $this );
        // check if the field is a ORM field; if not, derivate to the method that should get the dynamic non-ORM field
        try {
            ORMmd::get_field_specs( $classname, $fieldname );   // ignore return value
        }
        catch (ORMnotifierException $ex) {
            return false;
        }
        return true;
    }



    //////////////////////////////////
    // OBJECT PROPERTIES QUERY METHODS
    //////////////////////////////////



    /**
     * Pass an ID or ID[] and this method will return an array with the relation names whose this object's pairmaps point to those IDs
     * Pass null or no argument to check for loop-type relations (relations pointing to the same object)
     * Will return null if there are metadata errors or the argument types are invalid (won't raise exceptions); will return an empty array if no matches
     * Usage: $obj(5) ; $obj(array(6,7,8,...))
     * @param null|int|int[] $id_or_ids_to_match
     * @return null|string[]
     */
    public final function __invoke ( $id_or_ids_to_match = null )
    {
        // check argument
        if ( $id_or_ids_to_match === null ) {
            $id_or_ids_to_match = array( $this->orm_id );
        }
        if ( is_int( $id_or_ids_to_match ) ) {
            $id_or_ids_to_match = array( $id_or_ids_to_match );
        }
        if ( ! is_array( $id_or_ids_to_match ) ) {
            return null;
        }
        $all_are_int = array_reduce( $id_or_ids_to_match, function ( $carry, $item ) { return $carry && is_int( $item ); }, true );
        if ( ! $all_are_int ) {
            return null;
        }
        // check metadata and get all fieldnames
        $classname = get_class( $this );
        try {
            $fieldnames = ORMmd::get_all_fields_names( $classname, true );
        }
        catch (ORMnotifierException $ex) {
            return null;
        }
        // iterate over fieldnames and get matches
        $matched_fieldnames = array ();
        foreach ( $fieldnames as $fieldname ) {
            $fieldname_specs = ORMmd::get_field_specs( $classname, $fieldname );
            $fieldname_type  = ORMmd::get_fieldspecs_relationtype( $fieldname_specs );
            $ptr             = ORMmt::follow_pointer_as_pairmap( $this->orm_id, $fieldname );
            if ( in_array( $fieldname_type, array( ORMconfiguration::ASSOC_MULTIPLE_DIRECT, ORMconfiguration::ASSOC_MULTIPLE_INDIRECT ) ) ) {
                $ids_in_pairmap = $ptr->get_ids();
            }
            else {
                $ids_in_pairmap = array( $ptr->get_id() );
            }
            foreach ( $ids_in_pairmap as $id ) {
                if ( in_array( $id, $id_or_ids_to_match ) ) {
                    $matched_fieldnames[] = $fieldname;
                    break;
                }
            }
        }
        return $matched_fieldnames;
    }



    ///////////////
    // END OF CLASS
    ///////////////



}
