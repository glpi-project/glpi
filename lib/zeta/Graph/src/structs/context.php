<?php
/**
 * File containing the ezcGraphContext struct
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
 * Struct to represent the context of a renderer operation
 *
 * Objects of this class are passed to the renderer, so the renderer is able to
 * maintain the associations between image primitives and the datpoints. With
 * this information the renderer build the array returned by the
 * getElementReferences() method of the ezcGraphRenderer classes. In the
 * returned array the datapoints are then associated with the identifiers for
 * the image primitives returned by the respective driver.
 *
 * The ezcGraphTools class offers convience methods to handle this data and
 * enrich your charts with hyperlinks, so you don't need to handle this
 * yourself.
 *
 * The struct contains information about the $dataset and $datapoint the
 * image primitive is associated with. If the dataset or datapoint has an
 * URL associated, this URL is also available in the context struct.
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphContext extends ezcBaseStruct
{
    /**
     * Name of dataset
     * 
     * @var string
     */
    public $dataset = false;

    /**
     * Name of datapoint 
     * 
     * @var string
     */
    public $datapoint = false;
    
    /**
     * Associated URL for datapoint
     * 
     * @var string
     */
    public $url;

    /**
     * Simple constructor 
     * 
     * @param string $dataset
     * @param string $datapoint
     * @param string $url
     * @return void
     * @ignore
     */
    public function __construct( $dataset = null, $datapoint = null, $url = null )
    {
        $this->dataset = $dataset;
        $this->datapoint = $datapoint;
        $this->url = $url;
    }

    /**
     * __set_state 
     * 
     * @param array $properties Struct properties
     * @return void
     * @ignore
     */
    public function __set_state( array $properties )
    {
        $this->dataset = (string) $properties['dataset'];
        $this->datapoint = (string) $properties['datapoint'];

        // Check to keep BC
        // @TODO: Remvove unnesecary check on next major version
        if ( array_key_exists( 'url', $properties ) )
        {
            $this->url = (string) $properties['url'];
        }
    }
}

?>
