<?php
/**
 * Student Billing Premium: Monthly Fees program
 *
 * Define recurring monthly fee templates and assign them to students
 * (auto-create billing_fees rows for the selected month).
 *
 * Handbook behavior:
 * - The __MONTH__ keyword in a template TITLE is replaced by the month name
 *   at assignment time (e.g. "__MONTH__ fee" -> "September fee").
 * - Active templates are auto-assigned once a day: a template whose Due Day
 *   is today is assigned for the current month. A template created on its
 *   Due Day is assigned next month (the save handler marks today as the last
 *   auto-run day).
 * - The "Assign" link opens a Find a Student screen with checkboxes to add
 *   the fee to selected students.
 *
 * @package ScholaPro
 * @subpackage modules/Student_Billing_Premium
 */

require_once 'modules/Student_Billing_Premium/includes/functions.inc.php';

if ( User( 'PROFILE' ) !== 'admin' )
{
	DrawHeader( ProgramTitle() );

	echo ErrorMessage( [ _( 'You do not have permission to use this program.' ) ] );

	return;
}

DrawHeader( ProgramTitle() );

$error = [];

$note = [];

$assign_template = false;

// Delete a template.
if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit()
	&& isset( $_REQUEST['id'] ) )
{
	if ( DeletePrompt( _( 'Monthly Fee' ) ) )
	{
		DBQuery( "DELETE FROM billing_monthly_fees
			WHERE ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// Save a template (add or update).
if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	if ( is_array( $_REQUEST['values'] ) )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			if ( empty( $columns['TITLE'] )
				|| ! is_numeric( $columns['AMOUNT'] ) )
			{
				$error[] = _( 'Title and amount are required.' );

				continue;
			}

			$columns['AMOUNT'] = (float) $columns['AMOUNT'];

			if ( $id !== 'new' )
			{
				DBUpdate(
					'billing_monthly_fees',
					[
						'TITLE' => $columns['TITLE'],
						'AMOUNT' => $columns['AMOUNT'],
						'DUE_DAY' => (int) issetVal( $columns['DUE_DAY'], 5 ),
						'GRADE_ID' => (int) issetVal( $columns['GRADE_ID'], 0 ),
						'ACTIVE' => issetVal( $columns['ACTIVE'], 'Y' ),
					],
					[ 'ID' => (int) $id, 'SCHOOL_ID' => UserSchool(), 'SYEAR' => UserSyear() ]
				);
			}
			else
			{
				DBInsert(
					'billing_monthly_fees',
					[
						'SCHOOL_ID' => UserSchool(),
						'SYEAR' => UserSyear(),
						'TITLE' => $columns['TITLE'],
						'AMOUNT' => $columns['AMOUNT'],
						'DUE_DAY' => (int) issetVal( $columns['DUE_DAY'], 5 ),
						'GRADE_ID' => (int) issetVal( $columns['GRADE_ID'], 0 ),
						'ACTIVE' => issetVal( $columns['ACTIVE'], 'Y' ),
					]
				);
			}
		}

		// Handbook: a template created today with Due Day == today is
		// assigned next month. Mark today so the once-a-day auto-run skips it.
		_sbp_save_config( 'MONTHLY_FEES_LAST_AUTO_RUN', DBDate() );

		$note[] = _( 'Monthly fee templates saved.' );
	}

	RedirectURL( [ 'modfunc', 'values', 'month_values', 'day_values', 'year_values' ] );
}

// Run assignment for the given month.
if ( $_REQUEST['modfunc'] === 'run'
	&& AllowEdit()
	&& isset( $_REQUEST['month'] ) )
{
	$month = mb_substr( (string) $_REQUEST['month'], 0, 7 );

	// Validate YYYY-MM format.
	if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) )
	{
		$error[] = _( 'Invalid month.' );
	}
	else
	{
		$assigned_count = _sbp_monthly_fees_run( $month );

		$note[] = sprintf( _( '%d fee(s) assigned for %s.' ), $assigned_count, $month );
	}

	RedirectURL( [ 'modfunc', 'month', 'month_values', 'day_values', 'year_values' ] );
}

