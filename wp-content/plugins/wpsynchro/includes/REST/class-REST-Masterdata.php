<?php
namespace WPSynchro\REST;

/**
 * Class for handling REST service "masterdata"
 * Call should already be verified by permissions callback
 *
 * @since 1.0.0
 */
class RESTMasterData
{

    public $numeric_column_types = array("integer", "int", "decimal", "numeric", "float", "double", "real", "dec", "fixed");

    public function service($request)
    {

        global $wpdb;
        $result = new \stdClass();

        // Check php/mysql/wp requirements
        global $wpsynchro_container;
        $commonfunctions = $wpsynchro_container->get('class.CommonFunctions');
        $compat_errors = $commonfunctions->checkEnvCompatability();
        $temp_table_prefix = $commonfunctions->getDBTempTableName();

        if (count($compat_errors) > 0) {
            // @codeCoverageIgnoreStart
            foreach ($compat_errors as &$error) {
                $error = __("Error from remote server:", "wpsynchro") . " " . $error;
            }
            $result->errors = $compat_errors;

            global $wpsynchro_container;
            $returnresult = $wpsynchro_container->get('class.ReturnResult');
            $returnresult->init();
            $returnresult->setDataObject($result);
            $returnresult->setHTTPStatus(500);
            return $returnresult->echoDataFromRestAndExit();
            // @codeCoverageIgnoreEnd
        }

        $parameters = $request->get_params();
        if (isset($parameters['type'])) {
            $type = $parameters['type'];
        } else {
            $type = array();
        }
        if (isset($parameters['transport'])) {
            $wpsynchrotransfer = true;
        } else {
            $wpsynchrotransfer = false;
        }

        /**
         *  Table to exclude
         */
        $exclude_tables = array();
        $exclude_tables[] = $wpdb->prefix . "wpsynchro_sync_list";
        $exclude_tables[] = $wpdb->prefix . "wpsynchro_file_population_list";

        /**
         *  Get tables in database
         */
        if (in_array('dbtables', $type)) {
            $tables_sql = $wpdb->get_results('SHOW TABLES');
            $tables = array();
            foreach ($tables_sql as $tb) {
                foreach ($tb as $tablename) {
                    if (strpos($tablename, $temp_table_prefix) === 0) {
                        continue;
                    }
                    if (in_array($tablename, $exclude_tables)) {
                        continue;
                    }

                    $tables[] = $tablename;
                }
            }
            $result->dbtables = $tables;
        }

        /**
         *  Get detailed listing of database tables and sizes
         */
        if (in_array('dbdetails', $type)) {
            $tables_sql = $wpdb->get_results('SHOW TABLE STATUS');
            $tables_details = array();
            $table_tmptables_details = array();
            foreach ($tables_sql as $tb) {

                if (in_array($tb->Name, $exclude_tables)) {
                    continue;
                }

                // Get the actual count on rows, because show table status is not precise
                $exactrows = $wpdb->get_var("select count(*) from `" . $tb->Name . "`");
                $tmp_arr = [];
                $tmp_arr['name'] = $tb->Name;
                $tmp_arr['rows'] = intval($exactrows);
                $tmp_arr['completed_rows'] = 0;
                $tmp_arr['row_avg_bytes'] = $tb->Avg_row_length;
                $tmp_arr['data_total_bytes'] = $tb->Data_length;

                // If temp table, add to seperate array (mostly used in finalize)
                if (strpos($tb->Name, $temp_table_prefix) === 0) {
                    $table_tmptables_details[] = $tmp_arr;
                } else {
                    $tables_details[] = $tmp_arr;
                }
            }

            // Show create table
            foreach ($tables_details as &$tb) {
                $createsql = $wpdb->get_row('show create table `' . $tb['name'] . '`', ARRAY_N);
                $createsql[1] = mb_convert_encoding($createsql[1], 'UTF-8', 'UTF-8');
                $tb['create_table'] = $createsql[1];
            }

            // Get primary key (for faster data fetch)
            foreach ($tables_details as &$tb) {
                $primarysql_key = $wpdb->get_row('SHOW KEYS FROM `' . $tb['name'] . '` WHERE Key_name = "PRIMARY"', ARRAY_N);
                $tb['primary_key_column'] = $primarysql_key[4];

                if (!$this->isPrimaryIndexNumeric($tb['create_table'], $tb['primary_key_column'])) {
                    $tb['primary_key_column'] = "";
                }

                $tb['last_primary_key'] = 0;
            }

            // Check for speciel columns, ex blob's
            foreach ($tables_details as &$tb) {
                $tb['binary_columns'] = $this->extractBlobBinaryColumnsFromSQLCreate($tb['create_table']);
            }

            $result->dbdetails = $tables_details;
            $result->tmptables_dbdetails = $table_tmptables_details;
        }

        /**
         *  Get information needed for files
         */
        if (in_array('filedetails', $type)) {

            // Web root
            $result->files_home_dir_readwrite = $commonfunctions->checkReadWriteOnDir($_SERVER['DOCUMENT_ROOT']);
            $result->files_home_dir = untrailingslashit($commonfunctions->fixPath($_SERVER['DOCUMENT_ROOT']));

            // One dir above webroot
            $files_above_webroot_dir = untrailingslashit(dirname($result->files_home_dir));
            $result->files_above_webroot_dir_readwrite = $commonfunctions->checkReadWriteOnDir($files_above_webroot_dir);
            $result->files_above_webroot_dir = $commonfunctions->fixPath($files_above_webroot_dir);

            // Absolut directory of WordPress root folder   
            $result->files_wp_dir = untrailingslashit($commonfunctions->fixPath(ABSPATH));
            $result->files_wp_dir_readwrite = $commonfunctions->checkReadWriteOnDir($result->files_wp_dir);

            // Absolut directory of WP_CONTENT folder, or whatever it is called
            $result->files_wp_content_dir = untrailingslashit($commonfunctions->fixPath(WP_CONTENT_DIR));
            $result->files_wp_content_dir_readwrite = $commonfunctions->checkReadWriteOnDir($result->files_wp_content_dir);

            // Plugins dir
            $result->files_plugins_dir = untrailingslashit($commonfunctions->fixPath(WP_PLUGIN_DIR));
            $result->files_plugins_dir_readwrite = $commonfunctions->checkReadWriteOnDir($result->files_plugins_dir);

            // Themes dir
            $result->files_themes_dir = untrailingslashit($commonfunctions->fixPath(get_theme_root()));
            $result->files_themes_dir_readwrite = $commonfunctions->checkReadWriteOnDir($result->files_themes_dir);

            // Uploads dir
            $upload_dir_obj = wp_upload_dir();
            $result->files_uploads_dir = untrailingslashit($commonfunctions->fixPath($upload_dir_obj['basedir']));
            $result->files_uploads_dir_readwrite = $commonfunctions->checkReadWriteOnDir($result->files_uploads_dir);

            // Get plugin list
            $result->files_plugin_list = array();
            if (!function_exists('get_plugins')) {
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }
            $all_pluginlist = \get_plugins();
            foreach ($all_pluginlist as $pluginslug => $plugindata) {
                $tmp_arr = array();
                $tmp_arr['slug'] = $pluginslug;
                $tmp_arr['name'] = $plugindata['Name'];

                $result->files_plugin_list[] = $tmp_arr;
            }
            // Get theme list
            $result->files_theme_list = array();
            $all_themeslist = \wp_get_themes();
            foreach ($all_themeslist as $themeslug => $wp_theme) {
                $tmp_arr = array();
                $tmp_arr['slug'] = $themeslug;
                $tmp_arr['name'] = $wp_theme->get("Name");

                $result->files_theme_list[] = $tmp_arr;
            }
        }

        /**
         *  Insert standard information on site
         */
        $result->client_home_url = home_url('/');
        $result->rest_base_url = rest_url();
        $result->wpdb_prefix = $wpdb->prefix;
        $result->wp_options_table = $wpdb->options;
        $result->wp_usermeta_table = $wpdb->usermeta;


        // Get max allowed packet size from sql
        $result->max_allowed_packet_size = (int) $wpdb->get_row("SHOW VARIABLES LIKE 'max_allowed_packet'")->Value;
        // Get max post size
        $result->max_post_size = $commonfunctions->convertPHPSizeToBytes(ini_get('post_max_size'));
        if ($result->max_post_size < 1) {
            // If set to 0, which mean unlimited, we just set it to 100mb
            $result->max_post_size = 104857600;
        }
        // Get max upload filesize and if 0, set it to 512mb
        $result->upload_max_filesize = $commonfunctions->convertPHPSizeToBytes(ini_get('upload_max_filesize'));
        if ($result->upload_max_filesize < 1) {
            $result->upload_max_filesize = 536870912;
        }
        // Get memory limit
        $result->memory_limit = $commonfunctions->convertPHPSizeToBytes(ini_get('memory_limit'));
        // If set to -1, which mean unlimited, we just set it to 512mb
        if ( $result->memory_limit < 1) {
            $result->memory_limit = 536870912;
        }
        // Get max_file_uploads
        $result->max_file_uploads = (int) ini_get('max_file_uploads');
        // MySQL version
        $result->sql_version = $wpdb->get_var("select VERSION()");
        // WP Synchro plugin version
        $result->plugin_version = WPSYNCHRO_VERSION . " " . (\WPSynchro\WPSynchro::isPremiumVersion() ? 'PRO' : 'FREE');
        // Include debug data for log
        global $wpsynchro_container;
        $debuginformation = $wpsynchro_container->get('class.DebugInformation');
        $result->debug = $debuginformation->getAllDebugInformationArray();

        if ($wpsynchrotransfer) {
            global $wpsynchro_container;
            $returnresult = $wpsynchro_container->get('class.ReturnResult');
            $returnresult->init();
            $returnresult->setDataObject($result);
            return $returnresult->echoDataFromRestAndExit();
        } else {
            return new \WP_REST_Response($result, 200);
        }
    }

    /**
     *  Function to return column that are blobs
     */
    public function extractBlobBinaryColumnsFromSQLCreate($sqlcreate)
    {
        $columns = array();
        $lines = explode("\n", $sqlcreate);
        foreach ($lines as $line) {
            if (strpos(trim($line), "`") != 0) {
                continue;
            }

            $parts = explode("`", $line);
            if (isset($parts[1]) && isset($parts[2])) {
                if (stripos($parts[2], "blob") > -1 || stripos($parts[2], "binary") > -1 || stripos($parts[2], "point") > -1) {
                    $columns[] = $parts[1];
                }
            }
        }
        return $columns;
    }

    /**
     *  Function to determine if primary index is numeric
     */
    public function isPrimaryIndexNumeric($sqlcreate, $column)
    {
        if ($column == "") {
            return false;
        }

        $lines = explode("\n", $sqlcreate);
        $column = '`' . $column . '`';
        foreach ($lines as $line) {
            if (strpos($line, $column) > -1) {
                $parts = explode("`", $line);
                $col_part = trim($parts[2]);
                $col_parts = explode(" ", $col_part);

                foreach ($this->numeric_column_types as $num_col_type) {
                    if (strpos($col_parts[0], $num_col_type) > -1) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
