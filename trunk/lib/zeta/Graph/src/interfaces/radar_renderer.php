<?php
/**
 * File containing the ezcGraphRadarRenderer interface
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
 * Interface which adds the methods required for rendering radar charts to a 
 * renderer
 *
 * @version //autogentag//
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
