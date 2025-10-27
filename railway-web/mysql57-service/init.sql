-- MySQL 5.7 Initialization Script
-- This script runs automatically when the container starts for the first time

USE net8_dev;

-- Create mst_cameralist table
CREATE TABLE IF NOT EXISTS `mst_cameralist` (
  `mac_address` varchar(17) NOT NULL,
  `state` int(10) unsigned DEFAULT NULL,
  `camera_no` int(10) unsigned DEFAULT NULL,
  `system_name` varchar(64) DEFAULT NULL,
  `ip_address` varchar(17) DEFAULT NULL,
  `identifing_number` varchar(32) DEFAULT NULL,
  `product_name` varchar(128) DEFAULT NULL,
  `cpu_name` varchar(64) DEFAULT NULL,
  `core` int(10) unsigned DEFAULT NULL,
  `uuid` varchar(64) DEFAULT NULL,
  `license_id` varchar(128) DEFAULT NULL,
  `license_cd` varchar(100) DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`mac_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert Windows PC data
INSERT INTO `mst_cameralist`
(`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
VALUES
('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', 'f2e419eee66138df5444cecab202fa3001944c772f0dada61288b7142925e5a1', 0, 0, 1, NOW())
ON DUPLICATE KEY UPDATE
license_cd = 'f2e419eee66138df5444cecab202fa3001944c772f0dada61288b7142925e5a1';

-- Create mst_camera table
CREATE TABLE IF NOT EXISTS `mst_camera` (
  `camera_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `camera_mac` varchar(17) NOT NULL,
  `camera_name` varchar(32) NOT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`camera_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert camera data
INSERT INTO mst_camera (camera_no, camera_mac, camera_name, add_no, add_dt)
VALUES (1, '34-a6-ef-35-73-73', 'Windows PC Camera', 1, NOW())
ON DUPLICATE KEY UPDATE camera_mac = camera_mac;

-- Create mst_model table
CREATE TABLE IF NOT EXISTS `mst_model` (
  `model_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` tinyint(4) NOT NULL,
  `model_cd` varchar(20) NOT NULL,
  `model_name` varchar(50) NOT NULL,
  `model_roman` varchar(200) DEFAULT NULL,
  `maker_no` tinyint(3) unsigned NOT NULL,
  `type_no` tinyint(3) unsigned DEFAULT NULL,
  `unit_no` tinyint(3) unsigned DEFAULT NULL,
  `renchan_games` smallint(5) unsigned DEFAULT '0',
  `tenjo_games` smallint(5) unsigned DEFAULT '9999',
  `setting_list` varchar(50) DEFAULT NULL,
  `push_order_flg` tinyint(3) unsigned DEFAULT '0',
  `image_list` varchar(50) DEFAULT NULL,
  `image_detail` varchar(50) DEFAULT NULL,
  `image_reel` varchar(50) DEFAULT NULL,
  `prizeball_data` text,
  `layout_data` text,
  `remarks` text,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`model_no`),
  KEY `INDEX1` (`category`),
  KEY `INDEX2` (`model_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert model data
INSERT INTO mst_model
(model_no, category, model_cd, model_name, maker_no, renchan_games, tenjo_games, layout_data, prizeball_data, add_no, add_dt)
VALUES
(1, 1, 'TEST001', 'Test Model', 1, 0, 9999, '{"version":"1"}', '{"MAX":10,"MAX_RATE":100,"NAVEL":3,"TULIP":1,"ATTACKER1":15,"ATTACKER2":10}', 1, NOW())
ON DUPLICATE KEY UPDATE model_cd = model_cd;

-- Create dat_machine table
CREATE TABLE IF NOT EXISTS `dat_machine` (
  `machine_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_no` int(10) unsigned NOT NULL,
  `machine_cd` varchar(20) DEFAULT NULL,
  `owner_no` int(10) unsigned DEFAULT NULL,
  `camera_no` int(10) unsigned DEFAULT NULL,
  `signaling_id` varchar(10) NOT NULL,
  `convert_no` tinyint(4) unsigned NOT NULL,
  `release_date` date NOT NULL,
  `end_date` date NOT NULL DEFAULT '2099-12-31',
  `machine_corner` mediumtext,
  `real_setting` tinyint(4) DEFAULT '0',
  `upd_setting` tinyint(4) DEFAULT '0',
  `setting_upd_no` int(10) unsigned DEFAULT NULL,
  `setting_upd_dt` datetime DEFAULT NULL,
  `reboot_sw` int(10) unsigned DEFAULT '0',
  `reboot_dt` datetime DEFAULT NULL,
  `remarks` mediumtext,
  `machine_status` tinyint(4) NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`machine_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert machine data
INSERT INTO dat_machine
(machine_no, model_no, camera_no, signaling_id, convert_no, release_date, add_no, add_dt)
VALUES
(1, 1, 1, 'peer1', 0, NOW(), 1, NOW())
ON DUPLICATE KEY UPDATE signaling_id = signaling_id;
