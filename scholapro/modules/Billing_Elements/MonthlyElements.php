<?php
/**
 * Billing Elements: Monthly Elements program
 *
 * Set up monthly fees to be automatically assigned to students.
 * Can be used for recurring fees or installments.
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Billing_Elements
 */

require_once 'modules/Billing_Elements/includes/functions.inc.php';

if ( User( 'PROFILE' ) !== 'admin' )
{
	DrawHeader( ProgramTitle() );

	echo ErrorMessage( [ _( 'You do not have permission to use this program.' ) ] );

	return;
}

DrawHeader( ProgramTitle() );

$error = [];

// Remove a setup.
if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Monthly Element' ) ) )
	{
		DBQuery( "DELETE FROM billing_monthly_elements
			WHERE ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		$note[] = _( 'Monthly Element setup removed.' );

		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// Save setups.
if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	if ( is_array( $_REQUEST['values'] ) )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			if ( empty( $columns['ELEMENT_ID'] ) )
			{
				$error[] = _( 'Please select an Element.' );

				continue;
			}

			$columns['DUE_DAY'] = (int) issetVal( $columns['DUE_DAY'], 5 );

			$columns['GRADE_LEVELS'] = DBEscapeString( implode( ',', (array) issetVal( $columns['GRADE_LEVELS'], [] ) ) );

			if ( $id !== 'new' )
			{
				DBUpdate(
					'billing_monthly_elements',
					$columns,
					[ 'ID' => (int) $id, 'SCHOOL_ID' => UserSchool(), 'SYEAR' => UserSyear() ]
				);
			}
			else
			{
				DBInsert(
					'billing_monthly_elements',
					[
						'SCHOOL_ID' => UserSchool(),
						'SYEAR' => UserSyear(),
						'ELEMENT_ID' => (int) $columns['ELEMENT_ID'],
						'DUE_DAY' => $columns['DUE_DAY'],
						'GRADE_LEVELS' => $columns['GRADE_LEVELS'],
					]
				);
			}
		}

		$note[] = _( 'Monthly Element setups saved.' );
	}

	RedirectURL( [ 'modfunc', 'values', 'month', 'month_values', 'day_values', 'year_values' ] );
}

// Run assignment for a month.
if ( $_REQUEST['modfunc'] === 'run'
	&& AllowEdit()
	&& isset( $_REQUEST['month'] ) )
{
	$month = mb_substr( (string) $_REQUEST['month'], 0, 7 );

	if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) )
	{
		$error[] = _( 'Invalid month.' );
	}
	else
	{
		$assigned_count = _be_monthly_elements_run( $month );

		$note[] = sprintf( _( '%d fee(s) assigned for %s.' ), $assigned_count, $month );
	}

	RedirectURL( [ 'modfunc', 'month', 'month_values', 'day_values', 'year_values' ] );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

