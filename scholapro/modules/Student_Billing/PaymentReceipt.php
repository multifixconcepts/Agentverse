<?php
/**
 * Payment Receipt — printable receipt for student fee payments
 *
 * @package ScholaPro
 * @subpackage Student_Billing
 */

require_once 'modules/Student_Billing/functions.inc.php';

// Receipt requested: display printable receipt.
if ( ! empty( $_REQUEST['receipt_id'] ) && UserStudentID() )
{
	$receipt_id = (int) $_REQUEST['receipt_id'];

	// Fetch payment record.
	$payment = DBGetOne( "SELECT ID, SYEAR, SCHOOL_ID, STUDENT_ID, AMOUNT, PAYMENT_DATE, PAYMENT_TYPE, REFERENCE, COMMENTS
		FROM billing_payments
		WHERE ID='" . $receipt_id . "'
		AND STUDENT_ID='" . UserStudentID() . "'" );

	if ( ! $payment )
	{
		echo '<p>' . _( 'Payment not found.' ) . '</p>';
		exit;
	}

	// Fetch student info.
	$student = DBGetOne( "SELECT FIRST_NAME, LAST_NAME, STUDENT_ID
		FROM students
		WHERE STUDENT_ID='" . UserStudentID() . "'" );

	// Fetch school info.
	$school = DBGetOne( "SELECT TITLE, ADDRESS, CITY, STATE, ZIPCODE, PHONE
		FROM schools
		WHERE ID='" . UserSchool() . "'" );

	// Format the receipt.
	echo '<div id="payment-receipt">';
	echo '<style>
		#payment-receipt { font-family: monospace; max-width: 600px; margin: 0 auto; }
		#payment-receipt .receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
		#payment-receipt .receipt-row { display: flex; justify-content: space-between; padding: 4px 0; }
		#payment-receipt .receipt-label { font-weight: bold; }
		#payment-receipt .receipt-footer { border-top: 2px solid #000; margin-top: 15px; padding-top: 10px; text-align: center; font-size: 0.9em; }
		@media print { #payment-receipt { margin: 0; } }
	</style>';

	echo '<div class="receipt-header">';
	echo '<h2>' . _( 'Payment Receipt' ) . '</h2>';
	if ( ! empty( $school['TITLE'] ) )
	{
		echo '<p>' . htmlspecialchars( $school['TITLE'] ) . '</p>';
	}
	if ( ! empty( $school['ADDRESS'] ) )
	{
		echo '<p>' . htmlspecialchars( $school['ADDRESS'] ) . '</p>';
	}
	if ( ! empty( $school['CITY'] ) )
	{
		echo '<p>' . htmlspecialchars( $school['CITY'] ) . ', ' . htmlspecialchars( $school['STATE'] ) . ' ' . htmlspecialchars( $school['ZIPCODE'] ) . '</p>';
	}
	echo '</div>';

	echo '<div class="receipt-body">';

	echo '<div class="receipt-row">';
	echo '<span class="receipt-label">' . _( 'Receipt #' ) . '</span>';
	echo '<span>' . $payment['ID'] . '</span>';
	echo '</div>';

	echo '<div class="receipt-row">';
	echo '<span class="receipt-label">' . _( 'Date' ) . '</span>';
	echo '<span>' . date( 'm/d/Y', strtotime( $payment['PAYMENT_DATE'] ) ) . '</span>';
	echo '</div>';

	echo '<div class="receipt-row">';
	echo '<span class="receipt-label">' . _( 'Student' ) . '</span>';
	echo '<span>' . htmlspecialchars( $student['LAST_NAME'] . ', ' . $student['FIRST_NAME'] ) . '</span>';
	echo '</div>';

	echo '<div class="receipt-row">';
	echo '<span class="receipt-label">' . _( 'Amount' ) . '</span>';
	echo '<span>' . number_format( (float) $payment['AMOUNT'], 2 ) . '</span>';
	echo '</div>';

	echo '<div class="receipt-row">';
	echo '<span class="receipt-label">' . _( 'Payment Type' ) . '</span>';
	echo '<span>' . htmlspecialchars( $payment['PAYMENT_TYPE'] ) . '</span>';
	echo '</div>';

	if ( ! empty( $payment['REFERENCE'] ) )
	{
		echo '<div class="receipt-row">';
		echo '<span class="receipt-label">' . _( 'Reference' ) . '</span>';
		echo '<span>' . htmlspecialchars( $payment['REFERENCE'] ) . '</span>';
		echo '</div>';
	}

	if ( ! empty( $payment['COMMENTS'] ) )
	{
		echo '<div class="receipt-row">';
		echo '<span class="receipt-label">' . _( 'Comments' ) . '</span>';
		echo '<span>' . htmlspecialchars( $payment['COMMENTS'] ) . '</span>';
		echo '</div>';
	}

	echo '</div>'; // receipt-body.

	echo '<div class="receipt-footer">';
	echo '<p>' . _( 'This is an official receipt. Please retain for your records.' ) . '</p>';
	echo '<p>' . _( 'Printed' ) . ': ' . date( 'm/d/Y H:i' ) . '</p>';
	echo '</div>';

	echo '</div>'; // payment-receipt.

	// Print button.
	echo '<br>';
	echo '<center>';
	echo '<input type="button" value="' . _( 'Print Receipt' ) . '" onclick="window.print();" />';
	echo ' &nbsp; ';
	echo '<input type="button" value="' . _( 'Close' ) . '" onclick="window.close();" />';
	echo '</center>';

	exit;
}

// No receipt requested — redirect to payments page.
echo '<script>window.location="Modules.php?modname=Student_Billing/StudentPayments.php";</script>';
exit;
