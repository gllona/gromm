<?php



/**
 * Metadata behaviours and tools
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
final class ORMmd extends ORMbaseObject {



    /**
     * @param string $classname
     * @return string
     */
    static final public function table_name ( $classname )
    {
        return strtolower( $classname );
    }



    /**
     * @param string $classname
     * @return bool
     */
    static final public function is_soft_delete ( $classname )
    {
        return self::is_this_delete_internals( $classname, ORMconfiguration::DELETE_SOFT );
    }



    /**
     * @param string $classname
     * @return bool
     */
    static final public function is_hard_delete ( $classname )
    {
        return self::is_this_delete_internals( $classname, ORMconfiguration::DELETE_HARD );
    }



    /**
     * Don't use this method
     * @param string $classname
     * @param int $type
     * @return bool
     * @throws ORMnotifierException
     */
    static private function is_this_delete_internals ( $classname, $type )
    {
        if ( ! isset(ORMconfiguration::$md[$classname]) ) {
            throw new ORMnotifierException( "no metadata for class $classname", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        $specs = ORMconfiguration::$md[$classname];
        if ( ! is_array($specs) ) {
            throw new ORMnotifierException( "malformed metadata for class $classname (should be array)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        if ( count($specs) < 2 ) {
            throw new ORMnotifierException( "malformed metadata for class $classname (array must have at least 2 members)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        if ( ! is_int($specs[0]) ) {
            throw new ORMnotifierException( "malformed metadata for class $classname (array[0] must be integer)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        switch ($specs[0]) {
            case ORMconfiguration::DELETE_SOFT :
            case ORMconfiguration::DELETE_HARD :
                break;
            default:
                throw new ORMnotifierException( "malformed metadata for class $classname (wrong value for array[0])", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        return $specs[0] == $type;
    }



    /**
     * @param string $classname
     * @return null|array
     * @throws ORMnotifierException
     */
    static final public function get_children_classes ( $classname )
    {
        self::is_hard_delete( $classname );   // only for throwing exceptions
        $fields_specs = ORMconfiguration::$md[$classname][1];
        if ( ! is_array($fields_specs[1]) ) {
            throw new ORMnotifierException( "malformed metadata for class $classname (array[1] must be array)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        if ( ! isset(ORMconfiguration::$md[$classname][2]) || isset(ORMconfiguration::$md[$classname][2]) === null ) {
            return null;   // no children classes
        }
        $children_specs = ORMconfiguration::$md[$classname][2];
        if ( !( is_array($children_specs) || $children_specs === null ) ) {
            throw new ORMnotifierException( "malformed metadata for class $classname (array[2] must be array or null)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        foreach ( ORMconfiguration::$md[$classname][2] as $child_name ) {
            if ( ! isset(ORMconfiguration::$md[$child_name]) ) {
                throw new ORMnotifierException( "no metadata for child class $child_name of class $classname", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
            }
        }
        return ORMconfiguration::$md[$classname][2];
    }



    /**
     * @param string $classname
     * @return null|string
     * @throws ORMnotifierException
     */
    static final public function get_parent_class ( $classname )
    {
        self::get_children_classes( $classname );   // only for throwing exceptions
        if ( ! isset(ORMconfiguration::$md[$classname][3]) || ORMconfiguration::$md[$classname][3] === null ) {
            return null;   // no parent class
        }
        $parent_specs = ORMconfiguration::$md[$classname][3];
        if ( !( is_array($parent_specs) || is_string($parent_specs) ) ) {
            throw new ORMnotifierException( "malformed metadata for class $classname: array[3] must be string, array or null", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        if ( is_string($parent_specs) )
            $parent_name = $parent_specs;
        elseif ( isset($parent_specs[0]) && is_string($parent_specs[0]) )
            $parent_name = $parent_specs[0];
        else {
            throw new ORMnotifierException( "malformed metadata for class $classname: array[3][0] must be string when array[3] is array", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        if ( ! isset(ORMconfiguration::$md[$parent_name]) ) {
            throw new ORMnotifierException( "no metadata for parent class $parent_name of $classname", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        return $parent_name;
    }



    /**
     * @param string $classname
     * @param bool $include_parent_classes
     * @return string[]
     * @throws ORMnotifierException
     */
    static final public function get_all_fields_names ( $classname, $include_parent_classes = false )
    {
        self::is_hard_delete( $classname, true );   // only for throwing exceptions
        $all_fields = array ();
        do {
            $fields_specs = ORMconfiguration::$md[$classname][1];
            if ( ! is_array($fields_specs[1]) ) {
                throw new ORMnotifierException( "malformed metadata for class $classname (array[1] must be array)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
            }
            $all_fields = array_merge( $all_fields, $fields_specs[1] );
            $classname = self::get_parent_class( $classname );
        } while ( $include_parent_classes && $classname !== null );
        return $all_fields;
    }



    /**
     * @param string $classname
     * @param string $fieldname
     * @return array
     * @throws ORMnotifierException
     */
    static final public function get_field_specs ( $classname, $fieldname )
    {
        return self::get_field_specs_internals( $classname, $fieldname, true, false );
    }



    /**
     * Don't use this method
     * @param string $classname
     * @param string $fieldname
     * @param bool $follow_links_when_checking
     * @return array
     * @throws ORMnotifierException
     */
    static private function get_field_specs_internals ( $classname, $fieldname, $follow_links_when_checking = true )
    {
        self::is_hard_delete( $classname, true );   // only for throwing exceptions
        $fields_specs = ORMconfiguration::$md[$classname][1];
        if ( ! is_array($fields_specs[1]) ) {
            throw new ORMnotifierException( "malformed metadata for class $classname (array[1] must be array)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        if ( ! isset($fields_specs[1][$fieldname]) ) {
            throw new ORMnotifierException( "no metadata for field $fieldname of $classname", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        $field_spec = $fields_specs[1][$fieldname];
        if ( ! is_array($field_spec) || count($field_spec) < 3 ) {
            throw new ORMnotifierException( "metadata for each field should be array and have at least 3 members (checking field $fieldname of $classname)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }
        $counterpart_classname = $field_spec[0];
        $relation_type         = $field_spec[1];
        if ( ! is_string($counterpart_classname) || ! isset(ORMconfiguration::$md[$counterpart_classname]) ) {
            throw new ORMnotifierException( "metadata for field $fieldname of $classname should refer to another (counterpart) class (array[0] should also be string)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }

        switch ($relation_type) {

            case ORMconfiguration::ASSOC_SINGLE_INSIDE          :
            case ORMconfiguration::ASSOC_SINGLE_INSIDE_NULLABLE :
            case ORMconfiguration::ASSOC_SINGLE_OUTSIDE         :
            case ORMconfiguration::ASSOC_MULTIPLE_DIRECT        :
                $target_classname = $counterpart_classname;
                $target_class_counterpart_fieldname = isset($field_spec[2]) && is_string($field_spec[2]) ? $field_spec[2] : null;
                if ( $target_class_counterpart_fieldname === null ) {
                    throw new ORMnotifierException( "invalid metadata specification for field $fieldname of $classname (member 3 should be string)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( isset($field_spec[3]) ) {
                    throw new ORMnotifierException( "extra field in metadata specification for field $fieldname of $classname (should have only 3 members)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                $target_class_counterpart_field_spec = self::get_field_specs_internals( $target_classname, $target_class_counterpart_fieldname, false );
                if ( $target_class_counterpart_field_spec === null ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $target_class_counterpart_fieldname of target class $target_classname, referred by field $fieldname of $classname was not found", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( $target_class_counterpart_field_spec[0] != $classname ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $target_class_counterpart_fieldname of target class $target_classname, referred by field $fieldname of $classname doesn't refer to the source class", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                switch ($relation_type) {
                    case ORMconfiguration::ASSOC_SINGLE_INSIDE          : $counterpart_relation_types = array ( ORMconfiguration::ASSOC_SINGLE_OUTSIDE, ORMconfiguration::ASSOC_MULTIPLE_DIRECT ); break;
                    case ORMconfiguration::ASSOC_SINGLE_INSIDE_NULLABLE : $counterpart_relation_types = array ( ORMconfiguration::ASSOC_SINGLE_OUTSIDE                                          ); break;
                    case ORMconfiguration::ASSOC_SINGLE_OUTSIDE         : $counterpart_relation_types = array ( ORMconfiguration::ASSOC_SINGLE_INSIDE                                           ); break;
                    case ORMconfiguration::ASSOC_MULTIPLE_DIRECT        : $counterpart_relation_types = array ( ORMconfiguration::ASSOC_SINGLE_INSIDE                                           ); break;
                    default                                             : $counterpart_relation_types = array ();
                }
                if ( ! in_array( $target_class_counterpart_field_spec[1], $counterpart_relation_types ) ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $target_class_counterpart_fieldname of target class $target_classname, referred by field $fieldname of $classname has a wrong relation type (allowed: " . implode(",", $counterpart_relation_types) . ")", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                $full_field_spec = array (
                    $field_spec,
                    null,
                    null,
                    $target_class_counterpart_field_spec,
                );
                break;

            case ORMconfiguration::ASSOC_MULTIPLE_INDIRECT :
                // walk to the mediator class and look back
                $mediator_classname                   = $counterpart_classname;
                $mediator_class_counterpart_fieldname = isset($field_spec[2]) && is_string($field_spec[2]) ? $field_spec[2] : null;
                $mediator_class_linking_fieldname     = isset($field_spec[3]) && is_string($field_spec[3]) ? $field_spec[3] : null;
                if ( $mediator_class_counterpart_fieldname === null || $mediator_class_linking_fieldname === null ) {
                    throw new ORMnotifierException( "invalid metadata specification for field $fieldname of $classname (members 3 and 4 should be strings)", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( isset($field_spec[4]) ) {
                    throw new ORMnotifierException( "extra field in metadata specification for field $fieldname of $classname (should have only 4 members)" );
                }
                $mediator_class_counterpart_field_spec = self::get_field_specs_internals( $mediator_classname, $mediator_class_counterpart_fieldname, false );
                if ( $mediator_class_counterpart_field_spec === null ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $mediator_class_counterpart_fieldname of target class $mediator_classname, referred by field $fieldname of $classname was not found", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( $mediator_class_counterpart_field_spec[0] != $classname ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $mediator_class_counterpart_fieldname of target class  $mediator_classname, referred by field $fieldname of $classname doesn't refer to the source class", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( $mediator_class_counterpart_field_spec[1] !== ORMconfiguration::ASSOC_SINGLE_INSIDE ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $mediator_class_counterpart_fieldname of target class $mediator_classname, referred by field $fieldname of $classname should define a single_inside (not nullable) relation", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( ! $follow_links_when_checking ) {
                    $full_field_spec = array (
                        $field_spec,
                        $mediator_class_counterpart_field_spec,
                        null,
                        null,
                    );
                    break;   // don't follow the path to the target class
                }
                // now walk forward on the linking path to the target class
                $mediator_class_linking_field_spec = self::get_field_specs_internals( $mediator_classname, $mediator_class_linking_fieldname, false );
                if ( $mediator_class_linking_field_spec === null ) {
                    throw new ORMnotifierException( "metadata specification for linking field $mediator_class_linking_fieldname of target class $mediator_classname, referred by field $fieldname of $classname was not found", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( $mediator_class_linking_field_spec[1] !== ORMconfiguration::ASSOC_SINGLE_INSIDE ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $mediator_class_linking_fieldname of target class $mediator_classname, referred by field $fieldname of $classname should define a single_inside (not nullable) relation", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                // finally walk to the target class and look back
                $target_classname                    = $mediator_class_linking_field_spec[0];
                $target_class_counterpart_fieldname  = isset($mediator_class_linking_field_spec[1]) && is_string($mediator_class_linking_field_spec[2]) ? $mediator_class_linking_field_spec[2] : null;
                $target_class_counterpart_field_spec = self::get_field_specs_internals( $target_classname, $target_class_counterpart_fieldname, false, true );
                if ( $target_class_counterpart_field_spec === null ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $target_class_counterpart_fieldname of target  class $target_classname, referred by field $fieldname of $classname was not found", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( $target_class_counterpart_field_spec[0] !== $mediator_classname ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $target_class_counterpart_fieldname of target class $target_classname, referred by field $fieldname of $classname doesn't refer to the source class", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                if ( $mediator_class_counterpart_field_spec[1] !== ORMconfiguration::ASSOC_MULTIPLE_INDIRECT ) {
                    throw new ORMnotifierException( "metadata specification for counterpart field $target_class_counterpart_fieldname of target class $target_classname, referred by field $fieldname of $classname should define a multiple_indirect relation", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
                }
                $full_field_spec = array (
                    $field_spec,
                    $mediator_class_counterpart_field_spec,
                    $mediator_class_linking_field_spec,
                    $target_class_counterpart_field_spec,
                );
                break;

            default:
                throw new ORMnotifierException( "metadata for field $fieldname should specify a valid relation type", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
        }

        return $full_field_spec;
    }



    static final public function get_fieldspecs_classname    ( $fieldspecs ) { return $fieldspecs[0]; }
    static final public function get_fieldspecs_relationtype ( $fieldspecs ) { return $fieldspecs[1]; }
    static final public function get_fieldspecs_backlink     ( $fieldspecs ) { return $fieldspecs[2]; }
    static final public function get_fieldspecs_forwardlink  ( $fieldspecs ) { return count( $fieldspecs ) < 3 ? null : $fieldspecs[3]; }



    /**
     * @throws ORMnotifierException
     */
    static final public function test_all ()
    {
        foreach ( ORMconfiguration::$md as $classname => $class_specs ) {
            if ( ! is_array($class_specs) || ! isset($class_specs[1]) || ! is_array($class_specs[1]) ) {
                throw new ORMnotifierException( "bad metadata specification for class $class_specs when testing all metadata", ORMnotifierException::NO_OR_BAD_METADATA_FOUND );
            }
            foreach ( array_keys($class_specs[1]) as $fieldname ) {
                self::get_field_specs($classname, $fieldname);
            }
        }
    }



    ///////////////////////////
    // INITIALIZER, CONSTRUCTOR
    ///////////////////////////



    static function init ()
    {
        // parent::init();
        self::test_all();
    }



    function __construct ()
    {
        throw new ORMexception("ORMconfiguration creation is not allowed (should be used as a static singleton)");
    }



}
