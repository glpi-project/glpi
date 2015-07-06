<?php
/**
 * File containing the ezcBaseStruct.
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
 * Base class for all struct classes.
 *
 * @package Base
 * @version //autogentag//
 */
class ezcBaseStruct
{
    /**
     * Throws a BasePropertyNotFound exception.
     *
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    final public function __set( $name, $value )
    {
        throw new ezcBasePropertyNotFoundException( $name );
    }

    /**
     * Throws a BasePropertyNotFound exception.
     *
     * @param string $name
     * @ignore
     */
    final public function __get( $name )
    {
        throw new ezcBasePropertyNotFoundException( $name );
    }
}
?>
