<?php
/**
 * File containing the ezcBaseFilePermissionException class
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
 * ezcBaseFilePermissionException is thrown whenever a permission problem with
 * a file, directory or stream occurred.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseFilePermissionException extends ezcBaseFileException
{
    /**
     * Constructs a new ezcPropertyPermissionException for the property $name.
     *
     * @param string $path The name of the file.
     * @param int    $mode The mode of the property that is allowed
     *               (ezcBaseFileException::READ, ezcBaseFileException::WRITE,
     *               ezcBaseFileException::EXECUTE,
     *               ezcBaseFileException::CHANGE or
     *               ezcBaseFileException::REMOVE).
     * @param string $message A string with extra information.
     */
    function __construct( $path, $mode, $message = null )
    {
        switch ( $mode )
        {
            case ezcBaseFileException::READ:
                $operation = "The file '{$path}' can not be opened for reading";
                break;
            case ezcBaseFileException::WRITE:
                $operation = "The file '{$path}' can not be opened for writing";
                break;
            case ezcBaseFileException::EXECUTE:
                $operation = "The file '{$path}' can not be executed";
                break;
            case ezcBaseFileException::CHANGE:
                $operation = "The permissions for '{$path}' can not be changed";
                break;
            case ezcBaseFileException::REMOVE:
                $operation = "The file '{$path}' can not be removed";
                break;
            case ( ezcBaseFileException::READ || ezcBaseFileException::WRITE ):
                $operation = "The file '{$path}' can not be opened for reading and writing";
                break;
        }

        $messagePart = '';
        if ( $message )
        {
            $messagePart = " ($message)";
        }

        parent::__construct( "$operation.$messagePart" );
    }
}
?>
