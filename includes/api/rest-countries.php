<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST Countries API Client
 * Fetches country list from https://restcountries.com/v3.1/all
 * Uses WordPress transients for caching (7 days).
 */

/** Transient key for cached countries list */
define('CSI_COUNTRIES_TRANSIENT_KEY', 'csi_rest_countries_list');

/** Cache duration in seconds (7 days) */
define('CSI_COUNTRIES_CACHE_DURATION', 7 * DAY_IN_SECONDS);

/** REST Countries API URL (all countries) */
define('CSI_REST_COUNTRIES_API_URL', 'https://restcountries.com/v3.1/all?fields=cca2,name');

/**
 * Fetch countries from REST Countries API (with caching).
 *
 * @return array|null Array of [cca2_lower => name.common], or null on failure
 */
function csi_fetch_countries_from_api() {
    $cached = get_transient(CSI_COUNTRIES_TRANSIENT_KEY);
    if (is_array($cached) && !empty($cached)) {
        return $cached;
    }

    $response = wp_remote_get(CSI_REST_COUNTRIES_API_URL, array(
        'timeout' => 15,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        return null;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data)) {
        return null;
    }

    $countries = array();
    foreach ($data as $item) {
        $cca2 = isset($item['cca2']) ? $item['cca2'] : null;
        $name = isset($item['name']['common']) ? $item['name']['common'] : null;
        if ($cca2 !== null && $name !== null) {
            $countries[strtolower($cca2)] = $name;
        }
    }

    if (empty($countries)) {
        return null;
    }

    set_transient(CSI_COUNTRIES_TRANSIENT_KEY, $countries, CSI_COUNTRIES_CACHE_DURATION);
    return $countries;
}
