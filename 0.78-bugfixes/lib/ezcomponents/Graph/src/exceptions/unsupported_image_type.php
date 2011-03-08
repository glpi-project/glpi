<?php
/**
 * File containing the ezcGraphGdDriverUnsupportedImageTypeException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown if the image type is not supported and therefore could not
 * be used in the gd driver.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphGdDriverUnsupportedImageTypeException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param int $type
     * @return void
     * @ignore
     */
    public function __construct( $type )
    {
        $typeName = array(
            1 => 'GIF',
            2 => 'Jpeg',
            3 => 'PNG',
            4 => 'SWF',
            5 => 'PSD',
            6 => 'BMP',
            7 => 'TIFF (intel)',
            8 => 'TIFF (motorola)',
            9 => 'JPC',
            10 => 'JP2',
            11 => 'JPX',
            12 => 'JB2',
            13 => 'SWC',
            14 => 'IFF',
            15 => 'WBMP',
            16 => 'XBM',

        );

        if ( isset( $typeName[$type] ) )
        {
            $type = $typeName[$type];
        }
        else
        {
            $type = 'Unknown';
        }

        parent::__construct( "Unsupported image format '{$type}'." );
    }
}

?>
