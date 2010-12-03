magento-shell-cache
===================

Mage_Shell script that interfaces with the Magento cache.


Description
-------------------

Magento base code supplies a set of shell scripts for Magento CLI. Unfortunately, they did not include a shell interface
 for the cache.  magento-shell-cache fills the gaps.

Perfect for use in deployment scripts.


Usage
-------------------

You can use this shell script like the other Magento shells. Help is provided.

    Usage:  php -f cache.php -- [options]
      info                          Show Magento cache types.
      --enable <cachetype>          Enable caching for a cachetype.
      --disable <cachetype>         Disable caching for a cachetype.
      --refresh <cachetype>         Clean cache types.
      --flush <magento|storage>     Flushes slow|fast cache storage.

      cleanmedia                    Clean the JS/CSS cache.
      cleanimages                   Clean the image cache.
      destroy                       Clear all caches.
      help                          This help.

      <cachetype>     Comma separated cache codes or value "all" for all caches
    

Requirements
-------------------

Magento (with shell). The script was developed on EE 1.9, but should work with any Magento version that has the /shell
directory.


Installation
--------------------

Copy cache.php to your Magento /shell directory.


License
-------------------
http://www.opensource.org/licenses/osl-3.0.php

