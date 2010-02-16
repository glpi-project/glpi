<?php
/**
 * File containing the ezcBase class.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Base class implements the methods needed to use the eZ components.
 *
 * @package Base
 * @version 1.8
 * @mainclass
 */
class ezcBase
{
    /**
     * Used for dependency checking, to check for a PHP extension.
     */
    const DEP_PHP_EXTENSION = "extension";

    /**
     * Used for dependency checking, to check for a PHP version.
     */
    const DEP_PHP_VERSION = "version";

    /**
     * Denotes the production mode
     */
    const MODE_PRODUCTION = 0;

    /**
     * Denotes the development mode
     */
    const MODE_DEVELOPMENT = 1;

    /**
     * Indirectly it determines the path where the autoloads are stored.
     *
     * @var string
     */
    private static $libraryMode = "tarball";

    /**
     * Contains the current working directory, which is used when the
     * $libraryMode is set to "custom".
     *
     * @var string
     */
    private static $currentWorkingDirectory = null;

    /**
     * The full path to the autoload directory.
     *
     * @var string
     */
    protected static $packageDir = null;

    /**
     * Contains which development mode is used. It's "development" by default,
     * because of backwards compatibility reasons.
     */
    private static $runMode = self::MODE_DEVELOPMENT;

    /**
     * Stores info with additional paths where autoload files and classes for
     * autoloading could be found. Each item of $repositoryDirs looks like
     * array( autoloadFileDir, baseDir ). The array key is the prefix belonging
     * to classes within that repository - if provided when calling
     * addClassRepository(), or an autoincrement integer otherwise.
     *
     * @var array(string=>array)
     */
    protected static $repositoryDirs = array();

    /**
     * This variable stores all the elements from the autoload arrays. When a
     * new autoload file is loaded, their files are added to this array.
     *
     * @var array(string=>string)
     */
    protected static $autoloadArray = array();

    /**
     * This variable stores all the elements from the autoload arrays for
     * external repositories. When a new autoload file is loaded, their files
     * are added to this array.
     *
     * @var array(string=>string)
     */
    protected static $externalAutoloadArray = array();

    /**
     * Options for the ezcBase class.
     *
     * @var ezcBaseOptions
     */
    static private $options;

    /**
     * Associates an option object with this static class.
     *
     * @param ezcBaseAutoloadOptions $options
     */
    static public function setOptions( ezcBaseAutoloadOptions $options )
    {
        self::$options = $options;
    }

