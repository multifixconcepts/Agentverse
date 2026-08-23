<?php
/**
 * Billing Elements: Daily Transactions program
 *
 * Lists transactions (Element Fees & Payments) for a specific timeframe,
 * optionally filtered by Element Category.
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Billing_Elements
 */

require_once 'modules/Billing_Elements/includes/functions.inc.php';

DrawHeader( ProgramTitle() );

// Timeframe.
$start_date = _be_validate_date( issetVal( $_REQUEST['start_date'], date( 'Y-m' ) . '-01' ) );

$end_date = _be_validate_date( issetVal( $_REQUEST['end_date'], DBDate() ) );

$category_id = (int) issetVal( $_REQUEST['category_id'], 0 );

$categories_RET = DBGet( "SELECT ID,TITLE
	FROM billing_elements_categories
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

$categories_options = [];

foreach ( (array) $categories_RET as $category )
{
	$categories_options[ $category['ID'] ] = $category['TITLE'];
}

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) . '" method="GET">';

echo '<table class="width-100p cellspacing-0">
	<tr>
		<td>' . _( 'From' ) . '</td><td>' .
		DateInput( $start_date, 'start_date', '', false, false ) .
		'</td>
		<td>' . _( 'To' ) . '</td><td>' .
		DateInput( $end_date, 'end_date', '', false, false ) .
		'</td>
		<td>' . _( 'Category' ) . '</td><td>' .
		SelectInput( $category_id, 'category_id', _( 'Category' ), $categories_options, _( 'All Categories' ), '', false ) .
		'</td>
		<td>' . Buttons( _( 'Go' ) ) . '</td>
	</tr>
	</table>';

echo '</form>';

// Fees linked to Elements (optionally filtered by category).
$category_where = $category_id ?
	"AND e.CATEGORY_ID='" . $category_id . "'" :
	'';

$fees_RET = DBGet( "SELECT f.STUDENT_ID,f.AMOUNT AS DEBIT,'' AS CREDIT,
		CONCAT(e.TITLE, ' ', COALESCE(f.COMMENTS,'')) AS EXPLANATION,
		f.ASSIGNED_DATE AS DATE,f.ID AS ID,f.CREATED_BY,f.CREATED_AT
	FROM student_billing_elements sbe
	LEFT JOIN billing_elements e ON e.ID=sbe.ELEMENT_ID
	LEFT JOIN billing_fees f ON f.ID=sbe.FEE_ID
	WHERE sbe.SCHOOL_ID='" . UserSchool() . "'
	AND sbe.SYEAR='" . UserSyear() . "'
	AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
	" . $category_where . "
	ORDER BY f.ASSIGNED_DATE" );

// Payments (all).
$payments_RET = DBGet( "SELECT p.STUDENT_ID,'' AS DEBIT,p.AMOUNT AS CREDIT,
		COALESCE(p.COMMENTS,'') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID,
		p.CREATED_BY,p.CREATED_AT
	FROM billing_payments p
	WHERE p.SCHOOL_ID='" . UserSchool() . "'
	AND p.SYEAR='" . UserSyear() . "'
	AND p.PAYMENT_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
	ORDER BY p.PAYMENT_DATE" );

// Merge & sort by date.
$RET = [];

$totals = [ 'DEBIT' => 0, 'CREDIT' => 0 ];

foreach ( (array) $fees_RET as $fee )
{
	$RET[] = $fee;

	$totals['DEBIT'] += (float) $fee['DEBIT'];
}

foreach ( (array) $payments_RET as $payment )
{
	$RET[] = $payment;

	$totals['CREDIT'] += (float) $payment['CREDIT'];
}

// Sort by DATE.
usort( $RET, function ( $a, $b )
{
	return strcmp( $a['DATE'], $b['DATE'] );
} );

$functions = [
	'DEBIT' => '_be_transaction_currency',
	'CREDIT' => '_be_transaction_currency',
	'DATE' => 'ProperDate',
	'CREATED_AT' => 'ProperDateTime',
];

$columns = [
	'STUDENT_ID' => _( 'Student ID' ),
	'DEBIT' => _( 'Fee' ),
	'CREDIT' => _( 'Payment' ),
	'DATE' => _( 'Date' ),
	'EXPLANATION' => _( 'Comment' ),
];

if ( isset( $_REQUEST['expanded_view'] )
	&& $_REQUEST['expanded_view'] === 'true' )
{
	$columns += [
		'CREATED_BY' => _( 'Created by' ),
		'CREATED_AT' => _( 'Created at' ),
	];
}

$link['add']['html'] = [
	'STUDENT_ID' => _( 'Total' ) . ': ' .
		'<b>' . Currency( $totals['CREDIT'] - $totals['DEBIT'] ) . '</b>',
	'DEBIT' => '<b>' . Currency( $totals['DEBIT'] ) . '</b>',
	'CREDIT' => '<b>' . Currency( $totals['CREDIT'] ) . '</b>',
];

ListOutput(
	$RET,
	$columns,
	'Transaction',
	'Transactions',
	$link,
	$functions,
	[ 'add' => true ]
);

/**
 * Currency column function with totals.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_transaction_currency( $value, $column )
{
	global $totals;

	if ( ! isset( $totals[ $column ] ) )
	{
		$totals[ $column ] = 0;
	}

	$totals[ $column ] += (float) $value;

	if ( ! empty( $value ) || $value == '0' )
	{
		return Currency( $value );
	}
}
