<?php
/**
 * File containing the ezcGraphFontRenderingException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when it is not possible to render a string beacause of 
 * minimum font size in the desinated bounding box.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphFontRenderingException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param string $string
     * @param float $size
     * @param int $width
     * @param int $height
     * @return void
     * @ignore
     */
    public function __construct( $string, $size, $width, $height )
    {
        parent::__construct( "Could not fit string '{$string}' with font size '{$size}' in box '{$width} * {$height}'.
Possible solutions to solve this problem:
    - Decrease the amount of steps on the axis.
    - Increase the size of the chart.
    - Decrease the minimum font size.
    - Use a font which consumes less space for each character." );
    }
}

?>
