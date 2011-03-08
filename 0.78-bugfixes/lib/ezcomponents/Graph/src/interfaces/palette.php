<?php
/**
 * File containing the abstract ezcGraphPalette class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Abstract class to contain pallet definitions
 * 
 * @version 1.5
 * @package Graph
 * @mainclass
 */
abstract class ezcGraphPalette
{
    /**
     * Indicates which color should be used for the next dataset
     * 
     * @var integer
     */
    protected $colorIndex = -1;

    /**
     * Indicates which symbol should be used for the nect dataset
     * 
     * @var integer
     */
    protected $symbolIndex = -1;

    /**
     * Axiscolor 
     * 
     * @var ezcGraphColor
     */
    protected $axisColor;

    /**
     * Color of grid lines
     * 
     * @var ezcGraphColor
     */
    protected $majorGridColor;

    /**
     * Color of minor grid lines
     * 
     * @var ezcGraphColor
     */
    protected $minorGridColor;

    /**
     * Array with colors for datasets
     * 
     * @var array
     */
    protected $dataSetColor;

    /**
     * Array with symbols for datasets 
     * 
     * @var array
     */
    protected $dataSetSymbol;

    /**
     * Name of font to use
     * 
     * @var string
     */
    protected $fontName;

    /**
     * Fontcolor 
     * 
     * @var ezcGraphColor
     */
    protected $fontColor;

    /**
     * Backgroundcolor 
     * 
     * @var ezcGraphColor
     */
    protected $chartBackground;

    /**
     * Bordercolor the chart
     * 
     * @var ezcGraphColor
     */
    protected $chartBorderColor;

    /**
     * Borderwidth for the chart
     * 
     * @var integer
     * @access protected
     */
    protected $chartBorderWidth = 0;

    /**
     * Backgroundcolor for elements
     * 
     * @var ezcGraphColor
     */
    protected $elementBackground;

    /**
     * Bordercolor for elements
     * 
     * @var ezcGraphColor
     */
    protected $elementBorderColor;

    /**
     * Borderwidth for elements
     * 
     * @var integer
     * @access protected
     */
    protected $elementBorderWidth = 0;

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

    /**
     * Ensure value to be a color
     * 
     * @param mixed $color Color to transform into a ezcGraphColor object
     * @return ezcGraphColor
     */
    protected function checkColor( &$color )
    {
        if ( $color == null )
        {
            return ezcGraphColor::fromHex( '#000000FF' );
        }
        elseif ( !( $color instanceof ezcGraphColor ) )
        {
            $color = ezcGraphColor::create( $color );
        }

        return $color;
    }

    /**
     * Manually reset the color counter to use the first color again
     * 
     * @access public
     */
    public function resetColorCounter()
    {
        $this->colorIndex = -1;
        $this->symbolIndex = -1;
    }

    /**
     * Returns the requested property
     * 
     * @param string $propertyName Name of property
     * @return mixed
     * @ignore
     */
    public function __get( $propertyName )
    {
        switch ( $propertyName )
        {
            case 'axisColor':
            case 'majorGridColor':
            case 'minorGridColor':
            case 'fontColor':
            case 'chartBackground':
            case 'chartBorderColor':
            case 'elementBackground':
            case 'elementBorderColor':
                return ( $this->$propertyName = $this->checkColor( $this->$propertyName ) );
            
            case 'dataSetColor':
                $this->colorIndex = ( ( $this->colorIndex + 1 ) % count( $this->dataSetColor ) );
                return $this->checkColor( $this->dataSetColor[ $this->colorIndex ] );
            case 'dataSetSymbol':
                $this->symbolIndex = ( ( $this->symbolIndex + 1 ) % count( $this->dataSetSymbol ) );
                return $this->dataSetSymbol[ $this->symbolIndex ];

            case 'fontName':
            case 'chartBorderWidth':
            case 'elementBorderWidth':
            case 'padding':
            case 'margin':
                return $this->$propertyName;

            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
    }

    /**
     * __set 
     * 
     * @param mixed $propertyName Property name
     * @param mixed $propertyValue Property value
     * @access public
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'axisColor':
            case 'majorGridColor':
            case 'minorGridColor':
            case 'fontColor':
            case 'chartBackground':
            case 'chartBorderColor':
            case 'elementBackground':
            case 'elementBorderColor':
                $this->$propertyName = ezcGraphColor::create( $propertyValue );
                break;

            case 'dataSetColor':
                if ( !is_array( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'array( ezcGraphColor )' );
                }

                $this->dataSetColor = array();
                foreach ( $propertyValue as $value )
                {
                    $this->dataSetColor[] = ezcGraphColor::create( $value );
                }
                $this->colorIndex = -1;
                break;
            case 'dataSetSymbol':
                if ( !is_array( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'array( (int) ezcGraph::SYMBOL_TYPE )' );
                }

                $this->dataSetSymbol = array();
                foreach ( $propertyValue as $value )
                {
                    $this->dataSetSymbol[] = (int) $value;
                }
                $this->symbolIndex = -1;
                break;

            case 'fontName':
                $this->$propertyName = (string) $propertyValue;
                break;

            case 'chartBorderWidth':
            case 'elementBorderWidth':
            case 'padding':
            case 'margin':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->$propertyName = (int) $propertyValue;
                break;
        
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
    }
}

?>
