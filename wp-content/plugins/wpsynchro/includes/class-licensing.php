<?php
namespace WPSynchro;

/**
 * Class used for license handling (only used for PRO version)
 * @since 1.0.3
 */
class Licensing
{

    public $validation_timeout = 86400; // 86400 = 1 day
    public $time_between_retries = 300; // 300 = 5 min
    public $max_retries = 10;
    public $license_server_service = "https://wpsynchro.com/wp-json/wpsynchro/com/v1/license/";
    public $synchronization_server_service = "https://wpsynchro.com/wp-json/wpsynchro/com/v1/synchronizerequest/";

    /**
     *  Constructor
     */
    public function __construct()
    {
        
    }

    /**
     * Get license status
     * @since 1.0.3
     */
    public function getLicenseDetails()
    {
        $licensedetails = get_transient('wpsynchro_license_key_validation');
        if ($licensedetails == null) {
            $this->verifyLicense();
            return get_transient('wpsynchro_license_key_validation');
        } else {
            return $licensedetails;
        }
    }

    /**
     * Verify license
     * @since 1.0.3
     */
    public function verifyLicense()
    {

        if (!\WPSynchro\WPSynchro::isPremiumVersion()) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        // Check validation transient as we dont want to check every time   
        if (false === ( $validation_obj = get_transient('wpsynchro_license_key_validation') )) {
            // No validation data - Must be first time or new key
            $license_key = $this->getCurrentLicenseKey();

            // If license key is not set, just return false
            if (strlen(trim($license_key)) == 0) {
                return false;
            }
            // Setup new base object
            $validation_obj = new \stdClass();
            $validation_obj->license_key = $license_key;
            $validation_obj->status = null;
            $validation_obj->timestamp = time();
            $validation_obj->retries = 0;
            $validation_obj->last_retry = null;
            $validation_obj->error = "";
        }


        //  If it has been checked and came back negative, just return, so user can set a new licensekey
        if ($validation_obj->status === false) {
            return false;
        }


        // If it is valid 
        $within_valid_timeframe = ($validation_obj->timestamp > (time() - $this->validation_timeout));
        if ($validation_obj->status === true && $within_valid_timeframe) {
            // Its validated to true and is within time limit
            return true;
        }

        // If it is valid, but has timed out, reset the validation
        if ($validation_obj->status === true && !$within_valid_timeframe) {
            // Reset 
            $validation_obj->status = null;
            $validation_obj->timestamp = time();
            $validation_obj->retries = 0;
            $validation_obj->last_retry = null;
            $validation_obj->error = "";
        }

        // If we are here, we need to check if it validates (taking retries into account)
        if ($validation_obj->retries < 10 && ($validation_obj->last_retry === null || $validation_obj->last_retry <= (time() - $this->time_between_retries) )) {
            $retry_status = $this->checkLicenseKeyOnLicenseServer($validation_obj->license_key);
            if ($retry_status === null) {
                // No useful result
                $validation_obj->retries++;
                $validation_obj->last_retry = time();
            } else {
                $validation_obj->status = $retry_status;
                $validation_obj->timestamp = time();
            }
        }

        if ($validation_obj->retries > 9) {
            $validation_obj->status = false;
            $validation_obj->timestamp = time();
            $validation_obj->error = __("License validation failed - Please update your license or contact support", "wpsynchro");
        }

        set_transient('wpsynchro_license_key_validation', $validation_obj);

        if ($validation_obj->status === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check license key on license server
     * @since 1.0.3
     */
    public function checkLicenseKeyOnLicenseServer($key)
    {

        // Contact license server               
        $bodydata = new \stdClass();
        $bodydata->homeurl = \get_home_url();

        // Get remote transfer object
        global $wpsynchro_container;
        $remotetransport = $wpsynchro_container->get('class.RemoteTransfer');
        $remotetransport->init();
        $remotetransport->setUrl($this->license_server_service . $key);
        $remotetransport->setDataObject($bodydata);
        $remotetransport->setSendDataAsJSON();
        $license_result = $remotetransport->remotePOST();

        if ($license_result->isSuccess()) {
            if (isset($license_result->getBody()->valid)) {
                return $license_result->getBody()->valid;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get current license key
     * @since 1.0.3
     */
    public function getCurrentLicenseKey()
    {
        if (!\WPSynchro\WPSynchro::isPremiumVersion()) {
            // @codeCoverageIgnoreStart
            return "";
            // @codeCoverageIgnoreEnd
        }
        $licensekey = get_option("wpsynchro_license_key", "");
        return $licensekey;
    }

    /**
     * Set current license key
     * @since 1.0.3
     */
    public function setCurrentLicenseKey($newkey)
    {
        // Save new license key
        update_option("wpsynchro_license_key", $newkey, false);
        // Reset current validation in db, if it exist, so new is validated
        delete_transient("wpsynchro_license_key_validation");
        // Reset last healthcheck, to make sure things are okay after
        delete_site_option("wpsynchro_healthcheck_timestamp");
        return true;
    }

    /**
     * Get license error message
     * @since 1.0.3
     */
    public function getLicenseErrorMessage()
    {
        return sprintf(__("%s uses a WP Synchro PRO version that can not be validated using the current license key. Update license key on this site to a valid one and try again. This can be done in menu WP Synchro > Licensing.", "wpsynchro"), get_home_url());
    }

    /**
     * Check if problems with licensing
     * @since 1.0.3
     */
    public function hasProblemWithLicensing()
    {
        if (\WPSynchro\WPSynchro::isPremiumVersion()) {
            $details = $this->getLicenseDetails();
            if ($details && $details->status === true) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     *  Verify license when synchronizing
     *  @since 1.0.3
     */
    public function verifyLicenseForSynchronization($from_url, $to_url)
    {

        $result = new \stdClass();
        $result->state = false;
        $result->errors = array();

        $verified_license = $this->verifyLicense();
        if ($verified_license) {
            // If licens is verified, send a request for synchronizing to license server

            $bodydata = new \stdClass();
            $bodydata->from_url = $from_url;
            $bodydata->to_url = $to_url;

            // Get remote transfer object
            global $wpsynchro_container;
            $remotetransport = $wpsynchro_container->get('class.RemoteTransfer');
            $remotetransport->init();
            $remotetransport->setUrl($this->synchronization_server_service . $this->getCurrentLicenseKey());
            $remotetransport->setDataObject($bodydata);
            $remotetransport->setSendDataAsJSON();
            $sync_request_result = $remotetransport->remotePOST();
 
            // Check the result
            if ($sync_request_result->isSuccess()) {
                $body = $sync_request_result->getBody();
                
                if (isset($body->valid) && $body->valid === true) {
                    $result->state = true;
                } else {
                    $result->state = false;
                    $result->errors[] = __("Synchronization was denied by license server - Check log for details", "wpsynchro");
                }
            } else {
                $result->state = false;
                $result->errors[] = $this->getLicenseErrorMessage();
            }
        } else {
            $result->state = false;
            $result->errors[] = $this->getLicenseErrorMessage();
        }
        
        
        return $result;
    }
}
