<?php
namespace WPSynchro\Files;

/**
 * Class for processing paths on file list 
 * @since 1.0.3
 */
class PathHandler
{

    // Data objects   
    public $sync_list = null;
    public $job = null;
    public $timer = null;
    public $logger = null;

    /**
     *  Constructor
     *  @since 1.0.3
     */
    public function __construct()
    {
        
    }

    /**
     *  Initialize class
     *  @since 1.0.3
     */
    public function init(\WPSynchro\Files\SyncList &$sync_list, \WPSynchro\Job &$job)
    {
        $this->job = $job;
        $this->sync_list = $sync_list;
    }

    /**
     * Path processing of file list 
     * @since 1.0.3
     */
    public function processFilelist()
    {

        // Timer
        global $wpsynchro_container;
        $this->timer = $wpsynchro_container->get("class.SyncTimerList");
        $pathhandler_timer = $this->timer->startTimer("filessync", "pathhandler", "run");

        global $wpsynchro_container;

        $this->logger = $wpsynchro_container->get("class.Logger");
        $this->logger->log("INFO", "Begin path handling with remaining time:" . $this->timer->getRemainingSyncTime());

        // Cycle section to handle each section in an appropriate way
        foreach ($this->job->files_sections as &$section) {
            if ($section->files_path_handled) {
                continue;
            }

            // Run through all sections and clean up the work the work that needs to be done
            if ($section->strategy == "clean") {
                if (!$section->files_path_handler_transfers) {
                    $this->setNeededTransfer($section);
                    break;
                }
                if (!$section->files_path_handled_deletes) {
                    $this->setNeededDeletes($section);
                    break;
                }
            } else if ($section->strategy == "keep") {
                if (!$section->files_path_handler_transfers) {
                    $this->setNeededTransfer($section);
                    break;
                }
            }

            $this->logger->log("DEBUG", "Did path handling on section " . $section->name . " with id " . $section->id . " with remaining time " . $this->timer->getRemainingSyncTime());
        }

        $this->logger->log("INFO", "Did path handling in " . $this->timer->endTimer($pathhandler_timer));
    }

    /**
     * Set those that need to be delete on target (for clean strategy)
     * @since 1.2.0
     */
    public function setNeededDeletes(&$section)
    {

        global $wpdb;
        $runtimer = $this->timer->startTimer("filessync", "pathhandling", "needed_deletes");

        // Check if we need to run (such as a file section not on target)
        if ($section->target_id_start === null) {
            $this->logger->log("INFO", "Path handling - " . $section->name . " - Ignored for needs delete because target_id_start is null"); 
            $section->files_path_handled_deletes = true;
            return;
        }

        // Determine the id's to start from and end with
        $start_id = $section->target_id_last;
        $end_id = $start_id + 1000;
        // If the end is after last target id, just set it to last
        if ($end_id > $section->target_id_end) {
            $end_id = $section->target_id_end;
        }
        $section->target_id_last = $end_id;

        $this->logger->log("INFO", "Path handling - " . $section->name . " - Starting setting needed deletes sql with start id: " . $start_id . " and end id: " . $end_id);

        $set_deletes_needed_sql = "update " . $wpdb->prefix . "wpsynchro_sync_list as t1
left join " . $wpdb->prefix . "wpsynchro_sync_list as t2 on t1.needs_delete_hash = t2.needs_delete_hash and t2.origin='source'
set t1.needs_delete=1 
where t1.id > " . $start_id . " and t1.id <= " . $end_id . " and t2.id is null";

        $wpdb->query($set_deletes_needed_sql);
        $this->logger->log("INFO", sprintf("Path handling - Completed setting needed deletes sql - Took %f seconds", $this->timer->getElapsedTimeToNow($runtimer)));

        if ($section->target_id_end == $section->target_id_last) {
            $section->files_path_handled_deletes = true;
        }
    }

    /**
     * Set those that need to be transferred
     * @since 1.2.0
     */
    public function setNeededTransfer(&$section)
    {

        global $wpdb;
        $runtimer = $this->timer->startTimer("filessync", "pathhandling", "needed_transfer");

        // Check if we need to run (such as a file section not on target)
        if ($section->source_id_start === null) {
            $this->logger->log("INFO", "Path handling - " . $section->name . " - Ignored for needs transfer because source_id_start is null"); 
            $section->files_path_handler_transfers = true;
            return;
        }
        
        // Determine the id's to start from and end with
        $start_id = $section->source_id_last;
        $end_id = $start_id + 1000;
        // If the end is after last source id, just set it to last
        if ($end_id > $section->source_id_end) {
            $end_id = $section->source_id_end;
        }
        $section->source_id_last = $end_id;

        $this->logger->log("INFO", "Path handling - " . $section->name . " - Starting setting needed transfer sql with start id: " . $start_id . " and end id: " . $end_id);

        $set_transfer_needed_sql = "update " . $wpdb->prefix . "wpsynchro_sync_list as t1
left join " . $wpdb->prefix . "wpsynchro_sync_list as t2 on t1.needs_transfer_hash = t2.needs_transfer_hash and t2.origin='target'
set t1.needs_transfer=1 
where t1.id > " . $start_id . " and t1.id <= " . $end_id . " and t2.id is null";
        $wpdb->query($set_transfer_needed_sql);

        $this->logger->log("INFO", sprintf("Path handling - Completed setting needed transfer sql - Took %f seconds", $this->timer->getElapsedTimeToNow($runtimer)));

        if ($section->source_id_end == $section->source_id_last) {
            $section->files_path_handler_transfers = true;
        }
    }
}
