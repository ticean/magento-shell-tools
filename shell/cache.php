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
 * @copyright   Copyright (c) 2010 Guidance Solutions, Inc. (http://www.guidance.com)
 * @license     http://www.opensource.org/licenses/osl-3.0.php
 */

require_once 'abstract.php';

/**
 * Magento Cache Shell Script
 *
 * @category    Guidance
 * @package     Mage_Shell
 * @author      Ticean Bennett
 */
class Guidance_Shell_Cache extends Mage_Shell_Abstract
{
    /**
     * Returns a list of cachetypes, and their current cache status.
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
     * Gets a list of invalidated cache types that should be refreshed.
     * @return array Array of invalidated types.
     */
    private function _getInvalidatedTypes() {
        return Mage::getModel('core/cache')->getInvalidatedTypes();
    }

    /**
     * Gets an array of cache type code.
     * @return array Cache type codes.
     */
    private function _getCacheBetaTypeCodes() {
        return array_keys($this->_getCacheBetaTypes());
    }

    /**
     * Returns a list of cache types.
     * @return void
     */
    public function info() {
        $invalidTypes = $this->_getInvalidatedTypes();
        foreach($this->_getCacheTypes() as $cache) {
            $enabled = ($cache->status)? 'Enabled':'Disabled';
            if($enabled=='Enabled') {
                $invalid = (array_key_exists($cache, $invalidTypes))? 'Invalid':'Valid';
            } else {
                $invalid = 'N/A';
            }

            echo sprintf('%-16s', $cache->id);
            echo sprintf('%-12s', $enabled);
            echo sprintf('%-10s', $invalid);
            echo  $cache->cache_type . "\n";
        }
    }

    /**
     * Enables caching for provided array of cache types.
     * @param  $types An array of cache types.
     * @return void
     */
    public function enable($types) {
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
    }

    /**
     * Disables caching for provided array of cache types.
     * @param  $types An array of cache types.
     * @return void
     */
    public function disable($types) {
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
    }

    /**
     * Flushes the cache storage (Magentos "fast" caching). Flushing cache storage can clear other shared cache data on
     * the storage. Please take care when using this method..
     * @return void
     */
    public function flushAll() {
        try {
            Mage::app()->getCacheInstance()->flush();
            echo "The cache storage has been flushed.\n";
        } catch (Exception $e) {
            echo "Exception:\n";
            echo $e . "\n";
        }
    }

    /**
     * Flushes the Magento cache (Magentos "slow" caching).
     * @return void
     */
    public function flushSystem() {
        try {
            Mage::app()->cleanCache();
            echo "The Magento cache storage has been flushed.\n";
        } catch (Exception $e) {
            echo "Exception:\n";
            echo $e . "\n";
        }
    }

    /**
     * Refreshes caches for the provided cache types.
     * @param  $types
     * @return void
     */
    public function refresh($types) {
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
    }

    /**
     * Clears the merged JS/CSS cache.
     * @return void
     */
    public function cleanMedia() {
        try {
            Mage::getModel('core/design_package')->cleanMergedJsCss();
            Mage::dispatchEvent('clean_media_cache_after');
            echo "The JavaScript/CSS cache has been cleaned.\n";
        }
        catch (Exception $e) {
            echo "An error occurred while clearing the JavaScript/CSS cache.\n";
            echo $e->toString() . "\n";
        }
    }

    /**
     * Clears the image cache.
     * @return void
     */
    public function cleanImages() {
        try {
            Mage::getModel('catalog/product_image')->clearCache();
            Mage::dispatchEvent('clean_catalog_images_cache_after');
            echo "The image cache was cleaned.\n";
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
    }

    /**
     * Clears all possible Magento cache in one command. Be careful, This will DESTROY the cache!
     * @return void
     */
    public function destroy() {
        $types = $this->_parseCacheTypeString('all');
        $this->refresh($types);
        $this->cleanImages();
        $this->cleanMedia();
        $this->flushSystem();
        $this->flushAll();
    }

    /**
     * Run script
     *
     */
    public function run()
    {
        // info
        if ($this->getArg('info')) {
            $this->info();
        } else if ($this->getArg('enable')) {
            $types = $this->_parseCacheTypeString($this->getArg('enable'));
            $this->enable($types);

        // --disable
        } else if ($this->getArg('disable')) {
            $types = $this->_parseCacheTypeString($this->getArg('disable'));
            echo print_r($types, true);
            $this->disable($types);

        // --flush
        } else if ($this->getArg('flush')) {
            $type = $this->getArg('flush');
            if($type == 'magento') {
                $this->flushSystem();
            } else if($type == 'storage') {
                $this->flushAll();
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
            $this->refresh($types);

        // cleanmedia
        } else if ($this->getArg('cleanmedia')) {
            $this->cleanMedia();

        // cleanimages
        } else if ($this->getArg('cleanimages')) {
            $this->cleanImages();

        // destroy
        } else if ($this->getArg('destroy')) {
            $this->destroy();

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

