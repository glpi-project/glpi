<?php
/**
 * File containing the ezcGraphPaletteEz class
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
 * Color pallet for ezcGraph based on eZ color scheme
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphPaletteEz extends ezcGraphPalette
{
    /**
     * Axiscolor 
     * 
     * @var ezcGraphColor
     */
    protected $axisColor = '#1E1E1E';

    /**
     * Color of grid lines
     * 
     * @var ezcGraphColor
     */
    protected $majorGridColor = '#D3D7DF';

    /**
     * Array with colors for datasets
     * 
     * @var array
     */
    protected $dataSetColor = array(
        '#C60C30',
        '#C90062',
        '#E05206',
        '#F0AB00',
        '#D4BA00',
        '#9C9A00',
        '#3C8A2E',
        '#006983',
        '#0098C3',
        '#21578A',
        '#55517B',
        '#4E7D5B',
    );

    /**
     * Array with symbols for datasets 
     * 
     * @var array
     */
    protected $dataSetSymbol = array(
        ezcGraph::BULLET,
    );

    /**
     * Name of font to use
     * 
     * @var string
     */
    protected $fontName = 'sans-serif';

    /**
     * Fontcolor 
     * 
     * @var ezcGraphColor
     */
    protected $fontColor = '#1E1E1E';

    /**
     * Backgroundcolor for chart
     * 
     * @var ezcGraphColor
     */
    protected $chartBackground = '#FFFFFFFF';

    /**
     * Padding in elements
     * 
     * @var integer
     */
    protected $padding = 1;

    /**
     * Margin of elements
     * 
     * @var integer
     */
    protected $margin = 0;
}

?>
