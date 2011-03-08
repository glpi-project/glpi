<?php
/**
 * File containing the ezcBaseMetaDataTarballReader class.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Base class implements ways of fetching information about the installed
 * eZ Components when installed as tarball.
 *
 * @package Base
 * @version 1.8
 * @mainclass
 */
class ezcBaseMetaDataTarballReader
{
    /**
     * Contains the handler to the XML file containing the release information.
     * @var SimpleXmlElement
     */
    private $xml;

    /**
     * Creates the reader object and opens the release-info file.
     */
    public function __construct()
    {
        $filename = dirname( __FILE__ ) . '/../../../release-info.xml';
        $this->xml = simplexml_load_file( $filename );
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
        return (string) $this->xml->version;
    }

    /**
     * Returns a PHP version string that describes the required PHP version for
     * this installed eZ Components bundle.
     *
     * @return string
     */
    public function getRequiredPhpVersion()
    {
        return (string) $this->xml->deps->php;
    }

    /**
     * Returns whether $componentName is installed
     *
     * Returns true for every component that exists (because all of them are
     * then available).
     *
     * @return bool
     */
    public function isComponentInstalled( $componentName )
    {
        $root = $this->xml->deps->packages->package;

        foreach ( $root as $package )
        {
            if ( (string) $package['name'] == $componentName )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the version string of the available $componentName or false when
     * the component is not installed.
     *
     * @return string
     */
    public function getComponentVersion( $componentName )
    {
        $root = $this->xml->deps->packages->package;

        foreach ( $root as $package )
        {
            if ( (string) $package['name'] == $componentName )
            {
                return (string) $package['version'];
            }
        }
        return false;
    }

    /**
     * Returns a list of components that $componentName depends on.
     *
     * If $componentName is left empty, all installed components are returned.
     *
     * The returned array has as keys the component names, and as values the
     * version of the components. It returns null of the $componentName
     * is not found.
     *
     * @return array(string=>string).
     */
    public function getComponentDependencies( $componentName = null )
    {
        $baseVersion = false;
        $root = $this->xml->deps->packages;
        $found = $componentName === null ? true : false;

        // in case $componentName != null, we loop through all the components
        // in the file, and figure out the new root that we can list dependency
        // packages from.
        foreach ( $root->package as $package )
        {
            if ( (string) $package['name'] == 'Base' )
            {
                $baseVersion = $package['version'];
            }
            if ( !$found && (string) $package['name'] == $componentName )
            {
                $root = $package->deps;
                $found = true;
            }
        }

        if ( !$found )
        {
            return null;
        }

        // We always add the Base dependency even though it's not in the dependency file.
        $deps = array();
        $deps['Base'] = (string) $baseVersion;

        if ( !isset( $root->package ) )
        {
            return $deps;
        }
        foreach ( $root->package as $package )
        {
            $deps[(string) $package['name']] = (string) $package['version'];
        }
        return $deps;
    }
}
?>
