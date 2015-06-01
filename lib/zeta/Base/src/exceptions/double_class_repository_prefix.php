<?php
/**
 * File containing the ezcBaseDoubleClassRepositoryPrefixException class
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
 * ezcBaseDoubleClassRepositoryPrefixException is thrown whenever you try to
 * register a class repository with a prefix that has already been added
 * before.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseDoubleClassRepositoryPrefixException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseDoubleClassRepositoryPrefixException for the
     * $prefix that points to $basePath with autoload directory
     * $autoloadDirPath.
     *
     * @param string $prefix
     * @param string $basePath
     * @param string $autoloadDirPath
     */
    function __construct( $prefix, $basePath, $autoloadDirPath )
    {
        parent::__construct( "The class repository in '{$basePath}' (with autoload dir '{$autoloadDirPath}') can not be added because another class repository already uses the prefix '{$prefix}'." );
    }
}
?>
