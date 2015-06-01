<?php
/**
 * File containing the ezcGraphRenderer2dOptions class
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
 * Class containing the extended options available in 2d renderer.
 * 
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->palette = new ezcGraphPaletteBlack();
 *   $graph->title = 'Access statistics';
 *   $graph->options->label = '%2$d (%3$.1f%%)';
 *   
 *   $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *       'Mozilla' => 19113,
 *       'Explorer' => 10917,
 *       'Opera' => 1464,
 *       'Safari' => 652,
 *       'Konqueror' => 474,
 *   ) );
 *   $graph->data['Access statistics']->highlight['Explorer'] = true;
 *   
 *   // $graph->renderer = new ezcGraphRenderer2d();
 *   
 *   $graph->renderer->options->moveOut = .2;
 *   
 *   $graph->renderer->options->pieChartOffset = 63;
 *   
 *   $graph->renderer->options->pieChartGleam = .3;
 *   $graph->renderer->options->pieChartGleamColor = '#FFFFFF';
 *   $graph->renderer->options->pieChartGleamBorder = 2;
 *   
 *   $graph->renderer->options->pieChartShadowSize = 3;
 *   $graph->renderer->options->pieChartShadowColor = '#000000';
 *   
 *   $graph->renderer->options->legendSymbolGleam = .5;
 *   $graph->renderer->options->legendSymbolGleamSize = .9;
 *   $graph->renderer->options->legendSymbolGleamColor = '#FFFFFF';
 *   
 *   $graph->renderer->options->pieChartSymbolColor = '#BABDB688';
 *   
 *   $graph->render( 400, 150, 'tutorial_pie_chart_pimped.svg' );
 * </code>
 *
 * @property int $pieChartShadowSize
 *           Size of shadows.
 * @property float $pieChartShadowTransparency
 *           Used transparency for pie chart shadows.
 * @property float $pieChartShadowColor
 *           Color used for pie chart shadows.
 * 
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphRenderer2dOptions extends ezcGraphRendererOptions
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
        $this->properties['pieChartShadowSize'] = 0;
        $this->properties['pieChartShadowTransparency'] = .3;
        $this->properties['pieChartShadowColor'] = ezcGraphColor::fromHex( '#000000' );

        parent::__construct( $options );
    }

    /**
     * Set an option value
     * 
     * @param string $propertyName 
     * @param mixed $propertyValue 
     * @throws ezcBasePropertyNotFoundException
     *          If a property is not defined in this class
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'pieChartShadowSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float >= 0' );
                }

                $this->properties['pieChartShadowSize'] = (int) $propertyValue;
                break;
            case 'pieChartShadowTransparency':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties['pieChartShadowTransparency'] = (float) $propertyValue;
                break;
            case 'pieChartShadowColor':
                $this->properties['pieChartShadowColor'] = ezcGraphColor::create( $propertyValue );
                break;
            default:
                return parent::__set( $propertyName, $propertyValue );
        }
    }
}

?>
