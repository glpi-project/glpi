<?php
/**
 * File containing the ezcGraphRadarChartOption class
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
 * Class containing the basic options for radar charts.
 *
 * <code>
 *   $wikidata = include 'tutorial_wikipedia_data.php';
 *   
 *   $graph = new ezcGraphRadarChart();
 *   $graph->title = 'Wikipedia articles';
 *
 *   $graph->options->fillLines = 220;
 *   
 *   // Add data
 *   foreach ( $wikidata as $language => $data )
 *   {
 *       $graph->data[$language] = new ezcGraphArrayDataSet( $data );
 *       $graph->data[$language][] = reset( $data );
 *   }
 *   
 *   $graph->render( 400, 150, 'tutorial_radar_chart.svg' );
 * </code>
 *
 * @property float $lineThickness
 *           Theickness of chart lines
 * @property mixed $fillLines
 *           Status wheather the space between line and axis should get filled.
 *            - FALSE to not fill the space at all.
 *            - (int) Opacity used to fill up the space with the lines color.
 * @property int $symbolSize
 *           Size of symbols in line chart.
 * @property ezcGraphFontOptions $highlightFont
 *           Font configuration for highlight tests
 * @property int $highlightSize
 *           Size of highlight blocks
 * @property bool $highlightRadars
 *           If true, it adds lines to highlight the values position on the 
 *           axis.
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphRadarChartOptions extends ezcGraphChartOptions
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
        $this->properties['lineThickness'] = 1;
        $this->properties['fillLines'] = false;
        $this->properties['symbolSize'] = 8;
        $this->properties['highlightFont'] = new ezcGraphFontOptions();
        $this->properties['highlightFontCloned'] = false;
        $this->properties['highlightSize'] = 14;
        $this->properties['highlightRadars'] = false;
    
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
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'lineThickness':
            case 'symbolSize':
            case 'highlightSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 1' );
                }

                $this->properties[$propertyName] = (int) $propertyValue;
                break;
            case 'fillLines':
                if ( ( $propertyValue !== false ) &&
                     !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 255 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'false OR 0 <= int <= 255' );
                }

                $this->properties[$propertyName] = ( 
                    $propertyValue === false
                    ? false
                    : (int) $propertyValue );
                break;
            case 'highlightFont':
                if ( $propertyValue instanceof ezcGraphFontOptions )
                {
                    $this->properties['highlightFont'] = $propertyValue;
                }
                elseif ( is_string( $propertyValue ) )
                {
                    if ( !$this->properties['highlightFontCloned'] )
                    {
                        $this->properties['highlightFont'] = clone $this->font;
                        $this->properties['highlightFontCloned'] = true;
                    }

                    $this->properties['highlightFont']->path = $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphFontOptions' );
                }
                break;
                $this->properties['highlightSize'] = max( 1, (int) $propertyValue );
                break;
            case 'highlightRadars':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['highlightRadars'] = $propertyValue;
                break;
            default:
                return parent::__set( $propertyName, $propertyValue );
        }
    }
    
    /**
     * __get 
     * 
     * @param mixed $propertyName 
     * @throws ezcBasePropertyNotFoundException
     *          If a the value for the property options is not an instance of
     * @return mixed
     * @ignore
     */
    public function __get( $propertyName )
    {
        switch ( $propertyName )
        {
            case 'highlightFont':
                // Clone font configuration when requested for this element
                if ( !$this->properties['highlightFontCloned'] )
                {
                    $this->properties['highlightFont'] = clone $this->properties['font'];
                    $this->properties['highlightFontCloned'] = true;
                }
                return $this->properties['highlightFont'];
            default:
                return parent::__get( $propertyName );
        }
    }
}

?>
