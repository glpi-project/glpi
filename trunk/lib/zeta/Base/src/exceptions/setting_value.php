<?php
/**
 * File containing the ezcBaseSettingValueException class.
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
 * ezcBaseSettingValueExeception is thrown whenever a value to a class'
 * configuration option is either of the wrong type, or has a wrong value.
 *
 * @package Base
 * @version //autogen//
 */
class ezcBaseSettingValueException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseConfigException
     *
     * @param string  $settingName The name of the setting where something was
     *                wrong with.
     * @param mixed   $value The value that the option was tried to be set too.
     * @param string  $expectedValue A string explaining the allowed type and value range.
     */
    function __construct( $settingName, $value, $expectedValue = null )
    {
        $type = gettype( $value );
        if ( in_array( $type, array( 'array', 'object', 'resource' ) ) )
        {
            $value = serialize( $value );
        }
        $msg = "The value '{$value}' that you were trying to assign to setting '{$settingName}' is invalid.";
        if ( $expectedValue )
        {
            $msg .= " Allowed values are: " . $expectedValue;
        }
        parent::__construct( $msg );
    }
}
?>
