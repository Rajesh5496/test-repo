<?php
namespace WPSynchro;

/**
 * Class for handling REST for WP Synchro
 *
 * @since 1.0.0
 */
class RESTServices
{

    /**
     * Setup the REST routes needed for WP Synchro
     *
     * @since 1.0.0
     */
    public function setup()
    {
        // Add "initiate" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/initiate/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTInitiate')
                )
            );
        }
        );

        // Add "masterdata" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/masterdata/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTMasterdata'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "backupdatabase" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/backupdatabase/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTBackupDatabase'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "clientsyncdatabase" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/clientsyncdatabase/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTClientSyncDatabase'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "populatefilelist" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/populatefilelist/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTPopulateFileList'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "filetransfer" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/filetransfer/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTFileTransfer'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "getfiles" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/getfiles/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTGetFiles'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "finalize" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/finalize/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTFinalize'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "filesystem" REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/filesystem/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTFilesystem'),
                'permission_callback' => array($this, 'permissionCheck'),
                )
            );
        }
        );

        // Add "synchronize"  REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/synchronize/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTSynchronize'),
                'permission_callback' => function($request) {
                    if ($this->permissionCheck($request)) {
                        return true;
                    } else {
                        return current_user_can('manage_options');
                    }
                },
                )
            );
        }
        );

        // Add "status"  REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/status/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTStatus'),
                'permission_callback' => function($request) {
                    if ($this->permissionCheck($request)) {
                        return true;
                    } else {
                        return current_user_can('manage_options');
                    }
                },
                )
            );
        }
        );

        // Add "downloadlog"  REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/downloadlog/', array(
                'methods' => 'GET',
                'callback' => array($this, 'restPOSTDownloadLog'),
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
                )
            );
        }
        );

        // Add "healthcheck"  REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/healthcheck/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTHealthCheck'),
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
                )
            );
        }
        );

        // Add "timeoutcheck"  REST endpoint
        add_action(
            'rest_api_init', function () {
            register_rest_route(
                'wpsynchro/v1', '/timeoutcheck/', array(
                'methods' => 'POST',
                'callback' => array($this, 'restPOSTTimeoutCheck'),
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
                )
            );
        }
        );
    }

    /**
     *  Validates access to WP Synchro REST services
     */
    public function permissionCheck($request)
    {

        $token = $request->get_param("token");
        if ($token == null || strlen($token) < 20) {
            return false;
        }
        $token = trim($token);

        // Get current correct token to compare with
        require_once("class-common-functions.php");
        $common = new \WPSynchro\CommonFunctions();

        // Check if it is a transfer token
        return $common->validateTransferToken($token);
    }

    /**
     *  Used for Filesystem API, used by addedit to select files and folders
     *  @since 1.2.0
     */
    public function restPOSTFilesystem($request)
    {
        require_once( 'REST/class-REST-Filesystem.php' );
        $restservice = new \WPSynchro\REST\RESTFilesystem();
        return $restservice->service($request);
    }
  
    /**
     *  Used for HealthCheck timeout check
     *  @since 1.3.2
     */
    public function restPOSTTimeoutCheck($request)
    {
        require_once( 'REST/class-REST-TimeoutCheck.php' );
        $restservice = new \WPSynchro\REST\TimeoutCheck();
        return $restservice->service($request);
    }
    
    /**
     *  Used for HealthCheck
     *  @since 1.1
     */
    public function restPOSTHealthCheck($request)
    {
        require_once( 'REST/class-REST-HealthCheck.php' );
        $restservice = new \WPSynchro\REST\HealthCheck();
        return $restservice->service($request);
    }

    /**
     *  Used for transferring files to target
     *  @since 1.0.3
     */
    public function restPOSTGetFiles($request)
    {
        require_once( 'REST/class-REST-GetFiles.php' );
        $restservice = new \WPSynchro\REST\GetFiles();
        return $restservice->service($request);
    }

    /**
     *  Used for transferring files to target
     *  @since 1.0.3
     */
    public function restPOSTFinalize($request)
    {
        require_once( 'REST/class-REST-Finalize.php' );
        $restservice = new \WPSynchro\REST\Finalize();
        return $restservice->service($request);
    }

    /**
     *  Used for transferring files to target
     *  @since 1.0.3
     */
    public function restPOSTFileTransfer($request)
    {
        require_once( 'REST/class-REST-FileTransfer.php' );
        $restservice = new \WPSynchro\REST\FileTransfer();
        return $restservice->service($request);
    }

    /**
     *  Used for returning list data on files
     *  @since 1.0.3
     */
    public function restPOSTPopulateFileList($request)
    {
        require_once( 'REST/class-REST-PopulateFileList.php' );
        $restservice = new \WPSynchro\REST\PopulateFileList();
        return $restservice->service($request);
    }

    /**
     *  Act as client for database sync for executing SQL on remote
     *  @since 1.0.0
     */
    public function restPOSTClientSyncDatabase($request)
    {

        require_once( 'REST/class-REST-ClientSyncDatabase.php' );
        $restservice = new \WPSynchro\REST\RESTClientSyncDatabase();
        return $restservice->service($request);
    }

    /**
     *  Handles database backup
     *  @since 1.0.0
     */
    public function restPOSTBackupDatabase($request)
    {

        require_once( 'REST/class-REST-DatabaseBackup.php' );
        $restservice = new \WPSynchro\REST\RESTDatabaseBackup();
        return $restservice->service($request);
    }

    /**
     *  Returns initiate data for this installation - Called to start synchronization
     *  @since 1.0.0
     */
    public function restPOSTInitiate($request)
    {

        require_once( 'REST/class-REST-Initiate.php' );
        $restservice = new \WPSynchro\REST\RESTInitiate();
        return $restservice->service($request);
    }

    /**
     *  Returns masterdata for this installation. Aka database structure etc.
     *  @since 1.0.0
     */
    public function restPOSTMasterdata($request)
    {

        require_once( 'REST/class-REST-Masterdata.php' );
        $restservice = new \WPSynchro\REST\RESTMasterData();
        return $restservice->service($request);
    }

    /**
     *  REST synchronize
     *  @since 1.0.0
     */
    public function restPOSTSynchronize($request)
    {

        require_once( 'REST/class-REST-Synchronize.php' );
        $restservice = new \WPSynchro\REST\RESTSynchronize();
        return $restservice->service($request);
    }

    /**
     *  REST status
     *  @since 1.0.0
     */
    public function restPOSTStatus($request)
    {

        require_once( 'REST/class-REST-Status.php' );
        $restservice = new \WPSynchro\REST\RESTStatus();
        return $restservice->service($request);
    }

    /**
     *  REST Download log
     *  @since 1.0.0
     */
    public function restPOSTDownloadLog($request)
    {

        require_once( 'REST/class-REST-DownloadLog.php' );
        $restservice = new \WPSynchro\REST\DownloadLog();
        return $restservice->service($request);
    }
}
