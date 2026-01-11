USE u743570205_wishluvhrmsapp;

ALTER TABLE attendance
ADD COLUMN clock_in_lat DECIMAL(10, 8) NULL,
ADD COLUMN clock_in_lng DECIMAL(11, 8) NULL,
ADD COLUMN clock_in_address TEXT NULL,
ADD COLUMN clock_out_lat DECIMAL(10, 8) NULL,
ADD COLUMN clock_out_lng DECIMAL(11, 8) NULL,
ADD COLUMN clock_out_address TEXT NULL;