    /**
     * Tries to autoload the given className. If the className could be found
     * this method returns true, otherwise false.
     *
     * This class caches the requested class names (including the ones who
     * failed to load).
     *
     * @param string $className  The name of the class that should be loaded.
     *
     * @return bool
     */
    public static function autoload( $className )
    {
        ezcBase::setPackageDir();

        // Check whether the classname is already in the cached autoloadArray.
        if ( array_key_exists( $className, ezcBase::$autoloadArray ) )
        {
            // Is it registered as 'unloadable'?
            if ( ezcBase::$autoloadArray[$className] == false )
            {
                return false;
            }
            ezcBase::loadFile( ezcBase::$autoloadArray[$className] );

            return true;
        }

        // Check whether the classname is already in the cached autoloadArray
        // for external repositories.
        if ( array_key_exists( $className, ezcBase::$externalAutoloadArray ) )
        {
            // Is it registered as 'unloadable'?
            if ( ezcBase::$externalAutoloadArray[$className] == false )
            {
                return false;
            }
            ezcBase::loadExternalFile( ezcBase::$externalAutoloadArray[$className] );

            return true;
        }

        // Not cached, so load the autoload from the package.
        // Matches the first and optionally the second 'word' from the classname.
        $fileNames = array();
        if ( preg_match( "/^([a-z0-9]*)([A-Z][a-z0-9]*)?([A-Z][a-z0-9]*)?/", $className, $matches ) !== false )
        {
            $autoloadFile = "";
            // Try to match with both names, if available.
            switch ( sizeof( $matches ) )
            {
                case 4:
                    // check for x_y_autoload.php
                    $autoloadFile = strtolower( "{$matches[2]}_{$matches[3]}_autoload.php" );
                    $fileNames[] = $autoloadFile;
                    if ( ezcBase::requireFile( $autoloadFile, $className, $matches[1] ) )
                    {
                        return true;
                    }
                    // break intentionally missing.

                case 3:
                    // check for x_autoload.php
                    $autoloadFile = strtolower( "{$matches[2]}_autoload.php" );
                    $fileNames[] = $autoloadFile;
                    if ( ezcBase::requireFile( $autoloadFile, $className, $matches[1] ) )
                    {
                        return true;
                    }
                    // break intentionally missing.

                case 2:
                    // check for autoload.php
                    $autoloadFile = 'autoload.php';
                    $fileNames[] = $autoloadFile;
                    if ( ezcBase::requireFile( $autoloadFile, $className, $matches[1] ) )
                    {
                        return true;
                    }
                    break;
            }

            // Maybe there is another autoload available.
            // Register this classname as false.
            ezcBase::$autoloadArray[$className] = false;
        }

        $path = ezcBase::$packageDir . 'autoload/';
        $realPath = realpath( $path );

        if ( $realPath == '' )
        {
            // Can not be tested, because if this happens, then the autoload
            // environment has not been set-up correctly.
            trigger_error( "Couldn't find autoload directory '$path'", E_USER_ERROR );
        }

        $dirs = self::getRepositoryDirectories();
        if ( ezcBase::$options && ezcBase::$options->debug )
        {
            throw new ezcBaseAutoloadException( $className, $fileNames, $dirs );
        }

        return false;
    }

    /**
     * Sets the current working directory to $directory.
     *
     * @param string $directory
     */
    public static function setWorkingDirectory( $directory )
    {
        self::$libraryMode = 'custom';
        self::$currentWorkingDirectory = $directory;
    }

    /**
     * Figures out the base path of the eZ Components installation.
     *
     * It stores the path that it finds in a static member variable. The path
     * depends on the installation method of the eZ Components. The SVN version
     * has a different path than the PEAR installed version.
     */
    protected static function setPackageDir()
    {
        if ( ezcBase::$packageDir !== null )
        {
            return;
        }

        // Get the path to the components.
        $baseDir = dirname( __FILE__ );

        switch ( ezcBase::$libraryMode )
        {
            case "custom":
                ezcBase::$packageDir = self::$currentWorkingDirectory . '/';
                break;
            case "devel":
            case "tarball":
                ezcBase::$packageDir = $baseDir. "/../../";
                break;
            case "pear";
                ezcBase::$packageDir = $baseDir. "/../";
                break;
        }
    }

