<?php
/**
 * Billing Elements module helper functions
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Billing_Elements
 */

/**
 * Get the current grade level of a student (in the current school year).
 *
 * @param  int $student_id Student ID.
 *
 * @return int Grade level ID (0 if none).
 */
function _be_student_grade_id( $student_id )
{
	$grade_id = DBGetOne( "SELECT GRADE_ID
		FROM student_enrollment
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND START_DATE<=CURRENT_DATE
		AND (END_DATE IS NULL OR END_DATE>=CURRENT_DATE)
		ORDER BY START_DATE DESC
		LIMIT 1" );

	return (int) $grade_id;
}

/**
 * Does the Element apply to the given grade level?
 *
 * An element without grade restriction applies to all grades.
 *
 * @param  array $element  Element row (must contain GRADE_LEVELS).
 * @param  int   $grade_id Grade level ID.
 *
 * @return bool True if the element applies.
 */
function _be_element_applies_to_grade( $element, $grade_id )
{
	if ( empty( $element['GRADE_LEVELS'] ) )
	{
		// No grade restriction.
		return true;
	}

	$grade_levels = explode( ',', str_replace( ' ', '', (string) $element['GRADE_LEVELS'] ) );

	return in_array( (string) $grade_id, $grade_levels );
}

/**
 * Get elements available for a student (grade appropriate), current school year.
 *
 * @param  int $student_id Student ID.
 *
 * @return array Elements list.
 */
function _be_elements_for_student( $student_id )
{
	$grade_id = _be_student_grade_id( $student_id );

	$elements_RET = DBGet( "SELECT e.ID,e.CATEGORY_ID,e.TITLE,e.AMOUNT,e.REFERENCE,e.DESCRIPTION,e.GRADE_LEVELS,e.COURSE_PERIOD_ID,c.TITLE AS CATEGORY_TITLE
		FROM billing_elements e
		LEFT JOIN billing_elements_categories c ON c.ID=e.CATEGORY_ID
		WHERE e.SCHOOL_ID='" . UserSchool() . "'
		AND e.SYEAR='" . UserSyear() . "'
		ORDER BY c.SORT_ORDER,c.TITLE,e.TITLE" );

	$students_elements = [];

	foreach ( (array) $elements_RET as $element )
	{
		if ( _be_element_applies_to_grade( $element, $grade_id ) )
		{
			$students_elements[] = $element;
		}
	}

	return $students_elements;
}

/**
 * Is the Element already assigned to the student for the current school year?
 *
 * @param  int $student_id Student ID.
 * @param  int $element_id Element ID.
 *
 * @return bool True if already assigned.
 */
function _be_is_element_assigned( $student_id, $element_id )
{
	return (bool) DBGetOne( "SELECT 1
		FROM student_billing_elements
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND ELEMENT_ID='" . (int) $element_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		LIMIT 1" );
}

/**
 * Enroll a student in a course period (schedule).
 *
 * @param  int $student_id      Student ID.
 * @param  int $course_period_id Course period ID.
 *
 * @return bool True on success.
 */
function _be_enroll_course_period( $student_id, $course_period_id )
{
	$course_period_RET = DBGet( "SELECT COURSE_ID,MARKING_PERIOD_ID,MP
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( empty( $course_period_RET[1] ) )
	{
		return false;
	}

	$course_period = $course_period_RET[1];

	// Check the student is not already enrolled in this course period.
	$already = DBGetOne( "SELECT 1
		FROM schedule
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		LIMIT 1" );

	if ( $already )
	{
		return false;
	}

	return (bool) DBInsert(
		'schedule',
		[
			'SYEAR' => UserSyear(),
			'SCHOOL_ID' => UserSchool(),
			'STUDENT_ID' => (int) $student_id,
			'COURSE_ID' => (int) $course_period['COURSE_ID'],
			'COURSE_PERIOD_ID' => (int) $course_period_id,
			'MARKING_PERIOD_ID' => (int) $course_period['MARKING_PERIOD_ID'],
			'MP' => issetVal( $course_period['MP'], '' ),
			'START_DATE' => DBDate(),
			'MODIFIED_BY' => DBEscapeString( User( 'NAME' ) ),
		],
		'id'
	);
}

/**
 * Assign an Element & create the corresponding Fee for a student.
 *
 * Refuses to create a duplicate assignment for the same school year.
 *
 * @param  int    $student_id Student ID.
 * @param  int    $element_id Element ID.
 * @param  string $due_date   Due date (YYYY-MM-DD). Defaults to '' (none).
 * @param  string $comments   Fee comment.
 * @param  bool   $enroll_course Whether to enroll the student in the associated course period.
 *
 * @return int|false Fee ID (or link ID) on success, false on failure.
 */
function _be_assign_element( $student_id, $element_id, $due_date = '', $comments = '', $enroll_course = false )
{
	$element_RET = DBGet( "SELECT *
		FROM billing_elements
		WHERE ID='" . (int) $element_id . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( empty( $element_RET[1] ) )
	{
		return false;
	}

	$element = $element_RET[1];

	if ( _be_is_element_assigned( $student_id, $element_id ) )
	{
		return false;
	}

	// Create the billing fee.
	$fee_id = DBInsert(
		'billing_fees',
		[
			'STUDENT_ID' => (int) $student_id,
			'SCHOOL_ID' => UserSchool(),
			'SYEAR' => UserSyear(),
			'TITLE' => DBEscapeString( $element['TITLE'] ),
			'AMOUNT' => (float) $element['AMOUNT'],
			'ASSIGNED_DATE' => DBDate(),
			'DUE_DATE' => _be_validate_date( $due_date ) ? $due_date : null,
			'COMMENTS' => $comments ? DBEscapeString( $comments ) : DBEscapeString( _( 'Billing Element' ) . ' #' . $element['ID'] ),
			'CREATED_BY' => DBEscapeString( User( 'NAME' ) ),
		],
		'id'
	);

	if ( ! $fee_id )
	{
		return false;
	}

	// Record the element assignment.
	$link_id = DBInsert(
		'student_billing_elements',
		[
			'SYEAR' => UserSyear(),
			'SCHOOL_ID' => UserSchool(),
			'STUDENT_ID' => (int) $student_id,
			'ELEMENT_ID' => (int) $element_id,
			'FEE_ID' => (int) $fee_id,
			'COMMENT' => $comments ? DBEscapeString( $comments ) : '',
		],
		'id'
	);

	if ( ! $link_id )
	{
		// Rollback: remove the fee to avoid orphan rows.
		DBQuery( "DELETE FROM billing_fees WHERE ID='" . (int) $fee_id . "'" );

		return false;
	}

	// Optional course enrollment (Store purchase).
	if ( $enroll_course
		&& ! empty( $element['COURSE_PERIOD_ID'] ) )
	{
		_be_enroll_course_period( $student_id, $element['COURSE_PERIOD_ID'] );
	}

	return $link_id;
}

/**
 * Remove an Element assignment & its corresponding Fee.
 *
 * @param  int $link_id student_billing_elements.ID.
 *
 * @return bool
 */
function _be_remove_element( $link_id )
{
	$link_RET = DBGet( "SELECT *
		FROM student_billing_elements
		WHERE ID='" . (int) $link_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( empty( $link_RET[1] ) )
	{
		return false;
	}

	$link = $link_RET[1];

	$deleted = DBQuery( "DELETE FROM student_billing_elements
		WHERE ID='" . (int) $link_id . "'" );

	if ( ! $deleted )
	{
		return false;
	}

	// Delete associated fee (unless it has been paid: then delete only the link).
	if ( ! empty( $link['FEE_ID'] ) )
	{
		$paid = DBGetOne( "SELECT 1
			FROM billing_fees
			WHERE ID='" . (int) $link['FEE_ID'] . "'
			AND WAIVED_FEE_ID IS NOT NULL" );

		if ( ! $paid )
		{
			DBQuery( "DELETE FROM billing_fees
				WHERE ID='" . (int) $link['FEE_ID'] . "'" );
		}
	}

	return true;
}

/**
 * Validate a YYYY-MM-DD date.
 *
 * @param  string $date Date.
 *
 * @return string YYYY-MM-DD or ''.
 */
function _be_validate_date( $date )
{
	$date = (string) $date;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) )
	{
		return '';
	}

	$parts = explode( '-', $date );

	if ( ! checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) )
	{
		return '';
	}

	return $date;
}

/**
 * Rollover: copy Categories & Elements (ROLLOVER='Y') to the next school year.
 *
 * Hooked on `School_Setup/Rollover.php|rollover_after`.
 *
 * @return void
 */
function _be_rollover()
{
	$next_syear = UserSyear() + 1;

	// Categories.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM billing_elements_categories
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$category_new_ids = [];

	foreach ( (array) $categories_RET as $category )
	{
		$new_id = DBInsert(
			'billing_elements_categories',
			[
				'SCHOOL_ID' => UserSchool(),
				'SYEAR' => $next_syear,
				'TITLE' => DBEscapeString( $category['TITLE'] ),
				'SORT_ORDER' => (int) $category['SORT_ORDER'],
			],
			'id'
		);

		if ( $new_id )
		{
			$category_new_ids[ $category['ID'] ] = $new_id;
		}
	}

	// Elements.
	$elements_RET = DBGet( "SELECT *
		FROM billing_elements
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND ROLLOVER='Y'" );

	foreach ( (array) $elements_RET as $element )
	{
		$new_category_id = isset( $category_new_ids[ $element['CATEGORY_ID'] ] ) ?
			$category_new_ids[ $element['CATEGORY_ID'] ] :
			0;

		DBInsert(
			'billing_elements',
			[
				'SCHOOL_ID' => UserSchool(),
				'SYEAR' => $next_syear,
				'CATEGORY_ID' => (int) $new_category_id,
				'TITLE' => DBEscapeString( $element['TITLE'] ),
				'AMOUNT' => (float) $element['AMOUNT'],
				'REFERENCE' => $element['REFERENCE'] ? DBEscapeString( $element['REFERENCE'] ) : '',
				'DESCRIPTION' => $element['DESCRIPTION'] ? DBEscapeString( $element['DESCRIPTION'] ) : '',
				'GRADE_LEVELS' => DBEscapeString( $element['GRADE_LEVELS'] ),
				'COURSE_PERIOD_ID' => ! empty( $element['COURSE_PERIOD_ID'] ) ? (int) $element['COURSE_PERIOD_ID'] : null,
				'ROLLOVER' => 'Y',
			]
		);
	}
}
