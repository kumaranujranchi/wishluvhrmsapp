USE u743570205_wishluvhrmsapp;

ALTER TABLE employees
ADD COLUMN shift_start_time TIME DEFAULT '10:00:00',
ADD COLUMN shift_end_time TIME DEFAULT '19:00:00';
