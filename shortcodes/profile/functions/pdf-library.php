<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PDF Certificate Generation
 * Uses FPDF and FPDI libraries
 */

require_once(CSI_PLUGIN_DIR . 'includes/fpdf/fpdf.php');
require_once(CSI_PLUGIN_DIR . 'includes/fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

/**
 * Generate certificate PDF
 * 
 * @param string $fullname User's full name
 */
function csi_generate_certificate($fullname) {
    $pdf = new Fpdi();
    
    // Import the template page
    $template_path = CSI_PLUGIN_DIR . 'assets/pdf/Doc1.pdf';
    if (!file_exists($template_path)) {
        wp_die(__('Certificate template not found.', 'custom-signup-plugin'));
    }
    
    $pdf->setSourceFile($template_path);
    $tplId = $pdf->importPage(1);
    $templateSize = $pdf->getTemplateSize($tplId);
    
    // Determine orientation
    $width = $templateSize['width'];
    $height = $templateSize['height'];
    $orientation = ($width > $height) ? 'L' : 'P';
    
    // Add a new page with the same dimensions and orientation as the template
    $pdf->AddPage($orientation, [$width, $height]);
    
    // Use the template on the new page without scaling
    $pdf->useTemplate($tplId, null, null, null, null, false);
    
    // Set font and size
    $fontSize = 26;
    $pdf->SetFont('Arial', 'B', $fontSize);
    
    // Calculate leading (line height)
    $leading = $fontSize * 1.5;
    
    // Calculate text width
    $textWidth = $pdf->GetStringWidth($fullname);
    
    // Calculate X coordinate for horizontal centering
    $x = ($width - $textWidth) / 2;
    
    // Assume Y positions of the lines and calculate the middle Y position
    $Y1 = 75; // Y position of "This certificate recognizes"
    $Y2 = 100; // Y position of "As a member in the Egyptian Society of Extracellular Vesicles"
    $y = ($Y1 + $Y2) / 2;
    
    // Output the user's name
    $pdf->SetXY($x, $y);
    $pdf->Cell($textWidth, $leading, $fullname, 0, 1, 'C');
    
    // Output the PDF
    $pdf->Output('certificate.pdf', 'D');
    exit;
}
