<?php
namespace WPSynchro;

/**
 * Class for handling deactivate tasks for WP Synchro
 *
 * @since 1.1.0
 */
class AdminDeactivation
{

    public static function deactivation()
    {

        // Deactivate MU plugin if exists
        require_once( trailingslashit(dirname(__FILE__)) . 'compatibility/class-mu-plugin-handler.php' );
        $mupluginhandler = new \WPSynchro\Compatibility\MUPluginHandler();
        $mupluginhandler->disablePlugin();
    }
}
