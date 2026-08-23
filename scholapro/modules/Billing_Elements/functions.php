<?php
/**
 * Billing Elements module functions & hooks
 *
 * Auto-loaded by ScholaPro for each activated non-core module.
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Billing_Elements
 */

require_once 'modules/Billing_Elements/includes/functions.inc.php';

/**
 * Rollover hook: copy Categories & Elements (ROLLOVER='Y') to the next school year.
 */
add_action( 'School_Setup/Rollover.php|rollover_after', '_be_rollover' );
