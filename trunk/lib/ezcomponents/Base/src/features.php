<?php
/**
 * File containing the ezcBaseFeatures class.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Provides methods needed to check for features.
 *
 * Example:
 * <code>
 * <?php
 * echo "supports uid: " . ezcBaseFeatures::supportsUserId() . "\n";
 * echo "supports symlink: " . ezcBaseFeatures::supportsSymLink() . "\n";
 * echo "supports hardlink: " . ezcBaseFeatures::supportsLink() . "\n";
 * echo "has imagemagick identify: " . ezcBaseFeatures::hasImageIdentify() . "\n";
 * echo " identify path: " . ezcBaseFeatures::getImageIdentifyExecutable() . "\n";
 * echo "has imagemagick convert: " . ezcBaseFeatures::hasImageConvert() . "\n";
 * echo " convert path: " . ezcBaseFeatures::getImageConvertExecutable() . "\n";
 * echo "has gzip extension: " . ezcBaseFeatures::hasExtensionSupport( 'zlib' ) . "\n";
 * echo "has pdo_mysql 1.0.2: " . ezcBaseFeatures::hasExtensionSupport( 'pdo_mysql', '1.0.2' ) . "\n"
 * ?>
 * </code>
 *
 * @package Base
 * @version 1.8
 */
class ezcBaseFeatures
{
    /**
      * Used to store the path of the ImageMagick convert utility.
      *
      * It is initialized in the {@link getImageConvertExecutable()} function.
      *
      * @var string
      */
    private static $imageConvert = null;

    /**
      * Used to store the path of the ImageMagick identify utility.
      *
      * It is initialized in the {@link getImageIdentifyExecutable()} function.
      *
      * @var string
      */
    private static $imageIdentify = null;

    /**
      * Used to store the operating system.
      *
      * It is initialized in the {@link os()} function.
      *
      * @var string
      */
    private static $os = null;

    /**
     * Determines if hardlinks are supported.
     *
     * @return bool
     */
    public static function supportsLink()
    {
        return function_exists( 'link' );
    }

    /**
     * Determines if symlinks are supported.
     *
     * @return bool
     */
    public static function supportsSymLink()
    {
        return function_exists( 'symlink' );
    }

    /**
     * Determines if posix uids are supported.
     *
     * @return bool
     */
    public static function supportsUserId()
    {
        return function_exists( 'posix_getpwuid' );
    }

    /**
     * Determines if the ImageMagick convert utility is installed.
     *
     * @return bool
     */
    public static function hasImageConvert()
    {
        return !is_null( self::getImageConvertExecutable() );
    }

    /**
     * Returns the path to the ImageMagick convert utility.
     *
     * On Linux, Unix,... it will return something like: /usr/bin/convert
     * On Windows it will return something like: C:\Windows\System32\convert.exe
     *
     * @return string
     */
    public static function getImageConvertExecutable()
    {
        if ( !is_null( self::$imageConvert ) )
        {
            return self::$imageConvert;
        }
        return ( self::$imageConvert = self::findExecutableInPath( 'convert' ) );
    }

    /**
     * Determines if the ImageMagick identify utility is installed.
     *
     * @return bool
     */
    public static function hasImageIdentify()
    {
        return !is_null( self::getImageIdentifyExecutable() );
    }

    /**
     * Returns the path to the ImageMagick identify utility.
     *
     * On Linux, Unix,... it will return something like: /usr/bin/identify
     * On Windows it will return something like: C:\Windows\System32\identify.exe
     *
     * @return string
     */
    public static function getImageIdentifyExecutable()
    {
        if ( !is_null( self::$imageIdentify ) )
        {
            return self::$imageIdentify;
        }
        return ( self::$imageIdentify = self::findExecutableInPath( 'identify' ) );
    }

    /**
     * Determines if the specified extension is loaded.
     *
     * If $version is specified, the specified extension will be tested also
     * against the version of the loaded extension.
     *
     * Examples:
     * <code>
     * hasExtensionSupport( 'gzip' );
     * </code>
     * will return true if gzip extension is loaded.
     *
     * <code>
     * hasExtensionSupport( 'pdo_mysql', '1.0.2' );
     * </code>
     * will return true if pdo_mysql extension is loaded and its version is at least 1.0.2.
     *
     * @param string $extension
     * @param string $version
     * @return bool
     */
    public static function hasExtensionSupport( $extension, $version = null )
    {
        if ( is_null( $version ) )
        {
            return extension_loaded( $extension );
        }
        return extension_loaded( $extension ) && version_compare( phpversion( $extension ), $version, ">=" ) ;
    }

    /**
     * Determines if the specified function is available.
     *
     * Examples:
     * <code>
     * ezcBaseFeatures::hasFunction( 'imagepstext' );
     * </code>
     * will return true if support for Type 1 fonts is available with your GD
     * extension.
     *
     * @param string $functionName
     * @return bool
     */
    public static function hasFunction( $functionName )
    {
        return function_exists( $functionName );
    }

