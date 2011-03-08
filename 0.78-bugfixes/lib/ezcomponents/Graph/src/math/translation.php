<?php
/**
 * File containing the ezcGraphTranslation class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */
/**
 * Class creating translation matrices from given movements
 *
 * Three dimensional matrices (3x3) may be used to specify transformation of
 * points, vectors and complexer structures in a two dimensional cartesian
 * coordinate system. For more details have a look here:
 * http://en.wikipedia.org/wiki/Transformation_matrix
 *
 * This class implements a convenient interface to create matrixes to move
 * elements. This matrix may be combined with other transformation matrices, as
 * usual.
 *
 * @version 1.5
 * @package Graph
 * @access private
 */
class ezcGraphTranslation extends ezcGraphTransformation
{
    /**
     * Constructor
     * 
     * @param float $x 
     * @param float $y 
     * @return void
     * @ignore
     */
    public function __construct( $x = 0., $y = 0. )
    {
        parent::__construct( array( 
            array( 1, 0, $x ),
            array( 0, 1, $y ),
            array( 0, 0, 1 ),
        ) );
    }
}

?>
