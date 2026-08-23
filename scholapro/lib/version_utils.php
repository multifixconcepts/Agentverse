<?php
/**
 * Version utilities for ScholaPro.
 *
 * Provides functions to read, parse, and compare RosarioSIS versions.
 *
 * @package ScholaPro
 */

/**
 * Get the RosarioSIS version string from the ROSARIO_VERSION constant.
 *
 * @since 12.9.3
 *
 * @return string The version string (e.g. '12.9.2').
 */
function getRosariosisVersion()
{
	if ( defined( 'ROSARIO_VERSION' ) )
	{
		return ROSARIO_VERSION;
	}

	return '0.0.0';
}

/**
 * Split a version string into its major, minor, and patch components.
 *
 * @since 12.9.3
 *
 * @param string $version Version string in 'X.Y.Z' format.
 *
 * @return array Associative array with keys 'major', 'minor', 'patch'.
 */
function getVersionArray( $version )
{
	$parts = explode( '.', $version );

	return array(
		'major' => isset( $parts[0] ) ? (int) $parts[0] : 0,
		'minor' => isset( $parts[1] ) ? (int) $parts[1] : 0,
		'patch' => isset( $parts[2] ) ? (int) $parts[2] : 0,
	);
}

/**
 * Compare two version strings.
 *
 * @since 12.9.3
 *
 * @param string $v1 First version string.
 * @param string $v2 Second version string.
 *
 * @return int -1 if $v1 < $v2, 0 if equal, 1 if $v1 > $v2.
 */
function compareVersions( $v1, $v2 )
{
	$a1 = getVersionArray( $v1 );
	$a2 = getVersionArray( $v2 );

	if ( $a1['major'] !== $a2['major'] )
	{
		return $a1['major'] < $a2['major'] ? -1 : 1;
	}

	if ( $a1['minor'] !== $a2['minor'] )
	{
		return $a1['minor'] < $a2['minor'] ? -1 : 1;
	}

	if ( $a1['patch'] !== $a2['patch'] )
	{
		return $a1['patch'] < $a2['patch'] ? -1 : 1;
	}

	return 0;
}