    /**
     * Returns if a given class exists.
     * Checks for a given class name and returns if this class exists or not.
     * Catches the ezcBaseAutoloadException and returns false, if it was thrown.
     *
     * @param string $className The class to check for.
     * @param bool $autoload True to use __autoload(), otherwise false.
     * @return bool True if the class exists. Otherwise false.
     */
    public static function classExists( $className, $autoload = true )
    {
        try
        {
            if ( class_exists( $className, $autoload ) )
            {
                return true;
            }
            return false;
        }
        catch ( ezcBaseAutoloadException $e )
        {
            return false;
        }
    }

    /**
     * Returns the operating system on which PHP is running.
     *
     * This method returns a sanitized form of the OS name, example
     * return values are "Windows", "Mac", "Linux" and "FreeBSD". In
     * all other cases it returns the value of the internal PHP constant
     * PHP_OS.
     *
     * @return string
     */
    public static function os()
    {
        if ( is_null( self::$os ) )
        {
            $uname = php_uname( 's' );
            if ( substr( $uname, 0, 7 ) == 'Windows' )
            {
                self::$os = 'Windows';
            }
            elseif ( substr( $uname, 0, 3 ) == 'Mac' )
            {
                self::$os = 'Mac';
            }
            elseif ( strtolower( $uname ) == 'linux' )
            {
                self::$os = 'Linux';
            }
            elseif ( strtolower( substr( $uname, 0, 7 ) ) == 'freebsd' )
            {
                self::$os = 'FreeBSD';
            }
            else
            {
                self::$os = PHP_OS;
            }
        }
        return self::$os;
    }

    /**
     * Returns the path of the specified executable, if it can be found in the system's path.
     *
     * It scans the PATH enviroment variable based on the OS to find the
     * $fileName. For Windows, the path is with \, not /.  If $fileName is not
     * found, it returns null.
     *
     * @todo consider using getenv( 'PATH' ) instead of $_ENV['PATH']
     *       (but that won't work under IIS)
     *
     * @param string $fileName
     * @return string
     */
    public static function findExecutableInPath( $fileName )
    {
        if ( array_key_exists( 'PATH', $_ENV ) )
        {
            $envPath = trim( $_ENV['PATH'] );
        }
        else if ( ( $envPath = getenv( 'PATH' ) ) !== false )
        {
            $envPath = trim( $envPath );
        }
        if ( is_string( $envPath ) && strlen( trim( $envPath ) ) == 0 )
        {
            $envPath = false;
        }

        switch ( self::os() )
        {
            case 'Unix':
            case 'FreeBSD':
            case 'Mac':
            case 'MacOS':
            case 'Darwin':
            case 'Linux':
            case 'SunOS':
                if ( $envPath )
                {
                    $dirs = explode( ':', $envPath );
                    foreach ( $dirs as $dir )
                    {
                        // The @-operator is used here mainly to avoid
                        // open_basedir warnings. If open_basedir (or any other
                        // circumstance) prevents the desired file from being
                        // accessed, it is fine for file_exists() to return
                        // false, since it is useless for use then, anyway.
                        if ( file_exists( "{$dir}/{$fileName}" ) )
                        {
                            return "{$dir}/{$fileName}";
                        }
                    }
                }
                // The @-operator is used here mainly to avoid open_basedir
                // warnings. If open_basedir (or any other circumstance)
                // prevents the desired file from being accessed, it is fine
                // for file_exists() to return false, since it is useless for
                // use then, anyway.
                elseif ( @file_exists( "./{$fileName}" ) )
                {
                    return $fileName;
                }
                break;
            case 'Windows':
                if ( $envPath )
                {
                    $dirs = explode( ';', $envPath );
                    foreach ( $dirs as $dir )
                    {
                        // The @-operator is used here mainly to avoid
                        // open_basedir warnings. If open_basedir (or any other
                        // circumstance) prevents the desired file from being
                        // accessed, it is fine for file_exists() to return
                        // false, since it is useless for use then, anyway.
                        if ( @file_exists( "{$dir}\\{$fileName}.exe" ) )
                        {
                            return "{$dir}\\{$fileName}.exe";
                        }
                    }
                }
                // The @-operator is used here mainly to avoid open_basedir
                // warnings. If open_basedir (or any other circumstance)
                // prevents the desired file from being accessed, it is fine
                // for file_exists() to return false, since it is useless for
                // use then, anyway.
                elseif ( @file_exists( "{$fileName}.exe" ) )
                {
                    return "{$fileName}.exe";
                }
                break;
        }
        return null;
    }

    /**
     * Reset the cached information. 
     * 
     * @return void
     * @access private
     * @ignore
     */
    public static function reset()
    {
        self::$imageIdentify = null;
        self::$imageConvert  = null;
        self::$os            = null;
    }
}
?>
