<?php
/**
 * File containing the abstract ezcGraphDataSetIntProperty class
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
 * Class for axis properties of datasets
 *
 * This class is used to store properties for datasets, which should be
 * validated as objects extending the ezcGraphChartElementAxis class.
 *
 * For a basic usage example of those dataset properties take a look at the API
 * documentation of the ezcGraphDataSetProperty class.
 *
 * @version //autogentag//
 * @package Graph
 */
class ezcGraphDataSetAxisProperty extends ezcGraphDataSetProperty
{
    /**
     * Chacks if value is really an axis
     * 
     * @param ezcGraphChartElementAxis $value 
     * @return void
     */
    protected function checkValue( &$value )
    {
       if ( ! $value instanceof ezcGraphChartElementAxis )
       {
           throw new ezcBaseValueException( 'default', $value, 'ezcGraphChartElementAxis' );
       }

       return true;
    }

    /**
     * Set an option.
     *
     * Sets an option using ArrayAccess.
     *
     * This is deaktivated, because you need not set a different axis for some
     * data point.
     * 
     * @param string $key The option to set.
     * @param mixed $value The value for the option.
     * @return void
     *
     * @throws ezcGraphInvalidAssignementException
     *         Always
     */
    public function offsetSet( $key, $value )
    {
        throw new ezcGraphInvalidAssignementException();
    }

}

?>
