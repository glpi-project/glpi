<?php
/**
 * File containing the ezcGraphFontRenderingException class
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
 * Exception thrown when it is not possible to render a string beacause of 
 * minimum font size in the desinated bounding box.
 *
 * @package Graph
 * @version //autogentag//
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