// Current setups.
$monthly_elements_RET = DBGet( "SELECT m.ID,m.ELEMENT_ID,m.DUE_DAY,m.GRADE_LEVELS,
		e.TITLE AS ELEMENT_TITLE,e.AMOUNT AS ELEMENT_AMOUNT
	FROM billing_monthly_elements m
	LEFT JOIN billing_elements e ON e.ID=m.ELEMENT_ID
	WHERE m.SCHOOL_ID='" . UserSchool() . "'
	AND m.SYEAR='" . UserSyear() . "'
	ORDER BY e.TITLE", [], [ 'ID' ] );

$elements_options = [];

$elements_RET = DBGet( "SELECT e.ID,e.TITLE,e.AMOUNT
	FROM billing_elements e
	WHERE e.SCHOOL_ID='" . UserSchool() . "'
	AND e.SYEAR='" . UserSyear() . "'
	ORDER BY e.TITLE" );

foreach ( (array) $elements_RET as $element )
{
	$elements_options[ $element['ID'] ] = $element['TITLE'] . ' ' . Currency( $element['AMOUNT'] );
}

$functions = [
	'REMOVE' => '_be_makeMonthlyElementsRemove',
	'ELEMENT_ID' => '_be_makeMonthlyElementsElementInput',
	'DUE_DAY' => '_be_makeMonthlyElementsDueDayInput',
	'GRADE_LEVELS' => '_be_makeMonthlyElementsGradeLevelsInput',
];

$columns = [ 'ELEMENT_ID' => _( 'Element' ), 'DUE_DAY' => _( 'Due Day' ), 'GRADE_LEVELS' => _( 'Grade Levels' ) ];

if ( AllowEdit() )
{
	$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ] + $columns;
}

$link = [];

if ( AllowEdit() )
{
	$link['add']['html'] = [
		'REMOVE' => button( 'add' ),
		'ELEMENT_ID' => _be_makeMonthlyElementsElementInput( '', 'ELEMENT_ID' ),
		'DUE_DAY' => _be_makeMonthlyElementsDueDayInput( '', 'DUE_DAY' ),
		'GRADE_LEVELS' => _be_makeMonthlyElementsGradeLevelsInput( '', 'GRADE_LEVELS' ),
	];
}

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

ListOutput(
	$monthly_elements_RET,
	$columns,
	'Monthly Element',
	'Monthly Elements',
	$link,
	$functions,
	[ 'valign-middle' => true ]
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

/**
 * Run monthly element assignment (local function).
 *
 * @param  string $month YYYY-MM.
 *
 * @return int Number of billing_fees rows created.
 */
function _be_monthly_elements_run( $month )
{
	$assigned_date = $month . '-01';

	$count = 0;

	$setups_RET = DBGet( "SELECT m.ELEMENT_ID,m.DUE_DAY,m.GRADE_LEVELS,e.TITLE,e.AMOUNT
		FROM billing_monthly_elements m
		LEFT JOIN billing_elements e ON e.ID=m.ELEMENT_ID
		WHERE m.SCHOOL_ID='" . UserSchool() . "'
		AND m.SYEAR='" . UserSyear() . "'" );

	foreach ( (array) $setups_RET as $setup )
	{
		$due_date = date( 'Y-m-d', strtotime( $assigned_date . ' + ' . ( (int) $setup['DUE_DAY'] - 1 ) . ' days' ) );

		$grade_where = '';

		$grade_levels = explode( ',', str_replace( ' ', '', (string) $setup['GRADE_LEVELS'] ) );

		if ( $grade_levels )
		{
			$grade_ids_sql = '';

			foreach ( $grade_levels as $grade_id )
			{
				if ( $grade_id !== '' )
				{
					$grade_ids_sql .= ( $grade_ids_sql ? ',' : '' ) . "'" . (int) $grade_id . "'";
				}
			}

			if ( $grade_ids_sql )
			{
				$grade_where = "AND ssm.GRADE_ID IN(" . $grade_ids_sql . ")";
			}
		}

		$students_RET = DBGet( "SELECT DISTINCT ssm.STUDENT_ID
			FROM student_enrollment ssm
			WHERE ssm.SYEAR='" . UserSyear() . "'
			AND ssm.SCHOOL_ID='" . UserSchool() . "'
			AND ssm.START_DATE<=LAST_DAY('" . $assigned_date . "')
			AND (ssm.END_DATE IS NULL OR ssm.END_DATE>='" . $assigned_date . "')
			" . $grade_where );

		foreach ( (array) $students_RET as $student )
		{
			// Do not assign twice for the same month.
			$exists = DBGetOne( "SELECT 1
				FROM billing_fees
				WHERE STUDENT_ID='" . (int) $student['STUDENT_ID'] . "'
				AND SYEAR='" . UserSyear() . "'
				AND TITLE='" . DBEscapeString( $setup['TITLE'] ) . "'
				AND AMOUNT='" . (float) $setup['AMOUNT'] . "'
				AND ASSIGNED_DATE='" . $assigned_date . "'" );

			if ( $exists )
			{
				continue;
			}

			DBInsert(
				'billing_fees',
				[
					'STUDENT_ID' => (int) $student['STUDENT_ID'],
					'SCHOOL_ID' => UserSchool(),
					'SYEAR' => UserSyear(),
					'TITLE' => DBEscapeString( $setup['TITLE'] ),
					'AMOUNT' => (float) $setup['AMOUNT'],
					'ASSIGNED_DATE' => $assigned_date,
					'DUE_DATE' => $due_date,
					'COMMENTS' => DBEscapeString( _( 'Monthly Element' ) . ' ' . $month ),
					'CREATED_BY' => DBEscapeString( _( 'Monthly Elements' ) ),
				]
			);

			$count++;
		}
	}

	return $count;
}

/**
 * Remove button.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeMonthlyElementsRemove( $value, $column )
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
 * Element select input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeMonthlyElementsElementInput( $value, $column )
{
	global $THIS_RET,
		$elements_options;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	if ( $id !== 'new' )
	{
		return issetVal( $THIS_RET['ELEMENT_TITLE'], '' );
	}

	return SelectInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$elements_options,
		_( 'Please select an Element' ),
		'',
		false
	);
}

/**
 * Due day input (1-28).
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeMonthlyElementsDueDayInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$value = $value ? $value : 5;

	$options = [];

	for ( $i = 1; $i <= 28; $i++ )
	{
		$options[ $i ] = $i;
	}

	return SelectInput( $value, 'values[' . $id . '][' . $column . ']', '', $options, false, '', false );
}

/**
 * Grade Levels input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeMonthlyElementsGradeLevelsInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$grades_options = [];

	$grades_RET = DBGet( "SELECT g.ID,g.TITLE
		FROM school_gradelevels g
		WHERE g.SCHOOL_ID='" . UserSchool() . "'
		AND g.SYEAR='" . UserSyear() . "'
		ORDER BY g.SORT_ORDER,g.TITLE" );

	foreach ( (array) $grades_RET as $grade )
	{
		$grades_options[ $grade['ID'] ] = $grade['TITLE'];
	}

	$selected = [];

	foreach ( explode( ',', str_replace( ' ', '', (string) $value ) ) as $grade_id )
	{
		if ( $grade_id !== '' )
		{
			$selected[] = $grade_id;
		}
	}

	if ( $id !== 'new' )
	{
		$titles = [];

		foreach ( $selected as $grade_id )
		{
			if ( isset( $grades_options[ $grade_id ] ) )
			{
				$titles[] = $grades_options[ $grade_id ];
			}
		}

		return $titles ? implode( ', ', $titles ) : _( 'All Grades' );
	}

	return SelectInput(
		$selected,
		'values[' . $id . '][' . $column . '][]',
		'',
		$grades_options,
		_( 'All Grades' ),
		'multiple size="4"',
		false
	);
}
