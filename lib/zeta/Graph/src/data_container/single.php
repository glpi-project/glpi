<?php
/**
 * File containing the abstract ezcGraphChartSingleDataContainer class
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
 * Container class for datasets, which ensures, that only one dataset is used.
 * Needed for pie charts which can only display one dataset.
 *
 * @version //autogentag//
 * @package Graph
 */

class ezcGraphChartSingleDataContainer extends ezcGraphChartDataContainer 
{
    /**
     * Adds a dataset to the charts data
     * 
     * @param string $name
     * @param ezcGraphDataSet $dataSet
     * @throws ezcGraphTooManyDataSetExceptions
     *          If too many datasets are created
     * @return ezcGraphDataSet
     */
    protected function addDataSet( $name, ezcGraphDataSet $dataSet )
    {
        if ( count( $this->data ) >= 1 &&
             !isset( $this->data[$name] ) )
        {
            throw new ezcGraphTooManyDataSetsExceptions( $name );
        }
        else
        {
            parent::addDataSet( $name, $dataSet );

            // Resette palette color counter
            $this->chart->palette->resetColorCounter();

            // Colorize each data element
            foreach ( $this->data[$name] as $label => $value )
            {
                $this->data[$name]->color[$label] = $this->chart->palette->dataSetColor;
            }
        }
    }
}
?>
