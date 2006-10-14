<?php

/**
* This class extends Cache_Lite and offers a cache system driven by a master file
*
* With this class, cache validity is only dependent of a given file. Cache files
* are valid only if they are older than the master file. It's a perfect way for
* caching templates results (if the template file is newer than the cache, cache
* must be rebuild...) or for config classes...
* There are some examples in the 'docs/examples' file
* Technical choices are described in the 'docs/technical' file
*
* @package Cache_Lite
* @version $Id: File.php,v 1.3 2005/12/04 16:03:55 fab Exp $
* @author Fabien MARTY <fab@php.net>
*/
 
require_once('Cache/Lite.php');

class Cache_Lite_File extends Cache_Lite
{

    // --- Private properties ---
    
    /**
    * Complete path of the file used for controlling the cache lifetime
    *
    * @var string $_masterFile
    */
    var $_masterFile = '';
    
    /**
    * Masterfile mtime
    *
    * @var int $_masterFile_mtime
    */
    var $_masterFile_mtime = 0;
    
    // --- Public methods ----
    
    /**
    * Constructor
    *
    * $options is an assoc. To have a look at availables options,
    * see the constructor of the Cache_Lite class in 'Cache_Lite.php'
    *
    * Comparing to Cache_Lite constructor, there is another option :
    * $options = array(
    *     (...) see Cache_Lite constructor
    *     'masterFile' => complete path of the file used for controlling the cache lifetime(string)
    * );
    *
    * @param array $options options
    * @access public
    */
    function Cache_Lite_File($options = array(NULL))
    {   
        $options['lifetime'] = 0;
        $this->Cache_Lite($options);
        if (isset($options['masterFile'])) {
            $this->_masterFile = $options['masterFile'];
        } else {
            return $this->raiseError('Cache_Lite_File : masterFile option must be set !');
        }
        if (!($this->_masterFile_mtime = @filemtime($this->_masterFile))) {
            return $this->raiseError('Cache_Lite_File : Unable to read masterFile : '.$this->_masterFile, -3);
        }
    }
    
    /**
    * Test if a cache is available and (if yes) return it
    *
    * @param string $id cache id
    * @param string $group name of the cache group
    * @return string data of the cache (or false if no cache available)
    * @access public
    */
    function get($id, $group = 'default') 
    {
        if ($data = parent::get($id, $group, true)) {
            if ($filemtime = $this->lastModified()) {
                if ($filemtime > $this->_masterFile_mtime) {
                    return $data;
                }
            }
        }
        return false;
    }

}

?>
