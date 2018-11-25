<?php
/**
 * hc_doudou模块微站定义
 *
 * @author 陈亮
 * @url https://ue.c1993.com
 */
defined('IN_IA') or exit('Access Denied');
require_once IA_ROOT."/addons/hc_doudou/functions.php"; 
class Hc_doudouModuleSite extends WeModuleSite {


	public function doWebSetting() {
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		if($_GPC['act']=='submit'){
			$data = array(
				'basic'   => json_encode($_GPC['basic']),
				'icon'    => json_encode($_GPC['icon']),
				'pay'     => json_encode($_GPC['pay']),
				'forward' => json_encode($_GPC['forward']),
				'version' => json_encode($_GPC['version']),
				'notice'  => json_encode($_GPC['notice']),
				'fenxiao' => json_encode($_GPC['fenxiao']),
				'cash'    => json_encode($_GPC['cash']),
			);
			foreach ($data as $key => $val) {
				pdo_insert('hcdoudou_setting',array('weid'=>$weid,'only'=>$key.$weid,'title'=>$key,'value'=>$val),'true');
			}
			$dir = IA_ROOT.'/addons/hc_doudou/cert';
			if(!file_exists($dir)){
	            mkdir($dir);
	            chmod($dir,0777);
	        }
			if(!empty($_GPC['apiclient_cert'])){
				file_put_contents($dir.'/apiclient_cert_'.$weid.'.pem',$_GPC['apiclient_cert']);
			}
			if(!empty($_GPC['apiclient_key'])){
				file_put_contents($dir.'/apiclient_key_'.$weid.'.pem',$_GPC['apiclient_key']);
			}
			message('保存成功','referer','info');
		}else{
			$res = pdo_getall('hcdoudou_setting',array('weid'=>$weid));
			foreach($res as $key => $val) {
				$set[$val['title']] = json_decode($val['value'],true);
			}
			include $this->template('web/setting');
		}
	}
	/**
	 * 用户管理
	 * @return [type] [description]
	 */
	public function doWebUsers() {
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 10;
        $keyword = $_GPC['keyword'];
        if(!empty($keyword)){
        	$where = array('weid'=>$weid,'nickname like'=>'%'.$_GPC['keyword'].'%');
        }else{
        	$where = array('weid'=>$weid);
        }
        $users = pdo_getslice('hcdoudou_users',$where,array($pageindex, $pagesize),$total,array(),'','createtime desc');
        $page = pagination($total, $pageindex, $pagesize);
		include $this->template('web/users');
	}
	/**
	 * 用户操作
	 * @return [type] [description]
	 */
	public function doWebUserdo() {
		global $_GPC, $_W;
		if($_GPC['act']=='del'){
			pdo_delete('hcdoudou_users',array('uid'=>$_GPC['uid']));
			message('操作成功',$this->createWebUrl('users'),'success');
		}
		$info = pdo_get('hcdoudou_users',array('uid'=>$_GPC['uid']));
		include $this->template('web/users_post');
	}
	/**
	 * 关卡管理
	 * @return [type] [description]
	 */
	public function doWebGuan() {
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;
        $keyword = $_GPC['keyword'];
        if(!empty($keyword)){
        	$where['title like'] = '%'.$keyword.'%';
        }
        $where['weid'] = $weid;
        $list = pdo_getslice('hcdoudou_guan',$where,array($pageindex, $pagesize),$total,array(),'','id desc');

        foreach ($list as $key => $val) {
        	$list[$key]['loadpic'] = tomedia($val['loadpic']);
        	$list[$key]['rollpic'] = tomedia($val['rollpic']);
        	$list[$key]['proppic'] = tomedia($val['proppic']);
        	$list[$key]['gamebgm'] = tomedia($val['gamebgm']);
        	$list[$key]['passbgm'] = tomedia($val['passbgm']);
        	$list[$key]['losebgm'] = tomedia($val['losebgm']);
        }
        $page = pagination($total, $pageindex, $pagesize);
		include $this->template('web/guan');
	}
	/**
	 * 关卡操作
	 * @return [type] [description]
	 */
	public function doWebGuan_post(){
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		if($_GPC['act']=='edit'){
			$data = array(
				'weid'  => $weid,
				'sort'  => $_GPC['sort'],
				'times' => $_GPC['times'],
				'loadpic' => $_GPC['loadpic'],
				'rollpic' => $_GPC['rollpic'],
				'proppic' => $_GPC['proppic'],
				'gamebgm' => $_GPC['gamebgm'],
				'passbgm' => $_GPC['passbgm'],
				'losebgm' => $_GPC['losebgm']
			);
			pdo_update('hcdoudou_guan',$data,array('id'=>$_GPC['id']));
			message('操作成功',$this->createWebUrl('guan'),'success');
		}else{
			if(!empty($_GPC['id'])){
				$info = pdo_get('hcdoudou_guan',array('id'=>$_GPC['id']));
			}
			include $this->template('web/guan_post');
		}
	}
	/**
	 * 商品管理
	 * @return [type] [description]
	 */
	public function doWebGoods() {
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;
        $keyword = $_GPC['keyword'];
        if(!empty($keyword)){
        	$where['title like'] = '%'.$keyword.'%';
        }
        $where['weid'] = $weid;
        $list = pdo_getslice('hcdoudou_goods',$where,array($pageindex, $pagesize),$total,array(),'','id desc');

        foreach ($list as $key => $val) {
        	$list[$key]['thumb'] = tomedia($val['thumb']);
        }
        $page = pagination($total, $pageindex, $pagesize);
		include $this->template('web/goods');
	}
	/**
	 * 商品操作
	 * @return [type] [description]
	 */
	public function doWebGoods_post(){
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		if($_GPC['act']=='add'){
			$data = array(
				'weid'  => $weid,
				'sort'  => $_GPC['sort'],
				'title' => $_GPC['title'],
				'model' => $_GPC['model'],
				'price' => $_GPC['price'],
				'storeprice' => $_GPC['storeprice'],
				'thumb' => $_GPC['thumb']
			);
			pdo_insert('hcdoudou_goods',$data);
			message('操作成功',$this->createWebUrl('goods'),'success');
		}elseif($_GPC['act']=='edit'){
			$data = array(
				'weid'  => $weid,
				'sort'  => $_GPC['sort'],
				'title' => $_GPC['title'],
				'model' => $_GPC['model'],
				'price' => $_GPC['price'],
				'storeprice' => $_GPC['storeprice'],
				'thumb' => $_GPC['thumb']
			);
			pdo_update('hcdoudou_goods',$data,array('id'=>$_GPC['id']));
			message('操作成功',$this->createWebUrl('goods'),'success');
		}elseif($_GPC['act']=='del'){
			pdo_delete('hcdoudou_goods',array('id'=>$_GPC['id']));
			message('操作成功',$this->createWebUrl('goods'),'success');
		}elseif($_GPC['act']=='moredel'){
			foreach(explode(',',$_GPC['ids']) as $key=>$val){
				pdo_delete('hcdoudou_goods',array('id'=>$val));
			}
			message('操作成功',$this->createWebUrl('question'),'success');
		}else{
			if(!empty($_GPC['id'])){
				$info = pdo_get('hcdoudou_goods',array('id'=>$_GPC['id']));
			}
			include $this->template('web/goods_post');
		}
	}
	/**
	 * 参与记录
	 * @return [type] [description]
	 */
	public function doWebCount() {
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;
        $type = $_GPC['type'];
        if($type==1){
        	$where['type'] = 1;
        }
        if($type==2){
        	$where['type'] = 0;
        }
        $keyword = $_GPC['keyword'];
        if(!empty($keyword)){
        	$where['trade_no'] = $keyword;
        }
        $where['weid'] = $weid;
        $list = pdo_getslice('hcdoudou_order',$where,array($pageindex, $pagesize),$total,array(),'','id desc');

        foreach ($list as $key => $val) {
        	$user = pdo_get('hcdoudou_users',array('uid'=>$val['uid']),array('avatar','nickname'));
        	$list[$key]['avatar'] = $user['avatar'];
        	$list[$key]['nickname'] = $user['nickname'];
        	unset($user);
        	$goods = pdo_get('hcdoudou_goods',array('id'=>$val['gid']),array('thumb'));
        	$list[$key]['goodsthumb'] = tomedia($goods['thumb']);
        	unset($goods);
        }
        $page = pagination($total, $pageindex, $pagesize);


        $total_price = pdo_getcolumn('hcdoudou_order',array('weid'=>$weid),array('sum(price)'));

		include $this->template('web/count');
	}
	/**
	 * 参与记录
	 * @return [type] [description]
	 */
	public function doWebOrder() {
		global $_GPC, $_W;
		$weid = $_W['uniacid'];

		$keywordtype = $_GPC['keywordtype'];
		$keyword  = $_GPC['keyword'];

		$status   = $_GPC['status'];
		if(!empty($status)){
			$where['status'] = $status;
		}
		if($keywordtype=='1'){
			$where['trade_no'] = $keyword;
		}elseif($keywordtype=='2'){
			$where['openid'] = $keyword;
		}elseif($keywordtype=='3'){
			$where['title like'] = '%'.$keyword.'%';
		}elseif($keywordtype=='4'){
			$where['gid'] = $keyword;
		}
        $where['type'] = 1;
        $where['weid'] = $weid;


		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;
    
        $list = pdo_getslice('hcdoudou_order',$where,array($pageindex, $pagesize),$total,array(),'','createtime desc');

        foreach ($list as $key => $val) {
        	$user = pdo_get('hcdoudou_users',array('uid'=>$val['uid']),array('avatar','nickname'));
        	$list[$key]['avatar'] = $user['avatar'];
        	$list[$key]['nickname'] = $user['nickname'];
        	unset($user);
        	$goods = pdo_get('hcdoudou_goods',array('id'=>$val['gid']),array('thumb'));
        	$list[$key]['goodsthumb'] = tomedia($goods['thumb']);
        	unset($goods);
        }
        $page = pagination($total, $pageindex, $pagesize);
		include $this->template('web/order');
	}
	/**
	 * 订单操作
	 * @return [type] [description]
	 */
	public function doWebOrder_post(){
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		if($_GPC['act']=='addexpresn'){
			pdo_update('hcdoudou_order',array('expretime'=>time(),'expresn'=>$_GPC['code'],'status'=>2),array('id'=>$_GPC['id']));
			message('操作成功',$this->createWebUrl('order'),'success');
		}else{
			$info = pdo_get('hcdoudou_order',array('trade_no'=>$_GPC['trade_no']));
			$address = pdo_get('hcdoudou_address',array('uid'=>$info['uid'],'weid'=>$weid));
			include $this->template('web/order_post');
		}
	}
	/**
	 * 充值记录
	 * @return [type] [description]
	 */
	public function doWebRecharge(){
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;

        $status = $_GPC['status'];
        if($status==1){
        	$where['status'] = 1;
        }
        if($status==2){
        	$where['status'] = 0;
        }
        $keyword = $_GPC['keyword'];
        if(!empty($keyword)){
        	$where['trade_no'] = $keyword;
        }
        $where['weid'] = $weid;
        $list = pdo_getslice('hcdoudou_paylog',$where,array($pageindex, $pagesize),$total,array(),'','createtime desc');
        foreach ($list as $key => $val) {
        	$user = pdo_get('hcdoudou_users',array('uid'=>$val['uid']),array('avatar','nickname'));
        	$list[$key]['avatar'] = $user['avatar'];
        	$list[$key]['nickname'] = $user['nickname'];
        	unset($user);
        }
        $page = pagination($total, $pageindex, $pagesize);

        $total_fee = pdo_getcolumn('hcdoudou_paylog',array('status'=>1,'weid'=>$weid),array('sum(total_fee)'));
		include $this->template('web/recharge');
	}

