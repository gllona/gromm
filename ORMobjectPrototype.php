<?php



class ORMobjectPrototype extends ORMobject
{



    const VALUE1  = "value1";
    const VALUE2  = "value2";
    const VALUE3  = "value3";
    const SVALUE1 = "svalue1";
    const SVALUE2 = "svalue2";
    const SVALUE3 = "svalue3";

    private   $attrib1 = self::VALUE1;
    protected $attrib2 = self::VALUE2;
    public    $attrib3 = self::VALUE3;

    static private   $sattrib1 = self::SVALUE1;
    static protected $sattrib2 = self::SVALUE2;
    static public    $sattrib3 = self::SVALUE3;

    private   function method1 () { }
    protected function method2 () { }
    public    function method3 () { }

    static private   function smethod1 () { }
    static protected function smethod2 () { }
    static public    function smethod3 () { }

    private $orm_metadata = null;



    function __construct ( $id )
    {
        parent::__construct( $id );



    }



    /**
     * @return bool
     */
    protected function orm_constructor_delegate ()
    {
        // TODO: Implement orm_constructor_delegate() method.
    }



    /**
     * @return bool
     */
    protected function orm_instance_create_delegate ()
    {
        // TODO: Implement orm_instance_create_delegate() method.
    }



    /**
     * @return bool
     */
    protected function orm_instance_fetch_delegate ()
    {
        // TODO: Implement orm_instance_fetch_delegate() method.
    }



    /**
     * @return bool
     */
    protected function orm_instance_save_delegate ()
    {
        // TODO: Implement orm_instance_save_delegate() method.
    }



    /**
     * @return bool
     */
    protected function orm_instance_delete_delegate ()
    {
        // TODO: Implement orm_instance_delete_delegate() method.
    }



    /**
     * @param string $fieldname
     * @return mixed
     * @throws Exception
     * @throws ORMnotifierException
     */
    protected function orm___get_delegate ( $fieldname )
    {
        // TODO: Implement orm___get_delegate() method.
    }



    /**
     * @param string $fieldname
     * @param array $arguments
     * @return mixed
     * @throws Exception
     * @throws ORMnotifierException
     */
    protected function orm___call_non_orm_attribute ( $fieldname, $arguments )
    {
        // TODO: Implement orm___call_non_orm_attribute() method.
    }



    /**
     * @param string $fieldname
     * @param mixed $arguments
     * @throws Exception
     * @throws ORMnotifierException
     */
    protected function orm___set_non_orm_attribute ( $fieldname, $arguments )
    {
        // TODO: Implement orm___set_non_orm_attribute() method.
    }



    /**
     * @param string $fieldname
     * @throws Exception
     * @throws ORMnotifierException
     */
    protected function orm___unset_non_orm_attribute ( $fieldname )
    {
        // TODO: Implement orm___unset_non_orm_attribute() method.
    }



}