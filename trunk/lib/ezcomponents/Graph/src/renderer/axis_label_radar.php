<?php
/**
 * File containing the ezcGraphAxisRadarLabelRenderer class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Renders axis labels and grid optimized for radar charts. May cause
 * unexpected results when used with other chart types.
 *
 * <code>
 *   $chart->xAxis->axisLabelRenderer = new ezcGraphAxisRadarLabelRenderer();
 * </code>
 *
 * @property float $lastStep
 *           Position of last step on the axis to calculate the grid.
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphAxisRadarLabelRenderer extends ezcGraphAxisLabelRenderer
{
    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['lastStep'] = null;

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
            case 'lastStep':
                if ( !is_null( $propertyValue ) &&
                     ( !is_float( $propertyValue ) ||
                       ( $propertyValue < 0 ) ||
                       ( $propertyValue > 1 ) ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties['lastStep'] = $propertyValue;
                break;
            default:
                return parent::__set( $propertyName, $propertyValue );
        }
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
    public function renderLabels(
        ezcGraphRenderer $renderer,
        ezcGraphBoundings $boundings,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $end,
        ezcGraphChartElementAxis $axis )
    {
        // receive rendering parameters from axis
        $steps = $axis->getSteps();

        $axisBoundings = new ezcGraphBoundings(
            $start->x, $start->y,
            $end->x, $end->y
        );

        // Determine normalized axis direction
        $direction = new ezcGraphVector(
            $start->x - $end->x,
            $start->y - $end->y
        );
        $direction->unify();

        // Draw steps and grid
        foreach ( $steps as $nr => $step )
        {
            $position = new ezcGraphCoordinate(
                $start->x + ( $end->x - $start->x ) * $step->position,
                $start->y + ( $end->y - $start->y ) * $step->position
            );
            $stepSize = new ezcGraphCoordinate(
                $axisBoundings->width * $step->width,
                $axisBoundings->height * $step->width
            );

            // Draw major grid
            if ( ( $this->lastStep !== null ) && $axis->majorGrid )
            {
                $this->drawGrid( 
                    $renderer, 
                    $boundings, 
                    $position,
                    $stepSize,
                    $axis->majorGrid,
                    $step->position
                );
            }
            
            // major step
            $this->drawStep( 
                $renderer, 
                $position,
                $direction, 
                $axis->position, 
                $this->majorStepSize, 
                $axis->border
            );

            // draw label
            if ( $this->showLabels && ( $this->lastStep === null ) )
            {
                // Calculate label boundings
                if ( abs( $direction->x ) > abs( $direction->y ) )
                {
                    // Horizontal labels
                    switch ( true )
                    {
                        case ( $nr === 0 ):
                            // First label
                            $labelSize = min(
                                $renderer->xAxisSpace * 2,
                                $step->width * $axisBoundings->width
                            );
                            break;
                        case ( $step->isLast ):
                            // Last label
                            $labelSize = min(
                                $renderer->xAxisSpace * 2,
                                $steps[$nr - 1]->width * $axisBoundings->width
                            );
                            break;
                        default:
                            $labelSize = min(
                                $step->width * $axisBoundings->width,
                                $steps[$nr - 1]->width * $axisBoundings->width
                            );
                            break;
                    }

                    $labelBoundings = new ezcGraphBoundings(
                        $position->x - $labelSize / 2 + $this->labelPadding,
                        $position->y + $this->labelPadding,
                        $position->x + $labelSize / 2 - $this->labelPadding,
                        $position->y + $renderer->yAxisSpace - $this->labelPadding
                    );

                    $alignement = ezcGraph::CENTER | ezcGraph::TOP;
                }
                else
                {
                    // Vertical labels
                    switch ( true )
                    {
                        case ( $nr === 0 ):
                            // First label
                            $labelSize = min(
                                $renderer->yAxisSpace * 2,
                                $step->width * $axisBoundings->height
                            );
                            break;
                        case ( $step->isLast ):
                            // Last label
                            $labelSize = min(
                                $renderer->yAxisSpace * 2,
                                $steps[$nr - 1]->width * $axisBoundings->height
                            );
                            break;
                        default:
                            $labelSize = min(
                                $step->width * $axisBoundings->height,
                                $steps[$nr - 1]->width * $axisBoundings->height
                            );
                            break;
                    }

                    $labelBoundings = new ezcGraphBoundings(
                        $position->x - $renderer->xAxisSpace + $this->labelPadding,
                        $position->y - $labelSize / 2 + $this->labelPadding,
                        $position->x - $this->labelPadding,
                        $position->y + $labelSize / 2 - $this->labelPadding
                    );

                    $alignement = ezcGraph::MIDDLE | ezcGraph::RIGHT;
                }

                $renderer->drawText( $labelBoundings, $step->label, $alignement );
            }

            // Iterate over minor steps
            if ( !$step->isLast )
            {
                foreach ( $step->childs as $minorStep )
                {
                    $minorStepPosition = new ezcGraphCoordinate(
                        $start->x + ( $end->x - $start->x ) * $minorStep->position,
                        $start->y + ( $end->y - $start->y ) * $minorStep->position
                    );
                    $minorStepSize = new ezcGraphCoordinate(
                        $axisBoundings->width * $minorStep->width,
                        $axisBoundings->height * $minorStep->width
                    );

                    if ( ( $this->lastStep !== null ) && $axis->minorGrid )
                    {
                        $this->drawGrid( 
                            $renderer, 
                            $boundings, 
                            $minorStepPosition,
                            $minorStepSize,
                            $axis->minorGrid,
                            $minorStep->position
                        );
                    }
                    
                    // major step
                    $this->drawStep( 
                        $renderer, 
                        $minorStepPosition,
                        $direction, 
                        $axis->position, 
                        $this->minorStepSize, 
                        $axis->border
                    );
                }
            }
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
     * @param int $stepPosition
     * @return void
     */
    protected function drawGrid( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings, ezcGraphCoordinate $position, ezcGraphCoordinate $direction, ezcGraphColor $color, $stepPosition = null )
    {
        // Calculate position on last axis
        $start = new ezcGraphCoordinate(
            $boundings->x0 + $width = ( $boundings->width / 2 ),
            $boundings->y0 + $height = ( $boundings->height / 2 )
        );

        $lastAngle = $this->lastStep * 2 * M_PI;
        $end = new ezcGraphCoordinate(
            $start->x + sin( $lastAngle ) * $width,
            $start->y - cos( $lastAngle ) * $height
        );

        $direction = new ezcGraphVector(
            $end->x - $start->x,
            $end->y - $start->y
        );
        $direction->unify();

        // Convert elipse to circle for correct angle calculation
        $direction->y *= ( $renderer->xAxisSpace / $renderer->yAxisSpace );
        $angle = $direction->angle( new ezcGraphVector( 0, 1 ) );

        $movement = new ezcGraphVector(
            sin( $angle ) * $renderer->xAxisSpace 
                * ( $direction->x < 0 ? -1 : 1 ),
            cos( $angle ) * $renderer->yAxisSpace
        );

        $start->x += $movement->x;
        $start->y += $movement->y;
        $end->x -= $movement->x;
        $end->y -= $movement->y;

        $lastPosition = new ezcGraphCoordinate(
            $start->x + ( $end->x - $start->x ) * $stepPosition,
            $start->y + ( $end->y - $start->y ) * $stepPosition
        );

        $renderer->drawGridLine(
            $position,
            $lastPosition,
            $color
        );
    }
}
?>
