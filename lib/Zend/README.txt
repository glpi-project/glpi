Welcome to the Zend Framework 1.11 Release! 

RELEASE INFORMATION
---------------
Zend Framework 1.11.11 Release (r24485).
Released on September 29, 2011.

SECURITY NOTICE FOR 1.11.6
--------------------------

This release includes a patch that helps prevent SQL injection attacks
in applications using the MySQL PDO driver of PHP while using non-ASCII
compatible encodings. Developers using ASCII-compatible encodings like
UTF8 or latin1 are not affected by this PHP issue, which is described
in more detail here: http://bugs.php.net/bug.php?id=47802

The PHP Group included a feature in PHP 5.3.6+ that allows any
character set information to be passed as part of the DSN in PDO to
allow both the database as well as the c-level driver to be aware of
which charset is in use which is of special importance when PDO's
quoting mechanisms are utilized, which Zend Framework also relies on.

Our patch ensures that any charset information provided to the Zend_Db
PDO MySQL adapter will be sent to PDO both as part of the DSN as well
as in a SET NAMES query.  This ensures that any developer using ZF on
PHP 5.3.6+ while using non-ASCII compatible encodings is safe from SQL
injection while using the PDO's quoting mechanisms or emulated prepared
statements.

If you are using non-ASCII compatible encodings, like GBK, we strongly
urge you to consider upgrading to at least PHP 5.3.6 and use
Zend Framework version 1.11.6 or 1.10.9

NEW FEATURES
------------

Mobile Support:

    Zend Framework 1.11 marks the first release with explicit support
    for mobile devices, via the new component Zend_Http_UserAgent. This
    component was developed by Raphael Carles, CTO of Interakting.
    
    Zend_Http_UserAgent performs two responsibilities:
    
     * User-Agent detection
     * Device capabilities detection, based on User-Agent
    
    The component includes a "features" adapter mechanism that allows
    developers to tie into different backends for the purpose of
    discovering device capabilities. Currently, ships with adapters for
    the WURFL (Wireless Universal Resource File) API, TeraWURFL, and
    DeviceAtlas.
    
     * Note: Luca Passani, author and lead of the WURFL project, has
       provided an exemption to Zend Framework to provide a non-GPL
       adapter accessing the WURFL PHP API.
    
    Additional hooks into the component are provided via a
    Zend_Application resource plugin, and a Zend_View helper, allowing
    developers the ability to return output customized for the detected
    device (e.g., alternate layouts, alternate images, Flash versus
    HTML5 support, etc.).

Zend_Cloud: SimpleCloud API:

    During ZendCon 2009, Zend announced a prototype of the SimpleCloud
    API. This API was to provide hooks into cloud-based document
    storage, queue services, and file storage.

    Zend Framework 1.11.0 markes the first official, stable release of
    Zend_Cloud, Zend Framework's PHP version of the SimpleCloud API.
    Current support includes:

    * Document Services:
      - Amazon SimpleDB
      - Windows Azure's Table Storage
    * Queue Services:
      - Amazon Simple Queue Service (SQS)
      - Windows Azure's Queue Service
      - All adapters supported by Zend_Queue:
        * Zend Platform JobQueue
        * Memcacheq
        * Relational Database
        * ActiveMQ
    * Storage Services:
      - Amazon Simple Storage Service (S3)
      - Windows Azure's Blog Storage
      - Nirvanix
      - Local filesystem

    When using any of the SimpleCloud APIs, your code will be portable
    across the various adapters provided, allowing you to pick and
    choose your services, as well as try different services until you
    find one that suits your application or business needs.
    Additionally, if you find you need to code adapter-specific
    features, you can drop down to the specific adapter in order to do
    so.

    More adapters will be arriving in the coming months, giving you even
    more options!

    We thank Wil Sinclair and Stas Malyshev for their assistance in the
    initial releases of Zend_Cloud.

Security:

    Several classes in Zend Framework were patched to eliminate the
    potential for leaking timing information from the direct comparison
    of sensitive data such as plaintext passwords or cryptographic
    signatures to user input. These leaks arise from the normal process
    of comparing any two strings in PHP. The nature of the leaks is that
    strings are often compared byte by byte, with a negative result
    being returned early as soon as any set of non-matching bytes is
    detected. The more bytes that are equal (starting from the first
    byte) between both sides of the comparison, the longer it takes for
    a final result to be returned. Based on the time it takes to return
    a negative or positive result, it is possible that an attacker
    could, over many samples of requests, craft a string that compares
    positively to another secret string value known only to a target
    server simply by guessing the string one byte at a time and
    measuring each guess' execution time. This server secret could be a
    plaintext password or the correct cryptographic signature of a
    request the attacker wants to execute, such as is used in several
    open protocols including OpenID and OAuth. This could obviously
    enable an attacker to gain sufficient information to perform a
    secondary attack such as masquerading as an authenticated user.

    This form of attack is known as a Remote Timing Attack. Timing
    Attacks have been problematic in the past but to date have been very
    difficult to perform remotely over the internet due to the
    interference of network jitter which limits their effectiveness in
    resolving very small timing differences. While the internet still
    poses a challenge to performing successful Timing Attacks against a
    remote server, the increasing use of frameworks on local networks
    and in cloud computing, where network jitter may be significantly
    reduced, raises the distinct possibility that remote Timing Attacks
    will become feasible against ever smaller timing information leaks,
    such as those leaked when comparing any two strings. As a
    precaution, the applied changes implement a fixed time comparison
    for several classes which would be attractive targets in any
    potential remote Timing Attack. A fixed time comparison function
    does not leak any timing information useful to an attacker thus
    proactively preventing any future vulnerability to these forms of
    attack.

    We thank Padraic Brady for his efforts in identifying and patching
    these vulnerabilities.

