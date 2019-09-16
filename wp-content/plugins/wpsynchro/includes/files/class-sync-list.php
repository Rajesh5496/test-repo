<?php
namespace WPSynchro\Files;

/**
 * Class for handling the file sync list used to synchronize files
 * @since 1.0.3
 */
class SyncList
{

    // Base data
    public $job = null;
    public $installation = null;
    // Progress counters
    public $tmp_prefix = "wpsyntmp-";
    // Cache
    public $section_lookup = array();

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
    public function init(\WPSynchro\Installation &$installation, \WPSynchro\Job &$job)
    {
        $this->installation = &$installation;
        $this->job = $job;

        if (!$this->job->files_sync_list_initialized) {
            // Setup initial data
            $this->setupInitialFileStructure();
            // Make sure table for sync list exist and is truncated
            $this->checkSyncTableExistAndIsTruncated();
            // Set it to initialized
            $this->job->files_sync_list_initialized = true;
            // Save sections            
        }
    }

    /**
     *  Create section for sections array
     *  @since 1.2.0
     */
    public function createSection($type, $name, $is_file, $exclusions, $strategy, $temp_dirs = array(), $source_basepath = "", $target_basepath = "")
    {
        global $wpsynchro_container;
        $common = $wpsynchro_container->get("class.CommonFunctions");

        $tmp_obj = new \stdClass();
        // Section base data
        $tmp_obj->id = uniqid();
        $tmp_obj->name = $name;
        $tmp_obj->type = $type;
        $tmp_obj->is_file = $is_file;
        $tmp_obj->exclusions = $exclusions;
        $tmp_obj->strategy = $strategy;
        $tmp_obj->temp_locations_in_basepath = $temp_dirs;
        $tmp_obj->source_basepath = trailingslashit($common->fixPath($source_basepath));
        $tmp_obj->target_basepath = trailingslashit($common->fixPath($target_basepath));
        // Population
        $tmp_obj->source_files_population_complete = false;
        $tmp_obj->target_files_population_complete = false;
        $tmp_obj->files_population_source_count = 0;          // Count of files found on source to this point (can increase)
        $tmp_obj->files_population_target_count = 0;          // Count of files found on target to this point (can increase)
        // Path handling
        $tmp_obj->source_id_last = -1;
        $tmp_obj->source_id_start = -1;
        $tmp_obj->source_id_end = -1;
        $tmp_obj->target_id_last = -1;
        $tmp_obj->target_id_start = -1;
        $tmp_obj->target_id_end = -1;
        $tmp_obj->files_path_handler_transfers = false;
        $tmp_obj->files_path_handled_deletes = false;
        $tmp_obj->files_path_handled = false;

        return $tmp_obj;
    }

    /**
     *  Setup initial objects and data
     *  @since 1.0.3
     */
    public function setupInitialFileStructure()
    {
        if (!$this->job->files_sync_list_initialized) {

            // Preset: All or file_all
            if ($this->installation->sync_preset == 'all' || $this->installation->sync_preset == 'file_all') {
                $this->job->files_sections[] = $this->createSection("sync_preset_all_files", __("All files in webroot", "wpsynchro"), false, "", "clean", array(), $this->job->from_files_home_dir, $this->job->to_files_home_dir);
            } else {
                if ($this->installation->sync_preset == 'none') {

                    // Add all the manually added file locations
                    $counter = 1;
                    foreach ($this->installation->file_locations as $filelocation) {
                        if ($filelocation->base == 'webroot') {
                            $source_basepath = $this->job->from_files_home_dir;
                            $target_basepath = $this->job->to_files_home_dir;
                        } else if ($filelocation->base == 'outsidewebroot') {
                            $source_basepath = $this->job->from_files_above_webroot_dir;
                            $target_basepath = $this->job->to_files_above_webroot_dir;
                        } else if ($filelocation->base == 'wpcontent') {
                            $source_basepath = $this->job->from_files_wp_content_dir;
                            $target_basepath = $this->job->to_files_wp_content_dir;
                        }

                        $this->job->files_sections[] = $this->createSection("filelocation_" . $counter, __("Filelocation", "wpsynchro") . "_" . $counter, $filelocation->is_file, $filelocation->exclusions, $filelocation->strategy, array($filelocation->path => true), $source_basepath, $target_basepath);
                        $counter++;
                    }
                }
            }

            global $wpsynchro_container;
            $logger = $wpsynchro_container->get("class.Logger");
            $logger->log("DEBUG", "Section list after init:", $this->job->files_sections);
        }
    }

