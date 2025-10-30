-- MACアドレス登録SQL
-- Windows PC slotserver.exeとkeysocket.exe用

INSERT INTO mst_cameralist (
  mac_address,
  state,
  camera_no,
  license_id,
  add_dt,
  del_flg
) VALUES
  ('34-a6-ef-35-73-73', 1, 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', NOW(), 0),
  ('de-2e-80-43-28-b3', 1, 2, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', NOW(), 0);
