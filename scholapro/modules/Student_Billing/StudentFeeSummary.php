<?php
/**
 * Student Fee Summary — summary of fees per student
 *
 * @package ScholaPro
 * @subpackage Student_Billing
 */

require_once 'modules/Student_Billing/functions.inc.php';

/**
 * Calculate total fees for a student.
 *
 * @param  int $student_id  Student ID.
 * @param  int $syear       School year.
 * @return float            Total fees amount.
 */
function _calculateStudentFees( $student_id, $syear )
{
	$total = DBGetOne( "SELECT COALESCE(SUM(AMOUNT), 0) AS TOTAL
		FROM billing_fees
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . (int) $syear . "'" );

	return (float) $total['TOTAL'];
}

// Display summary for current student.
if ( UserStudentID() )
{
	$fees_total = _calculateStudentFees( UserStudentID(), UserSyear() );
	$payments_total = DBGetOne( "SELECT COALESCE(SUM(AMOUNT), 0) AS TOTAL
		FROM billing_payments
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND REFUNDED_PAYMENT_ID IS NULL" );

	$balance = $fees_total - (float) $payments_total['TOTAL'];

	echo '<h3>' . _( 'Fee Summary' ) . '</h3>';
	echo '<table class="billing-summary">';
	echo '<tr><td>' . _( 'Total Fees' ) . '</td><td class="amount">' . Currency( $fees_total ) . '</td></tr>';
	echo '<tr><td>' . _( 'Total Payments' ) . '</td><td class="amount">' . Currency( (float) $payments_total['TOTAL'] ) . '</td></tr>';
	echo '<tr class="total"><td>' . _( 'Balance' ) . '</td><td class="amount">' . Currency( $balance ) . '</td></tr>';
	echo '</table>';
}
