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
 - **snapshot**: Import / Export remote databases, and media folder (optional) to a local snapshot folder.
   Remote hosts controlled via entries in local.xml.
   ability to run sql commands on the import of the snapshot to reconfigure for local dev work.
   Ignore some tables data (but structure is gotten.

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

Snapshot:

    Usage:  php -f snapshot.php -- [options]

    Options:

      help              This help
                
      --export [server]  Take snapshot of the given remote server
      --import [server] <dbname>  [import options] Import the given snapshot
  
      Import Options: 
      --name <name> Name of new import db. If none given, [current_shell_user]_[default_store_name]_[server] will be used.              
      --drop    drop the import database if exists
      
      include-images  Also bring down images folder [manual extraction required, file is placed in snapshot folder]       

Configuring snapshot:

In app/etc/local.xml place directive for servers that can be snapshoted:

    <resources>
            <snapshots>
                <uat>
                   <connection>
                            <host><![CDATA[localhost]]></host>
                            <ssh_username><![CDATA[lucas]]></ssh_username>
                            <ssh_port><![CDATA[22]]></ssh_port>
                            <db_username><![CDATA[username]]></db_username>
                            <db_password><![CDATA[password]]></db_password>
                            <dbname><![CDATA[some_database]]></dbname>
                   </connection>
                   <structure>
                       <ignore_tables>importexport_importdata,dataflow_batch,dataflow_import_data,report_event,dataflow_batch_import,dataflow_batch_export,import_export,log_customer,log_quote,log_summary,log_summary_type,log_url,log_url_info,log_visitor,log_visitor_info,log_visitor_online</ignore_tables>
                   </structure>
                   <import>
                        <core_config_data>   
                            <update>
                                <where>
                                    <field><![CDATA[path]]></field>
                                    <value><![CDATA[web/secure/use_in_frontend]]></value>
                                </where>
                                <set>
                                    <field><![CDATA[value]]></field>
                                    <value><![CDATA[0]]></value>
                                </set>
                            </update>
                            <update>
                                <where>
                                    <field><![CDATA[path]]></field>
                                    <value><![CDATA[web/secure/use_in_adminhtml]]></value>
                                </where>
                                <set>
                                    <field><![CDATA[value]]></field>
                                    <value><![CDATA[0]]></value>
                                </set>
                            </update>
                            <update>
                                <where>
                                    <field><![CDATA[path]]></field>
                                    <value><![CDATA[web/unsecure/base_url]]></value>
                                </where>
                                <set>
                                    <field><![CDATA[value]]></field>
                                    <value><![local_dev_url]]></value>
                                </set>
                            </update>
                            <update>
                                <where>
                                    <field><![CDATA[path]]></field>
                                    <value><![CDATA[web/secure/base_url]]></value>
                                </where>
                                <set>
                                    <field><![CDATA[value]]></field>
                                    <value><![CDATA[local_dev_url]]></value>
                                </set>
                            </update>
                        </core_config_data>
                   </import>    
                </uat>        
            </snapshots>
    </reseources>

Requirements
-------------------

Magento (with shell). The script was developed on EE 1.9, but should work with any Magento version that has the /shell
directory.
for snapshot to/from remote hosts, you would ideally need to have ssh keys installed. 
The process does a remote ssh command to make the snapshot as a 'over the wire' mysqldump will lock the database for a loooong time.
If no ssh key is installed you will get prompted for password many times!


Installation
--------------------

Installation is very simple! Clone/copy the contents of /shell to your Magento /shell directory.


License
-------------------
http://www.opensource.org/licenses/osl-3.0.php

Snapshot updated by Lucas van Staden (lucas@dhmedia.com.au)
