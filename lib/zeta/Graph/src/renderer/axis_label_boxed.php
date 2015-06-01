<?php
/**
 * File containing the ezcGraphAxisBoxedLabelRenderer class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Graph
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
/**
 * Renders axis labels centered between two axis steps like normally used for
 * bar charts. Used with other chart types this axis label renderer may cause
 * unexpected results. You may use this renderer by assigning it to one of the
 * charts axis.
 *
 * <code>
 *   $chart->xAxis->axisLabelRenderer = new ezcGraphAxisBoxedLabelRenderer();
 * </code>
 *
 * @version //autogentag//
 * @package Graph
 * @mainclass
 */
class ezcGraphAxisBoxedLabelRenderer extends ezcGraphAxisLabelRenderer
{
    /**
     * Store step array for later coordinate modifications
     * 
     * @var array(ezcGraphStep)
     */
    protected $steps;

    /**
     * Store direction for later coordinate modifications
     * 
     * @var ezcGraphVector
     */
    protected $direction;

    /**
     * Store coordinate width modifier for later coordinate modifications
     * 
     * @var float
     */
    protected $widthModifier;
    
    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        parent::__construct( $options );
        $this->properties['outerStep'] = true;
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
        ezcGraphChartElementAxis $axis,
        ezcGraphBoundings $innerBoundings = null )
    {
        // receive rendering parameters from axis
        $steps = $axis->getSteps();
        $this->steps = $steps;

        $axisBoundings = new ezcGraphBoundings(
            $start->x, $start->y,
            $end->x, $end->y
        );

        // Determine normalized axis direction
        $this->direction = new ezcGraphVector(
            $end->x - $start->x,
            $end->y - $start->y
        );
        $this->direction->unify();
        
        // Get axis space
        $gridBoundings = null;
        list( $xSpace, $ySpace ) = $this->getAxisSpace( $renderer, $boundings, $axis, $innerBoundings, $gridBoundings );

        // Determine additional required axis space by boxes
        $firstStep = reset( $steps );
        $lastStep = end( $steps );

        $this->widthModifier = 1 + $firstStep->width / 2 + $lastStep->width / 2;

        // Draw steps and grid
        foreach ( $steps as $nr => $step )
        {
            $position = new ezcGraphCoordinate(
                $start->x + ( $end->x - $start->x ) * ( $step->position + $step->width ) / $this->widthModifier,
                $start->y + ( $end->y - $start->y ) * ( $step->position + $step->width ) / $this->widthModifier
            );
    
            $stepWidth = $step->width / $this->widthModifier;

            $stepSize = new ezcGraphCoordinate(
                $axisBoundings->width * $stepWidth,
                $axisBoundings->height * $stepWidth
            );

            if ( $this->showLabels )
            {
                // Calculate label boundings
                switch ( true )
                {
                    case ( abs( $this->direction->x ) > abs( $this->direction->y ) ) &&
                         ( $this->direction->x > 0 ):
                        $labelBoundings = new ezcGraphBoundings(
                            $position->x - $stepSize->x + $this->labelPadding,
                            $position->y + $this->labelPadding,
                            $position->x - $this->labelPadding,
                            $position->y + $ySpace - $this->labelPadding
                        );

                        $alignement = ezcGraph::CENTER | ezcGraph::TOP;
                    break;
                    case ( abs( $this->direction->x ) > abs( $this->direction->y ) ) &&
                         ( $this->direction->x < 0 ):
                        $labelBoundings = new ezcGraphBoundings(
                            $position->x + $this->labelPadding,
                            $position->y + $this->labelPadding,
                            $position->x + $stepSize->x - $this->labelPadding,
                            $position->y + $ySpace - $this->labelPadding
                        );

                        $alignement = ezcGraph::CENTER | ezcGraph::TOP;
                    break;
                    case ( $this->direction->y > 0 ):
                        $labelBoundings = new ezcGraphBoundings(
                            $position->x - $xSpace + $this->labelPadding,
                            $position->y - $stepSize->y + $this->labelPadding,
                            $position->x - $this->labelPadding,
                            $position->y - $this->labelPadding
                        );

                        $alignement = ezcGraph::MIDDLE | ezcGraph::RIGHT;
                        break;
                    case ( $this->direction->y < 0 ):
                        $labelBoundings = new ezcGraphBoundings(
                            $position->x - $xSpace + $this->labelPadding,
                            $position->y + $this->labelPadding,
                            $position->x - $this->labelPadding,
                            $position->y + $stepSize->y - $this->labelPadding
                        );

                        $alignement = ezcGraph::MIDDLE | ezcGraph::RIGHT;
                        break;
                }

                $renderer->drawText( $labelBoundings, $step->label, $alignement );
            }

            // major grid
            if ( $axis->majorGrid )
            {
                $this->drawGrid( 
                    $renderer, 
                    $gridBoundings, 
                    $position,
                    $stepSize,
                    $axis->majorGrid
                );
            }
            
            // major step
            $this->drawStep( 
                $renderer, 
                $position,
                $this->direction, 
                $axis->position, 
                $this->majorStepSize, 
                $axis->border
            );
        }
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
        $firstStep = reset( $this->steps );
        $offset = $firstStep->width / 2 / $this->widthModifier;

        return new ezcGraphCoordinate(
            $coordinate->x * abs( $this->direction->y ) +
                ( $coordinate->x / $this->widthModifier + $offset ) * abs( $this->direction->x ),
            $coordinate->y * abs( $this->direction->x ) +
                ( $coordinate->y / $this->widthModifier + $offset ) * abs( $this->direction->y )
        );
    }
}
?>
