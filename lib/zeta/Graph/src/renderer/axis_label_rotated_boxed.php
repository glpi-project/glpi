<?php
/**
 * File containing the ezcGraphAxisRotatedLabelRenderer class
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
 * Can render axis labels rotated, so that more axis labels fit on one axis.
 * Produces best results if the axis space was increased, so that more spcae is
 * available below the axis.
 *
 * <code>
 *   $chart->xAxis->axisLabelRenderer = new ezcGraphAxisRotatedLabelRenderer();
 *
 *   // Define angle manually in degree
 *   $chart->xAxis->axisLabelRenderer->angle = 45;
 *
 *   // Increase axis space
 *   $chart->xAxis->axisSpace = .2;
 * </code>
 *
 * @property float $angle
 *           Angle of labels on axis in degrees.
 *
 * @version //autogentag//
 * @package Graph
 * @mainclass
 */
class ezcGraphAxisRotatedBoxedLabelRenderer extends ezcGraphAxisRotatedLabelRenderer
{
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
        $this->steps = $steps = $axis->getSteps();

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

        // Determine optimal angle if none specified
        $this->determineAngle( $steps, $xSpace, $ySpace, $axisBoundings );
        $degTextAngle = $this->determineTextOffset( $axis, $steps );
        $labelLength  = $this->calculateLabelLength( $start, $end, $xSpace, $ySpace, $axisBoundings );

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

            // Calculate label boundings
            $labelSize = $this->calculateLabelSize( $steps, $nr, $step, $xSpace, $ySpace, $axisBoundings );
            $lengthReducement = min(
                abs( tan( deg2rad( $this->angle ) ) * ( $labelSize / 2 ) ),
                abs( $labelLength / 2 )
            );

            $this->renderLabelText( $renderer, $axis, $position, $step->label, $degTextAngle, $labelLength, $labelSize, $lengthReducement );

            // Major grid
            if ( $axis->majorGrid )
            {
                $this->drawGrid( $renderer, $gridBoundings, $position, $stepSize, $axis->majorGrid );
            }
            
            // Major step
            $this->drawStep( $renderer, $position, $this->direction, $axis->position, $this->majorStepSize, $axis->border );
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
            $coordinate->x * abs( $this->direction->y ) + (
                $coordinate->x * ( 1 / $this->widthModifier ) * ( 1 - abs( $this->offset ) ) +
                abs( $this->offset ) +
                $offset
            ) * abs( $this->direction->x ),
            $coordinate->y * abs( $this->direction->x ) + (
                $coordinate->y * ( 1 / $this->widthModifier ) * ( 1 - abs( $this->offset ) ) +
                abs( $this->offset ) +
                $offset
            ) * abs( $this->direction->y )
        );
    }
}
?>
