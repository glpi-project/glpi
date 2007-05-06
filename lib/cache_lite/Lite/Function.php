<?php

/**
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
*/
 
require_once('Cache/Lite.php');

class Cache_Lite_Function extends Cache_Lite
{

    // --- Private properties ---
    
    /**
     * Default cache group for function caching
     *
     * @var string $_defaultGroup
     */
    var $_defaultGroup = 'Cache_Lite_Function';
    
    /**
     * Don't cache the method call when its output contains the string "NOCACHE"
     * 
     * if set to true, the output of the method will never be displayed (because the output is used
     * to control the cache)
     * 
     * @var boolean $_dontCacheWhenTheOutputContainsNOCACHE
     */
    var $_dontCacheWhenTheOutputContainsNOCACHE = false;
    
    /**
     * Don't cache the method call when its result is false
     * 
     * @var boolean $_dontCacheWhenTheResultIsFalse
     */
    var $_dontCacheWhenTheResultIsFalse = false;
    
    /**
     * Don't cache the method call when its result is null
     * 
     * @var boolean $_dontCacheWhenTheResultIsNull
     */
    var $_dontCacheWhenTheResultIsNull = false;
    
    /**
     * Debug the Cache_Lite_Function caching process
     * 
     * @var boolean $_debugCacheLiteFunction
     */
    var $_debugCacheLiteFunction = false;
    
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
    *     'debugCacheLiteFunction' => (bool) debug the caching process,
    *     'defaultGroup' => default cache group for function caching (string),
    *     'dontCacheWhenTheOutputContainsNOCACHE' => (bool) don't cache when the function output contains "NOCACHE",
    *     'dontCacheWhenTheResultIsFalse' => (bool) don't cache when the function result is false,
    *     'dontCacheWhenTheResultIsNull' => (bool don't cache when the function result is null
    * );
    *
    * @param array $options options
    * @access public
    */
    function Cache_Lite_Function($options = array(NULL))
    {
        $availableOptions = array('debugCacheLiteFunction', 'defaultGroup', 'dontCacheWhenTheOutputContainsNOCACHE', 'dontCacheWhenTheResultIsFalse', 'dontCacheWhenTheResultIsNull');
        while (list($name, $value) = each($options)) {
	        if (in_array($name, $availableOptions)) {
	            $property = '_'.$name;
	            $this->$property = $value;
	        }
        }
        reset($options);
        $this->Cache_Lite($options);
    }
    
    /**
    * Calls a cacheable function or method (or not if there is already a cache for it)
    *
    * Arguments of this method are read with func_get_args. So it doesn't appear
    * in the function definition. Synopsis : 
    * call('functionName', $arg1, $arg2, ...)
    * (arg1, arg2... are arguments of 'functionName')
    *
    * @return mixed result of the function/method
    * @access public
    */
    function call()
    {
        $arguments = func_get_args();
        $id = $this->_makeId($arguments);
        $data = $this->get($id, $this->_defaultGroup);
        if ($data !== false) {
            if ($this->_debugCacheLiteFunction) {
                echo "Cache hit !\n";
            }
            $array = unserialize($data);
            $output = $array['output'];
            $result = $array['result'];
        } else {
            if ($this->_debugCacheLiteFunction) {
                echo "Cache missed !\n";
            } 
            ob_start();
            ob_implicit_flush(false);
            $target = array_shift($arguments);
            if (is_array($target)) {
                // in this case, $target is for example array($obj, 'method')
                $object = $target[0];
                $method = $target[1];
                $result = call_user_func_array(array(&$object, $method), $arguments);
            } else {
	            if (strstr($target, '::')) { // classname::staticMethod
	                list($class, $method) = explode('::', $target);
	                $result = call_user_func_array(array($class, $method), $arguments);
	            } else if (strstr($target, '->')) { // object->method
	                // use a stupid name ($objet_123456789 because) of problems where the object
	                // name is the same as this var name
	                list($object_123456789, $method) = explode('->', $target);
	                global $$object_123456789;
	                $result = call_user_func_array(array($$object_123456789, $method), $arguments);
	            } else { // function
	                $result = call_user_func_array($target, $arguments);
	            }
            }
            $output = ob_get_contents();
            ob_end_clean();
            if ($this->_dontCacheWhenTheResultIsFalse) {
                if ((is_bool($result)) && (!($result))) {
                    echo($output);
                    return $result;
                }
            }
            if ($this->_dontCacheWhenTheResultIsNull) {
                if (is_null($result)) {
                    echo($output);
                    return $result;
                }
            }
            if ($this->_dontCacheWhenTheOutputContainsNOCACHE) {
                if (strpos($output, 'NOCACHE') > -1) {
                    return $result;
                }
            }
            $array['output'] = $output;
            $array['result'] = $result;
            $this->save(serialize($array), $id, $this->_defaultGroup);
        }
        echo($output);
        return $result;
    }
    
    /**
    * Drop a cache file
    *
    * Arguments of this method are read with func_get_args. So it doesn't appear
    * in the function definition. Synopsis : 
    * remove('functionName', $arg1, $arg2, ...)
    * (arg1, arg2... are arguments of 'functionName')
    *
    * @return boolean true if no problem
    * @access public
    */
    function drop()
    {
        $id = $this->_makeId(func_get_args());
        $this->remove($id, $this->_defaultGroup);
    }
    
    /**
    * Make an id for the cache
    *
    * @var array result of func_get_args for the call() or the remove() method
    * @return string id
    * @access private
    */
    function _makeId($arguments) 
    {
        $id = serialize($arguments); // Generate a cache id
        if (!$this->_fileNameProtection) {
            $id = md5($id);
            // if fileNameProtection is set to false, then the id has to be hashed
            // because it's a very bad file name in most cases
        }    
        return $id;
    }
    
}

?>
