-- Billing Elements module: PostgreSQL install script
-- Executed on module activation (Modules.php > School > Configuration > Modules).
-- Idempotent: safe to re-run (CREATE TABLE / INDEX IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS billing_elements_categories (
	ID serial PRIMARY KEY,
	SCHOOL_ID integer NOT NULL,
	SYEAR numeric(4,0) NOT NULL,
	TITLE varchar(255) NOT NULL,
	SORT_ORDER integer NOT NULL DEFAULT 0,
	CREATED_AT timestamp NULL DEFAULT current_timestamp,
	UPDATED_AT timestamp NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS billing_elements_categories_school_id_idx ON billing_elements_categories (SCHOOL_ID);
CREATE INDEX IF NOT EXISTS billing_elements_categories_syear_idx ON billing_elements_categories (SYEAR);

CREATE TABLE IF NOT EXISTS billing_elements (
	ID serial PRIMARY KEY,
	SCHOOL_ID integer NOT NULL,
	SYEAR numeric(4,0) NOT NULL,
	CATEGORY_ID integer NOT NULL,
	TITLE varchar(255) NOT NULL,
	AMOUNT numeric(14,2) NOT NULL,
	REFERENCE varchar(50) DEFAULT NULL,
	DESCRIPTION text DEFAULT NULL,
	GRADE_LEVELS varchar(255) DEFAULT NULL,
	COURSE_PERIOD_ID integer DEFAULT NULL,
	ROLLOVER varchar(1) NOT NULL DEFAULT 'Y',
	CREATED_AT timestamp NULL DEFAULT current_timestamp,
	UPDATED_AT timestamp NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS billing_elements_school_id_idx ON billing_elements (SCHOOL_ID);
CREATE INDEX IF NOT EXISTS billing_elements_syear_idx ON billing_elements (SYEAR);
CREATE INDEX IF NOT EXISTS billing_elements_category_id_idx ON billing_elements (CATEGORY_ID);

CREATE TABLE IF NOT EXISTS student_billing_elements (
	ID serial PRIMARY KEY,
	SYEAR numeric(4,0) NOT NULL,
	SCHOOL_ID integer NOT NULL,
	STUDENT_ID integer NOT NULL,
	ELEMENT_ID integer NOT NULL,
	FEE_ID integer DEFAULT NULL,
	COMMENT text DEFAULT NULL,
	CREATED_AT timestamp NULL DEFAULT current_timestamp,
	UPDATED_AT timestamp NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS student_billing_elements_syear_idx ON student_billing_elements (SYEAR);
CREATE INDEX IF NOT EXISTS student_billing_elements_school_id_idx ON student_billing_elements (SCHOOL_ID);
CREATE INDEX IF NOT EXISTS student_billing_elements_student_id_idx ON student_billing_elements (STUDENT_ID);
CREATE INDEX IF NOT EXISTS student_billing_elements_element_id_idx ON student_billing_elements (ELEMENT_ID);
CREATE INDEX IF NOT EXISTS student_billing_elements_fee_id_idx ON student_billing_elements (FEE_ID);

CREATE TABLE IF NOT EXISTS billing_monthly_elements (
	ID serial PRIMARY KEY,
	SCHOOL_ID integer NOT NULL,
	SYEAR numeric(4,0) NOT NULL,
	ELEMENT_ID integer NOT NULL,
	DUE_DAY integer NOT NULL DEFAULT 5,
	GRADE_LEVELS varchar(255) DEFAULT NULL,
	CREATED_AT timestamp NULL DEFAULT current_timestamp,
	UPDATED_AT timestamp NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS billing_monthly_elements_school_id_idx ON billing_monthly_elements (SCHOOL_ID);
CREATE INDEX IF NOT EXISTS billing_monthly_elements_syear_idx ON billing_monthly_elements (SYEAR);
CREATE INDEX IF NOT EXISTS billing_monthly_elements_element_id_idx ON billing_monthly_elements (ELEMENT_ID);