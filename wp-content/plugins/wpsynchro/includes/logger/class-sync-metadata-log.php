<?php
namespace WPSynchro;

/**
 * Class for handling logging data on sync for use in logs menu (not the logger-logger, but just a log...  :) )
 *
 * @since 1.0.5
 */
class SyncMetadataLog
{

    public function __construct()
    {
        
    }

    /**
     *  Start a synchronization entry in the log 
     *  @since 1.0.5          
     */
    public function startSynchronization($job_id, $installation_id, $description)
    {

        $synclog = get_option("wpsynchro_sync_logs");
        if (!is_array($synclog)) {
            $synclog = array();
        }

        $newsync = new \stdClass();
        $newsync->start_time = current_time('timestamp');
        $newsync->state = 'started';
        $newsync->description = $description;
        $newsync->job_id = $job_id;
        $newsync->installation_id = $installation_id;

        $synclog[] = $newsync;

        update_option("wpsynchro_sync_logs", $synclog, 'no');
    }

    /**
     *  Mark a synchronization entry as completed
     *  @since 1.0.5          
     */
    public function stopSynchronization($job_id, $installation_id)
    {
        $synclog = get_option("wpsynchro_sync_logs");

        if (is_array($synclog)) {
            foreach ($synclog as &$log) {
                if ($log->job_id == $job_id && $log->installation_id == $installation_id) {
                    $log->state = "completed";
                    update_option("wpsynchro_sync_logs", $synclog, 'no');
                    break;
                }
            }
        }
    }

    /**
     *  Retrieve all log entries
     *  @since 1.0.5  
     */
    public function getAllLogs()
    {
        $synclog = get_option("wpsynchro_sync_logs");
        if (!is_array($synclog)) {
            $synclog = array();
        }

        return $synclog;
    }
}
