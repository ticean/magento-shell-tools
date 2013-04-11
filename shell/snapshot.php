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

/**
 * Added import / export functionality by Lucas van Staden (lucas@dhmedia.com.au)
 */

require_once 'abstract.php';

/**
 * Guidance snapshot shell script
 *
 * @category    Guidance
 * @package     Mage_Shell
 * @author      Gordon Knoppe
 */
class Guidance_Shell_Snapshot extends Mage_Shell_Abstract {

    function _import() {
        if (!Mage::isInstalled()) {
            echo "Application is not installed yet, please complete install wizard first.";
            exit;
        }


        $stores = mage::app()->getStores(true);
        $frontStore = array_pop($stores);

        if (!$this->getArg('name')) {
            $dbname = $_SERVER['USER'] . "_" . preg_replace("/[^A-Za-z0-9 ]/", '_', strtolower($frontStore->getName())) . "_" . $this->getArg('import');
        } else {
            $dbname = $this->getArg('name');
        }
        
        # Initialize configuration values
        $connection = Mage::getConfig()->getNode('global/resources/default_setup/connection');

        if ($dname == $connection->dbname) {
            die('Sorry, you cannot import directly into the current active database {$dbname}. Things WILL break');
        }

        $rootpath = $this->_getRootPath();
        $snapshot = $rootpath . 'snapshot';

        if ($this->getArg('drop')) {
            passthru("echo Y | mysqladmin -h {$connection->host} -u {$connection->username} --password={$connection->password} drop {$dbname}");
        }
        // create the db
        passthru("mysqladmin -h {$connection->host} -u {$connection->username} --password={$connection->password} create {$dbname}");
        // import structure
        echo "Importing structure...\n";
        passthru("zcat {$snapshot}/{$this->getArg('import')}_structure.sql.gz | mysql  -h {$connection->host} -u {$connection->username} --password={$connection->password} {$dbname}");
        // import data
        echo "Importing data...\n";
        passthru("zcat {$snapshot}/{$this->getArg('import')}_data.sql.gz | mysql -h {$connection->host} -u {$connection->username} --password={$connection->password} {$dbname}");


        //lets manipulate the database.
        // magento's base config model merges tags, thus having multiple tags of the same name in our import tag does not work right.
        // parse the file ourself, so we can use the nodes correctly
        // since magento is connected to your current db, let make a new zend connection to the new db

        try {
            $db = Zend_Db::factory('Pdo_Mysql', array(
                        'host' => $connection->host,
                        'username' => $connection->username,
                        'password' => $connection->password,
                        'dbname' => $dbname
            ));
            $db->getConnection();
        } catch (Zend_Db_Adapter_Exception $e) {
            mage::throwException($e);
            die($e->getMessage());
        } catch (Zend_Exception $e) {
            mage::throwException($e);
            die($e->getMessage());
        }


        $xmlPath = Mage::getBaseDir('etc') . DS . 'local.xml';
        $xmlObj = simplexml_load_string(file_get_contents($xmlPath));
        foreach ($xmlObj->global->resources->snapshots->uat->import as $key => $importUpdates) {
            foreach ($importUpdates as $tableName => $changes) {
                foreach ($changes as $changeKey => $updateData) {
                    switch ($changeKey) {
                        case 'update':
                            try {
                                $db->getProfiler()->setEnabled(true);
                                $where = $updateData->where->field . " = '" . $updateData->where->value . "'";
                                $db->update($tableName, array((string) $updateData->set->field => (string) $updateData->set->value), $where);
                            } catch (Exception $e) {
                                echo"Failed to do an update:";
                                Zend_Debug::dump($db->getProfiler()->getLastQueryProfile()->getQuery());
                                Zend_Debug::dump($db->getProfiler()->getLastQueryProfile()->getQueryParams());
                                $db->getProfiler()->setEnabled(false);
                            }
                            break;
                        default:
                            echo "import method {$changeKey} not yet implemented!";
                            break;
                    }
                }
            }
        }
    }

    /**
     * Perform snapshot
     */
    function _snapshot() {
        # Check to make sure Magento is installed
        if (!Mage::isInstalled()) {
            echo "Application is not installed yet, please complete install wizard first.";
            exit;
        }

        # Initialize configuration values
        $connection = Mage::getConfig()->getNode('global/resources/snapshots/' . $this->getArg('export') . '/connection');
        if (!$connection) {
            echo "Could not find a snapshot configuration for " . $this->getArg('export');
            echo $this->usageHelp();
            die();
        }

        $structureOnly = Mage::getConfig()->getNode('global/resources/snapshots/' . $this->getArg('export') . '/structure');
        $ignoreTables = " --ignore-table={$connection->dbname}." . implode(" --ignore-table={$connection->dbname}.", explode(',', $structureOnly->ignore_tables));

        $rootpath = $this->_getRootPath();
        $snapshot = $rootpath . 'snapshot';

        # Create the snapshot directory if not exists
        $io = new Varien_Io_File();
        $io->mkdir($snapshot);

        if ($this->getArg('include-images')) {
            # Create the media archive
            passthru("ssh {$connection->ssh_username}@{$connection->host} tar -chz -C \"$rootpath\" -f \"~/media.tgz\" media");
            passthru("scp {$connection->ssh_username}@{$connection->host}:~/media.tgz {$snapshot}");
            passthru("ssh {$connection->ssh_username}@{$connection->host} 'rm -rf ~/media.tgz'");
        }

        # Dump the database
        echo "Extracting structure...\n";
        passthru("ssh {$connection->ssh_username}@{$connection->host} 'mysqldump -d -h localhost -u {$connection->db_username} --password={$connection->db_password} {$connection->dbname} | gzip > \"{$this->getArg('export')}_structure.sql.gz\"'");
        passthru("scp {$connection->ssh_username}@{$connection->host}:~/{$this->getArg('export')}_structure.sql.gz {$snapshot}");
        passthru("ssh {$connection->ssh_username}@{$connection->host} 'rm -rf ~/{$this->getArg('export')}_structure.sql.gz'");

        echo "Extracting data...\n";
        passthru("ssh {$connection->ssh_username}@{$connection->host} 'mysqldump -h localhost -u {$connection->db_username} --password={$connection->db_password} {$connection->dbname} $ignoreTables | gzip > \"{$this->getArg('export')}_data.sql.gz\"'");
        passthru("scp {$connection->ssh_username}@{$connection->host}:~/{$this->getArg('export')}_data.sql.gz {$snapshot}");
        passthru("ssh {$connection->ssh_username}@{$connection->host} 'rm -rf ~/{$this->getArg('export')}_data.sql.gz'");
        
        echo "Done\n";
    }

    /**
     * Run script
     */
    public function run() {
        set_time_limit(0);
        if ($this->getArg('export')) {
            $this->_snapshot();
        } else if ($this->getArg('import')) {
            $this->_import();
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp() {
        global $argv;
        $self = basename($argv[0]);
        return <<<USAGE

Snapshot

Saves a tarball of the media directory and a gzipped database dump
taken with mysqldump

Usage:  php -f $self -- [options]

Options:

  help              This help
                
  --export [uat|live|???]  Take snapshot of the given remote server
  --import [uat|live|???] <dbname>  [import options] Impot the given snapshot
  
  Import Options: 
  --name <name> Name of new import db. If none given, current shell user_[uat|live|???] will be used.              
  --drop    drop the import database if exists
      
  include-images  Also bring down images folder              
  
USAGE;
    }

}

if (basename($argv[0]) == basename(__FILE__)) {
    $shell = new Guidance_Shell_Snapshot();
    $shell->run();
}