SimpleDB Support:

    Zend Framework has provided support for Amazon's Simple Storage
    Service (S3), Simple Queue Service (SQS), and Elastic Cloud Compute
    (EC2) platforms for several releases. Zend Framework 1.11.0 adds
    support for SimpleDB, Amazon's non-relational document storage
    database offering. Support is available for all SimpleDB operations
    via Zend_Service_Amazon_SimpleDb.
    
    Zend Framework's SimpleDB adapter was originally written by Wil
    Sinclair.

eBay Findings API Support:

    eBay has an extensive REST API, allowing developers to build
    applications interacting with their extensive data. Zend Framework
    1.11.0 includes Zend_Service_Ebay_Findings, which provides complete
    support for the eBay Findings API. This API allows developers to
    query eBay for details on active auctions, using categories or
    keywords.

    Zend_Service_Ebay was contributed by Renan de Lima and Ramon
    Henrique Ornelas.

New Configuration Formats:

    Zend_Config has been a quite popular component in Zend Framework,
    and has offerred adapters for PHP arrays, XML, and INI configuration
    files. Zend Framework 1.11.0 now offers two additional configuration
    formats: YAML and JSON.

    Zend_Config_Yaml provides a very rudimentary YAML-parser that should
    work with most configuration formats. However, it also allows you to
    specify an alternate YAML parser if desired, allowing you to lever
    tools such as PECL's ext/syck or Symfony's YAML component, sfYaml.

    Zend_Config_Json leverages the Zend_Json component, and by extension
    ext/json.

    Both adapters have support for PHP constants, as well as provide the
    ability to write configuration files based on configuration objects.

    Stas Malyshev created both adapters for Zend Framework;
    Zend_Config_Json also had assistance from Sudheer Satyanarayana.

URL Shortening:

    Zend_Service_ShortUrl was added for this release. The component
    provides a simple interface for use with most URL shortening
    services, defining simply the methods "shorten" and "unshorten".
    Adapters for the services http://is.gd, http://jdem.cz,
    http://metamark.net, and http://tinyurl.com, are provided with this
    release. 

    Zend_Service_ShortUrl was contributed by Martin Hujer.

Additional View Helpers:

    Several new view helpers are now exposed:

    * Zend_View_Helper_UserAgent ties into the Zend_Http_UserAgent
      component, detailed above. It gives you access to the UserAgent
      instance, allowing you to query for the device and capabilities.
    * Zend_View_Helper_TinySrc is an additional portion of Zend
      Framework's mobile offering for version 1.11.0. The helper ties
      into the TinySrc API, allowing you to a) provide device-specific
      image sizes and formats for your site, and b) offload generation
      of those images to this third-party service. The helper creates
      img tags pointing to the service, and provides options for
      specifying adaptive sizing and formats.
    * Zend_View_Helper_Gravatar ties into the Gravatar API, allowing you
      to provide avatar images for registered users that utilize the
      Gravatar service. This helper was contributed by Marcin Morawski.

A detailed list of all features and bug fixes in this release may be found at:

http://framework.zend.com/changelog/

SYSTEM REQUIREMENTS
-------------------

Zend Framework requires PHP 5.2.4 or later. Please see our reference
guide for more detailed system requirements:

http://framework.zend.com/manual/en/requirements.html

INSTALLATION
------------

Please see INSTALL.txt.

QUESTIONS AND FEEDBACK
----------------------

Online documentation can be found at http://framework.zend.com/manual.
Questions that are not addressed in the manual should be directed to the
appropriate mailing list:

http://framework.zend.com/wiki/display/ZFDEV/Mailing+Lists

If you find code in this release behaving in an unexpected manner or
contrary to its documented behavior, please create an issue in the Zend
Framework issue tracker at:

http://framework.zend.com/issues

If you would like to be notified of new releases, you can subscribe to
the fw-announce mailing list by sending a blank message to
fw-announce-subscribe@lists.zend.com.

LICENSE
-------

The files in this archive are released under the Zend Framework license.
You can find a copy of this license in LICENSE.txt.

ACKNOWLEDGEMENTS
----------------

The Zend Framework team would like to thank all the contributors to the Zend
Framework project, our corporate sponsor, and you, the Zend Framework user.
Please visit us sometime soon at http://framework.zend.com.
