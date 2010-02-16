<?php
/**
 * File containing the ezcGraphSVGDriver class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Extension of the basic Driver package to utilize the SVGlib.
 *
 * This drivers options are defined in the class 
 * {@link ezcGraphSvgDriverOptions} extending the basic driver options class
 * {@link ezcGraphDriverOptions}. 
 *
 * As this is the default driver you do not need to explicitely set anything to
 * use it, but may use some of its advanced features.
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->background->color = '#FFFFFFFF';
 *   $graph->title = 'Access statistics';
 *   $graph->legend = false;
 *   
 *   $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *       'Mozilla' => 19113,
 *       'Explorer' => 10917,
 *       'Opera' => 1464,
 *       'Safari' => 652,
 *       'Konqueror' => 474,
 *   ) );
 *   
 *   $graph->renderer = new ezcGraphRenderer3d();
 *   $graph->renderer->options->pieChartShadowSize = 10;
 *   $graph->renderer->options->pieChartGleam = .5;
 *   $graph->renderer->options->dataBorder = false;
 *   $graph->renderer->options->pieChartHeight = 16;
 *   $graph->renderer->options->legendSymbolGleam = .5;
 * 
 *   // SVG driver options
 *   $graph->driver->options->templateDocument = dirname( __FILE__ ) . '/template.svg';
 *   $graph->driver->options->graphOffset = new ezcGraphCoordinate( 25, 40 );
 *   $graph->driver->options->insertIntoGroup = 'ezcGraph';
 *   
 *   $graph->render( 400, 200, 'tutorial_driver_svg.svg' );
 * </code>
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphSvgDriver extends ezcGraphDriver
{

    /**
     * DOM tree of the svg document
     * 
     * @var DOMDocument
     */
    protected $dom;

    /**
     * DOMElement containing all svg style definitions
     * 
     * @var DOMElement
     */
    protected $defs;

    /**
     * DOMElement containing all svg objects
     * 
     * @var DOMElement
     */
    protected $elements;

    /**
     * List of strings to draw
     * array ( array(
     *          'text' => array( 'strings' ),
     *          'options' => ezcGraphFontOptions,
     *      )
     * 
     * @var array
     */
    protected $strings = array();

    /**
     * List of already created gradients
     * 
     * @var array
     */
    protected $drawnGradients = array();

    /**
     * Numeric unique element id
     * 
     * @var int
     */
    protected $elementID = 0;

    /**
     * Font storage for SVG font glyphs and kernings.
     * 
     * @var ezcGraphSvgFont
     */
    protected $font = null;

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        ezcBase::checkDependency( 'Graph', ezcBase::DEP_PHP_EXTENSION, 'dom' );
        $this->options = new ezcGraphSvgDriverOptions( $options );
        $this->font = new ezcGraphSvgFont();
    }

    /**
     * Creates the DOM object to insert SVG nodes in.
     *
     * If the DOM document does not exists it will be created or loaded 
     * according to the settings.
     * 
     * @return void
     */
    protected function createDocument()
    {
        if ( $this->dom === null )
        {
            // Create encoding based dom document
            if ( $this->options->encoding !== null )
            {
                $this->dom = new DOMDocument( '1.0', $this->options->encoding );
            }
            else
            {
                $this->dom = new DOMDocument( '1.0' );
            }

            if ( $this->options->templateDocument !== false )
            {
                $this->dom->load( $this->options->templateDocument );

                $this->defs = $this->dom->getElementsByTagName( 'defs' )->item( 0 );
                $svg = $this->dom->getElementsByTagName( 'svg' )->item( 0 );
            }
            else
            {
                $svg = $this->dom->createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
                $this->dom->appendChild( $svg );

                $svg->setAttribute( 'width', $this->options->width );
                $svg->setAttribute( 'height', $this->options->height );
                $svg->setAttribute( 'version', '1.0' );
                $svg->setAttribute( 'id', $this->options->idPrefix );

                $this->defs = $this->dom->createElement( 'defs' );
                $this->defs = $svg->appendChild( $this->defs );
            }

            if ( $this->options->insertIntoGroup !== false )
            {
                // getElementById only works for Documents validated against a certain 
                // schema, so that the use of XPath should be faster in most cases.
                $xpath = new DomXPath( $this->dom );
                $this->elements = $xpath->query( '//*[@id = \'' . $this->options->insertIntoGroup . '\']' )->item( 0 );
                if ( !$this->elements )
                {
                    throw new ezcGraphSvgDriverInvalidIdException( $this->options->insertIntoGroup );
                }
            }
            else
            {
                $this->elements = $this->dom->createElement( 'g' );
                $this->elements->setAttribute( 'id', $this->options->idPrefix . 'Chart' );
                $this->elements->setAttribute( 'color-rendering', $this->options->colorRendering );
                $this->elements->setAttribute( 'shape-rendering', $this->options->shapeRendering );
                $this->elements->setAttribute( 'text-rendering', $this->options->textRendering );
                $this->elements = $svg->appendChild( $this->elements );
            }
        }
    }

    /**
     * Return gradient URL
     *
     * Creates the definitions needed for a gradient, if a proper gradient does
     * not yet exists. In each case a URL referencing the correct gradient will
     * be returned.
     * 
     * @param ezcGraphColor $color Gradient
     * @return string Gradient URL
     */
    protected function getGradientUrl( ezcGraphColor $color )
    {
        switch ( true )
        {
            case ( $color instanceof ezcGraphLinearGradient ):
                if ( !in_array( $color->__toString(), $this->drawnGradients, true ) )
                {
                    $gradient = $this->dom->createElement( 'linearGradient' );
                    $gradient->setAttribute( 'id', 'Definition_' . $color->__toString() );
                    $this->defs->appendChild( $gradient );

                    // Start of linear gradient
                    $stop = $this->dom->createElement( 'stop' );
                    $stop->setAttribute( 'offset', 0 );
                    $stop->setAttribute( 'style', sprintf( 'stop-color: #%02x%02x%02x; stop-opacity: %.2F;',
                        $color->startColor->red,
                        $color->startColor->green,
                        $color->startColor->blue,
                        1 - ( $color->startColor->alpha / 255 )
                        )
                    );
                    $gradient->appendChild( $stop );

                    // End of linear gradient
                    $stop = $this->dom->createElement( 'stop' );
                    $stop->setAttribute( 'offset', 1 );
                    $stop->setAttribute( 'style', sprintf( 'stop-color: #%02x%02x%02x; stop-opacity: %.2F;',
                        $color->endColor->red,
                        $color->endColor->green,
                        $color->endColor->blue,
                        1 - ( $color->endColor->alpha / 255 )
                        )
                    );
                    $gradient->appendChild( $stop );

                    $gradient = $this->dom->createElement( 'linearGradient' );
                    $gradient->setAttribute( 'id', $color->__toString() );
                    $gradient->setAttribute( 'x1', sprintf( '%.4F', $color->startPoint->x ) );
                    $gradient->setAttribute( 'y1', sprintf( '%.4F', $color->startPoint->y ) );
                    $gradient->setAttribute( 'x2', sprintf( '%.4F', $color->endPoint->x ) );
                    $gradient->setAttribute( 'y2', sprintf( '%.4F', $color->endPoint->y ) );
                    $gradient->setAttribute( 'gradientUnits', 'userSpaceOnUse' );
                    $gradient->setAttributeNS( 
                        'http://www.w3.org/1999/xlink', 
                        'xlink:href',
                        '#Definition_' . $color->__toString()
                    );
                    $this->defs->appendChild( $gradient );

                    $this->drawnGradients[] = $color->__toString();
                }

                return sprintf( 'url(#%s)',
                    $color->__toString()
                );
            case ( $color instanceof ezcGraphRadialGradient ):
                if ( !in_array( $color->__toString(), $this->drawnGradients, true ) )
                {
                    $gradient = $this->dom->createElement( 'linearGradient' );
                    $gradient->setAttribute( 'id', 'Definition_' . $color->__toString() );
                    $this->defs->appendChild( $gradient );

                    // Start of linear gradient
                    $stop = $this->dom->createElement( 'stop' );
                    $stop->setAttribute( 'offset', 0 );
                    $stop->setAttribute( 'style', sprintf( 'stop-color: #%02x%02x%02x; stop-opacity: %.2F;',
                        $color->startColor->red,
                        $color->startColor->green,
                        $color->startColor->blue,
                        1 - ( $color->startColor->alpha / 255 )
                        )
                    );
                    $gradient->appendChild( $stop );

                    // End of linear gradient
                    $stop = $this->dom->createElement( 'stop' );
                    $stop->setAttribute( 'offset', 1 );
                    $stop->setAttribute( 'style', sprintf( 'stop-color: #%02x%02x%02x; stop-opacity: %.2F;',
                        $color->endColor->red,
                        $color->endColor->green,
                        $color->endColor->blue,
                        1 - ( $color->endColor->alpha / 255 )
                        )
                    );
                    $gradient->appendChild( $stop );

                    $gradient = $this->dom->createElement( 'radialGradient' );
                    $gradient->setAttribute( 'id', $color->__toString() );
                    $gradient->setAttribute( 'cx', sprintf( '%.4F', $color->center->x ) );
                    $gradient->setAttribute( 'cy', sprintf( '%.4F', $color->center->y ) );
                    $gradient->setAttribute( 'fx', sprintf( '%.4F', $color->center->x ) );
                    $gradient->setAttribute( 'fy', sprintf( '%.4F', $color->center->y ) );
                    $gradient->setAttribute( 'r', max( $color->height, $color->width ) );
                    $gradient->setAttribute( 'gradientUnits', 'userSpaceOnUse' );
                    $gradient->setAttributeNS( 
                        'http://www.w3.org/1999/xlink', 
                        'xlink:href',
                        '#Definition_' . $color->__toString()
                    );
                    $this->defs->appendChild( $gradient );

                    $this->drawnGradients[] = $color->__toString();
                }

                return sprintf( 'url(#%s)',
                    $color->__toString()
                );
            default:
                return false;
        }

    }

    /**
     * Get SVG style definition
     *
     * Returns a string with SVG style definitions created from color, 
     * fillstatus and line thickness.
     * 
     * @param ezcGraphColor $color Color
     * @param mixed $filled Filled
     * @param float $thickness Line thickness.
     * @return string Formatstring
     */
    protected function getStyle( ezcGraphColor $color, $filled = true, $thickness = 1. )
    {
        if ( $filled )
        {
            if ( $url = $this->getGradientUrl( $color ) )
            {
                return sprintf( 'fill: %s; stroke: none;', $url );
            }
            else
            {
                return sprintf( 'fill: #%02x%02x%02x; fill-opacity: %.2F; stroke: none;',
                    $color->red,
                    $color->green,
                    $color->blue,
                    1 - ( $color->alpha / 255 )
                );
            }
        }
        else
        {
            if ( $url = $this->getGradientUrl( $color ) )
            {
                return sprintf( 'fill: none; stroke: %s;', $url );
            }
            else
            {
                return sprintf( 'fill: none; stroke: #%02x%02x%02x; stroke-width: %d; stroke-opacity: %.2F; stroke-linecap: %s; stroke-linejoin: %s;',
                    $color->red,
                    $color->green,
                    $color->blue,
                    $thickness,
                    1 - ( $color->alpha / 255 ),
                    $this->options->strokeLineCap,
                    $this->options->strokeLineJoin
                );
            }
        }
    }

    /**
     * Draws a single polygon. 
     * 
     * @param array $points Point array
     * @param ezcGraphColor $color Polygon color
     * @param mixed $filled Filled
     * @param float $thickness Line thickness
     * @return void
     */
    public function drawPolygon( array $points, ezcGraphColor $color, $filled = true, $thickness = 1. )
    {
        $this->createDocument();

        if ( !$filled )
        {
            // The middle of the border is on the outline of a polygon in SVG, 
            // fix that:
            try
            {
                $points = $this->reducePolygonSize( $points, $thickness / 2 );
            }
            catch ( ezcGraphReducementFailedException $e )
            {
                return false;
            }
        }

        $lastPoint = end( $points );
        $pointString = sprintf( ' M %.4F,%.4F', 
            $lastPoint->x + $this->options->graphOffset->x, 
            $lastPoint->y + $this->options->graphOffset->y
        );

        foreach ( $points as $point )
        {
            $pointString .= sprintf( ' L %.4F,%.4F', 
                $point->x + $this->options->graphOffset->x,
                $point->y + $this->options->graphOffset->y
            );
        }
        $pointString .= ' z ';

        $path = $this->dom->createElement( 'path' );
        $path->setAttribute( 'd', $pointString );

        $path->setAttribute(
            'style',
            $this->getStyle( $color, $filled, $thickness )
        );
        $path->setAttribute( 'id', $id = ( $this->options->idPrefix . 'Polygon_' . ++$this->elementID ) );
        $this->elements->appendChild( $path );

        return $id;
    }
    
    /**
     * Draws a line 
     * 
     * @param ezcGraphCoordinate $start Start point
     * @param ezcGraphCoordinate $end End point
     * @param ezcGraphColor $color Line color
     * @param float $thickness Line thickness
     * @return void
     */
    public function drawLine( ezcGraphCoordinate $start, ezcGraphCoordinate $end, ezcGraphColor $color, $thickness = 1. )
    {
        $this->createDocument();  
        
        $pointString = sprintf( ' M %.4F,%.4F L %.4F,%.4F', 
            $start->x + $this->options->graphOffset->x, 
            $start->y + $this->options->graphOffset->y,
            $end->x + $this->options->graphOffset->x, 
            $end->y + $this->options->graphOffset->y
        );

        $path = $this->dom->createElement( 'path' );
        $path->setAttribute( 'd', $pointString );
        $path->setAttribute(
            'style', 
            $this->getStyle( $color, false, $thickness )
        );

        $path->setAttribute( 'id', $id = ( $this->options->idPrefix . 'Line_' . ++$this->elementID ) );
        $this->elements->appendChild( $path );

        return $id;
    }

    /**
     * Returns boundings of text depending on the available font extension
     * 
     * @param float $size Textsize
     * @param ezcGraphFontOptions $font Font
     * @param string $text Text
     * @return ezcGraphBoundings Boundings of text
     */
    protected function getTextBoundings( $size, ezcGraphFontOptions $font, $text )
    {
        if ( $font->type === ezcGraph::SVG_FONT )
        {
            return new ezcGraphBoundings(
                0,
                0,
                $this->font->calculateStringWidth( $font->path, $text ) * $size,
                $size
            );
        }
        else
        {
            // If we didn't get a SVG font, continue guessing the font width.
            return new ezcGraphBoundings(
                0,
                0,
                $this->getTextWidth( $text, $size ),
                $size
            );
        }
    }

    /**
     * Writes text in a box of desired size
     * 
     * @param string $string Text
     * @param ezcGraphCoordinate $position Top left position
     * @param float $width Width of text box
     * @param float $height Height of text box
     * @param int $align Alignement of text
     * @param ezcGraphRotation $rotation
     * @return void
     */
    public function drawTextBox( $string, ezcGraphCoordinate $position, $width, $height, $align, ezcGraphRotation $rotation = null )
    {
        $padding = $this->options->font->padding + ( $this->options->font->border !== false ? $this->options->font->borderWidth : 0 );

        $width -= $padding * 2;
        $height -= $padding * 2;
        $textPosition = new ezcGraphCoordinate(
            $position->x + $padding,
            $position->y + $padding
        );

        // Try to get a font size for the text to fit into the box
        $maxSize = min( $height, $this->options->font->maxFontSize );
        $result = false;
        for ( $size = $maxSize; $size >= $this->options->font->minFontSize; )
        {
            $result = $this->testFitStringInTextBox( $string, $position, $width, $height, $size );
            if ( is_array( $result ) )
            {
                break;
            }
            $size = ( ( $newsize = $size * ( $result ) ) >= $size ? $size - 1 : floor( $newsize ) );
        }
        
        if ( !is_array( $result ) )
        {
            if ( ( $height >= $this->options->font->minFontSize ) &&
                 ( $this->options->autoShortenString ) )
            {
                $result = $this->tryFitShortenedString( $string, $position, $width, $height, $size = $this->options->font->minFontSize );
            } 
            else
            {
                throw new ezcGraphFontRenderingException( $string, $this->options->font->minFontSize, $width, $height );
            }
        }

        $this->options->font->minimalUsedFont = $size;
        $this->strings[] = array(
            'text' => $result,
            'id' => $id = ( $this->options->idPrefix . 'TextBox_' . ++$this->elementID ),
            'position' => $textPosition,
            'width' => $width,
            'height' => $height,
            'align' => $align,
            'font' => $this->options->font,
            'rotation' => $rotation,
        );

        return $id;
    }

    /**
     * Guess text width for string
     *
     * The is no way to know the font or fontsize used by the SVG renderer to
     * render the string. We assume some character width defined in the SVG 
     * driver options, tu guess the length of a string. We discern between
     * numeric an non numeric strings, because we often use only numeric 
     * strings to display chart data and numbers tend to be a bit wider then
     * characters.
     * 
     * @param mixed $string 
     * @param mixed $size 
     * @access protected
     * @return void
     */
    protected function getTextWidth( $string, $size )
    {
        switch ( strtolower( $this->options->encoding ) )
        {
            case '':
            case 'utf-8':
            case 'utf-16':
                $string = utf8_decode( $string );
            break;
        }

        if ( is_numeric( $string ) )
        {
            return $size * strlen( $string ) * $this->options->assumedNumericCharacterWidth;
        }
        else
        {
            return $size * strlen( $string ) * $this->options->assumedTextCharacterWidth;
        }
    }

    /**
     * Encodes non-utf-8 strings
     *
     * Transforms non-utf-8 strings to their hex entities, because ext/DOM 
     * fails here with conversion errors.
     * 
     * @param string $string 
     * @return string
     */
    protected function encode( $string )
    {
        $string = htmlspecialchars( $string );

        switch ( strtolower( $this->options->encoding ) )
        {
            case '':
            case 'utf-8':
            case 'utf-16':
                return $string;
            default:
                // Manual escaping of non ANSII characters, because ext/DOM fails here
                return preg_replace_callback( 
                    '/[\\x80-\\xFF]/', 
                    create_function(
                        '$char',
                        'return sprintf( \'&#x%02x;\', ord( $char[0] ) );'
                    ),
                    $string 
                );
        }
    }

    /**
     * Draw all collected texts
     *
     * The texts are collected and their maximum possible font size is 
     * calculated. This function finally draws the texts on the image, this
     * delayed drawing has two reasons:
     *
     * 1) This way the text strings are always on top of the image, what 
     *    results in better readable texts
     * 2) The maximum possible font size can be calculated for a set of texts
     *    with the same font configuration. Strings belonging to one chart 
     *    element normally have the same font configuration, so that all texts
     *    belonging to one element will have the same font size.
     * 
     * @access protected
     * @return void
     */
    protected function drawAllTexts()
    {
        $elementsRoot = $this->elements;

        foreach ( $this->strings as $text )
        {
            // Add all text elements into one group
            $group = $this->dom->createElement( 'g' );
            $group->setAttribute( 'id', $text['id'] );

            if ( $text['rotation'] !== null )
            {
                $group->setAttribute( 'transform', sprintf( 'rotate( %.2F %.4F %.4F )',
                    $text['rotation']->getRotation(),
                    $text['rotation']->getCenter()->x,
                    $text['rotation']->getCenter()->y
                ) );
            }

            $group = $elementsRoot->appendChild( $group );

            $size = $text['font']->minimalUsedFont;
            $font = $text['font']->name;

            $completeHeight = count( $text['text'] ) * $size + ( count( $text['text'] ) - 1 ) * $this->options->lineSpacing;

            // Calculate y offset for vertical alignement
            switch ( true )
            {
                case ( $text['align'] & ezcGraph::BOTTOM ):
                    $yOffset = $text['height'] - $completeHeight;
                    break;
                case ( $text['align'] & ezcGraph::MIDDLE ):
                    $yOffset = ( $text['height'] - $completeHeight ) / 2;
                    break;
                case ( $text['align'] & ezcGraph::TOP ):
                default:
                    $yOffset = 0;
                    break;
            }

            $padding = $text['font']->padding + $text['font']->borderWidth / 2;
            if ( $this->options->font->minimizeBorder === true )
            {
                // Calculate maximum width of text rows
                $width = false;
                foreach ( $text['text'] as $line )
                {
                    $string = implode( ' ', $line );
                    if ( ( $strWidth = $this->getTextBoundings( $size, $text['font'], $string )->width ) > $width )
                    {
                        $width = $strWidth;
                    }
                }

                switch ( true )
                {
                    case ( $text['align'] & ezcGraph::LEFT ):
                        $xOffset = 0;
                        break;
                    case ( $text['align'] & ezcGraph::CENTER ):
                        $xOffset = ( $text['width'] - $width ) / 2;
                        break;
                    case ( $text['align'] & ezcGraph::RIGHT ):
                        $xOffset = $text['width'] - $width;
                        break;
                }

                $borderPolygonArray = array(
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding + $xOffset,
                        $text['position']->y - $padding + $yOffset
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $xOffset + $width,
                        $text['position']->y - $padding + $yOffset
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $xOffset + $width,
                        $text['position']->y + $padding * 2 + $yOffset + $completeHeight
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding + $xOffset,
                        $text['position']->y + $padding * 2 + $yOffset + $completeHeight
                    ),
                );
            }
            else
            {
                $borderPolygonArray = array(
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding,
                        $text['position']->y - $padding
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $text['width'],
                        $text['position']->y - $padding
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $text['width'],
                        $text['position']->y + $padding * 2 + $text['height']
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding,
                        $text['position']->y + $padding * 2 + $text['height']
                    ),
                );
            }

            // Set elements root temporary to local text group to ensure 
            // background and border beeing elements of text group
            $this->elements = $group;
            if ( $text['font']->background !== false )
            {
                $this->drawPolygon( 
                    $borderPolygonArray, 
                    $text['font']->background,
                    true
                );
            }
            else
            {
                // Always draw full tranparent background polygon as fallback, 
                // to be able to click on complete font space, not only on 
                // the text
                $this->drawPolygon( 
                    $borderPolygonArray, 
                    ezcGraphColor::fromHex( '#FFFFFFFF' ),
                    true
                );
            }

            if ( $text['font']->border !== false )
            {
                $this->drawPolygon( 
                    $borderPolygonArray, 
                    $text['font']->border,
                    false,
                    $text['font']->borderWidth
                );
            }
            $this->elements = $elementsRoot;

            // Bottom line for SVG fonts is lifted a bit
            $text['position']->y += $size * .85;

            // Render text with evaluated font size
            foreach ( $text['text'] as $line )
            {
                $string = implode( ' ', $line );

                switch ( true )
                {
                    case ( $text['align'] & ezcGraph::LEFT ):
                        $position = new ezcGraphCoordinate(
                            $text['position']->x, 
                            $text['position']->y + $yOffset
                        );
                        break;
                    case ( $text['align'] & ezcGraph::RIGHT ):
                        $position = new ezcGraphCoordinate(
                            $text['position']->x + ( $text['width'] - $this->getTextBoundings( $size, $text['font'], $string )->width ),
                            $text['position']->y + $yOffset
                        );
                        break;
                    case ( $text['align'] & ezcGraph::CENTER ):
                        $position = new ezcGraphCoordinate(
                            $text['position']->x + ( ( $text['width'] - $this->getTextBoundings( $size, $text['font'], $string )->width ) / 2 ),
                            $text['position']->y + $yOffset
                        );
                        break;
                }

                // Optionally draw text shadow
                if ( $text['font']->textShadow === true )
                {
                    $textNode = $this->dom->createElement( 'text', $this->encode( $string ) );
                    $textNode->setAttribute( 'id', $text['id'] . '_shadow' );
                    $textNode->setAttribute( 'x', sprintf( '%.4F', $position->x + $this->options->graphOffset->x + $text['font']->textShadowOffset ) );
                    $textNode->setAttribute( 'text-length', sprintf( '%.4Fpx', $this->getTextBoundings( $size, $text['font'], $string )->width ) );
                    $textNode->setAttribute( 'y', sprintf( '%.4F', $position->y + $this->options->graphOffset->y + $text['font']->textShadowOffset ) );
                    $textNode->setAttribute( 
                        'style', 
                        sprintf(
                            'font-size: %dpx; font-family: \'%s\'; fill: #%02x%02x%02x; fill-opacity: %.2F; stroke: none;',
                            $size,
                            $text['font']->name,
                            $text['font']->textShadowColor->red,
                            $text['font']->textShadowColor->green,
                            $text['font']->textShadowColor->blue,
                            1 - ( $text['font']->textShadowColor->alpha / 255 )
                        )
                    );
                    $group->appendChild( $textNode );
                }
                
                // Finally draw text
                $textNode = $this->dom->createElement( 'text', $this->encode( $string ) );
                $textNode->setAttribute( 'id', $text['id'] . '_text' );
                $textNode->setAttribute( 'x', sprintf( '%.4F', $position->x + $this->options->graphOffset->x ) );
                $textNode->setAttribute( 'text-length', sprintf( '%.4Fpx', $this->getTextBoundings( $size, $text['font'], $string )->width ) );
                $textNode->setAttribute( 'y', sprintf( '%.4F', $position->y + $this->options->graphOffset->y ) );
                $textNode->setAttribute( 
                    'style', 
                    sprintf(
                        'font-size: %dpx; font-family: \'%s\'; fill: #%02x%02x%02x; fill-opacity: %.2F; stroke: none;',
                        $size,
                        $text['font']->name,
                        $text['font']->color->red,
                        $text['font']->color->green,
                        $text['font']->color->blue,
                        1 - ( $text['font']->color->alpha / 255 )
                    )
                );
                $group->appendChild( $textNode );

                $text['position']->y += $size + $size * $this->options->lineSpacing;
            }
        }
    }

    /**
     * Draws a sector of cirlce
     * 
     * @param ezcGraphCoordinate $center Center of circle
     * @param mixed $width Width
     * @param mixed $height Height
     * @param mixed $startAngle Start angle of circle sector
     * @param mixed $endAngle End angle of circle sector
     * @param ezcGraphColor $color Color
     * @param mixed $filled Filled;
     * @return void
     */
    public function drawCircleSector( ezcGraphCoordinate $center, $width, $height, $startAngle, $endAngle, ezcGraphColor $color, $filled = true )
    {
        $this->createDocument();  

        // Normalize angles
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }
        
        if ( ( $endAngle - $startAngle ) >= 360 )
        {
            return $this->drawCircle( $center, $width, $height, $color, $filled );
        }

        // We need the radius
        $width /= 2;
        $height /= 2;

        // Apply offset to copy of center coordinate
        $center = clone $center;
        $center->x += $this->options->graphOffset->x;
        $center->y += $this->options->graphOffset->y;

        if ( $filled )
        {
            $Xstart = $center->x + $width * cos( -deg2rad( $startAngle ) );
            $Ystart = $center->y + $height * sin( deg2rad( $startAngle ) );
            $Xend = $center->x + $width * cos( ( -deg2rad( $endAngle ) ) );
            $Yend = $center->y + $height * sin( ( deg2rad( $endAngle ) ) );

            $arc = $this->dom->createElement( 'path' );
            $arc->setAttribute( 'd', sprintf( 'M %.2F,%.2F L %.2F,%.2F A %.2F,%.2F 0 %d,1 %.2F,%.2F z',
                // Middle
                $center->x, $center->y,
                // Startpoint
                $Xstart, $Ystart,
                // Radius
                $width, $height,
                // SVG-Stuff
                ( $endAngle - $startAngle ) > 180,
                // Endpoint
                $Xend, $Yend
                )
            );

            $arc->setAttribute(
                'style', 
                $this->getStyle( $color, $filled, 1 )
            );
            $arc->setAttribute( 'id', $id = ( $this->options->idPrefix . 'CircleSector_' . ++$this->elementID ) );
            $this->elements->appendChild( $arc );
            return $id;
        }
        else
        {
            try
            {
                $reduced = $this->reduceEllipseSize( $center, $width * 2, $height * 2, $startAngle, $endAngle, .5 );
            }
            catch ( ezcGraphReducementFailedException $e )
            {
                return false;
            }

            $arc = $this->dom->createElement( 'path' );
            $arc->setAttribute( 'd', sprintf( 'M %.2F,%.2F L %.2F,%.2F A %.2F,%.2F 0 %d,1 %.2F,%.2F z',
                // Middle
                $reduced['center']->x, $reduced['center']->y,
                // Startpoint
                $reduced['start']->x, $reduced['start']->y,
                // Radius
                $width - .5, $height - .5,
                // SVG-Stuff
                ( $endAngle - $startAngle ) > 180,
                // Endpoint
                $reduced['end']->x, $reduced['end']->y
                )
            );

            $arc->setAttribute(
                'style', 
                $this->getStyle( $color, $filled, 1 )
            );
            
            $arc->setAttribute( 'id', $id = ( $this->options->idPrefix . 'CircleSector_' . ++$this->elementID ) );
            $this->elements->appendChild( $arc );
            
            return $id;
        }
    }

    /**
     * Draws a circular arc
     * 
     * @param ezcGraphCoordinate $center Center of ellipse
     * @param integer $width Width of ellipse
     * @param integer $height Height of ellipse
     * @param integer $size Height of border
     * @param float $startAngle Starting angle of circle sector
     * @param float $endAngle Ending angle of circle sector
     * @param ezcGraphColor $color Color of Border
     * @param bool $filled
     * @return void
     */
    public function drawCircularArc( ezcGraphCoordinate $center, $width, $height, $size, $startAngle, $endAngle, ezcGraphColor $color, $filled = true )
    {
        $this->createDocument();  

        // Normalize angles
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }
        
        if ( ( $endAngle - $startAngle > 180 ) ||
             ( ( $startAngle % 180 != 0) && ( $endAngle % 180 != 0) && ( ( $startAngle % 360 > 180 ) XOR ( $endAngle % 360 > 180 ) ) ) )
        {
            // Border crosses he 180 degrees border
            $intersection = floor( $endAngle / 180 ) * 180;
            while ( $intersection >= $endAngle )
            {
                $intersection -= 180;
            }

            $this->drawCircularArc( $center, $width, $height, $size, $startAngle, $intersection, $color, $filled );
            $this->drawCircularArc( $center, $width, $height, $size, $intersection, $endAngle, $color, $filled );
            return;
        }

        // We need the radius
        $width /= 2;
        $height /= 2;

        $Xstart = $center->x + $this->options->graphOffset->x + $width * cos( -deg2rad( $startAngle ) );
        $Ystart = $center->y + $this->options->graphOffset->y + $height * sin( deg2rad( $startAngle ) );
        $Xend = $center->x + $this->options->graphOffset->x + $width * cos( ( -deg2rad( $endAngle ) ) );
        $Yend = $center->y + $this->options->graphOffset->y + $height * sin( ( deg2rad( $endAngle ) ) );
        
        if ( $filled === true )
        {
            $arc = $this->dom->createElement( 'path' );
            $arc->setAttribute( 'd', sprintf( 'M %.2F,%.2F A %.2F,%.2F 0 %d,0 %.2F,%.2F L %.2F,%.2F A %.2F,%2F 0 %d,1 %.2F,%.2F z',
                // Endpoint low
                $Xend, $Yend + $size,
                // Radius
                $width, $height,
                // SVG-Stuff
                ( $endAngle - $startAngle ) > 180,
                // Startpoint low
                $Xstart, $Ystart + $size,
                // Startpoint
                $Xstart, $Ystart,
                // Radius
                $width, $height,
                // SVG-Stuff
                ( $endAngle - $startAngle ) > 180,
                // Endpoint
                $Xend, $Yend
                )
            );
        }
        else
        {
            $arc = $this->dom->createElement( 'path' );
            $arc->setAttribute( 'd', sprintf( 'M %.2F,%.2F  A %.2F,%.2F 0 %d,1 %.2F,%.2F',
                // Startpoint
                $Xstart, $Ystart,
                // Radius
                $width, $height,
                // SVG-Stuff
                ( $endAngle - $startAngle ) > 180,
                // Endpoint
                $Xend, $Yend
                )
            );
        }

        $arc->setAttribute(
            'style', 
            $this->getStyle( $color, $filled )
        );

        $arc->setAttribute( 'id', $id = ( $this->options->idPrefix . 'CircularArc_' . ++$this->elementID ) );
        $this->elements->appendChild( $arc );

        if ( ( $this->options->shadeCircularArc !== false ) &&
             $filled )
        {
            $gradient = new ezcGraphLinearGradient(
                new ezcGraphCoordinate(
                    $center->x - $width,
                    $center->y
                ),
                new ezcGraphCoordinate(
                    $center->x + $width,
                    $center->y
                ),
                ezcGraphColor::fromHex( '#FFFFFF' )->transparent( $this->options->shadeCircularArc * 1.5 ),
                ezcGraphColor::fromHex( '#000000' )->transparent( $this->options->shadeCircularArc )
            );

            $arc = $this->dom->createElement( 'path' );
            $arc->setAttribute( 'd', sprintf( 'M %.2F,%.2F A %.2F,%.2F 0 %d,0 %.2F,%.2F L %.2F,%.2F A %.2F,%2F 0 %d,1 %.2F,%.2F z',
                // Endpoint low
                $Xend, $Yend + $size,
                // Radius
                $width, $height,
                // SVG-Stuff
                ( $endAngle - $startAngle ) > 180,
                // Startpoint low
                $Xstart, $Ystart + $size,
                // Startpoint
                $Xstart, $Ystart,
                // Radius
                $width, $height,
                // SVG-Stuff
                ( $endAngle - $startAngle ) > 180,
                // Endpoint
                $Xend, $Yend
                )
            );
        
            $arc->setAttribute(
                'style', 
                $this->getStyle( $gradient, $filled )
            );
            $arc->setAttribute( 'id', $id = ( $this->options->idPrefix . 'CircularArc_' . ++$this->elementID ) );

            $this->elements->appendChild( $arc );
        }

        return $id;
    }

    /**
     * Draw circle 
     * 
     * @param ezcGraphCoordinate $center Center of ellipse
     * @param mixed $width Width of ellipse
     * @param mixed $height height of ellipse
     * @param ezcGraphColor $color Color
     * @param mixed $filled Filled
     * @return void
     */
    public function drawCircle( ezcGraphCoordinate $center, $width, $height, ezcGraphColor $color, $filled = true )
    {
        $this->createDocument();  
        
        $ellipse = $this->dom->createElement( 'ellipse' );
        $ellipse->setAttribute( 'cx', sprintf( '%.4F', $center->x + $this->options->graphOffset->x ) );
        $ellipse->setAttribute( 'cy', sprintf( '%.4F', $center->y + $this->options->graphOffset->y ) );
        $ellipse->setAttribute( 'rx', sprintf( '%.4F', $width / 2 - ( $filled ? 0 : .5 ) ) );
        $ellipse->setAttribute( 'ry', sprintf( '%.4F', $height / 2 - ( $filled ? 0 : .5 ) ) );

        $ellipse->setAttribute(
            'style', 
            $this->getStyle( $color, $filled, 1 )
        );
        
        $ellipse->setAttribute( 'id', $id = ( $this->options->idPrefix . 'Circle_' . ++$this->elementID ) );
        $this->elements->appendChild( $ellipse );

        return $id;
    }

    /**
     * Draw an image 
     *
     * The image will be inlined in the SVG document using data URL scheme. For
     * this the mime type and base64 encoded file content will be merged to 
     * URL.
     * 
     * @param mixed $file Image file
     * @param ezcGraphCoordinate $position Top left position
     * @param mixed $width Width of image in destination image
     * @param mixed $height Height of image in destination image
     * @return void
     */
    public function drawImage( $file, ezcGraphCoordinate $position, $width, $height )
    {
        $this->createDocument();

        $data = getimagesize( $file );
        $image = $this->dom->createElement( 'image' );

        $image->setAttribute( 'x', sprintf( '%.4F', $position->x + $this->options->graphOffset->x ) );
        $image->setAttribute( 'y', sprintf( '%.4F', $position->y + $this->options->graphOffset->y ) );
        $image->setAttribute( 'width', sprintf( '%.4Fpx', $width ) );
        $image->setAttribute( 'height', sprintf( '%.4Fpx', $height ) );
        $image->setAttributeNS( 
            'http://www.w3.org/1999/xlink', 
            'xlink:href', 
            sprintf( 'data:%s;base64,%s',
                $data['mime'],
                base64_encode( file_get_contents( $file ) )
            )
        );

        $this->elements->appendChild( $image );
        $image->setAttribute( 'id', $id = ( $this->options->idPrefix . 'Image_' . ++$this->elementID ) );

        return $id;
    }

    /**
     * Return mime type for current image format
     * 
     * @return string
     */
    public function getMimeType()
    {
        return 'image/svg+xml';
    }

    /**
     * Render image directly to output
     *
     * The method renders the image directly to the standard output. You 
     * normally do not want to use this function, because it makes it harder 
     * to proper cache the generated graphs.
     * 
     * @return void
     */
    public function renderToOutput()
    {
        $this->createDocument();  
        $this->drawAllTexts();

        header( 'Content-Type: ' . $this->getMimeType() );
        echo $this->dom->saveXML();
    }

    /**
     * Finally save image
     * 
     * @param string $file Destination filename
     * @return void
     */
    public function render( $file )
    {
        $this->createDocument();  
        $this->drawAllTexts();

        // Embed used glyphs
        $this->font->addFontToDocument( $this->dom );
        $this->dom->save( $file );
    }

    /**
     * Get resource of rendered result
     *
     * Return the resource of the rendered result. You should not use this
     * method before you called either renderToOutput() or render(), as the
     * image may not be completely rendered until then.
     * 
     * @return DOMDocument
     */
    public function getResource()
    {
        return $this->dom;
    }
}

?>
