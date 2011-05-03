<?php
/**
 * File containing the ezcGraphCairoOODriver class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Extension of the basic driver package to utilize the cairo library.
 *
 * This drivers options are defined in the class 
 * {@link ezcGraphCairoDriverOptions} extending the basic driver options class
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
 *   // Use cairo driver
 *   $graph->driver = new ezcGraphCairoOODriver();
 *   
 *   $graph->render( 400, 200, 'tutorial_driver_cairo.png' );
 * </code>
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphCairoOODriver extends ezcGraphDriver
{
    /**
     * Surface for cairo
     * 
     * @var CairoImageSurface
     */
    protected $surface;

    /**
     * Current cairo context.
     * 
     * @var CairoContext
     */
    protected $context;

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
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        ezcBase::checkDependency( 'Graph', ezcBase::DEP_PHP_EXTENSION, 'cairo' );
        $this->options = new ezcGraphCairoDriverOptions( $options );
    }

    /**
     * Initilize cairo surface
     *
     * Initilize cairo surface from values provided in the options object, if
     * is has not been already initlized.
     * 
     * @return void
     */
    protected function initiliazeSurface()
    {
        // Immediatly exit, if surface already exists
        if ( $this->surface !== null )
        {
            return;
        }

        $this->surface = new CairoImageSurface(
            CairoFormat::ARGB32, 
            $this->options->width, 
            $this->options->height
        );

        $this->context = new CairoContext( $this->surface );
        $this->context->setLineWidth( 1 );
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
        switch ( true )
        {
            case $color instanceof ezcGraphLinearGradient:
                $pattern = new CairoLinearGradient(
                    $color->startPoint->x, $color->startPoint->y,
                    $color->endPoint->x, $color->endPoint->y
                );

                $pattern->addColorStopRgba(
                    0,
                    $color->startColor->red / 255,
                    $color->startColor->green / 255,
                    $color->startColor->blue / 255,
                    1 - $color->startColor->alpha / 255
                );

                $pattern->addColorStopRgba(
                    1,
                    $color->endColor->red / 255,
                    $color->endColor->green / 255,
                    $color->endColor->blue / 255,
                    1 - $color->endColor->alpha / 255
                );

                $this->context->setSource( $pattern );
                $this->context->fill();
                break;

            case $color instanceof ezcGraphRadialGradient:
                $pattern = new CairoRadialGradient(
                    0, 0, 0,
                    0, 0, 1
                );

                $pattern->addColorStopRgba(
                    0,
                    $color->startColor->red / 255,
                    $color->startColor->green / 255,
                    $color->startColor->blue / 255,
                    1 - $color->startColor->alpha / 255
                );

                $pattern->addColorStopRgba(
                    1,
                    $color->endColor->red / 255,
                    $color->endColor->green / 255,
                    $color->endColor->blue / 255,
                    1 - $color->endColor->alpha / 255
                );

                // Scale pattern, and move it to the correct position
                $matrix = CairoMatrix::multiply(
                    $move = CairoMatrix::initTranslate( -$color->center->x, -$color->center->y ),
                    $scale = CairoMatrix::initScale( 1 / $color->width, 1 / $color->height )
                );
                $pattern->setMatrix( $matrix );

                $this->context->setSource( $pattern );
                $this->context->fill();
                break;
            default:
                $this->context->setSourceRgba(
                    $color->red / 255,
                    $color->green / 255,
                    $color->blue / 255,
                    1 - $color->alpha / 255
                );
                break;
        }

        // Set line width
         $this->context->setLineWidth( $thickness );

        // Set requested fill state for context
        if ( $filled )
        {
            $this->context->fillPreserve();
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
        $this->initiliazeSurface();

        $this->context->newPath();

        $lastPoint = end( $points );
        $this->context->moveTo( $lastPoint->x, $lastPoint->y );

        foreach ( $points as $point )
        {
            $this->context->lineTo( $point->x, $point->y );
        }

        $this->context->closePath();

        $this->getStyle( $color, $filled, $thickness );
        $this->context->stroke();

        return $points;
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
        $this->initiliazeSurface();

        $this->context->newPath();

        $this->context->moveTo( $start->x, $start->y );
        $this->context->lineTo( $end->x, $end->y );

        $this->getStyle( $color, false, $thickness );
        $this->context->stroke();

        return array( $start, $end );
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
        $this->context->selectFontFace( $font->name, CairoFontSlant::NORMAL, CairoFontWeight::NORMAL );
        $this->context->setFontSize( $size );
        $extents = $this->context->textExtents( $text );

        return new ezcGraphBoundings(
            0,
            0,
            $extents['width'],
            $extents['height']
        );
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
        $this->initiliazeSurface();

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
            'position' => $textPosition,
            'width' => $width,
            'height' => $height,
            'align' => $align,
            'font' => $this->options->font,
            'rotation' => $rotation,
        );

        return array(
            clone $position,
            new ezcGraphCoordinate( $position->x + $width, $position->y ),
            new ezcGraphCoordinate( $position->x + $width, $position->y + $height ),
            new ezcGraphCoordinate( $position->x, $position->y + $height ),
        );
    }
    
    /**
     * Render text depending of font type and available font extensions
     * 
     * @param string $id 
     * @param string $text 
     * @param string $font 
     * @param ezcGraphColor $color 
     * @param ezcGraphCoordinate $position 
     * @param float $size 
     * @param float $rotation 
     * @return void
     */
    protected function renderText( $text, $font, ezcGraphColor $color, ezcGraphCoordinate $position, $size, $rotation = null )
    {
        $this->context->selectFontFace( $font, CairoFontSlant::NORMAL, CairoFontWeight::NORMAL );
        $this->context->setFontSize( $size );
        
        // Store current state of context
        $this->context->save();
        $this->context->moveTo( 0, 0 );

        if ( $rotation !== null )
        {
            // Move to the center
            $this->context->translate(
                $rotation->getCenter()->x, 
                $rotation->getCenter()->y
            );
            // Rotate around text center
            $this->context->rotate(
                deg2rad( $rotation->getRotation() ) 
            );
            // Center the text
            $this->context->translate(
                $position->x - $rotation->getCenter()->x,
                $position->y - $rotation->getCenter()->y - $size * .15
            );
        } else {
            $this->context->translate(
                $position->x,
                $position->y - $size * .15
            );
        }

        $this->context->newPath();
        $this->getStyle( $color, true );
        $this->context->showText( $text );
        $this->context->stroke();

        // Restore state of context
        $this->context->restore();
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
        $this->initiliazeSurface();

        foreach ( $this->strings as $text )
        {
            $size = $text['font']->minimalUsedFont;

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
                    $boundings = $this->getTextBoundings( $size, $text['font'], $string );
                    if ( ( $width === false) || ( $boundings->width > $width ) )
                    {
                        $width = $boundings->width;
                    }
                }

                switch ( true )
                {
                    case ( $text['align'] & ezcGraph::CENTER ):
                        $xOffset = ( $text['width'] - $width ) / 2;
                        break;
                    case ( $text['align'] & ezcGraph::RIGHT ):
                        $xOffset = $text['width'] - $width;
                        break;
                    case ( $text['align'] & ezcGraph::LEFT ):
                    default:
                        $xOffset = 0;
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

            if ( $text['rotation'] !==  null )
            {
                foreach ( $borderPolygonArray as $nr => $point )
                {
                    $borderPolygonArray[$nr] = $text['rotation']->transformCoordinate( $point );
                }
            }

            if ( $text['font']->background !== false )
            {
                $this->drawPolygon( 
                    $borderPolygonArray, 
                    $text['font']->background,
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

            // Render text with evaluated font size
            $completeString = '';
            foreach ( $text['text'] as $line )
            {
                $string = implode( ' ', $line );
                $completeString .= $string;
                $boundings = $this->getTextBoundings( $size, $text['font'], $string );
                $text['position']->y += $size;

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
                            $text['position']->x + ( $text['width'] - $boundings->width ), 
                            $text['position']->y + $yOffset
                        );
                        break;
                    case ( $text['align'] & ezcGraph::CENTER ):
                        $position = new ezcGraphCoordinate( 
                            $text['position']->x + ( ( $text['width'] - $boundings->width ) / 2 ), 
                            $text['position']->y + $yOffset
                        );
                        break;
                }

                // Optionally draw text shadow
                if ( $text['font']->textShadow === true )
                {
                    $this->renderText( 
                        $string,
                        $text['font']->name, 
                        $text['font']->textShadowColor,
                        new ezcGraphCoordinate(
                            $position->x + $text['font']->textShadowOffset,
                            $position->y + $text['font']->textShadowOffset
                        ),
                        $size,
                        $text['rotation']
                    );
                }
                
                // Finally draw text
                $this->renderText( 
                    $string,
                    $text['font']->name, 
                    $text['font']->color, 
                    $position,
                    $size,
                    $text['rotation']
                );

                $text['position']->y += $size * $this->options->lineSpacing;
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
        $this->initiliazeSurface();

        // Normalize angles
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }
        
        $this->context->save();
        
        // Draw circular arc path
        $this->context->newPath();
        $this->context->translate(
            $center->x,
            $center->y
        );
        $this->context->scale(
            1, $height / $width
        );

        $this->context->moveTo( 0, 0 );
        $this->context->arc(
            0., 0., 
            $width / 2, 
            deg2rad( $startAngle ),
            deg2rad( $endAngle )
        );
        $this->context->lineTo( 0, 0 );

        $this->context->restore();
        $this->getStyle( $color, $filled );
        $this->context->stroke();

        // Create polygon array to return
        $polygonArray = array( $center );
        for ( $angle = $startAngle; $angle < $endAngle; $angle += $this->options->imageMapResolution )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( deg2rad( $angle ) ) * $width ) / 2 ),
                $center->y + 
                    ( ( sin( deg2rad( $angle ) ) * $height ) / 2 )
            );
        }
        $polygonArray[] = new ezcGraphCoordinate(
            $center->x + 
                ( ( cos( deg2rad( $endAngle ) ) * $width ) / 2 ),
            $center->y + 
                ( ( sin( deg2rad( $endAngle ) ) * $height ) / 2 )
        );

        return $polygonArray;
    }

    /**
     * Draws a circular arc consisting of several minor steps on the bounding 
     * lines.
     * 
     * @param ezcGraphCoordinate $center 
     * @param mixed $width 
     * @param mixed $height 
     * @param mixed $size 
     * @param mixed $startAngle 
     * @param mixed $endAngle 
     * @param ezcGraphColor $color 
     * @param bool $filled 
     * @return string Element id
     */
    protected function simulateCircularArc( ezcGraphCoordinate $center, $width, $height, $size, $startAngle, $endAngle, ezcGraphColor $color, $filled )
    {
        for ( 
            $tmpAngle = min( ceil ( $startAngle / 180 ) * 180, $endAngle ); 
            $tmpAngle <= $endAngle; 
            $tmpAngle = min( ceil ( $startAngle / 180 + 1 ) * 180, $endAngle ) )
        {
            $this->context->newPath();
            $this->context->moveTo(
                $center->x + cos( deg2rad( $startAngle ) ) * $width / 2, 
                $center->y + sin( deg2rad( $startAngle ) ) * $height / 2
            );

            // @TODO: Use cairo_curve_to()
            for(
                $angle = $startAngle;
                $angle <= $tmpAngle;
                $angle = min( $angle + $this->options->circleResolution, $tmpAngle ) )
            {
                $this->context->lineTo(
                    $center->x + cos( deg2rad( $angle ) ) * $width / 2, 
                    $center->y + sin( deg2rad( $angle ) ) * $height / 2 + $size
                );

                if ( $angle === $tmpAngle )
                {
                    break;
                }
            }

            for(
                $angle = $tmpAngle;
                $angle >= $startAngle;
                $angle = max( $angle - $this->options->circleResolution, $startAngle ) )
            {
                $this->context->lineTo(
                    $center->x + cos( deg2rad( $angle ) ) * $width / 2, 
                    $center->y + sin( deg2rad( $angle ) ) * $height / 2
                );

                if ( $angle === $startAngle )
                {
                    break;
                }
            }

            $this->context->closePath();
            $this->getStyle( $color, $filled );
            $this->context->stroke( );

            $startAngle = $tmpAngle;
            if ( $tmpAngle === $endAngle ) 
            {
                break;
            }
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
        $this->initiliazeSurface();

        // Normalize angles
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }

        $this->simulateCircularArc( $center, $width, $height, $size, $startAngle, $endAngle, $color, $filled );

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
                ezcGraphColor::fromHex( '#000000' )->transparent( $this->options->shadeCircularArc * 1.5 )
            );
        
            $this->simulateCircularArc( $center, $width, $height, $size, $startAngle, $endAngle, $gradient, $filled );
        }

        // Create polygon array to return
        $polygonArray = array();
        for ( $angle = $startAngle; $angle < $endAngle; $angle += $this->options->imageMapResolution )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( deg2rad( $angle ) ) * $width ) / 2 ),
                $center->y + 
                    ( ( sin( deg2rad( $angle ) ) * $height ) / 2 )
            );
        }
        $polygonArray[] = new ezcGraphCoordinate(
            $center->x + 
                ( ( cos( deg2rad( $endAngle ) ) * $width ) / 2 ),
            $center->y + 
                ( ( sin( deg2rad( $endAngle ) ) * $height ) / 2 )
        );

        for ( $angle = $endAngle; $angle > $startAngle; $angle -= $this->options->imageMapResolution )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( deg2rad( $angle ) ) * $width ) / 2 ) + $size,
                $center->y + 
                    ( ( sin( deg2rad( $angle ) ) * $height ) / 2 )
            );
        }
        $polygonArray[] = new ezcGraphCoordinate(
            $center->x + 
                ( ( cos( deg2rad( $startAngle ) ) * $width ) / 2 ) + $size,
            $center->y + 
                ( ( sin( deg2rad( $startAngle ) ) * $height ) / 2 )
        );

        return $polygonArray;
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
        $this->initiliazeSurface();
        
        $this->context->save();
        
        // Draw circular arc path
        $this->context->newPath();
        $this->context->translate( 
            $center->x,
            $center->y
        );
        $this->context->scale( 
            1, $height / $width
        );

        $this->context->arc( 
            0., 0., 
            $width / 2, 
            0, 2 * M_PI
        );

        $this->context->restore();
        $this->getStyle( $color, $filled );
        $this->context->stroke();

        // Create polygon array to return
        $polygonArray = array();
        for ( $angle = 0; $angle < ( 2 * M_PI ); $angle += deg2rad( $this->options->imageMapResolution ) )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( $angle ) * $width ) / 2 ),
                $center->y + 
                    ( ( sin( $angle ) * $height ) / 2 )
            );
        }

        return $polygonArray;
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
        $this->initiliazeSurface();

        // Ensure given bitmap is a PNG image
        $data = getimagesize( $file );
        if ( $data[2] !== IMAGETYPE_PNG )
        {
            throw new Exception( 'Cairo only has support for PNGs.' );
        }

        // Create new surface from given bitmap
        $imageSurface = CairoImageSurface::createFromPng( $file );

        // Create pattern from source image to be able to transform it
        $pattern = new CairoSurfacePattern( $imageSurface );

        // Scale pattern to defined dimensions and move it to its destination position
        $matrix = CairoMatrix::multiply(
            $move = CairoMatrix::initTranslate( -$position->x, -$position->y ),
            $scale = CairoMatrix::initScale( $data[0] / $width, $data[1] / $height )
        );
        $pattern->setMatrix( $matrix );

        // Merge surfaces
        $this->context->setSource( $pattern );
        $this->context->rectangle( $position->x, $position->y, $width, $height );
        $this->context->fill();
    }

    /**
     * Return mime type for current image format
     * 
     * @return string
     */
    public function getMimeType()
    {
        return 'image/png';
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
        $this->drawAllTexts();

        header( 'Content-Type: ' . $this->getMimeType() );

        // Write to tmp file, echo and remove tmp file again.
        $fileName = tempnam( '/tmp', 'ezc' );

        // cairo_surface_write_to_png( $this->surface, $file );
        $this->surface->writeToPng( $fileName );
        $contents = file_get_contents( $fileName );
        unlink( $fileName );

        // Directly echo contents
        echo $contents;
    }

    /**
     * Finally save image
     * 
     * @param string $file Destination filename
     * @return void
     */
    public function render( $file )
    {
        $this->drawAllTexts();
        $this->surface->writeToPng( $file );
    }

    /**
     * Get resource of rendered result
     *
     * Return the resource of the rendered result. You should not use this
     * method before you called either renderToOutput() or render(), as the
     * image may not be completely rendered until then.
     *
     * This method returns an array, containing the surface and the context in
     * a structure like:
     * <code>
     *  array(
     *      'surface' => resource,
     *      'context' => resource,
     *  )
     * </code>
     * 
     * @return array
     */
    public function getResource()
    {
        return array( 
            'surface' => $this->surface,
            'context' => $this->context,
        );
    }
}

?>
