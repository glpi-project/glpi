<?php
/**
 * File containing the ezcGraphArrayDataSet class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Dataset class which receives arrays and use them as a base for datasets.
 *
 * @version 1.5
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
