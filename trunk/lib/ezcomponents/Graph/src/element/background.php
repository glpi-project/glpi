<?php
/**
 * File containing the ezcGraphChartElementBackground class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Chart element representing the background.
 *
 * Chart elements can be understood as widgets or layout container inside the
 * chart. The actual transformation to images happens inside the renderers.
 * They represent all elements inside the chart and contain mostly general
 * formatting options, while the renderer itself might define additional
 * formatting options for some chart elments. You can find more about the
 * general formatting options for chart elements in the base class
 * ezcGraphChartElement.
 *
 * Additionally to common background and border for chart elements it can draw
 * an image on the chart background, and optionally repeat it. The position
 * will be used to define the start of the repetition.
 *
 * The repetition effects are modelled similar to the background settings in
 * CSS. The example shows some common settings:
 *
 * <code>
 *  $chart = new ezcGraphPieChart();
 *  $chart->data['example'] = new ezcGraphArrayDataSet( array(
 *      'Foo' => 23,
 *      'Bar' => 42,
 *  ) );
 *
 *  $chart->background->image = 'background.png';
 *
 *  // Image would be repeated horizontal at the top of the background
 *  $chart->background->repeat = ezcGraph::HORIZONTAL;
 *  $chart->background->postion = ezcGraph::TOP;
 *
 *  // Image would be placed once in the center
 *  $chart->background->repeat = ezcGraph::NO_REPEAT; // default;
 *  $chart->background->position = ezcGraph::CENTER | ezcGraph::MIDDLE;
 *
 *  // Image would be repeated all over the chart, the position is irrelevant
 *  $chart->background->repeat = ezcGraph::HORIZONTAL | ezcGraph::VERTICAL;
 *
 *  $graph->render( 400, 250, 'legend.svg' );
 * </code>
 *
 * @property string $image
 *           Filename of the file to use for background
 * @property int $repeat
 *           Defines how the background image gets repeated
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphChartElementBackground extends ezcGraphChartElement
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
        $this->properties['image'] = false;
        $this->properties['repeat'] = ezcGraph::NO_REPEAT;

        parent::__construct( $options );
    }

    /**
     * __set 
     * 
     * @param mixed $propertyName 
     * @param mixed $propertyValue 
     * @throws ezcBaseValueException
     *          If a submitted parameter was out of range or type.
     * @throws ezcBasePropertyNotFoundException
     *          If a the value for the property options is not an instance of
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'image':
                // Check for existance of file
                if ( !is_file( $propertyValue ) || !is_readable( $propertyValue ) )
                {
                    throw new ezcBaseFileNotFoundException( $propertyValue );
                }

                // Check for beeing an image file
                $data = getImageSize( $propertyValue );
                if ( $data === false )
                {
                    throw new ezcGraphInvalidImageFileException( $propertyValue );
                }

                // SWF files are useless..
                if ( $data[2] === 4 ) 
                {
                    throw new ezcGraphInvalidImageFileException( 'We cant use SWF files like <' . $propertyValue . '>.' );
                }

                $this->properties['image'] = $propertyValue;
                break;
            case 'repeat':
                if ( ( $propertyValue >= 0 ) && ( $propertyValue <= 3 ) )
                {
                    $this->properties['repeat'] = (int) $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= int <= 3' );
                }
                break;
            case 'position':
                // Overwrite parent position setter, to be able to use 
                // combination of positions like 
                //      ezcGraph::TOP | ezcGraph::CENTER
                if ( is_int( $propertyValue ) )
                {
                    $this->properties['position'] = $propertyValue;
                }
                else 
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'integer' );
                }
                break;
            case 'color':
                // Use color as an alias to set background color for background
                $this->__set( 'background', $propertyValue );
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
            case 'color':
                // Use color as an alias to set background color for background
                return $this->properties['background'];
            default:
                return parent::__get( $propertyName );
        }
    }

    /**
     * Set colors and border for this element
     *
     * Method is overwritten because we do not ant to apply the global padding 
     * and margin here.
     * 
     * @param ezcGraphPalette $palette Palette
     * @return void
     */
    public function setFromPalette( ezcGraphPalette $palette )
    {
        $this->border = $palette->chartBorderColor;
        $this->borderWidth = $palette->chartBorderWidth;
        $this->background = $palette->chartBackground;
        $this->padding = 0;
        $this->margin = 0;
    }

    /**
     * Render the background
     *
     * @param ezcGraphRenderer $renderer Renderer
     * @param ezcGraphBoundings $boundings Boundings
     * @return ezcGraphBoundings Remaining boundings
     */
    public function render( ezcGraphRenderer $renderer, ezcGraphBoundings $boundings )
    {
        $boundings = $renderer->drawBox(
            $boundings,
            $this->background,
            $this->border,
            $this->borderWidth,
            $this->margin,
            $this->padding
        );

        if ( $this->image === false )
        {
            return $boundings;
        }

        $renderer->drawBackgroundImage(
            $boundings,
            $this->image,
            $this->position,
            $this->repeat
        );

        return $boundings;
    }
}

?>
