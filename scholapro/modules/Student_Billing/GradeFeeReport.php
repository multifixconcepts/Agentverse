<?php
/**
 * Grade Fee Report — report of fees grouped by grade level
 *
 * @package ScholaPro
 * @subpackage Student_Billing
 */

require_once 'modules/Student_Billing/functions.inc.php';

/**
 * Get fees aggregated by grade level.
 *
 * Queries all fees for the current school year and school,
 * joined to student enrollment and grade levels, aggregated
 * per grade level.
 *
 * @param  int   $syear     School year.
 * @param  int   $school_id School ID.
 * @return array            Fee totals per grade level from DBGet.
 */
function GradeFeeReportGet( $syear, $school_id )
{
	$fees_RET = DBGet( "SELECT g.ID AS GRADE_ID,
		COALESCE(g.TITLE, 'N/A') AS GRADE_TITLE,
		COUNT(f.ID) AS FEE_COUNT,
		SUM(f.AMOUNT) AS TOTAL_AMOUNT
		FROM billing_fees f
		JOIN student_enrollment se ON (f.STUDENT_ID = se.STUDENT_ID
			AND se.SYEAR = f.SYEAR)
		LEFT JOIN school_gradelevels g ON (se.GRADE_ID = g.ID
			AND g.SCHOOL_ID = '" . (int) $school_id . "')
		WHERE f.SYEAR = '" . (int) $syear . "'
		AND se.SCHOOL_ID = '" . (int) $school_id . "'
		GROUP BY g.ID, g.TITLE
		ORDER BY g.SORT_ORDER, g.TITLE" );

	return $fees_RET;
}

/**
 * Generate CSV content from the grade fee report.
 *
 * @param  array  $fees_RET  Fee totals per grade level from DBGet.
 * @return string            CSV content.
 */
function GradeFeeReportCSV( $fees_RET )
{
	$output = fopen( 'php://temp', 'r+' );

	// Header row.
	fputcsv( $output, [
		_( 'Grade Level' ),
		_( 'Number of Fees' ),
		_( 'Total Amount' ),
	] );

	// Data rows.
	foreach ( $fees_RET as $fee )
	{
		fputcsv( $output, [
			$fee['GRADE_TITLE'],
			$fee['FEE_COUNT'],
			$fee['TOTAL_AMOUNT'],
		] );
	}

	rewind( $output );
	$csv = stream_get_contents( $output );
	fclose( $output );

	return $csv;
}

// Display the report.
$fees_RET = GradeFeeReportGet( UserSyear(), UserSchool() );

// CSV export: send as file download.
if ( ! empty( $_REQUEST['export_csv'] ) )
{
	$csv = GradeFeeReportCSV( $fees_RET );

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="grade_fee_report_' . UserSyear() . '_' . date( 'Y-m-d' ) . '.csv"' );
	header( 'Content-Length: ' . strlen( $csv ) );
	header( 'Pragma: no-cache' );

	echo $csv;
	exit;
}

// Print-friendly layout styles.
echo '<style>
	.grade-fee-report {
		width: 100%;
		border-collapse: collapse;
	}

	.grade-fee-report th,
	.grade-fee-report td {
		border: 1px solid #ccc;
		padding: 6px 10px;
		text-align: left;
	}

	.grade-fee-report-header {
		background-color: #f0f0f0;
	}

	.grade-fee-report-amount,
	.grade-fee-report-count {
		text-align: right;
	}

	@media print {
		body {
			font-size: 11pt;
			margin: 0;
		}

		.grade-fee-report {
			page-break-inside: avoid;
		}

		.grade-fee-report th,
		.grade-fee-report td {
			border: 1px solid #000;
			color: #000;
			background: none !important;
		}

		.grade-fee-report-header {
			font-weight: bold;
		}

		#menu,
		#footer,
		.no-print {
			display: none !important;
		}
	}
</style>';

echo '<h3>' . _( 'Grade Fee Report' ) . '</h3>';

if ( empty( $fees_RET ) )
{
	echo '<p>' . _( 'No fees found for this school year.' ) . '</p>';
}
else
{
	echo '<table class="grade-fee-report">';
	echo '<tr class="grade-fee-report-header">
		<th>' . _( 'Grade Level' ) . '</th>
		<th>' . _( 'Number of Fees' ) . '</th>
		<th>' . _( 'Total Amount' ) . '</th>
	</tr>';

	foreach ( $fees_RET as $fee )
	{
		echo '<tr>
			<td>' . $fee['GRADE_TITLE'] . '</td>
			<td class="grade-fee-report-count">' . $fee['FEE_COUNT'] . '</td>
			<td class="grade-fee-report-amount">' . Currency( (float) $fee['TOTAL_AMOUNT'] ) . '</td>
		</tr>';
	}

	echo '</table>';

	// CSV export button (hidden when printing).
	echo '<form method="post" class="no-print">
		<input type="hidden" name="export_csv" value="1" />
		<input type="submit" value="' . _( 'Export CSV' ) . '" />
	</form>';
}
