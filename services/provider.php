<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  SWJProjects.WordPressServerScheme
 *
 * @copyright   (C) 2025 Alex Revo <https://alexrevo.pw>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Swjprojects\Wordpressserverscheme\Extension\Wordpressserverscheme;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin     = new Wordpressserverscheme(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('swjprojects', 'wordpressserverscheme')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
