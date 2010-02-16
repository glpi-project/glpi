<?php
/**
 * File containing the ezcBaseFileFindContext class.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Struct which defines the information collected by the file walker for locating files.
 *
 * @package Base
 * @version 1.8
 */
class ezcBaseFileFindContext extends ezcBaseStruct
{
    /**
     * The list of files
     *
     * @var array(string)
     */
    public $elements;

    /**
     * The number of files
     *
     * @var int
     */
    public $count;

    /**
     * The total file size of all files found
     *
     * @var int
     */
    public $size;

    /**
     * Constructs a new ezcBaseFileFindContext with initial values.
     *
     * @param array(string) $elements
     * @param int $count
     * @param int $size
     */
    public function __construct( $elements = array(), $count = 0, $size = 0 )
    {
        $this->elements = $elements;
        $this->count = $count;
        $this->size = $size;
    }

    /**
     * Returns a new instance of this class with the data specified by $array.
     *
     * $array contains all the data members of this class in the form:
     * array('member_name'=>value).
     *
     * __set_state makes this class exportable with var_export.
     * var_export() generates code, that calls this method when it
     * is parsed with PHP.
     *
     * @param array(string=>mixed) $array
     * @return ezcBaseFileFindContext
     */
    static public function __set_state( array $array )
    {
        return new ezcBaseFileFindContext( $array['elements'], $array['count'], $array['size'] );
    }
}
?>
