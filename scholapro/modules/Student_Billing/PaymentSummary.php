<?php
/**
 * Payment Summary
 *
 * School-wide summary of Student Billing activity for the current school year.
 * Groups payments by PAYMENT_TYPE and displays the totals:
 * collected, refunded, pending & net balance.
 *
 * @package ScholaPro
 * @subpackage Student_Billing
 */

require_once 'modules/Student_Billing/functions.inc.php';

DrawHeader( ProgramTitle() );

// Load summary table styles (striped rows, right-aligned amounts, bold totals).
$summary_css = 'modules/Student_Billing/css/billing-summary.css';

if ( file_exists( $summary_css ) )
{
	echo '<link rel="stylesheet" href="' . URLEscape( $summary_css ) . '" />';
}

// Payments grouped by payment type, for current school & school year.
// Refunds are stored as negative amounts and are included in the type totals.
$payments_by_type_RET = DBGet( "SELECT COALESCE(PAYMENT_TYPE,
		'" . DBEscapeString( _( 'Unspecified' ) ) . "') AS PAYMENT_TYPE,
		COUNT(*) AS PAYMENTS_NUMBER,
		SUM(AMOUNT) AS TOTAL_AMOUNT
	FROM billing_payments
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	GROUP BY PAYMENT_TYPE
	ORDER BY TOTAL_AMOUNT DESC" );

$type_summary_RET = [];

foreach ( (array) $payments_by_type_RET as $payment_type )
{
	$type_summary_RET[] = [
		'PAYMENT_TYPE' => $payment_type['PAYMENT_TYPE'] !== '' ?
			$payment_type['PAYMENT_TYPE'] :
			_( 'Unspecified' ),
		'PAYMENTS_NUMBER' => (int) $payment_type['PAYMENTS_NUMBER'],
		'TOTAL_AMOUNT' => Currency( $payment_type['TOTAL_AMOUNT'] ),
	];
}

$columns = [
	'PAYMENT_TYPE' => _( 'Payment Type' ),
	'PAYMENTS_NUMBER' => _( 'Number of Payments' ),
	'TOTAL_AMOUNT' => _( 'Total Amount' ),
];

// Wrapper div enables summary-specific styles (striping, right-aligned amounts).
echo '<div class="billing-summary">';

ListOutput(
	$type_summary_RET,
	$columns,
	'Payment type',
	'Payment types',
	[],
	[],
	[ 'valign-middle' => true ]
);

echo '</div>';

// Totals:
// - Collected: gross payments received (positive amounts only).
// - Refunded: refunds issued (negative amounts).
// - Pending: fees billed but not yet covered by payments.
// - Net balance: fees billed minus net payments received (credit if negative).
$totals_RET = DBGetOne( "SELECT COALESCE(SUM(CASE WHEN AMOUNT >= 0 THEN AMOUNT ELSE 0 END),0) AS TOTAL_COLLECTED,
		COALESCE(SUM(CASE WHEN AMOUNT < 0 THEN AMOUNT ELSE 0 END),0) AS TOTAL_REFUNDED
	FROM billing_payments
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'" );

$total_collected = (float) $totals_RET['TOTAL_COLLECTED'];

$total_refunded = (float) $totals_RET['TOTAL_REFUNDED'];

// Net payments received after refunds.
$total_net_received = $total_collected + $total_refunded;

$total_billed = (float) DBGetOne( "SELECT COALESCE(SUM(f.AMOUNT),0)
	FROM billing_fees f
	WHERE f.SYEAR='" . UserSyear() . "'
	AND f.SCHOOL_ID='" . UserSchool() . "'" );

// Fees billed but not yet paid (never negative).
$total_pending = max( 0, $total_billed - $total_net_received );

// Outstanding balance owed by students ('CR' displays credit when negative).
$net_balance = $total_billed - $total_net_received;

echo '<table class="billing-summary-totals">
	<tr><td>' . _( 'Total Collected' ) . ': </td>
	<td class="amount">' . Currency( $total_collected ) . '</td></tr>

	<tr><td>' . _( 'Total Refunded' ) . ': </td>
	<td class="amount">' . Currency( $total_refunded ) . '</td></tr>

	<tr><td>' . _( 'Total Billed (Fees)' ) . ': </td>
	<td class="amount">' . Currency( $total_billed ) . '</td></tr>

	<tr><td>' . _( 'Total Pending' ) . ': </td>
	<td class="amount">' . Currency( $total_pending ) . '</td></tr>

	<tr class="total-row"><td>' . _( 'Net Balance' ) . ': </td>
	<td class="amount total-amount"><b>' . Currency( $net_balance, 'CR' ) . '</b></td></tr>
</table>';
