<?php
namespace WPSynchro\Files;

/**
 * Class for handling files finalize
 * @since 1.0.3
 */
class FinalizeFiles
{

    // Data objects 
    public $job = null;
    public $installation = null;
    public $sync_list = null;
    public $target_url = null;
    public $target_token = null;
    public $timer = null;

    /**
     *  Constructor
     */
    public function __construct()
    {
        
    }

    /**
     *  Initialize class
     *  @since 1.0.3
     */
    public function init(\WPSynchro\Files\SyncList &$sync_list, \WPSynchro\Installation &$installation, \WPSynchro\Job &$job)
    {

        $this->sync_list = $sync_list;
        $this->installation = $installation;
        $this->job = $job;

        if ($this->installation->type == 'pull') {
            $this->target_url = $this->job->to_rest_base_url . "wpsynchro/v1/finalize/";
        } else if ($this->installation->type == 'push') {
            $this->target_url = $this->job->to_rest_base_url . "wpsynchro/v1/finalize/";
        }
    }

    /**
     * Clean up files on target
     * @since 1.0.3
     */
    public function finalizeFiles()
    {
        // Timer
        global $wpsynchro_container;
        $this->timer = $wpsynchro_container->get("class.SyncTimerList");
        // Logger
        $logger = $wpsynchro_container->get("class.Logger");
        $logger->log("INFO", "Starting file finalize with remaining time: " . $this->timer->getRemainingSyncTime());

        // Set progress description    
        $this->job->finalize_progress_description = sprintf(__("Remaining files to delete: %d", "wpsynchro"), $this->sync_list->getRemainingFileDeletes());

        // Fetch a chunk of files to delete on target
        $limit = 100;
        $filelist = $this->sync_list->getFilesChunkForDeletion($limit);
        if (count($filelist) == 0) {
            $this->job->finalize_files_completed = true;
            return;
        }

        // Call service with delete list         
        $returned_file_list = $this->callFileFinalizeService($filelist);

        // Set files to deleted
        if ($returned_file_list) {
            $this->sync_list->setFilesToDeleted($returned_file_list);
        }
    }

    /**
     * Call file finalize service on target
     * @since 1.0.3
     */
    public function callFileFinalizeService($deletes)
    {
        global $wpsynchro_container;
        $logger = $wpsynchro_container->get("class.Logger");

        // Now we have all the work needed, so call the finalize REST service on the target  
        $body = new \stdClass();
        $body->allotted_time = $this->timer->getRemainingSyncTime();
        $body->delete = $deletes;

        // Get remote transfer object
        $remotetransport = $wpsynchro_container->get('class.RemoteTransfer');
        $remotetransport->init();
        $remotetransport->setUrl($this->target_url);
        $remotetransport->setDataObject($body);
        $finalize_result = $remotetransport->remotePOST();

        if ($finalize_result->isSuccess()) {
            $body = $finalize_result->getBody();
            return $body->delete;
        } else {            
            $this->job->errors[] = __("Error calling finalize REST service - Did not get a proper response - Check logs for details.", "wpsynchro");
        }
    }
}
