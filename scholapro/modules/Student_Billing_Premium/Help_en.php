<?php
/**
 * Student Billing Premium module Help texts
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules
 */

function _help( $text, $domain = 'default' )
{
	return $text;
}

$help['Student_Billing_Premium/PaypalConfiguration.php'] = '<p>' . _help( 'The Configuration program sets up the Monnify payment gateway for online fee collection. Enter your Test and Live API keys, secret keys, contract code, currency, invoice and receipt prefixes, legal notice, and webhook URL.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator only. Teachers, students and parents do not have access to this program.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> obtain your Monnify credentials from the Monnify dashboard. Fill in the Test API Key, Test Secret Key, Live API Key, Live Secret Key, and Contract Code. Set the Currency (default NGN), Invoice Prefix, Receipt Prefix, and Legal Notice text. Enter the Webhook URL (your school URL + modules/Student_Billing_Premium/Webhook.php). Click "Test Connection" to verify the credentials. When ready, switch the Mode to Live and Save.', 'Student_Billing_Premium' ) . '</p>';

$help['Student_Billing_Premium/StudentFeesMonthly.php'] = '<p>' . _help( 'The Monthly Fees program manages recurring monthly fee templates. Each template defines an Element, a Due Day (1–28), applicable Grade Levels, and an optional description. The template title uses __MONTH__ as a placeholder for the month name (e.g., "Tuition - __MONTH__").', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator only. Teachers, students and parents do not have access to this program.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> click "Add Template" to create a new monthly fee template. Select the Element, set the Due Day, choose Grade Levels (or "All Grades"), and enter a Description. The Title field supports __MONTH__ which will be replaced with the month name when fees are generated. Use "Auto-assign daily" to automatically create fees each day for new students. Click "Assign" next to a template to run it for a specific month: this opens the Find a Student screen where you can select students and click "Add Fee". The student count link shows how many students match the template\'s grade levels. Use "Delete" to remove a template (already-created fees are kept).', 'Student_Billing_Premium' ) . '</p>';

$help['Student_Billing_Premium/PaymentsImport.php'] = '<p>' . _help( 'The Payments Import program imports payments from CSV or XLSX files. The file must have columns for Student (name or ID), Amount, Date, and optionally Payment Method, Reference, and Comment. Maximum file size is 5MB.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator only. Teachers, students and parents do not have access to this program.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> click "Choose File" and select a CSV or XLSX file. The preview screen shows the first 10 rows with column mapping dropdowns — map each required column (Student, Amount, Date) and optional columns. Click "Import" to process: each row is validated (student must exist in your school, amount must be numeric, date must be valid), and a Payment record is created for each valid row. Errors are shown with row numbers. Formula injection characters (= + - @) in the file are neutralized. Zip bombs and XXE attacks are blocked.', 'Student_Billing_Premium' ) . '</p>';

$help['Student_Billing_Premium/Invoices.php'] = '<p>' . _help( 'The Print Invoices program generates PDF invoices for student fees. You can filter by Student, Grade Level, Date Range, and Fee Status. The invoice includes the school name, student details, fee breakdown, totals, and the legal notice from Configuration.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator, Teacher, and Parent. Students do not have access to this program.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> set the filters (Student, Grade Level, From/To dates, Status) and click "Generate". The PDF opens in a new tab for printing or saving. Use the "All Students" option to batch-generate invoices for a grade level.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Teacher:</b> you can generate invoices for students in your classes using the same filters. The output is identical to the Administrator view.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Parent:</b> you can generate invoices for your own student(s) only. The filters are pre-restricted to your student(s).', 'Student_Billing_Premium' ) . '</p>';

$help['Student_Billing_Premium/Receipts.php'] = '<p>' . _help( 'The Print Receipts program generates PDF receipts for payments. You can filter by Student, Grade Level, Date Range, and Payment Method. Options include: print two copies (original + duplicate), hide the Lunch column, show the Payment Number, and include the legal notice. A direct link parameter print_receipt=Y is available from the Payments list.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator, Teacher, and Parent. Students do not have access to this program.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> set the filters and options, then click "Generate". The PDF includes payment details, student info, and the legal notice. The "Two copies" option prints the receipt twice on one page (original top, duplicate bottom).', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Teacher:</b> you can generate receipts for students in your classes with the same options.', 'Student_Billing_Premium' ) . '</p>

	<p>' . _help( '<b>For the Parent:</b> you can generate receipts for your own student(s) only.', 'Student_Billing_Premium' ) . '</p>';


