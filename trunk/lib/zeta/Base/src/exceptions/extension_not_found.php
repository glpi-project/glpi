<?php
/**
 * File containing the ezcBaseExtensionNotFoundException class
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
 * ezcBaseExtensionNotFoundException is thrown when a requested PHP extension was not found.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseExtensionNotFoundException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseExtensionNotFoundException.
     *
     * @param string $name The name of the extension
     * @param string $version The version of the extension
     * @param string $message Additional text
     */
    function __construct( $name, $version = null, $message = null )
    {
        if ( $version === null )
        {
            parent::__construct( "The extension '{$name}' could not be found. {$message}" );
        }
        else
        {
            parent::__construct( "The extension '{$name}' with version '{$version}' could not be found. {$message}" );
        }
    }
}
?>
