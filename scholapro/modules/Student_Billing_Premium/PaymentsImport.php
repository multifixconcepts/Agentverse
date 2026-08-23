<?php
/**
 * Student Billing Premium: Payments Import program
 *
 * Import payments from a CSV or XLSX file into billing_payments.
 *
 * Accepted columns:
 *   student (username or student ID), amount, date, comment, lunch
 *
 * @since 12.9.2
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

// Step 1: upload & preview.
if ( $_REQUEST['modfunc'] === 'upload' )
{
	if ( empty( $_FILES['file']['tmp_name'] ) )
	{
		$error[] = _( 'Please select a file to upload.' );
	}
	else
	{
		$file = $_FILES['file'];

		$extension = mb_strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $extension, [ 'csv', 'xlsx' ] ) )
		{
			$error[] = _( 'Only CSV and XLSX files are supported.' );
		}
		else
		{
			$rows = $extension === 'csv' ?
				_sbp_import_parse_csv( $file['tmp_name'] ) :
				_sbp_import_parse_xlsx( $file['tmp_name'] );

			if ( ! $rows )
			{
				$error[] = _( 'Could not read the file. Check the format.' );
			}
			else
			{
				// Store rows in session for the import step.
				$_SESSION['sbp_import_rows'] = $rows;

				$import_errors = [];

				$valid_rows = _sbp_import_validate_rows( $rows, $import_errors );

				$note[] = sprintf(
					_( '%d row(s) parsed, %d valid, %d with errors.' ),
					count( $rows ),
					count( $valid_rows ),
					count( $import_errors )
				);

				if ( $import_errors )
				{
					$error[] = _( 'Fix the following rows before importing:' );

					$error = array_merge( $error, array_slice( $import_errors, 0, 20 ) );
				}

				if ( ! $valid_rows )
				{
					unset( $_SESSION['sbp_import_rows'] );

					echo ErrorMessage( $error );

					echo ErrorMessage( $note, 'note' );
				}
				else
				{
					echo ErrorMessage( $error );

					echo ErrorMessage( $note, 'note' );

					// Preview table (first 20 rows).
					echo '<h3>' . _( 'Preview' ) . '</h3>';

					echo '<table class="width-100p cellspacing-0"><tr class="st">
						<th>' . _( 'Student' ) . '</th>
						<th>' . _( 'Amount' ) . '</th>
						<th>' . _( 'Date' ) . '</th>
						<th>' . _( 'Comment' ) . '</th>
						<th>' . _( 'Lunch Payment' ) . '</th>
						</tr>';

					foreach ( array_slice( $valid_rows, 0, 20 ) as $row )
					{
						echo '<tr>
							<td>' . htmlspecialchars( $row['student_display'], ENT_QUOTES ) . '</td>
							<td>' . Currency( $row['amount'] ) . '</td>
							<td>' . ProperDate( $row['date'] ) . '</td>
							<td>' . htmlspecialchars( $row['comment'], ENT_QUOTES ) . '</td>
							<td>' . ( $row['lunch'] === 'Y' ? _( 'Yes' ) : '' ) . '</td>
							</tr>';
					}

					echo '</table>';

					echo '<div class="center" style="margin-top:15px;">
						<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=import' ) . '" method="POST" style="display:inline;">
						<input type="submit" value="' . AttrEscape( _( 'Import Payments' ) ) . '" class="button-primary" />
						</form>
						<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=cancel' ) . '" method="POST" style="display:inline;">
						<input type="submit" value="' . AttrEscape( _( 'Cancel' ) ) . '" class="button" />
						</form>
						</div>';
				}
			}
		}
	}
}

// Step 2: import validated rows.
if ( $_REQUEST['modfunc'] === 'import' )
{
	if ( empty( $_SESSION['sbp_import_rows'] ) )
	{
		$error[] = _( 'No pending import found. Please upload the file again.' );
	}
	else
	{
		$rows = $_SESSION['sbp_import_rows'];

		$import_errors = [];

		$valid_rows = _sbp_import_validate_rows( $rows, $import_errors );

		$imported = 0;

		foreach ( $valid_rows as $row )
		{
			$payment_id = _sbp_add_payment(
				$row['student_id'],
				$row['amount'],
				$row['comment'] ? $row['comment'] : _( 'Imported payment' ),
				$row['date'],
				UserSchool(),
				UserSyear(),
				$row['lunch']
			);

			if ( $payment_id )
			{
				$imported++;
			}
		}

		unset( $_SESSION['sbp_import_rows'] );

		$note[] = sprintf( _( '%d payment(s) imported.' ), $imported );

		if ( $import_errors )
		{
			$error[] = _( 'Some rows were skipped:' );

			$error = array_merge( $error, array_slice( $import_errors, 0, 20 ) );
		}
	}

	RedirectURL( [ 'modfunc' ] );
}

// Cancel import.
if ( $_REQUEST['modfunc'] === 'cancel' )
{
	unset( $_SESSION['sbp_import_rows'] );

	$note[] = _( 'Import cancelled.' );

	RedirectURL( [ 'modfunc' ] );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

// Upload form.
if ( empty( $_SESSION['sbp_import_rows'] )
	&& $_REQUEST['modfunc'] !== 'import' )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=upload' ) . '" method="POST" enctype="multipart/form-data">
		<table class="width-100p cellspacing-0"><tr>
		<td>' . _( 'CSV or XLSX file' ) . '</td>
		<td><input type="file" name="file" accept=".csv,.xlsx" required /></td>
		<td>' . SubmitButton( _( 'Upload & Preview' ) ) . '</td>
		</tr></table>
		</form>';

	echo '<p class="size-1">' . _( 'Accepted columns: student (username or ID), amount, date, comment, lunch (Y/N).' ) . '</p>';
}

/**
 * Parse a CSV file into rows (associative arrays by normalized header).
 *
 * @param  string $path File path.
 *
 * @return array Rows.
 */
