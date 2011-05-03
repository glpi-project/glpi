<?php
/**
 * File containing the ezcGraphVector class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */
/**
 * Represents two dimensional vectors
 *
 * This class is internally used to represent vectors for geometric calculation
 * in the two dimensional cartesian coordinate system.
 *
 * Vectors are an extension of the basic coordinate class, and add methods to
 * calculate the length of a vector, perform various operations on angles, like
 * rotations and the calculation of angles between two vectors.
 *
 * @version 1.5
 * @package Graph
 * @access private
 */
class ezcGraphVector extends ezcGraphCoordinate
{
    /**
     * Rotates vector to the left by 90 degrees
     * 
     * @return void
     */
    public function rotateCounterClockwise()
    {
        $tmp = $this->x;
        $this->x = $this->y;
        $this->y = -$tmp;

        return $this;
    }

    /**
     * Rotates vector to the right by 90 degrees
     * 
     * @return void
     */
    public function rotateClockwise()
    {
        $tmp = $this->x;
        $this->x = -$this->y;
        $this->y = $tmp;

        return $this;
    }
    
    /**
     * Unifies vector length to 1
     * 
     * @return void
     */
    public function unify()
    {
        $length = $this->length();
        if ( $length == 0 )
        {
            return $this;
        }

        $this->x /= $length;
        $this->y /= $length;

        return $this;
    }

    /**
     * Returns length of vector
     * 
     * @return float
     */
    public function length()
    {
        return sqrt(
            pow( $this->x, 2 ) +
            pow( $this->y, 2 )
        );
    }

    /**
     * Multiplies vector with a scalar
     * 
     * @param float $value 
     * @return void
     */
    public function scalar( $value )
    {
        $this->x *= $value;
        $this->y *= $value;

        return $this;
    }

    /**
     * Calculates scalar product of two vectors
     * 
     * @param ezcGraphCoordinate $vector 
     * @return void
     */
    public function mul( ezcGraphCoordinate $vector )
    {
        return $this->x * $vector->x + $this->y * $vector->y;
    }

    /**
     * Returns the angle between two vectors in radian
     * 
     * @param ezcGraphCoordinate $vector 
     * @return float
     */
    public function angle( ezcGraphCoordinate $vector )
    {
        if ( !$vector instanceof ezcGraphVector )
        {
            // Ensure beeing a vector for calling length()
            $vector = ezcGraphVector::fromCoordinate( $vector );
        }
        
        $factor = $this->length() * $vector->length();

        if ( $factor == 0 )
        {
            return false;
        }
        else
        {
            return acos( $this->mul( $vector ) / $factor );
        }
    }

    /**
     * Adds a vector to another vector
     * 
     * @param ezcGraphCoordinate $vector 
     * @return void
     */
    public function add( ezcGraphCoordinate $vector )
    {
        $this->x += $vector->x;
        $this->y += $vector->y;

        return $this;
    }

    /**
     * Subtracts a vector from another vector
     * 
     * @param ezcGraphCoordinate $vector 
     * @return void
     */
    public function sub( ezcGraphCoordinate $vector )
    {
        $this->x -= $vector->x;
        $this->y -= $vector->y;

        return $this;
    }

    /**
     * Creates a vector from a coordinate object
     * 
     * @param ezcGraphCoordinate $coordinate 
     * @return ezcGraphVector
     */
    public static function fromCoordinate( ezcGraphCoordinate $coordinate )
    {
        return new ezcGraphVector( $coordinate->x, $coordinate->y );
    }

    /**
     * Transform vector using transformation matrix
     * 
     * @param ezcGraphTransformation $transformation 
     * @return ezcGraphVector
     */
    public function transform( ezcGraphTransformation $transformation )
    {
        $result = $transformation->transformCoordinate( $this );

        $this->x = $result->x;
        $this->y = $result->y;

        return $this;
    }
}

?>
