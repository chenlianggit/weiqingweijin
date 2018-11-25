<?php
/**
 * hc_doudou模块小程序接口定义
 *
 * @author 陈亮
 * @url https://ue.c1993.com
 */
defined('IN_IA') or exit('Access Denied');
require_once IA_ROOT."/addons/hc_doudou/functions.php"; 
require_once IA_ROOT."/addons/hc_doudou/wxBizDataCrypt.php"; 
class Hc_doudouModuleWxapp extends WeModuleWxapp {
	/*
    * code 
    * @param $code 微信授权code
    * @param $result['openid'] 
     */
    public function doPageGetopenid(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $code = $_GPC['code'];
        $account = pdo_get('account_wxapp',array('uniacid'=>$_W['uniacid']));
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$account['key'].'&secret='.$account['secret'].'&js_code='.$code.'&grant_type=authorization_code';
        $result = ihttp_get($url);
        $result = json_decode($result['content'],true);
        return $this->result(0, '获取成功', $result);
    }
    /*
    * 授权登录，获取用户信息
    * @param $uid 用户ID
     */

    public function doPageGetuserinfo(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $sessionKey    = $_GPC['session_key'];
        $encryptedData = $_GPC['encryptedData'];
        $iv            = $_GPC['iv'];
        $openid        = $_GPC['openid'];
        $appid = pdo_getcolumn('account_wxapp',array('uniacid'=>$weid),array('key'));
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData,$iv,$data);
        $return  = json_decode($data, true);

