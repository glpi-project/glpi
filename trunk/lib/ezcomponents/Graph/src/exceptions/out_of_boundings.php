<?php
/**
 * File containing the ezcGraphMatrixOutOfBoundingsException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when a requested matrix value is out of the boundings of 
 * the matrix.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphMatrixOutOfBoundingsException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param int $rows
     * @param int $columns
     * @param int $rPos
     * @param int $cPos
     * @return void
     * @ignore
     */
    public function __construct( $rows, $columns, $rPos, $cPos )
    {
        parent::__construct( "Position '{$rPos}, {$cPos}' is out of the matrix boundings '{$rows}, {$columns}'." );
    }
}

?>
