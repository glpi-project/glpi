<?php
/**
 * File containing the ezcGraphBoundings class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Graph
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
 * @version //autogentag//
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
