<?php
/**
 * File containing the ezcGraphAxisCenteredLabelRenderer class
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
 * Renders axis labels centered below the axis steps.
 *
 * <code>
 *   $chart->xAxis->axisLabelRenderer = new ezcGraphAxisCenteredLabelRenderer();
 * </code>
 *
 * @property bool $showZeroValue
 *           Show the value at the zero point of an axis. This value might be 
 *           crossed by the other axis which would result in an unreadable 
 *           label.
 *
 * @version //autogentag//
 * @package Graph
 * @mainclass
 */
class ezcGraphAxisCenteredLabelRenderer extends ezcGraphAxisLabelRenderer
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
        $this->properties['showZeroValue'] = false;

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
            case 'showZeroValue':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['showZeroValue'] = (bool) $propertyValue;
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
        ezcGraphChartElementAxis $axis,
        ezcGraphBoundings $innerBoundings = null )
    {
        // receive rendering parameters from axis
        $steps = $axis->getSteps();

        $axisBoundings = new ezcGraphBoundings(
            $start->x, $start->y,
            $end->x, $end->y
        );

        // Determine normalized axis direction
        $direction = new ezcGraphVector(
            $end->x - $start->x,
            $end->y - $start->y
        );
        $direction->unify();

        // Get axis space
        $gridBoundings = null;
        list( $xSpace, $ySpace ) = $this->getAxisSpace( $renderer, $boundings, $axis, $innerBoundings, $gridBoundings );

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

            if ( ! $step->isZero )
            {
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
                    $direction, 
                    $axis->position, 
                    $this->majorStepSize, 
                    $axis->border
                );
            }

            // draw label
            if ( $this->showLabels && ( $this->showZeroValue || ! $step->isZero ) )
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
                                $xSpace * 2,
                                $step->width * $axisBoundings->width
                            );
                            break;
                        case ( $step->isLast ):
                            // Last label
                            $labelSize = min(
                                $xSpace * 2,
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
                        $position->y + $ySpace - $this->labelPadding
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
                                $ySpace * 2,
                                $step->width * $axisBoundings->height
                            );
                            break;
                        case ( $step->isLast ):
                            // Last label
                            $labelSize = min(
                                $ySpace * 2,
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
                        $position->x - $xSpace + $this->labelPadding,
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

                    if ( $axis->minorGrid )
                    {
                        $this->drawGrid( 
                            $renderer, 
                            $gridBoundings, 
                            $minorStepPosition,
                            $minorStepSize,
                            $axis->minorGrid
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
}
?>
