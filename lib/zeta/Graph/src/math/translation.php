<?php
/**
 * File containing the ezcGraphTranslation class
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
 * Class creating translation matrices from given movements
 *
 * Three dimensional matrices (3x3) may be used to specify transformation of
 * points, vectors and complexer structures in a two dimensional cartesian
 * coordinate system. For more details have a look here:
 * http://en.wikipedia.org/wiki/Transformation_matrix
 *
 * This class implements a convenient interface to create matrixes to move
 * elements. This matrix may be combined with other transformation matrices, as
 * usual.
 *
 * @version //autogentag//
 * @package Graph
 * @access private
 */
class ezcGraphTranslation extends ezcGraphTransformation
{
    /**
     * Constructor
     * 
     * @param float $x 
     * @param float $y 
     * @return void
     * @ignore
     */
    public function __construct( $x = 0., $y = 0. )
    {
        parent::__construct( array( 
            array( 1, 0, $x ),
            array( 0, 1, $y ),
            array( 0, 0, 1 ),
        ) );
    }
}

?>
