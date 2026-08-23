<?php
/**
 * Student Billing Premium: Print Invoices program
 *
 * Generate PDF invoices for selected students.
 *
 * @package ScholaPro
 * @subpackage modules/Student_Billing_Premium
 */

require_once 'modules/Student_Billing_Premium/includes/functions.inc.php';

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	DrawHeader( ProgramTitle() );

	// From & To date range for fees.
	$from_date = _sbp_validate_date( issetVal( $_REQUEST['from_date'], '' ) );

	$to_date = _sbp_validate_date( issetVal( $_REQUEST['to_date'], '' ) );

	$extra['search'] = '<tr><td>' . _( 'From' ) . '</td><td>' .
		DateInput( $from_date, 'from_date', '', false, false ) .
		'</td></tr><tr><td>' . _( 'To' ) . '</td><td>' .
		DateInput( $to_date, 'to_date', '', false, false ) .
		'</td></tr>';

	$extra['new'] = true;

	$extra['action'] = empty( $extra['action'] ) ? '&_ROSARIO_PDF=true' : $extra['action'] . '&_ROSARIO_PDF=true';

	Search( 'student_id', $extra );
}
else
{
	// Generate invoices PDF.
	$_REQUEST['print_statements'] = true;

	$students_RET = GetStuList( $extra );

	if ( ! empty( $students_RET ) )
	{
		$SESSION_student_id_save = UserStudentID();

		$handle = PDFStart();

		foreach ( (array) $students_RET as $student )
		{
			SetUserStudentID( $student['STUDENT_ID'] );

			echo _sbp_invoice_html( $student );

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
 * Build invoice HTML for a student.
 *
 * @param  array $student Student row from GetStuList().
 *
 * @return string Invoice HTML.
 */
function _sbp_invoice_html( $student )
{
	$from_date = _sbp_validate_date( issetVal( $_REQUEST['from_date'], '' ) );

	$to_date = _sbp_validate_date( issetVal( $_REQUEST['to_date'], '' ) );

	$date_where = '';

	if ( $from_date )
	{
		$date_where .= " AND f.ASSIGNED_DATE>='" . $from_date . "'";
	}

	if ( $to_date )
	{
		$date_where .= " AND f.ASSIGNED_DATE<='" . $to_date . "'";
	}

	$invoice_number = _sbp_next_number( 'INVOICE_NUMBER' );

	$html = _sbp_document_header( _( 'Invoice' ), sprintf( '%s-%05d', _sbp_config( 'INVOICE_PREFIX' ) ? _sbp_config( 'INVOICE_PREFIX' ) : 'INV', $invoice_number ) );

	// Student block.
	$html .= '<table style="width:100%;margin-bottom:10px;"><tr>
		<td><b>' . _( 'Student' ) . ':</b> ' . htmlspecialchars( $student['FULL_NAME'], ENT_QUOTES ) . '<br />
		<b>' . _( 'Student ID' ) . ':</b> ' . htmlspecialchars( $student['STUDENT_ID'], ENT_QUOTES ) . '</td>
		<td><b>' . _( 'Grade' ) . ':</b> ' . htmlspecialchars( issetVal( $student['GRADE_ID'] ), ENT_QUOTES ) . '<br />
		<b>' . _( 'Date' ) . ':</b> ' . ProperDate( DBDate() ) . '</td>
		</tr></table>';

	// Fees table.
	$fees_RET = DBGet( "SELECT f.TITLE,f.ASSIGNED_DATE,f.DUE_DATE,f.AMOUNT
		FROM billing_fees f
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND f.WAIVED_FEE_ID IS NULL" . $date_where . "
		ORDER BY f.ASSIGNED_DATE" );

	$html .= '<table style="width:100%;border-collapse:collapse;">
		<tr style="background:#eee;">
		<th style="border:1px solid #ccc;padding:4px;text-align:left;">' . _( 'Title' ) . '</th>
		<th style="border:1px solid #ccc;padding:4px;text-align:left;">' . _( 'Assigned' ) . '</th>
		<th style="border:1px solid #ccc;padding:4px;text-align:left;">' . _( 'Due' ) . '</th>
		<th style="border:1px solid #ccc;padding:4px;text-align:right;">' . _( 'Amount' ) . '</th>
		</tr>';

	$fees_total = 0;

	foreach ( (array) $fees_RET as $fee )
	{
		$fees_total += $fee['AMOUNT'];

		$html .= '<tr>
			<td style="border:1px solid #ccc;padding:4px;">' . htmlspecialchars( $fee['TITLE'], ENT_QUOTES ) . '</td>
			<td style="border:1px solid #ccc;padding:4px;">' . ProperDate( $fee['ASSIGNED_DATE'] ) . '</td>
			<td style="border:1px solid #ccc;padding:4px;">' . ProperDate( $fee['DUE_DATE'] ) . '</td>
			<td style="border:1px solid #ccc;padding:4px;text-align:right;">' . Currency( $fee['AMOUNT'] ) . '</td>
			</tr>';
	}

	if ( ! $fees_RET )
	{
		$html .= '<tr><td colspan="4" style="border:1px solid #ccc;padding:4px;">' . _( 'No fees found.' ) . '</td></tr>';
	}

	$html .= '</table>';

	// Totals.
	$payments_total = DBGetOne( "SELECT SUM(AMOUNT) AS TOTAL
		FROM billing_payments
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$balance = (float) $fees_total - (float) $payments_total;

	$html .= '<table class="align-right" style="margin-top:10px;margin-left:auto;">
		<tr><td>' . _( 'Total from Fees' ) . ': </td><td>' . Currency( $fees_total ) . '</td></tr>
		<tr><td>' . _( 'Total from Payments' ) . ': </td><td>' . Currency( $payments_total ) . '</td></tr>
		<tr><td><b>' . _( 'Balance' ) . ':</b> </td><td><b>' . Currency( $balance, 'CR' ) . '</b></td></tr>
		</table>';

	return $html;
}
