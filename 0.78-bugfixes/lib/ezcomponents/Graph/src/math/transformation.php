<?php
/**
 * File containing the ezcGraphTransformation class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */
/**
 * Class defining transformations
 *
 * Three dimensional matrices (3x3) may be used to specify transformation of
 * points, vectors and complexer structures in a two dimensional cartesian
 * coordinate system. For more details have a look here:
 * http://en.wikipedia.org/wiki/Transformation_matrix
 *
 * There are some classes extending this basic tranformation class, to
 * give you more convinient access to the creation of such transformation
 * matrices, which are:
 * 
 * - ezcGraphRotation (rotations of objects)
 * - ezcGraphTranslation (moving of objects)
 *
 * @version 1.5
 * @package Graph
 * @access private
 */
class ezcGraphTransformation extends ezcGraphMatrix
{
    /**
     * Constructor
     *
     * Creates a matrix with 3x3 dimensions. Optionally accepts an array to 
     * define the initial matrix values. If no array is given an identity 
     * matrix is created.
     * 
     * @param array $values
     * @return void
     */
    public function __construct( array $values = null )
    {
        parent::__construct( 3, 3, $values );
    }

    /**
     * Multiplies two matrices
     *
     * Multiply current matrix with another matrix and returns the result 
     * matrix.
     *
     * @param ezcGraphMatrix $matrix Second factor
     * @return ezcGraphMatrix Result matrix
     */
    public function multiply( ezcGraphMatrix $matrix ) 
    {
        $mColumns = $matrix->columns();

        // We want to ensure, that the matrix stays 3x3
        if ( ( $this->columns !== $matrix->rows() ) &&
             ( $this->rows !== $mColumns ) )
        {
            throw new ezcGraphMatrixInvalidDimensionsException( $this->columns, $this->rows, $mColumns, $matrix->rows() );
        }

        $result = parent::multiply( $matrix );

        // The matrix dimensions stay the same, so that we can modify $this.
        for ( $i = 0; $i < $this->rows; ++$i ) 
        {
            for ( $j = 0; $j < $mColumns; ++$j ) 
            {
                $this->set( $i, $j, $result->get( $i, $j ) );
            }
        }

        return $this;
    }

    /**
     * Transform a coordinate with the current transformation matrix.
     * 
     * @param ezcGraphCoordinate $coordinate 
     * @return ezcGraphCoordinate
     */
    public function transformCoordinate( ezcGraphCoordinate $coordinate )
    {
        $vector = new ezcGraphMatrix( 3, 1, array( array( $coordinate->x ), array( $coordinate->y ), array( 1 ) ) );
        $vector = parent::multiply( $vector );

        return new ezcGraphCoordinate( $vector->get( 0, 0 ), $vector->get( 1, 0 ) );
    }
}

?>
