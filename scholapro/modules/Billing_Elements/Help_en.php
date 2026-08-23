<?php
/**
 * Billing Elements module Help texts
 *
 * @since 12.9.2
 * @package ScholaPro
 * @subpackage modules
 */

function _help( $text, $domain = 'default' )
{
	return $text;
}

$help['Billing_Elements/Elements.php'] = '<p>' . _help( 'The Elements program is where you define the Billing Elements (items) that your school offers — tuition, uniforms, textbooks, transport, activities, etc. Each Element belongs to a Category, has a Reference code, a Description and an Amount.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator only. Teachers, students and parents do not have access to this program.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> to create an Element, click the add button of the list, fill in the Category, Reference, Description and Amount, optionally restrict the Element to one or several Grade Levels (leave it on "All Grades" for every student), and click Save.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'To change an Element, edit the fields and Save. To remove an Element, click its remove button (Fees that were already created are kept).', 'Billing_Elements' ) . '</p>';

$help['Billing_Elements/MassAssignElements.php'] = '<p>' . _help( 'The Mass Assign Elements program lets you assign one or more Billing Elements to a group of students in a single operation. You pick the Element(s), choose the students (by grade, by individual selection, or all), set the Assigned Date and Due Date, and run the assignment.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator only. Teachers, students and parents do not have access to this program.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> use the "Find a Student" section to filter by Grade Level or search by name, check the students you want, pick the Element(s) from the dropdown, set the dates, and click "Assign Elements". A Fee is created for each selected student and Element. Running the same assignment again is safe: a Fee is never created twice for the same student, Element and Assigned Date.', 'Billing_Elements' ) . '</p>';

$help['Billing_Elements/StudentElements.php'] = '<p>' . _help( 'The Student Elements program shows the Billing Elements assigned to a specific student. You can add new Elements to that student, remove Elements that have not yet been paid, and see the Fee status for each Element.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator and Teacher. Administrators can add/remove Elements for any student; Teachers can view and add Elements for students in their classes. Students and parents do not have access to this program.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> use "Find a Student" to locate the student, then click "Add Element" to assign a new Element (pick from the dropdown, set dates, Save). To remove an unpaid Element, click its remove button. Paid Elements cannot be removed here.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Teacher:</b> you can view the Elements assigned to students in your classes and add new Elements using the same "Add Element" flow. You cannot remove Elements.', 'Billing_Elements' ) . '</p>';

$help['Billing_Elements/MonthlyElements.php'] = '<p>' . _help( 'The Monthly Elements program manages recurring monthly Fee setups. Each setup links an Element to a Due Day (1–28) and optionally to specific Grade Levels. When you run "Assign Monthly Fees" for a month, one Fee is created per matching student, dated on the 1st with the Due Day as due date.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator only. Teachers, students and parents do not have access to this program.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> to create a setup, click the add button of the list, choose the Element (options show the title and amount), pick the Due Day (the day of the month, between 1 and 28, on which the monthly Fee is dated) and optionally restrict the setup to one or several Grade Levels (leave it on "All Grades" for every student). Click Save.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'To change a setup, edit the Due Day or the Grade Levels and Save. The Element itself cannot be changed once the setup exists: delete the setup and create a new one instead. To remove a setup, click its remove button (Fees that were already created are kept).', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'To run a month, use the "Assign fees for month" section: pick the month, then click "Assign Monthly Fees". One Fee is created per student of the matching grades, dated on the 1st of the month with the Due Day as due date. Running the same month again is safe: a Fee is never created twice for the same student, Element and month.', 'Billing_Elements' ) . '</p>';

$help['Billing_Elements/DailyTransactions.php'] = '<p>' . _help( 'The Daily Transactions program lists the Element Fees and Payments of your school for a specific timeframe, so you can see exactly what was sold, assigned and paid day by day.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator and Teacher. The list is school-wide for both roles. Students and parents do not have access to this program.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> set the From and To dates (they default to the first day of the current month and today), optionally choose a Category to filter the list, then click Go. Each row shows the Student, the Fee (debit), the Payment (credit), the Date and the Comment, and a totals row below the list gives the Total, Fees and Payments for the period.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'You can switch to the expanded view of the list to add the "Created by" and "Created at" columns, which is useful to audit who recorded each transaction. Use a wide date range to see the whole school year, or the Category filter to isolate one Category.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Teacher:</b> you can open the same program and see the school-wide list with the same filters, totals and expanded view. Use it to follow what has been sold and paid; this program is read-only.', 'Billing_Elements' ) . '</p>';

$help['Billing_Elements/CategoryBreakdown.php'] = '<p>' . _help( 'The Category Breakdown report shows how your Billing Elements are distributed per Category, as a Bar chart, a Pie chart or a plain List.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'Who uses it: Administrator only. Teachers, students and parents cannot use this program.', 'Billing_Elements' ) . '</p>

	<p>' . _help( '<b>For the Administrator:</b> choose a Category (a Category must be selected for the report to run), set the Start and End Dates of the period, pick the Chart type (Bar, Pie or List), the Values (Amount or Count of Elements) and check "Breakdown by Grade Level" if you want the results split per grade. Click Go.', 'Billing_Elements' ) . '</p>

	<p>' . _help( 'The Bar and Pie charts display the chart of the period; click "Download" to export it to PDF. The List view shows one row per Element with the Amount (or Count) and, when the grade breakdown is on, a Grade Level column, plus a Total row. Use this report to see which items sell the most per Category and to prepare state reports.', 'Billing_Elements' ) . '</p>';