    /**
     * Tries to load the autoload array and, if loaded correctly, includes the class.
     *
     * @param string $fileName    Name of the autoload file.
     * @param string $className   Name of the class that should be autoloaded.
     * @param string $prefix      The prefix of the class repository.
     *
     * @return bool  True is returned when the file is correctly loaded.
     *                   Otherwise false is returned.
     */
    protected static function requireFile( $fileName, $className, $prefix )
    {
        $autoloadDir = ezcBase::$packageDir . "autoload/";

        // We need the full path to the fileName. The method file_exists() doesn't
        // automatically check the (php.ini) library paths. Therefore:
        // file_exists( "ezc/autoload/$fileName" ) doesn't work.
        if ( $prefix === 'ezc' && file_exists( "$autoloadDir$fileName" ) )
        {
            $array = require( "$autoloadDir$fileName" );

            if ( is_array( $array) && array_key_exists( $className, $array ) )
            {
                // Add the array to the cache, and include the requested file.
                ezcBase::$autoloadArray = array_merge( ezcBase::$autoloadArray, $array );
                if ( ezcBase::$options !== null && ezcBase::$options->preload && !preg_match( '/Exception$/', $className ) )
                {
                    foreach ( $array as $loadClassName => $file )
                    {
                        if ( $loadClassName !== 'ezcBase' && !class_exists( $loadClassName, false ) && !interface_exists( $loadClassName, false ) && !preg_match( '/Exception$/', $loadClassName ) /*&& !class_exists( $loadClassName, false ) && !interface_exists( $loadClassName, false )*/ )
                        {
                            ezcBase::loadFile( ezcBase::$autoloadArray[$loadClassName] );
                        }
                    }
                }
                else
                {
                    ezcBase::loadFile( ezcBase::$autoloadArray[$className] );
                }
                return true;
            }
        }

        // It is not in components autoload/ dir.
        // try to search in additional dirs.
        foreach ( ezcBase::$repositoryDirs as $repositoryPrefix => $extraDir )
        {
            if ( gettype( $repositoryPrefix ) === 'string' && $repositoryPrefix !== $prefix )
            {
                continue;
            }

            if ( file_exists( $extraDir['autoloadDirPath'] . '/' . $fileName ) )
            {
                $array = array();
                $originalArray = require( $extraDir['autoloadDirPath'] . '/' . $fileName );

                // Building paths.
                // Resulting path to class definition file consists of:
                // path to extra directory with autoload file +
                // basePath provided for current extra directory +
                // path to class definition file stored in autoload file.
                foreach ( $originalArray as $class => $classPath )
                {
                    $array[$class] = $extraDir['basePath'] . '/' . $classPath;
                }

                if ( is_array( $array ) && array_key_exists( $className, $array ) )
                {
                    // Add the array to the cache, and include the requested file.
                    ezcBase::$externalAutoloadArray = array_merge( ezcBase::$externalAutoloadArray, $array );
                    ezcBase::loadExternalFile( ezcBase::$externalAutoloadArray[$className] );
                    return true;
                }
            }
        }

        // Nothing found :-(.
        return false;
    }

    /**
     * Loads, require(), the given file name. If we are in development mode,
     * "/src/" is inserted into the path.
     *
     * @param string $file  The name of the file that should be loaded.
     */
    protected static function loadFile( $file )
    {
        switch ( ezcBase::$libraryMode )
        {
            case "devel":
            case "tarball":
                list( $first, $second ) = explode( '/', $file, 2 );
                $file = $first . "/src/" . $second;
                break;

            case "custom":
                list( $first, $second ) = explode( '/', $file, 2 );
                // Add the "src/" after the package name.
                if ( $first == 'Base' || $first == 'UnitTest' )
                {
                    list( $first, $second ) = explode( '/', $file, 2 );
                    $file = $first . "/src/" . $second;
                }
                else
                {
                    list( $first, $second, $third ) = explode( '/', $file, 3 );
                    $file = $first . '/' . $second . "/src/" . $third;
                }
                break;

            case "pear":
                /* do nothing, it's already correct */
                break;
        }

        if ( file_exists( ezcBase::$packageDir . $file ) )
        {
            require( ezcBase::$packageDir . $file );
        }
        else
        {
            // Can not be tested, because if this happens, then one of the
            // components has a broken autoload file.
            throw new ezcBaseFileNotFoundException( ezcBase::$packageDir.$file );
        }
    }

    /**
     * Loads, require(), the given file name from an external package.
     *
     * @param string $file  The name of the file that should be loaded.
     */
    protected static function loadExternalFile( $file )
    {
        if ( file_exists( $file ) )
        {
            require( $file );
        }
        else
        {
            throw new ezcBaseFileNotFoundException( $file );
        }
    }

