<?php
/**
 * hc_doudou熊掌号接口定义
 *
 * @author 陈亮
 * @url https://ue.c1993.com
 */
defined('IN_IA') or exit('Access Denied');

class Hc_doudouModuleXzapp extends WeModuleXzapp {
	public function doPageTest(){
		global $_GPC, $_W;
		// 此处开发者自行处理
		include $this->template('test');
	}

}