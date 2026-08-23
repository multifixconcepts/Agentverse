<?php
/**
 * Student Billing Premium: Configuration program
 *
 * Monnify (Moniepoint) gateway settings.
 *
 * Secret handling: the API Key and Secret Key are displayed masked and are
 * only updated when a new value is typed (an empty or placeholder value keeps
 * the currently stored secret).
 *
 * @package ScholaPro
 * @subpackage modules/Student_Billing_Premium
 */

require_once 'modules/Student_Billing_Premium/includes/functions.inc.php';
require_once 'modules/Student_Billing_Premium/includes/Monnify.fnc.php';

if ( ! AllowEdit()
	|| User( 'PROFILE' ) !== 'admin' )
{
	DrawHeader( ProgramTitle() );

	echo ErrorMessage( [ _( 'You do not have permission to edit this program.' ) ] );

	return;
}

DrawHeader( ProgramTitle() );

$error = [];

$note = [];

if ( $_REQUEST['modfunc'] === 'save'
	&& is_array( $_REQUEST['values'] ) )
{
	// Save each setting.
	foreach ( (array) $_REQUEST['values'] as $title => $value )
	{
		// Keep the current secret when the field is left blank or unmasked:
		// never overwrite a stored credential with an empty value.
		if ( in_array( $title, [ 'API_KEY', 'SECRET_KEY' ] )
			&& ( $value === ''
				|| $value === '********' ) )
		{
			continue;
		}

		_sbp_save_config( $title, $value );
	}

	$note[] = _( 'Settings saved.' );
}

if ( $_REQUEST['modfunc'] === 'test' )
{
	$token = _sbp_monnify_auth();

	if ( $token )
	{
		$note[] = _( 'Connection to Monnify API successful.' );
	}
	else
	{
		$error[] = _( 'Connection to Monnify API failed. Check your API key, secret key and contract code.' );
	}
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

// Build the settings form.
$enabled = _sbp_config( 'ENABLED' );

$mode = _sbp_config( 'MODE' ) ? _sbp_config( 'MODE' ) : 'test';

$api_key = _sbp_config( 'API_KEY' );

$secret_key = _sbp_config( 'SECRET_KEY' );

$contract_code = _sbp_config( 'CONTRACT_CODE' );

$currency = _sbp_config( 'CURRENCY' ) ? _sbp_config( 'CURRENCY' ) : 'NGN';

$legal_notice = _sbp_config( 'LEGAL_NOTICE' );

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

$table = '<table class="width-100p cellspacing-0"><tr><td style="width:25%;">';

$table .= '<span class="a11y-hidden">' . _( 'Enabled' ) . '</span>';

$table .= '</td><td style="width:75%;">' .
	CheckboxInput(
		$enabled,
		'values[ENABLED]',
		_( 'Enable Online Payments' ),
		'',
		false
	) .
	'</td></tr>';

$table .= '<tr><td>' . _( 'Mode' ) . '</td><td>' .
	SelectInput(
		$mode,
		'values[MODE]',
		_( 'Mode' ),
		[
			'test' => _( 'Test (Sandbox)' ),
			'live' => _( 'Live (Production)' ),
		],
		false,
		'autocomplete="off"',
		false
	) .
	'</td></tr>';

// Masked secret fields: leave blank to keep the current value.
$api_key_display = $api_key ? '********' : '';

$table .= '<tr><td>' . _( 'API Key' ) . '</td><td>' .
	TextInput( $api_key_display, 'values[API_KEY]', _( 'API Key' ), 'size="40" autocomplete="off"', false ) .
	( $api_key ? '<br /><span class="size-1">' . _( 'Leave blank to keep the current API key.' ) . '</span>' : '' ) .
	'</td></tr>';

$secret_key_display = $secret_key ? '********' : '';

$table .= '<tr><td>' . _( 'Secret Key' ) . '</td><td>' .
	TextInput( $secret_key_display, 'values[SECRET_KEY]', _( 'Secret Key' ), 'size="40" autocomplete="off"', false ) .
	( $secret_key ? '<br /><span class="size-1">' . _( 'Leave blank to keep the current secret key.' ) . '</span>' : '' ) .
	'</td></tr>';

$table .= '<tr><td>' . _( 'Contract Code' ) . '</td><td>' .
	TextInput( $contract_code, 'values[CONTRACT_CODE]', _( 'Contract Code' ), 'size="40" autocomplete="off"', false ) .
	'</td></tr>';

$table .= '<tr><td>' . _( 'Currency' ) . '</td><td>' .
	TextInput( $currency, 'values[CURRENCY]', _( 'Currency' ), 'size="10" maxlength="3" autocomplete="off"', false ) .
	'</td></tr>';

$table .= '<tr><td>' . _( 'Legal Notice (Receipts)' ) . '</td><td>' .
	TextInput( $legal_notice, 'values[LEGAL_NOTICE]', _( 'Legal Notice (Receipts)' ), 'size="60" autocomplete="off"', false ) .
	'</td></tr>';

$table .= '</table>';

echo $table;

echo '<div class="center">' . SubmitButton() . '</div>';

echo '</form>';

// Webhook URL info.
$webhook_url = _sbp_webhook_url();

echo '<div style="margin-top:15px;padding:10px;border:1px solid #ccc;border-radius:4px;">' .
	'<b>' . _( 'Webhook URL' ) . ':</b> ' . $webhook_url . '<br />' .
	_( 'Add this URL in your Monnify dashboard (Developers > Webhook URLs) to receive asynchronous payment notifications.' ) .
	'</div>';

// Test connection button (separate form so it does not submit settings).
echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=test' ) . '" method="POST" style="margin-top:10px;">' .
	'<div class="center">' .
	'<input type="submit" value="' . AttrEscape( _( 'Test Connection' ) ) . '" class="button" />' .
	'</div></form>';
