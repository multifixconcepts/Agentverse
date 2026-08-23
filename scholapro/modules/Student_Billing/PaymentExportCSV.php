<?php
/**
 * Payment Export CSV — export student payments to CSV format
 *
 * SESSION 1: Initial implementation — CSV generation core.
 * SESSION 2: Will add date range filtering and file download.
 *
 * @package ScholaPro
 * @subpackage Student_Billing
 */

require_once 'modules/Student_Billing/functions.inc.php';

/**
 * Generate CSV content from payment records.
 *
 * @param  array $payments_RET  Payment records from DBGet.
 * @return string               CSV content.
 */
function _generatePaymentCSV( $payments_RET )
{
	$output = fopen( 'php://temp', 'r+' );

	// Header row.
	fputcsv( $output, [
		_( 'Payment ID' ),
		_( 'Student' ),
		_( 'Amount' ),
		_( 'Date' ),
		_( 'Type' ),
		_( 'Reference' ),
		_( 'Comments' ),
	] );

	// Data rows.
	foreach ( $payments_RET as $payment )
	{
		fputcsv( $output, [
			$payment['ID'],
			$payment['STUDENT_NAME'],
			$payment['AMOUNT'],
			$payment['PAYMENT_DATE'],
			$payment['PAYMENT_TYPE'],
			$payment['REFERENCE'],
			$payment['COMMENTS'],
		] );
	}

	rewind( $output );
	$csv = stream_get_contents( $output );
	fclose( $output );

	return $csv;
}

// SESSION 2: Date range filtering, file download, student filter.

if ( ! empty( $_REQUEST['export_csv'] ) && UserStudentID() )
{
	// Build query with optional date range.
	$where = [
		"STUDENT_ID='" . UserStudentID() . "'",
		"SYEAR='" . UserSyear() . "'",
		"REFUNDED_PAYMENT_ID IS NULL",
	];

	if ( ! empty( $_REQUEST['date_from'] ) )
	{
		$where[] = "PAYMENT_DATE>='" . DBEscapeString( $_REQUEST['date_from'] ) . "'";
	}

	if ( ! empty( $_REQUEST['date_to'] ) )
	{
		$where[] = "PAYMENT_DATE<='" . DBEscapeString( $_REQUEST['date_to'] ) . "'";
	}

	$payments_RET = DBGet( "SELECT p.ID, CONCAT(s.LAST_NAME, ', ', s.FIRST_NAME) AS STUDENT_NAME,
		p.AMOUNT, p.PAYMENT_DATE, p.PAYMENT_TYPE, p.REFERENCE, p.COMMENTS
		FROM billing_payments p
		LEFT JOIN students s ON (p.STUDENT_ID = s.STUDENT_ID)
		WHERE " . implode( ' AND ', $where ) . "
		ORDER BY p.PAYMENT_DATE" );

	$csv = _generatePaymentCSV( $payments_RET );

	// Send as download.
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="payments_' . UserStudentID() . '_' . date( 'Y-m-d' ) . '.csv"' );
	header( 'Content-Length: ' . strlen( $csv ) );
	echo $csv;
	exit;
}

// Display date range form.
echo '<form method="post">';
echo '<div class="center">';
echo '<label>' . _( 'From' ) . ': <input type="date" name="date_from" value="' . issetVal( $_REQUEST['date_from'], '' ) . '" /></label> ';
echo '<label>' . _( 'To' ) . ': <input type="date" name="date_to" value="' . issetVal( $_REQUEST['date_to'], '' ) . '" /></label> ';
echo '<input type="hidden" name="export_csv" value="1" />';
echo '<input type="submit" value="' . _( 'Export CSV' ) . '" />';
echo '</div>';
echo '</form>';
