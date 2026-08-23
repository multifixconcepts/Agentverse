<?php
/**
 * Student Billing Premium module helper functions
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules/Student_Billing_Premium
 */

/**
 * Get module configuration value(s)
 * Stored in the program_config table (per school & school year).
 *
 * @example _sbp_config( 'ENABLED' )
 * @example _sbp_config() // All values as array
 *
 * @param  string $title Config title. Defaults to 'all'.
 *
 * @return string|array  Config value, or array of all values.
 */
function _sbp_config( $title = 'all' )
{
	return ProgramConfig( 'student_billing_premium', $title );
}

/**
 * Validate a YYYY-MM-DD date before it is used inside an SQL string.
 *
 * Prevents SQL injection via date range filters. Returns '' for anything
 * that is not a plain calendar date.
 *
 * @param  string $date Raw input (e.g. from a request).
 *
 * @return string YYYY-MM-DD date, or '' when invalid.
 */
function _sbp_validate_date( $date )
{
	$date = (string) $date;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) )
	{
		return '';
	}

	// checkdate() guard against impossible dates (e.g. 2026-02-31).
	$parts = explode( '-', $date );

	if ( ! checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) )
	{
		return '';
	}

	return $date;
}

/**
 * Save module configuration value.
 *
 * @param string $title Config title.
 * @param string $value Config value.
 *
 * @return void
 */
function _sbp_save_config( $title, $value )
{
	ProgramConfig( 'student_billing_premium', $title, $value );
}

/**
 * Is the gateway enabled and configured?
 *
 * @return bool True if enabled and API keys set.
 */
function _sbp_is_configured()
{
	if ( _sbp_config( 'ENABLED' ) !== 'Y' )
	{
		return false;
	}

	return _sbp_config( 'API_KEY' )
		&& _sbp_config( 'SECRET_KEY' )
		&& _sbp_config( 'CONTRACT_CODE' );
}

/**
 * Compute Student Billing balance for a student
 * (Total from Fees - Total from Payments).
 *
 * @param  int $student_id Student ID.
 *
 * @return float Balance.
 */
