<?php
/**
 * hc_doudou模块APP接口定义
 *
 * @author 陈亮
 * @url https://ue.c1993.com
 */
defined('IN_IA') or exit('Access Denied');

class Hc_doudouModulePhoneapp extends WeModulePhoneapp {
	public function doPageTest(){
		global $_GPC, $_W;
		$errno = 0;
		$message = '返回消息';
		$data = array();
		return $this->result($errno, $message, $data);
	}
	
	
}