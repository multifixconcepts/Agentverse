<?php
/**
 * Student Billing Premium module Menu entries
 *
 * Adds the Premium programs to the existing Student Billing menu,
 * grouped under a "Student Billing" section (numeric key).
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules
 */

// Admin: full access to the Premium programs.
$menu['Student_Billing']['admin'] = issetVal( $menu['Student_Billing']['admin'], [] ) + [
	2 => _( 'Student Billing' ),
	'Student_Billing_Premium/StudentFeesMonthly.php' => _( 'Monthly Fees' ),
	'Student_Billing_Premium/Invoices.php' => _( 'Print Invoices' ),
	'Student_Billing_Premium/Receipts.php' => _( 'Print Receipts' ),
	'Student_Billing_Premium/PaymentsImport.php' => _( 'Payments Import' ),
	'Student_Billing_Premium/PaypalConfiguration.php' => _( 'Configuration' ),
];

// Teacher: Print Invoices & Print Receipts.
$menu['Student_Billing']['teacher'] = issetVal( $menu['Student_Billing']['teacher'], [] ) + [
	2 => _( 'Student Billing' ),
	'Student_Billing_Premium/Invoices.php' => _( 'Print Invoices' ),
	'Student_Billing_Premium/Receipts.php' => _( 'Print Receipts' ),
];

// Parent (and Student, forced to parent profile in Menu.php): Print Invoices & Print Receipts.
$menu['Student_Billing']['parent'] = issetVal( $menu['Student_Billing']['parent'], [] ) + [
	2 => _( 'Student Billing' ),
	'Student_Billing_Premium/Invoices.php' => _( 'Print Invoices' ),
	'Student_Billing_Premium/Receipts.php' => _( 'Print Receipts' ),
];

$exceptions['Student_Billing'] = [];