// Per-template Assign flow: load the template to display its details.
if ( $_REQUEST['modfunc'] === 'assign'
	&& AllowEdit()
	&& isset( $_REQUEST['id'] ) )
{
	$assign_template_RET = DBGet( "SELECT ID,TITLE,AMOUNT,DUE_DAY,GRADE_ID
		FROM billing_monthly_fees
		WHERE ID='" . (int) $_REQUEST['id'] . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( empty( $assign_template_RET[1] ) )
	{
		$error[] = _( 'Monthly fee not found.' );
	}
	else
	{
		$assign_template = $assign_template_RET[1];
	}
}

// Per-template Assign flow: add the fee to the selected students.
if ( $_REQUEST['modfunc'] === 'assign_save'
	&& AllowEdit()
	&& isset( $_REQUEST['id'] ) )
{
	$assign_template_RET = DBGet( "SELECT ID,TITLE,AMOUNT,DUE_DAY,GRADE_ID
		FROM billing_monthly_fees
		WHERE ID='" . (int) $_REQUEST['id'] . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( empty( $assign_template_RET[1] ) )
	{
		$error[] = _( 'Monthly fee not found.' );
	}
	elseif ( empty( $_REQUEST['student'] ) )
	{
		$error[] = _( 'You must choose at least one student.' );
	}
	else
	{
		$assign_template = $assign_template_RET[1];

		$month = date( 'Y-m' );

		$assigned_count = 0;

		foreach ( (array) $_REQUEST['student'] as $student_id )
		{
			$assigned_count += _sbp_monthly_fees_assign_student( $assign_template, (int) $student_id, $month );
		}

		$note[] = sprintf(
			_( '%d fee(s) assigned for %s.' ),
			$assigned_count,
			date( 'F', strtotime( $month . '-01' ) )
		);

		RedirectURL( [ 'modfunc', 'id', 'student' ] );
	}
}

// Handbook: once-a-day auto-assignment.
// Active templates whose Due Day is today are assigned for the current month.
// Runs at most once per day (marker stored in program_config).
if ( _sbp_config( 'MONTHLY_FEES_LAST_AUTO_RUN' ) !== DBDate() )
{
	$auto_count = _sbp_monthly_fees_run( date( 'Y-m' ), (int) date( 'j' ) );

	_sbp_save_config( 'MONTHLY_FEES_LAST_AUTO_RUN', DBDate() );

	if ( $auto_count )
	{
		$note[] = sprintf( _( '%d monthly fee(s) assigned automatically.' ), $auto_count );
	}
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

// Per-template Assign flow: Find a Student with checkboxes.
if ( $assign_template !== false )
{
	echo '<br />';

	DrawHeader( '', '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) . '">' . _( 'Back' ) . '</a>' );

	PopTable( 'header', _( 'Monthly Fee' ) );

	echo '<table><tr><td><b>' . _( 'Title' ) . ':</b> ' . htmlspecialchars( $assign_template['TITLE'], ENT_QUOTES ) . '</td></tr>';

	echo '<tr><td><b>' . _( 'Amount' ) . ':</b> ' . Currency( $assign_template['AMOUNT'] ) . '</td></tr>';

	echo '<tr><td><b>' . _( 'Due Day' ) . ':</b> ' . (int) $assign_template['DUE_DAY'] . '</td></tr>';

	echo '<tr><td><b>' . _( 'Month' ) . ':</b> ' . date( 'F Y', strtotime( date( 'Y-m' ) . '-01' ) ) . '</td></tr></table>';

	PopTable( 'footer' );

	echo '<br />';

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=assign_save&id=' . (int) $assign_template['ID'] ) . '" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Add Fee to Selected Students' ) ) );

		echo '<br />';
	}

	$extra = [];

	$extra['link'] = [ 'FULL_NAME' => false ];

	$extra['SELECT'] = ',NULL AS CHECKBOX';

	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];

	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'required', 'STUDENT_ID', 'student' ) ];

	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Fee to Selected Students' ) ) . '</div>';

		echo '</form>';
	}

	return;
}

// Current templates list (with enrolled-student count for the current month).
$cur_month_start = date( 'Y-m-01' );

$cur_month_end = date( 'Y-m-t' );

$functions = [
	'ASSIGN' => '_sbp_makeMonthlyFeesAssign',
	'REMOVE' => '_sbp_makeMonthlyFeesRemove',
	'TITLE' => '_sbp_makeMonthlyFeesTitleInput',
	'AMOUNT' => '_sbp_makeMonthlyFeesAmountInput',
	'DUE_DAY' => '_sbp_makeMonthlyFeesDueDayInput',
	'GRADE_ID' => '_sbp_makeMonthlyFeesGradeInput',
	'STUDENTS' => '_sbp_makeMonthlyFeesStudents',
	'ACTIVE' => '_sbp_makeMonthlyFeesActiveInput',
];

