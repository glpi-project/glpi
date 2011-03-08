<?php
/**
 * File containing the ezcGraphRotation class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */
/**
 * Class to create rotation matrices from given rotation and center point
 *
 * Three dimensional matrices (3x3) may be used to specify transformation of
 * points, vectors and complexer structures in a two dimensional cartesian
 * coordinate system. For more details have a look here:
 * http://en.wikipedia.org/wiki/Transformation_matrix
 *
 * This class implements a convenient interface to create matrixes to rotate
 * elements. This matrix may be combined with other transformation matrices, as
 * usual.
 *
 * @version 1.5
 * @package Graph
 * @access private
 */
class ezcGraphRotation extends ezcGraphTransformation
{
    /**
     * Rotation in degrees
     * 
     * @var float
     */
    protected $rotation;

    /**
     * Center point 
     * 
     * @var ezcGraphCoordinate
     */
    protected $center;

    /**
     * Construct rotation matrix from rotation (in degrees) and optional 
     * center point.
     * 
     * @param int $rotation 
     * @param ezcGraphCoordinate $center 
     * @return ezcGraphTransformation
     */
    public function __construct( $rotation = 0, ezcGraphCoordinate $center = null )
    {
        $this->rotation = (float) $rotation;

        if ( $center === null )
        {
            $this->center = new ezcGraphCoordinate( 0, 0 );

            $clockwiseRotation = deg2rad( $rotation );
            $rotationMatrixArray = array( 
                array( cos( $clockwiseRotation ), -sin( $clockwiseRotation ), 0 ),
                array( sin( $clockwiseRotation ), cos( $clockwiseRotation ), 0 ),
                array( 0, 0, 1 ),
            );

            return parent::__construct( $rotationMatrixArray );
        }

        parent::__construct();

        $this->center = $center;

        $this->multiply( new ezcGraphTranslation( $center->x, $center->y ) );
        $this->multiply( new ezcGraphRotation( $rotation ) );
        $this->multiply( new ezcGraphTranslation( -$center->x, -$center->y ) );
    }

    /**
     * Return rotaion angle in degrees
     * 
     * @return float
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * Return the center point of the current rotation
     * 
     * @return ezcGraphCoordinate
     */
    public function getCenter()
    {
        return $this->center;
    }
}

?>
