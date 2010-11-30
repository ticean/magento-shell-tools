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
        if ($this->getArg('info')) {
            //TODO: Add back the Beta.
            foreach($this->_getCacheTypes() as $cache) {
                echo $cache->id . ': ' . $cache->cache_type . "\n";
            }
        } else if ($this->getArg('clear') || $this->getArg('clearall')) {
            if ($this->getArg('clear')) {
                $cachetypes = $this->_parseCacheTypeString($this->getArg('clear'));
            } else {
                $cachetypes = $this->_parseCacheTypeString('all');
            }
            foreach ($cachetypes as $cachekey => $cachetype) {
                /* @var $process Mage_Index_Model_Process */
                try {
                    Mage::app()->cleanCache($cachekey);
                    echo $cachekey . " cache cleared.\n";
                } catch (Mage_Core_Exception $e) {
                    echo $e->getMessage() . "\n";
                } catch (Exception $e) {
                    echo $cachekey . " cache unknown error:\n";
                    echo $e . "\n";
                }
            }

        } else if ($this->getArg('status')) {
            if ($this->getArg('status')) {
                $cachetypes = $this->_parseCacheTypeString($this->getArg('status'));
            } else {
                $cachetypes = $this->_parseCacheTypeString('all');
            }
            foreach ($cachetypes as $cachekey) {
                try {
                    Mage::app()->cleanCache($cachekey);
                    echo $cachetype . " cache cleared.\n";
                } catch (Mage_Core_Exception $e) {
                    echo $e->getMessage() . "\n";
                } catch (Exception $e) {
                    echo $cachetype . " cache unknown error:\n";
                    echo $e . "\n";
                }
            }

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
  --clear <cachetype>           Clear cache.
  clearall                      Clear cache for all cachetypes.
  help                          This help

  <cachetype>     Comma separated cache codes or value "all" for all indexers

USAGE;
    }
}

$shell = new Guidance_Shell_Cache();
$shell->run();

