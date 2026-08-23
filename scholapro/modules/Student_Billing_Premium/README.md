# Student Billing Premium

Premium companion to the **Student Billing** module: online payments (Monnify / Moniepoint), recurring monthly fees, bulk payments import and printable invoices & receipts.

## Features

- **Configuration**: enable Online Payments, set the Monnify test/live API Key, Secret Key, Contract Code, Currency, Invoice & Receipt number prefixes and the legal notice printed on receipts. Displays the Monnify Webhook URL and a "Test Connection" button.
- **Pay Balance with Monnify**: Students & Parents see a "Pay Balance" button on their Student Payments screen. Payment is initiated on the Monnify hosted checkout and verified server-side (redirect callback + asynchronous Webhook). Transactions are credited exactly once (atomic PENDING -> PAID claim).
- **Monthly Fees**: define recurring monthly fee templates (Title, Amount, Due Day, Grade Level, Active) and assign them to students for a given month.
- **Payments Import**: bulk import payments from a CSV or XLSX file with a preview step and per-row validation.
- **Print Invoices**: generate PDF invoices for selected students (per student or by grade level search).
- **Print Receipts**: generate PDF payment receipts (with two-copy, lunch payment column, payment number and legal notice options).

## Install

Requires **RosarioSIS 9.2.1+** and the core **Student Billing** module.

Copy the `Student_Billing_Premium/` folder inside the `modules/` folder of RosarioSIS.

Or go to *School > Configuration > Modules* and upload the `zip` file of the module.

Then, go to *School > Configuration > Modules* and click "Activate".

The module tables are created automatically on activation (`install_mysql.sql` for MySQL/MariaDB, `install.sql` for PostgreSQL). Deletion runs `delete.sql`.

## Webhook

Add the Webhook URL shown in *Student Billing Premium > Configuration* to your Monnify dashboard (*Developers > Webhook URLs*). In **live** mode the webhook signature is verified (HMAC-SHA512 using the client Secret Key).

## Configuration

After activation, open *Student Billing Premium > Configuration* to enable Online Payments and enter your Monnify credentials (test/sandbox or live).
