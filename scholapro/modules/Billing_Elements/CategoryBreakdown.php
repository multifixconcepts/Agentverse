<?php
/**
 * Billing Elements: Category Breakdown report
 *
 * Displays Billing Elements breakdown per Category as Bar / Pie charts or a List.
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Billing_Elements
 */

require_once 'ProgramFunctions/Charts.fnc.php';
require_once 'modules/Billing_Elements/includes/functions.inc.php';

if ( User( 'PROFILE' ) !== 'admin' )
{
	DrawHeader( ProgramTitle() );

	echo ErrorMessage( [ _( 'You do not have permission to use this program.' ) ] );

	return;
}

DrawHeader( ProgramTitle() );

// Filters.
$category_id = (int) issetVal( $_REQUEST['category_id'], 0 );

$amount_mode = issetVal( $_REQUEST['amount_mode'], 'amount' );

$breakdown_grade = ! empty( $_REQUEST['breakdown_grade'] );

$start_date = _be_validate_date( issetVal( $_REQUEST['start_date'], date( 'Y-m' ) . '-01' ) );

$end_date = _be_validate_date( issetVal( $_REQUEST['end_date'], DBDate() ) );

$chart_type = issetVal( $_REQUEST['chart_type'], 'bar' );

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
		<td>' . _( 'Category' ) . '</td><td>' .
		SelectInput( $category_id, 'category_id', _( 'Category' ), $categories_options, _( 'All Categories' ), '', false ) .
		'</td>
		<td>' . _( 'Start Date' ) . '</td><td>' .
		DateInput( $start_date, 'start_date', '', false, false ) .
		'</td>
		<td>' . _( 'End Date' ) . '</td><td>' .
		DateInput( $end_date, 'end_date', '', false, false ) .
		'</td>
		<td>' . Buttons( _( 'Go' ) ) . '</td>
	</tr>
	<tr>
		<td>' . _( 'Chart' ) . '</td><td>' .
		RadioInput( $chart_type, 'chart_type', '', [ 'bar' => _( 'Bar' ), 'pie' => _( 'Pie' ), 'list' => _( 'List' ) ] ) .
		'</td>
		<td>' . _( 'Values' ) . '</td><td>' .
		RadioInput( $amount_mode, 'amount_mode', '', [ 'amount' => _( 'Amount' ), 'count' => _( 'Count' ) ] ) .
		'</td>
		<td colspan="2">' .
		CheckboxInput( $breakdown_grade, 'breakdown_grade', _( 'Breakdown by Grade Level' ), '', true ) .
		'</td>
	</tr>
	</table>';

echo '</form>';

if ( ! $category_id )
{
	echo ErrorMessage( [ _( 'Please select a Category.' ) ] );

	return;
}

// Date range.
$date_where = " AND f.ASSIGNED_DATE>='" . $start_date . "'
	AND f.ASSIGNED_DATE<='" . $end_date . "'";

// Elements in category.
$category_where = " AND e.CATEGORY_ID='" . $category_id . "'";

// Grade breakdown grouping.
$grade_select = '';

$grade_group = '';

if ( $breakdown_grade )
{
	$grade_select = "ssm.GRADE_ID AS GRADE_ID,";

	$grade_group = ',ssm.GRADE_ID';

	$grade_join = " LEFT JOIN student_enrollment ssm
		ON ssm.STUDENT_ID=f.STUDENT_ID
		AND ssm.SYEAR=f.SYEAR
		AND ssm.SCHOOL_ID=f.SCHOOL_ID
		AND ssm.START_DATE<=f.ASSIGNED_DATE
		AND (ssm.END_DATE IS NULL OR ssm.END_DATE>=f.ASSIGNED_DATE)";
}
else
{
	$grade_join = '';
}

$value_sql = $amount_mode === 'amount' ? 'COALESCE(SUM(f.AMOUNT),0)' : 'COUNT(sbe.ID)';

