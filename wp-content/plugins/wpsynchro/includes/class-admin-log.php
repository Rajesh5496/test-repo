<?php
namespace WPSynchro;

/**
 * Class for handling what to show when clicking on log in the menu in wp-admin
 *
 * @since 1.0.0
 */
class AdminLog
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
     *  Handle the update of data from log screen
     *  @since 1.0.0
     */
    private function handlePOST()
    {
        
    }

    /**
     *  Show WP Synchro log screen 
     *  @since 1.0.0
     */
    private function handleGET()
    {

        if (isset($_REQUEST['showlog']) && isset($_REQUEST['inst'])) {
            $job_id = sanitize_key($_REQUEST['showlog']);
            $inst_id = sanitize_key($_REQUEST['inst']);
            $this->showLog($job_id, $inst_id);
            return;
        }

        ?>
        <div class="wrap wpsynchro-setup">
            <h2>WP Synchro <?php echo ( \WPSynchro\WPSynchro::isPremiumVersion() ? 'PRO' : 'FREE' ); ?> - <?php _e('Logs', 'wpsynchro'); ?></h2>
            <p><?php _e('See your last synchronizations and the result of them. Here you can also download the log file from the synchronization.', 'wpsynchro'); ?></p>

            <div class="synclogs">
                <?php
                require_once( 'class-admin-log-table.php' );
                $table = new AdminLogTable();
                $table->prepare_items();

                ?>

                <form id="syncsetups" method="get">                   
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
                    <?php $table->display(); ?>
                </form>

            </div>


        </div>
        <?php
    }

    /**
     *  Show the log file for job
     *  @since 1.0.5
     */
    public function showLog($job_id, $inst_id)
    {
        // Check if file exist
        global $wpsynchro_container;
        $common = $wpsynchro_container->get('class.CommonFunctions');
        $inst_factory = $wpsynchro_container->get('class.InstallationFactory');

        $logpath = $common->getLogLocation();
        $filename = $common->getLogFilename($job_id);

        $job_obj = get_option("wpsynchro_" . $inst_id . "_" . $job_id, "");
        $inst_obj = $inst_factory->retrieveInstallation($inst_id);


        if (file_exists($logpath . $filename)) {
            $logcontents = file_get_contents($logpath . $filename);

            echo "<h1>Log file for jobid " . $job_id . "</h1> ";
            echo "<div><h3>Beware: Do not share this file with other people than WP Synchro support - It contains data that can compromise your site.</h3></div>";

            echo '<pre>';
            echo $logcontents;
            echo '</pre>';

            echo '<h3>Installation object:</h3>';
            echo '<pre>';
            print_r($inst_obj);
            echo '</pre>';

            echo '<h3>Job object:</h3>';
            echo '<pre>';
            print_r($job_obj);
            echo '</pre>';
        }
    }
}
