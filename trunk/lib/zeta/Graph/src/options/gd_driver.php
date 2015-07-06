<?php
/**
 * File containing the ezcGraphDriverOption class
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
 * Class containing the extended driver options for the gd driver.
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->palette = new ezcGraphPaletteEzGreen();
 *   $graph->title = 'Access statistics';
 *   $graph->legend = false;
 *   
 *   $graph->driver = new ezcGraphGdDriver();
 *   $graph->options->font = 'tutorial_font.ttf';
 *   
 *   // Generate a Jpeg with lower quality. The default settings result in a better
 *   // quality image
 *   $graph->driver->options->supersampling = 1;
 *   $graph->driver->options->jpegQuality = 100;
 *   $graph->driver->options->imageFormat = IMG_JPEG;
 *   
 *   $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *       'Mozilla' => 19113,
 *       'Explorer' => 10917,
 *       'Opera' => 1464,
 *       'Safari' => 652,
 *       'Konqueror' => 474,
 *   ) );
 *   
 *   $graph->render( 400, 200, 'tutorial_dirver_gd.jpg' );
 * </code>
 *
 * @property int $imageFormat
 *           Type of generated image.
 *           Should be one of those: IMG_PNG, IMG_JPEG
 * @property int $jpegQuality
 *           Quality of generated jpeg
 * @property int $detail
 *           Count of degrees to render one polygon for in circular arcs
 * @property int $supersampling
 *           Factor of supersampling used to simulate antialiasing
 * @property string $background
 *           Background image to put the graph on
 * @property string $resampleFunction
 *           Function used to resample / resize images
 * @property bool $forceNativeTTF
 *           Force use of native ttf functions instead of free type 2
 * @property float $imageMapResolution
 *           Degree step used to interpolate round image primitives by 
 *           polygons for image maps
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphGdDriverOptions extends ezcGraphDriverOptions
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
        $this->properties['imageFormat'] = IMG_PNG;
        $this->properties['jpegQuality'] = 70;
        $this->properties['detail'] = 1;
        $this->properties['shadeCircularArc'] = .5;
        $this->properties['supersampling'] = 2;
        $this->properties['background'] = false;
        $this->properties['resampleFunction'] = 'imagecopyresampled';
        $this->properties['forceNativeTTF'] = false;
        $this->properties['imageMapResolution'] = 10;

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
            case 'imageFormat':
                if ( imagetypes() & $propertyValue )
                {
                    $this->properties['imageFormat'] = (int) $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'Unsupported image type.' );
                }
                break;
            case 'jpegQuality':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 100 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= int <= 100' );
                }

                $this->properties['jpegQuality'] = (int) $propertyValue;
                break;
            case 'detail':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 1' );
                }

                $this->properties['detail'] = (int) $propertyValue;
                break;
            case 'supersampling':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 1' );
                }

                $this->properties['supersampling'] = (int) $propertyValue;
                break;
            case 'background':
                if ( $propertyValue === false ||
                     ( is_file( $propertyValue ) && is_readable( $propertyValue ) ) )
                {
                    $this->properties['background'] = realpath( $propertyValue );
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'readable file' );
                }
                break;
            case 'resampleFunction':
                if ( ezcBaseFeatures::hasFunction( $propertyValue ) )
                {
                    $this->properties['resampleFunction'] = $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'function' );
                }
                break;
            case 'forceNativeTTF':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }

                $this->properties['forceNativeTTF'] = (bool) $propertyValue;
                break;
            case 'imageMapResolution':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 1' );
                }

                $this->properties['imageMapResolution'] = (int) $propertyValue;
                break;
            default:
                parent::__set( $propertyName, $propertyValue );
                break;
        }
    }
}

?>
