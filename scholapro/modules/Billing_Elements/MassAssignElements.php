<?php
/**
 * Billing Elements: Mass Assign Elements program
 *
 * Assign a Billing Element and the corresponding Fee to various students at once.
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

$error = [];

// Element selected (from the Assign button in the Elements program).
$element_id = (int) issetVal( $_REQUEST['element_id'], 0 );

// Assign Element & Fee to selected students.
if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit()
	&& $element_id
	&& ! empty( $_REQUEST['student_id'] ) )
{
	AddRequestedDates( 'values', 'post' );

	$due_date = _be_validate_date( issetVal( $_REQUEST['due_date'], '' ) );

	$comment = issetVal( $_REQUEST['comment'], '' );

	$assigned = 0;

	$skipped = 0;

	foreach ( (array) $_REQUEST['student_id'] as $student_id )
	{
		$link_id = _be_assign_element(
			(int) $student_id,
			$element_id,
			$due_date,
			$comment
		);

		if ( $link_id )
		{
			$assigned++;
		}
		else
		{
			$skipped++;
		}
	}

	$note[] = sprintf( _( '%d element(s) assigned.' ), $assigned );

	if ( $skipped )
	{
		$error[] = sprintf( _( '%d student(s) skipped (element already assigned or invalid).' ), $skipped );
	}

	RedirectURL( [ 'modfunc', 'student_id', 'element_id', 'due_date', 'comment' ] );
}

// Assign all Grade Level restricted Elements to the matching students.
if ( $_REQUEST['modfunc'] === 'grade_assign'
	&& AllowEdit() )
{
	$assigned = 0;

	$elements_RET = DBGet( "SELECT e.ID,e.GRADE_LEVELS
		FROM billing_elements e
		WHERE e.SCHOOL_ID='" . UserSchool() . "'
		AND e.SYEAR='" . UserSyear() . "'
		AND e.GRADE_LEVELS IS NOT NULL
		AND e.GRADE_LEVELS<>''" );

	foreach ( (array) $elements_RET as $element )
	{
		$grade_levels = explode( ',', str_replace( ' ', '', (string) $element['GRADE_LEVELS'] ) );

		if ( ! $grade_levels )
		{
			continue;
		}

		$grade_ids_sql = '';

		foreach ( $grade_levels as $grade_id )
		{
			if ( $grade_id !== '' )
			{
				$grade_ids_sql .= ( $grade_ids_sql ? ',' : '' ) . "'" . (int) $grade_id . "'";
			}
		}

		if ( ! $grade_ids_sql )
		{
			continue;
		}

		$students_RET = DBGet( "SELECT DISTINCT ssm.STUDENT_ID
			FROM student_enrollment ssm
			WHERE ssm.SYEAR='" . UserSyear() . "'
			AND ssm.SCHOOL_ID='" . UserSchool() . "'
			AND ssm.GRADE_ID IN(" . $grade_ids_sql . ")
			AND ssm.START_DATE<=CURRENT_DATE
			AND (ssm.END_DATE IS NULL OR ssm.END_DATE>=CURRENT_DATE)" );

		foreach ( (array) $students_RET as $student )
		{
			if ( _be_assign_element( (int) $student['STUDENT_ID'], (int) $element['ID'], '', '' ) )
			{
				$assigned++;
			}
		}
	}

	$note[] = sprintf( _( '%d element(s) assigned by Grade Level.' ), $assigned );

	RedirectURL( [ 'modfunc' ] );
}

DrawHeader( ProgramTitle() );

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

// Help.
echo '<div class="center">' .
	'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=grade_assign' ) .
	'" class="button">' . _( 'Assign Elements by Grade Level' ) . '</a></div><br />';

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) .
	'&element_id=' . $element_id . '" method="POST">';

// Element select.
$elements_RET = DBGet( "SELECT e.ID,e.TITLE,e.AMOUNT,e.CATEGORY_ID,c.TITLE AS CATEGORY_TITLE
	FROM billing_elements e
	LEFT JOIN billing_elements_categories c ON c.ID=e.CATEGORY_ID
	WHERE e.SCHOOL_ID='" . UserSchool() . "'
	AND e.SYEAR='" . UserSyear() . "'
	ORDER BY e.TITLE" );

$elements_options = [];

foreach ( (array) $elements_RET as $element )
{
	$elements_options[ $element['ID'] ] = $element['TITLE'] .
		( $element['CATEGORY_TITLE'] ? ' (' . $element['CATEGORY_TITLE'] . ')' : '' );
}

// Prefill selected element details (JS).
$selected_element = [];

if ( $element_id
	&& isset( $elements_RET[ $element_id ] ) )
{
	$selected_element = $elements_RET[ $element_id ];
}

$extra = [];

$extra['search'] = '<tr><td>' . _( 'Element' ) . '</td><td>' .
	SelectInput(
		$element_id,
		'element_id',
		_( 'Element' ),
		$elements_options,
		_( 'Please select an Element' ),
		'onchange="' . AttrEscape( 'ajaxLink(' . json_encode( PreparePHP_SELF( $_REQUEST, [], [ 'element_id' ] ) ) . ' + \'&element_id=\' + this.value);' ) . '"',
		false
	) .
	'</td></tr>';

$extra['search'] .= '<tr><td>' . _( 'Due Date' ) . '</td><td>' .
	DateInput( issetVal( $_REQUEST['due_date'], '' ), 'due_date', '', false, false ) .
	'</td></tr>';

$extra['search'] .= '<tr><td>' . _( 'Comment' ) . '</td><td>' .
	TextInput( issetVal( $_REQUEST['comment'], '' ), 'comment', _( 'Comment' ), 'size="40" maxlength="255"', false ) .
	'</td></tr>';

$extra['new'] = true;

$extra['search'] .= '<tr><td class="valign-top">' . _( 'Fee' ) . '</td><td>' .
	'<div id="fee_preview" style="padding-top:4px;">' .
		( $selected_element ?
			'<b>' . $selected_element['TITLE'] . '</b> ' . Currency( $selected_element['AMOUNT'] ) :
			_( 'Select an Element to preview the fee.' ) ) .
	'</div></td></tr>';

Search( 'student_id', $extra );

echo '<div class="center">' . SubmitButton( _( 'Add Element and Fee to Selected Students' ) ) . '</div>';

echo '</form>';
