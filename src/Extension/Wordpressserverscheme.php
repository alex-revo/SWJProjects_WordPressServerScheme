<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      SWJProjects.WordPressServerScheme
 *
 * @copyright   (C) 2025 Alex Revo <https://alexrevo.pw>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Swjprojects\Wordpressserverscheme\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\SWJProjects\Administrator\Event\ServerschemeEvent;
use Joomla\Component\SWJProjects\Administrator\Serverscheme\ServerschemePlugin;
use Joomla\Component\SWJProjects\Site\Helper\RouteHelper;
use Joomla\Component\SWJProjects\Site\Helper\ImagesHelper;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;

use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * WordPress Server Scheme Plugin
 * 
 * Generates WordPress Plugin Update API compatible JSON format
 * Based on: https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
 *
 * @since  1.0.0
 */
final class Wordpressserverscheme extends ServerschemePlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation.
     *
     * @var    bool
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * MIME type for output
     *
     * @var string
     * @since 1.0.0
     */
    protected string $mimeType = 'application/json';

    /**
     * Charset for output
     *
     * @var string
     * @since 1.0.0
     */
    protected string $charset = 'utf-8';

    /**
     * Scheme name displayed in admin
     *
     * @var string
     * @since 1.0.0
     */
    protected string $name = 'WordPress Plugin Update API';

    /**
     * Scheme type identifier
     *
     * @var string
     * @since 1.0.0
     */
    protected string $type = 'wordpress';

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGetServerschemeList' => 'onGetServerschemeList',
        ];
    }


    /**
     * Add this scheme to the list of available schemes
     *
     * @param   ServerschemeEvent  $event  The event object
     *
     * @return  void
     *
     * @since 1.0.0
     */
    public function onGetServerschemeList(ServerschemeEvent $event): void
    {


        $event->addResult([
            'type'  => $this->getType(),
            'name'  => $this->getName(),
            'class' => $this,
        ]);
    }

    /**
     * Render output based on requested scheme
     *
     * @param   array  $data  Project data from SW JProjects
     *
     * @return  string  JSON encoded output
     *
     * @since 1.0.0
     */
    public function renderOutput(array $data): string
    {
        // Set JSON header explicitly
        Factory::getApplication()->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        
        // Output the JSON data
        echo $this->buildWordPressJson($data);
        


        // Close the application to prevent the component view from interfering
        // This fixes the "Call to undefined method JsonDocument::addCustomTag()" error
        Factory::getApplication()->close();
        
        return '';
    }

    /**
     * Build WordPress-compatible plugin update JSON
     * 
     * This method generates JSON format compatible with WordPress Plugin Update API
     * as described in https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
     * 
     * WordPress expects the following structure:
     * - name: Plugin name
     * - slug: Plugin slug (unique identifier)
     * - version: Current version
     * - download_url: URL to download the plugin ZIP
     * - requires: Minimum WordPress version
     * - tested: Maximum tested WordPress version
     * - requires_php: Minimum PHP version
     * - sections: Array with description, installation, changelog
     * - banners: Array with low and high resolution banner images
     * 
     * @param   array  $data  Array of project data from SW JProjects
     * 
     * @return  string  JSON encoded WordPress plugin info
     * 
     * @since   1.0.0
     */
    protected function buildWordPressJson(array $data): string
    {
        $site_root = Uri::getInstance()->toString(['scheme', 'host', 'port']);
        
        // WordPress expects single plugin info, so we take the first item
        if (empty($data)) {
            return json_encode([]);
        }
        
        $item = reset($data);
        
        // Build changelog HTML from changelog data
        // Build changelog HTML from full history
        $changelogHtml = $this->fetchFullChangelog($item->project_id ?? 0);
        
        // Fetch additional project details from DB
        $projectDetails = $this->fetchProjectDetailsFromDb($item->project_id ?? 0);
        
        // Load plugin params for overrides
        $pluginParams = $this->getPluginParams();
        $overrides = $pluginParams->get('projects_metadata', []);
        $projectOverride = null;
        
        if (!empty($overrides)) {
            // Convert to array if it's an object (subform data can be tricky)
            $overrides = (array) $overrides;
            foreach ($overrides as $override) {
                $override = (object) $override; // Ensure object access
                if (isset($override->project_id) && $override->project_id == ($item->project_id ?? 0)) {
                    $projectOverride = $override;
                    break;
                }
            }
        }

        // Prepare data with defaults
        $authorName = $item->author ?? null;
        $authorUrl = $item->urls->developer ?? null;
        $requiresPhp = $item->php->min ?? '7.4';
        $installation = 'Upload the plugin files to your WordPress plugins directory, or install the plugin through the WordPress plugins screen directly.';

        // Apply overrides
        if ($projectOverride) {
            if (!empty($projectOverride->author_name)) {
                $authorName = $projectOverride->author_name;
            }
            if (!empty($projectOverride->author_url)) {
                $authorUrl = $projectOverride->author_url;
            }
            if (!empty($projectOverride->requires_php)) {
                $requiresPhp = $projectOverride->requires_php;
            }
            if (!empty($projectOverride->installation)) {
                $installation = $projectOverride->installation;
            }
        }

        // Build WordPress-compatible structure
        $wpData = [
            'name'           => $item->name ?? $item->title,
            'slug'           => $item->element,
            'version'        => $item->version,
            'download_url'   => $site_root . $item->download,
            'requires'       => '5.0', // Default
            'tested'         => '6.4', // Default
            'requires_php'   => $requiresPhp,
            'last_updated'   => date('Y-m-d H:i:s', strtotime($item->date ?? 'now')),
            'sections'       => [
                'description'  => $projectDetails['description'] ?? strip_tags($item->description ?? ''),
                'installation' => $installation,
                'changelog'    => $changelogHtml,
            ],
        ];

        // Add author info only if available
        if (!empty($authorName)) {
            $wpData['author'] = $authorName;
            if (!empty($authorUrl)) {
                 $wpData['author'] = '<a href="' . htmlspecialchars($authorUrl) . '">' . htmlspecialchars($authorName) . '</a>';
                 $wpData['author_profile'] = $authorUrl;
            }
        }

        // Update compatibility if available
        if (!empty($item->joomla_version)) {
             $wpData['tested'] = $item->joomla_version;
        }

        // Add icons
        if (!empty($projectDetails['icon'])) {
            $iconPath = ltrim($projectDetails['icon'], '/');
            $wpData['icons'] = [
                '1x' => $site_root . '/' . $iconPath,
                '2x' => $site_root . '/' . $iconPath, // Use same for 2x if no separate image
            ];
        }

        // Add banners
        if (!empty($projectDetails['cover'])) {
            $coverPath = ltrim($projectDetails['cover'], '/');
            $wpData['banners'] = [
                'low'  => $site_root . '/' . $coverPath,
                'high' => $site_root . '/' . $coverPath,
            ];
        } elseif (isset($item->images)) {
             $wpData['banners'] = [
                'low'  => $item->images->banner_low ?? '',
                'high' => $item->images->banner_high ?? '',
            ];
        }
        
        // Add homepage URL if available
        if (isset($item->urls->homepage)) {
            $wpData['homepage'] = $item->urls->homepage;
        }
        
        return json_encode($wpData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Fetch changelog from database
     *
     * @param   int  $versionId  Version ID
     *
     * @return  mixed  Changelog object/array or null
     *
     * @since   1.0.0
     */
    private function fetchChangelogFromDb(int $versionId)
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            
            // Determine language safely
            $lang = Factory::getApplication()->getLanguage()->getTag();
            if (property_exists($this, 'config') && isset($this->config['translates']['current'])) {
                $lang = $this->config['translates']['current'];
            }
            
            $defaultLang = 'en-GB';
            if (property_exists($this, 'config') && isset($this->config['translates']['default'])) {
                $defaultLang = $this->config['translates']['default'];
            }

            // Try current language
            $query->select($db->quoteName('changelog'))
                  ->from($db->quoteName('#__swjprojects_translate_versions'))
                  ->where($db->quoteName('id') . ' = ' . (int) $versionId)
                  ->where($db->quoteName('language') . ' = ' . $db->quote($lang));
            
            $result = $db->setQuery($query)->loadResult();

            // If empty and languages differ, try default language
            if (empty($result) && $lang !== $defaultLang) {
                $query->clear()
                      ->select($db->quoteName('changelog'))
                      ->from($db->quoteName('#__swjprojects_translate_versions'))
                      ->where($db->quoteName('id') . ' = ' . (int) $versionId)
                      ->where($db->quoteName('language') . ' = ' . $db->quote($defaultLang));
                
                $result = $db->setQuery($query)->loadResult();
            }

            if (!empty($result)) {
                return json_decode($result);
            }
        } catch (\Exception $e) {
            // Ignore DB errors
        }

        return null;
    }

    /**
     * Fetch additional project details from database
     *
     * @param   int  $projectId  Project ID
     *
     * @return  array  Project details
     *
     * @since   1.0.0
     */
    private function fetchProjectDetailsFromDb(int $projectId): array
    {
        $details = [
            'description' => '',
            'icon'        => '',
            'cover'       => '',
            'joomla'      => [],
        ];

        if (empty($projectId)) {
            return $details;
        }

        try {
            $db = Factory::getDbo();
            
            // Determine language
            $lang = Factory::getApplication()->getLanguage()->getTag();
            if (property_exists($this, 'config') && isset($this->config['translates']['current'])) {
                $lang = $this->config['translates']['current'];
            }

            // Fetch fulltext from translate_projects
            $query = $db->getQuery(true)
                ->select($db->quoteName('fulltext'))
                ->from($db->quoteName('#__swjprojects_translate_projects'))
                ->where($db->quoteName('id') . ' = ' . (int) $projectId)
                ->where($db->quoteName('language') . ' = ' . $db->quote($lang));
            
            $fulltext = $db->setQuery($query)->loadResult();
            if ($fulltext) {
                $details['description'] = $fulltext;
            }

            // Fetch images using ImagesHelper if available
            if (class_exists(ImagesHelper::class)) {
                $details['icon'] = ImagesHelper::getImage('projects', $projectId, 'icon', $lang);
                $details['cover'] = ImagesHelper::getImage('projects', $projectId, 'cover', $lang);
            }

            // Fetch joomla params from projects table
            $query->clear()
                ->select($db->quoteName('joomla'))
                ->from($db->quoteName('#__swjprojects_projects'))
                ->where($db->quoteName('id') . ' = ' . (int) $projectId);
            
            $joomlaParams = $db->setQuery($query)->loadResult();
            if ($joomlaParams) {
                $details['joomla'] = json_decode($joomlaParams, true);
            }

        } catch (\Exception $e) {
            // Ignore errors
        }

        return $details;
    }

    /**
     * Fetch full changelog history from database
     *
     * @param   int  $projectId  Project ID
     *
     * @return  string  HTML Changelog
     *
     * @since   1.0.0
     */
    private function fetchFullChangelog(int $projectId): string
    {
        if (empty($projectId)) {
            return '';
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        // Determine language
        $lang = Factory::getApplication()->getLanguage()->getTag();
        if (property_exists($this, 'config') && isset($this->config['translates']['current'])) {
            $lang = $this->config['translates']['current'];
        }
        $defaultLang = 'en-GB';
        if (property_exists($this, 'config') && isset($this->config['translates']['default'])) {
            $defaultLang = $this->config['translates']['default'];
        }

        $query->select($db->quoteName(['v.major', 'v.minor', 'v.patch', 'v.hotfix']))
              ->select('COALESCE(tv.changelog, tv_def.changelog) as changelog')
              ->from($db->quoteName('#__swjprojects_versions', 'v'))
              ->leftJoin($db->quoteName('#__swjprojects_translate_versions', 'tv') . ' ON tv.id = v.id AND tv.language = ' . $db->quote($lang))
              ->leftJoin($db->quoteName('#__swjprojects_translate_versions', 'tv_def') . ' ON tv_def.id = v.id AND tv_def.language = ' . $db->quote($defaultLang))
              ->where($db->quoteName('v.project_id') . ' = ' . (int) $projectId)
              ->where($db->quoteName('v.state') . ' = 1')
              ->order($db->quoteName('v.major') . ' DESC')
              ->order($db->quoteName('v.minor') . ' DESC')
              ->order($db->quoteName('v.patch') . ' DESC')
              ->order($db->quoteName('v.hotfix') . ' DESC');

        try {
            $versions = $db->setQuery($query)->loadObjectList();
        } catch (\Exception $e) {
            return '';
        }

        $html = '';
        foreach ($versions as $version) {
            $changelogData = null;
            if (!empty($version->changelog)) {
                $changelogData = json_decode($version->changelog);
            }
            
            if (!empty($changelogData) && (is_object($changelogData) || is_array($changelogData))) {
                $verStr = $version->major . '.' . $version->minor . '.' . $version->patch;
                if ($version->hotfix > 0) $verStr .= '.' . $version->hotfix;
                
                $html .= '<h4>' . htmlspecialchars($verStr) . '</h4><ul>';
                foreach ($changelogData as $value) {
                    $valObj = (object) $value;
                    $title = htmlspecialchars($valObj->title ?? '');
                    $description = htmlspecialchars($valObj->description ?? '');
                    $html .= '<li>';
                    if ($title) $html .= '<strong>' . $title . '</strong>';
                    if ($title && $description) $html .= ' - ';
                    if ($description) $html .= $description;
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
        }
        
        return $html;
    }

    /**
     * Get plugin parameters
     *
     * @return  Registry
     *
     * @since   1.0.0
     */
    private function getPluginParams(): Registry
    {
        static $params = null;

        if ($params === null) {
            $plugin = PluginHelper::getPlugin('swjprojects', 'wordpressserverscheme');
            if ($plugin && isset($plugin->params)) {
                $params = new Registry($plugin->params);
            } else {
                $params = new Registry();
            }
        }

        return $params;
    }
}
