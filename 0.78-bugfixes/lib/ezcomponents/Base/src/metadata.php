<?php
/**
 * File containing the ezcBaseMetaData class.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Base class implements ways of fetching information about the installed
 * eZ Components. It knows whether to use the PEAR registry or the bundled XML
 * file, depending on how eZ Components is installed.
 *
 * @package Base
 * @version 1.8
 * @mainclass
 */
class ezcBaseMetaData
{
    /**
     * Creates a ezcBaseMetaData object
     *
     * The sole parameter $installMethod should only be used if you are really
     * sure that you need to use it. It is mostly there to make testing at
     * least slightly possible. Again, do not set it unless instructed.
     *
     * @param string $installMethod
     */
    public function __construct( $installMethod = NULL )
    {
        $installMethod = $installMethod !== NULL ? $installMethod : ezcBase::getInstallMethod();

        // figure out which reader to use
        switch ( $installMethod )
        {
            case 'tarball':
                $this->reader = new ezcBaseMetaDataTarballReader;
                break;
            case 'pear':
                $this->reader = new ezcBaseMetaDataPearReader;
                break;
            default:
                throw new ezcBaseMetaDataReaderException( "Unknown install method '$installMethod'." );
                break;
        }
    }

    /**
     * Returns the version string for the installed eZ Components bundle.
     *
     * A version string such as "2008.2.2" is returned.
     *
     * @return string
     */
    public function getBundleVersion()
    {
        return $this->reader->getBundleVersion();
    }

    /**
     * Returns a PHP version string that describes the required PHP version for
     * this installed eZ Components bundle.
     *
     * @return string
     */
    public function getRequiredPhpVersion()
    {
        return $this->reader->getRequiredPhpVersion();
    }

    /**
     * Returns whether $componentName is installed
     *
     * If installed with PEAR, it checks the PEAR registry whether the
     * component is there. In case the tarball installation method is used, it
     * will return true for every component that exists (because all of them
     * are then available).
     *
     * @return bool
     */
    public function isComponentInstalled( $componentName )
    {
        return $this->reader->isComponentInstalled( $componentName );
    }

    /**
     * Returns the version string of the available $componentName or false when
     * the component is not installed.
     *
     * @return string
     */
    public function getComponentVersion( $componentName )
    {
        return $this->reader->getComponentVersion( $componentName );
    }

    /**
     * Returns a list of components that $componentName depends on.
     *
     * If $componentName is left empty, all installed components are returned.
     *
     * The returned array has as keys the component names, and as values the
     * version of the components.
     *
     * @return array(string=>string).
     */
    public function getComponentDependencies( $componentName = null )
    {
        if ( $componentName === null )
        {
            return $this->reader->getComponentDependencies();
        }
        else
        {
            return $this->reader->getComponentDependencies( $componentName );
        }
    }
}
?>
