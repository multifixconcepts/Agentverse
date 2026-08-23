<?php
/**
 * Student Search functions
 *
 * @package ScholaPro
 * @subpackage modules
 */

if ( ! function_exists( 'searchStudents' ) )
{
	/**
	 * Search students by name within a school with advanced filtering and pagination
	 *
	 * @since 12.5 Initial version (v1): searchStudents($school_id, $query)
	 * @since 12.6 Disruptive rewrite (v2): Added $options param, structured return format,
	 *             grade_level filter, status filter, pagination support.
	 *
	 * @deprecated 12.5 The v1 signature searchStudents($school_id, $query) returning a flat array
	 *             is removed. All callers MUST migrate to v2.
	 *
	 * @see StudentSearch.CHANGELOG.md for migration guide.
	 *
	 * @param int    $school_id School ID.
	 * @param string $query     Search query (matched against first_name, last_name LIKE).
	 * @param array  $options {
	 *     Optional search and pagination parameters.
	 *
	 *     @type string $grade_level  Filter by grade level ID. Default empty (no filter).
	 *     @type string $status       Student enrollment status filter.
	 *                                 'active'   — only currently enrolled (END_DATE IS NULL).
	 *                                 'inactive' — only formerly enrolled (END_DATE IS NOT NULL).
	 *                                 'all'      — both active and inactive.
	 *                                 Default 'active'.
	 *     @type int    $limit        Maximum number of results to return. Default 50.
	 *     @type int    $offset       Number of results to skip for pagination. Default 0.
	 * }
	 *
	 * @return array {
	 *     @type array  $students  Matching student records indexed by STUDENT_ID.
	 *                             Each record contains: STUDENT_ID, FIRST_NAME, LAST_NAME,
	 *                             NAME_PREFIX, GRADE_ID, SCHOOL_ID, ENROLLMENT_STATUS.
	 *     @type int    $total     Total number of matching records (before LIMIT/OFFSET).
	 *     @type bool   $has_more  True if more results exist beyond current page.
	 * }
	 */
	function searchStudents( $school_id, $query, $options = [] )
	{
		$school_id = (int) $school_id;

		$query = trim( $query );

		if ( $query === '' )
		{
			return [
				'students' => [],
				'total' => 0,
				'has_more' => false,
			];
		}

		$search_term = '%' . DBEscapeString( mb_strtolower( $query ) ) . '%';

		// Merge defaults.
		$options += [
			'grade_level' => '',
			'status' => 'active',
			'limit' => 50,
			'offset' => 0,
		];

		$limit = max( 1, (int) $options['limit'] );
		$offset = max( 0, (int) $options['offset'] );

		// Validate status.
		$status = in_array( $options['status'], [ 'active', 'inactive', 'all' ], true )
			? $options['status']
			: 'active';

		// --- Build WHERE clauses ---
		$where = [];

		$where[] = "se.SCHOOL_ID='" . $school_id . "'";
		$where[] = "se.SYEAR='" . UserSyear() . "'";
		$where[] = "(LOWER(s.FIRST_NAME) LIKE '" . $search_term . "'
			OR LOWER(s.LAST_NAME) LIKE '" . $search_term . "')";

		// Status filter.
		if ( $status === 'active' )
		{
			$where[] = 'se.END_DATE IS NULL';
		}
		elseif ( $status === 'inactive' )
		{
			$where[] = 'se.END_DATE IS NOT NULL';
		}
		// 'all' — no END_DATE filter.

		// Grade level filter.
		if ( $options['grade_level'] !== '' )
		{
			$grade_level_id = (int) $options['grade_level'];

			if ( $grade_level_id > 0 )
			{
				$where[] = "se.GRADE_ID='" . $grade_level_id . "'";
			}
		}

		$where_sql = implode( "\n			AND ", $where );

		// --- Total count query (before LIMIT/OFFSET) ---
		$total = (int) DBGetOne( "SELECT COUNT(*)
			FROM students s
			JOIN student_enrollment se ON (se.STUDENT_ID = s.STUDENT_ID)
			WHERE " . $where_sql );

		// --- Main result query ---
		$results = DBGet( "SELECT s.STUDENT_ID, s.FIRST_NAME, s.LAST_NAME, s.NAME_PREFIX,
			se.GRADE_ID, se.SCHOOL_ID,
			CASE WHEN se.END_DATE IS NULL THEN 'active' ELSE 'inactive' END AS ENROLLMENT_STATUS
			FROM students s
			JOIN student_enrollment se ON (se.STUDENT_ID = s.STUDENT_ID)
			WHERE " . $where_sql . "
			ORDER BY s.LAST_NAME, s.FIRST_NAME
			LIMIT " . $limit . " OFFSET " . $offset,
			[],
			[ 'STUDENT_ID' ]
		);

		if ( empty( $results ) )
		{
			$results = [];
		}

		// Determine has_more.
		$has_more = ( $offset + $limit ) < $total;

		return [
			'students' => $results,
			'total' => $total,
			'has_more' => $has_more,
		];
	}
}
