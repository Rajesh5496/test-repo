<?php
namespace WPSynchro;

/**
 * Class for handling what to show when clicking licensing on the menu in wp-admin
 *
 * @since 1.0.0
 */
class AdminLicensing
{

    /**
     *  Called from WP menu to show setup
     *  @since 1.0.0
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
     *  Handle the update of data from licensing screen
     *  @since 1.0.0
     */
    private function handlePOST()
    {
        // Save access key  
        if (isset($_POST['wpsynchro_license_key'])) {
            $licensekey = sanitize_key($_POST['wpsynchro_license_key']);
        } else {
            $licensekey = "";
        }

        global $wpsynchro_container;
        $licensing = $wpsynchro_container->get("class.Licensing");
        $licensing->setCurrentLicenseKey($licensekey);
    }

    /**
     *  Show WP Synchro licensing screen 
     *  @since 1.0.0
     */
    private function handleGET()
    {

        global $wpsynchro_container;
        $licensing = $wpsynchro_container->get("class.Licensing");
        $licensekey = $licensing->getCurrentLicenseKey();
        $licensing_details = $licensing->getLicenseDetails();

        ?>
        <div class="wrap wpsynchro-licensing">
            <h2>WP Synchro <?php echo ( \WPSynchro\WPSynchro::isPremiumVersion() ? 'PRO' : 'FREE' ); ?> - <?php _e('Licensing', 'wpsynchro'); ?></h2>
            <p><?php _e('Here you can insert your license key for your PRO version, so you can use the full functionality of WP Synchro PRO.', 'wpsynchro'); ?></p>


            <div class="sectionheader"><span class="dashicons dashicons-shield-alt"></span> <?php _e('Your license', 'wpsynchro'); ?></div>
            <p><?php _e('Your license key can be found on <a href="https://wpsynchro.com" target="_blank">https://wpsynchro.com</a> on My Account after login in with your credentials.', 'wpsynchro'); ?><br><?php _e('The license key will be validated against license server and will be revalidated every day automatically.', 'wpsynchro'); ?></p>

            <?php
            echo '<b>' . __('Currently used license key', 'wpsynchro') . ':</b> ';
            if (strlen($licensekey) > 10) {
                echo sprintf(__('starts with %s and ends with %s', 'wpsynchro'), substr($licensekey, 0, 5), substr($licensekey, strlen($licensekey) - 5, 5));
            } else {
                echo __('no license key', 'wpsynchro');
            }

            if ($licensing_details) {
                $diff_seconds = time() - $licensing_details->timestamp;
                $diff_mins = floor($diff_seconds / 60);
                $diff_hours = floor($diff_seconds / 3600);

                $diff_last_retry = time() - $licensing_details->last_retry;
                $diff_last_retry_mins = floor($diff_last_retry / 60);

                if ($licensing_details->status === null) {

                    ?>
                    <p><b><?php echo sprintf(__("License is in a unknown state - We are retrying to contact license server to determine state - Attempt %d of %d", "wpsynchro"), $licensing_details->retries, 10) ?></b></p>
                    <p><b><?php echo sprintf(__("Last retry was %s minutes ago and we will retry with ~%d min intervals up to %d attempts.", "wpsynchro"), $diff_last_retry_mins, floor($licensing->time_between_retries / 60), $licensing->max_retries) ?></b></p>
                    <p><?php _e('Make sure it is possible to connect out of this webserver to license server at wpsynchro.com.', 'wpsynchro'); ?></p>
                    <?php
                } else if ($licensing_details->status === true) {

                    ?>            
                    <p><b><?php echo sprintf(__("License is <u>valid</u> and <u>active</u>. Last checked %s hours ago.", "wpsynchro"), $diff_hours) ?></b></p>
                    <?php
                } else if ($licensing_details->status === false) {

                    ?>
                    <p><b><?php echo sprintf(__("License is NOT valid. Last checked %s hours ago. Change your key to a valid one or contact support if you have questions.", "wpsynchro"), $diff_hours) ?></b></p>
                    <p><?php _e('To recheck your current key, just save the key again and it will be re-validated.', 'wpsynchro'); ?></p>
                    <?php
                }
            } else {

                ?>
                <p><b><?php _e("License is not yet validated - Insert your license key and validate it.", "wpsynchro") ?></b></p>   

                <?php
            }

            ?>

            <div class="sectionheader"><span class="dashicons dashicons-admin-network"></span> <?php _e('Change license key', 'wpsynchro'); ?></div>
            <form id="wpsynchro-licensing-form" method="POST" >
                <table class="">
                    <tr>
                        <td><label for="name"><?php _e('New license key', 'wpsynchro'); ?></label></td>
                        <td>
                            <input type="text" name="wpsynchro_license_key" id="wpsynchro_license_key_field" value="" class="regular-text ltr" ><br>                    
                        </td>
                    </tr>
                </table>
                <p>By saving your new license key, you agree to the terms of use, outlined in section "TERMS OF USE" on this page.</p>
                <p><input class="btn btn-primary" type="submit" value="<?php _e('Save license key and validate', 'wpsynchro'); ?>" /></p>
            </form>

            <div class="sectionheader"><span class="dashicons dashicons-media-default"></span> <?php _e('Terms of use', 'wpsynchro'); ?> </div>
            <p><?php _e('By inserting your license key and saving it, you accept that we use this information to contact WP Synchro license server.', 'wpsynchro'); ?>
                <br><?php _e('After successful validation, the key will be revalidated every day to make sure it is still valid.', 'wpsynchro'); ?>
                <br><?php _e('On the server, the key will be checked and request is logged, along with your public IP address and site url.', 'wpsynchro'); ?>
                <br><?php _e('We dont save or send other information to the license server.', 'wpsynchro'); ?></p>
            <p><?php _e('For every synchronization, the license server will be contacted to verify the license and the active sites limit for your subscription.', 'wpsynchro'); ?> 
                <br><?php _e('These requests contain only your license key and the urls of synchronization. These will be logged along with your public IP address.', 'wpsynchro'); ?></p>
        </div>
        <?php
    }
}
