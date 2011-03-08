<?php
/**
 * File containing the ezcGraphChartElementDateAxis class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class to represent date axis.
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
 * Date axis will try to find a "nice" interval based on the values on the x
 * axis. If non numeric values are given, ezcGraphChartElementDateAxis will
 * convert them to timestamps using PHPs strtotime function.
 *
 * It is always possible to set start date, end date and the interval manually
 * by yourself.
 *
 * The $dateFormat option provides an additional way of formatting the labels
 * used on the axis. The options from the parent class $formatString and
 * $labelCallback do still apply.
 *
 * You may use a date axis like in the following example:
 *
 * <code>
 *  $graph = new ezcGraphLineChart();
 *  $graph->options->fillLines = 210;
 *  $graph->title = 'Concurrent requests';
 *  $graph->legend = false;
 *  
 *  $graph->xAxis = new ezcGraphChartElementDateAxis();
 *  
 *  // Add data
 *  $graph->data['Machine 1'] = new ezcGraphArrayDataSet( array(
 *      '8:00' => 3241,
 *      '8:13' => 934,
 *      '8:24' => 1201,
 *      '8:27' => 1752,
 *      '8:51' => 123,
 *  ) );
 *  $graph->data['Machine 2'] = new ezcGraphArrayDataSet( array(
 *      '8:05' => 623,
 *      '8:12' => 2103,
 *      '8:33' => 543,
 *      '8:43' => 2034,
 *      '8:59' => 3410,
 *  ) );
 *  
 *  $graph->data['Machine 1']->symbol = ezcGraph::BULLET;
 *  $graph->data['Machine 2']->symbol = ezcGraph::BULLET;
 *  
 *  $graph->render( 400, 150, 'tutorial_axis_datetime.svg' );
 * </code>
 *
 * @property float $startDate
 *           Starting date used to display on axis.
 * @property float $endDate
 *           End date used to display on axis.
 * @property float $interval
 *           Time interval between steps on axis.
 * @property string $dateFormat
 *           Format of date string
 *           Like http://php.net/date
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphChartElementDateAxis extends ezcGraphChartElementAxis
{
    
    const MONTH = 2629800;

    const YEAR = 31536000;

    const DECADE = 315360000;

    /**
     * Minimum inserted date
     * 
     * @var int
     */
    protected $minValue = false;

    /**
     * Maximum inserted date 
     * 
     * @var int
     */
    protected $maxValue = false;

    /**
     * Nice time intervals to used if there is no user defined interval
     * 
     * @var array
     */
    protected $predefinedIntervals = array(
        // Second
        1           => 'H:i.s',
        // Ten seconds
        10          => 'H:i.s',
        // Thirty seconds
        30          => 'H:i.s',
        // Minute
        60          => 'H:i',
        // Ten minutes
        600         => 'H:i',
        // Half an hour
        1800        => 'H:i',
        // Hour
        3600        => 'H:i',
        // Four hours
        14400       => 'H:i',
        // Six hours
        21600       => 'H:i',
        // Half a day
        43200       => 'd.m a',
        // Day
        86400       => 'd.m',
        // Week
        604800      => 'W',
        // Month
        self::MONTH => 'M y',
        // Year
        self::YEAR  => 'Y',
        // Decade
        self::DECADE => 'Y',
    );

    /**
     * Constant used for calculation of automatic definition of major scaling 
     * steps
     */
    const MAJOR_COUNT = 10;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['startDate'] = false;
        $this->properties['endDate'] = false;
        $this->properties['interval'] = false;
        $this->properties['dateFormat'] = false;

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
            case 'startDate':
                $this->properties['startDate'] = (int) $propertyValue;
                break;
            case 'endDate':
                $this->properties['endDate'] = (int) $propertyValue;
                break;
            case 'interval':
                $this->properties['interval'] = (int) $propertyValue;
                $this->properties['initialized'] = true;
                break;
            case 'dateFormat':
                $this->properties['dateFormat'] = (string) $propertyValue;
                break;
            default:
                parent::__set( $propertyName, $propertyValue );
                break;
        }
    }

    /**
     * Ensure proper timestamp
     *
     * Takes a mixed value from datasets, like timestamps, or strings 
     * describing some time and converts it to a timestamp.
     * 
     * @param mixed $value 
     * @return int
     */
    protected static function ensureTimestamp( $value )
    {
        if ( is_numeric( $value ) )
        {
            $timestamp = (int) $value;
        }
        elseif ( ( $timestamp = strtotime( $value ) ) === false )
        {
            throw new ezcGraphErrorParsingDateException( $value );
        }

        return $timestamp;
    }

    /**
     * Add data for this axis
     * 
     * @param array $values Value which will be displayed on this axis
     * @return void
     */
    public function addData( array $values )
    {
        foreach ( $values as $nr => $value )
        {
            $value = self::ensureTimestamp( $value );

            if ( $this->minValue === false ||
                 $value < $this->minValue )
            {
                $this->minValue = $value;
            }

            if ( $this->maxValue === false ||
                 $value > $this->maxValue )
            {
                $this->maxValue = $value;
            }
        }

        $this->properties['initialized'] = true;
    }

    /**
     * Calculate nice time interval
     *
     * Use the best fitting time interval defined in class property array
     * predefinedIntervals.
     * 
     * @param int $min Start time
     * @param int $max End time
     * @return void
     */
    protected function calculateInterval( $min, $max )
    {
        $diff = $max - $min;

        foreach ( $this->predefinedIntervals as $interval => $format )
        {
            if ( ( $diff / $interval ) <= self::MAJOR_COUNT )
            {
                break;
            }
        }

        if ( ( $this->properties['startDate'] !== false ) &&
             ( $this->properties['endDate'] !== false ) )
        {
            // Use interval between defined borders
            if ( ( $diff % $interval ) > 0 )
            {
                // Stil use predefined date format from old interval if not set
                if ( $this->properties['dateFormat'] === false )
                {
                    $this->properties['dateFormat'] = $this->predefinedIntervals[$interval];
                }

                $count = ceil( $diff / $interval );
                $interval = round( $diff / $count, 0 );
            }
        }

        $this->properties['interval'] = $interval;
    }

    /**
     * Calculate lower nice date
     *
     * Calculates a date which is earlier or equal to the given date, and is
     * divisible by the given interval.
     * 
     * @param int $min Date
     * @param int $interval Interval 
     * @return int Earlier date
     */
    protected function calculateLowerNiceDate( $min, $interval )
    {
        switch ( $interval )
        {
            case self::MONTH:
                // Special handling for months - not covered by the default 
                // algorithm 
                return mktime(
                    1,
                    0,
                    0,
                    (int) date( 'm', $min ),
                    1,
                    (int) date( 'Y', $min )
                );
            default:
                $dateSteps = array( 60, 60, 24, 7, 52 );

                $date = array(
                    (int) date( 's', $min ),
                    (int) date( 'i', $min ),
                    (int) date( 'H', $min ),
                    (int) date( 'd', $min ),
                    (int) date( 'm', $min ),
                    (int) date( 'Y', $min ),
                );

                $element = 0;
                while ( ( $step = array_shift( $dateSteps ) ) &&
                        ( $interval > $step ) )
                {
                    $interval /= $step;
                    $date[$element++] = (int) ( $element > 2 );
                }

                $date[$element] -= $date[$element] % $interval;

                return mktime(
                    $date[2],
                    $date[1],
                    $date[0],
                    $date[4],
                    $date[3],
                    $date[5]
                );
        }
    }

    /**
     * Calculate start date
     *
     * Use calculateLowerNiceDate to get a date earlier or equal date then the 
     * minimum date to use it as the start date for the axis depending on the
     * selected interval.
     * 
     * @param mixed $min Minimum date
     * @param mixed $max Maximum date
     * @return void
     */
    public function calculateMinimum( $min, $max )
    {
        if ( $this->properties['endDate'] === false )
        {
            $this->properties['startDate'] = $this->calculateLowerNiceDate( $min, $this->interval );
        }
        else
        {
            $this->properties['startDate'] = $this->properties['endDate'];

            while ( $this->properties['startDate'] > $min )
            {
                switch ( $this->interval )
                {
                    case self::MONTH:
                        $this->properties['startDate'] = strtotime( '-1 month', $this->properties['startDate'] );
                        break;
                    case self::YEAR:
                        $this->properties['startDate'] = strtotime( '-1 year', $this->properties['startDate'] );
                        break;
                    case self::DECADE:
                        $this->properties['startDate'] = strtotime( '-10 years', $this->properties['startDate'] );
                        break;
                    default:
                        $this->properties['startDate'] -= $this->interval;
                }
            }
        }
    }

    /**
     * Calculate end date
     *
     * Use calculateLowerNiceDate to get a date later or equal date then the 
     * maximum date to use it as the end date for the axis depending on the
     * selected interval.
     * 
     * @param mixed $min Minimum date
     * @param mixed $max Maximum date
     * @return void
     */
    public function calculateMaximum( $min, $max )
    {
        $this->properties['endDate'] = $this->properties['startDate'];

        while ( $this->properties['endDate'] < $max )
        {
            switch ( $this->interval )
            {
                case self::MONTH:
                    $this->properties['endDate'] = strtotime( '+1 month', $this->properties['endDate'] );
                    break;
                case self::YEAR:
                    $this->properties['endDate'] = strtotime( '+1 year', $this->properties['endDate'] );
                    break;
                case self::DECADE:
                    $this->properties['endDate'] = strtotime( '+10 years', $this->properties['endDate'] );
                    break;
                default:
                    $this->properties['endDate'] += $this->interval;
            }
        }
    }

    /**
     * Calculate axis bounding values on base of the assigned values 
     * 
     * @return void
     */
    public function calculateAxisBoundings()
    {
        // Prevent division by zero, when min == max
        if ( $this->minValue == $this->maxValue )
        {
            if ( $this->minValue == 0 )
            {
                $this->maxValue = 1;
            }
            else
            {
                $this->minValue -= ( $this->minValue * .1 );
                $this->maxValue += ( $this->maxValue * .1 );
            }
        }

        // Use custom minimum and maximum if available
        if ( $this->properties['startDate'] !== false )
        {
            $this->minValue = $this->properties['startDate'];
        }

        if ( $this->properties['endDate'] !== false )
        {
            $this->maxValue = $this->properties['endDate'];
        }

        // Calculate "nice" values for scaling parameters
        if ( $this->properties['interval'] === false )
        {
            $this->calculateInterval( $this->minValue, $this->maxValue );
        }

        if ( $this->properties['dateFormat'] === false && isset( $this->predefinedIntervals[$this->interval] ) )
        {
            $this->properties['dateFormat'] = $this->predefinedIntervals[$this->interval];
        }

        if ( $this->properties['startDate'] === false )
        {
            $this->calculateMinimum( $this->minValue, $this->maxValue );
        }

        if ( $this->properties['endDate'] === false )
        {
            $this->calculateMaximum( $this->minValue, $this->maxValue );
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
        $intValue = ( $value === false ? false : self::ensureTimestamp( $value ) );

        if ( ( $value === false ) &&
             ( ( $intValue < $this->startDate ) || ( $intValue > $this->endDate ) ) )
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
            switch ( $this->position )
            {
                case ezcGraph::LEFT:
                case ezcGraph::TOP:
                    return ( $intValue - $this->startDate ) / ( $this->endDate - $this->startDate );
                case ezcGraph::RIGHT:
                case ezcGraph::BOTTOM:
                    return 1 - ( $intValue - $this->startDate ) / ( $this->endDate - $this->startDate );
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
        return false;
    }

    /**
     * Return count of major steps
     * 
     * @return integer Count of major steps
     */
    public function getMajorStepCount()
    {
        return (int) ceil( ( $this->properties['endDate'] - $this->startDate ) / $this->interval );
    }

    /**
     * Get label for a dedicated step on the axis
     * 
     * @param integer $step Number of step
     * @return string label
     */
    public function getLabel( $step )
    {
        return $this->getLabelFromTimestamp( $this->startDate + ( $step * $this->interval ), $step );
    }

    /**
     * Get label for timestamp
     * 
     * @param int $time
     * @param int $step
     * @return string
     */
    protected function getLabelFromTimestamp( $time, $step )
    {
        if ( $this->properties['labelCallback'] !== null )
        {
            return call_user_func_array(
                $this->properties['labelCallback'],
                array(
                    date( $this->properties['dateFormat'], $time ),
                    $step,
                )
            );
        }
        else
        {
            return date( $this->properties['dateFormat'], $time );
        }
    }

    /**
     * Return array of steps on this axis
     * 
     * @return array( ezcGraphAxisStep )
     */
    public function getSteps()
    {
        $steps = array();

        $start = $this->properties['startDate'];
        $end = $this->properties['endDate'];
        $distance = $end - $start;

        $step = 0;
        for ( $time = $start; $time <= $end; )
        {
            $steps[] = new ezcGraphAxisStep(
                ( $time - $start ) / $distance,
                $this->interval / $distance,
                $this->getLabelFromTimestamp( $time, $step++ ),
                array(),
                $step === 1,
                $time >= $end
            );

            switch ( $this->interval )
            {
                case self::MONTH:
                    $time = strtotime( '+1 month', $time );
                    break;
                case self::YEAR:
                    $time = strtotime( '+1 year', $time );
                    break;
                case self::DECADE:
                    $time = strtotime( '+10 years', $time );
                    break;
                default:
                    $time += $this->interval;
                    break;
            }
        }

        return $steps;
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
