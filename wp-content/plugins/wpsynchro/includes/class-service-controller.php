<?php
namespace WPSynchro;

/**
 * Class for setting up the service controller
 *
 * @since 1.0.0
 */
class ServiceController
{

    private $map = array();
    private $singletons = array();

    public function add($identifier, $function)
    {
        $this->map[$identifier] = $function;
    }

    public function get($identifier)
    {
        if (isset($this->singletons[$identifier])) {
            return $this->singletons[$identifier];
        }
        return $this->map[$identifier]();
    }

    public function share($identifier, $function)
    {
        $this->singletons[$identifier] = $function();
    }

    public static function init()
    {

        global $wpsynchro_container;
        $wpsynchro_container = new ServiceController();

        /*
         *  InstallationFactory
         */
        $wpsynchro_container->share(
            'class.InstallationFactory', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-installation-factory.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-location.php' );
            return new \WPSynchro\InstallationFactory();
        }
        );

        /*
         *  Installation
         */
        $wpsynchro_container->add(
            'class.Installation', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-installation.php' );
            return new \WPSynchro\Installation();
        }
        );

        /*
         *  Job
         */
        $wpsynchro_container->add(
            'class.Job', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-job.php' );
            return new \WPSynchro\Job();
        }
        );

        /*
         *  InitiateSync
         */
        $wpsynchro_container->add(
            'class.InitiateSync', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/initiate/class-initiate-sync.php' );
            return new \WPSynchro\Initiate\InitiateSync();
        }
        );

        /*
         *  MasterdataSync
         */
        $wpsynchro_container->add(
            'class.MasterdataSync', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/masterdata/class-masterdata-sync.php' );
            return new \WPSynchro\Masterdata\MasterdataSync();
        }
        );

        /*
         *  DatabaseBackup
         */
        $wpsynchro_container->add(
            'class.DatabaseBackup', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/database/class-database-backup.php' );
            return new \WPSynchro\Database\DatabaseBackup();
        }
        );

        /*
         *  DatabaseSync
         */
        $wpsynchro_container->add(
            'class.DatabaseSync', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/database/class-database-sync.php' );
            return new \WPSynchro\Database\DatabaseSync();
        }
        );

        /*
         *  DatabaseFinalize
         */
        $wpsynchro_container->add(
            'class.DatabaseFinalize', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/database/class-database-finalize.php' );
            return new \WPSynchro\Database\DatabaseFinalize();
        }
        );

        /*
         *  FilesSync
         */
        $wpsynchro_container->add(
            'class.FilesSync', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/files/class-files-sync.php' );
            return new \WPSynchro\Files\FilesSync();
        }
        );

        /*
         *  SyncList
         */
        $wpsynchro_container->add(
            'class.SyncList', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-transfer-file.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/files/class-sync-list.php' );
            return new \WPSynchro\Files\SyncList();
        }
        );

        /*
         *  PopulateListHandler
         */
        $wpsynchro_container->add(
            'class.PopulateListHandler', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/files/class-populate-list-handler.php' );
            return new \WPSynchro\Files\PopulateListHandler();
        }
        );

        /*
         *  PathHandler
         */
        $wpsynchro_container->add(
            'class.PathHandler', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/files/class-path-handler.php' );
            return new \WPSynchro\Files\PathHandler();
        }
        );

        /*
         *  TransferFiles
         */
        $wpsynchro_container->add(
            'class.TransferFiles', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/files/class-transfer-files-handler.php' );
            return new \WPSynchro\Files\TransferFiles();
        }
        );

        /*
         *  TransportHandler
         */
        $wpsynchro_container->add(
            'class.TransportHandler', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/files/class-transport-handler.php' );
            return new \WPSynchro\Files\TransportHandler();
        }
        );

        /*
         *  FinalizeFiles
         */
        $wpsynchro_container->add(
            'class.FinalizeFiles', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/files/class-finalize-files-handler.php' );
            return new \WPSynchro\Files\FinalizeFiles();
        }
        );

        /*
         *  FinalizeSync
         */
        $wpsynchro_container->add(
            'class.FinalizeSync', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/finalize/class-finalize-sync.php' );
            return new \WPSynchro\Finalize\FinalizeSync();
        }
        );

        /*
         *  Location
         */
        $wpsynchro_container->add(
            'class.Location', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-location.php' );
            return new \WPSynchro\Files\Location();
        }
        );

        /*
         *  SynchronizeController - Singleton
         */
        $wpsynchro_container->share(
            'class.SynchronizeController', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-sync-controller.php' );
            return new \WPSynchro\SynchronizeController();
        }
        );

        /*
         *  SynchronizeStatus
         */
        $wpsynchro_container->add(
            'class.SynchronizeStatus', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/status/class-sync-status.php' );
            return new \WPSynchro\Status\SynchronizeStatus();
        }
        );

        /*
         *  CommonFunctions
         */
        $wpsynchro_container->share(
            'class.CommonFunctions', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-common-functions.php' );
            return new \WPSynchro\CommonFunctions();
        }
        );

        /*
         *  DebugInformation
         */
        $wpsynchro_container->add(
            'class.DebugInformation', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-debug-information.php' );
            return new \WPSynchro\DebugInformation();
        }
        );

        /*
         *  Licensing 
         */
        $wpsynchro_container->add(
            'class.Licensing', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/class-licensing.php' );
            return new \WPSynchro\Licensing();
        }
        );

        /**
         *  UpdateChecker
         */
        $wpsynchro_container->add(
            'class.UpdateChecker', function() {

            if (!class_exists("Puc_v4_Factory")) {
                require dirname(__FILE__) . '/updater/Puc/v4p5/Factory.php';
                require dirname(__FILE__) . '/updater/Puc/v4/Factory.php';
                require dirname(__FILE__) . '/updater/Puc/v4p5/Autoloader.php';
                new \Puc_v4p5_Autoloader();
                \Puc_v4_Factory::addVersion('Plugin_UpdateChecker', 'Puc_v4p5_Plugin_UpdateChecker', '4.5');
            }

            $updatechecker = \Puc_v4_Factory::buildUpdateChecker(
                    'https://wpsynchro.com/update/?action=get_metadata&slug=wpsynchro', WPSYNCHRO_PLUGIN_DIR . 'wpsynchro.php', 'wpsynchro'
            );

            return $updatechecker;
        }
        );

        /**
         *  Logger
         */
        $wpsynchro_container->share(
            'class.Logger', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/logger/class-logger.php' );
            $logpath = wp_upload_dir()['basedir'] . "/wpsynchro/";
            $logger = new \WPSynchro\Logger\FileLogger;
            $logger->setFilePath($logpath);

            $enable_debuglogging = get_option('wpsynchro_debuglogging_enabled');
            if ($enable_debuglogging && strlen($enable_debuglogging) > 0) {
                $logger->log_level_threshold = "DEBUG";
            } else {
                $logger->log_level_threshold = "INFO";
            }

            return $logger;
        }
        );

        /**
         *  MetadataLog - for saving data on a sync run
         */
        $wpsynchro_container->share(
            'class.SyncMetadataLog', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/logger/class-sync-metadata-log.php' );
            return new \WPSynchro\SyncMetadataLog();
        }
        );

        /**
         *  SyncTimerList - Controls all the timers during sync
         */
        $wpsynchro_container->share(
            'class.SyncTimerList', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/utilities/class-sync-timer.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/utilities/class-sync-timer-list.php' );
            return new \WPSynchro\Utilities\SyncTimerList();
        }
        );

        /**
         *  Transfer - Get transfer object
         */
        $wpsynchro_container->add(
            'class.Transfer', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-transfer-file.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-transfer.php' );
            return new \WPSynchro\Transport\Transfer();
        }
        );

        /**
         *  RemoteTransfer - Get transfer object to move and receive data
         */
        $wpsynchro_container->add(
            'class.RemoteTransfer', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/interface-remote-connection.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-transfer-file.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-remote-transport.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-remote-transport-result.php' );
            return new \WPSynchro\Transport\RemoteTransport();
        }
        );

        /**
         *  RemoteTransferResult - Result of remote transfer, to be used in code
         */
        $wpsynchro_container->add(
            'class.RemoteTransferResult', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/interface-remote-connection.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-transfer-file.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-remote-transport.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-remote-transport-result.php' );
            return new \WPSynchro\Transport\RemoteTransportResult();
        }
        );

        /**
         *  ReturnResult - Return data from REST service (wrapper for Transfer object)
         */
        $wpsynchro_container->add(
            'class.ReturnResult', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/interface-remote-connection.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-transfer-file.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-remote-transport.php' );
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/transport/class-return-result.php' );
            return new \WPSynchro\Transport\ReturnResult();
        }
        );

        /**
         *  MU Plugin handler
         */
        $wpsynchro_container->share(
            'class.MUPluginHandler', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/compatibility/class-mu-plugin-handler.php' );
            return new \WPSynchro\Compatibility\MUPluginHandler();
        }
        );

        /**
         *  WP CLI command Handler
         */
        $wpsynchro_container->add(
            'class.WPSynchroCLI', function() {
            require_once( WPSYNCHRO_PLUGIN_DIR . 'includes/cli/class-wpsynchro-cli-command.php' );
            return new \WPSynchro\CLI\WP_CLI_Command();
        }
        );
    }
}
