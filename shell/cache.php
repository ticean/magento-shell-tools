<?php
/**
 * Author: Ticean Bennett
 * Date: Nov 18, 2010
 * Time: 12:54:29 AM
 *
 * Mage_Shell script that interfaces with the Magento cache. 
 *
 *
 * @category    Guidance
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

require_once 'abstract.php';

/**
 * Magento Cache Shell Script
 *
 * @category    Guidance
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Guidance_Shell_Cache extends Mage_Shell_Abstract
{
    /**
     * Parse string with indexers and return array of indexer instances
     *
     * @param string $string
     * @return array
     */
    protected function _parseCacheTypeString($string)
    {
        $cachetypes = array();
        if ($string == 'all') {
            $collection = $this->_getCacheTypeCodes();
            foreach ($collection as $cache) {
                $cachetypes[] = $cache;
            }
        } else if (!empty($string)) {
            $codes = explode(',', $string);
            foreach ($codes as $code) {
                // do any validation on cache type?
                #$process = $this->_getIndexer()->getProcessByCode(trim($code));
                #if (!$process) {
                #    echo 'Warning: Unknown indexer with code ' . trim($code) . "\n";
                #} else {
                    $cachetypes[] = $code;
                #}
            }
        }

        return $cachetypes;
    }

    /**
     * Gets Magento cache types.
     * @return
     */
    private function _getCacheTypes() {
        //return Mage::helper('core')->getCacheTypes();
        return Mage::getModel('core/cache')->getTypes();
    }

    /**
     * Gets an array of cache type code.
     * @return array Cache type codes.
     */
    private function _getCacheTypeCodes() {
        return array_keys($this->_getCacheTypes());
    }

    /**
     * Gets Magento cache types.
     * @return
     */
    private function _getCacheBetaTypes() {
        return Mage::helper('core')->getCacheBetaTypes();
    }

    /**
     * Gets an array of cache type code.
     * @return array Cache type codes.
     */
    private function _getCacheBetaTypeCodes() {
        return array_keys($this->_getCacheBetaTypes());
    }

    /**
     * Run script
     *
     */
    public function run()
    {
        // info
        if ($this->getArg('info')) {
            foreach($this->_getCacheTypes() as $cache) {
                echo $cache->id . ': ' . $cache->cache_type . "\n";
            }
        } else if ($this->getArg('status')) {
            //TODO: Implement status.
            echo '--status arg is not yet implemented.' . "\n";

        // --enable
        } else if ($this->getArg('enable')) {
            $types = $this->_parseCacheTypeString($this->getArg('enable'));
            $allTypes = Mage::app()->useCache();

            $updatedTypes = 0;
            foreach ($types as $code) {
                if (empty($allTypes[$code])) {
                    $allTypes[$code] = 1;
                    $updatedTypes++;
                }
            }
            if ($updatedTypes > 0) {
                Mage::app()->saveUseCache($allTypes);
                echo "$updatedTypes cache type(s) enabled.\n";
            }

        // --disable
        } else if ($this->getArg('disable')) {
            $types = $this->_parseCacheTypeString($this->getArg('disable'));
            $allTypes = Mage::app()->useCache();

            $updatedTypes = 0;
            foreach ($types as $code) {
                if (!empty($allTypes[$code])) {
                    $allTypes[$code] = 0;
                    $updatedTypes++;
                }
                $tags = Mage::app()->getCacheInstance()->cleanType($code);
            }
            if ($updatedTypes > 0) {
                Mage::app()->saveUseCache($allTypes);
                echo "$updatedTypes cache type(s) disabled.\n";
            }

        // --flush
        } else if ($this->getArg('flush')) {
            $type = $this->getArg('flush');
            if($type == 'magento') {
                try {
                    Mage::app()->cleanCache();
                    echo "The Magento cache storage has been flushed.\n";
                } catch (Exception $e) {
                    echo "Exception:\n";
                    echo $e . "\n";
                }
            } else if($type == 'storage') {
                try {
                    Mage::app()->getCacheInstance()->flush();
                    echo "The cache storage has been flushed.\n";
                } catch (Exception $e) {
                    echo "Exception:\n";
                    echo $e . "\n";
                }
            } else {
                echo "The flush type must be magento|storage\n";
            }

        // --refresh
        } else if ($this->getArg('refresh')) {
            if ($this->getArg('refresh')) {
                $types = $this->_parseCacheTypeString($this->getArg('refresh'));
            } else {
                $types = $this->_parseCacheTypeString('all');
            }
            $updatedTypes = 0;
            if (!empty($types)) {
                foreach ($types as $type) {
                    try {
                        $tags = Mage::app()->getCacheInstance()->cleanType($type);
                        $updatedTypes++;
                    } catch (Exception $e) {
                        echo $type . " cache unknown error:\n";
                        echo $e . "\n";
                    }
                }
            }
            if ($updatedTypes > 0) {
                echo "$updatedTypes cache type(s) refreshed.\n";
            }

        // cleanmedia
        } else if ($this->getArg('cleanmedia')) {
            try {
                Mage::getModel('core/design_package')->cleanMergedJsCss();
                Mage::dispatchEvent('clean_media_cache_after');
                echo "The JavaScript/CSS cache has been cleaned.\n";
            }
            catch (Exception $e) {
                echo "An error occurred while clearing the JavaScript/CSS cache.\n";
                echo $e->toString() . "\n";
            }

        // cleanimages    
        } else if ($this->getArg('cleanimages')) {
            try {
                Mage::getModel('catalog/product_image')->clearCache();
                Mage::dispatchEvent('clean_catalog_images_cache_after');
                echo "The image cache was cleaned.\n";
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }

        // help
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f cache.php -- [options]
  info                          Show Magento cache types.
  --status <cachetype>          Show cache status
  --enable <cachetype>          Enable caching for a cachetype.
  --disable <cachetype>         Disable caching for a cachetype.
  --refresh <cachetype>         Clean cache types.
  --flush <magento|storage>     Flushes slow|fast cache storage.

  cleanmedia                    Clean the JS/CSS cache.
  cleanimages                   Clean the image cache.
  destroy                       Clear all caches.
  help                          This help.

  <cachetype>     Comma separated cache codes or value "all" for all caches

USAGE;
    }
}

$shell = new Guidance_Shell_Cache();
$shell->run();

