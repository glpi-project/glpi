Technical choices for Cache_Lite...
-----------------------------------

To begin, the main goals of Cache_Lite :
- performances
- safe use (even on very high traffic or with NFS (file locking doesn't work
            with NFS))
- flexibility (can be used by the end user or as a part of a larger script)


For speed reasons, it has been decided to focus on the file container (the 
faster one). So, cache is only stored in files. The class is optimized for that. 
If you want to use a different cache container, have a look to PEAR/Cache.

For speed reasons too, the class 'Cache_Lite' has do be independant (so no 
'require_once' at all in 'Cache_Lite.php'). But, a conditional include_once
is allowed. For example, when an error is detected, the class include dynamicaly
the PEAR base class 'PEAR.php' to be able to use PEAR::raiseError(). But, in
most cases, PEAR.php isn't included.

For the second goal (safe use), there is three (optional) mecanisms :
- File Locking : seems to work fine (but not with distributed file system
                 like NFS...)
- WriteControl : the cache is read and compared just after being stored
                 (efficient but not perfect)
- ReadControl : a control key (crc32(), md5() ou strlen()) is embeded is the 
                cache file and compared just after reading (the most efficient
                but the slowest)
