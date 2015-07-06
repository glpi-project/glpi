<?php
/**
 * File containing the ezcGraphCoordinate struct
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
 */
/**
 * Represents coordinates in two dimensional catesian coordinate system.
 *
 * Coordinates are used to represent the location of objects on the drawing
 * plane. They are simple structs conatining the two coordinate values required
 * in a two dimensional cartesian coordinate system. The class ezcGraphVector
 * extends the Coordinate class and provides additional methods like rotations,
 * simple arithmetic operations etc.
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphCoordinate extends ezcBaseStruct
{
    /**
     * x coordinate
     * 
     * @var float
     */
    public $x = 0;

    /**
     * y coordinate
     * 
     * @var float
     */
    public $y = 0;
    
    /**
     * Simple constructor
     *
     * @param float $x x coordinate
     * @param float $y y coordinate
     * @ignore
     */
    public function __construct( $x, $y )
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * __set_state 
     * 
     * @param array $properties Struct properties
     * @return void
     * @ignore
     */
    public function __set_state( array $properties )
    {
        $this->x = $properties['x'];
        $this->y = $properties['y'];
    }

    /**
     * Returns simple string representation of coordinate
     * 
     * @return string
     * @ignore
     */
    public function __toString()
    {
        return sprintf( '( %.2F, %.2F )', $this->x, $this->y );
    }
}

?>
