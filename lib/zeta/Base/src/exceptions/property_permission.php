<?php
/**
 * File containing the ezcPropertyReadOnlyException class
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
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
/**
 * ezcBasePropertyPermissionException is thrown whenever a read-only property
 * is tried to be changed, or when a write-only property was accessed for reading.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBasePropertyPermissionException extends ezcBaseException
{
    /**
     * Used when the property is read-only.
     */
    const READ  = 1;

    /**
     * Used when the property is write-only.
     */
    const WRITE = 2;

    /**
     * Constructs a new ezcPropertyPermissionException for the property $name.
     *
     * @param string $name The name of the property.
     * @param int    $mode The mode of the property that is allowed (::READ or ::WRITE).
     */
    function __construct( $name, $mode )
    {
        parent::__construct( "The property '{$name}' is " .
            ( $mode == self::READ ? "read" : "write" ) .
            "-only." );
    }
}
?>
