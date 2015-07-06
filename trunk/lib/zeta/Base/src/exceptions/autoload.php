<?php
/**
 * File containing the ezcBaseAutoloadException class
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
 * ezcBaseAutoloadException is thrown whenever a class can not be found with
 * the autoload mechanism.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseAutoloadException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseAutoloadException for the $className that was
     * searched for in the autoload files $fileNames from the directories
     * specified in $dirs.
     *
     * @param string $className
     * @param array(string) $files
     * @param array(ezcBaseRepositoryDirectory) $dirs
     */
    function __construct( $className, $files, $dirs )
    {
        $paths = array();
        foreach ( $dirs as $dir )
        {
            $paths[] = realpath( $dir->autoloadPath );
        }
        parent::__construct( "Could not find a class to file mapping for '{$className}'. Searched for ". implode( ', ', $files ) . " in: " . implode( ', ', $paths ) );
    }
}
?>
