<?php
/**
 * File containing the ezcBaseFileNotFoundException class
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
 * ezcBaseFileNotFoundException is thrown when a file or directory was tried to
 * be opened, but did not exist.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseFileNotFoundException extends ezcBaseFileException
{
    /**
     * Constructs a new ezcBaseFileNotFoundException.
     *
     * @param string $path The name of the file.
     * @param string $type The type of the file.
     * @param string $message A string with extra information.
     */
    function __construct( $path, $type = null, $message = null )
    {
        $typePart = '';
        if ( $type )
        {
            $typePart = "$type ";
        }

        $messagePart = '';
        if ( $message )
        {
            $messagePart = " ($message)";
        }

        parent::__construct( "The {$typePart}file '{$path}' could not be found.$messagePart" );
    }
}
?>
