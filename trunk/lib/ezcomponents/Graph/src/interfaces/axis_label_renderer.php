<?php
/**
 * File containing the abstract ezcGraphAxisLabelRenderer class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Abstract class to render labels and grids on axis. Will be extended to
 * make it possible using different algorithms for rendering axis labels.
 *
 * Implements basic methods to render the grid and steps on a axis.
 *
 * @property bool $majorStepCount
 *           Count of major steps.
 * @property bool $minorStepCount
 *           Count of minor steps.
 * @property int $majorStepSize
 *           Size of major steps.
 * @property int $minorStepSize
 *           Size of minor steps.
 * @property bool $innerStep
 *           Indicates if steps are shown on the inner side of axis.
 * @property bool $outerStep
 *           Indicates if steps are shown on the outer side of axis.
 * @property bool $outerGrid
 *           Indicates if the grid is shown on the outer side of axis.
 * @property bool $showLables
 *           Indicates if the labels should be shown
 * @property int $labelPadding
 *           Padding of labels.
 *
 * @version 1.5
 * @package Graph
 */
abstract class ezcGraphAxisLabelRenderer extends ezcBaseOptions
{
    /**
     * Driver to render axis labels
     * 
     * @var ezcGraphDriver
     */
    protected $driver;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['majorStepCount'] = false;
        $this->properties['minorStepCount'] = false;
        $this->properties['majorStepSize'] = 3;
        $this->properties['minorStepSize'] = 1;
        $this->properties['innerStep'] = true;
        $this->properties['outerStep'] = false;
        $this->properties['outerGrid'] = false;
        $this->properties['showLabels'] = true;
        $this->properties['labelPadding'] = 2;

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
            case 'driver':
                if ( $propertyValue instanceof ezcGraphDriver )
                {
                    $this->properties['driver'] = $propertyValue;
                }
                else
                {
                    throw new ezcGraphInvalidDriverException( $propertyValue );
                }
                break;
            case 'majorStepCount':
                if ( ( $propertyValue !== false ) &&
                     !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->properties['majorStepCount'] = (int) $propertyValue;
                break;
            case 'minorStepCount':
                if ( ( $propertyValue !== false ) &&
                     !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->properties['minorStepCount'] = (int) $propertyValue;
                break;
            case 'majorStepSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->properties['majorStepSize'] = (int) $propertyValue;
                break;
            case 'minorStepSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->properties['minorStepSize'] = (int) $propertyValue;
                break;
            case 'innerStep':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['innerStep'] = (bool) $propertyValue;
                break;
            case 'outerStep':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['outerStep'] = (bool) $propertyValue;
                break;
            case 'outerGrid':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['outerGrid'] = (bool) $propertyValue;
                break;
            case 'showLabels':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['showLabels'] = (bool) $propertyValue;
                break;
            case 'labelPadding':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->properties['labelPadding'] = (int) $propertyValue;
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
    }

    /**
     * Checks for the cutting point of two lines.
     *
     * The lines are given by a start position and the direction of the line, 
     * both as instances of {@link ezcGraphCoordinate}. If no cutting point
     * could be calculated, because the lines are parallel the function will
     * return false. Otherwise the factor returned can be used to calculate the
     * cutting point using the following equatation:
     *  point = $aStart + $factor * $aDir;
     *
     * We return the factor instead of the resulting point because it can be 
     * easily determined from the factor if the cutting point is in "behind"
     * the line starting point, or if the distance to the cutting point is
     * bigger then the direction vector is long ( $factor > 1 ).
     * 
     * @param ezcGraphCoordinate $aStart 
     * @param ezcGraphCoordinate $aDir 
     * @param ezcGraphCoordinate $bStart 
     * @param ezcGraphCoordinate $bDir 
     * @return mixed
     */
    public function determineLineCuttingPoint( ezcGraphCoordinate $aStart, ezcGraphCoordinate $aDir, ezcGraphCoordinate $bStart, ezcGraphCoordinate $bDir )
    {
        // Check if lines are parallel
        if ( ( ( abs( $aDir->x ) < .000001 ) && ( abs( $bDir->x ) < .000001 ) ) ||
             ( ( abs( $aDir->y ) < .000001 ) && ( abs( $bDir->y ) < .000001 ) ) || 
             ( ( abs( $aDir->x * $bDir->x * $aDir->y * $bDir->y ) > .000001 ) && 
               ( abs( ( $aDir->x / $aDir->y ) - ( $bDir->x / $bDir->y ) ) < .000001 )
             )
           )
        {
            return false;
        }

        // Use ? : to prevent division by zero
        $denominator = 
            ( abs( $aDir->y ) > .000001 ? $bDir->y / $aDir->y : .0 ) - 
            ( abs( $aDir->x ) > .000001 ? $bDir->x / $aDir->x : .0 );

        // Solve equatation
        if ( abs( $denominator ) < .000001 )
        {
            return - ( 
                ( abs( $aDir->y ) > .000001 ? $bStart->y / $aDir->y : .0 ) -
                ( abs( $aDir->y ) > .000001 ? $aStart->y / $aDir->y : .0 ) -
                ( abs( $aDir->x ) > .000001 ? $bStart->x / $aDir->x : .0 ) +
                ( abs( $aDir->x ) > .000001 ? $aStart->x / $aDir->x : .0 )
            );
        }
        else 
        {
            return - ( 
                ( abs( $aDir->y ) > .000001 ? $bStart->y / $aDir->y : .0 ) -
                ( abs( $aDir->y ) > .000001 ? $aStart->y / $aDir->y : .0 ) -
                ( abs( $aDir->x ) > .000001 ? $bStart->x / $aDir->x : .0 ) +
                ( abs( $aDir->x ) > .000001 ? $aStart->x / $aDir->x : .0 )
            ) / $denominator;
        }
    }

    /**
     * Draw single step on a axis
     *
     * Draws a step on a axis at the current position
     * 
     * @param ezcGraphRenderer $renderer Renderer to draw the step with
     * @param ezcGraphCoordinate $position Position of step
     * @param ezcGraphCoordinate $direction Direction of axis
     * @param int $axisPosition Position of axis
     * @param int $size Step size
     * @param ezcGraphColor $color Color of axis
     * @return void
     */
    public function drawStep( ezcGraphRenderer $renderer, ezcGraphCoordinate $position, ezcGraphCoordinate $direction, $axisPosition, $size, ezcGraphColor $color )
    {
        if ( ! ( $this->innerStep || $this->outerStep ) )
        {
            return false;
        }

        $drawStep = false;
        if ( ( ( $axisPosition === ezcGraph::CENTER ) && $this->innerStep ) ||
             ( ( $axisPosition === ezcGraph::BOTTOM ) && $this->outerStep ) ||
             ( ( $axisPosition === ezcGraph::TOP ) && $this->innerStep ) ||
             ( ( $axisPosition === ezcGraph::RIGHT ) && $this->outerStep ) ||
             ( ( $axisPosition === ezcGraph::LEFT ) && $this->innerStep ) )
        {
            // Turn direction vector to left by 90 degrees and multiply 
            // with major step size
            $stepStart = new ezcGraphCoordinate(
                $position->x + $direction->y * $size,
                $position->y - $direction->x * $size
            );
            $drawStep = true;
        }
        else
        {
            $stepStart = $position;
        }

        if ( ( ( $axisPosition === ezcGraph::CENTER ) && $this->innerStep ) ||
             ( ( $axisPosition === ezcGraph::BOTTOM ) && $this->innerStep ) ||
             ( ( $axisPosition === ezcGraph::TOP ) && $this->outerStep ) ||
             ( ( $axisPosition === ezcGraph::RIGHT ) && $this->innerStep ) ||
             ( ( $axisPosition === ezcGraph::LEFT ) && $this->outerStep ) )
        {
            // Turn direction vector to right by 90 degrees and multiply 
            // with major step size
            $stepEnd = new ezcGraphCoordinate(
                $position->x - $direction->y * $size,
                $position->y + $direction->x * $size
            );
            $drawStep = true;
        }
        else
        {
            $stepEnd = $position;
        }

        if ( $drawStep )
        {
            $renderer->drawStepLine(
                $stepStart,
                $stepEnd,
                $color
            );
        }
    }
    
    /**
     * Draw non-rectangular grid lines grid
     *
     * Draws a grid line at the current position, for non-rectangular axis.
     * 
     * @param ezcGraphRenderer $renderer Renderer to draw the grid with
     * @param ezcGraphBoundings $boundings Boundings of axis
     * @param ezcGraphCoordinate $position Position of step
     * @param ezcGraphCoordinate $direction Direction of axis
     * @param ezcGraphColor $color Color of axis
     * @return void
     */
    protected function drawNonRectangularGrid( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings, ezcGraphCoordinate $position, ezcGraphCoordinate $direction, ezcGraphColor $color )
    {
        // Direction of grid line is direction of axis turned right by 90 
        // degrees
        $gridDirection = new ezcGraphCoordinate(
            $direction->y,
            - $direction->x
        );

        $cuttingPoints = array();
        foreach ( array( // Bounding lines
                array(
                    'start' => new ezcGraphCoordinate( $boundings->x0, $boundings->y0 ),
                    'dir' => new ezcGraphCoordinate( 0, $boundings->y1 - $boundings->y0 )
                ),
                array(
                    'start' => new ezcGraphCoordinate( $boundings->x0, $boundings->y0 ),
                    'dir' => new ezcGraphCoordinate( $boundings->x1 - $boundings->x0, 0 )
                ),
                array(
                    'start' => new ezcGraphCoordinate( $boundings->x1, $boundings->y1 ),
                    'dir' => new ezcGraphCoordinate( 0, $boundings->y0 - $boundings->y1 )
                ),
                array(
                    'start' => new ezcGraphCoordinate( $boundings->x1, $boundings->y1 ),
                    'dir' => new ezcGraphCoordinate( $boundings->x0 - $boundings->x1, 0 )
                ),
            ) as $boundingLine )
        {
            // Test for cutting points with bounding lines, where cutting
            // position is between 0 and 1, which means, that the line is hit
            // on the bounding box rectangle. Use these points as a start and
            // ending point for the grid lines. There should *always* be
            // exactly two points returned.
            $cuttingPosition = $this->determineLineCuttingPoint(
                $boundingLine['start'],
                $boundingLine['dir'],
                $position,
                $gridDirection
            );

            if ( $cuttingPosition === false )
            {
                continue;
            }

            $cuttingPosition = abs( $cuttingPosition );

            if ( ( $cuttingPosition >= 0 ) && 
                 ( $cuttingPosition <= 1 ) )
            {
                $cuttingPoints[] = new ezcGraphCoordinate(
                    $boundingLine['start']->x + $cuttingPosition * $boundingLine['dir']->x,
                    $boundingLine['start']->y + $cuttingPosition * $boundingLine['dir']->y
                );
            }
        }

        if ( count( $cuttingPoints ) < 2 )
        {
            // This should not happpen
            return false;
        }

        // Finally draw grid line
        $renderer->drawGridLine(
            $cuttingPoints[0],
            $cuttingPoints[1],
            $color
        );
    }

    /**
     * Draw rectangular grid
     *
     * Draws a grid line at the current position for rectangular directed axis.
     *
     * Method special for rectangularly directed axis to minimize the floating
     * point calculation inaccuracies. Those are not necessary for rectangles,
     * while for non-rectangular directed axis.
     * 
     * @param ezcGraphRenderer $renderer Renderer to draw the grid with
     * @param ezcGraphBoundings $boundings Boundings of axis
     * @param ezcGraphCoordinate $position Position of step
     * @param ezcGraphCoordinate $direction Direction of axis
     * @param ezcGraphColor $color Color of axis
     * @return void
     */
    protected function drawRectangularGrid( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings, ezcGraphCoordinate $position, ezcGraphCoordinate $direction, ezcGraphColor $color )
    {
        if ( abs( $direction->x ) < .00001 )
        {
            $renderer->drawGridLine(
                new ezcGraphCoordinate(
                    $boundings->x0,
                    $position->y
                ),
                new ezcGraphCoordinate(
                    $boundings->x1,
                    $position->y
                ),
                $color
            );
        }
        else
        {
            $renderer->drawGridLine(
                new ezcGraphCoordinate(
                    $position->x,
                    $boundings->y0
                ),
                new ezcGraphCoordinate(
                    $position->x,
                    $boundings->y1
                ),
                $color
            );
        }
    }

    /**
     * Draw grid
     *
     * Draws a grid line at the current position
     * 
     * @param ezcGraphRenderer $renderer Renderer to draw the grid with
     * @param ezcGraphBoundings $boundings Boundings of axis
     * @param ezcGraphCoordinate $position Position of step
     * @param ezcGraphCoordinate $direction Direction of axis
     * @param ezcGraphColor $color Color of axis
     * @return void
     */
    protected function drawGrid( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings, ezcGraphCoordinate $position, ezcGraphCoordinate $direction, ezcGraphColor $color )
    {
        // Check if the axis direction is rectangular
        if ( ( abs( $direction->x ) < .00001 ) ||
             ( abs( $direction->y ) < .00001 ) )
        {
            return $this->drawRectangularGrid( $renderer, $boundings, $position, $direction, $color );
        }
        else
        {
            return $this->drawNonRectangularGrid( $renderer, $boundings, $position, $direction, $color );
        }
    }

    /**
     * Modify chart boundings
     *
     * Optionally modify boundings of chart data
     * 
     * @param ezcGraphBoundings $boundings Current boundings of chart
     * @param ezcGraphCoordinate $direction Direction of the current axis
     * @return ezcGraphBoundings Modified boundings
     */
    public function modifyChartBoundings( ezcGraphBoundings $boundings, ezcGraphCoordinate $direction )
    {
        return $boundings;
    }
    
    /**
     * Modify chart data position
     *
     * Optionally additionally modify the coodinate of a data point
     * 
     * @param ezcGraphCoordinate $coordinate Data point coordinate
     * @return ezcGraphCoordinate Modified coordinate
     */
    public function modifyChartDataPosition( ezcGraphCoordinate $coordinate )
    {
        return $coordinate;
    }

    /**
     * Get axis space values
     *
     * Get axis space values, depending on passed parameters. If
     * $innerBoundings is given it will be used to caclulat the axis spaces
     * available for label rendering. If not given the legacy method will be
     * used, which uses the xAxisSpace and yAxisSpace values calcualted by the
     * renderer.
     *
     * Returns an array( $xSpace, $ySpace ), containing the irespective size in
     * pixels. Additionally calculates the grid boundings passed by reference.
     * 
     * @param ezcGraphRenderer $renderer 
     * @param ezcGraphBoundings $boundings 
     * @param mixed $innerBoundings 
     * @return array
     */
    protected function getAxisSpace( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings, ezcGraphChartElementAxis $axis, $innerBoundings, &$gridBoundings )
    {
        if ( $innerBoundings !== null )
        {
            $gridBoundings = clone $innerBoundings;
            $xSpace = abs( $axis->position === ezcGraph::LEFT ? $innerBoundings->x0 - $boundings->x0 : $boundings->x1 - $innerBoundings->x1 );
            $ySpace = abs( $axis->position === ezcGraph::TOP  ? $innerBoundings->y0 - $boundings->y0 : $boundings->y1 - $innerBoundings->y1 );
        }
        else
        {
            $gridBoundings = new ezcGraphBoundings(
                $boundings->x0 + ( $xSpace = abs( $renderer->xAxisSpace ) ),
                $boundings->y0 + ( $ySpace = abs( $renderer->yAxisSpace ) ),
                $boundings->x1 - $xSpace,
                $boundings->y1 - $ySpace
            );
        }

        if ( $this->outerGrid )
        {
            $gridBoundings = $boundings;
        }

        return array( $xSpace, $ySpace );
    }

    /**
     * Render Axis labels
     *
     * Render labels for an axis.
     *
     * @param ezcGraphRenderer $renderer Renderer used to draw the chart
     * @param ezcGraphBoundings $boundings Boundings of the axis
     * @param ezcGraphCoordinate $start Axis starting point
     * @param ezcGraphCoordinate $end Axis ending point
     * @param ezcGraphChartElementAxis $axis Axis instance
     * @return void
     */
    abstract public function renderLabels(
        ezcGraphRenderer $renderer,
        ezcGraphBoundings $boundings,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $end,
        ezcGraphChartElementAxis $axis
    );
}

?>
