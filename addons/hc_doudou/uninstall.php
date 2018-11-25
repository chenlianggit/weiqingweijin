<?php
pdo_query("
	DROP TABLE IF EXISTS ".tablename('hcdoudou_address').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_cash').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_checkgoods').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_commission').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_goods').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_guan').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_nexus').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_order').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_paylog').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_setting').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_upgrade').";
	DROP TABLE IF EXISTS ".tablename('hcdoudou_users').";
");
