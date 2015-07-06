<?php
/**
 * File containing the ezcBaseFileFindContext class.
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
 * @package Base
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Struct which defines the information collected by the file walker for locating files.
 *
 * @package Base
 * @version //autogentag//
 */
class ezcBaseFileFindContext extends ezcBaseStruct
{
    /**
     * The list of files
     *
     * @var array(string)
     */
    public $elements;

    /**
     * The number of files
     *
     * @var int
     */
    public $count;

    /**
     * The total file size of all files found
     *
     * @var int
     */
    public $size;

    /**
     * Constructs a new ezcBaseFileFindContext with initial values.
     *
     * @param array(string) $elements
     * @param int $count
     * @param int $size
     */
    public function __construct( $elements = array(), $count = 0, $size = 0 )
    {
        $this->elements = $elements;
        $this->count = $count;
        $this->size = $size;
    }

    /**
     * Returns a new instance of this class with the data specified by $array.
     *
     * $array contains all the data members of this class in the form:
     * array('member_name'=>value).
     *
     * __set_state makes this class exportable with var_export.
     * var_export() generates code, that calls this method when it
     * is parsed with PHP.
     *
     * @param array(string=>mixed) $array
     * @return ezcBaseFileFindContext
     */
    static public function __set_state( array $array )
    {
        return new ezcBaseFileFindContext( $array['elements'], $array['count'], $array['size'] );
    }
}
?>
