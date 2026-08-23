<?php
/**
 * Student Billing Premium module functions & hooks
 *
 * Auto-loaded by ScholaPro for each activated non-core module.
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Student_Billing_Premium
 */

require_once 'modules/Student_Billing_Premium/includes/functions.inc.php';
require_once 'modules/Student_Billing_Premium/includes/Monnify.fnc.php';

/**
 * Student Payments header hook: display the "Pay" button.
 *
 * Hooked on `student_payments_header` action (demo shows Pay button on
 * core Student Billing > Payments screen, NOT as a separate program).
 *
 * @return void
 */
function StudentBillingPremiumPayButton()
{
	// Only when a student is selected.
	if ( ! UserStudentID() )
	{
		return;
	}

	// Skip when printing statements or generating PDF.
	if ( ! empty( $_REQUEST['print_statements'] )
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return;
	}

	// Skip if gateway not enabled & configured.
	if ( ! _sbp_is_configured() )
	{
		return;
	}

	$balance = _sbp_balance( UserStudentID() );

	if ( $balance <= 0 )
	{
		return;
	}

	// Pay button opens the payment modal/gateway directly (no separate Pay.php program).
	$pay_url = URLEscape(
		'Modules.php?modname=Student_Billing/StudentPayments.php&modfunc=pay&student_id=' . UserStudentID()
	);

	DrawHeader(
		'',
		'<a class="button-primary" href="' . $pay_url . '" style="color:#fff;text-decoration:none;padding:6px 14px;border-radius:4px;display:inline-block;">' .
		_( 'Pay Balance with Monnify' ) .
		' (' . Currency( $balance ) . ')</a>'
	);
}

/**
 * Hook: Pay button on core Student Billing > Payments.
 */
add_action( 'student_payments_header', 'StudentBillingPremiumPayButton', 10 );