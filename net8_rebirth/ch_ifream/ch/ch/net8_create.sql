-- MySQL dump 10.13  Distrib 5.7.25, for Linux (x86_64)
--
-- Host: localhost    Database: net8_xxx
-- ------------------------------------------------------
-- Server version	5.7.25

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `dat_address`
--

DROP TABLE IF EXISTS `dat_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_address` (
  `member_no` int(10) unsigned NOT NULL,
  `seq` tinyint(4) NOT NULL,
  `syll` varchar(20) NOT NULL,
  `name` varchar(20) NOT NULL,
  `postal` varchar(9) NOT NULL,
  `address1` varchar(100) NOT NULL,
  `address2` varchar(100) NOT NULL,
  `address3` varchar(100) NOT NULL,
  `address4` varchar(100) DEFAULT NULL,
  `tel` varchar(20) NOT NULL,
  `use_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`member_no`,`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_benefits`
--

DROP TABLE IF EXISTS `dat_benefits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_benefits` (
  `benefits_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `end_dt` datetime NOT NULL,
  `issued` int(10) unsigned NOT NULL,
  `point` int(11) NOT NULL,
  `limit_days` int(11) DEFAULT '0',
  `stop_flg` tinyint(4) NOT NULL DEFAULT '0',
  `stop_no` int(10) unsigned DEFAULT NULL,
  `stop_dt` datetime DEFAULT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`benefits_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_benefitsDetail`
--

DROP TABLE IF EXISTS `dat_benefitsDetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_benefitsDetail` (
  `benefits_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `benefits_cd` varchar(255) NOT NULL,
  `member_no` int(10) unsigned DEFAULT NULL,
  `use_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`benefits_no`,`benefits_cd`),
  KEY `INDEX1` (`benefits_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_client_message`
--

DROP TABLE IF EXISTS `dat_client_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_client_message` (
  `message_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_time` datetime NOT NULL,
  `message_text` varchar(512) NOT NULL,
  `machines` varchar(512) NOT NULL DEFAULT '*',
  `stop_time` datetime DEFAULT NULL,
  `reset_bonus` tinyint(4) NOT NULL DEFAULT '0',
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`message_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_contactBox`
--

DROP TABLE IF EXISTS `dat_contactBox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_contactBox` (
  `member_no` int(10) unsigned NOT NULL,
  `seq` int(10) unsigned NOT NULL,
  `contact_type` char(2) NOT NULL,
  `key_no` int(10) unsigned NOT NULL,
  `delivery_dt` datetime NOT NULL,
  `dsp_flg` tinyint(4) NOT NULL DEFAULT '0',
  `dsp_dt` datetime DEFAULT NULL,
  `opened_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`member_no`,`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_contactBox_lang`
--

DROP TABLE IF EXISTS `dat_contactBox_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_contactBox_lang` (
  `member_no` int(10) unsigned NOT NULL,
  `seq` int(10) unsigned NOT NULL,
  `lang` varchar(2) NOT NULL,
  `contents` varchar(255) DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`member_no`,`seq`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_coupon`
--

DROP TABLE IF EXISTS `dat_coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_coupon` (
  `coupon_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_type` tinyint(4) NOT NULL DEFAULT '1',
  `point` int(11) NOT NULL DEFAULT '0',
  `limit_days` int(11) DEFAULT '0',
  `plan_count` int(11) DEFAULT '0',
  `grant_count` int(11) DEFAULT '0',
  `plan_dt` datetime DEFAULT NULL,
  `grant_dt` datetime DEFAULT NULL,
  `coupon_state` tinyint(4) NOT NULL DEFAULT '0',
  `cond_member_no` int(10) unsigned DEFAULT NULL,
  `cond_sex` tinyint(4) DEFAULT NULL,
  `cond_bmonth` tinyint(4) DEFAULT NULL,
  `cond_point_from` int(10) unsigned DEFAULT NULL,
  `cond_point_to` int(10) unsigned DEFAULT NULL,
  `cond_draw_point_from` int(10) unsigned DEFAULT NULL,
  `cond_draw_point_to` int(10) unsigned DEFAULT NULL,
  `cond_join_from` date DEFAULT NULL,
  `cond_join_to` date DEFAULT NULL,
  `cond_login_from` date DEFAULT NULL,
  `cond_login_to` date DEFAULT NULL,
  `cond_play_count_from` int(11) DEFAULT NULL,
  `cond_play_count_to` int(11) DEFAULT NULL,
  `cond_play_dt_from` date DEFAULT NULL,
  `cond_play_dt_to` date DEFAULT NULL,
  `cond_purchase_type` varchar(100) DEFAULT NULL,
  `cond_purchase_count_from` int(11) DEFAULT NULL,
  `cond_purchase_count_to` int(11) DEFAULT NULL,
  `cond_purchase_amount_from` int(11) DEFAULT NULL,
  `cond_purchase_amount_to` int(11) DEFAULT NULL,
  `cond_purchase_dt_from` date DEFAULT NULL,
  `cond_purchase_dt_to` date DEFAULT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`coupon_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_coupon_lang`
--

DROP TABLE IF EXISTS `dat_coupon_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_coupon_lang` (
  `coupon_no` int(10) unsigned NOT NULL,
  `lang` varchar(2) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `contents` text,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`coupon_no`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_giftSMS`
--

DROP TABLE IF EXISTS `dat_giftSMS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_giftSMS` (
  `member_no` int(11) NOT NULL,
  `pin` int(11) NOT NULL,
  `limit_dt` datetime NOT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`member_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_machine`
--

DROP TABLE IF EXISTS `dat_machine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_machine` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_machineCorner`
--

DROP TABLE IF EXISTS `dat_machineCorner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_machineCorner` (
  `machine_no` int(10) unsigned NOT NULL,
  `corner_no` int(10) unsigned NOT NULL,
  `add_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`machine_no`,`corner_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_machinePlay`
--

DROP TABLE IF EXISTS `dat_machinePlay`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_machinePlay` (
  `machine_no` int(10) unsigned NOT NULL,
  `day_count` int(11) DEFAULT '0',
  `total_count` int(11) DEFAULT '0',
  `count` int(11) DEFAULT '0',
  `bb_count` int(11) DEFAULT '0',
  `rb_count` int(11) DEFAULT '0',
  `in_credit` int(11) DEFAULT '0',
  `out_credit` int(11) DEFAULT '0',
  `hit_data` mediumtext,
  `renchan_count` int(11) DEFAULT '0',
  `maxrenchan_count` int(11) DEFAULT '0',
  `tenjo_count` int(11) DEFAULT '0',
  `ichigeki_credit` int(11) DEFAULT '0',
  `max_credit` int(11) DEFAULT '0',
  `past_max_credit` int(11) DEFAULT '0',
  `past_max_bb` int(11) DEFAULT '0',
  `past_max_rb` int(11) DEFAULT '0',
  `add_dt` datetime DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`machine_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_magazine`
--

DROP TABLE IF EXISTS `dat_magazine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_magazine` (
  `magazine_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `send_target` tinyint(4) NOT NULL DEFAULT '0',
  `title` varchar(50) NOT NULL,
  `contents` text NOT NULL,
  `plan_dt` datetime DEFAULT NULL,
  `plan_count` int(11) DEFAULT '0',
  `make_dt` datetime DEFAULT NULL,
  `send_start_dt` datetime DEFAULT NULL,
  `send_end_dt` datetime DEFAULT NULL,
  `send_count` int(11) DEFAULT '0',
  `magazine_state` tinyint(4) NOT NULL DEFAULT '0',
  `cond_member_no` int(10) unsigned DEFAULT NULL,
  `cond_sex` tinyint(4) DEFAULT NULL,
  `cond_bmonth` tinyint(4) DEFAULT NULL,
  `cond_point_from` int(10) unsigned DEFAULT NULL,
  `cond_point_to` int(10) unsigned DEFAULT NULL,
  `cond_draw_point_from` int(10) unsigned DEFAULT NULL,
  `cond_draw_point_to` int(10) unsigned DEFAULT NULL,
  `cond_join_from` date DEFAULT NULL,
  `cond_join_to` date DEFAULT NULL,
  `cond_login_from` date DEFAULT NULL,
  `cond_login_to` date DEFAULT NULL,
  `cond_play_count_from` int(11) DEFAULT NULL,
  `cond_play_count_to` int(11) DEFAULT NULL,
  `cond_play_dt_from` date DEFAULT NULL,
  `cond_play_dt_to` date DEFAULT NULL,
  `cond_purchase_type` varchar(100) DEFAULT NULL,
  `cond_purchase_count_from` int(11) DEFAULT NULL,
  `cond_purchase_count_to` int(11) DEFAULT NULL,
  `cond_purchase_amount_from` int(11) DEFAULT NULL,
  `cond_purchase_amount_to` int(11) DEFAULT NULL,
  `cond_purchase_dt_from` date DEFAULT NULL,
  `cond_purchase_dt_to` date DEFAULT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`magazine_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_magazineControl`
--

DROP TABLE IF EXISTS `dat_magazineControl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_magazineControl` (
  `no` tinyint(4) NOT NULL,
  `magazine_no` int(10) unsigned DEFAULT NULL,
  `member_no` int(10) unsigned DEFAULT NULL,
  `send_start_dt` datetime DEFAULT NULL,
  `send_end_dt` datetime DEFAULT NULL,
  `send_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_magazineTarget`
--

DROP TABLE IF EXISTS `dat_magazineTarget`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_magazineTarget` (
  `magazine_no` int(10) unsigned NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  `add_dt` datetime DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`magazine_no`,`member_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_mail_identify`
--

DROP TABLE IF EXISTS `dat_mail_identify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_mail_identify` (
  `identify_key` varchar(255) NOT NULL,
  `identify_kbn` tinyint(4) NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `limit_dt` datetime DEFAULT NULL,
  `incidental_info` varchar(255) DEFAULT NULL,
  `add_ua` varchar(1024) DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`identify_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_notice`
--

DROP TABLE IF EXISTS `dat_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_notice` (
  `notice_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notice_name` varchar(50) NOT NULL,
  `link_type` tinyint(4) NOT NULL DEFAULT '0',
  `link_url` varchar(100) DEFAULT NULL,
  `disp_order` tinyint(4) NOT NULL DEFAULT '9',
  `start_dt` date NOT NULL,
  `end_dt` date NOT NULL DEFAULT '2099-12-31',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`notice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_notice_lang`
--

DROP TABLE IF EXISTS `dat_notice_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_notice_lang` (
  `notice_no` int(10) unsigned NOT NULL,
  `lang` varchar(2) NOT NULL,
  `top_image` varchar(50) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `sub_title` varchar(100) DEFAULT NULL,
  `list_title` varchar(200) DEFAULT NULL,
  `contents` text,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`notice_no`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_request`
--

DROP TABLE IF EXISTS `dat_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_request` (
  `goods_no` int(10) unsigned NOT NULL,
  `seq` int(11) NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `request_dt` datetime NOT NULL,
  `result` tinyint(4) NOT NULL DEFAULT '0',
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`goods_no`,`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_sms_identify`
--

DROP TABLE IF EXISTS `dat_sms_identify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_sms_identify` (
  `member_no` int(10) unsigned NOT NULL,
  `identify_kbn` tinyint(4) NOT NULL,
  `pin` int(11) NOT NULL,
  `limit_dt` datetime NOT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`member_no`,`identify_kbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dat_win`
--

DROP TABLE IF EXISTS `dat_win`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dat_win` (
  `goods_no` int(10) unsigned NOT NULL,
  `seq` int(11) NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `syll` varchar(20) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `postal` varchar(9) DEFAULT NULL,
  `address1` varchar(100) DEFAULT NULL,
  `address2` varchar(100) DEFAULT NULL,
  `address3` varchar(100) DEFAULT NULL,
  `address4` varchar(100) DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `remarks` text,
  `shipping_no` varchar(50) DEFAULT NULL,
  `shipping_dt` date DEFAULT NULL,
  `in_remarks` text,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`goods_no`,`seq`),
  KEY `INDEX1` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_drawPoint`
--

DROP TABLE IF EXISTS `his_drawPoint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_drawPoint` (
  `member_no` int(10) unsigned NOT NULL,
  `proc_dt` datetime(3) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `proc_cd` char(2) NOT NULL,
  `key_no` bigint(20) unsigned DEFAULT NULL,
  `before_draw_point` int(11) NOT NULL DEFAULT '0',
  `draw_point` int(11) NOT NULL DEFAULT '0',
  `after_draw_point` int(11) NOT NULL DEFAULT '0',
  `reason` varchar(255) DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`member_no`,`proc_dt`),
  KEY `INDEX1` (`key_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_gift`
--

DROP TABLE IF EXISTS `his_gift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_gift` (
  `gift_no` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_no` int(10) unsigned NOT NULL,
  `agent_flg` tinyint(4) NOT NULL DEFAULT '0',
  `gift_dt` datetime NOT NULL,
  `gift_point` int(11) DEFAULT '0',
  `commission_rate` tinyint(4) DEFAULT NULL,
  `commission_point` int(11) DEFAULT '0',
  `bearer` tinyint(4) NOT NULL DEFAULT '1',
  `receive_member_no` int(10) unsigned DEFAULT NULL,
  `receive_agent_flg` tinyint(4) NOT NULL DEFAULT '0',
  `receive_point` int(11) DEFAULT '0',
  PRIMARY KEY (`gift_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_gift_add`
--

DROP TABLE IF EXISTS `his_gift_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_gift_add` (
  `proc_dt` date NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `addset_no` int(10) unsigned NOT NULL,
  `addset_type` tinyint(4) NOT NULL,
  `base_val` int(11) NOT NULL,
  `add_point` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`proc_dt`,`member_no`,`addset_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_machinePlay`
--

DROP TABLE IF EXISTS `his_machinePlay`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_machinePlay` (
  `play_dt` date NOT NULL,
  `machine_no` int(10) unsigned NOT NULL,
  `day_count` int(11) DEFAULT '0',
  `total_count` int(11) DEFAULT '0',
  `count` int(11) DEFAULT '0',
  `bb_count` int(11) DEFAULT '0',
  `rb_count` int(11) DEFAULT '0',
  `in_credit` int(11) DEFAULT '0',
  `out_credit` int(11) DEFAULT '0',
  `hit_data` text,
  `renchan_count` int(11) DEFAULT '0',
  `maxrenchan_count` int(11) DEFAULT '0',
  `tenjo_count` int(11) DEFAULT '0',
  `ichigeki_credit` int(11) DEFAULT '0',
  `max_credit` int(11) DEFAULT '0',
  `past_max_credit` int(11) DEFAULT '0',
  `past_max_bb` int(11) DEFAULT '0',
  `past_max_rb` int(11) DEFAULT '0',
  `add_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`play_dt`,`machine_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_machineSetting`
--

DROP TABLE IF EXISTS `his_machineSetting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_machineSetting` (
  `machine_no` int(10) unsigned NOT NULL,
  `start_dt` datetime NOT NULL,
  `end_dt` datetime DEFAULT NULL,
  `real_setting` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`machine_no`,`start_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_member_login`
--

DROP TABLE IF EXISTS `his_member_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_member_login` (
  `member_no` int(10) unsigned NOT NULL,
  `login_dt` datetime NOT NULL,
  `login_ua` varchar(1024) NOT NULL,
  PRIMARY KEY (`member_no`,`login_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_play`
--

DROP TABLE IF EXISTS `his_play`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_play` (
  `machine_no` int(10) unsigned NOT NULL,
  `start_dt` datetime NOT NULL,
  `end_dt` datetime NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `owner_no` int(10) unsigned DEFAULT NULL,
  `convert_no` tinyint(3) unsigned DEFAULT NULL,
  `point` int(11) DEFAULT '0',
  `credit` int(11) DEFAULT '0',
  `draw_point` int(11) DEFAULT '0',
  `in_point` int(11) NOT NULL DEFAULT '0',
  `out_point` int(11) NOT NULL DEFAULT '0',
  `in_credit` int(11) NOT NULL DEFAULT '0',
  `out_credit` int(11) NOT NULL DEFAULT '0',
  `out_draw_point` int(11) NOT NULL DEFAULT '0',
  `lost_point` int(11) NOT NULL DEFAULT '0',
  `play_count` int(11) DEFAULT '0',
  `bb_count` int(11) DEFAULT '0',
  `rb_count` int(11) DEFAULT '0',
  `out_action_type` char(2) DEFAULT NULL,
  PRIMARY KEY (`machine_no`,`start_dt`),
  KEY `INDEX1` (`member_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_point`
--

DROP TABLE IF EXISTS `his_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_point` (
  `member_no` int(10) unsigned NOT NULL,
  `proc_dt` datetime(3) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `proc_cd` char(2) NOT NULL,
  `key_no` bigint(20) unsigned DEFAULT NULL,
  `limit_dt` datetime DEFAULT NULL,
  `before_point` int(11) NOT NULL DEFAULT '0',
  `point` int(11) NOT NULL DEFAULT '0',
  `after_point` int(11) NOT NULL DEFAULT '0',
  `reason` varchar(255) DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`member_no`,`proc_dt`,`proc_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_pointLimit`
--

DROP TABLE IF EXISTS `his_pointLimit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_pointLimit` (
  `point_no` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_no` int(10) unsigned NOT NULL,
  `proc_dt` datetime NOT NULL,
  `proc_cd` char(2) NOT NULL,
  `limit_dt` datetime DEFAULT NULL,
  `point` int(11) NOT NULL DEFAULT '0',
  `valid_point` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`point_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `his_purchase`
--

DROP TABLE IF EXISTS `his_purchase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `his_purchase` (
  `purchase_no` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_no` int(10) unsigned NOT NULL,
  `recept_dt` datetime NOT NULL,
  `purchase_type` char(2) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `point` int(11) NOT NULL DEFAULT '0',
  `result_status` int(11) NOT NULL DEFAULT '0',
  `result_message` text,
  `purchase_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lnk_machine`
--

DROP TABLE IF EXISTS `lnk_machine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lnk_machine` (
  `machine_no` tinyint(3) unsigned NOT NULL,
  `assign_flg` tinyint(4) NOT NULL DEFAULT '0',
  `member_no` int(10) unsigned DEFAULT NULL,
  `onetime_id` varchar(50) DEFAULT NULL,
  `exit_flg` tinyint(4) DEFAULT '0',
  `start_dt` datetime DEFAULT NULL,
  `end_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`machine_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_coupon`
--

DROP TABLE IF EXISTS `log_coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_coupon` (
  `coupon_no` int(10) unsigned NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `add_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`coupon_no`,`member_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_play`
--

DROP TABLE IF EXISTS `log_play`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_play` (
  `play_dt` datetime NOT NULL,
  `machine_no` int(10) unsigned NOT NULL,
  `member_no` int(10) unsigned NOT NULL,
  `start_point` int(11) NOT NULL DEFAULT '0',
  `in_point` int(11) NOT NULL DEFAULT '0',
  `out_point` int(11) NOT NULL DEFAULT '0',
  `in_credit` int(11) NOT NULL DEFAULT '0',
  `out_credit` int(11) NOT NULL DEFAULT '0',
  `out_draw_point` int(11) NOT NULL DEFAULT '0',
  `play_count` int(11) DEFAULT '0',
  `bb_count` int(11) DEFAULT '0',
  `rb_count` int(11) DEFAULT '0',
  `renchan_count` int(11) DEFAULT '0',
  `tenjo_count` int(11) DEFAULT '0',
  `ichigeki_credit` int(11) DEFAULT '0',
  `max_credit` int(11) DEFAULT '0',
  PRIMARY KEY (`play_dt`,`machine_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_pointLimit`
--

DROP TABLE IF EXISTS `log_pointLimit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_pointLimit` (
  `point_no` bigint(20) unsigned NOT NULL,
  `proc_dt` datetime(3) NOT NULL,
  `point` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`point_no`,`proc_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_admin`
--

DROP TABLE IF EXISTS `mst_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_admin` (
  `admin_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(30) DEFAULT NULL,
  `admin_id` varchar(20) NOT NULL,
  `admin_pass` varchar(255) NOT NULL,
  `auth_flg` tinyint(4) NOT NULL DEFAULT '0',
  `deny_menu` text,
  `login_dt` datetime DEFAULT NULL,
  `login_ua` varchar(1024) DEFAULT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`admin_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_camera`
--

DROP TABLE IF EXISTS `mst_camera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_camera` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_cameralist`
--

DROP TABLE IF EXISTS `mst_cameralist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_cameralist` (
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
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`mac_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_convertPoint`
--

DROP TABLE IF EXISTS `mst_convertPoint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_convertPoint` (
  `convert_no` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `convert_name` varchar(20) NOT NULL,
  `point` int(11) NOT NULL DEFAULT '0',
  `credit` int(11) NOT NULL DEFAULT '0',
  `draw_point` int(11) NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`convert_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_corner`
--

DROP TABLE IF EXISTS `mst_corner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_corner` (
  `corner_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `corner_name` varchar(20) NOT NULL,
  `corner_roman` varchar(50) DEFAULT NULL,
  `notice_flg` tinyint(4) DEFAULT '0',
  `remarks` text,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`corner_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_gift`
--

DROP TABLE IF EXISTS `mst_gift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_gift` (
  `no` tinyint(4) NOT NULL DEFAULT '1',
  `min_point` int(11) NOT NULL DEFAULT '0',
  `lot` int(11) NOT NULL DEFAULT '0',
  `commission_rate` tinyint(4) NOT NULL DEFAULT '0',
  `commission_rounding` tinyint(4) NOT NULL DEFAULT '1',
  `bearer` tinyint(4) NOT NULL DEFAULT '1',
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_gift_addset`
--

DROP TABLE IF EXISTS `mst_gift_addset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_gift_addset` (
  `addset_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `addset_type` tinyint(4) NOT NULL,
  `add_point` int(10) unsigned NOT NULL DEFAULT '0',
  `base_val` int(11) NOT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`addset_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_gift_limit`
--

DROP TABLE IF EXISTS `mst_gift_limit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_gift_limit` (
  `no` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `total_gift_point` int(10) unsigned NOT NULL,
  `gift_limit` int(10) unsigned NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_goods`
--

DROP TABLE IF EXISTS `mst_goods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_goods` (
  `goods_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_cd` varchar(20) NOT NULL,
  `goods_image` varchar(50) NOT NULL,
  `draw_point` int(10) unsigned NOT NULL,
  `release_dt` datetime DEFAULT NULL,
  `recept_start_dt` datetime NOT NULL,
  `recept_end_dt` datetime NOT NULL,
  `draw_dt` datetime NOT NULL,
  `draw_type` tinyint(4) NOT NULL DEFAULT '1',
  `draw_min_count` int(11) DEFAULT '0',
  `recept_count` int(11) NOT NULL DEFAULT '0',
  `win_count` int(11) NOT NULL DEFAULT '0',
  `request_count` int(11) NOT NULL DEFAULT '0',
  `sold_out_flg` tinyint(4) NOT NULL DEFAULT '0',
  `draw_state` tinyint(4) NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`goods_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_goods_lang`
--

DROP TABLE IF EXISTS `mst_goods_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_goods_lang` (
  `goods_no` int(10) unsigned NOT NULL,
  `lang` varchar(2) NOT NULL,
  `goods_name` varchar(50) NOT NULL,
  `goods_info` text NOT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`goods_no`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_grantPoint`
--

DROP TABLE IF EXISTS `mst_grantPoint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_grantPoint` (
  `proc_cd` char(2) NOT NULL,
  `point` int(11) NOT NULL DEFAULT '0',
  `limit_days` int(11) DEFAULT '0',
  `special_start_dt` date DEFAULT NULL,
  `special_end_dt` date DEFAULT NULL,
  `special_point` int(11) DEFAULT '0',
  `special_limit_days` int(11) DEFAULT '0',
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`proc_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_maker`
--

DROP TABLE IF EXISTS `mst_maker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_maker` (
  `maker_no` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `maker_name` varchar(20) NOT NULL,
  `maker_roman` varchar(50) NOT NULL,
  `pachi_flg` tinyint(4) NOT NULL DEFAULT '0',
  `slot_flg` tinyint(4) NOT NULL DEFAULT '0',
  `disp_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`maker_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_member`
--

DROP TABLE IF EXISTS `mst_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_member` (
  `member_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(20) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `mobile_upd_dt` datetime DEFAULT NULL,
  `mobile_checked_dt` datetime DEFAULT NULL,
  `international_cd` varchar(10) DEFAULT NULL,
  `pass` varchar(255) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `sex` tinyint(4) DEFAULT NULL,
  `point` int(10) unsigned NOT NULL DEFAULT '0',
  `draw_point` int(10) unsigned NOT NULL DEFAULT '0',
  `loss_count` int(11) DEFAULT '0',
  `deadline_point` int(10) unsigned NOT NULL DEFAULT '0',
  `remarks` mediumtext,
  `mail_magazine` tinyint(4) NOT NULL DEFAULT '0',
  `login_dt` datetime DEFAULT NULL,
  `login_ua` varchar(1024) DEFAULT NULL,
  `login_days` int(11) NOT NULL DEFAULT '0',
  `regist_id` varchar(30) DEFAULT NULL,
  `regist_dt` datetime DEFAULT NULL,
  `tester_flg` tinyint(4) NOT NULL DEFAULT '0',
  `mail_error_count` int(11) DEFAULT '0',
  `mail_error_dt` datetime DEFAULT NULL,
  `invite_cd` varchar(12) DEFAULT NULL,
  `invite_member_no` int(11) DEFAULT NULL,
  `benefits_cd` varchar(255) DEFAULT NULL,
  `agent_flg` tinyint(4) NOT NULL DEFAULT '0',
  `gift_point` int(10) unsigned NOT NULL DEFAULT '0',
  `total_gift_point` int(10) unsigned NOT NULL DEFAULT '0',
  `black_flg` tinyint(4) NOT NULL DEFAULT '0',
  `black_reason` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `black_dt` datetime DEFAULT NULL,
  `temp_dt` datetime DEFAULT NULL,
  `join_dt` datetime DEFAULT NULL,
  `quit_dt` datetime DEFAULT NULL,
  `quit_reason` text,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`member_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_model`
--

DROP TABLE IF EXISTS `mst_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_model` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_owner`
--

DROP TABLE IF EXISTS `mst_owner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_owner` (
  `owner_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_cd` varchar(20) DEFAULT NULL,
  `owner_name` varchar(20) DEFAULT NULL,
  `owner_nickname` varchar(20) NOT NULL,
  `owner_pref` tinyint(4) DEFAULT NULL,
  `mail` varchar(255) DEFAULT NULL,
  `machine_count` int(11) DEFAULT '0',
  `remarks` text,
  `dummy_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`owner_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_purchasePoint`
--

DROP TABLE IF EXISTS `mst_purchasePoint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_purchasePoint` (
  `purchase_type` tinyint(4) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `point` int(11) NOT NULL DEFAULT '0',
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`purchase_type`,`amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_setting`
--

DROP TABLE IF EXISTS `mst_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_setting` (
  `setting_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_type` tinyint(4) NOT NULL DEFAULT '1',
  `setting_name` varchar(255) DEFAULT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_format` tinyint(4) NOT NULL DEFAULT '1',
  `setting_val` varchar(255) NOT NULL,
  `remarks` text,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`setting_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_type`
--

DROP TABLE IF EXISTS `mst_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_type` (
  `type_no` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `category` tinyint(4) NOT NULL,
  `type_name` varchar(20) NOT NULL,
  `type_roman` varchar(50) NOT NULL,
  `sort_no` tinyint(4) NOT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`type_no`),
  KEY `INDEX1` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mst_unit`
--

DROP TABLE IF EXISTS `mst_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mst_unit` (
  `unit_no` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(20) NOT NULL,
  `unit_roman` varchar(50) NOT NULL,
  `sort_no` tinyint(4) NOT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`unit_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'net8_xxx'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-05-10 19:34:42
