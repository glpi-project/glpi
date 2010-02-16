<?php
/**
 * File containing the ezcGraphChartElementLogarithmicalAxis class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class to represent a logarithmic axis.
 *
 * Axis elements represent the axis in a bar, line or radar chart. They are
 * chart elements (ezcGraphChartElement) extending from
 * ezcGraphChartElementAxis, where additional formatting options can be found.
 * You should generally use the axis, which matches your input data best, so
 * that the automatic chart layouting works best. Aavailable axis types are:
 *
 * - ezcGraphChartElementDateAxis
 * - ezcGraphChartElementLabeledAxis
 * - ezcGraphChartElementLogarithmicalAxis
 * - ezcGraphChartElementNumericAxis
 *
 * Logarithmic axis are normally used to display very large or small values.
 * Logarithmic axis can not be used for value spans including zero, so you
 * should either pass only positive or only negative values to the chart.
 *
 * By default the axis uses a base of 10 for scaling, you may assign any other
 * base to the $base property of the chart. With a base of 10 the steps on the
 * axis may, for example, be at: 1, 10, 100, 1000, 10000, ...
 *
 * The logarithmic axis may be used like:
 *
 * <code>
 *  $graph = new ezcGraphLineChart();
 *  $graph->title = 'The power of x';
 *  $graph->legend->position = ezcGraph::BOTTOM;
 *  
 *  $graph->xAxis = new ezcGraphChartElementNumericAxis();
 *  $graph->yAxis = new ezcGraphChartElementLogarithmicalAxis();
 *  
 *  $graph->data['x^2'] = new ezcGraphNumericDataSet( 
 *      -10, 10,
 *      create_function( '$x', 'return pow( $x, 2 ) + 1;' )
 *  );
 *  
 *  $graph->data['x^4'] = new ezcGraphNumericDataSet( 
 *      -10, 10,
 *      create_function( '$x', 'return pow( $x, 4 ) + 1;' )
 *  );
 *  
 *  $graph->data['x^6'] = new ezcGraphNumericDataSet( 
 *      -10, 10,
 *      create_function( '$x', 'return pow( $x, 6 ) + 1;' )
 *  );
 *  
 *  $graph->render( 400, 250, 'tutorial_axis_logarithmic.svg' );
 * </code>
 *
 * @property float $base
 *           Base for logarithmical scaling.
 * @property string $logarithmicalFormatString
 *           Sprintf formatstring for the axis labels where
 *              $1 is the base and
 *              $2 is the exponent.
 * @property-read float $minValue
 *                Minimum Value to display on this axis.
 * @property-read float $maxValue
 *                Maximum value to display on this axis.
 *           
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphChartElementLogarithmicalAxis extends ezcGraphChartElementAxis
{

    /**
     * Constant used for calculation of automatic definition of major scaling 
     * steps
     */
    const MAX_STEPS = 9;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['min'] = null;
        $this->properties['max'] = null;
        $this->properties['base'] = 10;
        $this->properties['logarithmicalFormatString'] = '%1$d^%2$d';
        $this->properties['minValue'] = null;
        $this->properties['maxValue'] = null;

        parent::__construct( $options );
    }

    /**
     * __set 
     * 
     * @param mixed $propertyName 
     * @param mixed $propertyValue 
     * @throws ezcBaseValueException
     *          If a submitted parameter was out of range or type.
     * @throws ezcBasePropertyNotFoundException
     *          If a the value for the property options is not an instance of
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'min':
            case 'max':
                if ( !is_numeric( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                $this->properties['initialized'] = true;
                break;
            case 'base':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;
            case 'logarithmicalFormatString':
                $this->properties['logarithmicalFormatString'] = (string) $propertyValue;
                break;
            default:
                parent::__set( $propertyName, $propertyValue );
                break;
        }
    }
    
    /**
     * Add data for this axis
     * 
     * @param array $values Value which will be displayed on this axis
     * @return void
     */
    public function addData( array $values )
    {
        foreach ( $values as $value )
        {
            if ( $this->properties['minValue'] === null ||
                 $value < $this->properties['minValue'] )
            {
                $this->properties['minValue'] = $value;
            }

            if ( $this->properties['maxValue'] === null ||
                 $value > $this->properties['maxValue'] )
            {
                $this->properties['maxValue'] = $value;
            }
        }

        $this->properties['initialized'] = true;
    }

    /**
     * Calculate axis bounding values on base of the assigned values 
     * 
     * @abstract
     * @access public
     * @return void
     */
    public function calculateAxisBoundings()
    {
        // Prevent division by zero, when min == max
        if ( $this->properties['minValue'] == $this->properties['maxValue'] )
        {
            if ( $this->properties['minValue'] == 0 )
            {
                $this->properties['maxValue'] = 1;
            }
            else
            {
                $this->properties['minValue'] -= ( $this->properties['minValue'] * .1 );
                $this->properties['maxValue'] += ( $this->properties['maxValue'] * .1 );
            }
        }

        if ( $this->properties['minValue'] <= 0 )
        {
            throw new ezcGraphOutOfLogithmicalBoundingsException( $this->properties['minValue'] );
        }

        // Use custom minimum and maximum if available
        if ( $this->properties['min'] !== null )
        {
            $this->properties['minValue'] = pow( $this->properties['base'], $this->properties['min'] );
        }

        if ( $this->properties['max'] !== null )
        {
            $this->properties['maxValue'] = pow( $this->properties['base'], $this->properties['max'] );
        }

        // Calculate "nice" values for scaling parameters
        if ( $this->properties['min'] === null )
        {
            $this->properties['min'] = floor( log( $this->properties['minValue'], $this->properties['base'] ) );
        }

        if ( $this->properties['max'] === null )
        {
            $this->properties['max'] = ceil( log( $this->properties['maxValue'], $this->properties['base'] ) );
        }

        $this->properties['minorStep'] = 1;
        if ( ( $modifier = ( ( $this->properties['max'] - $this->properties['min'] ) / self::MAX_STEPS ) ) > 1 )
        {
            $this->properties['majorStep'] = $modifier = ceil( $modifier );
            $this->properties['min'] = floor( $this->properties['min'] / $modifier ) * $modifier;
            $this->properties['max'] = floor( $this->properties['max'] / $modifier ) * $modifier;
        }
        else
        {
            $this->properties['majorStep'] = 1;
        }
    }

    /**
     * Get coordinate for a dedicated value on the chart
     * 
     * @param float $value Value to determine position for
     * @return float Position on chart
     */
    public function getCoordinate( $value )
    {
        // Force typecast, because ( false < -100 ) results in (bool) true
        $floatValue = (float) $value;

        if ( $value === false )
        {
            switch ( $this->position )
            {
                case ezcGraph::LEFT:
                case ezcGraph::TOP:
                    return 0.;
                case ezcGraph::RIGHT:
                case ezcGraph::BOTTOM:
                    return 1.;
            }
        }
        else
        {
            $position = ( log( $value, $this->properties['base'] ) - $this->properties['min'] ) / ( $this->properties['max'] - $this->properties['min'] );

            switch ( $this->position )
            {
                case ezcGraph::LEFT:
                case ezcGraph::TOP:
                    return $position;
                case ezcGraph::RIGHT:
                case ezcGraph::BOTTOM:
                    return 1 - $position;
            }
        }
    }

    /**
     * Return count of minor steps
     * 
     * @return integer Count of minor steps
     */
    public function getMinorStepCount()
    {
        return (int) ( ( $this->properties['max'] - $this->properties['min'] ) / $this->properties['minorStep'] );
    }

    /**
     * Return count of major steps
     * 
     * @return integer Count of major steps
     */
    public function getMajorStepCount()
    {
        return (int) ( ( $this->properties['max'] - $this->properties['min'] ) / $this->properties['majorStep'] );
    }

    /**
     * Get label for a dedicated step on the axis
     * 
     * @param integer $step Number of step
     * @return string label
     */
    public function getLabel( $step )
    {
        if ( $this->properties['labelCallback'] !== null )
        {
            return call_user_func_array(
                $this->properties['labelCallback'],
                array(
                    sprintf( 
                        $this->properties['logarithmicalFormatString'],
                        $this->properties['base'],
                        $this->properties['min'] + ( $step * $this->properties['majorStep'] )
                    ),
                    $step,
                )
            );
        }
        else
        {
            return sprintf( 
                $this->properties['logarithmicalFormatString'],
                $this->properties['base'],
                $this->properties['min'] + ( $step * $this->properties['majorStep'] )
            );
        }
    }

    /**
     * Is zero step
     *
     * Returns true if the given step is the one on the initial axis position
     * 
     * @param int $step Number of step
     * @return bool Status If given step is initial axis position
     */
    public function isZeroStep( $step )
    {
        return ( $step == 0 );
    }
}

?>
