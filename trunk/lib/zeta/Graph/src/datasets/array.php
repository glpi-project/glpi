<?php
/**
 * File containing the ezcGraphArrayDataSet class
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
 * Dataset class which receives arrays and use them as a base for datasets.
 *
 * @version //autogentag//
 * @package Graph
 * @mainclass
 */
class ezcGraphArrayDataSet extends ezcGraphDataSet
{
    /**
     * Constructor
     * 
     * @param array|Iterator $data Array or Iterator containing the data
     * @return void
     */
    public function __construct( $data )
    {
        $this->createFromArray( $data );
        parent::__construct();
    }

    /**
     * setData
     *
     * Can handle data provided through an array or iterator.
     * 
     * @param array|Iterator $data 
     * @access public
     * @return void
     */
    protected function createFromArray( $data = array() ) 
    {
        if ( !is_array( $data ) && 
             !( $data instanceof Traversable ) )
        {
            throw new ezcGraphInvalidArrayDataSourceException( $data );
        }

        $this->data = array();
        foreach ( $data as $key => $value )
        {
            $this->data[$key] = $value;
        }

        if ( !count( $this->data ) )
        {
            throw new ezcGraphInvalidDataException( 'Data sets should contain some values.' );
        }
    }

    /**
     * Returns the number of elements in this dataset
     * 
     * @return int
     */
    public function count()
    {
        return count( $this->data );
    }
}

?>
