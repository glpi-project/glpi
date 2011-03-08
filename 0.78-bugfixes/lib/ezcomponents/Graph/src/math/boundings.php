<?php
/**
 * File containing the ezcGraphBoundings class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */
/**
 * Provides a class representing boundings in a cartesian coordinate system.
 *
 * Currently only works with plane rectangular boundings, should be enhanced by
 * rotated rectangular boundings.
 *
 * This class is only used internally to represent space which is used by
 * certain obejcts on the drawing plane, or which free space is still
 * available.
 *
 * @version 1.5
 * @package Graph
 * @access private
 */
class ezcGraphBoundings
{
    /**
     * Top left x coordinate 
     * 
     * @var float
     */
    public $x0 = 0;

    /**
     * Top left y coordinate 
     * 
     * @var float
     */
    public $y0 = 0;
    
    /**
     * Bottom right x coordinate 
     * 
     * @var float
     */
    public $x1 = false;

    /**
     * Bottom right y coordinate 
     * 
     * @var float
     */
    public $y1 = false;
    
    /**
     * Constructor
     * 
     * @param float $x0 Top left x coordinate
     * @param float $y0 Top left y coordinate
     * @param float $x1 Bottom right x coordinate
     * @param float $y1 Bottom right y coordinate
     * @return ezcGraphBoundings
     */
    public function __construct( $x0 = 0., $y0 = 0., $x1 = null, $y1 = null )
    {
        $this->x0 = $x0;
        $this->y0 = $y0;
        $this->x1 = $x1;
        $this->y1 = $y1;

        // Switch values to ensure correct order
        if ( $this->x0 > $this->x1 )
        {
            $tmp = $this->x0;
            $this->x0 = $this->x1;
            $this->x1 = $tmp;
        }

        if ( $this->y0 > $this->y1 )
        {
            $tmp = $this->y0;
            $this->y0 = $this->y1;
            $this->y1 = $tmp;
        }
    }

    /**
     * Getter for calculated values depending on the boundings.
     *  - 'width': Width of bounding recangle
     *  - 'height': Height of bounding recangle
     * 
     * @param string $name Name of property to get
     * @return mixed Calculated value
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'width':
                return $this->x1 - $this->x0;
            case 'height':
                return $this->y1 - $this->y0;
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }
}

?>
