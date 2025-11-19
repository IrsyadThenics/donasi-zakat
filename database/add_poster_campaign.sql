-- Add poster column to campaign table (run as a DBA or schema owner)
ALTER TABLE campaign ADD poster VARCHAR2(255);

-- Optionally, update existing rows with default poster values or NULL
COMMIT;
