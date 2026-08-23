<?php
/**
 * Billing Elements: Elements program
 *
 * Define Billing Elements and organize them into Categories.
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Billing_Elements
 */

require_once 'ProgramFunctions/Widgets.fnc.php';
require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

if ( User( 'PROFILE' ) !== 'admin' )
{
	DrawHeader( ProgramTitle() );

	echo ErrorMessage( [ _( 'You do not have permission to use this program.' ) ] );

	return;
}

DrawHeader( ProgramTitle() );

$error = [];

// Save Categories & Elements.
if ( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] ) )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			// Category or Element depending on the column set.
			if ( isset( $columns['SORT_ORDER'] )
				&& ! isset( $columns['AMOUNT'] ) )
			{
				// Category.
				if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
				{
					if ( $id !== 'new' )
					{
						DBUpdate(
							'billing_elements_categories',
							[
								'TITLE' => DBEscapeString( issetVal( $columns['TITLE'], '' ) ),
								'SORT_ORDER' => (int) issetVal( $columns['SORT_ORDER'], 0 ),
							],
							[ 'ID' => (int) $id, 'SCHOOL_ID' => UserSchool(), 'SYEAR' => UserSyear() ]
						);
					}
					elseif ( ! empty( $columns['TITLE'] ) )
					{
						DBInsert(
							'billing_elements_categories',
							[
								'SCHOOL_ID' => UserSchool(),
								'SYEAR' => UserSyear(),
								'TITLE' => DBEscapeString( $columns['TITLE'] ),
								'SORT_ORDER' => (int) issetVal( $columns['SORT_ORDER'], 0 ),
							]
						);
					}
				}
				else
				{
					$error[] = _( 'Please enter a valid Sort Order.' );
				}
			}
			elseif ( isset( $columns['AMOUNT'] ) )
			{
				// Element.
				if ( empty( $columns['TITLE'] )
					|| ! is_numeric( $columns['AMOUNT'] ) )
				{
					$error[] = _( 'Element title and amount are required.' );

					continue;
				}

				$element_columns = [
					'CATEGORY_ID' => (int) issetVal( $_REQUEST['category_id'], issetVal( $columns['CATEGORY_ID'], 0 ) ),
					'TITLE' => DBEscapeString( $columns['TITLE'] ),
					'AMOUNT' => (float) $columns['AMOUNT'],
					'REFERENCE' => DBEscapeString( issetVal( $columns['REFERENCE'], '' ) ),
					'DESCRIPTION' => DBEscapeString( issetVal( $columns['DESCRIPTION'], '' ) ),
					'GRADE_LEVELS' => DBEscapeString( implode( ',', (array) issetVal( $columns['GRADE_LEVELS'], [] ) ) ),
					'COURSE_PERIOD_ID' => ! empty( $columns['COURSE_PERIOD_ID'] ) ? (int) $columns['COURSE_PERIOD_ID'] : null,
					'ROLLOVER' => issetVal( $columns['ROLLOVER'], '' ) ? 'Y' : '',
				];

				if ( $id !== 'new' )
				{
					DBUpdate(
						'billing_elements',
						$element_columns,
						[ 'ID' => (int) $id, 'SCHOOL_ID' => UserSchool(), 'SYEAR' => UserSyear() ]
					);
				}
				else
				{
					$element_columns['SCHOOL_ID'] = UserSchool();
					$element_columns['SYEAR'] = UserSyear();

					DBInsert(
						'billing_elements',
						$element_columns
					);
				}
			}
		}

		$note[] = _( 'Changes saved.' );
	}

	RedirectURL( [ 'modfunc', 'values', 'category_id' ] );
}

