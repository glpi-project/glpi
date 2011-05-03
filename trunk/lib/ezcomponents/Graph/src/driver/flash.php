<?php
/**
 * File containing the ezcGraphFlashDriver class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Driver to create Flash4 (SWF) files as graph output. The options of this
 * class are defined in The options of this class are defined in the option
 * class {@link ezcGraphFlashDriverOptions} extending the basic
 * {@link ezcGraphDriverOptions}.
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->title = 'Access statistics';
 *   $graph->legend = false;
 *   
 *   $graph->driver = new ezcGraphFlashDriver();
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
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphFlashDriver extends ezcGraphDriver
{
    /**
     * Flash movie
     * 
     * @var SWFMovie
     */
    protected $movie;

    /**
     * Unique element id
     * 
     * @var int
     */
    protected $id = 1;

    /**
     * Array with strings to draw later
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
        ezcBase::checkDependency( 'Graph', ezcBase::DEP_PHP_EXTENSION, 'ming' );
        $this->options = new ezcGraphFlashDriverOptions( $options );
    }

    /**
     * Returns unique movie object as a parent canvas for all swf objects.
     * 
     * @return SWFMovie
     */
    public function getDocument()
    {
        if ( $this->movie === null )
        {
            ming_setscale( 1.0 );
            $this->movie = new SWFMovie();
            $this->movie->setDimension( $this->modifyCoordinate( $this->options->width ), $this->modifyCoordinate( $this->options->height ) );
            $this->movie->setRate( 1 );
            $this->movie->setBackground( 255, 255, 255 );
        }

        return $this->movie;
    }

    /**
     * Set the fill and line properties for a SWWFShape according to the 
     * given parameters.
     * 
     * @param SWFShape $shape
     * @param ezcGraphColor $color 
     * @param mixed $thickness 
     * @param mixed $filled 
     * @return void
     */
    protected function setShapeColor( SWFShape $shape, ezcGraphColor $color, $thickness, $filled )
    {
        if ( $filled )
        {
            switch ( true )
            {
                case ( $color instanceof ezcGraphLinearGradient ):
                    $gradient = new SWFGradient();
                    $gradient->addEntry( 
                        0, 
                        $color->startColor->red, 
                        $color->startColor->green, 
                        $color->startColor->blue, 
                        255 - $color->startColor->alpha 
                    );
                    $gradient->addEntry( 
                        1, 
                        $color->endColor->red, 
                        $color->endColor->green, 
                        $color->endColor->blue, 
                        255 - $color->endColor->alpha 
                    );

                    $fill = $shape->addFill( $gradient, SWFFILL_LINEAR_GRADIENT );

                    // Calculate desired length of gradient
                    $length = sqrt( 
                        pow( $color->endPoint->x - $color->startPoint->x, 2 ) + 
                        pow( $color->endPoint->y - $color->startPoint->y, 2 ) 
                    ); 

                    $fill->scaleTo( $this->modifyCoordinate( $length ) / 32768 , $this->modifyCoordinate( $length ) / 32768 );
                    $fill->rotateTo( 
                        rad2deg( asin( 
                            ( $color->endPoint->x - $color->startPoint->x ) / $length
                        ) + 180 )
                    );
                    $fill->moveTo( 
                        $this->modifyCoordinate( 
                            ( $color->startPoint->x + $color->endPoint->x ) / 2 
                        ), 
                        $this->modifyCoordinate( 
                            ( $color->startPoint->y + $color->endPoint->y ) / 2 
                        )
                    );

                    $shape->setLeftFill( $fill );
                    break;
                case ( $color instanceof ezcGraphRadialGradient ):
                    $gradient = new SWFGradient();
                    $gradient->addEntry( 
                        0, 
                        $color->startColor->red, 
                        $color->startColor->green, 
                        $color->startColor->blue, 
                        255 - $color->startColor->alpha 
                    );
                    $gradient->addEntry( 
                        1, 
                        $color->endColor->red, 
                        $color->endColor->green, 
                        $color->endColor->blue, 
                        255 - $color->endColor->alpha 
                    );

                    $fill = $shape->addFill( $gradient, SWFFILL_RADIAL_GRADIENT );

                    $fill->scaleTo( $this->modifyCoordinate( $color->width ) / 32768, $this->modifyCoordinate( $color->height ) / 32768 );
                    $fill->moveTo( $this->modifyCoordinate( $color->center->x ), $this->modifyCoordinate( $color->center->y ) );

                    $shape->setLeftFill( $fill );
                    break;
                default:
                    $fill = $shape->addFill( $color->red, $color->green, $color->blue, 255 - $color->alpha );
                    $shape->setLeftFill( $fill );
                    break;
            }
        }
        else
        {
            $shape->setLine( $this->modifyCoordinate( $thickness ), $color->red, $color->green, $color->blue, 255 - $color->alpha );
        }
    }

    /**
     * Modifies a coordinate value, as flash usally uses twips instead of 
     * pixels for a higher solution, as it only accepts integer values.
     * 
     * @param float $pointValue 
     * @return float
     */
    protected function modifyCoordinate( $pointValue )
    {
        return $pointValue * 10;
    }

    /**
     * Demodifies a coordinate value, as flash usally uses twips instead of 
     * pixels for a higher solution, as it only accepts integer values.
     * 
     * @param float $pointValue 
     * @return float
     */
    protected function deModifyCoordinate( $pointValue )
    {
        return $pointValue / 10;
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
        $movie = $this->getDocument();

        if ( !$filled )
        {
            // The middle of the border is on the outline of a polygon in ming, 
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

        $shape = new SWFShape();

        $this->setShapeColor( $shape, $color, $thickness, $filled );

        $lastPoint = end( $points );
        $shape->movePenTo( $this->modifyCoordinate( $lastPoint->x ), $this->modifyCoordinate( $lastPoint->y ) );

        foreach ( $points as $point )
        {
            $shape->drawLineTo( $this->modifyCoordinate( $point->x ), $this->modifyCoordinate( $point->y ) );
        }

        $object = $movie->add( $shape );
        $object->setName( $id = 'ezcGraphPolygon_' . $this->id++ );

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
        $movie = $this->getDocument();

        $shape = new SWFShape();

        $this->setShapeColor( $shape, $color, $thickness, false );

        $shape->movePenTo( $this->modifyCoordinate( $start->x ), $this->modifyCoordinate( $start->y ) );
        $shape->drawLineTo( $this->modifyCoordinate( $end->x ), $this->modifyCoordinate( $end->y ) );

        $object = $movie->add( $shape );
        $object->setName( $id = 'ezcGraphLine_' . $this->id++ );

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
        $t = new SWFText();
        $t->setFont( new SWFFont( $font->path ) );
        $t->setHeight( $size );

        $boundings = new ezcGraphBoundings( 0, 0, $t->getWidth( $text ), $size );
        
        return $boundings;
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

        $width = $this->modifyCoordinate( $width - $padding * 2 );
        $height = $this->modifyCoordinate( $height - $padding * 2 );
        $position = new ezcGraphCoordinate(
            $this->modifyCoordinate( $position->x + $padding ),
            $this->modifyCoordinate( $position->y + $padding )
        );

        // Try to get a font size for the text to fit into the box
        $maxSize = $this->modifyCoordinate( min( $height, $this->options->font->maxFontSize ) );
        $minSize = $this->modifyCoordinate( $this->options->font->minFontSize );
        $result = false;
        for ( $size = $maxSize; $size >= $minSize; )
        {
            $result = $this->testFitStringInTextBox( $string, $position, $width, $height, $size );
            if ( is_array( $result ) )
            {
                break;
            }
            $size = $this->deModifyCoordinate( $size );
            $size = $this->modifyCoordinate( floor( ( $newsize = $size * ( $result ) ) >= $size ? $size - 1 : $newsize ) );
        }

        if ( !is_array( $result ) )
        {
            if ( ( $height >= $this->options->font->minFontSize ) &&
                 ( $this->options->autoShortenString ) )
            {
                $result = $this->tryFitShortenedString( $string, $position, $width, $height, $size = $this->modifyCoordinate( $this->options->font->minFontSize ) );
            } 
            else
            {
                throw new ezcGraphFontRenderingException( $string, $this->options->font->minFontSize, $width, $height );
            }
        }


        $this->options->font->minimalUsedFont = $this->deModifyCoordinate( $size );

        $this->strings[] = array(
            'text' => $result,
            'id' => $id = 'ezcGraphTextBox_' . $this->id++,
            'position' => $position,
            'width' => $width,
            'height' => $height,
            'align' => $align,
            'font' => $this->options->font,
            'rotation' => $rotation,
        );

        return $id;
    }
    
    /**
     * Render text depending of font type and available font extensions
     * 
     * @param string $id 
     * @param string $text 
     * @param string $chars 
     * @param int $type 
     * @param string $path 
     * @param ezcGraphColor $color 
     * @param ezcGraphCoordinate $position 
     * @param float $size 
     * @param float $rotation 
     * @return void
     */
    protected function renderText( $id, $text, $chars, $type, $path, ezcGraphColor $color, ezcGraphCoordinate $position, $size, $rotation = null )
    {
        $movie = $this->getDocument();

        $tb = new SWFTextField( SWFTEXTFIELD_NOEDIT );
        $tb->setFont( new SWFFont( $path ) );
        $tb->setHeight( $size );
        $tb->setColor( $color->red, $color->green, $color->blue, 255 - $color->alpha );
        $tb->addString( $text );
        $tb->addChars( $chars );

        $object = $movie->add( $tb );
        $object->rotate(
            ( $rotation !== null ? -$rotation->getRotation() : 0 )
        );
        $object->moveTo( 
            $position->x +
                ( $rotation === null ? 0 : $this->modifyCoordinate( $rotation->get( 0, 2 ) ) ),
            $position->y -
                $size * ( 1 + $this->options->lineSpacing ) +
                ( $rotation === null ? 0 : $this->modifyCoordinate( $rotation->get( 1, 2 ) ) )
        );
        $object->setName( $id );
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
        // Iterate over all strings to collect used chars per font
        $chars = array();
        foreach ( $this->strings as $text )
        {
            $completeString = '';
            foreach ( $text['text'] as $line )
            {
                $completeString .= implode( ' ', $line );
            }

            // Collect chars for each font
            if ( !isset( $chars[$text['font']->path] ) )
            {
                $chars[$text['font']->path] = $completeString;
            }
            else
            {
                $chars[$text['font']->path] .= $completeString;
            }
        }

        foreach ( $this->strings as $text )
        {
            $size = $this->modifyCoordinate( $text['font']->minimalUsedFont );

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
                        $this->deModifyCoordinate( $text['position']->x - $padding + $xOffset ),
                        $this->deModifyCoordinate( $text['position']->y - $padding + $yOffset )
                    ),
                    new ezcGraphCoordinate(
                        $this->deModifyCoordinate( $text['position']->x + $padding * 2 + $xOffset + $width ),
                        $this->deModifyCoordinate( $text['position']->y - $padding + $yOffset )
                    ),
                    new ezcGraphCoordinate(
                        $this->deModifyCoordinate( $text['position']->x + $padding * 2 + $xOffset + $width ),
                        $this->deModifyCoordinate( $text['position']->y + $padding * 2 + $yOffset + $completeHeight )
                    ),
                    new ezcGraphCoordinate(
                        $this->deModifyCoordinate( $text['position']->x - $padding + $xOffset ),
                        $this->deModifyCoordinate( $text['position']->y + $padding * 2 + $yOffset + $completeHeight )
                    ),
                );
            }
            else
            {
                $borderPolygonArray = array(
                    new ezcGraphCoordinate(
                        $this->deModifyCoordinate( $text['position']->x - $padding ),
                        $this->deModifyCoordinate( $text['position']->y - $padding )
                    ),
                    new ezcGraphCoordinate(
                        $this->deModifyCoordinate( $text['position']->x + $padding * 2 + $text['width'] ),
                        $this->deModifyCoordinate( $text['position']->y - $padding )
                    ),
                    new ezcGraphCoordinate(
                        $this->deModifyCoordinate( $text['position']->x + $padding * 2 + $text['width'] ),
                        $this->deModifyCoordinate( $text['position']->y + $padding * 2 + $text['height'] )
                    ),
                    new ezcGraphCoordinate(
                        $this->deModifyCoordinate( $text['position']->x - $padding ),
                        $this->deModifyCoordinate( $text['position']->y + $padding * 2 + $text['height'] )
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
                        $text['id'],
                        $string,
                        $chars[$text['font']->path],
                        $text['font']->type, 
                        $text['font']->path, 
                        $text['font']->textShadowColor,
                        new ezcGraphCoordinate(
                            $position->x + $this->modifyCoordinate( $text['font']->textShadowOffset ),
                            $position->y + $this->modifyCoordinate( $text['font']->textShadowOffset )
                        ),
                        $size,
                        $text['rotation']
                    );
                }
                
                // Finally draw text
                $this->renderText( 
                    $text['id'],
                    $string,
                    $chars[$text['font']->path],
                    $text['font']->type, 
                    $text['font']->path, 
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
     * @param mixed $filled Filled
     * @return void
     */
    public function drawCircleSector( ezcGraphCoordinate $center, $width, $height, $startAngle, $endAngle, ezcGraphColor $color, $filled = true )
    {
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }

        $movie = $this->getDocument();

        $shape = new SWFShape();
        $this->setShapeColor( $shape, $color, 1, $filled );

        if ( !$filled )
        {
            try
            {
                $reduced = $this->reduceEllipseSize( $center, $width, $height, $startAngle, $endAngle, .5 );
            }
            catch ( ezcGraphReducementFailedException $e )
            {
                return false;
            }

            $startAngle = $reduced['startAngle'];
            $endAngle = $reduced['endAngle'];

            $width -= 1;
            $height -= 1;
        }

        $shape->movePenTo( $this->modifyCoordinate( $center->x ), $this->modifyCoordinate( $center->y ) );

        // @TODO: User SWFShape::curveTo
        for(
            $angle = $startAngle;
            $angle <= $endAngle;
            $angle = min( $angle + $this->options->circleResolution, $endAngle ) )
        {
            $shape->drawLineTo( 
                $this->modifyCoordinate( $center->x + cos( deg2rad( $angle ) ) * $width / 2 ), 
                $this->modifyCoordinate( $center->y + sin( deg2rad( $angle ) ) * $height / 2 )
            );

            if ( $angle === $endAngle )
            {
                break;
            }
        }

        $shape->drawLineTo( 
            $this->modifyCoordinate( $center->x ), 
            $this->modifyCoordinate( $center->y ) 
        );

        $object = $movie->add( $shape );
        $object->setName( $id = 'ezcGraphCircleSector_' . $this->id++ );

        return $id;
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
        $movie = $this->getDocument();
        $id = 'ezcGraphCircularArc_' . $this->id++;

        for ( 
            $tmpAngle = min( ceil ( $startAngle / 180 ) * 180, $endAngle ); 
            $tmpAngle <= $endAngle; 
            $tmpAngle = min( ceil ( $startAngle / 180 + 1 ) * 180, $endAngle ) )
        {
            $shape = new SWFShape();
            $this->setShapeColor( $shape, $color, 1, $filled );

            $shape->movePenTo( 
                $this->modifyCoordinate( $center->x + cos( deg2rad( $startAngle ) ) * $width / 2 ), 
                $this->modifyCoordinate( $center->y + sin( deg2rad( $startAngle ) ) * $height / 2 )
            );

            // @TODO: Use SWFShape::curveTo
            for(
                $angle = $startAngle;
                $angle <= $tmpAngle;
                $angle = min( $angle + $this->options->circleResolution, $tmpAngle ) )
            {
                $shape->drawLineTo( 
                    $this->modifyCoordinate( $center->x + cos( deg2rad( $angle ) ) * $width / 2 ), 
                    $this->modifyCoordinate( $center->y + sin( deg2rad( $angle ) ) * $height / 2 + $size )
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
                $shape->drawLineTo( 
                    $this->modifyCoordinate( $center->x + cos( deg2rad( $angle ) ) * $width / 2 ), 
                    $this->modifyCoordinate( $center->y + sin( deg2rad( $angle ) ) * $height / 2 )
                );

                if ( $angle === $startAngle )
                {
                    break;
                }
            }

            $object = $movie->add( $shape );
            $object->setName( $id );

            $startAngle = $tmpAngle;
            if ( $tmpAngle === $endAngle ) 
            {
                break;
            }
        }

        return $id;
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
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }

        $id = $this->simulateCircularArc( $center, $width, $height, $size, $startAngle, $endAngle, $color, $filled );

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
        $movie = $this->getDocument();

        $shape = new SWFShape();
        $this->setShapeColor( $shape, $color, 1, $filled );

        // Reduce size
        if ( !$filled )
        {
            $width -= 1;
            $height -= 1;
        }

        $shape->movePenTo( 
            $this->modifyCoordinate( $center->x + $width / 2 ), 
            $this->modifyCoordinate( $center->y )
        );

        // @TODO: User SWFShape::curveTo
        for ( $angle = $this->options->circleResolution; $angle < 360; $angle += $this->options->circleResolution )
        {
            $shape->drawLineTo( 
                $this->modifyCoordinate( $center->x + cos( deg2rad( $angle ) ) * $width / 2 ), 
                $this->modifyCoordinate( $center->y + sin( deg2rad( $angle ) ) * $height / 2 )
            );
        }

        $shape->drawLineTo( 
            $this->modifyCoordinate( $center->x + $width / 2 ), 
            $this->modifyCoordinate( $center->y )
        );

        $object = $movie->add( $shape );
        $object->setName( $id = 'ezcGraphCircle_' . $this->id++ );

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
     * @param float $width Width of image in destination image
     * @param float $height Height of image in destination image
     * @return void
     */
    public function drawImage( $file, ezcGraphCoordinate $position, $width, $height )
    {
        $movie = $this->getDocument();

        $imageData = getimagesize( $file );
        if ( ( $imageData[2] !== IMAGETYPE_JPEG ) && ( $imageData[2] !== IMAGETYPE_PNG ) )
        {
            throw new ezcGraphFlashBitmapTypeException( $imageData[2] );
        }

        // Try to create a new SWFBitmap object from provided file
        $bitmap = new SWFBitmap( fopen( $file, 'rb' ) );

        // Add the image to the movie
        $object = $this->movie->add( $bitmap );

        // Image size is calculated on the base of a tick size of 20, so
        // that we need to transform this, to our tick size.
        $factor = $this->modifyCoordinate( 1 ) / 20;
        $object->scale( $factor, $factor );

        // Scale by ratio of requested and original image size
        $object->scale(
            $width / $imageData[0],
            $height / $imageData[1]
        );

        // Move object to the right position
        $object->moveTo( 
            $this->modifyCoordinate( $position->x ),
            $this->modifyCoordinate( $position->y )
        );

        // Create, set and return unique ID
        $object->setName( $id = 'ezcGraphImage_'. $this->id++ );
        return $id;
    }

    /**
     * Return mime type for current image format
     * 
     * @return string
     */
    public function getMimeType()
    {
        return 'application/x-shockwave-flash';
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
        $movie = $this->getDocument();
        $movie->save( $file, $this->options->compression );
    }

    /**
     * Get resource of rendered result
     *
     * Return the resource of the rendered result. You should not use this
     * method before you called either renderToOutput() or render(), as the
     * image may not be completely rendered until then.
     * 
     * @return SWFMovie
     */
    public function getResource()
    {
        return $this->movie;
    }
}

?>
