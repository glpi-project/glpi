<?php
/**
 * File containing the ezcBaseSettingNotFoundException class.
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
 * ezcBaseSettingNotFoundException is thrown whenever there is a name passed as
 * part as the options array to setOptions() for an option that doesn't exist.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseSettingNotFoundException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseSettingNotFoundException for $settingName.
     *
     * @param string $settingName The name of the setting that does not exist.
     */
    function __construct( $settingName )
    {
        parent::__construct( "The setting '{$settingName}' is not a valid configuration setting." );
    }
}
?>