	/**
	 * 商品管理
	 * @return [type] [description]
	 */
	public function doWebCheckgoods() {
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;
        $where['weid'] = $weid;
        $list = pdo_getslice('hcdoudou_checkgoods',$where,array($pageindex, $pagesize),$total,array(),'','id desc');

        foreach ($list as $key => $val) {
        	$list[$key]['thumb'] = tomedia($val['thumb']);
        }
        $page = pagination($total, $pageindex, $pagesize);
		include $this->template('web/checkgoods');
	}
	/**
	 * 商品操作
	 * @return [type] [description]
	 */
	public function doWebCheckgoods_post(){
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		if($_GPC['act']=='add'){
			$data = array(
				'weid'  => $weid,
				'sort'  => $_GPC['sort'],
				'title' => $_GPC['title'],
				'model' => $_GPC['model'],
				'price' => $_GPC['price'],
				'thumb' => $_GPC['thumb']
			);
			pdo_insert('hcdoudou_checkgoods',$data);
			message('操作成功',$this->createWebUrl('checkgoods'),'success');
		}elseif($_GPC['act']=='edit'){
			$data = array(
				'weid'  => $weid,
				'sort'  => $_GPC['sort'],
				'title' => $_GPC['title'],
				'model' => $_GPC['model'],
				'price' => $_GPC['price'],
				'thumb' => $_GPC['thumb']
			);
			pdo_update('hcdoudou_checkgoods',$data,array('id'=>$_GPC['id']));
			message('操作成功',$this->createWebUrl('checkgoods'),'success');
		}elseif($_GPC['act']=='del'){
			pdo_delete('hcdoudou_checkgoods',array('id'=>$_GPC['id']));
			message('操作成功',$this->createWebUrl('checkgoods'),'success');
		}else{
			if(!empty($_GPC['id'])){
				$info = pdo_get('hcdoudou_checkgoods',array('id'=>$_GPC['id']));
			}
			include $this->template('web/checkgoods_post');
		}
	}
	/**
	 * 升级列表
	 * @return [type] [description]
	 */
	public function doWebUpgrade() {
		global $_GPC, $_W;
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 10;

        $list = pdo_getslice('hcdoudou_upgrade','',array($pageindex, $pagesize),$total,array(),'','createtime desc');
        foreach ($list as $key => $val) {
        	$user = pdo_get('hcdoudou_users',array('uid'=>$val['uid']),array('avatar','nickname'));
        	$list[$key]['avatar'] = $user['avatar'];
        	$list[$key]['nickname'] = $user['nickname'];
        	unset($user);
        }

        $page = pagination($total, $pageindex, $pagesize);
		include $this->template('web/upgrade');
	}
	/**
	 * 提现审核列表
	 * @return [type] [description]
	 */
	public function doWebCash() {
		global $_GPC, $_W;
		$pageindex = max(1, intval($_GPC['page']));
        $pagesize = 10;

        $list = pdo_getslice('hcdoudou_cash','',array($pageindex, $pagesize),$total,array(),'','createtime desc');
        foreach ($list as $key => $val) {
        	$user = pdo_get('hcdoudou_users',array('uid'=>$val['uid']),array('avatar','nickname'));
        	$list[$key]['avatar'] = $user['avatar'];
        	$list[$key]['nickname'] = $user['nickname'];
        	unset($user);
        }

        $page = pagination($total, $pageindex, $pagesize);
		include $this->template('web/cash');
	}
	/**
	 * 系统审核提现
	 * @return [type] [description]
	 */
	public function doWebSyscash(){
		global $_GPC, $_W;
		$weid = $_W['uniacid'];
		$id = $_GPC['id'];
		$type = $_GPC['type'];

		
		$cash = pdo_get('hcdoudou_cash',array('id'=>$id));
        $uid = $cash['uid'];
		$where = array(
            'user_id'=>$uid,
            'status'=>0,
            'freeze'=>1,
        );
		if($type==1){
	        
	        $openid = pdo_getcolumn('hcdoudou_users',array('uid'=>$uid),array('openid'));

	        $conf = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'basic'.$weid),array('value')),'true');
	        $money = $cash['money']-$cash['fee'];
	        $res = $this->cash($openid,$money,$cash['fee'],$cash['transid'],$conf['title']);

	        if($res['result_code'] == 'FAIL'){
	            message($res['err_code_des'],'','error');
	        }else{
	            pdo_update('hcdoudou_cash',array('status'=>1),array('id'=>$id));
                pdo_update('hcdoudou_commission',array('freeze'=>0,'status'=>1),$where);
	            message('提现成功','','success');
	        }
	    }elseif($type==2){
	    	pdo_update('hcdoudou_cash',array('status'=>2),array('id'=>$id));
	    	pdo_update('hcdoudou_commission',array('freeze'=>0),$where);
	    	message('拒绝成功','','success');
	    }
	}

	public function cash($openid,$money,$transid,$wxappname){
        global $_W;
        $weid = $_W['uniacid'];
        load()->model('payment');
        load()->model('account');
        $setting = uni_setting($_W['uniacid'], array('payment'));
        $mch_appid = $_W['account']['key'];
        $signkey = $setting['payment']['wechat']['signkey'];
        $mchid  = $setting['payment']['wechat']['mchid'];
        $model = new HcfkModel();
        $pars = array();
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $pars['mch_appid'] = $mch_appid;
        $pars['mchid'] = $mchid;
        $pars['nonce_str'] = random(32);
        $pars['partner_trade_no'] = $transid;
        $pars['openid'] = $openid;
        $pars['check_name'] = 'NO_CHECK';
        $pars['amount'] = intval($money * 100);
        $pars['desc'] = $wxappname."余额提现";
        $pars['spbill_create_ip'] = $model->get_client_ip();
        $pars['sign'] = $model->getSign($pars,$signkey);
        $xml = $model->array2xml($pars);
        $cert = array(
            'CURLOPT_SSLCERT' => IA_ROOT ."/addons/hc_doudou/cert/apiclient_cert_".$weid.".pem",
            'CURLOPT_SSLKEY'  => IA_ROOT ."/addons/hc_doudou/cert/apiclient_key_".$weid.".pem",
        );
        $resp = ihttp_request($url, $xml, $cert);
        
        return $model->xmlstr_to_array($resp['content']);
    }

	public function doMobileGames() {
		global $_GPC, $_W;
		$game = $_GPC['game'];
		if($game==1){
			include $this->template('taste');
		}else{
			include $this->template('play');
		}
	}





}