        //$openid  = $return['openId'];
        if(empty($openid)||$openid=='undefined'){
            return $this->result(1, '参数错误，缺少OPENID');
        }
        $ishave = pdo_get('hcdoudou_users', array('openid'=>$openid));
        if(empty($ishave)){
            $arr['createtime'] = time();
            $arr['weid']     = $weid;
            $arr['openid']   = $openid;
            $arr['nickname'] = $return['nickName'];
            $arr['gender']   = $return['gender'];
            $arr['city']     = $return['city'];
            $arr['province'] = $return['province'];
            $arr['country']  = $return['country'];
            $arr['avatar']   = $return['avatarUrl'];
            $arr['unionid']  = $return['unionId'];
            $arr['sessionkey'] = $sessionKey;

            $result = pdo_insert('hcdoudou_users', $arr);
            if (!empty($result)) {
                $uid = pdo_insertid();
            }
        }else{
            $arr['sessionkey'] = $sessionKey;
            pdo_update('hcdoudou_users',$arr,array('uid'=>$ishave['uid']));
            $uid = $ishave['uid'];
        }
        $mc_have = pdo_get('mc_mapping_fans',array('openid'=>$openid));
        if(empty($mc_have)){
            $mc_data = array(
                'acid' =>$weid,
                'uniacid' =>$weid,
                'uid' =>$uid,
                'openid' =>$openid,
                'nickname' =>$arr['nickname'],
            );
            pdo_insert('mc_mapping_fans',$mc_data);
        }
        return $this->result(0, '操作成功', $uid);
    }
    /**
     * 首页商品
     * @return [type] [description]
     */
    public function doPageGoods(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $list = pdo_getall('hcdoudou_goods', array('weid'=>$weid), array() , '' , 'sort ASC');
        foreach($list as $key=>$val){
            $list[$key]['thumb'] = tomedia($val['thumb']);
        }
        return $this->result(0, '获取成功',$list);
    }

    public function doPageMymoney(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $uid  = $_GPC['uid'];
        $money = pdo_getcolumn('hcdoudou_users',array('uid'=>$uid),array('money'));
        return $this->result(0, '获取成功',$money);
    }

    /**
     * 充值金额展示
     */
    public function doPageJine(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $pay = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'pay'.$weid),array('value')),'true');
        $money = explode('|',$pay['money']);
        return $this->result(0, '获取成功',$money);
    }
    /**
     * 充值
     */
    public function doPageRecharge(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $fee  = $_GPC['money'];
        $uid  = $_GPC['uid'];

        $pay = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'pay'.$weid),array('value')),'true');
        $money = explode('|',$pay['money']);
        for($i=0;$i<count($money);$i++){
            if($money[$i]==$fee){
                $status = true;
                break;
            }
        }
        if(!$status){
            return $this->result(1, '金额错误');
        }
        $trade_no = date('YmdHis').rand(100000,999999);
        $openid   = pdo_getcolumn('hcdoudou_users',array('uid'=>$uid),array('openid'));
        $params = array(
            'weid'      => $weid,
            'uid'       => $uid,
            'openid'    => $openid,
            'trade_no'  => $trade_no,
            'money'     => $fee,
            'createtime'=> time()
        );
        $res = pdo_insert('hcdoudou_paylog',$params);
        if($res){
            $pid = pdo_insertid();
            $paylog = pdo_get('hcdoudou_paylog',array('id'=>$pid));
            $this->payment($paylog);
        }else{
            return $this->result(1, '购买失败');
        }

    }

    /**
     * 调起微信支付
     */
    public function payment($order){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        load()->model('payment');
        load()->model('account');
        $setting = uni_setting($weid, array('payment'));
        $wechat_payment = array(
            'appid'   => $_W['account']['key'],
            'signkey' => $setting['payment']['wechat']['signkey'],
            'mchid'   => $setting['payment']['wechat']['mchid'],
        );
        //返回小程序参数
        $notify_url = $_W['siteroot'].'addons/hc_doudou/wxpay.php';
        $res = $this->getPrePayOrder($wechat_payment,$notify_url,$order,$order['openid']);


        if($res['return_code']=='FAIL'){
            return $this->result(1, '操作失败',$res['return_msg']);
        }
        if($res['result_code']=='FAIL'){
            return $this->result(1, '操作失败',$res['err_code'].$res['err_code_des']);
        }
        if($res['return_code']=='SUCCESS'){
            $wxdata = $this->getOrder($res['prepay_id'],$wechat_payment);
            
            return $this->result(0, '操作成功',$wxdata);
        }else{
            return $this->result(1, '操作失败');
        }
    }
    //微信统一支付
    public function getPrePayOrder($wechat_payment,$notify_url,$order,$openid){
        $model = new HcfkModel();
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        
        $data["appid"] = $wechat_payment['appid'];
        $data["body"] = '会员充值';
        $data["mch_id"] = $wechat_payment['mchid'];
        $data["nonce_str"] = $model->getRandChar(32);
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $order['trade_no'];
        $data["spbill_create_ip"] = $model->get_client_ip();
        $data["total_fee"] = $order['money']*100;
        $data["trade_type"] = "JSAPI";
        $data["openid"] = $openid;
        $data["sign"] = $model->getSign($data,$wechat_payment['signkey']);
    
        $xml = $model->arrayToXml($data);
        $response = $model->postXmlCurl($xml, $url);
        return $model->xmlstr_to_array($response);
    }
    
    // 执行第二次签名，才能返回给客户端使用
    public function getOrder($prepayId,$wechat_payment){

        $model = new HcfkModel();
        $data["appId"] = $wechat_payment['appid'];
        $data["nonceStr"] = $model->getRandChar(32);
        $data["package"] = "prepay_id=".$prepayId;
        $data['signType'] = "MD5";
        $data["timeStamp"] = time();
        $data["sign"] = $model->MakeSign($data,$wechat_payment['signkey']);

        return $data;
    }


    /**
     * 开始玩游戏
     * @return [type] [description]
     */
    public function doPagePlay(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $gid  = $_GPC['gid'];
        $uid  = $_GPC['uid'];

        $goods = pdo_get('hcdoudou_goods',array('id'=>$gid));
        $users = pdo_get('hcdoudou_users',array('uid'=>$uid));
        if($users['money']<$goods['price']){
            return $this->result(1, '余额不足');
        }

        $params = array(
            'weid'     => $weid,
            'gid'      => $gid,
            'uid'      => $uid,
            'openid'   => $users['openid'],
            'title'    => $goods['title'],
            'trade_no' => date('YmdHis').rand(100000,999999),
            'price'    => $goods['price'],
            'createtime'=>time(),
        );
        $res = pdo_insert('hcdoudou_order',$params);
        pdo_update('hcdoudou_users',array('money'=>$users['money']-$goods['price']),array('uid'=>$uid));
        if($res){
            return $this->result(0, '付款成功',$params);
        }else{
            return $this->result(1, '下单失败');
        }
    }
    /**
     * 游戏结果
     * @return [type] [description]
     */
    public function doPageResult(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $trade_no= $_GPC['orderid'];
        $uid    = $_GPC['uid'];
        $level  = $_GPC['level'];
        $result = $_GPC['result'];

        $params['level'] =  $level;
        if($level==3 && $result==2){
            $params['type'] = 1;
            $params['status'] = 1;
            $params['passtime'] = time(); 
        }else{
            $params['type'] = 2;
        }
        $res = pdo_update('hcdoudou_order',$params,array('trade_no'=>$trade_no,'uid'=>$uid));

        if($res){
            return $this->result(0, '成功');
        }else{
            return $this->result(1, '闯关失败');
        }
    }
    /**
     * 填写收货地址
     * @return [type] [description]
     */
    public function doPageAddress(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];

        $uid      = $_GPC['uid'];
        $username = $_GPC['username'];
        $mobile   = $_GPC['mobile'];
        $address  = $_GPC['address'];
        $data = array(
            'weid'     => $weid,
            'uid'      => $_GPC['uid'],
            'username' => $_GPC['username'],
            'mobile'   => $_GPC['mobile'],
            'address'  => $_GPC['address']
        );
        pdo_insert('hcdoudou_address',$data);
        return $this->result(0, '填写成功');
    }

    /**
     * 我的口红
     * @return [type] [description]
     */
    public function doPageMyrouge(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $uid    = $_GPC['uid'];
        $count = pdo_getcolumn('hcdoudou_order',array('uid'=>$uid,'type'=>1),array('count(id)'));
        

        return $this->result(0, '成功',$count);
    }


    /**
     * 订单列表
     * @return [type] [description]
     */
    public function doPageOrder(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $uid    = $_GPC['uid'];

        $pageindex = max(1, intval($_GPC['page']));
        $pagesize = 10;
        $list = pdo_getslice('hcdoudou_order',array('weid'=>$weid,'uid'=>$uid),array($pageindex, $pagesize),$total,array(),'','createtime desc');

        foreach($list as $key=>$val){
            $goods = pdo_get('hcdoudou_goods',array('id'=>$val['gid']));
            $goods['thumb'] = tomedia($goods['thumb']);
            $list[$key]['goods'] = $goods;
            unset($goods);
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            $list[$key]['passtime'] = date('Y-m-d H:i:s',$val['passtime']);
            $address = pdo_get('hcdoudou_address',array('uid'=>$val['uid']));
            $list[$key]['address'] = $address;
            unset($address);
        }
        $page = pagination($total, $pageindex, $pagesize);

        return $this->result(0, '成功',$list);
    }
    /**
     * 骗审商品
     * @return [type] [description]
     */
    public function doPageCheckgoods(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];

        $list = pdo_getall('hcdoudou_checkgoods', array('weid'=>$weid), array() , '' , 'sort ASC');
        foreach($list as $key=>$val){
            $list[$key]['thumb'] = tomedia($val['thumb']);
        }
        return $this->result(0, '获取成功',$list);
    }
    /**
     * 骗审商品详情
     * @return [type] [description]
     */
    public function doPageCheckgoods_detail(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $id   = $_GPC['gid'];
        $info = pdo_get('hcdoudou_checkgoods', array('weid'=>$weid,'id'=>$id));
        if(!empty($info)){
            $info['thumb'] = tomedia($info['thumb']);
        }
        return $this->result(0, '获取成功',$info);
    }

    /**
     * 2018-09-26
     * 分销开发
     */
    /*
    * 用户识别推广二维码进入小程序，绑定分销关系
    * @param $list
     */
    public function doPageBindnexus(){
        global $_GPC, $_W;
        $uid = $_GPC['uid'];
        $pid = empty($_GPC['pid'])?0:$_GPC['pid'];
        $self = pdo_get('hcdoudou_users',array('uid'=>$uid));
        if(empty($self['pid']) && $uid!=$pid && $self['createtime']-time()<5){
            $data = array(
                'pid'=>$pid,
                'uid'=>$uid,
                'ctime'=>time()
            );
            $ppid = pdo_getcolumn('hcdoudou_users',array('uid'=>$pid),array('pid'));
            if(!empty($ppid)){
                $data['ppid'] = $ppid;
                $pppid = pdo_getcolumn('hcdoudou_users',array('uid'=>$ppid),array('pid'));
                if(!empty($pppid)){
                    $data['pppid'] = $pppid;
                }
            }
            pdo_insert('hcdoudou_nexus',$data);
            pdo_update('hcdoudou_users',array('pid'=>$pid),array('uid'=>$uid));
        }
        return $this->result(0, '绑定成功');
    }

    /**
     * 生成推广二维码
     */
    public function doPageQrcode(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $uid = $_GPC['uid'];
        $model = new HcfkModel();
        $image = IA_ROOT.'/addons/hc_doudou/upload/qr'.$uid.'.png';
        if(!file_exists($image)){
            $qrcode = $model->wxappqrcode($uid);
            $fenxiao = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'fenxiao'.$weid),array('value')),'true');
            $image = $model->qrcode($fenxiao['bgimg'],$qrcode,'qr'.$uid.'.png');
        }else{
            $image = '/addons/hc_doudou/upload/qr'.$uid.'.png';
        }
        return $this->result(0, '操作成功',$_W['siteroot'].$image);
    }
    /**
     * 我的团队
     * @return [type] [description]
     */
    public function doPageTeamlist(){
        global $_GPC, $_W;
        $weid  = $_W['uniacid'];
        $uid   = $_GPC['uid'];
        $level = $_GPC['level'];

        $pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;

        if($level==1){
            $where['pid'] = $uid;
        }elseif($level==2){
            $where['ppid'] = $uid;
        }elseif($level==3){
            $where['pppid'] = $uid;
        }

        $list = pdo_getslice('hcdoudou_nexus',$where,array($pageindex, $pagesize),$total,array('uid','ctime'),'','ctime desc');
        foreach ($list as $key => $val) {
            $list[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
            $user = pdo_get('hcdoudou_users',array('uid'=>$val['uid']),array('nickname','avatar'));
            $list[$key]['nickname'] = $user['nickname'];
            $list[$key]['avatar'] = $user['avatar'];
            unset($user);
        }
        $page = pagination($total, $pageindex, $pagesize);
        return $this->result(0, '操作成功',$list);
    }
    /**
     * 我的团队数量统计
     * @return [type] [description]
     */
    public function doPageTeamcount(){
        global $_GPC, $_W;
        $uid   = $_GPC['uid'];
        $data['level1'] = pdo_getcolumn('hcdoudou_nexus',array('pid'=>$uid),array('count(uid)'));
        $data['level2'] = pdo_getcolumn('hcdoudou_nexus',array('ppid'=>$uid),array('count(uid)'));
        $data['level3'] = pdo_getcolumn('hcdoudou_nexus',array('pppid'=>$uid),array('count(uid)'));
        return $this->result(0, '操作成功',$data);
    }
    /*
     * 分销明细
     * @return [type] [description]
     */
    public function doPageCommissionList(){
        global $_GPC, $_W;
        $uid  = $_GPC['uid'];
        $level = $_GPC['level'];
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize = 10;

        $list = pdo_getslice('hcdoudou_commission',array('user_id'=>$uid,'sort'=>$level),array($pageindex, $pagesize),$total,array(),'','createtime desc');
        foreach ($list as $key => $val) {
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            $user = pdo_get('hcdoudou_users',array('uid'=>$val['sub_id']),array('nickname','avatar'));
            $list[$key]['nickname'] = $user['nickname'];
            $list[$key]['avatar'] = $user['avatar'];
            unset($user);
        }
        
        $page = pagination($total, $pageindex, $pagesize);
        return $this->result(0, '操作成功',$list);
    }
    /*
     * 分销明细数量统计
     * @return [type] [description]
     */
    public function doPageCommissionCount(){
        global $_GPC, $_W; 
        $uid  = $_GPC['uid'];
        $data['level1'] = pdo_getcolumn('hcdoudou_commission',array('user_id'=>$uid,'sort'=>1),array('count(id)'));
        $data['level2'] = pdo_getcolumn('hcdoudou_commission',array('user_id'=>$uid,'sort'=>2),array('count(id)'));
        $data['level3'] = pdo_getcolumn('hcdoudou_commission',array('user_id'=>$uid,'sort'=>3),array('count(id)'));
        return $this->result(0, '操作成功',$data);
    }

    /*
     * 可提现佣金
     * @return [type] [description]
     */
    public function doPageCanmoney(){
        global $_GPC, $_W; 
        $uid  = $_GPC['uid'];
        $weid = $_W['uniacid'];
        $where = array(
            'user_id'=>$uid,
            'status'=>0,
            'freeze'=>0,
            'createtime <'=>time()-86400*0
        );
        $money= pdo_getcolumn('hcdoudou_commission',$where,array('sum(profit)'));
        $money = empty($money)?0:$money;
        //用户会员等级
        $fenxiao = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'fenxiao'.$weid),array('value')),'true');
        $level = pdo_getcolumn('hcdoudou_users',array('uid'=>$uid),array('level'));

        $levelname = $fenxiao['grade'][$level-1]['grade'];
        return $this->result(0, '操作成功',array('money'=>$money,'level'=>$levelname,'levelno'=>$level));
    }
    /*
     * 提现佣金
     * @return [type] [description]
     */
    public function doPageCash(){
        global $_GPC, $_W; 
        $weid = $_W['uniacid'];
        $uid = $_GPC['uid'];
        $openid = pdo_getcolumn('hcdoudou_users',array('uid'=>$uid),array('openid'));
        $cash = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'cash'.$weid),array('value')),'true');
        $minmoney = $cash['min'];
        $maxmoney = $cash['max'];
        $feerate  = $cash['fee'];

        $basic = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'basic'.$weid),array('value')),'true'); 

        $where = array(
            'user_id'=>$uid,
            'status'=>0,
            'createtime <'=>time()-86400*0
        );
        $cashmoney= pdo_getcolumn('hcdoudou_commission',$where,array('sum(profit)'));

        $transid = date('Ymdhis').rand(11111,99999);
        $fee = round($cashmoney*$feerate/100,2);
        $money = $cashmoney-$fee;
        if($cashmoney>=$minmoney && $cashmoney<$maxmoney){
            $res = $this->cash($openid,$money,$fee,$transid,$basic['title']);
            if($res['result_code'] == 'FAIL'){
                return $this->result(0, '提现失败',$res['err_code_des']);
            }else{
                pdo_insert(
                    'hcdoudou_cash',
                    array(
                        'uid'=>$uid,
                        'transid'=>$transid,
                        'money'=>$cashmoney,
                        'fee'=>$fee,
                        'status'=>1,
                        'createtime'=>time()
                    )
                );
                pdo_update('hcdoudou_commission',array('status'=>1),$where);
                return $this->result(0, '提现成功');
            }
        }elseif($cashmoney>=$maxmoney){
            $undeal = pdo_get('hcdoudou_cash',array('uid'=>$uid,'status'=>0));
            if(!empty($undeal)){
                return $this->result(0, '您有待处理的提现请求，请联系客服处理后再试');
            }else{
               pdo_insert(
                    'hcdoudou_cash',
                    array(
                        'uid'=>$uid,
                        'transid'=>$transid,
                        'money'=>$cashmoney,
                        'fee'=>$fee,
                        'status'=>0,
                        'createtime'=>time()
                    )
                );
                pdo_update('hcdoudou_commission',array('freeze'=>1),$where);
                return $this->result(0, '提现请求已发送'); 
            }
        }else{
            return $this->result(0, '最低提现金额'.$minmoney.'元');
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

    /*
     * 提现明细
     * @return [type] [description]
     */
    public function doPageCashList(){
        global $_GPC, $_W;
        $uid  = $_GPC['uid'];
        $pageindex = max(1, intval($_GPC['page']));
        $pagesize = 20;

        $list = pdo_getslice('hcdoudou_cash',array('status'=>1,'uid'=>$uid),array($pageindex, $pagesize),$total,array(),'','createtime desc');

        foreach ($list as $key => $val) {
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            $user = pdo_get('hcdoudou_users',array('uid'=>$val['uid']),array('nickname','avatar'));
            $list[$key]['nickname'] = $user['nickname'];
            $list[$key]['avatar'] = $user['avatar'];
            unset($user);
        }
        
        $page = pagination($total, $pageindex, $pagesize);
        return $this->result(0, '操作成功',$list);
    }

    /*
     * 会员升级
     * @return [type] [description]
     */
    public function doPageUpgrade(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $fenxiao = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'fenxiao'.$weid),array('value')),'true');

        $arr[0] = $fenxiao['grade'][1];
        $arr[0]['pic'] = tomedia($fenxiao['grade'][1]['pic']);
        $arr[0]['commission'] = $fenxiao['commission'][1];

        $arr[0]['pricebg'] = $_W['siteroot'].'/addons/hc_doudou/public/middle.png';

        $arr[1] = $fenxiao['grade'][2];
        $arr[1]['pic'] = tomedia($fenxiao['grade'][2]['pic']);
        $arr[1]['commission'] = $fenxiao['commission'][2];
        $arr[1]['pricebg'] = $_W['siteroot'].'/addons/hc_doudou/public/high.png';

        return $this->result(0, '操作成功',$arr);
    }
    /*
     * 会员购买升级
     * @return [type] [description]
     */
    public function doPageUplevel(){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        $uid  = $_GPC['uid'];
        $level = $_GPC['level'];
        $fenxiao = json_decode(pdo_getcolumn('hcdoudou_setting',array('only'=>'fenxiao'.$weid),array('value')),'true');

        $openid = pdo_getcolumn('hcdoudou_users',array('uid'=>$uid),array('openid'));
        $data = array(
            'title' => $fenxiao['grade'][$level-1]['grade'],
            'trade_no' => date('YmdHis').rand(10000,99999),
            'uid'   => $uid,
            'openid'=>$openid,
            'price' => $fenxiao['grade'][$level-1]['money'],
            'level' => $level,
            'createtime' => time()
        );

        $res = pdo_insert('hcdoudou_upgrade',$data);
        if($res){
            $this->upgradePayment($data);
        }else{
            return $this->result(1, '网络错误');
        }

    }

    /**
     * 调起微信支付
     */
    public function upgradePayment($order){
        global $_GPC, $_W;
        $weid = $_W['uniacid'];
        load()->model('payment');
        load()->model('account');
        $setting = uni_setting($weid, array('payment'));
        $wechat_payment = array(
            'appid'   => $_W['account']['key'],
            'signkey' => $setting['payment']['wechat']['signkey'],
            'mchid'   => $setting['payment']['wechat']['mchid'],
        );
        //返回小程序参数
        $notify_url = $_W['siteroot'].'addons/hc_doudou/uplevel.php';
        $res = $this->getPrePayOrder2($wechat_payment,$notify_url,$order,$order['openid']);


        if($res['return_code']=='FAIL'){
            return $this->result(1, '操作失败',$res['return_msg']);
        }
        if($res['result_code']=='FAIL'){
            return $this->result(1, '操作失败',$res['err_code'].$res['err_code_des']);
        }
        if($res['return_code']=='SUCCESS'){
            $wxdata = $this->getOrder($res['prepay_id'],$wechat_payment);
            
            return $this->result(0, '操作成功',$wxdata);
        }else{
            return $this->result(1, '操作失败');
        }
    }
        //微信统一支付
    public function getPrePayOrder2($wechat_payment,$notify_url,$order,$openid){
        $model = new HcfkModel();
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        
        $data["appid"] = $wechat_payment['appid'];
        $data["body"] = '会员充值';
        $data["mch_id"] = $wechat_payment['mchid'];
        $data["nonce_str"] = $model->getRandChar(32);
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $order['trade_no'];
        $data["spbill_create_ip"] = $model->get_client_ip();
        $data["total_fee"] = $order['price']*100;
        $data["trade_type"] = "JSAPI";
        $data["openid"] = $openid;
        $data["sign"] = $model->getSign($data,$wechat_payment['signkey']);
    
        $xml = $model->arrayToXml($data);
        $response = $model->postXmlCurl($xml, $url);
        return $model->xmlstr_to_array($response);
    }


    /*
    * 基础信息配置
    * @param 
     */
    public function doPageSys(){
        global $_GPC, $_W; 
        $weid = $_W['uniacid'];
        $conf = pdo_getall('hcdoudou_setting',array('weid'=>$weid),array('title','value'));

        foreach ($conf as $key => $val) {
            if($val['title']=='basic'){
                $list['basic'] = json_decode($val['value'],true);
            }elseif($val['title']=='icon'){
                $list['icon'] = json_decode($val['value'],true);
            }elseif($val['title']=='pay'){
                $list['pay'] = json_decode($val['value'],true);
            }elseif($val['title']=='forward'){
                $list['forward'] = json_decode($val['value'],true);
            }elseif($val['title']=='version'){
                $list['version'] = json_decode($val['value'],true);
            }elseif($val['title']=='notice'){
                $list['notice'] = json_decode($val['value'],true);
            }elseif($val['title']=='fenxiao'){
                $fenxiao = json_decode($val['value'],true);
                $fenxiao['grade'][1]['pic'] = tomedia($fenxiao['grade'][1]['pic']);
                $fenxiao['grade'][2]['pic'] = tomedia($fenxiao['grade'][2]['pic']);
                $fenxiao['bgimg'] = tomedia($fenxiao['bgimg']);
            }
        }
        if($list['version']['number']==$_GPC['v']){
            $list['stake']=1;
        }else{
            $list['stake']=0;
        }
        foreach ($list as $key => $val) {
            foreach ($val as $k => $v) {
                if(strpos($v,'images') !== false || strpos($v,'audios') !== false || strpos($v,'videos') !== false){
                    $list[$key][$k] = tomedia($v);
                }
            }
        }

        $list['fenxiao'] = $fenxiao;
        return $this->result(0, '获取成功', $list);
    }


}