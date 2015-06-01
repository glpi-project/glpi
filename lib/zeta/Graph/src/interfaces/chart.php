<?php
/**
 * File containing the abstract ezcGraphChart class
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
 * Class to represent a complete chart.
 *
 * @property ezcGraphRenderer $renderer
 *           Renderer used to render chart
 * @property ezcGraphDriver $driver
 *           Output driver used for chart
 * @property ezcGraphPalette $palette
 *           Palette used for colorization of chart
 * @property-read mixed $renderedFile
 *           Contains the filename of the rendered file, if rendered.
 *
 * @package Graph
 * @version //autogentag//
 */
abstract class ezcGraphChart
{

    /**
     * Contains all general chart options
     * 
     * @var ezcGraphChartConfig
     */
    protected $options;

    /**
     * Contains subelelemnts of the chart like legend and axes
     * 
     * @var array(ezcGraphChartElement)
     */
    protected $elements = array();

    /**
     * Contains the data of the chart
     * 
     * @var ezcGraphChartDataContainer
     */
    protected $data;

    /**
     * Array containing chart properties
     * 
     * @var array
     */
    protected $properties;

    /**
     * Contains the status wheather an element should be rendered
     * 
     * @var array
     */
    protected $renderElement;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->palette = new ezcGraphPaletteTango();
        $this->data = new ezcGraphChartDataContainer( $this );

        // Add standard elements
        $this->addElement( 'background', new ezcGraphChartElementBackground() );
        $this->elements['background']->position = ezcGraph::CENTER | ezcGraph::MIDDLE;

        $this->addElement( 'title', new ezcGraphChartElementText() );
        $this->elements['title']->position = ezcGraph::TOP;
        $this->renderElement['title'] = false;

        $this->addElement( 'subtitle', new ezcGraphChartElementText() );
        $this->elements['subtitle']->position = ezcGraph::TOP;
        $this->renderElement['subtitle'] = false;

        $this->addElement( 'legend', new ezcGraphChartElementLegend() );
        $this->elements['legend']->position = ezcGraph::LEFT;

        // Define standard renderer and driver
        $this->properties['driver'] = new ezcGraphSvgDriver();
        $this->properties['renderer'] = new ezcGraphRenderer2d();
        $this->properties['renderer']->setDriver( $this->driver );

        // Initialize other properties
        $this->properties['renderedFile'] = null;
    }

    /**
     * Add element to chart
     *
     * Add a chart element to the chart and perform the required configuration
     * tasks for the chart element.
     * 
     * @param string $name Element name
     * @param ezcGraphChartElement $element Chart element
     * @return void
     */
    protected function addElement( $name, ezcGraphChartElement $element )
    {
        $this->elements[$name] = $element;
        $this->elements[$name]->font = $this->options->font;
        $this->elements[$name]->setFromPalette( $this->palette );

        // Render element by default
        $this->renderElement[$name] = true;
    }

    /**
     * Options write access
     * 
     * @throws ezcBasePropertyNotFoundException
     *          If Option could not be found
     * @throws ezcBaseValueException
     *          If value is out of range
     * @param mixed $propertyName   Option name
     * @param mixed $propertyValue  Option value;
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue ) 
    {
        switch ( $propertyName ) {
            case 'title':
            case 'subtitle':
                $this->elements[$propertyName]->title = $propertyValue;
                $this->renderElement[$propertyName] = true;
                break;
            case 'background':
                $this->elements[$propertyName]->color = $propertyValue;
                break;
            case 'legend':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'boolean' );
                }

                $this->renderElement['legend'] = (bool) $propertyValue;
                break;
            case 'renderer':
                if ( $propertyValue instanceof ezcGraphRenderer )
                {
                    $this->properties['renderer'] = $propertyValue;
                    $this->properties['renderer']->setDriver( $this->driver );
                    return $this->properties['renderer'];
                }
                else 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphRenderer' );
                }
                break;
            case 'driver':
                if ( $propertyValue instanceof ezcGraphDriver )
                {
                    $this->properties['driver'] = $propertyValue;
                    $this->properties['renderer']->setDriver( $this->driver );
                    return $this->properties['driver'];
                }
                else 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphDriver' );
                }
                break;
            case 'palette':
                if ( $propertyValue instanceof ezcGraphPalette )
                {
                    $this->properties['palette'] = $propertyValue;
                    $this->setFromPalette( $this->palette );
                }
                else
                {
                    throw new ezcBaseValueException( "palette", $propertyValue, "instanceof ezcGraphPalette" );
                }

                break;
            case 'renderedFile':
                $this->properties['renderedFile'] = (string) $propertyValue;
                break;
            case 'options':
                if ( $propertyValue instanceof ezcGraphChartOptions )
                {
                    $this->options = $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( "options", $propertyValue, "instanceof ezcGraphOptions" );
                }
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
                break;
        }
    }

    /**
     * Set colors and border fro this element
     * 
     * @param ezcGraphPalette $palette Palette
     * @return void
     */
    public function setFromPalette( ezcGraphPalette $palette )
    {
        $this->options->font->name = $palette->fontName;
        $this->options->font->color = $palette->fontColor;

        foreach ( $this->elements as $element )
        {
            $element->setFromPalette( $palette );
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
        if ( array_key_exists( $propertyName, $this->properties ) )
        {
            return $this->properties[$propertyName];
        }

        if ( isset( $this->elements[$propertyName] ) )
        {
            return $this->elements[$propertyName];
        }

        if ( ( $propertyName === 'options' ) ||
             ( $propertyName === 'data' ) )
        {
            return $this->$propertyName;
        }
        else
        {
            throw new ezcGraphNoSuchElementException( $propertyName );
        }
    }

    /**
     * Returns the default display type of the current chart type.
     * 
     * @return int Display type
     */
    abstract public function getDefaultDisplayType();

    /**
     * Return filename of rendered file, and false if no file was yet rendered.
     * 
     * @return mixed
     */
    public function getRenderedFile()
    {
        return ( $this->renderedFile !== null ? $this->renderedFile : false );
    }

    /**
     * Renders this chart
     * 
     * Creates basic visual chart elements from the chart to be processed by 
     * the renderer.
     * 
     * @param int $width
     * @param int $height
     * @param string $file
     * @return void
     */
    abstract public function render( $width, $height, $file = null );

    /**
     * Renders this chart to direct output
     * 
     * Does the same as ezcGraphChart::render(), but renders directly to 
     * output and not into a file.
     * 
     * @param int $width
     * @param int $height
     * @return void
     */
    abstract public function renderToOutput( $width, $height );
}

?>
