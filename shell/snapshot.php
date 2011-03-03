<?php
/**
 * Author: Gordon Knoppe
 * Date: Mar 02, 2011
 *
 * @category    Guidance
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2011 Gordon Knoppe (http://www.guidance.com)
 * @license     http://www.opensource.org/licenses/osl-3.0.php
 */

require_once 'abstract.php';

/**
 * Guidance snapshot shell script
 *
 * @category    Guidance
 * @package     Mage_Shell
 * @author      Gordon Knoppe
 */
class Guidance_Shell_Snapshot extends Mage_Shell_Abstract
{

    /**
     * Perform snapshot
     */
    function _snapshot()
    {
        # Initialize configuration values
        $connection = Mage::getConfig()->getNode('global/resources/default_setup/connection');
        $rootpath = $this->_getRootPath();
        $snapshot = $rootpath.'snapshot';

        # Create the snapshot directory if not exists
        $io = new Varien_Io_File();
        $io->mkdir($snapshot);

        # Create the media archive
        exec("tar -chz -C \"$rootpath\" -f \"{$snapshot}/media.tgz\" media");

        # Dump the database
        exec("mysqldump -h {$connection->host} -u {$connection->username} --password={$connection->password} {$connection->dbname} | gzip > \"{$snapshot}/{$connection->dbname}.sql.gz\"");
    }

    /**
     * Run script
     */
    public function run()
    {
        if ($this->getArg('snapshot')) {
            $this->_snapshot();
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
        global $argv;
        $self = basename($argv[0]);
        return <<<USAGE

Snapshot

Saves a tarball of the media directory and a gzipped database dump
taken with mysqldump

Usage:  php -f $self -- [options]

Options:

  help              This help
  snapshot          Take snapshot
  
USAGE;
    }
}

if (basename($argv[0]) == basename(__FILE__)) {
    $shell = new Guidance_Shell_Snapshot();
    $shell->run();
}
