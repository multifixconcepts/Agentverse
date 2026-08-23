<?php
/**
 * Billing Elements module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules
 */

// Admin only (demo shows only admin sees this menu; Teacher/Parent do NOT see it).
$menu['Billing_Elements']['admin'] = [
	'title' => _( 'Billing Elements' ),
	'default' => 'Billing_Elements/Elements.php',
	'Billing_Elements/Elements.php' => _( 'Elements' ),
	'Billing_Elements/MonthlyElements.php' => _( 'Monthly Elements' ),
	'Billing_Elements/MassAssignElements.php' => _( 'Mass Assign Elements' ),
	'Billing_Elements/StudentElements.php' => _( 'Student Elements' ),
	1 => _( 'Reports' ),
	'Billing_Elements/CategoryBreakdown.php' => _( 'Category Breakdown' ),
	'Billing_Elements/DailyTransactions.php' => _( 'Daily Transactions' ),
];

$exceptions['Billing_Elements'] = [];