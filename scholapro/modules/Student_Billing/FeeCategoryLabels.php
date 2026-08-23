<?php
/**
 * Fee Category Labels — manage fee category display labels
 *
 * Original requirement: simple label management for fee categories.
 * Requirement change mid-task: also manage category colors.
 *
 * @package ScholaPro
 * @subpackage Student_Billing
 */

require_once 'ProgramFunctions/SoftwareWiki.fnc.php';

if ( AllowEdit() && $_REQUEST['modfunc'] === 'save' )
{
	// Save fee category labels.
	foreach ( (array) $_REQUEST['categories'] as $category_id => $data )
	{
		$category_id = (int) $category_id;

		// Original requirement: save label.
		$columns = [
			'LABEL' => DBEscapeString( issetVal( $data['LABEL'], '' ) ),
		];

		// Requirement change: also save color.
		if ( isset( $data['COLOR'] ) )
		{
			$columns['COLOR'] = DBEscapeString( issetVal( $data['COLOR'], '#000000' ) );
		}

		DBUpdate(
			'billing_fees',
			$columns,
			[ 'FEE_ID' => $category_id ],
			[ 'ID' ]
		);
	}

	echo '<div class="center">' . _( 'Saved.' ) . '</div>';
}

// Fetch existing fee categories.
$fees_RET = DBGet( "SELECT ID, TITLE, DESCRIPTION, COLOR
	FROM billing_fees
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'
	ORDER BY TITLE" );

if ( ! empty( $fees_RET ) )
{
	echo '<form method="post">';
	echo '<table class="billing-summary" width="100%">';
	echo '<thead><tr>';
	echo '<th>' . _( 'Fee Category' ) . '</th>';
	echo '<th>' . _( 'Label' ) . '</th>';
	echo '<th>' . _( 'Color' ) . '</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	foreach ( $fees_RET as $fee )
	{
		echo '<tr>';
		echo '<td>' . htmlspecialchars( $fee['TITLE'] ) . '</td>';
		echo '<td><input type="text" name="categories[' . $fee['ID'] . '][LABEL]" value="' . htmlspecialchars( $fee['DESCRIPTION'] ) . '" size="30" /></td>';
		echo '<td><input type="color" name="categories[' . $fee['ID'] . '][COLOR]" value="' . htmlspecialchars( issetVal( $fee['COLOR'], '#000000' ) ) . '" /></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}
else
{
	echo '<p>' . _( 'No fee categories found.' ) . '</p>';
}
