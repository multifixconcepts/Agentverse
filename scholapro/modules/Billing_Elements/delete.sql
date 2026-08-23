-- Billing Elements module: delete script
-- Executed on module deletion (Modules.php > School > Configuration > Modules).

DROP TABLE IF EXISTS billing_monthly_elements;
DROP TABLE IF EXISTS student_billing_elements;
DROP TABLE IF EXISTS billing_elements;
DROP TABLE IF EXISTS billing_elements_categories;