function _sbp_import_parse_csv( $path )
{
	$handle = fopen( $path, 'r' );

	if ( ! $handle )
	{
		return [];
	}

	// Detect delimiter: comma, semicolon or tab.
	$first_line = fgets( $handle );

	rewind( $handle );

	$delimiter = ',';

	if ( mb_substr_count( $first_line, ';' ) > mb_substr_count( $first_line, ',' ) )
	{
		$delimiter = ';';
	}
	elseif ( mb_substr_count( $first_line, "\t" ) > mb_substr_count( $first_line, ',' ) )
	{
		$delimiter = "\t";
	}

	$rows = [];

	$headers = null;

	while ( ( $data = fgetcsv( $handle, 0, $delimiter ) ) !== false )
	{
		// Skip empty lines.
		if ( count( $data ) === 1
			&& trim( $data[0] ) === '' )
		{
			continue;
		}

		if ( $headers === null )
		{
			$headers = array_map( '_sbp_import_normalize_header', $data );

			continue;
		}

		$row = [];

		foreach ( $headers as $i => $header )
		{
			if ( $header !== '' )
			{
				$row[ $header ] = issetVal( $data[ $i ], '' );
			}
		}

		$rows[] = $row;
	}

	fclose( $handle );

	return $rows;
}

/**
 * Parse an XLSX file into rows (associative arrays by normalized header).
 *
 * @param  string $path File path.
 *
 * @return array Rows.
 */
function _sbp_import_parse_xlsx( $path )
{
	$zip = new ZipArchive();

	if ( $zip->open( $path ) !== true )
	{
		return [];
	}

	// Shared strings.
	$shared_strings = [];

	if ( ( $shared_xml = $zip->getFromName( 'xl/sharedStrings.xml' ) ) !== false )
	{
		$xml = simplexml_load_string( $shared_xml );

		foreach ( $xml->si as $si )
		{
			// Concatenate all <t> text runs (see note below).
			$text = '';

			foreach ( $si->t as $t )
			{
				$text .= (string) $t;
			}

			$shared_strings[] = trim( $text );
		}
	}

	// First worksheet.
	$sheet_xml = $zip->getFromName( 'xl/worksheets/sheet1.xml' );

	$zip->close();

	if ( $sheet_xml === false )
	{
		return [];
	}

	$xml = simplexml_load_string( $sheet_xml );

	if ( ! $xml )
	{
		return [];
	}

	// Build rows: row number => [ col letter => value ].
	$sheet_rows = [];

	$ns = $xml->getNamespaces( true );

	$xml->registerXPathNamespace( 'x', $ns[''] );

	$cells = $xml->xpath( '//x:sheetData/x:row/x:c' );

	foreach ( $cells as $cell )
	{
		// xpath() needs the namespace prefix registered on each element.
		$cell->registerXPathNamespace( 'x', $ns[''] );

		$ref = (string) $cell['r']; // e.g. "A1".

		if ( ! preg_match( '/^([A-Z]+)(\d+)$/', $ref, $m ) )
		{
			continue;
		}

		$col = $m[1];

		$row_num = (int) $m[2];

		$type = (string) $cell['t'];

		$value = '';

		$v = $cell->xpath( 'x:v' );

		$is = $cell->xpath( 'x:is' );

		if ( $type === 's' && ! empty( $v[0] ) )
		{
			$value = issetVal( $shared_strings[ (int) $v[0] ], '' );
		}
		elseif ( $type === 'inlineStr' && ! empty( $is[0] ) )
		{
			// Concatenate all <t> text runs (SimpleXML casts element to
			// its direct text only, which is empty for <is><t>...</t></is>).
			foreach ( $is[0]->t as $t )
			{
				$value .= (string) $t;
			}

			$value = trim( $value );
		}
		elseif ( ! empty( $v[0] ) )
		{
			$value = trim( (string) $v[0] );
		}

		$sheet_rows[ $row_num ][ $col ] = $value;
	}

	// Convert to header-keyed rows.
	$rows = [];

	$headers = [];

	foreach ( $sheet_rows as $row_num => $row )
	{
		if ( ! $headers )
		{
			foreach ( $row as $col => $value )
			{
				$headers[ $col ] = _sbp_import_normalize_header( $value );
			}

			continue;
		}

		$out_row = [];

		foreach ( $headers as $col => $header )
		{
			if ( $header !== '' )
			{
				$out_row[ $header ] = issetVal( $row[ $col ], '' );
			}
		}

		$rows[] = $out_row;
	}

	return $rows;
}

