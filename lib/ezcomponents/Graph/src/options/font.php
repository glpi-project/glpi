<?php
/**
 * File containing the ezcGraphFontOption class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class containing the options for font configuration.
 *
 * We try to fulfill two goals regarding font configuration. First, there
 * should be a single point to configure the fonts used for the text areas
 * in the chart.  On the other hand, it should be possible to configure
 * the fonts independently for each chart element.
 *
 * The solution is that you can modify the global font configuration by
 * accessing $graph->options->font. This takes effect on all chart
 * elements unless you intentionally access the font configuration of an
 * individual chart element. The following example shows, how this works.
 *
 * <code>
 *  $graph = new ezcGraphPieChart();
 *  $graph->title = 'Access statistics';
 *
 *  // Set the maximum font size to 8 for all chart elements
 *  $graph->options->font->maxFontSize = 8;
 *
 *  // Set the font size for the title independently to 14
 *  $graph->title->font->maxFontSize = 14;
 *
 *  // The following only affects all elements except the // title element,
 *  // which now has its own font configuration.
 *  //
 *  // Keep in mind that the specified font is driver specific. A pure name
 *  // works for the SVG driver, used here. The GD driver for example
 *  // requires a path to a TTF file.
 *  $graph->options->font->name = 'serif';
 *
 *  $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *      'Mozilla' => 19113,
 *      'Explorer' => 10917,
 *      'Opera' => 1464,
 *      'Safari' => 652,
 *      'Konqueror' => 474,
 *  ) );
 * </code>
 *
 * @property string $name
 *           Name of font.
 * @property string $path
 *           Path to font file.
 * @property int $type
 *           Type of used font. May be one of the following:
 *            - TTF_FONT    Native TTF fonts
 *            - PS_FONT     PostScript Type1 fonts
 *            - FT2_FONT    FreeType 2 fonts
 *           The type is normally automatically detected when you set the path
 *           to the font file.
 * @property float $minFontSize
 *           Minimum font size for displayed texts.
 * @property float $maxFontSize
 *           Maximum font size for displayed texts.
 * @property float $minimalUsedFont
 *           The minimal used font size for the current element group. This
 *           property is set by the driver to maintain this information and
 *           should not be used to configure the apperance of the chart. See
 *           $minFontSize instead.
 * @property ezcGraphColor $color
 *           Font color.
 * @property ezcGraphColor $background
 *           Background color. The actual area filled with the background color
 *           is influenced by the settings $padding and $minimizeBorder.
 * @property ezcGraphColor $border
 *           Border color for the text. The distance between the text and
 *           border is defined by the properties $padding and $minimizeBorder.
 * @property int $borderWidth
 *           With of the border. To enable the border you need to set the
 *           $border property to some color.
 * @property int $padding
 *           Padding between text and border.
 * @property bool $minimizeBorder
 *           Fit the border exactly around the text, or use the complete 
 *           possible space. This setting is only relevant, when a border
 *           color has been set for the font.
 * @property bool $textShadow
 *           Draw shadow for texts. The color of the shadow is defined in
 *           the property $textShadowColor.
 * @property int $textShadowOffset
 *           Offset for text shadow. This defines the distance the shadow
 *           is moved to the bottom left relative from the text position.
 * @property ezcGraphColor $textShadowColor
 *           Color of text shadow. If left at the default value "false""
 *           the inverse color of the text color will be used.
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphFontOptions extends ezcBaseOptions
{
    /**
     * Indicates if path already has been checked for correct font
     * 
     * @var bool
     */
    protected $pathChecked = false;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['name'] = 'sans-serif';
//        $this->properties['path'] = 'Graph/tests/data/font.ttf';
        $this->properties['path'] = '';
        $this->properties['type'] = ezcGraph::TTF_FONT;

        $this->properties['minFontSize'] = 6;
        $this->properties['maxFontSize'] = 96;
        $this->properties['minimalUsedFont'] = 96;
        $this->properties['color'] = ezcGraphColor::fromHex( '#000000' );

        $this->properties['background'] = false;
        $this->properties['border'] = false;
        $this->properties['borderWidth'] = 1;
        $this->properties['padding'] = 0;
        $this->properties['minimizeBorder'] = true;
        
        $this->properties['textShadow'] = false;
        $this->properties['textShadowOffset'] = 1;
        $this->properties['textShadowColor'] = false;

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
            case 'minFontSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 1' );
                }

                // Ensure min font size is smaller or equal max font size.
                if ( $propertyValue > $this->properties['maxFontSize'] )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float <= ' . $this->properties['maxFontSize'] );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;

            case 'maxFontSize':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 1' );
                }

                // Ensure max font size is greater or equal min font size.
                if ( $propertyValue < $this->properties['minFontSize'] )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float >= ' . $this->properties['minFontSize'] );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;

            case 'minimalUsedFont':
                $propertyValue = (float) $propertyValue;
                if ( $propertyValue < $this->minimalUsedFont )
                {
                    $this->properties['minimalUsedFont'] = $propertyValue;
                }
                break;

            case 'color':
            case 'background':
            case 'border':
            case 'textShadowColor':
                $this->properties[$propertyName] = ezcGraphColor::create( $propertyValue );
                break;

            case 'borderWidth':
            case 'padding':
            case 'textShadowOffset':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int >= 0' );
                }

                $this->properties[$propertyName] = (int) $propertyValue;
                break;

            case 'minimizeBorder':
            case 'textShadow':
                if ( !is_bool( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }
                $this->properties[$propertyName] = (bool) $propertyValue;
                break;

            case 'name':
                if ( is_string( $propertyValue ) )
                {
                    $this->properties['name'] = $propertyValue;
                }
                else 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'string' );
                }
                break;
            case 'path':
                if ( is_file( $propertyValue ) && is_readable( $propertyValue ) )
                {
                    $this->properties['path'] = $propertyValue;
                    $parts = pathinfo( $this->properties['path'] );
                    switch ( strtolower( $parts['extension'] ) )
                    {
                        case 'fdb':
                            $this->properties['type'] = ezcGraph::PALM_FONT;
                            break;
                        case 'pfb':
                            $this->properties['type'] = ezcGraph::PS_FONT;
                            break;
                        case 'ttf':
                            $this->properties['type'] = ezcGraph::TTF_FONT;
                            break;
                        case 'svg':
                            $this->properties['type'] = ezcGraph::SVG_FONT;
                            $this->properties['name'] = ezcGraphSvgFont::getFontName( $propertyValue );
                            break;
                        default:
                            throw new ezcGraphUnknownFontTypeException( $propertyValue, $parts['extension'] );
                    }
                    $this->pathChecked = true;
                }
                else 
                {
                    throw new ezcBaseFileNotFoundException( $propertyValue, 'font' );
                }
                break;
            case 'type':
                if ( is_int( $propertyValue ) )
                {
                    $this->properties['type'] = $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int' );
                }
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
                break;
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
            case 'textShadowColor':
                // Use inverted font color if false
                if ( $this->properties['textShadowColor'] === false )
                {
                    $this->properties['textShadowColor'] = $this->properties['color']->invert();
                }

                return $this->properties['textShadowColor'];
            case 'path':
                if ( $this->pathChecked === false )
                {
                    // Enforce call of path check
                    $this->__set( 'path', $this->properties['path'] );
                }
                // No break to use parent return
            default:
                return parent::__get( $propertyName );
        }
    }
}

?>
