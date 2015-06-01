<?php
/**
 * File containing the ezcBaseWhateverException class
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
 * ezcBaseWhateverException is thrown whenever something is so seriously wrong.
 *
 * If this happens it is not possible to repair anything gracefully. An
 * example for this could be, that your eZ components installation has thrown
 * far to many exceptions. Whenever you receive an ezcBaseWhateverException, do
 * not even try to catch it, but forget your project completely and immediately
 * stop coding! ;)
 *
 * @access private
 * @package Base
 * @version //autogen//
 */
class ezcBaseWhateverException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseWhateverException.
     *
     * @param string $what  What happened?
     * @param string $where Where did it happen?
     * @param string $who   Who is responsible?
     * @param string $why   Why did is happen?
     * @access protected
     * @return void
     */
    function __construct( $what, $where, $who, $why )
    {
        parent::__construct( "Thanks for using eZ components. Hope you like it! Greetings from Amos, Derick, El Frederico, Ray and Toby." );
    }
}
?>
