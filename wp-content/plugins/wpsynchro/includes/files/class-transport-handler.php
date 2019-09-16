<?php
namespace WPSynchro\Files;

/**
 * Class for processing filedata and file from pull/push
 * @since 1.3.0
 */
class TransportHandler
{

    public function handleFileTransport($data, $files)
    {

        // init 
        global $wpsynchro_container;
        $common = $wpsynchro_container->get("class.CommonFunctions");
        $timer = $wpsynchro_container->get("class.SyncTimerList");

        // Setup return array
        $result = new \stdClass();
        $result->data = array();
        $result->errors = array();
        $result->warnings = array();
        $result->debugs = array();

        // Generate the lookup
        $files_key_data_lookup = array();
        foreach ($data as $filedata) {
            if ($filedata->is_error) {
                // File could not be read 
                $result->warnings[] = sprintf(__("File no longer exist or could not be read: %s  - Ignoring", "wpsynchro"), $filedata->filename);
                $result->data[$filedata->key]['success'] = false;
                $result->data[$filedata->key]['size'] = $filedata->size;
                unset($files[$filedata->key]);
            }
            $files_key_data_lookup[$filedata->key] = $filedata;
        }

        // Run through files and write it to disk
        foreach ($files as $file) {
            $filedata = $files_key_data_lookup[$file->key];
            $result->data[$file->key]['size'] = $filedata->size;

            if ($filedata->is_dir) {
                // If dir exists as a file, remove that first
                if (file_exists($filedata->target_file) && is_file($filedata->target_file)) {
                    if (!unlink($filedata->target_file)) {
                        $result->errors[] = sprintf(__("Could not delete file %s on target before creating directory with same location", "wpsynchro"), $filedata->target_file);
                    }
                }

                if (wp_mkdir_p($filedata->target_file)) {
                    $result->data[$file->key]['success'] = true;
                } else {
                    $result->data[$file->key]['success'] = false;
                    $result->errors[] = sprintf(__("Could not create directory %s on target", "wpsynchro"), $filedata->target_file);
                }
            } else {
                $dirname = dirname($filedata->target_file);
                if (wp_mkdir_p($dirname)) {
                    $file_data_position = 0;

                    if ($filedata->is_partial) {
                        $file_data_position = $filedata->partial_start;
                        $result->data[$file->key]['partial'] = $filedata->is_partial;
                        $result->data[$file->key]['last_partial_position'] = $file_data_position;
                        $result->data[$file->key]['partial_position'] = $file_data_position + strlen($file->data);
                    }

                    // If file exists as a dir, remove that first
                    if (file_exists($filedata->target_file) && is_dir($filedata->target_file)) {
                        $common->removeDirectory($filedata->target_file, $timer);
                    }

                    if ($file_data_position == 0) {
                        if ($filedata->size == 0) {
                            $write_success = touch($filedata->target_file);
                        } else {
                            $write_success = file_put_contents($filedata->target_file, $file->data);
                        }

                        if ($write_success) {
                            $result->data[$file->key]['success'] = true;
                        } else {
                            $result->warnings[] = sprintf(__("Creating file %s failed - Could not write to filesystem - This is normally due to filename not being supported on target platform or because of wrong permissions.", "wpsynchro"), $filedata->target_file);
                            $result->data[$file->key]['success'] = false;
                        }
                    } else {
                        $file_append_result = file_put_contents($filedata->target_file, $file->data, FILE_APPEND);
                        if ($file_append_result === false) {
                            $result->errors[] = sprintf(__("Appending temporary filedata to %s failed.", "wpsynchro"), $filedata->target_file);
                            $result->data[$file->key]['success'] = false;
                        } else {
                            $result->data[$file->key]['success'] = true;
                        }
                    }
                } else {
                    $result->errors[] = sprintf(__("Could not create directory %s on target for file %s. Check permissions.", "wpsynchro"), $dirname, $filedata->target_file);
                    $result->data[$file->key]['success'] = false;
                }
            }
        }
        return $result;
    }
}
