<?php
/**
 * File containing the ezcGraphFlashDriverOption class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class containing the extended configuration options for the flash driver.
 *
 * The flash driver can be configured to use a different circle resolution, as
 * circles are only emulated in the flash driver, and to use a diffrent
 * compression for the generated SWF files.
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->title = 'Access statistics';
 *   $graph->legend = false;
 *   
 *   $graph->driver = new ezcGraphFlashDriver();
 *   $graph->driver->options->compresion = 0;
 *
 *   $graph->options->font = 'tutorial_font.fdb';
 *   
 *   $graph->driver->options->compression = 7;
 *   
 *   $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *       'Mozilla' => 19113,
 *       'Explorer' => 10917,
 *       'Opera' => 1464,
 *       'Safari' => 652,
 *       'Konqueror' => 474,
 *   ) );
 *   
 *   $graph->render( 400, 200, 'tutorial_driver_flash.swf' );
 * </code>
 *
 * @property int $compression
 *           Compression level used for generated flash file
 *           @see http://php.net/manual/en/function.swfmovie.save.php
 * @property float $circleResolution
 *           Resolution for circles, until I understand how to draw ellipses
 *           with SWFShape::curveTo()
 * 
 * @version 1.5
 * @package Graph
 */
class ezcGraphFlashDriverOptions extends ezcGraphDriverOptions
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
        $this->properties['compression'] = 9;
        $this->properties['circleResolution'] = 2.;

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
            case 'compression':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) ||
                     ( $propertyValue > 9 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= int <= 9' );
                }

                $this->properties['compression'] = max( 0, min( 9, (int) $propertyValue ) );
                break;
            case 'circleResolution':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }

                $this->properties['circleResolution'] = (float) $propertyValue;
                break;
            default:
                parent::__set( $propertyName, $propertyValue );
                break;
        }
    }
}

?>
