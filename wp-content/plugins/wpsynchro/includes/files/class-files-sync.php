<?php
namespace WPSynchro\Files;

/**
 * Class for handling files synchronization
 * @since 1.0.3
 */
class FilesSync
{

    // Data objects
    public $job = null;
    public $installation = null;
    public $logger = null;
    public $timer = null;
    // Specific task classes
    public $sync_list = null;
    public $populate_list_handler = null;
    public $path_handler = null;
    public $transfer_files_handler = null;

    // Files data

    /**
     *  Constructor
     */
    public function __construct()
    {
        
    }

    /**
     * Initialize needed objects
     * @since 1.0.3
     */
    public function init()
    {

        global $wpsynchro_container;

        // Logger
        $this->logger = $wpsynchro_container->get("class.Logger");

        // File sync list object
        $this->sync_list = $wpsynchro_container->get("class.SyncList");
        $this->sync_list->init($this->installation, $this->job);

        // File populate list handler
        $this->populate_list_handler = $wpsynchro_container->get("class.PopulateListHandler");
        $this->populate_list_handler->init($this->sync_list, $this->installation, $this->job);

        // Path handler object
        $this->path_handler = $wpsynchro_container->get("class.PathHandler");
        $this->path_handler->init($this->sync_list, $this->job);

        // TransferFiles object
        $this->transfer_files_handler = $wpsynchro_container->get("class.TransferFiles");
        $this->transfer_files_handler->init($this->sync_list, $this->installation, $this->job);
    }

    /**
     * Handle files step
     * @since 1.0.3
     */
    public function runFilesSync(&$installation, &$job)
    {

        $this->installation = $installation;
        $this->job = $job;

        // Start timer
        global $wpsynchro_container;
        $this->timer = $wpsynchro_container->get("class.SyncTimerList");

        // Init 
        $this->init();

        // If not locations, just return
        if (count($this->job->files_sections) == 0) {
            $this->job->files_all_completed = true;
            return;
        }

        // Now, do some work
        $lastrun_time = 0;
        while ($this->timer->shouldContinueWithLastrunTime($lastrun_time)) {

            $lastrun_timer = $this->timer->startTimer("filessync", "while", "lastrun");
            $this->logger->log("INFO", "Running files sync loop - With remaining time: " . $this->timer->getRemainingSyncTime() . " and last run time: " . $lastrun_time);

            // If there is errors, break out
            if (count($this->job->errors) > 0) {
                break;
            }

            // If run requires full time frame
            if ($this->job->request_full_timeframe) {
                break;
            }

            // Calculate progress
            $this->calculateProgress();

            // Start processing
            if (!$this->job->files_all_sections_populated) {
                // Populated from source (list files)
                $this->setFilesProgressDescription(__("Populating files (please wait)", "wpsynchro") . " - " . $this->sync_list->getFileProgressDescriptionPart());
                $this->logger->log("INFO", "Files: Gather file data from source and target - " . $this->sync_list->getFileProgressDescriptionPart());
                $this->populate_list_handler->populateFilelist();
            } else if (!$this->job->files_all_sections_path_handled) {
                // Determine files to transfer and delete  
                $this->setFilesProgressDescription(__("Determine files to delete and transfer", "wpsynchro"));
                $this->logger->log("INFO", "Files: Determine files to delete/transfer");
                $this->path_handler->processFilelist();
            } else if (!$this->job->files_all_completed) {
                // Transfer the needed files - Copy from source to target    
                $this->setFilesProgressDescription(__("Transferring files", "wpsynchro") . " - " . $this->sync_list->getFileProgressDescriptionPart());
                $this->logger->log("INFO", "Files: Transferring files - " . $this->sync_list->getFileProgressDescriptionPart());
                $this->transfer_files_handler->transferFiles();
            } else {
                $this->setFilesProgressDescription("");
                // Nothing more to do
                break;
            }

            // Make sure we dont pass the max allotted time     
            $lastrun_time = $this->timer->getElapsedTimeToNow($lastrun_timer);

            // Update state
            $this->sync_list->updateSectionState();

            // Calculate progress
            $this->calculateProgress();

            // Save status to DB       
            $this->job->save();
        }
    }

    /**
     *  Set a new progress description and save if neeeded
     */
    public function setFilesProgressDescription($desc)
    {
        if ($this->job->files_progress_description != $desc) {
            $this->job->files_progress_description = $desc;
            $this->job->save();
        }
    }

    /**
     * Calculate progress
     * @since 1.0.3
     */
    public function calculateProgress()
    {
        // We start at 5, to show we are started
        $progress_number = 5;

        /**
         *  File population
         */
        $progress_per_section_part = 65 / (count($this->job->files_sections) * 2);
        foreach ($this->job->files_sections as &$section) {
            if ($section->source_files_population_complete) {
                $progress_number += $progress_per_section_part;
            }
            if ($section->target_files_population_complete) {
                $progress_number += $progress_per_section_part;
            }
        }
        $progress_number = ceil($progress_number);

        /**
         * When population is done, we need to transfer the needed files
         */
        $total_size = $this->job->files_needs_transfer_size;
        $size_completed = $this->job->files_transfer_completed_size;
        if ($total_size > 0) {
            $rest_progress = 100 - $progress_number;
            if ($size_completed > 0) {
                $hash_percent_completed = $size_completed / $total_size;
                if ($hash_percent_completed == 1) {
                    $progress_number = 100;
                } else {
                    $progress_number += floor($rest_progress * $hash_percent_completed);
                }
            }
        }

        $this->job->files_progress = $progress_number;

        if ($this->job->files_progress >= 100) {
            $this->job->files_progress = 100;
            $this->job->files_progress_description = "";
            $this->job->files_all_completed = true;
        }
    }
}
