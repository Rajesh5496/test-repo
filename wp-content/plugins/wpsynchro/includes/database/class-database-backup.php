<?php
namespace WPSynchro\Database;

/**
 * Class for handling database backup
 * @since 1.2.0
 */
class DatabaseBackup
{

    // Data objects
    public $job = null;
    public $installation = null;
    public $databasesync = null;
    public $logger = null;
    public $timer = null;
    public $table_prefix;

    /**
     * Constructor
     * @since 1.2.0
     */
    public function __construct()
    {

        global $wpsynchro_container;
        $this->databasesync = $wpsynchro_container->get('class.DatabaseSync');
        $this->logger = $wpsynchro_container->get("class.Logger");
        $commonfunctions = $wpsynchro_container->get('class.CommonFunctions');
        $this->table_prefix = $commonfunctions->getDBTempTableName();
    }

    /**
     *  Backup database on target
     *  @since 1.2.0
     */
    public function backupDatabase(&$installation, &$job)
    {

        // Start timer
        global $wpsynchro_container;
        $this->timer = $wpsynchro_container->get("class.SyncTimerList");

        $this->installation = &$installation;
        $this->job = &$job;

        $this->logger->log("INFO", "Starting database backup loop with remaining time: " . $this->timer->getRemainingSyncTime());

        // Make sure we have a list of tables to do
        if ($this->job->db_backup_tables == null) {
            $this->createListOfTablesToBackup();
        }

        if (count($this->job->db_backup_tables) == 0) {
            // update progress
            $this->updateProgress();
            return;
        }

        // Do a work chunk on remote end
        foreach ($this->job->db_backup_tables as &$table) {
            if ($table->is_completed) {
                continue;
            }

            while (!$table->is_completed) {
                $lastrun_timer = $this->timer->startTimer("databasebackup", "while", "lastrun");

                // Set temp name on table to the same as table, as we are not renaming anything
                $table->temp_name = $table->name;

                // Send table to be dumped on remote
                $url = $this->job->to_rest_base_url . "wpsynchro/v1/backupdatabase/";
                $body = new \stdClass();
                $body->table = $table;
                $body->filename = "database_backup_" . $this->job->id . ".sql";
                $body->memory_limit = intval($this->job->to_memory_limit * 0.7);

                // Get remote transfer object
                $remotetransport = $wpsynchro_container->get('class.RemoteTransfer');
                $remotetransport->init();
                $remotetransport->setUrl($url);
                $remotetransport->setDataObject($body);

                $databasebackup_result = $remotetransport->remotePOST();

                if ($databasebackup_result->isSuccess()) {
                    $result_body = $databasebackup_result->getBody();
                    $result_table = $result_body->table;
                    // Copy data
                    $table->completed_rows = $result_table->completed_rows;
                    $table->last_primary_key = $result_table->last_primary_key;
                    // Check for completion
                    if ($table->completed_rows >= $table->rows) {
                        $table->completed_rows = $table->rows;
                        $table->is_completed = true;
                    }
                } else {
                    $this->job->errors[] = __("Database backup REST Service responded with error. Check log for further information.", "wpsynchro");
                }

                // update progress
                $this->updateProgress();

                // Timings
                $remainingtime = $this->timer->getRemainingSyncTime();
                $lastrun_time = $this->timer->endTimer($lastrun_timer);
                $this->logger->log("DEBUG", sprintf("Backup table %s with rows %d / %d with remainingtime %s and lastrun %s", $table->name, $table->completed_rows, $table->rows, $remainingtime, $lastrun_time));
                if (!$this->timer->shouldContinueWithLastrunTime($lastrun_time)) {
                    $this->logger->log("INFO", "Ending database backup loop with remaining time: " . $this->timer->getRemainingSyncTime());
                    return;
                }
            }
        }

        $this->logger->log("INFO", "Ending database backup loop with remaining time: " . $this->timer->getRemainingSyncTime());
    }

    /**
     *  Create list of tables to backup
     *  @since 1.2.0
     */
    public function createListOfTablesToBackup()
    {
        $this->job->db_backup_tables = array();
        foreach ($this->job->to_dbmasterdata as $table) {
            if (strpos($table->name, $this->table_prefix) === 0) {
                continue;
            }

            $table->is_completed = false;
            $this->job->db_backup_tables[] = $table;
        }

        // Remove tables from, if not all tables should be synced
        if ($this->installation->include_all_database_tables === false) {
            $onlyinclude = $this->installation->only_include_database_table_names;
            $newdbdata = [];
            foreach ($this->job->db_backup_tables as $table) {

                if (in_array($table->name, $onlyinclude)) {
                    $newdbdata[] = $table;
                }
            }
            $this->job->db_backup_tables = $newdbdata;
        }
    }

    /**
     *  Create list of tables to backup
     *  @since 1.2.0
     */
    public function updateProgress()
    {
        $totalrows = 0;
        $completed_rows = 0;
        $completion_percent = 0;
        foreach ($this->job->db_backup_tables as $table) {
            $totalrows += $table->rows;
            $completed_rows += $table->completed_rows;
        }

        if ($totalrows == 0) {
            $this->job->database_backup_progress = 100;
            $this->job->database_backup_completed = true;
            $this->job->database_backup_progress_description = "";
            $this->job->save();
            return;
        }

        if ($completed_rows > 0) {
            $completion_percent = intval(($completed_rows / $totalrows) * 100);
            if ($completion_percent > 100) {
                $completion_percent = 100;
            }
        }

        if ($completion_percent == 100) {
            $this->job->database_backup_completed = true;
        }
        $this->job->database_backup_progress = $completion_percent;

        if ($completion_percent < 100) {
            $this->job->database_backup_progress_description = sprintf(__("Row %d / %d", "wpsynchro"), $completed_rows, $totalrows);
        } else {
            $this->job->database_backup_progress_description = "";
        }
        $this->job->save();
    }
}
