-- Execute ONLY with approval from the shared DB owner, after checking the existing constraint.
-- Needed only if Staff EDU combined annual targets can exceed the original 40-hour cache limit.
-- Do not run before backing up; these are modifications to the shared staff_edu table.
ALTER TABLE staff_edu DROP CHECK chk_staff_edu_1;
ALTER TABLE staff_edu
    ADD CONSTRAINT chk_staff_edu_1 CHECK (credit_hour BETWEEN 0 AND 999.99);
