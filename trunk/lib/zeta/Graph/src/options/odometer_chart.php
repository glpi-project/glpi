<?php
/**
 * File containing the ezcGraphOdometerChartOptions class
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
 * Class containing the options for odometer charts
 *
 * <code>
 *  $graph = new ezcGraphOdoMeterChart();
 *  
 *  $graph->data['Test'] = new ezcGraphArrayDataSet( array( 0, 1, 23, 30 ) );
 * 
 *  $graph->options->odometerHeight = .3;
 *  $graph->options->borderColor = '#2e3436';
 *  
 *  $graph->render( 150, 50, 'odometer.svg' );
 * </code>
 *
 * @property ezcGraphColor $borderColor
 *           Color of border around odometer chart
 * @property int $borderWidth
 *           Width of border around odometer chart
 * @property ezcGraphColor $startColor
 *           Start color of grdient used as the odometer chart background.
 * @property ezcGraphColor $endColor
 *           End color of grdient used as the odometer chart background.
 * @property int $markerWidth
 *           Width of odometer markers
 * @property float $odometerHeight
 *           Height consumed by odometer chart
 * 
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphOdometerChartOptions extends ezcGraphChartOptions
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
        $this->properties['borderColor']    = ezcGraphColor::create( '#000000' );
        $this->properties['borderWidth']    = 0;

        $this->properties['startColor']     = ezcGraphColor::create( '#4e9a06A0' );
        $this->properties['endColor']       = ezcGraphColor::create( '#A40000A0' );

        $this->properties['markerWidth']    = 2;

        $this->properties['odometerHeight'] = 0.5;

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
            case 'borderWidth':
            case 'markerWidth':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) ) 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 1' );
                }

                $this->properties[$propertyName] = (int) $propertyValue;
                break;

            case 'borderColor':
            case 'startColor':
            case 'endColor':
                $this->properties[$propertyName] = ezcGraphColor::create( $propertyValue );
                break;

            case 'odometerHeight':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;

            default:
                return parent::__set( $propertyName, $propertyValue );
        }
    }
}

?>