$templates_RET = DBGet( "SELECT bmf.ID,bmf.TITLE,bmf.AMOUNT,bmf.DUE_DAY,bmf.GRADE_ID,bmf.ACTIVE,'' AS ASSIGN,'' AS REMOVE,
		(SELECT COUNT(DISTINCT se.STUDENT_ID)
			FROM student_enrollment se
			WHERE se.SYEAR=bmf.SYEAR
			AND se.SCHOOL_ID=bmf.SCHOOL_ID
			AND (bmf.GRADE_ID=0 OR se.GRADE_ID=bmf.GRADE_ID)
			AND se.START_DATE<='" . $cur_month_end . "'
			AND (se.END_DATE IS NULL OR se.END_DATE>='" . $cur_month_start . "')
		) AS STUDENTS
	FROM billing_monthly_fees bmf
	WHERE bmf.SCHOOL_ID='" . UserSchool() . "'
	AND bmf.SYEAR='" . UserSyear() . "'
	ORDER BY bmf.ID", $functions );

// Grade levels.
$grades_options = [];

$grades_RET = DBGet( "SELECT g.ID,g.TITLE
	FROM school_gradelevels g
	WHERE g.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY g.SORT_ORDER" );

foreach ( (array) $grades_RET as $grade )
{
	$grades_options[ $grade['ID'] ] = $grade['TITLE'];
}

$link = [];

if ( AllowEdit() )
{
	$link['add']['html'] = [
		'TITLE' => _sbp_makeMonthlyFeesTitleInput( '', 'TITLE' ),
		'AMOUNT' => _sbp_makeMonthlyFeesAmountInput( '', 'AMOUNT' ),
		'DUE_DAY' => _sbp_makeMonthlyFeesDueDayInput( '', 'DUE_DAY' ),
		'GRADE_ID' => _sbp_makeMonthlyFeesGradeInput( '', 'GRADE_ID' ),
		'ACTIVE' => _sbp_makeMonthlyFeesActiveInput( '', 'ACTIVE' ),
	];
}

$columns = [
	'TITLE' => _( 'Title' ),
	'AMOUNT' => _( 'Amount' ),
	'DUE_DAY' => _( 'Due Day' ),
	'GRADE_ID' => _( 'Grade' ),
	'STUDENTS' => _( 'Students' ),
	'ACTIVE' => _( 'Active' ),
];

// Add assign & remove button columns for existing rows.
if ( AllowEdit() )
{
	$columns = [
		'ASSIGN' => _( 'Assign' ),
		'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
	] + $columns;
}

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

ListOutput(
	$templates_RET,
	$columns,
	'Monthly Fee',
	'Monthly Fees',
	$link
);

if ( AllowEdit() )
{
	echo '<div class="center">' . SubmitButton() . '</div>';
}

echo '</form>';

// Run assignment form.
echo '<br />';

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=run' ) . '" method="POST">
	<table class="width-100p cellspacing-0"><tr>
		<td>' . _( 'Assign fees for month' ) . '</td>
		<td>' . DateInput( date( 'Y-m' ) . '-01', 'month', '', false, false ) . '</td>
		<td>' . SubmitButton( _( 'Assign Monthly Fees' ) ) . '</td>
	</tr></table>
	</form>';

// Helpers.

/**
 * Last day of the month, portable across MySQL / PostgreSQL.
 *
 * @param  string $date YYYY-MM-DD (first day of the month).
 *
 * @return string SQL expression returning the month's last day.
 */
function _sbp_monthly_fees_last_day_sql( $date )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return "LAST_DAY('" . $date . "')";
	}

	return "(DATE('" . $date . "') + INTERVAL '1 MONTH' - INTERVAL '1 DAY')";
}

/**
 * Assign one monthly fee template to one student for a month.
 * Does nothing if the same fee was already assigned for that month.
 *
 * @param  array  $template   billing_monthly_fees row.
 * @param  int    $student_id Student ID.
 * @param  string $month      YYYY-MM.
 *
 * @return int 1 when a billing_fees row was created, 0 when skipped.
 */
