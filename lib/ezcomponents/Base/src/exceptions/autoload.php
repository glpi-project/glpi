<?php
/**
 * File containing the ezcBaseAutoloadException class
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * ezcBaseAutoloadException is thrown whenever a class can not be found with
 * the autoload mechanism.
 *
 * @package Base
 * @version 1.8
 */
class ezcBaseAutoloadException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseAutoloadException for the $className that was
     * searched for in the autoload files $fileNames from the directories
     * specified in $dirs.
     *
     * @param string $className
     * @param array(string) $files
     * @param array(ezcBaseRepositoryDirectory) $dirs
     */
    function __construct( $className, $files, $dirs )
    {
        $paths = array();
        foreach ( $dirs as $dir )
        {
            $paths[] = realpath( $dir->autoloadPath );
        }
        parent::__construct( "Could not find a class to file mapping for '{$className}'. Searched for ". implode( ', ', $files ) . " in: " . implode( ', ', $paths ) );
    }
}
?>