/**
 * Normalize a header name: lowercase, no spaces/accents.
 *
 * @param  string $header Header.
 *
 * @return string Normalized header.
 */
function _sbp_import_normalize_header( $header )
{
	$header = mb_strtolower( trim( (string) $header ) );

	// Remove non-alphanumeric.
	$header = preg_replace( '/[^a-z0-9]/', '', $header );

	return $header;
}

/**
 * Validate & normalize imported rows.
 * Returns rows with keys: student_id, student_display, amount, date, comment, lunch.
 * Errors are appended to $errors.
 *
 * @param  array $rows   Parsed rows.
 * @param  array &$errors Error messages (by ref).
 *
 * @return array Valid rows.
 */
function _sbp_import_validate_rows( $rows, &$errors )
{
	$valid = [];

	foreach ( (array) $rows as $i => $row )
	{
		$line = $i + 2; // Header is line 1.

		$student_key = issetVal( $row['studentid'], issetVal( $row['id'], issetVal( $row['username'], issetVal( $row['student'], '' ) ) ) );

		$amount = issetVal( $row['amount'], '' );

		$date = issetVal( $row['date'], issetVal( $row['paymentdate'], DBDate() ) );

		$comment = issetVal( $row['comment'], issetVal( $row['comments'], issetVal( $row['description'], '' ) ) );

		$lunch_raw = mb_strtoupper( issetVal( $row['lunch'], issetVal( $row['lunchpayment'], '' ) ) );

		$lunch = in_array( $lunch_raw, [ 'Y', 'YES', '1', 'TRUE' ] ) ? 'Y' : '';

		if ( ! $student_key )
		{
			$errors[] = sprintf( _( 'Row %d: missing student.' ), $line );

			continue;
		}

		// Find student by username, then ID.
		$student_RET = DBGet( "SELECT STUDENT_ID,USERNAME,FIRST_NAME,LAST_NAME
			FROM students
			WHERE USERNAME='" . DBEscapeString( $student_key ) . "'
			OR STUDENT_ID='" . (int) $student_key . "'
			LIMIT 1" );

		if ( empty( $student_RET[1] ) )
		{
			$errors[] = sprintf( _( 'Row %d: student not found (%s).' ), $line, $student_key );

			continue;
		}

		if ( ! is_numeric( $amount ) || $amount <= 0 )
		{
			$errors[] = sprintf( _( 'Row %d: invalid amount (%s).' ), $line, $amount );

			continue;
		}

		$date_sql = _sbp_import_parse_date( $date );

		if ( ! $date_sql )
		{
			$errors[] = sprintf( _( 'Row %d: invalid date (%s).' ), $line, $date );

			continue;
		}

		$valid[] = [
			'student_id' => (int) $student_RET[1]['STUDENT_ID'],
			'student_display' => $student_RET[1]['FIRST_NAME'] . ' ' . $student_RET[1]['LAST_NAME'] .
				( $student_RET[1]['USERNAME'] ? ' (' . $student_RET[1]['USERNAME'] . ')' : '' ),
			'amount' => (float) $amount,
			'date' => $date_sql,
			'comment' => $comment,
			'lunch' => $lunch,
		];
	}

	return $valid;
}

/**
 * Parse a date into YYYY-MM-DD SQL format.
 * Supports Excel serial dates and common text formats.
 *
 * @param  string|int $value Date value.
 *
 * @return string|false YYYY-MM-DD or false.
 */
function _sbp_import_parse_date( $value )
{
	if ( is_numeric( $value )
		&& (float) $value > 20000 )
	{
		// Excel serial date (days since 1899-12-30).
		$timestamp = ( (float) $value - 25569 ) * 86400;

		return date( 'Y-m-d', (int) $timestamp );
	}

	$value = trim( (string) $value );

	if ( $value === '' )
	{
		return false;
	}

	// Try DD/MM/YYYY (common in Nigeria) first, then MM/DD/YYYY.
	if ( preg_match( '/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})$/', $value, $m ) )
	{
		$d = (int) $m[1];
		$mo = (int) $m[2];
		$y = (int) $m[3];

		if ( $y < 100 )
		{
			$y += 2000;
		}

		if ( checkdate( $mo, $d, $y ) )
		{
			return sprintf( '%04d-%02d-%02d', $y, $mo, $d );
		}

		if ( checkdate( $d, $mo, $y ) )
		{
			return sprintf( '%04d-%02d-%02d', $y, $d, $mo );
		}

		return false;
	}

	$timestamp = strtotime( $value );

	if ( $timestamp === false )
	{
		return false;
	}

	return date( 'Y-m-d', $timestamp );
}