    /**
     * Checks for dependencies on PHP versions or extensions
     *
     * The function as called by the $component component checks for the $type
     * dependency. The dependency $type is compared against the $value. The
     * function aborts the script if the dependency is not matched.
     *
     * @param string $component
     * @param int $type
     * @param mixed $value
     */
    public static function checkDependency( $component, $type, $value )
    {
        switch ( $type )
        {
            case self::DEP_PHP_EXTENSION:
                if ( extension_loaded( $value ) )
                {
                    return;
                }
                else
                {
                    // Can not be tested as it would abort the PHP script.
                    die( "\nThe {$component} component depends on the default PHP extension '{$value}', which is not loaded.\n" );
                }
                break;

            case self::DEP_PHP_VERSION:
                $phpVersion = phpversion();
                if ( version_compare( $phpVersion, $value, '>=' ) )
                {
                    return;
                }
                else
                {
                    // Can not be tested as it would abort the PHP script.
                    die( "\nThe {$component} component depends on the PHP version '{$value}', but the current version is '{$phpVersion}'.\n" );
                }
                break;
        }
    }

    /**
     * Return the list of directories that contain class repositories.
     *
     * The path to the eZ components directory is always included in the result
     * array. Each element in the returned array has the format of:
     * packageDirectory => ezcBaseRepositoryDirectory
     *
     * @return array(string=>ezcBaseRepositoryDirectory)
     */
    public static function getRepositoryDirectories()
    {
        $autoloadDirs = array();
        ezcBase::setPackageDir();
        $repositoryDir = self::$currentWorkingDirectory ? self::$currentWorkingDirectory : ( realpath( dirname( __FILE__ ) . '/../../' ) );
        $autoloadDirs['ezc'] = new ezcBaseRepositoryDirectory( ezcBaseRepositoryDirectory::TYPE_INTERNAL, $repositoryDir, $repositoryDir . "/autoload" );

        foreach ( ezcBase::$repositoryDirs as $extraDirKey => $extraDirArray )
        {
            $repositoryDirectory = new ezcBaseRepositoryDirectory( ezcBaseRepositoryDirectory::TYPE_EXTERNAL, realpath( $extraDirArray['basePath'] ), realpath( $extraDirArray['autoloadDirPath'] ) );
            $autoloadDirs[$extraDirKey] = $repositoryDirectory;
        }

        return $autoloadDirs;
    }

