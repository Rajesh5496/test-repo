<?php
namespace WPSynchro\CLI;

/**
 * WP Synchro plugin commands, such as run synchronization
 * @since 1.3.0
 */
class WP_CLI_Command
{

    /**
     * Run synchronization
     * 
     * ## OPTIONS
     *
     * <instid>
     * : The id of the installation - Can be found in the overview for a specific setup
     *
     * ---
     * default: success
     * options:
     *   - success
     *   - error
     * ---
     *
     * ## EXAMPLES
     *
     * wp wpsynchro run <instid>
     *
     */
    public function run($args, $assoc_args)
    {
        list( $instid ) = $args;
        $jobid = uniqid();

        // Check that installations id exists
        global $wpsynchro_container;
        $installationfactory = $wpsynchro_container->get('class.InstallationFactory');

        $installation = $installationfactory->retrieveInstallation($instid);
        if (!$installation) {
            \WP_CLI::error(sprintf(__('Installation id "%s" could not be found. Make sure it is identical to the one found on the overview page in WP Synchro.', 'wpsynchro'), $instid));
        }

        /**
         *  Running
         */
        $max_rounds = 1000;
        $round = 0;

        // Run synchronization
        \WP_CLI::log(__('Starting synchronization...', 'wpsynchro'));

        while ($round < $max_rounds) {
            $sync = $wpsynchro_container->get('class.SynchronizeController');
            $sync->setup($instid, $jobid);
            $sync->timer->overall_sync_timer = null;
            $result = $sync->runSynchronization();

            if ($result->is_completed) {
                break;
            }

            if (count($result->errors) > 0) {
                foreach ($result->errors as $error) {
                    \WP_CLI::error($error, false);
                }
                \WP_CLI::halt(1);
            }

            $round++;
        }


        \WP_CLI::success(__('Synchronization has completed without errors', 'wpsynchro'));
    }
}
