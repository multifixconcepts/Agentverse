# Billing Elements

Companion to the **Student Billing** module. Define, categorize, sell, track and generate reports (charts) for billing elements / items. Billing Elements are typically books, courses, school trips or any item you sell to students. A **Fee** is automatically created every time you assign an Element to a (group of) student. Optionally, each Element can be restricted to one or various Grade Levels, and associated to one Course Period.

## Features

- **Elements**: create Billing Elements and organize them into Categories. Set the Element Title, Amount, Reference, Description, Grade Level restriction and Course Period. Uncheck the Rollover checkbox to prevent the Element from being rolled to the next school year.
- **Mass Assign Elements**: assign an Element and the corresponding Fee to various students at once, or semi automatically by Grade Level.
- **Student Elements**: consult, assign or remove Billing Elements and their corresponding Fee for a single student.
- **Store** (Students & Parents): browse the catalog and purchase an Element. The corresponding Fee is created and, when associated to a Course Period, the student is automatically enrolled.
- **My Elements** (Students & Parents): consult the purchased or assigned Elements and their Fees.
- **Monthly Elements**: set up monthly fees to be automatically assigned to students (recurring fees or installments).
- **Daily Transactions**: list Element Fees & Payments for a specific timeframe, filterable by Category.
- **Category Breakdown**: Bar / Pie / List report of Elements per Category, with timeframe, Amount or Count and Breakdown by Grade Level options.

## Install

Requires **RosarioSIS 9.2.1+**.

Copy the `Billing_Elements/` folder inside the `modules/` folder of RosarioSIS.

Or go to *School > Configuration > Modules* and upload the `zip` file of the module.

Then, go to *School > Configuration > Modules* and click "Activate".

The module tables are created automatically on activation (`install_mysql.sql` for MySQL/MariaDB, `install.sql` for PostgreSQL). Deletion runs `delete.sql`.

## Notes

- Only Elements not assigned to students can be deleted.
- Elements are automatically rolled to the next school year. Uncheck the Rollover checkbox if you do not wish to roll an Element.
- *Category Breakdown* charts display at most 25 elements per Category. Please create enough Categories to fully benefit from the report.
- Students are **not** enrolled in the Course when the Element is assigned by an administrator.
- The "Purchase" button is hidden from Students and Parents if you remove them access to the *Store* / *My Elements* programs.