    /**
     * Adds an additional class repository.
     *
     * Used for adding class repositoryies outside the eZ components to be
     * loaded by the autoload system.
     *
     * This function takes two arguments: $basePath is the base path for the
     * whole class repository and $autoloadDirPath the path where autoload
     * files for this repository are found. The paths in the autoload files are
     * relative to the package directory as specified by the $basePath
     * argument. I.e. class definition file will be searched at location
     * $basePath + path to the class definition file as stored in the autoload
     * file.
     *
     * addClassRepository() should be called somewhere in code before external classes
     * are used.
     *
     * Example:
     * Take the following facts:
     * <ul>
     * <li>there is a class repository stored in the directory "./repos"</li>
     * <li>autoload files for that repository are stored in "./repos/autoloads"</li>
     * <li>there are two components in this repository: "Me" and "You"</li>
     * <li>the "Me" component has the classes "erMyClass1" and "erMyClass2"</li>
     * <li>the "You" component has the classes "erYourClass1" and "erYourClass2"</li>
     * </ul>
     *
     * In this case you would need to create the following files in
     * "./repos/autoloads". Please note that the part before _autoload.php in
     * the filename is the first part of the <b>classname</b>, not considering
     * the all lower-case letter prefix.
     *
     * "my_autoload.php":
     * <code>
     * <?php
     *     return array (
     *       'erMyClass1' => 'Me/myclass1.php',
     *       'erMyClass2' => 'Me/myclass2.php',
     *     );
     * ?>
     * </code>
     *
     * "your_autoload.php":
     * <code>
     * <?php
     *     return array (
     *       'erYourClass1' => 'You/yourclass1.php',
     *       'erYourClass2' => 'You/yourclass2.php',
     *     );
     * ?>
     * </code>
     *
     * The directory structure for the external repository is then:
     * <code>
     * ./repos/autoloads/my_autoload.php
     * ./repos/autoloads/you_autoload.php
     * ./repos/Me/myclass1.php
     * ./repos/Me/myclass2.php
     * ./repos/You/yourclass1.php
     * ./repos/You/yourclass2.php
     * </code>
     *
     * To use this repository with the autoload mechanism you have to use the
     * following code:
     * <code>
     * <?php
     * ezcBase::addClassRepository( './repos', './repos/autoloads' );
     * $myVar = new erMyClass2();
     * ?>
     * </code>
     *
     * @throws ezcBaseFileNotFoundException if $autoloadDirPath or $basePath do not exist.
     * @param string $basePath
     * @param string $autoloadDirPath
     * @param string $prefix
     */
    public static function addClassRepository( $basePath, $autoloadDirPath = null, $prefix = null )
    {
        // check if base path exists
        if ( !is_dir( $basePath ) )
        {
            throw new ezcBaseFileNotFoundException( $basePath, 'base directory' );
        }

        // calculate autoload path if it wasn't given
        if ( is_null( $autoloadDirPath ) )
        {
            $autoloadDirPath = $basePath . '/autoload';
        }

        // check if autoload dir exists
        if ( !is_dir( $autoloadDirPath ) )
        {
            throw new ezcBaseFileNotFoundException( $autoloadDirPath, 'autoload directory' );
        }

        // add info to $repositoryDirs
        if ( $prefix === null )
        {
            $array = array( 'basePath' => $basePath, 'autoloadDirPath' => $autoloadDirPath );

            // add info to the list of extra dirs
            ezcBase::$repositoryDirs[] = $array;
        }
        else
        {
            if ( array_key_exists( $prefix, ezcBase::$repositoryDirs ) )
            {
                throw new ezcBaseDoubleClassRepositoryPrefixException( $prefix, $basePath, $autoloadDirPath );
            }

            // add info to the list of extra dirs, and use the prefix to identify the new repository.
            ezcBase::$repositoryDirs[$prefix] = array( 'basePath' => $basePath, 'autoloadDirPath' => $autoloadDirPath );
        }
    }

    /**
     * Returns the base path of the eZ Components installation
     *
     * This method returns the base path, including a trailing directory
     * separator.
     *
     * @return string
     */
    public static function getInstallationPath()
    {
        self::setPackageDir();

        $path = realpath( self::$packageDir );
        if ( substr( $path, -1 ) !== DIRECTORY_SEPARATOR )
        {
            $path .= DIRECTORY_SEPARATOR;
        }
        return $path;
    }

    /**
     * Sets the development mode to the one specified.
     *
     * @param int $runMode
     */
    public static function setRunMode( $runMode )
    {
        if ( !in_array( $runMode, array( ezcBase::MODE_PRODUCTION, ezcBase::MODE_DEVELOPMENT ) ) )
        {
            throw new ezcBaseValueException( 'runMode', $runMode, 'ezcBase::MODE_PRODUCTION or ezcBase::MODE_DEVELOPMENT' );
        }

        self::$runMode = $runMode;
    }

    /**
     * Returns the current development mode.
     *
     * @return int
     */
    public static function getRunMode()
    {
        return self::$runMode;
    }

    /**
     * Returns true when we are in development mode.
     *
     * @return bool
     */
    public static function inDevMode()
    {
        return self::$runMode == ezcBase::MODE_DEVELOPMENT;
    }

    /**
     * Returns the installation method
     *
     * Possible return values are 'custom', 'devel', 'tarball' and 'pear'. Only
     * 'tarball' and 'pear' are returned for user-installed versions.
     *
     * @return string
     */
    public static function getInstallMethod()
    {
        return self::$libraryMode;
    }
}
?>
