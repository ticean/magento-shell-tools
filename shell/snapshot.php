<?php

/**
 * Author: Lucas van Satden
 */
require_once 'abstract.php';

/**
 * Snapshot shell script
 * @author      Lucas van Staden
 */
class DogHouse_Shell_Snapshot extends Mage_Shell_Abstract {

    protected $_includeMage = false;
    protected $_localDB = null;
    protected $_configXml = null;
    protected $_snapshotXml = null;

    public function __construct() {
        parent::__construct();
//        require_once $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
        $localXML = $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml';
        $this->_configXml = simplexml_load_string(file_get_contents($localXML));
        $snapshotXml = $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'snapshot.xml';
        if(!file_exists($snapshotXml)) {
            die("Your config file is missing. {$snapshotXml}");
        }
        $this->_snapshotXml = simplexml_load_string(file_get_contents($snapshotXml));
    }

    /**
     * Run script
     */
    public function run() {
        set_time_limit(0);
        if ($this->getArg('export-remote')) {
            $this->_export($this->getArg('export-remote'));
        } else if ($this->getArg('import')) {
            $this->_import($this->getArg('import'));
        } else if ($this->getArg('fetch')) {
            $this->_export($this->getArg('fetch'));
            $this->_import($this->getArg('fetch'));
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Perform snapshot
     */
    function _export($profile) {
        $timestamp = time();
        $connection = $this->_snapshotXml->$profile->connection;
        if (!$connection) {
            echo "Could not find a snapshot configuration for " . $profile;
            echo $this->usageHelp();
            die();
        }

        if (empty($connection->ssh_port)) {
            $connection->ssh_port = 22;
        }

        $structureOnly = $this->_snapshotXml->$profile->structure;
        $ignoreTables = " --ignore-table={$connection->dbname}." . implode(" --ignore-table={$connection->dbname}.", explode(',', $structureOnly->ignore_tables));

        $rootpath = $this->_getRootPath();
        $snapshot = $rootpath . 'snapshot';

        # Create the snapshot directory if not exists
        if (!file_exists($snapshot)) {
            mkdir($snapshot);
        }

        # Dump the database
        echo "Extracting structure...\n";
        passthru("ssh -p {$connection->ssh_port} {$connection->ssh_username}@{$connection->host} 'mysqldump -d -h localhost -u {$connection->db_username} --password={$connection->db_password} {$connection->dbname} | gzip > \"{$profile}_structure_" . $timestamp . ".sql.gz\"'");
        passthru("scp -P {$connection->ssh_port} {$connection->ssh_username}@{$connection->host}:~/{$profile}_structure_" . $timestamp . ".sql.gz {$snapshot}/{$profile}_structure.sql.gz");
        passthru("ssh -p {$connection->ssh_port} {$connection->ssh_username}@{$connection->host} 'rm -rf ~/{$profile}_structure_" . $timestamp . ".sql.gz'");

        echo "Extracting data...\n";
        passthru("ssh -p {$connection->ssh_port} {$connection->ssh_username}@{$connection->host} 'mysqldump -h localhost -u {$connection->db_username} --password={$connection->db_password} {$connection->dbname} $ignoreTables | gzip > \"{$profile}_data_" . $timestamp . ".sql.gz\"'");
        passthru("scp -P {$connection->ssh_port} {$connection->ssh_username}@{$connection->host}:~/{$profile}_data_" . $timestamp . ".sql.gz {$snapshot}/{$profile}_data.sql.gz");
        passthru("ssh -p {$connection->ssh_port} {$connection->ssh_username}@{$connection->host} 'rm -rf ~/{$profile}_data_" . $timestamp . ".sql.gz'");

        echo "Done\n";
    }

    function _import($profile) {

        $rootpath = $this->_getRootPath();
        $snapshot = $rootpath . 'snapshot';

        echo "Creating Database: " . $this->_configXml->global->resources->default_setup->connection->dbname . "\n";
        passthru("mysqladmin -h {$this->_configXml->global->resources->default_setup->connection->host} -u {$this->_configXml->global->resources->default_setup->connection->username} --password={$this->_configXml->global->resources->default_setup->connection->password} create {$this->_configXml->global->resources->default_setup->connection->dbname}");
        
        // import structure
        $pv = "";
        $hasPv = shell_exec("which pv");
        if (!empty($hasPv)) {
            echo "Structure...\n";
            echo "Extracting...\n";
            passthru("gzip -d {$snapshot}/{$profile}_structure.sql.gz");
            echo "Importing...\n";
            passthru("pv {$snapshot}/{$profile}_structure.sql | mysql  -h {$this->_configXml->global->resources->default_setup->connection->host} -u {$this->_configXml->global->resources->default_setup->connection->username} --password={$this->_configXml->global->resources->default_setup->connection->password} {$this->_configXml->global->resources->default_setup->connection->dbname}");
            echo "Repacking...\n";
            passthru("gzip {$snapshot}/{$profile}_structure.sql");
            echo "Data...\n";
            echo "Extracting...\n";
            passthru("gzip -d {$snapshot}/{$profile}_data.sql.gz");
            echo "Importing...\n";
            passthru("pv {$snapshot}/{$profile}_data.sql | mysql  -h {$this->_configXml->global->resources->default_setup->connection->host} -u {$this->_configXml->global->resources->default_setup->connection->username} --password={$this->_configXml->global->resources->default_setup->connection->password} {$this->_configXml->global->resources->default_setup->connection->dbname}");
            echo "Repacking...\n";
            passthru("gzip {$snapshot}/{$profile}_data.sql");
        } else {
            echo "install pv ( sudo apt-get install pv ) to get a progress indicator for importing!\n";

            echo "Importing structure...\n";
            passthru("zcat {$snapshot}/{$profile}_structure.sql.gz | {$pv} mysql  -h {$this->_configXml->global->resources->default_setup->connection->host} -u {$this->_configXml->global->resources->default_setup->connection->username} --password={$this->_configXml->global->resources->default_setup->connection->password} {$this->_configXml->global->resources->default_setup->connection->dbname}");
            // import data
            echo "Importing data...\n";
            passthru("zcat {$snapshot}/{$profile}_data.sql.gz | {$pv} mysql -h {$this->_configXml->global->resources->default_setup->connection->host} -u {$this->_configXml->global->resources->default_setup->connection->username} --password={$this->_configXml->global->resources->default_setup->connection->password} {$this->_configXml->global->resources->default_setup->connection->dbname}");
        }

        // lets manipulate the database.
        // at this pont we can instantiate the magento system, as the datbaase is now imported.
        require_once $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
        Mage::app($this->_appCode, $this->_appType);

        try {
            $db = Zend_Db::factory('Pdo_Mysql', array(
                        'host' => $this->_configXml->global->resources->default_setup->connection->host,
                        'username' => $this->_configXml->global->resources->default_setup->connection->username,
                        'password' => $this->_configXml->global->resources->default_setup->connection->password,
                        'dbname' => $this->_configXml->global->resources->default_setup->connection->dbname
                    ));
            $db->getConnection();
        } catch (Zend_Db_Adapter_Exception $e) {
            mage::throwException($e);
            die($e->getMessage());
        } catch (Zend_Exception $e) {
            mage::throwException($e);
            die($e->getMessage());
        }


        foreach ($this->_snapshotXml->$profile->import as $key => $importUpdates) {
            foreach ($importUpdates as $tableName => $changes) {
                foreach ($changes as $changeKey => $updateData) {
                    switch ($changeKey) {
                        case 'update':
                            try {
                                $db->getProfiler()->setEnabled(true);
                                $where = $updateData->where->field . " = '" . $updateData->where->value . "'";
                                $db->update($tableName, array((string) $updateData->set->field => (string) $updateData->set->value), $where);
                                echo "UPDATE: {$tableName} {$updateData->where->value} => {$updateData->set->value}\n";
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
                
  --fetch [server] Do export and import in one go.  Current database will be replaced with update 
  
  --export-remote [server]  Take snapshot of the given remote server [must be defined in snapshot.xml]
  --import [server] <dbname>  [import options] Import the given snapshot
   
USAGE;
    }

}

if (basename($argv[0]) == basename(__FILE__)) {
    $shell = new DogHouse_Shell_Snapshot();
    $shell->run();
}
