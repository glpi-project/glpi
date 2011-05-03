<?php
/**
 * File containing the abstract ezcGraphChartSingleDataContainer class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Container class for datasets, which ensures, that only one dataset is used.
 * Needed for pie charts which can only display one dataset.
 *
 * @version 1.5
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