$breakdown_RET = DBGet( "SELECT " . $grade_select . "
	e.TITLE AS ELEMENT_TITLE," . $value_sql . " AS VALUE
	FROM student_billing_elements sbe
	LEFT JOIN billing_elements e ON e.ID=sbe.ELEMENT_ID
	LEFT JOIN billing_fees f ON f.ID=sbe.FEE_ID" . $grade_join . "
	WHERE sbe.SCHOOL_ID='" . UserSchool() . "'
	AND sbe.SYEAR='" . UserSyear() . "'" . $category_where . $date_where . "
	GROUP BY e.TITLE" . $grade_group . "
	ORDER BY e.TITLE" . ( $breakdown_grade ? ',ssm.GRADE_ID' : '' ) );

if ( empty( $breakdown_RET ) )
{
	echo ErrorMessage( [ _( 'No Elements found for the selected period.' ) ] );

	return;
}

// Chart data.
$labels = [];

$values = [];

$data = [];

foreach ( (array) $breakdown_RET as $row )
{
	$label = $row['ELEMENT_TITLE'];

	if ( $breakdown_grade && isset( $row['GRADE_ID'] ) )
	{
		// Append grade title.
		$grade_title = DBGetOne( "SELECT TITLE
			FROM school_gradelevels
			WHERE ID='" . (int) $row['GRADE_ID'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		$label .= ' (' . ( $grade_title ? $grade_title : $row['GRADE_ID'] ) . ')';
	}

	$labels[] = $label;

	$values[] = (float) $row['VALUE'];

	$row['DISPLAY_VALUE'] = Currency( $row['VALUE'] );

	$display_RET[] = $row;
}

$data[0] = $labels;

$data[1] = $values;

// Chart tabs.
$tabs = '<a href="' . URLEscape( PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'bar' ] ) ) . '"' .
	( $chart_type === 'bar' ? ' class="button-primary"' : ' class="button"' ) . '>' . _( 'Bar' ) . '</a> ' .
	'<a href="' . URLEscape( PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'pie' ] ) ) . '"' .
	( $chart_type === 'pie' ? ' class="button-primary"' : ' class="button"' ) . '>' . _( 'Pie' ) . '</a> ' .
	'<a href="' . URLEscape( PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'list' ] ) ) . '"' .
	( $chart_type === 'list' ? ' class="button-primary"' : ' class="button"' ) . '>' . _( 'List' ) . '</a>';

DrawHeader( $tabs );

$chart_title = _( 'Category Breakdown' ) . ': ' .
	( $amount_mode === 'amount' ? _( 'Amount' ) : _( 'Count' ) );

if ( $chart_type === 'list' )
{
	$total_value = array_sum( $values );

	$columns = [
		'ELEMENT_TITLE' => _( 'Element' ),
		'VALUE' => $amount_mode === 'amount' ? _( 'Amount' ) : _( 'Count' ),
	];

	if ( $breakdown_grade )
	{
		$columns = [ 'GRADE_ID' => _( 'Grade Level' ) ] + $columns;
	}

	$link['add']['html'] = [
		'ELEMENT_TITLE' => _( 'Total' ) . ': <b>' .
			( $amount_mode === 'amount' ? Currency( $total_value ) : (int) $total_value ) . '</b>',
	];

	ListOutput(
		$display_RET,
		$columns,
		'Element',
		'Elements',
		$link,
		[ 'VALUE' => '_be_breakdown_value' ],
		[ 'add' => true ]
	);
}
else
{
	$type = $chart_type === 'pie' ? 'pie' : 'bar';

	echo ChartjsChart( $type, $data, $chart_title );

	// Download link: print to PDF.
	echo '<div class="center" style="margin-top:10px;">
		<a href="' . URLEscape( PreparePHP_SELF( $_REQUEST, [], [ '_ROSARIO_PDF' => 'true' ] ) ) .
		'" class="button">' . _( 'Download' ) . '</a></div>';
}

/**
 * Format breakdown value.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_breakdown_value( $value, $column )
{
	global $amount_mode;

	return $amount_mode === 'amount' ? Currency( $value ) : (string) (int) $value;
}
