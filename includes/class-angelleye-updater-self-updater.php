<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * AngellEYE Updater Self Updater Class
 *
 * The AngellEYE Updater self-updater class.
 *
 * @package WordPress
 * @subpackage AngellEYE Updater
 * @category Core
 * @author      Angell EYE <service@angelleye.com>
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * var $file
 *
 * - __construct()
 * - update_check()
 * - plugin_information()
 * - request()
 */

class AngellEYE_Updater_Self_Updater {

    public $file;
    private $api_url;

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct($file) {
        $this->api_url = AU_WEBSITE_URL . '?AngellEYE_Activation';
        $this->file = plugin_basename($file);

        // Check For Updates
        add_filter('pre_set_site_transient_update_plugins', array($this, 'update_check'));

        // Check For Plugin Information
        add_filter('plugins_api', array($this, 'plugin_information'), 10, 3);
    }

// End __construct()

    /**
     * update_check function.
     *
     * @access public
     * @param object $transient
     * @return object $transient
     */
    public function update_check($transient) {
        // Check if the transient contains the 'checked' information
        // If no, just return its value without hacking it
        if (empty($transient->checked))
            return $transient;

        // The transient contains the 'checked' information
        // Now append to it information form your own API
        $args = array(
            'action' => 'pluginupdatecheck',
            'plugin_name' => $this->file,
            'version' => $transient->checked[$this->file],
            'product_id' => 'angelleye-updater',
            'license_hash' => ''
        );

        // Send request checking for an update
        $response = $this->request($args);

        // If response is false, don't alter the transient
        if (false !== $response) {
            $transient->response[$this->file] = $response;
        }
        return $transient;
    }

// End update_check()

    /**
     * plugin_information function.
     *
     * @access public
     * @return object $response
     */
    public function plugin_information($false, $action, $args) {
        $transient = get_site_transient('update_plugins');

        // Check if this plugins API is about this plugin
        if (!isset($args->slug) || $args->slug != dirname($this->file)) {
            return $false;
        }

        // POST data to send to your API
        $args = array(
            'action' => 'plugininformation',
            'plugin_name' => $this->file,
            'version' => $transient->checked[$this->file],
            'product_id' => 'angelleye-updater',
            'license_hash' => ''
        );

        // Send request for detailed information
        $response = $this->request($args);
        if (isset($response->sections) && !empty($response->sections)) {
            $response->sections = (array) $response->sections;
        }

        if (isset($response->compatibility) && !empty($response->compatibility)) {
            $response->compatibility = (array) $response->compatibility;
        }

        if (isset($response->tags) && !empty($response->tags)) {
            $response->tags = (array) $response->tags;
        }

        if (isset($response->contributors) && count($response->contributors) > 0) {
            $response->contributors = (array) $response->contributors;
        }

        if (isset($response->compatibility) && count($response->compatibility) > 0) {
            foreach ($response->compatibility as $k => $v) {
                $response->compatibility[$k] = (array) $v;
            }
        }

        return $response;
    }

// End plugin_information()

    /**
     * request function.
     *
     * @access public
     * @param array $args
     * @return object $response or boolean false
     */
    public function request($args) {
        // Send request

        if (isset($args) && !empty($args)) {
            $plugin_checkup_url = $this->api_url . '&action=' . $args['action'];
        }
        $request = wp_remote_post($plugin_checkup_url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('user-agent' => 'AngellEYE_Updater'),
            'body' => $args,
            'cookies' => array(),
            'sslverify' => false
        ));

        // Make sure the request was successful
        if (is_wp_error($request) or wp_remote_retrieve_response_code($request) != 200) {
            // Request failed
            return false;
        }

        // Read server response, which should be an object
        if ($request != '') {
            $response = json_decode(wp_remote_retrieve_body($request));
        } else {
            $response = false;
        }

        if (is_object($response) && isset($response->payload)) {
            return $response->payload;
        } else {
            // Unexpected response
            return false;
        }
    }

// End prepare_request()
}

// End Class
?>