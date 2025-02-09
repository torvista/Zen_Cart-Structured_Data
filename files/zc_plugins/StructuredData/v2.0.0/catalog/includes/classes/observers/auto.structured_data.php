<?php

declare(strict_types=1);

/**
 * @author: torvista
 * @link: https://github.com/torvista/Zen_Cart-Structured_Data
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version auto.structured_data.php torvista 08 Feb 2025
 */
use App\Models\PluginControl;
use App\Models\PluginControlVersion;
use Zencart\PluginManager\PluginManager;

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class zcObserverStructuredData extends base
{
    public bool $enabled;

    protected bool $debug;
    protected string $zcPluginDir;

    public function __construct()
    {
        $this->debug = false;
        $this->enabled = (defined('PLUGIN_SDATA_ENABLE') && PLUGIN_SDATA_ENABLE === 'true');
        if ($this->enabled === false) {
            return;
        }

        // Determine this zc_plugin's installed directory
        $plugin_manager = new PluginManager(new PluginControl(), new PluginControlVersion());
        $this->zcPluginDir = str_replace(
            DIR_FS_CATALOG,
            '',
            $plugin_manager->getPluginVersionDirectory('StructuredData', $plugin_manager->getInstalledPlugins()) . 'catalog/'
        );

        // Observers
        $this->attach(
            $this,
            [
                /* From /includes/templates/{template}/common/html_header.php */
                'NOTIFY_HTML_HEAD_END',
            ]
        );
    }

    /**
     * Issued at the end of the active template's html_header.php just before the </head> tag.
     * Inserts the plugin's JS file.
     * @param $class
     * @param  string  $e
     * @return void
     */
    protected function notify_html_head_end(&$class, string $e): void
    {
        global $breadcrumb, $canonicalLink, $current_page_base, $db, $lng, $product_info, $reviewsArray, $sniffer;
        include $this->getZcPluginDir() . DIR_WS_TEMPLATES . 'default/jscript/structured_data_jscript.php';
    }

    /**
     * Return the plugin's currently-installed zc_plugin directory for the catalog.
     * @return string
     */
    public function getZcPluginDir(): string
    {
        return $this->zcPluginDir;
    }
}
