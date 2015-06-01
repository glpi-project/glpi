<?php
/**
 * File containing the abstract ezcGraph class
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
 * Base options class for all eZ components.
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraph
{
    /**
     * No symbol, will fallback to a rect in the legend
     */
    const NO_SYMBOL = 0;
    /**
     * Rhomb like looking symbol
     */
    const DIAMOND = 1;
    /**
     * Filled circle
     */
    const BULLET = 2;
    /**
     * Non filled circle
     */
    const CIRCLE = 3;
    /**
     * Arrow head symbol, used for axis end markers, not available as a dataset
     * symbol.
     */
    const ARROW = 4;
    /**
     * A square, filled box, symbol
     */
    const SQUARE = 5;
    /**
     * A non-filled box symbol
     */
    const BOX = 6;

    /**
     * Constant used for background repetition. No repeat.
     */
    const NO_REPEAT = 0;
    /**
     * Constant used for background repetition. Repeat along the x axis. May be
     * used as a bitmask together with ezcGraph::VERTICAL.
     */
    const HORIZONTAL = 1;
    /**
     * Constant used for background repetition. Repeat along the y axis. May be
     * used as a bitmask together with ezcGraph::HORIZONTAL.
     */
    const VERTICAL = 2;

    /**
     * Constant used for positioning of elements. May be used as a bitmask 
     * together with other postioning constants.
     * Element will be placed at the top of the current boundings.
     */
    const TOP = 1;
    /**
     * Constant used for positioning of elements. May be used as a bitmask 
     * together with other postioning constants.
     * Element will be placed at the bottom of the current boundings.
     */
    const BOTTOM = 2;
    /**
     * Constant used for positioning of elements. May be used as a bitmask 
     * together with other postioning constants.
     * Element will be placed at the left of the current boundings.
     */
    const LEFT = 4;
    /**
     * Constant used for positioning of elements. May be used as a bitmask 
     * together with other postioning constants.
     * Element will be placed at the right of the current boundings.
     */
    const RIGHT = 8;
    /**
     * Constant used for positioning of elements. May be used as a bitmask 
     * together with other postioning constants.
     * Element will be placed at the horizontalcenter of the current boundings.
     */
    const CENTER = 16;
    /**
     * Constant used for positioning of elements. May be used as a bitmask 
     * together with other postioning constants.
     * Element will be placed at the vertical middle of the current boundings.
     */
    const MIDDLE = 32;

    /**
     * Display type for datasets. Pie may only be used with pie charts. 
     */
    const PIE = 1;
    /**
     * Display type for datasets. Bar and line charts may contain datasets of
     * type ezcGraph::LINE.
     */
    const LINE = 2;
    /**
     * Display type for datasets. Bar and line charts may contain datasets of
     * type ezcGraph::BAR.
     */
    const BAR = 3;
    /**
     * @TODO:
     */
    const ODOMETER = 4;

    /**
     * Font type definition. Used for True Type fonts.
     */
    const TTF_FONT = 1;
    /**
     * Font type definition. Used for Postscript Type 1 fonts.
     */
    const PS_FONT = 2;
    /**
     * Font type definition. Used for Palm Format Fonts for Ming driver.
     */
    const PALM_FONT = 3;
    /**
     * Font type definition. Used for SVG fonts vonverted by ttf2svg used in
     * the SVG driver.
     */
    const SVG_FONT = 4;

    /**
     * Identifier for keys in complex dataset arrays
     */
    const KEY = 0;
    /**
     * Identifier for values in complex dataset arrays
     */
    const VALUE = 1;
}

?>
