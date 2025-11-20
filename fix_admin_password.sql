-- Fix Admin Password
-- Run this SQL if you've already imported the database and the admin password doesn't work
-- This updates the admin user password to 'admin123'

UPDATE users 
SET password = '$2y$10$I.7DuuAxb7wmu90j/M8cTuugPQfVC2dhJ/2zQ0UKznSborPoqrjOa' 
WHERE username = 'admin';

