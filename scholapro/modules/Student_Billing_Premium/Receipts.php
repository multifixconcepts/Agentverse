<?php
/**
 * Student Billing Premium: Print Receipts program
 *
 * Generate PDF payment receipts for selected students.
 *
 * @package ScholaPro
 * @subpackage modules/Student_Billing_Premium
 */

require_once 'modules/Student_Billing_Premium/includes/functions.inc.php';

$extra = [];

// Direct print for a single student (from the "Print Receipt" link on the
// Payments page): skip the search screen.
if ( $_REQUEST['print_receipt'] === 'Y' )
{
	$_REQUEST['search_modfunc'] = 'list';

	$_REQUEST['print_statements'] = true;

	$extra['WHERE'] = " AND s.STUDENT_ID='" . (int) issetVal( $_REQUEST['student_id'], 0 ) . "'";
}

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	DrawHeader( ProgramTitle() );

	// Payment date range.
	$from_date = _sbp_validate_date( issetVal( $_REQUEST['from_date'], '' ) );

	$to_date = _sbp_validate_date( issetVal( $_REQUEST['to_date'], '' ) );

	$extra['search'] = '<tr><td>' . _( 'From' ) . '</td><td>' .
		DateInput( $from_date, 'from_date', '', false, false ) .
		'</td></tr><tr><td>' . _( 'To' ) . '</td><td>' .
		DateInput( $to_date, 'to_date', '', false, false ) .
		'</td></tr>';

	$extra['search'] .= '<tr><td>' . _( 'Two copies' ) . '</td><td>' .
		CheckboxInput( issetVal( $_REQUEST['two_copies'] ), 'two_copies', '', '', true ) .
		'</td></tr>';

	$extra['search'] .= '<tr><td>' . _( 'Hide Lunch Payment column' ) . '</td><td>' .
		CheckboxInput( issetVal( $_REQUEST['hide_lunch'] ), 'hide_lunch', '', '', true ) .
		'</td></tr>';

	$extra['search'] .= '<tr><td>' . _( 'Include Payment Number' ) . '</td><td>' .
		CheckboxInput( issetVal( $_REQUEST['show_payment_number'] ), 'show_payment_number', '', '', true ) .
		'</td></tr>';

	$extra['search'] .= '<tr><td>' . _( 'Legal notice' ) . '</td><td>' .
		CheckboxInput( issetVal( $_REQUEST['legal_notice'] ), 'legal_notice', '', '', true ) .
		'</td></tr>';

	$extra['new'] = true;

	$extra['action'] = empty( $extra['action'] ) ? '&_ROSARIO_PDF=true' : $extra['action'] . '&_ROSARIO_PDF=true';

	Search( 'student_id', $extra );
}
else
{
	// Generate receipts PDF.
	$_REQUEST['print_statements'] = true;

	$students_RET = GetStuList( $extra );

	if ( ! empty( $students_RET ) )
	{
		$SESSION_student_id_save = UserStudentID();

		$handle = PDFStart();

		foreach ( (array) $students_RET as $student )
		{
			SetUserStudentID( $student['STUDENT_ID'] );

			echo _sbp_receipts_html( $student );

			echo '<div style="page-break-after: always;"></div>';
		}

		$_SESSION['student_id'] = $SESSION_student_id_save;

		PDFStop( $handle );
	}
	else
	{
		DrawHeader( ProgramTitle() );

		echo ErrorMessage( [ _( 'No students found.' ) ] );
	}
}

/**
 * Build receipts HTML for a student (one per payment in range).
 *
 * @param  array $student Student row from GetStuList().
 *
 * @return string Receipts HTML.
 */
