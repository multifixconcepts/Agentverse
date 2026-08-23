-- Student Billing Premium module: delete script
-- Executed on module deletion (Modules.php > School > Configuration > Modules).

DROP TABLE IF EXISTS sbp_webhook_log;
DROP TABLE IF EXISTS billing_monnify_transactions;
DROP TABLE IF EXISTS billing_monthly_fees;
