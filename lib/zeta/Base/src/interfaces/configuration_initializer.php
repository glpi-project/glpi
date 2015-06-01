<?php
/**
 * File containing the ezcBaseConfigurationInitializer class
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
 * This class provides the interface that classes need to implement to act as
 * an callback initializer class to work with the delayed initialization
 * mechanism.
 *
 * @package Base
 * @version //autogen//
 */
interface ezcBaseConfigurationInitializer
{
    /**
     * Configures the given object, or returns the proper object depending on
     * the given identifier.
     *
     * In case a string identifier was given, it should return the associated
     * object, in case an object was given the method should return null.
     *
     * @param string|object $object
     * @return mixed
     */
    static public function configureObject( $object );
}
?>
