<?php
/**
 * File containing the ezcBaseInitCallbackConfiguredException class
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
 * ezcBaseInitCallbackConfiguredException is thrown when you try to assign a
 * callback clasname to an identifier, while there is already a callback class
 * configured for this identifier.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseInitCallbackConfiguredException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseInitCallbackConfiguredException.
     *
     * @param string $identifier
     * @param string $originalCallbackClassName
     */
    function __construct( $identifier, $originalCallbackClassName )
    {
        parent::__construct( "The '{$identifier}' is already configured with callback class '{$originalCallbackClassName}'." );
    }
}
?>
