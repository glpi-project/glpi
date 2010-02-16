<?php
/**
 * File containing the ezcGraphContext struct
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
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
 * @version 1.5
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
