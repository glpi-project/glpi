<?php
/**
 * File containing the ezcGraphGdDriverUnsupportedImageTypeException class
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
 * Exception thrown if the image type is not supported and therefore could not
 * be used in the gd driver.
 *
 * @package Graph
 * @version //autogentag//
 */
class ezcGraphGdDriverUnsupportedImageTypeException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param int $type
     * @return void
     * @ignore
     */
    public function __construct( $type )
    {
        $typeName = array(
            1 => 'GIF',
            2 => 'Jpeg',
            3 => 'PNG',
            4 => 'SWF',
            5 => 'PSD',
            6 => 'BMP',
            7 => 'TIFF (intel)',
            8 => 'TIFF (motorola)',
            9 => 'JPC',
            10 => 'JP2',
            11 => 'JPX',
            12 => 'JB2',
            13 => 'SWC',
            14 => 'IFF',
            15 => 'WBMP',
            16 => 'XBM',

        );

        if ( isset( $typeName[$type] ) )
        {
            $type = $typeName[$type];
        }
        else
        {
            $type = 'Unknown';
        }

        parent::__construct( "Unsupported image format '{$type}'." );
    }
}

?>
