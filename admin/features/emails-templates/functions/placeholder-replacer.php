<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Placeholder Replacer
 * Replaces placeholders in email templates with user data
 */

/**
 * Replace placeholders in email content
 */
function csi_replace_placeholders($content, $user_id) {
    // Ensure content is a string
    $content = (string) ($content ?? '');
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return $content;
    }
    
    // Get user meta
    $fullname = get_user_meta($user_id, 'fullname', true);
    $title = get_user_meta($user_id, 'title', true);
    $specialty = get_user_meta($user_id, 'specialty', true);
    $phone = get_user_meta($user_id, 'phone', true);
    $dob = get_user_meta($user_id, 'dob', true);
    $home_address = get_user_meta($user_id, 'home_address', true);
    $work_address = get_user_meta($user_id, 'work_address', true);
    $institute = get_user_meta($user_id, 'institute', true);
    $country = get_user_meta($user_id, 'country', true);
    $membership = get_user_meta($user_id, 'membership', true);
    $registration_type = get_user_meta($user_id, 'registration_type', true);
    $payment_status = get_user_meta($user_id, 'payment_status', true) ?: 'pending';
    $payment_method = get_user_meta($user_id, 'payment_method', true);
    $paid_date = get_user_meta($user_id, 'paid_date', true);
    $membership_start_date = get_user_meta($user_id, 'membership_start_date', true);
    $membership_end_date = get_user_meta($user_id, 'membership_end_date', true);
    $membership_number = get_user_meta($user_id, 'membership_number', true) ?: $user_id;
    
    // Get file URLs
    $photo_id = get_user_meta($user_id, 'photo_id', true);
    $cv_id = get_user_meta($user_id, 'cv_id', true);
    $receipt_id = get_user_meta($user_id, 'receipt_id', true);
    $id_scan_ids = get_user_meta($user_id, 'id_scan_ids', true);
    $student_card_id = get_user_meta($user_id, 'student_card_id', true);
    
    $photo_url = $photo_id ? wp_get_attachment_url($photo_id) : '';
    $cv_url = $cv_id ? wp_get_attachment_url($cv_id) : '';
    $receipt_url = $receipt_id ? wp_get_attachment_url($receipt_id) : '';
    $student_card_url = $student_card_id ? wp_get_attachment_url($student_card_id) : '';
    
    $id_scans_urls = [];
    if (is_array($id_scan_ids)) {
        foreach ($id_scan_ids as $scan_id) {
            $url = wp_get_attachment_url($scan_id);
            if ($url) {
                $id_scans_urls[] = $url;
            }
        }
    }
    
    // Build replacements array - ensure all values are strings
    $replacements = [
        '{full_name}' => (string) ($fullname ?? ''),
        '{email}' => (string) ($user->user_email ?? ''),
        '{phone}' => (string) ($phone ?? ''),
        '{membership_number}' => (string) ($membership_number ?? ''),
        '{generated_id}' => (string) ($membership_number ?? ''), // Backward compatibility
        '{membership_type}' => (string) (csi_get_membership_type_label($membership) ?? ''),
        '{registration_type}' => ucfirst(str_replace('_', ' ', (string) ($registration_type ?? ''))),
        '{payment_status}' => ucfirst((string) ($payment_status ?? '')),
        '{paid_date}' => (string) ($paid_date ?? ''),
        '{expiry_date}' => (string) ($membership_end_date ?? ''),
        '{institute}' => (string) ($institute ?? ''),
        '{country}' => (string) (csi_get_country_name($country ?: '') ?? ''),
        '{personal_photo_url}' => (string) ($photo_url ?? ''),
        '{cv_url}' => (string) ($cv_url ?? ''),
        '{id_scans_urls}' => (string) (implode(', ', $id_scans_urls) ?? ''),
        '{student_card_url}' => (string) ($student_card_url ?? ''),
        '{payment_receipt_url}' => (string) ($receipt_url ?? '')
    ];
    
    // Handle dynamic meta placeholders {meta:key}
    preg_match_all('/\{meta:([^}]+)\}/', $content, $meta_matches);
    if (!empty($meta_matches[1])) {
        foreach ($meta_matches[1] as $meta_key) {
            $meta_value = get_user_meta($user_id, $meta_key, true);
            $replacements['{meta:' . $meta_key . '}'] = (string) ($meta_value ?? '');
        }
    }
    
    // Build files table if {files_table} placeholder exists
    if ($content && strpos($content, '{files_table}') !== false) {
        $files_table = csi_build_files_table($user_id);
        $replacements['{files_table}'] = (string) ($files_table ?? '');
    }
    
    // Replace all placeholders - ensure all values are strings
    foreach ($replacements as $placeholder => $value) {
        $value = (string) ($value ?? '');
        $content = str_replace($placeholder, $value, $content);
    }
    
    return $content;
}

/**
 * Build files table HTML
 */
function csi_build_files_table($user_id) {
    $files = csi_get_user_files($user_id);
    
    if (empty($files)) {
        return '<p>No files uploaded.</p>';
    }
    
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
    $html .= '<thead><tr><th>File Type</th><th>Link</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($files as $file) {
        if (is_array($file)) {
            foreach ($file as $f) {
                if (isset($f['name']) && isset($f['url'])) {
                    $html .= '<tr><td>' . esc_html($f['name']) . '</td><td><a href="' . esc_url($f['url']) . '">View</a></td></tr>';
                }
            }
        } elseif (isset($file['name']) && isset($file['url'])) {
            $html .= '<tr><td>' . esc_html($file['name']) . '</td><td><a href="' . esc_url($file['url']) . '">View</a></td></tr>';
        }
    }
    
    $html .= '</tbody></table>';
    
    return $html;
}