function _sbp_balance( $student_id )
{
	$fees_total = DBGetOne( "SELECT SUM(AMOUNT) AS TOTAL
		FROM billing_fees
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	$payments_total = DBGetOne( "SELECT SUM(AMOUNT) AS TOTAL
		FROM billing_payments
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	return (float) $fees_total - (float) $payments_total;
}

/**
 * Get Student name & email for the Monnify customer fields.
 *
 * @param  int $student_id Student ID.
 *
 * @return array [ name, email ]
 */
function _sbp_student_name_email( $student_id )
{
	global $RosarioNotifyAddress;

	$student_RET = DBGet( "SELECT FIRST_NAME,LAST_NAME,USERNAME
		FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	if ( empty( $student_RET[1] ) )
	{
		return [ '', $RosarioNotifyAddress ];
	}

	$name = trim( $student_RET[1]['FIRST_NAME'] . ' ' . $student_RET[1]['LAST_NAME'] );

	$username = $student_RET[1]['USERNAME'];

	$email = mb_strpos( $username, '@' ) !== false ?
		$username :
		$RosarioNotifyAddress;

	return [ $name, $email ];
}

/**
 * Full URL to the Monnify Webhook endpoint.
 *
 * @return string Webhook URL.
 */
function _sbp_webhook_url()
{
	return RosarioURL( 'script' ) !== ''
		? str_replace( 'Modules.php', 'modules/Student_Billing_Premium/Webhook.php', RosarioURL( 'script' ) )
		: '';
}

/**
 * Generate a unique Monnify payment reference.
 *
 * @param  int $student_id Student ID.
 *
 * @return string Unique payment reference.
 */
function _sbp_payment_reference( $student_id )
{
	return 'SBP' . UserSchool() . UserSyear() . UserStudentID() . date( 'YmdHis' ) . rand( 100, 999 );
}

/**
 * Insert a pending Monnify transaction row.
 *
 * @param array $data Transaction columns (values escaped).
 *
 * @return bool|int True or last inserted ID.
 */
function _sbp_insert_transaction( $data )
{
	return DBInsert( 'billing_monnify_transactions', $data, 'id' );
}

/**
 * Update a Monnify transaction row.
 *
 * @param string $payment_reference Payment reference.
 * @param array  $data              Columns to update (values escaped).
 *
 * @return bool
 */
function _sbp_update_transaction( $payment_reference, $data )
{
	return DBUpdate(
		'billing_monnify_transactions',
		$data,
		[ 'PAYMENT_REFERENCE' => $payment_reference ]
	);
}

/**
 * Number of rows affected by the last query.
 *
 * @return int
 */
function _sbp_db_affected_rows()
{
	global $db_connection,
		$DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return (int) mysqli_affected_rows( $db_connection );
	}

	return (int) pg_affected_rows( $db_connection );
}

/**
 * Atomically claim a PENDING Monnify transaction.
 *
 * Race-condition safe: only the first caller (webhook or callback) can move
 * the row from PENDING to PAID, preventing double credit.
 *
 * @param  string $payment_reference Payment reference.
 * @param  array  $data              Additional columns to set (values raw, escaped here).
 *                                   Allowed: TRANSACTION_REFERENCE, PAYMENT_METHOD, CURRENCY.
 *
 * @return bool True if the transaction was claimed (PENDING -> PAID).
 */
function _sbp_claim_transaction( $payment_reference, $data = [] )
{
	$allowed_columns = [
		'TRANSACTION_REFERENCE',
		'PAYMENT_METHOD',
		'CURRENCY',
	];

	$sets = [];

	foreach ( (array) $data as $column => $value )
	{
		if ( in_array( $column, $allowed_columns ) )
		{
			$sets[] = $column . "='" . DBEscapeString( $value ) . "'";
		}
	}

	$sets[] = "STATUS='PAID'";

	$sets[] = "UPDATED_AT=NOW()";

	$sql = "UPDATE billing_monnify_transactions
		SET " . implode( ', ', $sets ) . "
		WHERE PAYMENT_REFERENCE='" . DBEscapeString( $payment_reference ) . "'
		AND STATUS='PENDING'";

	DBQuery( $sql );

	// True only if exactly one PENDING row was updated (we claimed it).
	return _sbp_db_affected_rows() === 1;
}

/**
 * Get a Monnify transaction row by payment reference.
 *
 * @param  string $payment_reference Payment reference.
 *
 * @return array DBGet result.
 */
function _sbp_get_transaction( $payment_reference )
{
	return DBGet( "SELECT *
		FROM billing_monnify_transactions
		WHERE PAYMENT_REFERENCE='" . DBEscapeString( $payment_reference ) . "'" );
}

/**
 * Add a payment to the billing_payments table.
 *
 * @param  int    $student_id   Student ID.
 * @param  float  $amount       Payment amount (positive).
 * @param  string $comments     Comments (raw, will be escaped).
 * @param  string $payment_date Payment date (YYYY-MM-DD). Defaults to today.
 * @param  int    $school_id    School ID. Defaults to UserSchool().
 * @param  int    $syear        School year. Defaults to UserSyear().
 * @param  string $lunch        Lunch payment flag (Y|''). Defaults to ''.
 *
 * @return bool|int True or last inserted ID.
 */
function _sbp_add_payment( $student_id, $amount, $comments, $payment_date = '', $school_id = 0, $syear = 0, $lunch = '' )
{
	$columns = [
		'SYEAR' => $syear ? $syear : UserSyear(),
		'SCHOOL_ID' => $school_id ? $school_id : UserSchool(),
		'STUDENT_ID' => (int) $student_id,
		'AMOUNT' => (float) $amount,
		'PAYMENT_DATE' => $payment_date ? $payment_date : DBDate(),
		'COMMENTS' => DBEscapeString( $comments ),
		'CREATED_BY' => DBEscapeString( _( 'Online payment (Monnify)' ) ),
	];

	if ( $lunch === 'Y' )
	{
		$columns['LUNCH_PAYMENT'] = 'Y';
	}

	return DBInsert(
		'billing_payments',
		$columns,
		'id'
	);
}

/**
 * Get the next invoice/receipt number (per school & school year),
 * incrementing the stored counter.
 *
 * @param  string $prefix Counter title: INVOICE_NUMBER|RECEIPT_NUMBER.
 *
 * @return int Next number.
 */
function _sbp_next_number( $prefix )
{
	$current = (int) _sbp_config( $prefix );

	$next = $current + 1;

	_sbp_save_config( $prefix, $next );

	return $next;
}

/**
 * Invoice / Receipt header HTML (school info + document title).
 *
 * @param string $title Document title (Invoice / Receipt).
 * @param string $number Document number.
 *
 * @return string HTML.
 */
function _sbp_document_header( $title, $number )
{
	$school_title = ParseMLField( SchoolInfo( 'TITLE' ) );

	$school_address = trim(
		SchoolInfo( 'ADDRESS' ) . ' ' .
		SchoolInfo( 'CITY' ) . ' ' .
		SchoolInfo( 'STATE' ) . ' ' .
		SchoolInfo( 'ZIPCODE' )
	);

	$school_phone = SchoolInfo( 'PHONE' );

	$html = '<table style="width:100%;"><tr>
		<td style="text-align:left;">';

	$html .= '<h2 style="margin:0;">' . $school_title . '</h2>';

	if ( $school_address )
	{
		$html .= '<div>' . $school_address . '</div>';
	}

	if ( $school_phone )
	{
		$html .= '<div>' . _( 'Phone' ) . ': ' . $school_phone . '</div>';
	}

	$html .= '</td>
		<td style="text-align:right;">';

	$html .= '<h2 style="margin:0;">' . $title . '</h2>';

	if ( $number )
	{
		$html .= '<div>' . _( 'No.' ) . ': ' . $number . '</div>';
	}

	$html .= '<div>' . ProperDate( DBDate() ) . '</div>';

	$html .= '</td>
		</tr></table><hr />';

	return $html;
}

/**
 * Legal notice displayed on receipts (configurable in Configuration program).
 *
 * @return string HTML legal notice.
 */
function _sbp_legal_notice()
{
	$notice = _sbp_config( 'LEGAL_NOTICE' );

	return $notice ? '<p style="margin-top:20px;font-size:0.85em;">' . nl2br( $notice ) . '</p>' : '';
}
