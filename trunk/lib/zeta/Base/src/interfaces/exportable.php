<?php
/**
 * File containing the ezcBaseExportable interface.
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
 * Interface for class of which instances can be exported using var_export().
 *
 * In some components, objects can be stored (e.g. to disc) using the var_export() 
 * function. To ensure that an object supports proper importing again, this 
 * interface should be implemented.
 *
 * @see var_export()
 */
interface ezcBaseExportable
{
    /**
     * Returns an instance of the desired object, initialized from $state.
     *
     * This method must return a new instance of the class it is implemented 
     * in, which has its properties set from the given $state array.
     *
     * @param array $state 
     * @return object
     */
    public static function __set_state( array $state );
}

?>
