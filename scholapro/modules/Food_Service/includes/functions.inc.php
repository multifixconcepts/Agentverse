<?php
/**
 * Shared Food_Service utility functions.
 * Included via require_once to avoid redeclaration errors.
 */

/**
 * @param $type
 * @return mixed
 */
function types_locale( $type )
{
	$types = [ 'Deposit' => _( 'Deposit' ), 'Credit' => _( 'Credit' ), 'Debit' => _( 'Debit' ) ];

	if ( array_key_exists( $type, $types ) )
	{
		return $types[$type];
	}

	return $type;
}

/**
 * @param $option
 * @return mixed
 */
function options_locale( $option )
{
	$options = [ 'Cash ' => _( 'Cash' ), 'Check' => _( 'Check' ), 'Credit Card' => _( 'Credit Card' ), 'Debit Card' => _( 'Debit Card' ), 'Transfer' => _( 'Transfer' ) ];

	if ( array_key_exists( $option, $options ) )
	{
		return $options[$option];
	}

	return $option;
}

/**
 * @param $value
 * @return mixed
 */
function red( $value )
{
	if ( $value < 0 )
	{
		return '<span style="color:red">' . $value . '</span>';
	}
	else
	{
		return $value;
	}
}
