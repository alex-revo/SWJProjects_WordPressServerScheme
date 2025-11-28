<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  SWJProjects.WordPressServerScheme
 *
 * @copyright   (C) 2025 Alex Revo <https://alexrevo.pw>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class PlgSwjprojectsWordpressserverschemeInstallerScript
{
    /**
     * Method to run after the extension is installed/updated/discovered.
     *
     * @param   string  $type    The type of change (install, update or discover_install)
     * @param   object  $parent  The object responsible for running this script
     *
     * @return  void
     */
    public function postflight($type, $parent)
    {
        $this->enablePlugin();
    }

    /**
     * Method to enable the plugin.
     *
     * @return  void
     */
    private function enablePlugin()
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('wordpressserverscheme'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('swjprojects'));
            
            $db->setQuery($query);
            $db->execute();
            
            Factory::getApplication()->enqueueMessage('Plugin SW JProjects - WordPress Server Scheme has been automatically enabled.', 'message');
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Could not enable plugin automatically: ' . $e->getMessage(), 'warning');
        }
    }
}
