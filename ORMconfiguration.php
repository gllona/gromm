<?php



/**
 * Class defining all ORM configuration settings; the only file to be changed for each implementation
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
abstract class ORMconfiguration extends ORMbaseObject
{



    ////////////////////
    // BEHAVIOURAL FLAGS
    ////////////////////



    const RECORDS_TABLE             = "orm_records";
    const RECORDS_TABLE_DB_PK       = "pk";
    const AUTOSAVE_NEW_OBJECTS      = true;
    const AUTOSAVE_ALL_OBJECTS      = true;
    const MT_SAFE_MODE_FOR_OBJECTS  = false;   // TODO set to true @release
    const MT_SAFE_MODE_FOR_PAIRMAPS = false;   // TODO set to true @release



    ////////////////////
    // METADATA SETTINGS
    ////////////////////



    // ORM definitions for record deletions
    //
    const DELETE_SOFT         = 1;
    const DELETE_HARD         = 2;
    const DELETE_COMMON_FIELD = "deleted";

    // ORM relation types
    //
    const ASSOC_SINGLE_INSIDE          =  4;
    const ASSOC_SINGLE_INSIDE_NULLABLE =  8;
    const ASSOC_SINGLE_OUTSIDE         = 16;
    const ASSOC_MULTIPLE_DIRECT        = 32;
    const ASSOC_MULTIPLE_INDIRECT      = 64;



    /////////////////////////////////////////////////
    // METADATA FOR CLASS AND DATABASE REPRESENTATION
    /////////////////////////////////////////////////



    static public $md = array (
        'Paciente' => array ( self::DELETE_SOFT, array (
            'direccion_id_geoentidad' => array ( 'Geoentidad'   , self::ASSOC_SINGLE_INSIDE  , 'ids_pacientes' ),
            'ids_procedimientos'      => array ( 'Procedimiento', self::ASSOC_MULTIPLE_DIRECT, 'id_paciente'   ),
        )),
        'Geoentidad' => array ( self::DELETE_SOFT, array (
            'id_superior'              => array ( 'Geoentidad', self::ASSOC_SINGLE_INSIDE_NULLABLE, 'ids_subordinadas'        ),
            'id_coordinacion_regional' => array ( 'Geoentidad', self::ASSOC_SINGLE_OUTSIDE        , 'direccion_id_geoentidad' ),
            'ids_subordinadas'         => array ( 'Geoentidad', self::ASSOC_MULTIPLE_DIRECT       , 'id_superior'             ),
            'ids_pacientes'            => array ( 'Paciente'  , self::ASSOC_MULTIPLE_DIRECT       , 'direccion_id_geoentidad' ),
            'ids_sedes'                => array ( 'Sede'      , self::ASSOC_MULTIPLE_DIRECT       , 'direccion_id_geoentidad' ),
        )),
        'Sede' => array ( self::DELETE_SOFT, array (
            'direccion_id_geoentidad'                      => array ( 'Geoentidad'                , self::ASSOC_SINGLE_INSIDE    , 'ids_sedes'                   ),
            'id_responsable'                               => array ( 'User'                      , self::ASSOC_SINGLE_INSIDE    , 'id_sede_comoresponsable'     ),
            'ids_colas_sedes'                              => array ( 'Cola_Sede'                 , self::ASSOC_MULTIPLE_DIRECT  , 'id_sede'                     ),
            'ids_inventario_items_movimientos_comofuente'  => array ( 'Inventario_Item_Movimiento', self::ASSOC_MULTIPLE_DIRECT  , 'id_sede_origen'              ),
            'ids_inventario_items_movimientos_comodestino' => array ( 'Inventario_Item_Movimiento', self::ASSOC_MULTIPLE_DIRECT  , 'id_sede_destino'             ),
            'ids_procedimientos'                           => array ( 'Sede_Procedimiento'        , self::ASSOC_MULTIPLE_INDIRECT, 'id_sede', 'id_procedimiento' ),
            'ids_inventario_items_existencia'              => array ( 'Sede_Existencia_Item'      , self::ASSOC_MULTIPLE_INDIRECT, 'id_sede', 'id_item'          ),
            'mediators_to_sede_procedimiento'              => array ( 'Sede_Procedimiento'        , self::ASSOC_MULTIPLE_DIRECT  , 'id_sede'                     ),
        )),
        'Sede_Procedimiento' => array ( self::DELETE_SOFT, array (
            'id_sede'          => array ( 'Sede'         , self::ASSOC_SINGLE_INSIDE, 'mediators_to_sede_procedimiento' ),
            'id_procedimiento' => array ( 'Procedimiento', self::ASSOC_SINGLE_INSIDE, 'mediators_to_sede_procedimiento' ),
        )),
        'Cola_Sede' => array ( self::DELETE_SOFT, array (
            'id_sede'            => array ( 'Sede'         , self::ASSOC_SINGLE_INSIDE  , 'id_sede'      ),
            'ids_citas'          => array ( 'Cita'         , self::ASSOC_MULTIPLE_DIRECT, 'id_cola_sede' ),
            'ids_procedimientos' => array ( 'Procedimiento', self::ASSOC_MULTIPLE_DIRECT, 'id_cola_sede' ),
        )),
        'Especialista' => array ( self::DELETE_SOFT, array (
            'ids_procedimientos'                      => array ( 'Especialista_Procedimiento', self::ASSOC_MULTIPLE_INDIRECT, 'id_especialista', 'id_procedimiento' ),
            'mediators_to_especialista_procedimiento' => array ( 'Especialista_Procedimiento', self::ASSOC_MULTIPLE_DIRECT  , 'id_especialista'                     ),
        )),
        'Patologia_Clase' => array ( self::DELETE_SOFT, array (
            'ids_patologia_subclases' => array ( 'Patologia_SubClase', self::ASSOC_MULTIPLE_DIRECT, 'id_patologia_clase' ),
        )),
        'Patologia_SubClase' => array ( self::DELETE_SOFT, array (
            'id_patologia_clase'            => array ( 'Patologia_Clase'  , self::ASSOC_SINGLE_INSIDE    , 'id_patologia_clase'                        ),
            'ids_procedimientos'            => array ( 'Diagnostico_Item' , self::ASSOC_MULTIPLE_INDIRECT, 'id_patologia_subclase', 'id_procedimiento' ),
            'mediators_to_diagnostico_item' => array ( 'diagnostico_items', self::ASSOC_MULTIPLE_DIRECT  , 'id_patologia_subclase'                     ),
        )),
        'Procedimiento_Clase' => array ( self::DELETE_SOFT, array (
            'ids_procedimiento_subclases' => array ( 'Procedimiento_SubClase', self::ASSOC_MULTIPLE_DIRECT, 'id_procedimiento_clase' ),
        )),
        'Procedimiento_SubClase' => array ( self::DELETE_SOFT, array (
            'id_procedimiento_clase'   => array ( 'Procedimiento_Clase'   , self::ASSOC_SINGLE_INSIDE    , 'id_procedimiento_clase'                 ),
            'ids_procedimientos'       => array ( 'Procedimiento'         , self::ASSOC_MULTIPLE_DIRECT  , 'id_procedimiento_subclase'              ),
            'ids_bom_inventario_items' => array ( 'Procedimiento_BOM_Item', self::ASSOC_MULTIPLE_INDIRECT, 'id_procedimiento', 'id_inventario_item' ),
            'ids_sedes'                => array ( 'Sede_Procedimiento'    , self::ASSOC_MULTIPLE_INDIRECT, 'id_procedimiento', 'id_sede'            ),
        )),
        'Diagnostico_Item' => array ( self::DELETE_SOFT, array (
            'id_procedimiento'      => array ( 'Procedimiento'     , self::ASSOC_SINGLE_INSIDE, 'mediators_to_diagnostico_item' ),
            'id_patologia_subclase' => array ( 'Patologia_SubClase', self::ASSOC_SINGLE_INSIDE, 'mediators_to_diagnostico_item' ),
        )),
        'Cita' => array ( self::DELETE_SOFT, array (
            'id_procedimiento' => array ( 'Procedimiento', self::ASSOC_SINGLE_INSIDE_NULLABLE, 'id_cita'   ),
            'id_cola_sede'     => array ( 'Cola_Sede'    , self::ASSOC_SINGLE_INSIDE         , 'ids_citas' ),
        )),
        'Procedimiento' => array ( self::DELETE_SOFT, array (
            'id_paciente'                             => array ( 'Paciente'                  , self::ASSOC_SINGLE_INSIDE         , 'ids_procedimientos'                        ),
            'id_subclase'                             => array ( 'Procedimiento_SubClase'    , self::ASSOC_SINGLE_INSIDE         , 'ids_procedimientos'                        ),
            'id_anterior'                             => array ( 'Procedimiento'             , self::ASSOC_SINGLE_INSIDE_NULLABLE, 'ids_siguientes'                            ),
            'id_cola_sede'                            => array ( 'Cola_Sede'                 , self::ASSOC_SINGLE_INSIDE_NULLABLE, 'ids_procedimientos'                        ),
            'id_cita'                                 => array ( 'Cita'                      , self::ASSOC_SINGLE_OUTSIDE        , 'id_procedimiento'                          ),
            'ids_siguientes'                          => array ( 'Procedimiento'             , self::ASSOC_MULTIPLE_DIRECT       , 'id_anterior'                               ),
            'ids_especialistas'                       => array ( 'Especialista_Procedimiento', self::ASSOC_MULTIPLE_INDIRECT     , 'id_procedimiento', 'id_especialista'       ),
            'ids_patologias_subclases'                => array ( 'Diagnostico_Item'          , self::ASSOC_MULTIPLE_INDIRECT     , 'id_procedimiento', 'id_patologia_subclase' ),
            'ids_consumo_inventario_items'            => array ( 'Procedimiento_Consumo_Item', self::ASSOC_MULTIPLE_INDIRECT     , 'id_procedimiento', 'id_inventario_item'    ),
            'mediators_to_sede_procedimiento'         => array ( 'Sede_Procedimiento'        , self::ASSOC_MULTIPLE_DIRECT       , 'id_procedimiento'                          ),
            'mediators_to_diagnostico_item'           => array ( 'diagnostico_items'         , self::ASSOC_MULTIPLE_DIRECT       , 'id_procedimiento'                          ),
            'mediators_to_especialista_procedimiento' => array ( 'Especialista_Procedimiento', self::ASSOC_MULTIPLE_DIRECT       , 'id_procedimiento'                          ),
            'mediators_to_procedimiento_bom_item'     => array ( 'Procedimiento_BOM_Item'    , self::ASSOC_MULTIPLE_DIRECT       , 'id_procedimiento'                          ),
            'mediators_to_procedimiento_consumo_item' => array ( 'Procedimiento_Consumo_Item', self::ASSOC_MULTIPLE_DIRECT       , 'id_procedimiento'                          ),
        ), array (
            'Procedimiento_Consulta'   ,
            'Procedimiento_Estudio'    ,
            'Procedimiento_Tratamiento',
            'Procedimiento_Traslado'   ,
        )),
        'Especialista_Procedimiento' => array ( self::DELETE_SOFT, array (
            'id_especialista'  => array ( 'Especialista' , self::ASSOC_SINGLE_INSIDE, 'mediators_to_especialista_procedimiento' ),
            'id_procedimiento' => array ( 'Procedimiento', self::ASSOC_SINGLE_INSIDE, 'mediators_to_especialista_procedimiento' ),
        )),
        'Procedimiento_Traslado' => array ( self::DELETE_SOFT, array (
        ), array (
        ), array (
            'Procedimiento'
        )),
        'Procedimiento_Consulta' => array ( self::DELETE_SOFT, array (
        ), array (
        ), array (
            'Procedimiento'
        )),
        'Procedimiento_Estudio' => array ( self::DELETE_SOFT, array (
        ), array (
        ), array (
            'Procedimiento'
        )),
        'Procedimiento_Tratamiento' => array ( self::DELETE_SOFT, array (
        ), array (
            'Procedimiento_Tratamiento_Invasivo'             ,
            'Procedimiento_Tratamiento_NoInvasivo'           ,
            'Procedimiento_Tratamiento_EntregaDeLentes'      ,
            'Procedimiento_Tratamiento_EntregaDeMedicamentos',
        ), array (
            'Procedimiento'
        )),
        'Procedimiento_Tratamiento_Invasivo' => array ( self::DELETE_SOFT, array (
        ), array (
        ), array (
            'Procedimiento_Tratamiento'
        )),
        'Procedimiento_Tratamiento_NoInvasivo' => array ( self::DELETE_SOFT, array (
        ), array (
        ), array (
            'Procedimiento_Tratamiento'
        )),
        'Procedimiento_Tratamiento_EntregaDeLentes' => array ( self::DELETE_SOFT, array (
        ), array (
        ), array (
            'Procedimiento_Tratamiento'
        )),
        'Procedimiento_Tratamiento_EntregaDeMedicamentos' => array ( self::DELETE_SOFT, array (
        ), array (
        ), array (
            'Procedimiento_Tratamiento'
        )),
        'Procedimiento_BOM_Item' => array ( self::DELETE_SOFT, array (
            'id_procedimiento' => array ( 'Procedimiento'  , self::ASSOC_SINGLE_INSIDE, 'mediators_to_procedimiento_bom_item' ),
            'id_item'          => array ( 'Inventario_Item', self::ASSOC_SINGLE_INSIDE, 'mediators_to_procedimiento_bom_item' ),
        )),
        'Procedimiento_Consumo_Item' => array ( self::DELETE_SOFT, array (
            'id_procedimiento' => array ( 'Procedimiento'  , self::ASSOC_SINGLE_INSIDE, 'mediators_to_procedimiento_consumo_item' ),
            'id_item'          => array ( 'Inventario_Item', self::ASSOC_SINGLE_INSIDE, 'mediators_to_procedimiento_consumo_item' ),
        )),
        'Inventario_Categoria' => array ( self::DELETE_SOFT, array (
            'id_superior'      => array ( 'Inventario_Categoria', self::ASSOC_SINGLE_INSIDE_NULLABLE, 'ids_subordinadas' ),
            'ids_subordinadas' => array ( 'Inventario_Categoria', self::ASSOC_MULTIPLE_DIRECT       , 'id_superior'      ),
            'ids_items'        => array ( 'Inventario_Item'     , self::ASSOC_MULTIPLE_DIRECT       , 'id_categoria'     ),
        )),
        'Inventario_Item' => array ( self::DELETE_SOFT, array (
            'id_categoria'                            => array ( 'Inventario_Categoria'      , self::ASSOC_SINGLE_INSIDE    , 'ids_items'          ),
            'ids_movimientos'                         => array ( 'Inventario_Item_Movimiento', self::ASSOC_MULTIPLE_DIRECT  , 'id_item'            ),
            'ids_sedes_porexistencia'                 => array ( 'Sede_Existencia_Item'      , self::ASSOC_MULTIPLE_INDIRECT, 'id_item', 'id_sede' ),
            'ids_procedimientos_porbom'               => array ( 'Procedimiento_BOM_Item'    , self::ASSOC_MULTIPLE_INDIRECT, 'id_inventario_item' ),
            'ids_procedimientos_porconsumos'          => array ( 'Procedimiento_Consumo_Item', self::ASSOC_MULTIPLE_INDIRECT, 'id_inventario_item' ),
            'mediators_to_procedimiento_bom_item'     => array ( 'Procedimiento_BOM_Item'    , self::ASSOC_MULTIPLE_DIRECT  , 'id_item'            ),
            'mediators_to_procedimiento_consumo_item' => array ( 'Procedimiento_Consumo_Item', self::ASSOC_MULTIPLE_DIRECT  , 'id_item'            ),
        )),
        'Inventario_Item_Movimiento' => array ( self::DELETE_SOFT, array (
            'id_item'                                  => array ( 'Inventario_Item', self::ASSOC_SINGLE_INSIDE, 'ids_movimientos'                              ),
            'id_sede_origen'                           => array ( 'Sede'           , self::ASSOC_SINGLE_INSIDE, 'ids_inventario_items_movimientos_comofuente'  ),
            'id_sede_destino'                          => array ( 'Sede'           , self::ASSOC_SINGLE_INSIDE, 'ids_inventario_items_movimientos_comodestino' ),
            'id_user_movilizador_inventario_enfuente'  => array ( 'User'           , self::ASSOC_SINGLE_INSIDE, 'ids_inventario_items_movimientos_comofuente'  ),
            'id_user_movilizador_inventario_endestino' => array ( 'User'           , self::ASSOC_SINGLE_INSIDE, 'ids_inventario_items_movimientos_comodestino' ),
        )),
        'Sede_Existencia_Item' => array ( self::DELETE_SOFT, array (
            'id_sede' => array ( 'Sede'           , self::ASSOC_SINGLE_INSIDE, 'ids_inventario_items_existencia' ),
            'id_item' => array ( 'Inventario_Item', self::ASSOC_SINGLE_INSIDE, 'ids_sedes_porexistencia'         ),
        )),
        'Coordinacion_Regional' => array ( self::DELETE_SOFT, array (
            'id_direccion_geoentidad' => array ( 'Geoentidad', self::ASSOC_SINGLE_INSIDE, 'id_coordinacion_regional'                 ),
            'id_responsable'          => array ( 'User'      , self::ASSOC_SINGLE_INSIDE, 'id_coordinacion_regional_comoresponsable' ),
            'id_mision'               => array ( 'Mision'    , self::ASSOC_SINGLE_INSIDE, 'ids_coordinaciones_regionales'            ),
        )),
        'Mision' => array ( self::DELETE_SOFT, array (
            'ids_coordinaciones_regionales' => array ( 'Coordinacion_Regional', self::ASSOC_MULTIPLE_DIRECT ),
            'ids_directivos'                => array ( 'Directivo'            , self::ASSOC_MULTIPLE_DIRECT ),
        )),
        'Directivo' => array ( self::DELETE_SOFT, array (
            'id_mision' => array ( 'Mision', self::ASSOC_SINGLE_INSIDE         , 'ids_directivos' ),
            'id_user'   => array ( 'User'  , self::ASSOC_SINGLE_INSIDE_NULLABLE, 'id_directivo'   ),
        )),
        'User' => array ( self::DELETE_SOFT, array (
            'id_superior'                                  => array ( 'User'                      , self::ASSOC_SINGLE_INSIDE_NULLABLE                                    ),
            'id_directivo'                                 => array ( 'Directivo'                 , self::ASSOC_SINGLE_OUTSIDE         , 'id_user'                        ),
            'id_sede_comoresponsable'                      => array ( 'Sede'                      , self::ASSOC_SINGLE_OUTSIDE         , 'id_responsable'                 ),
            'id_coordinacion_regional_comoresponsable'     => array ( 'Coordinacion_Regional'     , self::ASSOC_SINGLE_OUTSIDE         , 'id_responsable'                 ),
            'ids_subordinados'                             => array ( 'User'                      , self::ASSOC_MULTIPLE_DIRECT        , 'id_superior'                    ),
            'ids_inventario_items_movimientos_comofuente'  => array ( 'Inventario_Item_Movimiento', self::ASSOC_MULTIPLE_DIRECT        , 'id_usuario_responsable_fuente'  ),
            'ids_inventario_items_movimientos_comodestino' => array ( 'Inventario_Item_Movimiento', self::ASSOC_MULTIPLE_DIRECT        , 'id_usuario_responsable_destino' ),
        )),
        'Log' => array ( self::DELETE_HARD, array (
            'id_user' => array ( 'User', self::ASSOC_SINGLE_INSIDE_NULLABLE ),
        )),
        'DB_Record' => array ( self::DELETE_HARD, array (
        )),
    );



    ///////////////////////////
    // INITIALIZER, CONSTRUCTOR
    ///////////////////////////



    static function init ()
    {
        // parent::init();
    }



    function __construct ()
    {
        throw new ORMexception("ORMconfiguration creation is not allowed (should be used as a static singleton)");
    }



}
