<?php
/**
 * File containing the ezcBaseFileIoException class
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
 * ezcBaseFileIoException is thrown when a problem occurs while writing
 * and reading to/from an open file.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseFileIoException extends ezcBaseFileException
{
    /**
     * Constructs a new ezcBaseFileIoException for the file $path.
     *
     * @param string $path The name of the file.
     * @param int    $mode The mode of the property that is allowed
     *               (ezcBaseFileException::READ, ezcBaseFileException::WRITE,
     *               ezcBaseFileException::EXECUTE or
     *               ezcBaseFileException::CHANGE).
     * @param string $message A string with extra information.
     */
    function __construct( $path, $mode, $message = null )
    {
        switch ( $mode )
        {
            case ezcBaseFileException::READ:
                $operation = "An error occurred while reading from '{$path}'";
                break;
            case ezcBaseFileException::WRITE:
                $operation = "An error occurred while writing to '{$path}'";
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
