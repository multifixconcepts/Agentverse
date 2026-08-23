-- Student Billing Premium module: PostgreSQL install script
-- Executed on module activation (Modules.php > School > Configuration > Modules).
-- Idempotent: safe to re-run (CREATE TABLE / INDEX IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS billing_monthly_fees (
	ID serial PRIMARY KEY,
	SCHOOL_ID integer NOT NULL,
	SYEAR numeric(4,0) NOT NULL,
	TITLE varchar(255) NOT NULL,
	AMOUNT numeric(14,2) NOT NULL,
	DUE_DAY integer NOT NULL DEFAULT 5,
	GRADE_ID integer NOT NULL DEFAULT 0,
	ACTIVE varchar(1) NOT NULL DEFAULT 'Y',
	CREATED_AT timestamp NULL DEFAULT current_timestamp,
	UPDATED_AT timestamp NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS billing_monthly_fees_school_id_idx ON billing_monthly_fees (SCHOOL_ID);
CREATE INDEX IF NOT EXISTS billing_monthly_fees_syear_idx ON billing_monthly_fees (SYEAR);

CREATE TABLE IF NOT EXISTS billing_monnify_transactions (
	ID serial PRIMARY KEY,
	SCHOOL_ID integer NOT NULL,
	SYEAR numeric(4,0) NOT NULL,
	STUDENT_ID integer NOT NULL,
	PAYMENT_REFERENCE varchar(100) NOT NULL,
	TRANSACTION_REFERENCE varchar(100) DEFAULT NULL,
	AMOUNT numeric(14,2) NOT NULL,
	CURRENCY varchar(3) NOT NULL DEFAULT 'NGN',
	STATUS varchar(20) NOT NULL DEFAULT 'PENDING',
	PAYMENT_METHOD varchar(50) DEFAULT NULL,
	PAYMENT_DESCRIPTION text DEFAULT NULL,
	BILLING_PAYMENT_ID integer DEFAULT NULL,
	CREATED_AT timestamp NULL DEFAULT current_timestamp,
	UPDATED_AT timestamp NULL DEFAULT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS billing_monnify_transactions_payment_reference_idx ON billing_monnify_transactions (PAYMENT_REFERENCE);
CREATE INDEX IF NOT EXISTS billing_monnify_transactions_school_id_idx ON billing_monnify_transactions (SCHOOL_ID);
CREATE INDEX IF NOT EXISTS billing_monnify_transactions_syear_idx ON billing_monnify_transactions (SYEAR);
CREATE INDEX IF NOT EXISTS billing_monnify_transactions_student_id_idx ON billing_monnify_transactions (STUDENT_ID);
CREATE INDEX IF NOT EXISTS billing_monnify_transactions_status_idx ON billing_monnify_transactions (STATUS);

CREATE TABLE IF NOT EXISTS sbp_webhook_log (
	ID serial PRIMARY KEY,
	EVENT_TYPE varchar(100) DEFAULT NULL,
	PAYMENT_REFERENCE varchar(100) DEFAULT NULL,
	TRANSACTION_REFERENCE varchar(100) DEFAULT NULL,
	STATUS varchar(50) DEFAULT NULL,
	SOURCE_IP varchar(45) DEFAULT NULL,
	PAYLOAD text DEFAULT NULL,
	CREATED_AT timestamp NULL DEFAULT current_timestamp
);

CREATE INDEX IF NOT EXISTS sbp_webhook_log_payment_reference_idx ON sbp_webhook_log (PAYMENT_REFERENCE);
CREATE INDEX IF NOT EXISTS sbp_webhook_log_status_idx ON sbp_webhook_log (STATUS);