    /**
     *  Make sure sync list table exist and is truncated
     *  @since 1.2.0
     */
    public function checkSyncTableExistAndIsTruncated()
    {
        // Create sync list table if not exists
        self::createSyncListTable();
    }

    /**
     *  Create sync list db table if not exists (also called from activation hook)
     *  @since 1.2.0
     */
    public static function createSyncListTable()
    {
        global $wpdb;
        $tablename = $wpdb->prefix . "wpsynchro_sync_list";
        // Delete the table
        $wpdb->query("drop table if exists `" . $tablename . "`");

        // Create it again
        $wpsynchro_synclist_sql = "CREATE TABLE $tablename (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                origin varchar(10) DEFAULT NULL,
                section varchar(32) DEFAULT NULL,      
                source_file varchar(4100) DEFAULT NULL,                
                is_dir tinyint(1) DEFAULT 0,  
                size bigint(20) DEFAULT NULL,
                hash varchar(32) DEFAULT NULL,
                is_partial tinyint(1) DEFAULT 0,
                partial_position bigint(20) DEFAULT 0,
                needs_transfer_hash varchar(32) DEFAULT NULL,   
                needs_delete_hash varchar(32) DEFAULT NULL,   
                needs_transfer tinyint(1) DEFAULT 0,  
                needs_delete tinyint(1) DEFAULT 0,         
                PRIMARY KEY  (id),
                KEY origin (origin),
                KEY section (section),
                KEY needs_transfer_hash (needs_transfer_hash),
                KEY needs_delete_hash (needs_delete_hash),
                KEY is_dir (is_dir)
            );";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($wpsynchro_synclist_sql);
    }

    /**
     *  Add file list from population
     *  @since 1.2.0
     */
    public function addUpdateFilelistFromPopulation($type, $section_id, &$filelist)
    {
        global $wpdb;
        // Start timer
        global $wpsynchro_container;
        $timer = $wpsynchro_container->get("class.SyncTimerList");
        $run_timer = $timer->startTimer("synclist", "addupdatefilelistfrompopulation", "lastrun");
        $allotted_time = $timer->getRemainingSyncTime();

        $section = $this->getSectionByID($section_id);

        global $wpsynchro_container;
        $logger = $wpsynchro_container->get("class.Logger");

        $insert_query_part = "INSERT INTO " . $wpdb->prefix . "wpsynchro_sync_list (origin,section, source_file, is_dir, size, hash) VALUES ";
        $insert_value_part_arr = array();
        $insert_counter = 0;
        $insert_total_counter = 0;

        foreach ($filelist as $file) {
            $insert_counter++;
            $insert_total_counter++;
            if ($insert_counter > 995) {

                if ($timer->getElapsedTimeToNow($run_timer) >= $allotted_time) {

                    $logger->log("ERROR", sprintf("Could not complete file population sql inserts in time. Had %f seconds to complete. Got to %d out of %d", $allotted_time, $insert_total_counter, count($filelist)));
                    $this->job->errors[] = __("Could not complete inserting file list into database in time before PHP timeout. You should increse PHP max_execution_time to prevent this error. Or you can make a custom synchronization with only the needed files.", "wpsynchro");
                    return false;
                }

                $wpdb->query($insert_query_part . " " . implode(",", $insert_value_part_arr));
                $insert_counter = 0;
                $insert_value_part_arr = array();
            }

            // Add value part
            $insert_value_part_arr[] = $wpdb->prepare("(%s,%s,%s,%d,%d,%s)", $type, $section_id, $file->source_file, ($file->is_dir ? 1 : 0), $file->size, $file->hash);
        }
        if ($insert_counter > 0) {
            $wpdb->query($insert_query_part . " " . implode(",", $insert_value_part_arr));
        }

        // Set delete hash and transfer hash
        $this->setDeleteAndTransferHashInDB();

        $logger->log("INFO", sprintf("Inserted %d rows in database from file population. It took %f seconds.", $insert_total_counter, $timer->getElapsedTimeToNow($run_timer)));
        return true;
    }

    /**
     *  Update current state of this object
     *  @since 1.0.3
     */
    public function updateSectionState()
    {

        global $wpdb;

        /**
         *  Check if all is populated
         */
        if (!$this->job->files_all_sections_populated) {
            // Source count
            $source_count = 0;
            foreach ($this->job->files_sections as &$section) {
                $source_count += $section->files_population_source_count;
            }
            $this->job->files_population_source_count = $source_count;

            // Target count
            $target_count = 0;
            foreach ($this->job->files_sections as &$section) {
                $target_count += $section->files_population_target_count;
            }
            $this->job->files_population_target_count = $target_count;

            $this->job->files_all_sections_populated = true;
            $this->job->request_full_timeframe = true;
            foreach ($this->job->files_sections as &$section) {
                if (!$section->source_files_population_complete || !$section->target_files_population_complete) {
                    $this->job->files_all_sections_populated = false;
                    $this->job->request_full_timeframe = false;
                    break;
                }
            }

            // If all sections are population, get some data (only runs once)
            if ($this->job->files_all_sections_populated) {
                foreach ($this->job->files_sections as &$section) {
                    if ($section->strategy == "clean") {
                        // Get id start/end to be used in determining deletes needed (only in clean mode)
                        $section->target_id_start = $wpdb->get_var("SELECT min(id) FROM `" . $wpdb->prefix . "wpsynchro_sync_list` WHERE origin='target' and section='" . $section->id . "'");
                        $section->target_id_end = $wpdb->get_var("SELECT max(id) FROM `" . $wpdb->prefix . "wpsynchro_sync_list` WHERE origin='target' and section='" . $section->id . "'");
                        $section->target_id_last = $section->target_id_start - 1;
                    }
                    // Get id start/end to be used in determining transfer needed
                    $section->source_id_start = $wpdb->get_var("SELECT min(id) FROM `" . $wpdb->prefix . "wpsynchro_sync_list` WHERE origin='source' and section='" . $section->id . "'");
                    $section->source_id_end = $wpdb->get_var("SELECT max(id) FROM `" . $wpdb->prefix . "wpsynchro_sync_list` WHERE origin='source' and section='" . $section->id . "'");
                    $section->source_id_last = $section->source_id_start - 1;
                }
            }

            return;
        }

        /**
         *  Check when path handling is running/done
         */
        if (!$this->job->files_all_sections_path_handled) {

            // Cycle through to see if section is completed
            foreach ($this->job->files_sections as &$section) {
                if ($section->strategy == "clean") {
                    if ($section->files_path_handler_transfers && $section->files_path_handled_deletes) {
                        $section->files_path_handled = true;
                    } else {
                        $section->files_path_handled = false;
                    }
                } else if ($section->strategy == "keep") {
                    if ($section->files_path_handler_transfers) {
                        $section->files_path_handled = true;
                    } else {
                        $section->files_path_handled = false;
                    }
                }
            }

            // Check if all sections are done
            $this->job->files_all_sections_path_handled = true;
            foreach ($this->job->files_sections as &$section) {
                if (!$section->files_path_handled) {
                    $this->job->files_all_sections_path_handled = false;
                }
            }

            // When all sections are done
            if ($this->job->files_all_sections_path_handled) {
                // Set data when we are done
                $this->job->files_needs_transfer = $wpdb->get_var("select count(*) from " . $wpdb->prefix . "wpsynchro_sync_list where needs_transfer=1");
                $this->job->files_needs_transfer_size = $wpdb->get_var("select sum(size) from " . $wpdb->prefix . "wpsynchro_sync_list where needs_transfer=1");
                $this->job->files_needs_delete = $wpdb->get_var("select count(*) from " . $wpdb->prefix . "wpsynchro_sync_list where needs_delete=1");
                // Set debug data on 
                global $wpsynchro_container;
                $logger = $wpsynchro_container->get("class.Logger");
                $logger->log("INFO", sprintf("Marked %d files for transfer with size %d and %d files for deletion.", $this->job->files_needs_transfer, $this->job->files_needs_transfer_size, $this->job->files_needs_delete));
            }
            return;
        }

        /**
         *  Check for all files completed
         */
        if (!$this->job->files_all_completed) {
            $remaining_transfers = $wpdb->get_var("select count(*) from " . $wpdb->prefix . "wpsynchro_sync_list where needs_transfer=1");
            $this->job->files_transfer_completed_counter = $this->job->files_needs_transfer - $remaining_transfers;

            if ($this->job->files_transfer_completed_counter >= $this->job->files_needs_transfer) {
                $this->job->files_all_completed = true;
            }
            return;
        }
    }

    /**
     *  File complete sync: Get the next file that needs moving
     *  @since 1.0.3
     */
    public function getFilesToMoveToTarget($max_size)
    {
        $files = array();

        $file_size_counter = 0;
        $max_files = 100;

        // Get work from DB
        global $wpdb;
        $files_chunk = $wpdb->get_results("SELECT id, section, source_file, is_dir, size, hash, is_partial, partial_position, @total := @total + size AS total FROM (" . $wpdb->prefix . "wpsynchro_sync_list, (select @total := 0) t) WHERE needs_transfer=1 and @total < " . $max_size . " order by source_file asc  limit " . $max_files);

        if ($files_chunk) {
            foreach ($files_chunk as $file) {
                $transferfile = new \WPSynchro\Transport\TransferFile();
                $transferfile->key = $file->id;
                $transferfile->is_dir = $file->is_dir;
                $transferfile->hash = $file->hash;
                $transferfile->is_partial = ($file->is_partial == 1 ? true : false);
                $transferfile->partial_start = $file->partial_position;
                $transferfile->size = $file->size;

                // Set target file and target tmp file
                $currentsection = $this->getSectionByID($file->section);
                $relativepath = ltrim($file->source_file, "/\\");
                $transferfile->filename = trailingslashit($currentsection->source_basepath) . $relativepath;
                $transferfile->target_file = trailingslashit($currentsection->target_basepath) . $relativepath;
                $file_size_counter += $transferfile->size;
                $files[$transferfile->key] = $transferfile;
                if ($file_size_counter >= $max_size) {
                    break;
                }
            }
        }

        return $files;
    }

    /**
     *  File complete sync: Set file key to completed and count up the completed file size
     *  @since 1.0.3
     */
    public function setFileKeyToCompleted($file_key, $file_size, $partial = false, $partial_position = 0, $last_partial_position = 0)
    {
        //error_log("Call completed with key:" . $file_key . " and size: " . $file_size . " and partial: " . ($partial ? "yes" : "no") . " and partialposition: " . $partial_position . " and last partialposition: " . $last_partial_position);

        $needs_transfer = 1;
        $is_partial = ($partial ? 1 : 0);

        if ($partial) {

            $bytes_processed = $partial_position - $last_partial_position;
            $this->job->files_transfer_completed_size += $bytes_processed;

            if ($partial_position >= $file_size) {
                // We are done  
                $needs_transfer = 0;
            }
        } else {
            $needs_transfer = 0;
            $this->job->files_transfer_completed_size += $file_size;
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . "wpsynchro_sync_list", array(
            'is_partial' => $is_partial,
            'partial_position' => $partial_position,
            'needs_transfer' => $needs_transfer
            ), array('id' => $file_key), array(
            '%d',
            '%d',
            '%d'
            ), array('%d')
        );
    }

    /**
     *  Get chunk of files that needs to be deleted
     *  @since 1.2.0
     */
    public function getFilesChunkForDeletion($max_files = 100)
    {
        $files = array();

        // Get work from DB
        global $wpdb;
        $files_chunk = $wpdb->get_results("select id, section, source_file from " . $wpdb->prefix . "wpsynchro_sync_list where needs_delete=1 limit " . $max_files);

        if ($files_chunk) {
            foreach ($files_chunk as $file) {
                $currentsection = $this->getSectionByID($file->section);
                $newfile = new \stdClass();
                $newfile->target_file = trailingslashit($currentsection->target_basepath) . ltrim($file->source_file, "/\\");
                $files[$file->id] = $newfile;
            }
        }

        return $files;
    }

    /**
     *  Set files to deleted
     *  @since 1.2.0
     */
    public function setFilesToDeleted($filelist)
    {

        global $wpdb;
        foreach ($filelist as $key => $file) {
            if (isset($file->deleted) && $file->deleted) {
                $wpdb->update(
                    $wpdb->prefix . "wpsynchro_sync_list", array(
                    'needs_delete' => 0
                    ), array('id' => $key), array(
                    '%d'
                    ), array('%d')
                );
            }
        }
    }

    /**
     *  Get remaining files to delete
     *  @since 1.2.0
     */
    public function getRemainingFileDeletes()
    {
        global $wpdb;
        $delete_count = $wpdb->get_var("select count(*) from " . $wpdb->prefix . "wpsynchro_sync_list where needs_delete=1");
        return $delete_count;
    }

    /**
     *  Get progress part for file description - Just part with (File: X / Y - Size: Z / V)
     *  @since 1.0.3
     */
    public function getFileProgressDescriptionPart()
    {
        if (!$this->job->files_all_sections_populated) {
            $source_count = number_format($this->job->files_population_source_count, 0, ",", ".");
            $target_count = number_format($this->job->files_population_target_count, 0, ",", ".");
            return sprintf(__("(Source: %s files - Target: %s files)", "wpsynchro"), $source_count, $target_count);
        }

        $one_mb = 1024 * 1024;
        $completed_size = intval($this->job->files_transfer_completed_size);
        $total_size = intval($this->job->files_needs_transfer_size);

        // Show in mb
        $completed_size = number_format($completed_size / $one_mb, 0, ",", ".") . " MB";
        $total_size = number_format($total_size / $one_mb, 0, ",", ".") . " MB";

        return sprintf(__("(File: %d / %d - Size: %s / %s)", "wpsynchro"), $this->job->files_transfer_completed_counter, $this->job->files_needs_transfer, $completed_size, $total_size);
    }

    /**
     *  Get section by id
     *  @since 1.2.0
     */
    public function getSectionByID($id)
    {
        if (isset($this->section_lookup[$id])) {
            return $this->section_lookup[$id];
        }

        foreach ($this->job->files_sections as &$section) {
            if ($section->id == $id) {
                $this->section_lookup[$id] = $section;
                return $section;
            }
        }
        return null;
    }

    /**
     *  Set hashes for delete and transfer detection
     *  @since 1.2.0
     */
    public function setDeleteAndTransferHashInDB()
    {
        global $wpdb;
        global $wpsynchro_container;
        $logger = $wpsynchro_container->get("class.Logger");

        // Set transfer hash
        $setsql = "update `" . $wpdb->prefix . "wpsynchro_sync_list` set needs_transfer_hash=md5(concat(section,source_file,is_dir,size,hash)) where needs_transfer_hash is null";
        $rows_changed = $wpdb->query($setsql);
        $logger->log("DEBUG", "Updated needs_transfer_hash on " . $rows_changed . " rows");

        // Set delete hash
        $deletehashsql = "update `" . $wpdb->prefix . "wpsynchro_sync_list` set needs_delete_hash=md5(concat(section,source_file)) where needs_delete_hash is null";
        $delete_hash_rows_changed = $wpdb->query($deletehashsql);
        $logger->log("DEBUG", "Updated needs_delete_hash on " . $delete_hash_rows_changed . " rows");
    }

}
