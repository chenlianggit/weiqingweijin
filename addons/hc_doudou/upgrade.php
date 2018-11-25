<?php
if (!pdo_fieldexists('hcdoudou_users', 'level')) {
	pdo_query("ALTER TABLE " . tablename('hcdoudou_users') . " ADD `level` TINYINT(1) NOT NULL DEFAULT '1'");
}
if (!pdo_tableexists('hcdoudou_checkgoods')) {
	pdo_query("CREATE TABLE ".tablename('hcdoudou_checkgoods')." (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `weid` int(11) NOT NULL,
	  `title` varchar(200) NOT NULL,
	  `model` varchar(200) NOT NULL,
	  `price` int(11) NOT NULL,
	  `thumb` varchar(300) NOT NULL,
	  `sort` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`),
	  KEY `weid` (`weid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

if (!pdo_tableexists('hcdoudou_cash')) {
	pdo_query("CREATE TABLE ".tablename('hcdoudou_cash')." (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `uid` int(11) NOT NULL,
	  `transid` varchar(20) NOT NULL,
	  `money` decimal(10,2) NOT NULL,
	  `fee` decimal(10,2) NOT NULL,
	  `status` tinyint(1) NOT NULL DEFAULT '0',
	  `createtime` char(10) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

if (!pdo_tableexists('hcdoudou_commission')) {
	pdo_query("CREATE TABLE ".tablename('hcdoudou_commission')." (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `sub_id` int(11) NOT NULL,
	  `trade_no` varchar(30) NOT NULL,
	  `price` decimal(10,2) NOT NULL,
	  `rate` int(11) NOT NULL,
	  `profit` decimal(10,2) NOT NULL,
	  `level` tinyint(1) NOT NULL,
	  `sort` tinyint(1) NOT NULL,
	  `status` tinyint(1) NOT NULL DEFAULT '0',
	  `freeze` tinyint(1) NOT NULL DEFAULT '0',
	  `createtime` char(10) NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

if (!pdo_tableexists('hcdoudou_nexus')) {
	pdo_query("CREATE TABLE ".tablename('hcdoudou_nexus')." (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `pppid` int(11) NOT NULL,
	  `ppid` int(11) NOT NULL,
	  `pid` int(11) NOT NULL,
	  `uid` int(11) NOT NULL,
	  `ctime` int(11) NOT NULL,
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `uid` (`uid`),
	  KEY `pppid` (`pppid`),
	  KEY `ppid` (`ppid`),
	  KEY `pid` (`pid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}
if (!pdo_tableexists('hcdoudou_upgrade')) {
	pdo_query("CREATE TABLE ".tablename('hcdoudou_upgrade')." (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `title` varchar(100) NOT NULL,
	  `trade_no` varchar(20) NOT NULL,
	  `uid` int(11) NOT NULL,
	  `openid` varchar(50) NOT NULL,
	  `price` decimal(10,2) NOT NULL,
	  `transaction_id` varchar(50) NOT NULL,
	  `createtime` char(10) NOT NULL,
	  `paytime` char(10) NOT NULL,
	  `level` tinyint(1) NOT NULL,
	  `status` tinyint(1) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}
