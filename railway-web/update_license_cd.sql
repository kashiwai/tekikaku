-- Update license_cd to match Windows client configuration
UPDATE mst_cameralist
SET license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c'
WHERE mac_address = '34-a6-ef-35-73-73';

-- Verify the update
SELECT mac_address, license_cd, license_id
FROM mst_cameralist
WHERE mac_address = '34-a6-ef-35-73-73';