function _sbp_receipts_html( $student )
{
	$from_date = _sbp_validate_date( issetVal( $_REQUEST['from_date'], '' ) );

	$to_date = _sbp_validate_date( issetVal( $_REQUEST['to_date'], '' ) );

	$two_copies = ! empty( $_REQUEST['two_copies'] );

	$hide_lunch = ! empty( $_REQUEST['hide_lunch'] );

	$show_payment_number = ! empty( $_REQUEST['show_payment_number'] );

	$show_legal_notice = ! empty( $_REQUEST['legal_notice'] );

	$date_where = '';

	if ( $from_date )
	{
		$date_where .= " AND p.PAYMENT_DATE>='" . $from_date . "'";
	}

	if ( $to_date )
	{
		$date_where .= " AND p.PAYMENT_DATE<='" . $to_date . "'";
	}

	$payments_RET = DBGet( "SELECT p.ID,p.AMOUNT,p.PAYMENT_DATE,p.COMMENTS,p.LUNCH_PAYMENT
		FROM billing_payments p
		WHERE p.STUDENT_ID='" . UserStudentID() . "'
		AND p.SYEAR='" . UserSyear() . "'
		AND p.REFUNDED_PAYMENT_ID IS NULL" . $date_where . "
		ORDER BY p.PAYMENT_DATE,p.ID" );

	$html = '';

	foreach ( (array) $payments_RET as $payment )
	{
		$copies = $two_copies ? 2 : 1;

		for ( $copy = 1; $copy <= $copies; $copy++ )
		{
			$receipt_number = _sbp_next_number( 'RECEIPT_NUMBER' );

			$html .= _sbp_document_header( _( 'Payment Receipt' ), sprintf( '%s-%05d', _sbp_config( 'RECEIPT_PREFIX' ) ? _sbp_config( 'RECEIPT_PREFIX' ) : 'RCP', $receipt_number ) );

			$html .= '<table style="width:100%;margin-bottom:10px;"><tr>
				<td><b>' . _( 'Received from' ) . ':</b> ' . htmlspecialchars( $student['FULL_NAME'], ENT_QUOTES ) . '<br />
				<b>' . _( 'Student ID' ) . ':</b> ' . htmlspecialchars( $student['STUDENT_ID'], ENT_QUOTES ) . '</td>
				<td><b>' . _( 'Date' ) . ':</b> ' . ProperDate( $payment['PAYMENT_DATE'] ) . '</td>
				</tr></table>';

			$html .= '<table style="width:100%;border-collapse:collapse;">
				<tr style="background:#eee;">
				<th style="border:1px solid #ccc;padding:4px;text-align:right;">' . _( 'Amount' ) . '</th>
				<th style="border:1px solid #ccc;padding:4px;text-align:left;">' . _( 'Comment' ) . '</th>';

			if ( ! $hide_lunch )
			{
				$html .= '<th style="border:1px solid #ccc;padding:4px;text-align:left;">' . _( 'Lunch Payment' ) . '</th>';
			}

			if ( $show_payment_number )
			{
				$html .= '<th style="border:1px solid #ccc;padding:4px;text-align:left;">' . _( 'Payment No.' ) . '</th>';
			}

			$html .= '</tr><tr>
				<td style="border:1px solid #ccc;padding:4px;text-align:right;"><b>' . Currency( $payment['AMOUNT'] ) . '</b></td>
				<td style="border:1px solid #ccc;padding:4px;">' . htmlspecialchars( $payment['COMMENTS'], ENT_QUOTES ) . '</td>';

			if ( ! $hide_lunch )
			{
				$html .= '<td style="border:1px solid #ccc;padding:4px;">' . ( $payment['LUNCH_PAYMENT'] === 'Y' ? _( 'Yes' ) : '' ) . '</td>';
			}

			if ( $show_payment_number )
			{
				$html .= '<td style="border:1px solid #ccc;padding:4px;">' . $payment['ID'] . '</td>';
			}

			$html .= '</tr></table>';

			if ( $show_legal_notice )
			{
				$html .= _sbp_legal_notice();
			}

			$html .= '<p style="text-align:center;margin-top:25px;">__________________________<br />' .
				_( 'School Authorized Signature' ) . '</p>';

			if ( $copy < $copies )
			{
				$html .= '<div style="page-break-after: always;"></div>';
			}
		}
	}

	if ( ! $payments_RET )
	{
		$html .= '<p>' . _( 'No payments found for the selected period.' ) . '</p>';
	}

	return $html;
}