function _sbp_monthly_fees_assign_student( $template, $student_id, $month )
{
	$assigned_date = $month . '-01';

	// Handbook: __MONTH__ is replaced by the month name at assignment time.
	$title = str_replace( '__MONTH__', date( 'F', strtotime( $assigned_date ) ), $template['TITLE'] );

	$due_date = date( 'Y-m-d', strtotime( $assigned_date . ' + ' . ( (int) $template['DUE_DAY'] - 1 ) . ' days' ) );

	// Do not assign twice for the same month.
	$exists = DBGetOne( "SELECT 1
		FROM billing_fees
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND TITLE='" . DBEscapeString( $title ) . "'
		AND AMOUNT='" . (float) $template['AMOUNT'] . "'
		AND ASSIGNED_DATE='" . $assigned_date . "'" );

	if ( $exists )
	{
		return 0;
	}

	DBInsert(
		'billing_fees',
		[
			'STUDENT_ID' => (int) $student_id,
			'SCHOOL_ID' => UserSchool(),
			'SYEAR' => UserSyear(),
			'TITLE' => DBEscapeString( $title ),
			'AMOUNT' => (float) $template['AMOUNT'],
			'ASSIGNED_DATE' => $assigned_date,
			'DUE_DATE' => $due_date,
			'CREATED_BY' => DBEscapeString( _( 'Monthly Fees' ) ),
		]
	);

	return 1;
}

/**
 * Run monthly fee assignment (bulk).
 *
 * @param  string $month   YYYY-MM.
 * @param  int    $due_day Only assign templates whose DUE_DAY matches (0 = all).
 *
 * @return int Number of billing_fees rows created.
 */
function _sbp_monthly_fees_run( $month, $due_day = 0 )
{
	$assigned_date = $month . '-01';

	$count = 0;

	$templates_RET = DBGet( "SELECT ID,TITLE,AMOUNT,DUE_DAY,GRADE_ID
		FROM billing_monthly_fees
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND ACTIVE='Y'"
		. ( (int) $due_day ? " AND DUE_DAY='" . (int) $due_day . "'" : '' ) );

	foreach ( (array) $templates_RET as $template )
	{
		$grade_where = $template['GRADE_ID'] ?
			"AND ssm.GRADE_ID='" . (int) $template['GRADE_ID'] . "'" :
			'';

		$students_RET = DBGet( "SELECT DISTINCT ssm.STUDENT_ID
			FROM student_enrollment ssm
			WHERE ssm.SYEAR='" . UserSyear() . "'
			AND ssm.SCHOOL_ID='" . UserSchool() . "'
			AND ssm.START_DATE<=" . _sbp_monthly_fees_last_day_sql( $assigned_date ) . "
			AND (ssm.END_DATE IS NULL OR ssm.END_DATE>='" . $assigned_date . "')
			" . $grade_where );

		foreach ( (array) $students_RET as $student )
		{
			$count += _sbp_monthly_fees_assign_student( $template, (int) $student['STUDENT_ID'], $month );
		}
	}

	return $count;
}

/**
 * Assign button for existing templates.
 */
function _sbp_makeMonthlyFeesAssign( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] )
		|| ! AllowEdit() )
	{
		return '';
	}

	return button(
		'add',
		_( 'Assign' ),
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=assign&id=' . $THIS_RET['ID'] )
	);
}

/**
 * Remove button for existing templates.
 */
function _sbp_makeMonthlyFeesRemove( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] )
		|| ! AllowEdit() )
	{
		return '';
	}

	return button(
		'remove',
		_( 'Remove' ),
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&id=' . $THIS_RET['ID'] )
	);
}

/**
 * Enrolled-student count with a link to the Students list (grade-filtered).
 */
function _sbp_makeMonthlyFeesStudents( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		return '';
	}

	$grade = (int) $THIS_RET['GRADE_ID'];

	$url = 'Modules.php?modname=Students/Students.php&search_modfunc=list' .
		( $grade ? '&grades[' . $grade . ']=Y' : '' );

	return '<a href="' . URLEscape( $url ) . '">' . (int) $value . '</a>';
}

/**
 * Title input.
 */
function _sbp_makeMonthlyFeesTitleInput( $value, $name )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', 'maxlength=100 size=20', false );
}

/**
 * Amount input.
 */
function _sbp_makeMonthlyFeesAmountInput( $value, $name )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', ' type="number" step="0.01" max="999999999999" min="0"', false );
}

/**
 * Due day input (1-28).
 */
function _sbp_makeMonthlyFeesDueDayInput( $value, $name )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$value = $value ? $value : 5;

	$options = [];

	for ( $i = 1; $i <= 28; $i++ )
	{
		$options[ $i ] = $i;
	}

	return SelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $options, false, '', false );
}

/**
 * Grade input.
 */
function _sbp_makeMonthlyFeesGradeInput( $value, $name )
{
	global $THIS_RET,
		$grades_options;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return SelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $grades_options, _( 'All Grades' ), '', false );
}

/**
 * Active input.
 */
function _sbp_makeMonthlyFeesActiveInput( $value, $name )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return CheckboxInput( $value, 'values[' . $id . '][' . $name . ']', '', '', ( $id === 'new' ) );
}
