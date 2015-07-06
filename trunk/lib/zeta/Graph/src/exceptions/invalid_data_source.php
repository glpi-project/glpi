<?php
/**
 * File containing the ezcGraphInvalidArrayDataSourceException class
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
 * Exception thrown when an invalid data source is provided for an array 
 * data set.
 *
 * @package Graph
 * @version //autogentag//
 */
class ezcGraphInvalidArrayDataSourceException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param mixed $value
     * @return void
     * @ignore
     */
    public function __construct( $value )
    {
        $type = gettype( $value );
        parent::__construct( "The array dataset can only use arrays and iterators, but you supplied '{$type}'." );
    }
}

?>
