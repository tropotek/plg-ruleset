<?php
namespace Rs;

use Tk\Event\Dispatcher;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \App\Plugin\Iface
{

    /**
     * A helper method to get the Plugin instance globally
     *
     * @return static
     * @throws \Tk\Exception
     */
    static function getInstance()
    {
        return \Tk\Config::getInstance()->getPluginFactory()->getPlugin('plg-ruleset');
    }

    /**
     * Init the plugin
     *
     * This is called when the session first registers the plugin to the queue
     * So it is the first called method after the constructor...
     * @throws \Tk\Exception
     */
    function doInit()
    {
        include dirname(__FILE__) . '/config.php';

        // Register the plugin for the different client areas if they are to be enabled/disabled/configured by those roles.
        //$this->getPluginFactory()->registerZonePlugin($this, self::ZONE_INSTITUTION);
        $this->getPluginFactory()->registerZonePlugin($this, self::ZONE_SUBJECT_PROFILE);
        //$this->getPluginFactory()->registerZonePlugin($this, self::ZONE_SUBJECT);

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->getConfig()->getEventDispatcher();
        $dispatcher->addSubscriber(new \Rs\Listener\SetupHandler());
    }

    /**
     * Activate the plugin, essentially
     * installing any DB and settings required to run
     * Will only be called when activating the plugin in the
     * plugin control panel
     *
     * @throws \Tk\Exception
     * @throws \Exception
     */
    function doActivate()
    {
        // Init Plugin Settings
        $config = \Tk\Config::getInstance();
        $db = $this->getConfig()->getDb();

        $migrate = new \Tk\Util\SqlMigrate($db);
        /** @var \Tk\Util\SqlMigrate $migrate */
        $migrate->setTempPath($config->getTempPath());
        $migrate->migrate(dirname(__FILE__) . '/sql');

        // Init Settings
//        $data = \Tk\Db\Data::create($this->getName());
//        $data->set('plugin.title', 'Day One Skills');
//        $data->set('plugin.email', 'fvas-elearning@unimelb.edu.au');
//        $data->save();
    }

    /**
     * Example upgrade code
     * This will be called when you update the plugin version in the composer.json file
     *
     * Upgrade the plugin
     * Called when the file version is larger than the version in the DB table
     *
     * @param string $oldVersion
     * @param string $newVersion
     * @throws \Exception
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    function doUpgrade($oldVersion, $newVersion) {
        // Init Plugin Settings
        $db = $this->getConfig()->getDb();

        $migrate = new \Tk\Util\SqlMigrate($db);
        $migrate->setTempPath($this->getConfig()->getTempPath());
        $migrate->migrate(dirname(__FILE__) . '/sql');

//        if (version_compare($oldVersion, '1.0.1', '<')) { ; }
//        if (version_compare($oldVersion, '1.0.2', '<')) { ; }

    }

    /**
     * Deactivate the plugin removing any DB data and settings
     * Will only be called when deactivating the plugin in the
     * plugin control panel
     * @throws \Tk\Db\Exception
     */
    function doDeactivate()
    {
        // TODO: Maybe we do not delete anything to ensure data is not lost??????
        $db = $this->getConfig()->getDb();

        // Clear the data table of all plugin data
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('fkey'),
            $db->quote($this->getName().'%'));
        $db->query($sql);

        // Delete all tables.
        $tables = array('rules');
        foreach ($tables as $name) {
            $db->dropTable($name);
        }

        // Remove migration track
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Util\SqlMigrate::$DB_TABLE), $db->quoteParameter('path'),
            $db->quote('/plugin/' . $this->getName().'/%'));
        $db->query($sql);
        
        // Delete any setting in the DB
//        $data = \Tk\Db\Data::create($this->getName());
//        $data->clear();
//        $data->save();
    }

    /**
     * Get the subject settings URL, if null then there is none
     *
     * @param $zoneName
     * @return string|\Tk\Uri|null
     */
    public function getZoneSettingsUrl($zoneName)
    {
//        switch ($zoneName) {
//            case self::ZONE_SUBJECT_PROFILE:
//                return \Tk\Uri::create('/ruleset/profileSettings.html');
//        }
        return null;
    }

}