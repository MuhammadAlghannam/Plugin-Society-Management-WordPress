<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Country Helper Functions
 * Uses REST Countries API (includes/api/rest-countries.php).
 */

/**
 * Get list of countries from REST Countries API (cached).
 *
 * @return array Array of countries with code as key and name as value
 */
function csi_get_countries() {
    if (function_exists('csi_fetch_countries_from_api')) {
        $api_countries = csi_fetch_countries_from_api();
        if (is_array($api_countries) && !empty($api_countries)) {
            return $api_countries;
        }
    }
    return [];
}

/**
 * Get country name by code
 * 
 * @param string $country_code The country code
 * @return string The country name or the code if not found
 */
function csi_get_country_name($country_code) {
    if (!$country_code) {
        return '';
    }
    $countries = csi_get_countries();
    return isset($countries[strtolower($country_code)]) ? $countries[strtolower($country_code)] : $country_code;
}
