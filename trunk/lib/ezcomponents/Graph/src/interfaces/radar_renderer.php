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
interface ezcGraphRadarRenderer
{
    /**
     * Draw radar chart data line
     *
     * Draws a line as a data element in a radar chart
     * 
     * @param ezcGraphBoundings $boundings Chart boundings
     * @param ezcGraphContext $context Context of call
     * @param ezcGraphColor $color Color of line
     * @param ezcGraphCoordinate $center Center of radar chart
     * @param ezcGraphCoordinate $start Starting point
     * @param ezcGraphCoordinate $end Ending point
     * @param int $dataNumber Number of dataset
     * @param int $dataCount Count of datasets in chart
     * @param int $symbol Symbol to draw for line
     * @param ezcGraphColor $symbolColor Color of the symbol, defaults to linecolor
     * @param ezcGraphColor $fillColor Color to fill line with
     * @param float $thickness Line thickness
     * @return void
     */
    public function drawRadarDataLine(
        ezcGraphBoundings $boundings,
        ezcGraphContext $context,
        ezcGraphColor $color,
        ezcGraphCoordinate $center,
        ezcGraphCoordinate $start,
        ezcGraphCoordinate $end,
        $dataNumber = 1,
        $dataCount = 1,
        $symbol = ezcGraph::NO_SYMBOL,
        ezcGraphColor $symbolColor = null,
        ezcGraphColor $fillColor = null,
        $thickness = 1.
    );
}

?>
