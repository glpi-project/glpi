<?php
/**
 * File containing the ezcBaseMetaDataPearReader class.
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
 * @package Base
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

@require 'PEAR/Registry.php';

/**
 * Base class implements ways of fetching information about the installed
 * eZ Components when installed as tarball.
 *
 * Note: there are lots of @ used here, because PEAR still lives in the stone
 * age with their PHP 3 code and general liberal use of throwing warnings and
 * notices.
 *
 * @package Base
 * @version //autogentag//
 * @mainclass
 */
class ezcBaseMetaDataPearReader
{
    /**
     * Stores the PEAR_Registry to query for information
     *
     * @var PEAR_Registry
     */
    private $registry;

    /**
     * Creates the reader object and initialized the registry for querying
     */
    public function __construct()
    {
        @$this->registry = new PEAR_Registry;
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
        @$packageInfo = $this->registry->packageInfo( 'ezcomponents', null, 'components.ez.no' );
        return $packageInfo['version']['release'];
    }

    /**
     * Returns a PHP version string that describes the required PHP version for
     * this installed eZ Components bundle.
     *
     * @return string
     */
    public function getRequiredPhpVersion()
    {
        @$packageInfo = $this->registry->packageInfo( 'ezcomponents', null, 'components.ez.no' );
        if ( array_key_exists( 'required', $packageInfo['dependencies'] ) )
        {
            return $packageInfo['dependencies']['required']['php']['min'];
        }
        return $packageInfo['dependencies']['php']['min'];
    }

    /**
     * Returns whether $componentName is installed
     *
     * Checks the PEAR registry whether the component is there.
     *
     * @return bool
     */
    public function isComponentInstalled( $componentName )
    {
        @$packageInfo = $this->registry->packageInfo( $componentName, null, 'components.ez.no' );
        return is_array( $packageInfo );
    }

    /**
     * Returns the version string of the available $componentName or false when
     * the component is not installed.
     *
     * @return string
     */
    public function getComponentVersion( $componentName )
    {
        @$packageInfo = $this->registry->packageInfo( $componentName, null, 'components.ez.no' );
        $release = $packageInfo['version']['release'];
        return $release === null ? false : $release;
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
    public function getComponentDependencies( $componentName = 'ezcomponents' )
    {
        @$packageInfo = $this->registry->packageInfo( $componentName, 'dependencies', 'components.ez.no' );
        if ( isset( $packageInfo['required']['package'] ) )
        {
            $deps = array();
            if ( isset( $packageInfo['required']['package']['name'] ) )
            {
                $deps[$packageInfo['required']['package']['name']] = $packageInfo['required']['package']['min'];
            }
            else
            {
                foreach ( $packageInfo['required']['package'] as $package )
                {
                    $deps[$package['name']] = $package['min'];
                }
            }
            return $deps;
        }
        return array();
    }
}
?>
