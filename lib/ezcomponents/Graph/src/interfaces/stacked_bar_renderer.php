<?php
/**
 * File containing the ezcGraphRadarRenderer interface
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Interface which adds the methods required for rendering radar charts to a 
 * renderer
 *
 * @version 1.5
 * @package Graph
 */
interface ezcGraphStackedBarsRenderer
{
    /**
     * Draw stacked bar
     *
     * Draws a stacked bar part as a data element in a line chart
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphColor $color Color of line
     * @param ezcGraphCoordinate $start
     * @param ezcGraphCoordinate $position
     * @param float $stepSize Space which can be used for bars
     * @param int $symbol Symbol to draw for line
     * @param float $axisPosition Position of axis for drawing filled lines
     * @return void
     */
    public function drawStackedBar(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $position,
        $stepSize,
        $symbol = ezcGraph::NO_SYMBOL,
        $axisPosition = 0.
    );
}

?>