// Remove a Category.
if ( $_REQUEST['modfunc'] === 'remove_category'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Category' ) ) )
	{
		// Do not remove a Category if Elements belong to it.
		$elements_count = DBGetOne( "SELECT COUNT(*)
			FROM billing_elements
			WHERE CATEGORY_ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		if ( $elements_count )
		{
			$error[] = _( 'You cannot delete a Category that has Elements.' );
		}
		else
		{
			DBQuery( "DELETE FROM billing_elements_categories
				WHERE ID='" . (int) $_REQUEST['id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" );

			$note[] = _( 'Category deleted.' );
		}

		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// Remove an Element.
if ( $_REQUEST['modfunc'] === 'remove_element'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Element' ) ) )
	{
		// Do not remove an Element already assigned to students.
		$assigned_count = DBGetOne( "SELECT COUNT(*)
			FROM student_billing_elements
			WHERE ELEMENT_ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		if ( $assigned_count )
		{
			$error[] = _( 'You cannot delete an Element already assigned to students.' );
		}
		else
		{
			// Delete its Monthly Elements setup, if any.
			DBQuery( "DELETE FROM billing_monthly_elements
				WHERE ELEMENT_ID='" . (int) $_REQUEST['id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" );

			DBQuery( "DELETE FROM billing_elements
				WHERE ID='" . (int) $_REQUEST['id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" );

			$note[] = _( 'Element deleted.' );
		}

		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

// Category selected.
$category_id = (int) issetVal( $_REQUEST['category_id'], 0 );

$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
	FROM billing_elements_categories
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [], [ 'ID' ] );

// Form for Categories & Elements.
echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&category_id=' . $category_id ) . '" method="POST">';

echo '<table class="width-100p cellspacing-0"><tr>';

// ---------------------------------------------------------------
// Categories list (left column).
// ---------------------------------------------------------------
echo '<td style="width:30%;vertical-align:top;padding-right:8px;">';

$functions = [
	'REMOVE' => '_be_makeCategoryRemove',
	'TITLE' => '_be_makeCategoryTitleInput',
	'SORT_ORDER' => '_be_makeCategorySortOrderInput',
];

$columns = [];

if ( AllowEdit() )
{
	// Do not Export Delete column.
	$columns['REMOVE'] = '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>';
}

$columns += [
	'TITLE' => _( 'Category' ),
	'SORT_ORDER' => _( 'Sort Order' ),
];

$link = [];

if ( AllowEdit() )
{
	$link['add']['html'] = [
		'REMOVE' => button( 'add' ),
		'TITLE' => _be_makeCategoryTitleInput( '', 'TITLE' ),
		'SORT_ORDER' => _be_makeCategorySortOrderInput( '', 'SORT_ORDER' ),
	];
}

ListOutput(
	$categories_RET,
	$columns,
	'Category',
	'Categories',
	$link,
	$functions,
	[ 'valign-middle' => true ]
);

echo '</td>';

// ---------------------------------------------------------------
// Elements list (right column).
// ---------------------------------------------------------------
echo '<td style="width:70%;vertical-align:top;">';

$elements_where = $category_id ?
	"AND e.CATEGORY_ID='" . $category_id . "'" :
	'';

$elements_RET = DBGet( "SELECT e.ID,e.CATEGORY_ID,e.TITLE,e.AMOUNT,e.REFERENCE,e.DESCRIPTION,
		e.GRADE_LEVELS,e.COURSE_PERIOD_ID,e.ROLLOVER,c.TITLE AS CATEGORY_TITLE
	FROM billing_elements e
	LEFT JOIN billing_elements_categories c ON c.ID=e.CATEGORY_ID
	WHERE e.SCHOOL_ID='" . UserSchool() . "'
	AND e.SYEAR='" . UserSyear() . "'
	" . $elements_where . "
	ORDER BY e.TITLE", [], [ 'ID' ] );

// Category filter.
$category_options = [];

foreach ( (array) $categories_RET as $category )
{
	$category_options[ $category['ID'] ] = $category['TITLE'];
}

echo '<table class="width-100p cellspacing-0"><tr><td style="padding-bottom:6px;">' .
	'<b>' . _( 'Category' ) . ':</b> ' .
	SelectInput(
		$category_id,
		'category',
		'',
		$category_options,
		_( 'All Categories' ),
		'onchange="' . AttrEscape( 'ajaxLink(' . json_encode( PreparePHP_SELF( [], [ 'category_id' ] ) ) . ' + \'&category_id=\' + this.value);' ) . '"',
		false
	) .
	'</td></tr></table>';

$functions = [
	'REMOVE' => '_be_makeElementRemove',
	'ASSIGN' => '_be_makeElementAssign',
	'TITLE' => '_be_makeElementTitleInput',
	'AMOUNT' => '_be_makeElementAmountInput',
	'REFERENCE' => '_be_makeElementReferenceInput',
	'DESCRIPTION' => '_be_makeElementDescriptionInput',
	'GRADE_LEVELS' => '_be_makeElementGradeLevelsInput',
	'COURSE_PERIOD_ID' => '_be_makeElementCoursePeriodInput',
	'ROLLOVER' => '_be_makeElementRolloverInput',
];

$columns = [];

if ( AllowEdit() )
{
	// Do not Export Delete column.
	$columns['REMOVE'] = '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>';
}

$columns += [
	'TITLE' => _( 'Title' ),
	'AMOUNT' => _( 'Amount' ),
	'REFERENCE' => _( 'Reference' ),
	'DESCRIPTION' => _( 'Description' ),
	'GRADE_LEVELS' => _( 'Grade Levels' ),
	'COURSE_PERIOD_ID' => _( 'Course Period' ),
	'ROLLOVER' => _( 'Rollover' ),
];

if ( AllowEdit() )
{
	// Add Assign button column.
	$columns['ASSIGN'] = _( 'Assign' );
}

$link = [];

if ( AllowEdit() )
{
	$link['add']['html'] = [
		'REMOVE' => button( 'add' ),
		'TITLE' => _be_makeElementTitleInput( '', 'TITLE' ),
		'AMOUNT' => _be_makeElementAmountInput( '', 'AMOUNT' ),
		'REFERENCE' => _be_makeElementReferenceInput( '', 'REFERENCE' ),
		'DESCRIPTION' => _be_makeElementDescriptionInput( '', 'DESCRIPTION' ),
		'GRADE_LEVELS' => _be_makeElementGradeLevelsInput( '', 'GRADE_LEVELS' ),
		'COURSE_PERIOD_ID' => _be_makeElementCoursePeriodInput( '', 'COURSE_PERIOD_ID' ),
		'ROLLOVER' => _be_makeElementRolloverInput( '', 'ROLLOVER' ),
	];
}

ListOutput(
	$elements_RET,
	$columns,
	'Element',
	'Elements',
	$link,
	$functions,
	[ 'valign-middle' => true ]
);

echo '</td></tr></table>';

if ( AllowEdit() )
{
	echo '<div class="center">' . SubmitButton() . '</div>';
}

echo '</form>';

/**
 * Category: Remove button.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeCategoryRemove( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] )
		|| ! AllowEdit() )
	{
		return '';
	}

	$button_link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove_category&id=' .
		$THIS_RET['ID'];

	return button( 'remove', '', URLEscape( $button_link ) );
}

/**
 * Category: Title input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeCategoryTitleInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', 'maxlength=100', false );
}

/**
 * Category: Sort Order input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeCategorySortOrderInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', 'type="number" min="-9999" max="9999"', false );
}

/**
 * Element: Remove button.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementRemove( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] )
		|| ! AllowEdit() )
	{
		return '';
	}

	$button_link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove_element&id=' .
		$THIS_RET['ID'];

	return button( 'remove', '', URLEscape( $button_link ) );
}

/**
 * Element: Assign button (link to Mass Assign Elements, prefilled).
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementAssign( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		return '';
	}

	$assign_link = 'Modules.php?modname=Billing_Elements/MassAssignElements.php&element_id=' .
		$THIS_RET['ID'];

	return '<a class="button" href="' . URLEscape( $assign_link ) . '">' . _( 'Assign' ) . '</a>';
}

/**
 * Element: Title input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementTitleInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', 'maxlength=100', false );
}

/**
 * Element: Amount input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementAmountInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', 'type="number" step="0.01" min="0" max="999999999999"', false );
}

/**
 * Element: Reference input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementReferenceInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', 'maxlength=50', false );
}

/**
 * Element: Description input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementDescriptionInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', 'maxlength=255', false );
}

/**
 * Element: Grade Levels input (multi-select).
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementGradeLevelsInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$grades_options = _be_grade_levels_options();

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
		// Display selected grade titles.
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

/**
 * Element: Course Period input.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementCoursePeriodInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$course_periods_options = _be_course_periods_options();

	if ( $id !== 'new' )
	{
		return issetVal( $course_periods_options[ $value ], '' );
	}

	return SelectInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$course_periods_options,
		_( 'None' ),
		'',
		false
	);
}

/**
 * Element: Rollover checkbox.
 *
 * @param  string $value  Value.
 * @param  string $column Column.
 *
 * @return string HTML.
 */
function _be_makeElementRolloverInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	if ( $id !== 'new' )
	{
		return CheckboxInput( $value, 'values[' . $id . '][' . $column . ']', '', '', false );
	}

	// New: checked by default.
	return CheckboxInput( 'Y', 'values[' . $id . '][' . $column . ']', '', '', true );
}

/**
 * Grade Levels options for the current school.
 *
 * @return array Grade ID => Title.
 */
function _be_grade_levels_options()
{
	static $grades_options;

	if ( ! is_null( $grades_options ) )
	{
		return $grades_options;
	}

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

	return $grades_options;
}

/**
 * Course Periods options for the current school.
 *
 * @return array Course Period ID => Title - Teacher.
 */
function _be_course_periods_options()
{
	static $course_periods_options;

	if ( ! is_null( $course_periods_options ) )
	{
		return $course_periods_options;
	}

	$course_periods_options = [];

	$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,
		COALESCE(c.TITLE,'') AS COURSE_TITLE,
		COALESCE(DisplayNameSQL( 's' ), '') AS TEACHER_NAME
		FROM course_periods cp
		LEFT JOIN courses c ON c.COURSE_ID=cp.COURSE_ID
		LEFT JOIN staff s ON s.STAFF_ID=cp.TEACHER_ID
		WHERE cp.SYEAR='" . UserSyear() . "'
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY c.TITLE" );

	foreach ( (array) $course_periods_RET as $course_period )
	{
		$course_periods_options[ $course_period['COURSE_PERIOD_ID'] ] =
			$course_period['COURSE_TITLE'] .
			( $course_period['TEACHER_NAME'] ? ' - ' . $course_period['TEACHER_NAME'] : '' );
	}

	return $course_periods_options;
}
