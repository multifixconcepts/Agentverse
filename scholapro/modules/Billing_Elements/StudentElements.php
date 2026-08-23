<?php
/**
 * Billing Elements: Student Elements program
 *
 * Consult, assign, or remove Billing Elements and their corresponding Fee for a single student.
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Billing_Elements
 */

require_once 'modules/Billing_Elements/includes/functions.inc.php';

$error = [];

// Add an Element & Fee to the student.
if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit()
	&& UserStudentID() )
{
	$element_id = (int) issetVal( $_REQUEST['element_id'], 0 );

	if ( ! $element_id )
	{
		$error[] = _( 'Please select an Element.' );
	}
	else
	{
		AddRequestedDates( 'values', 'post' );

		$due_date = _be_validate_date( issetVal( $_REQUEST['due_date'], '' ) );

		$comment = issetVal( $_REQUEST['comment'], '' );

		$link_id = _be_assign_element(
			UserStudentID(),
			$element_id,
			$due_date,
			$comment
		);

		if ( $link_id )
		{
			$note[] = _( 'Element and Fee added.' );
		}
		else
		{
			$error[] = _( 'The Element could not be added (already assigned or invalid).' );
		}

		RedirectURL( [ 'modfunc', 'element_id', 'due_date', 'comment', 'month_due_date', 'day_due_date', 'year_due_date' ] );
	}
}

// Remove an Element & its Fee.
if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit()
	&& UserStudentID() )
{
	if ( DeletePrompt( _( 'Element' ) ) )
	{
		if ( _be_remove_element( (int) $_REQUEST['id'] ) )
		{
			$note[] = _( 'Element and Fee removed.' );
		}
		else
		{
			$error[] = _( 'The Element could not be removed.' );
		}

		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );

	Search( 'student_id', issetVal( $extra ) );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

if ( ! UserStudentID() )
{
	return;
}

$elements_RET = DBGet( "SELECT sbe.ID AS LINK_ID,sbe.ELEMENT_ID,sbe.COMMENT AS LINK_COMMENT,
		f.ID AS FEE_ID,f.TITLE AS FEE_TITLE,f.AMOUNT AS FEE_AMOUNT,
		f.ASSIGNED_DATE AS FEE_ASSIGNED_DATE,f.DUE_DATE AS FEE_DUE_DATE,
		f.COMMENTS AS FEE_COMMENTS,e.TITLE AS ELEMENT_TITLE,e.REFERENCE,e.CATEGORY_ID,
		c.TITLE AS CATEGORY_TITLE
	FROM student_billing_elements sbe
	LEFT JOIN billing_elements e ON e.ID=sbe.ELEMENT_ID
	LEFT JOIN billing_elements_categories c ON c.ID=e.CATEGORY_ID
	LEFT JOIN billing_fees f ON f.ID=sbe.FEE_ID
	WHERE sbe.STUDENT_ID='" . UserStudentID() . "'
	AND sbe.SYEAR='" . UserSyear() . "'
	AND sbe.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY c.TITLE,e.TITLE", [], [ 'LINK_ID' ] );

$columns = [
	'ELEMENT_TITLE' => _( 'Element' ),
	'CATEGORY_TITLE' => _( 'Category' ),
	'REFERENCE' => _( 'Reference' ),
	'FEE_AMOUNT' => _( 'Fee Amount' ),
	'FEE_ASSIGNED_DATE' => _( 'Assigned' ),
	'FEE_DUE_DATE' => _( 'Due Date' ),
	'FEE_COMMENTS' => _( 'Comment' ),
];

$functions = [
	'FEE_AMOUNT' => 'Currency',
	'FEE_ASSIGNED_DATE' => 'ProperDate',
	'FEE_DUE_DATE' => 'ProperDate',
];

if ( AllowEdit() )
{
	// Do not Export Delete column.
	$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ] + $columns;

	$functions['REMOVE'] = '_be_makeRemoveButton';
}

ListOutput(
	$elements_RET,
	$columns,
	'Student Element',
	'Student Elements',
	[],
	$functions,
	[ 'valign-middle' => true ]
);

// Add Element form.
if ( AllowEdit() )
{
	$elements_options = [];

	$available_elements_RET = DBGet( "SELECT e.ID,e.TITLE,e.AMOUNT,c.TITLE AS CATEGORY_TITLE
		FROM billing_elements e
		LEFT JOIN billing_elements_categories c ON c.ID=e.CATEGORY_ID
		WHERE e.SCHOOL_ID='" . UserSchool() . "'
		AND e.SYEAR='" . UserSyear() . "'
		AND e.ID NOT IN (SELECT ELEMENT_ID
			FROM student_billing_elements
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "')
		ORDER BY e.TITLE" );

	foreach ( (array) $available_elements_RET as $element )
	{
		$elements_options[ $element['ID'] ] = $element['TITLE'] . ' ' . Currency( $element['AMOUNT'] ) .
			( $element['CATEGORY_TITLE'] ? ' (' . $element['CATEGORY_TITLE'] . ')' : '' );
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

	echo '<table class="width-100p cellspacing-0"><tr>
		<td>' . _( 'Element' ) . '</td><td>' .
		SelectInput(
			issetVal( $_REQUEST['element_id'], '' ),
			'element_id',
			_( 'Element' ),
			$elements_options,
			_( 'Please select an Element' ),
			'',
			false
		) .
		'</td>
		<td>' . _( 'Due Date' ) . '</td><td>' .
		DateInput( issetVal( $_REQUEST['due_date'], '' ), 'due_date', '', false, false ) .
		'</td>
		<td>' . _( 'Comment' ) . '</td><td>' .
		TextInput( issetVal( $_REQUEST['comment'], '' ), 'comment', _( 'Comment' ), 'size="30" maxlength="255"', false ) .
		'</td>
		<td>' . SubmitButton() . '</td>
		</tr></table>';

	echo '</form>';
}

/**
 * Delete button.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeRemoveButton( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['LINK_ID'] )
		|| ! AllowEdit() )
	{
		return '';
	}

	$button_link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' .
		$THIS_RET['LINK_ID'];

	return button( 'remove', '', URLEscape( $button_link ) );
}
