<?php
namespace WPSynchro;

/**
 * Class for handling what to show when clicking on support in the menu in wp-admin
 *
 * @since 1.0.3
 */
class AdminSupport
{

    private $show_delete_settings_notice = false;

    /**
     *  Called from WP menu to show support
     *  @since 1.0.3
     */
    public static function render()
    {
        $instance = new self;
        // Handle post
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $instance->handlePOST();
        }
        $instance->handleGET();
    }

    /**
     *  Handle the update of data from support screen 
     *  @since 1.0.3
     */
    private function handlePOST()
    {
        // Check if it is delete settings
        if (isset($_POST['deletesettings']) && $_POST['deletesettings'] == 1) {
            global $wpsynchro_container;
            $common = $wpsynchro_container->get("class.CommonFunctions");
            $common->cleanUpPluginInstallation();
            $this->show_delete_settings_notice = true;
            return;
        }
    }

    /**
     *  Show WP Synchro support screen
     *  @since 1.0.3
     */
    private function handleGET()
    {
        global $wpsynchro_container;
        $debug_obj = $wpsynchro_container->get("class.DebugInformation");
        $debug_arr = $debug_obj->getDebugInformationArray();
        $debug_json = $debug_obj->getJSONDebugInformation();
      
        if (\WPSynchro\WPSynchro::isPremiumVersion()) {
            // Licensing
            $licensing = $wpsynchro_container->get("class.Licensing");
        }

        ?>
        <div id="wpsynchro-support" class="wrap">
            <h2>WP Synchro <?php echo ( \WPSynchro\WPSynchro::isPremiumVersion() ? 'PRO' : 'FREE' ); ?> - <?php _e('Support', 'wpsynchro'); ?></h2>

            <?php
            if ($this->show_delete_settings_notice) {

                ?>
                <div class="notice notice-success">
                    <p><?php _e('WP Synchro data clean up completed - It is nice and clean now', 'wpsynchro'); ?></p>
                </div>
                <?php
            }

            ?>

            <p><?php _e('Here is how you get help on a support issue for WP Synchro.', 'wpsynchro'); ?></p>
            <div class="sectionheader"><span class="dashicons dashicons-lightbulb"></span> <?php _e('Getting support', 'wpsynchro'); ?></div>
            <?php
            if (\WPSynchro\WPSynchro::isPremiumVersion() && $licensing->verifyLicense()) {

                ?>
                <p><?php _e('You are on the PRO version with a validated license, so you have access to priority email support.', 'wpsynchro'); ?></p>
                <p><?php _e('Contact us on', 'wpsynchro'); ?> <a href="mailto:support@wpsynchro.com">support@wpsynchro.com</a>.</p>
                <p><?php _e('Be sure to include relevant information, such as:', 'wpsynchro'); ?></p>

                <ul>
                    <li> - <?php _e('Description of problem(s)', 'wpsynchro'); ?></li>
                    <li> - <?php _e('Screenshot of problem(s)', 'wpsynchro'); ?></li>
                    <li> - <?php _e('Result of Healthcheck just below', 'wpsynchro'); ?></li>
                    <li> - <?php _e('Log file from synchronization (found in menu "Logs")', 'wpsynchro'); ?></li>                    
                </ul>
                <p><?php _e('We will then get back to you as soon as we have investigated and we will ask for further information if needed.', 'wpsynchro'); ?></p>

                <?php
            } else {

                ?>
                <p><?php _e('You are using the free version of WP Synchro, which we also provide email support for.', 'wpsynchro'); ?></p>
                <p><?php _e('Users on the PRO version have priority support, so free version support requests can take more time depending on support load.<br>Check out <a href="https://wpsynchro.com" target="_blank">https://wpsynchro.com</a> on how to get the PRO version. The PRO version also contains more useful features, such as synchronizing files.', 'wpsynchro'); ?></p>
                <p><?php _e('If you just have a bug report, security issue or a good idea for WP Synchro, we would still like to hear from you.', 'wpsynchro'); ?></p>
                <p><?php _e('Contact us on', 'wpsynchro'); ?> <a href="mailto:support@wpsynchro.com">support@wpsynchro.com</a>.</p>
                <p><?php _e('Be sure to include relevant information, such as:', 'wpsynchro'); ?></p>

                <ul>
                    <li> - <?php _e('Description of problem(s)', 'wpsynchro'); ?></li>
                    <li> - <?php _e('Screenshot of problem(s)', 'wpsynchro'); ?></li>
                    <li> - <?php _e('Result of Healthcheck just below', 'wpsynchro'); ?></li>
                    <li> - <?php _e('Log file from synchronization (found in menu "Logs")', 'wpsynchro'); ?></li>                    
                </ul>
                <?php
            }

            ?>              

            <div class="sectionheader"><span class="dashicons dashicons-awards"></span> <?php _e('Healthcheck', 'wpsynchro'); ?></div>

            <healthcheck showinline></healthcheck>

            <div class="sectionheader"><span class="dashicons dashicons-admin-generic"></span> <?php _e('Debug information', 'wpsynchro'); ?></div>
            <p><?php _e('Find debug data for your site, for easier debugging by WP Synchro team on support issues.', 'wpsynchro'); ?></p>  
            <table class="debugtable mb-5">                    
                <thead>
                    <tr>
                        <th><?php _e('Setting', 'wpsynchro'); ?></th>
                        <th><?php _e('Value', 'wpsynchro'); ?></th>                            
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($debug_arr as $debug_name => $debug_value) {
                        echo '<tr><td class="setting">' . $debug_name . '</td><td>' . $debug_value . '</td></tr>';
                    }

                    ?>

                </tbody>                 
            </table>

            <div class="sectionheader"><span class="dashicons dashicons-admin-generic"></span> <?php _e('Debug JSON information', 'wpsynchro'); ?></div>
            <p><?php _e('Contains the above information + active plugins and dropins. No personal information is included.', 'wpsynchro'); ?></p>
            <textarea class="debugjson"><?php echo $debug_json; ?></textarea>

            <div class="sectionheader"><span class="dashicons dashicons-no"></span> <?php _e('Delete WP Synchro data', 'wpsynchro'); ?></div>
            <p><?php _e('Delete all data related to WP Synchro, in database and files. Can be used to clean up after WP Synchro if needed.', 'wpsynchro'); ?><br><?php _e('Does not reset access key and license key setup, but removes data like log files and installations.', 'wpsynchro'); ?></p>

            <form  method="POST" >
                <input type="hidden" name="deletesettings" value="1" />
                <p><button type="submit" class="deletesettingsbutton" /><?php _e('Delete all WP Synchro data', 'wpsynchro'); ?></button></p>

            </form>


        </div>
        <?php
    }
}
