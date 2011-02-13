magento-shell-tools
===================

Mage_Shell scripts to help manage Magento installations.


Description
-------------------

Magento contains a little-known shell abstract class to manage Magento via CLI.
While there are a few concrete classes, the core doesn't ship with much functionality.
This projects aims to augment the CLI interface and provide some useful tools.



The Tools
-------------------

 - **cache**: All functionality that exists in the admin cache management panel. Plus some more!
   Really useful in deployment scripts.
 - More to be added...


Usage
-------------------
You can use this shell script like the other Magento shells. Help is provided.

Here's example help output from the cache tool:

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

Installation is very simple! Clone/copy the contents of /shell to your Magento /shell directory.


License
-------------------
http://www.opensource.org/licenses/osl-3.0.php

