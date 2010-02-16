<?php
/**
 * File containing the abstract ezcGraphPolynom class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Provides a class for generic operations on polynoms
 *
 * This class is mainly used for internal representation of polynoms in the 
 * average dataset ezcGraphDataSetAveragePolynom.
 *
 * It provides only very basic mechanisms to work with polynoms, like adding of
 * polynomes and evaluating the polynom with a given number, to calculate a 
 * point in the chart for a given value on the x axis.
 *
 * Beside this the __toString implementation may be used to echo the polynoms
 * calculated by the least squares mechanism in the above mentioned average
 * datasets. The class does not provide any options to customize the output.
 *
 * The class can be used like:
 * 
 * <code>
 *  // Equivalent to: x^2 + .5
 *  $polynom = new ezcGraphPolynom( array( 2 => 1, 0 => .5 ) );
 *  
 *  // Calculate result for x = 1, echos: 1.5
 *  echo $polynom->evaluate( 1 ), PHP_EOL;
 *  
 *  // Build the sum with another polynom
 *  $polynom->add( new ezcGraphPolynom( array( 1 => 1 ) ) );
 *  
 *  // Print polynom, echos:
 *  // x^2 + x + 5.00e-1
 *  echo $polynom, PHP_EOL;
 * </code>
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphPolynom
{
    /**
     * Factors of the polynom
     *
     * An example:
     *  Polynom:
     *      2 * x^3 + .5 * x - 3
     *  Array:
     *      array (
     *          (int) 3 => (float) 2,
     *          (int) 1 => (float) .5,
     *          (int) 0 => (float) -3,
     *      )
     * 
     * @var array
     */
    protected $values;

    // @TODO: Introduce precision option for string output?

    /**
     * Constructor
     *
     * Constructs a polynom object from given array, where the key is the 
     * exponent and the value the factor.
     * An example:
     *  Polynom:
     *      2 * x^3 + .5 * x - 3
     *  Array:
     *      array (
     *          (int) 3 => (float) 2,
     *          (int) 1 => (float) .5,
     *          (int) 0 => (float) -3,
     *      )
     * 
     * @param array $values Array with values
     * @return ezcGraphPolynom
     */
    public function __construct( array $values = array() )
    {
        foreach ( $values as $exponent => $factor )
        {
            $this->values[(int) $exponent] = (float) $factor;
        }
    }

    /**
     * Initialise a polygon
     *
     * Initialise a polygon of the given order. Sets all factors to 0.
     * 
     * @param int $order Order of polygon
     * @return ezcGraphPolynom Created polynom
     */
    public function init( $order )
    {
        for ( $i = 0; $i <= $order; ++$i )
        {
            $this->values[$i] = 0;
        }

        return $this;
    }

    /**
     * Return factor for one exponent
     * 
     * @param int $exponent Exponent
     * @return float Factor
     */
    public function get( $exponent )
    {
        if ( !isset( $this->values[$exponent] ) )
        {
            return 0;
        }
        else
        {
            return $this->values[$exponent];
        }
    }

    /**
     * Set the factor for one exponent
     * 
     * @param int $exponent Exponent
     * @param float $factor Factor
     * @return ezcGraphPolynom Modified polynom
     */
    public function set( $exponent, $factor )
    {
        $this->values[(int) $exponent] = (float) $factor;

        return $this;
    }

    /**
     * Returns the order of the polynom
     * 
     * @return int Polynom order
     */
    public function getOrder()
    {
        return max( array_keys( $this->values ) );
    }

    /**
     * Adds polynom to current polynom
     * 
     * @param ezcGraphPolynom $polynom Polynom to add 
     * @return ezcGraphPolynom Modified polynom
     */
    public function add( ezcGraphPolynom $polynom )
    {
        $order = max(
            $this->getOrder(),
            $polynom->getOrder()
        );

        for ( $i = 0; $i <= $order; ++$i )
        {
            $this->set( $i, $this->get( $i ) + $polynom->get( $i ) );
        }

        return $this;
    }

    /**
     * Evaluate Polynom with a given value
     * 
     * @param float $x Value
     * @return float Result
     */
    public function evaluate( $x )
    {
        $value = 0;
        foreach ( $this->values as $exponent => $factor )
        {
            $value += $factor * pow( $x, $exponent );
        }

        return $value;
    }

    /**
     * Returns a string represenation of the polynom
     * 
     * @return string String representation of polynom
     */
    public function __toString()
    {
        krsort( $this->values );
        $string = '';

        foreach ( $this->values as $exponent => $factor )
        {
            if ( $factor == 0 )
            {
                continue;
            }

            $string .= ( $factor < 0 ? ' - ' : ' + ' );

            $factor = abs( $factor );
            switch ( true )
            {
                case abs( 1 - $factor ) < .0001:
                    // No not append, if factor is ~1
                    break;
                case $factor < 1:
                case $factor >= 1000:
                    $string .= sprintf( '%.2e ', $factor );
                    break;
                case $factor >= 100:
                    $string .= sprintf( '%.0f ', $factor );
                    break;
                case $factor >= 10:
                    $string .= sprintf( '%.1f ', $factor );
                    break;
                default:
                    $string .= sprintf( '%.2f ', $factor );
                    break;
            }

            switch ( true )
            {
                case $exponent > 1:
                    $string .= sprintf( 'x^%d', $exponent );
                    break;
                case $exponent === 1:
                    $string .= 'x';
                    break;
                case $exponent === 0:
                    if ( abs( 1 - $factor ) < .0001 )
                    {
                        $string .= '1';
                    }
                    break;
            }
        }

        if ( substr( $string, 0, 3 ) === ' + ' )
        {
            $string = substr( $string, 3 );
        }
        else
        {
            $string = '-' . substr( $string, 3 );
        }

        return trim( $string );
    }
}
?>
