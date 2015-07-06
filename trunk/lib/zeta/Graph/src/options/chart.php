<?php
/**
 * File containing the ezcGraphChartOption class
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
 * Class containing the basic options for charts.
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->palette = new ezcGraphPaletteEzBlue();
 *   $graph->title = 'Access statistics';
 *   
 *   // Global font options
 *   $graph->options->font->name = 'serif';
 *   
 *   // Special font options for sub elements
 *   $graph->title->background = '#EEEEEC';
 *   $graph->title->font->name = 'sans-serif';
 *   
 *   $graph->options->font->maxFontSize = 8;
 *   
 *   $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *       'Mozilla' => 19113,
 *       'Explorer' => 10917,
 *       'Opera' => 1464,
 *       'Safari' => 652,
 *       'Konqueror' => 474,
 *   ) );
 *   
 *   $graph->render( 400, 150, 'tutorial_chart_title.svg' );
 * </code>
 *
 * @property int $width
 *           Width of the chart.
 * @property int $height
 *           Height of the chart.
 * @property ezcGraphFontOptions $font
 *           Font used in the graph.
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphChartOptions extends ezcBaseOptions
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
        $this->properties['width'] = null;
        $this->properties['height'] = null;
        $this->properties['font'] = new ezcGraphFontOptions();

        parent::__construct( $options );
    }

    /**
     * Set an option value
     * 
     * @param string $propertyName 
     * @param mixed $propertyValue 
     * @throws ezcBasePropertyNotFoundException
     *          If a property is not defined in this class
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'width':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 1' );
                }

                $this->properties['width'] = (int) $propertyValue;
                break;
            case 'height':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 1' );
                }

                $this->properties['height'] = (int) $propertyValue;
                break;
            case 'font':
                $this->properties['font']->path = $propertyValue;
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
                break;
        }
    }
}

?>
