<?php
/**
 * File containing the ezcGraphToolsNotRenderedException class
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
 * @package Graph
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
/**
 * Exception thrown when a chart was not rendered yet, but the graph tool 
 * requires information only available in rendered charts.
 *
 * @package Graph
 * @version //autogentag//
 */
class ezcGraphToolsNotRenderedException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param ezcGraphChart $chart
     * @return void
     * @ignore
     */
    public function __construct( $chart )
    {
        parent::__construct( "Chart is not yet rendered." );
    }
}

?>
