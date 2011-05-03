<?php
/**
 * File containing the ezcGraphSVGDriver class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */
/**
 * Simple output driver for debuggin purposes. Just outputs shapes as text on
 * CLI.
 *
 * @version 1.5
 * @package Graph
 * @access private
 */

class ezcGraphVerboseDriver extends ezcGraphDriver
{
    /**
     * Number of call on driver
     * 
     * @var int
     */
    protected $call = 0;

    /**
     * Constructor
     * 
     * @param array $options 
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->options = new ezcGraphSvgDriverOptions( $options );
        echo "\n";
    }

    /**
     * Draws a single polygon 
     * 
     * @param array $points 
     * @param ezcGraphColor $color 
     * @param bool $filled 
     * @param float $thickness
     * @return void
     */
    public function drawPolygon( array $points, ezcGraphColor $color, $filled = true, $thickness = 1. )
    {
        $pointString = '';
        foreach ( $points as $point )
        {
            $pointString .= sprintf( "\t( %.2f, %.2f )\n", $point->x, $point->y );
        }

        printf( "% 4d: Draw %spolygon:\n%s", 
            $this->call++,
            ( $filled ? 'filled ' : '' ),
            $pointString
        );
    }
    
    /**
     * Draws a single line
     * 
     * @param ezcGraphCoordinate $start 
     * @param ezcGraphCoordinate $end 
     * @param ezcGraphColor $color 
     * @param float $thickness
     * @return void
     */
    public function drawLine( ezcGraphCoordinate $start, ezcGraphCoordinate $end, ezcGraphColor $color, $thickness = 1. )
    {
        printf( "% 4d: Draw line from ( %.2f, %.2f ) to ( %.2f, %.2f ) with thickness %d.\n",
            $this->call++,
            $start->x,
            $start->y,
            $end->x,
            $end->y,
            $thickness
        );
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
        return null;
    }

    /**
     * Wrties text in a box of desired size
     * 
     * @param mixed $string 
     * @param ezcGraphCoordinate $position 
     * @param mixed $width 
     * @param mixed $height 
     * @param int $align 
     * @param ezcGraphRotation $rotation
     * @return void
     */
    public function drawTextBox( $string, ezcGraphCoordinate $position, $width, $height, $align, ezcGraphRotation $rotation = null )
    {
        printf( "% 4d: Draw text '%s' at ( %.2f, %.2f ) with dimensions ( %d, %d ) and alignement %d.\n",
            $this->call++,
            $string,
            $position->x,
            $position->y,
            $width,
            $height,
            $align
        );
    }
    /**
     * Draws a sector of cirlce
     * 
     * @param ezcGraphCoordinate $center 
     * @param mixed $width
     * @param mixed $height
     * @param mixed $startAngle 
     * @param mixed $endAngle 
     * @param ezcGraphColor $color 
     * @param bool $filled
     * @return void
     */
    public function drawCircleSector( ezcGraphCoordinate $center, $width, $height, $startAngle, $endAngle, ezcGraphColor $color, $filled = true )
    {
        printf( "% 4d: Draw %scicle sector at ( %.2f, %.2f ) with dimensions ( %d, %d ) from %.2f to %.2f.\n",
            $this->call++,
            ( $filled ? 'filled ' : '' ),
            $center->x,
            $center->y,
            $width,
            $height,
            $startAngle,
            $endAngle
        );
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
        printf( "% 4d: Draw circular arc at ( %.2f, %.2f ) with dimensions ( %d, %d ) and size %.2f from %.2f to %.2f.\n",
            $this->call++,
            $center->x,
            $center->y,
            $width,
            $height,
            $size,
            $startAngle,
            $endAngle
        );
    }

    /**
     * Draws a circle
     * 
     * @param ezcGraphCoordinate $center 
     * @param mixed $width
     * @param mixed $height
     * @param ezcGraphColor $color
     * @param bool $filled
     *
     * @return void
     */
    public function drawCircle( ezcGraphCoordinate $center, $width, $height, ezcGraphColor $color, $filled = true )
    {
        printf( "% 4d: Draw %scircle at ( %.2f, %.2f ) with dimensions ( %d, %d ).\n",
            $this->call++,
            ( $filled ? 'filled ' : '' ),
            $center->x,
            $center->y,
            $width,
            $height
        );
    }

    /**
     * Draws a imagemap of desired size
     * 
     * @param mixed $file 
     * @param ezcGraphCoordinate $position 
     * @param mixed $width 
     * @param mixed $height 
     * @return void
     */
    public function drawImage( $file, ezcGraphCoordinate $position, $width, $height )
    {
        printf( "% 4d: Draw image '%s' at ( %.2f, %.2f ) with dimensions ( %d, %d ).\n",
            $this->call++,
            $file,
            $position->x,
            $position->y,
            $width,
            $height
        );
    }

    /**
     * Return mime type for current image format
     * 
     * @return string
     */
    public function getMimeType()
    {
        return 'text/plain';
    }

    /**
     * Finally save image
     * 
     * @param mixed $file 
     * @return void
     */
    public function render ( $file )
    {
        printf( "Render image.\n" );
    }
}

?>
