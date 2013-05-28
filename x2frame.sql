/*
SQLyog Ultimate v9.20 
MySQL - 5.0.33 : Database - tongji
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `tj_common_admincp_cmenu` */

DROP TABLE IF EXISTS `tj_common_admincp_cmenu`;

CREATE TABLE `tj_common_admincp_cmenu` (
  `id` smallint(6) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sort` tinyint(1) NOT NULL default '0',
  `displayorder` tinyint(3) NOT NULL,
  `clicks` smallint(6) unsigned NOT NULL default '1',
  `uid` mediumint(8) unsigned NOT NULL,
  `dateline` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `uid` (`uid`),
  KEY `displayorder` (`displayorder`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_admincp_cmenu` */

/*Table structure for table `tj_common_admincp_session` */

DROP TABLE IF EXISTS `tj_common_admincp_session`;

CREATE TABLE `tj_common_admincp_session` (
  `uid` mediumint(8) unsigned NOT NULL default '0',
  `adminid` smallint(6) unsigned NOT NULL default '0',
  `panel` tinyint(1) NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  `dateline` int(10) unsigned NOT NULL default '0',
  `errorcount` tinyint(1) NOT NULL default '0',
  `storage` mediumtext NOT NULL,
  PRIMARY KEY  (`uid`,`panel`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_admincp_session` */

insert  into `tj_common_admincp_session`(`uid`,`adminid`,`panel`,`ip`,`dateline`,`errorcount`,`storage`) values (2,1,1,'127.0.0.1',1337842839,-1,''),(1,1,1,'127.0.0.1',1337842939,-1,'');

/*Table structure for table `tj_common_adminnote` */

DROP TABLE IF EXISTS `tj_common_adminnote`;

CREATE TABLE `tj_common_adminnote` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `admin` varchar(15) NOT NULL default '',
  `access` tinyint(3) NOT NULL default '0',
  `adminid` tinyint(3) NOT NULL default '0',
  `dateline` int(10) unsigned NOT NULL default '0',
  `expiration` int(10) unsigned NOT NULL default '0',
  `message` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_adminnote` */

/*Table structure for table `tj_common_banned` */

DROP TABLE IF EXISTS `tj_common_banned`;

CREATE TABLE `tj_common_banned` (
  `id` smallint(6) unsigned NOT NULL auto_increment,
  `ip1` smallint(3) NOT NULL default '0',
  `ip2` smallint(3) NOT NULL default '0',
  `ip3` smallint(3) NOT NULL default '0',
  `ip4` smallint(3) NOT NULL default '0',
  `admin` varchar(15) NOT NULL default '',
  `dateline` int(10) unsigned NOT NULL default '0',
  `expiration` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_banned` */

/*Table structure for table `tj_common_cache` */

DROP TABLE IF EXISTS `tj_common_cache`;

CREATE TABLE `tj_common_cache` (
  `cachekey` varchar(255) NOT NULL default '',
  `cachevalue` mediumblob NOT NULL,
  `dateline` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`cachekey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_cache` */

/*Table structure for table `tj_common_cron` */

DROP TABLE IF EXISTS `tj_common_cron`;

CREATE TABLE `tj_common_cron` (
  `cronid` smallint(6) unsigned NOT NULL auto_increment,
  `available` tinyint(1) NOT NULL default '0',
  `type` enum('user','system') NOT NULL default 'user',
  `name` char(50) NOT NULL default '',
  `filename` char(50) NOT NULL default '',
  `lastrun` int(10) unsigned NOT NULL default '0',
  `nextrun` int(10) unsigned NOT NULL default '0',
  `weekday` tinyint(1) NOT NULL default '0',
  `day` tinyint(2) NOT NULL default '0',
  `hour` tinyint(2) NOT NULL default '0',
  `minute` char(36) NOT NULL default '',
  PRIMARY KEY  (`cronid`),
  KEY `nextrun` (`available`,`nextrun`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_cron` */

insert  into `tj_common_cron`(`cronid`,`available`,`type`,`name`,`filename`,`lastrun`,`nextrun`,`weekday`,`day`,`hour`,`minute`) values (16,1,'user','清理过期动态','cron_cleanfeed.php',1337842499,1337904000,-1,-1,0,'0');

/*Table structure for table `tj_common_failedlogin` */

DROP TABLE IF EXISTS `tj_common_failedlogin`;

CREATE TABLE `tj_common_failedlogin` (
  `ip` char(15) NOT NULL default '',
  `username` char(15) NOT NULL default '',
  `count` tinyint(1) unsigned NOT NULL default '0',
  `lastupdate` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ip`,`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_failedlogin` */

insert  into `tj_common_failedlogin`(`ip`,`username`,`count`,`lastupdate`) values ('127.0.0.1','团长',0,1337840851);

/*Table structure for table `tj_common_member` */

DROP TABLE IF EXISTS `tj_common_member`;

CREATE TABLE `tj_common_member` (
  `uid` mediumint(8) unsigned NOT NULL auto_increment,
  `email` char(40) NOT NULL default '',
  `username` char(15) NOT NULL default '',
  `password` char(32) NOT NULL default '',
  `status` tinyint(1) NOT NULL default '0',
  `emailstatus` tinyint(1) NOT NULL default '0',
  `adminid` tinyint(1) NOT NULL default '0',
  `groupid` smallint(6) unsigned NOT NULL default '0',
  `regdate` int(10) unsigned NOT NULL default '0',
  `timeoffset` char(4) NOT NULL default '',
  `allowadmincp` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`uid`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `groupid` (`groupid`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_member` */

insert  into `tj_common_member`(`uid`,`email`,`username`,`password`,`status`,`emailstatus`,`adminid`,`groupid`,`regdate`,`timeoffset`,`allowadmincp`) values (1,'admin@admin.com','admin','21232f297a57a5a743894a0e4a801fc3',0,0,1,1,1337752324,'',1),(2,'d@mama.cn','团长','e19d5cd5af0378da05f63f891c7467af',0,0,1,1,1337771040,'',1);

/*Table structure for table `tj_common_member_status` */

DROP TABLE IF EXISTS `tj_common_member_status`;

CREATE TABLE `tj_common_member_status` (
  `uid` mediumint(8) unsigned NOT NULL,
  `regip` char(15) NOT NULL default '',
  `lastip` char(15) NOT NULL default '',
  `lastvisit` int(10) unsigned NOT NULL default '0',
  `lastactivity` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_member_status` */

insert  into `tj_common_member_status`(`uid`,`regip`,`lastip`,`lastvisit`,`lastactivity`) values (1,'','127.0.0.1',1337842938,1337822180),(4,'Manual Acting','',1337771222,1337771222);

/*Table structure for table `tj_common_process` */

DROP TABLE IF EXISTS `tj_common_process`;

CREATE TABLE `tj_common_process` (
  `processid` char(32) NOT NULL,
  `expiry` int(10) default NULL,
  `extra` int(10) default NULL,
  PRIMARY KEY  (`processid`),
  KEY `expiry` (`expiry`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_process` */

/*Table structure for table `tj_common_session` */

DROP TABLE IF EXISTS `tj_common_session`;

CREATE TABLE `tj_common_session` (
  `sid` char(6) NOT NULL default '',
  `ip1` tinyint(3) unsigned NOT NULL default '0',
  `ip2` tinyint(3) unsigned NOT NULL default '0',
  `ip3` tinyint(3) unsigned NOT NULL default '0',
  `ip4` tinyint(3) unsigned NOT NULL default '0',
  `uid` mediumint(8) unsigned NOT NULL default '0',
  `username` char(15) NOT NULL default '',
  `groupid` smallint(6) unsigned NOT NULL default '0',
  `invisible` tinyint(1) NOT NULL default '0',
  `action` tinyint(1) unsigned NOT NULL default '0',
  `lastactivity` int(10) unsigned NOT NULL default '0',
  `lastolupdate` int(10) unsigned NOT NULL default '0',
  `fid` mediumint(8) unsigned NOT NULL default '0',
  `tid` mediumint(8) unsigned NOT NULL default '0',
  UNIQUE KEY `sid` (`sid`),
  KEY `uid` (`uid`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_session` */

insert  into `tj_common_session`(`sid`,`ip1`,`ip2`,`ip3`,`ip4`,`uid`,`username`,`groupid`,`invisible`,`action`,`lastactivity`,`lastolupdate`,`fid`,`tid`) values ('DrAaDq',127,0,0,1,1,'admin',1,0,0,1337842938,0,0,0);

/*Table structure for table `tj_common_setting` */

DROP TABLE IF EXISTS `tj_common_setting`;

CREATE TABLE `tj_common_setting` (
  `skey` varchar(255) NOT NULL default '',
  `svalue` text NOT NULL,
  PRIMARY KEY  (`skey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_setting` */

insert  into `tj_common_setting`(`skey`,`svalue`) values ('adminipaccess',''),('authkey','abf9f4z8YRZ7GZVt'),('bbname','妈网流量统计平台'),('dateconvert','1'),('dateformat','Y-n-j'),('globalstick','1'),('maxbdays','0'),('maxchargespan','0'),('maxfavorites','100'),('maxincperthread','0'),('maxmagicprice','50'),('maxmodworksmonths','3'),('memory',''),('nocacheheaders','0'),('sitemessage','a:5:{s:4:\"time\";s:1:\"3\";s:8:\"register\";s:0:\"\";s:5:\"login\";s:0:\"\";s:9:\"newthread\";s:0:\"\";s:5:\"reply\";s:0:\"\";}'),('sitename','妈妈网'),('siteuniqueid','DXLJPBdO3542W3Wr'),('siteurl','http://tongji.mama.cn'),('starthreshold','2'),('statcode',''),('statscachelife','180'),('statstatus',''),('timeformat','H:i'),('timeoffset','8'),('attachdir','./attachment'),('attachimgpost','1'),('attachurl','attachment'),('fastsmilies','1'),('fastsmiley','a:1:{i:1;a:16:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";i:3;s:1:\"4\";i:8;s:1:\"5\";i:9;s:1:\"6\";i:10;s:1:\"7\";i:11;s:1:\"8\";i:12;s:1:\"9\";i:13;s:2:\"10\";i:14;s:2:\"11\";i:15;s:2:\"12\";i:16;s:2:\"13\";i:17;s:2:\"14\";i:18;s:2:\"15\";i:19;s:2:\"16\";}}'),('smthumb','20'),('boardlicensed','0'),('bbclosed','0'),('closedallowactivation','0'),('adminemail','tongji@mama.cn'),('icp','23423423'),('oltimespan','10');

/*Table structure for table `tj_common_syscache` */

DROP TABLE IF EXISTS `tj_common_syscache`;

CREATE TABLE `tj_common_syscache` (
  `cname` varchar(32) NOT NULL,
  `ctype` tinyint(3) unsigned NOT NULL,
  `dateline` int(10) unsigned NOT NULL,
  `data` mediumblob NOT NULL,
  PRIMARY KEY  (`cname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `tj_common_syscache` */

insert  into `tj_common_syscache`(`cname`,`ctype`,`dateline`,`data`) values ('cronnextrun',0,1337842499,'1337904000');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
