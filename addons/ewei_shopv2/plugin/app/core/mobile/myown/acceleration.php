<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
//fbb

class Acceleration_EweiShopV2Page extends AppMobilePage{
    //加速宝列表信息
    public function index(){
        global $_W;
        global $_GPC;
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_member_accelerate")." order by id asc");
        app_error(0,$list);
    }
    //下单
    public function order(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member=pdo_get("ewei_shop_member",array("openid"=>$openid));
//         var_dump($_W['siteroot']);
        $id=$_GPC["id"];
        $log=pdo_get("ewei_shop_member_accelerate",array("id"=>$id));
        if (empty($log)){
            app_error(1,"加速宝id不正确");
        }
        if (!$member){
           app_error(1,"openid不正确");
        }
        //判断是否在加速期内
        $d=m("member")->acceleration($openid);
        if ($d["day"]!=0){
            app_error(2,$d);
        }
        //创建订单
        $order["ordersn"]="AC". date('YmdHis') . rand(10000000,99999999); 
        $order["openid"]=$openid;
        $order["acc_id"]=$log["id"];
        $order["money"]=$log["money"];
        $order["accelerate_day"]=$log["accelerate_day"];
        $order["duihuan"]=$log["duihuan"];
        $order["create_time"]=time();
        pdo_insert("ewei_shop_member_acceleration_order",$order);
        $order_id=pdo_insertid();
        
        if ($order_id){
            $payinfo = array( "openid" => $_W["openid_wa"], "title" =>"加速宝订单", "tid" => $order["ordersn"], "fee" => $order["money"] );
            $res["wx"] = $this->wxpay($payinfo, 14);
            $res["order_id"]=$order_id;
            if( !is_error($res["wx"]) )
            {
               app_error(0,$res); 
            }else{
                app_error(1,$res["wx"]);
            }
        }else{
            app_error(1,"生成订单失败");
        }
    }
    
    
    /**
     * 小程序微信支付
     * @param $params
     * @param int $type
     * @return array
     */
    public function wxpay($params, $type = 0)
    {
        global $_W;
        $data = m('common')->getSysset('app');
        $openid = ((empty($params['openid']) ? $_W['openid'] : $params['openid']));
        if (isset($openid) && strexists($openid, 'sns_wa_')) {
            $openid = str_replace('sns_wa_', '', $openid);
        }
        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);
        $package = array();
        $package['appid'] = $data['appid'];
        $package['mch_id'] = $sec['wxapp']['mchid'];
        $package['nonce_str'] = random(32);
        $package['body'] = $params['title'];
        $package['device_info'] = 'ewei_shopv2';
        $package['attach'] = $_W['uniacid'] . ':' . $type;
        $package['out_trade_no'] = $params['tid'];
        $package['total_fee'] = $params['fee'] * 100;
        $package['spbill_create_ip'] = CLIENT_IP;
        if (!(empty($params['goods_tag']))) {
            $package['goods_tag'] = $params['goods_tag'];
        }
        $package['notify_url'] = "https://paokucoin.com//app/ewei_shopv2_api.php?i=1&r=myown.acceleration.back&comefrom=wxapp";
        $package['trade_type'] = 'JSAPI';
        $package['openid'] = $openid;
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= $key . '=' . $v . '&';
        }
        $string1 .= 'key=' . $sec['wxapp']['apikey'];
        $package['sign'] = strtoupper(md5($string1));
        $dat = array2xml($package);
        load()->func('communication');
        $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
        if (is_error($response)) {
            return error(-1, $response['message']);
        }
        $xml = @simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if (strval($xml->return_code) == 'FAIL') {
            return error(-2, strval($xml->return_msg));
        }
        if (strval($xml->result_code) == 'FAIL') {
            return error(-3, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
        }
        $prepayid = $xml->prepay_id;
        $wOpt['appId'] = $data['appid'];
        $wOpt['timeStamp'] = TIMESTAMP . '';
        $wOpt['nonceStr'] = random(32);
        $wOpt['package'] = 'prepay_id=' . $prepayid;
        $wOpt['signType'] = 'MD5';
        ksort($wOpt, SORT_STRING);
        $string = '';
        foreach ($wOpt as $key => $v) {
            $string .= $key . '=' . $v . '&';
        }
        $string .= 'key=' . $sec['wxapp']['apikey'];
        $wOpt['paySign'] = strtoupper(md5($string));
        unset($wOpt['appId']);
        return $wOpt;
    }
    
    //微信支付回调
    public function back(){
        $input = file_get_contents('php://input');
        $isxml = true;
        $d["content"]=11;
        pdo_insert("ims_ewei_shop_member_cs",$d);
        if (!empty($input) && empty($_GET['out_trade_no'])) {
            $obj = isimplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
            $data = json_decode(json_encode($obj), true);
            if (empty($data)) {
                $result = array(
                    'return_code' => 'FAIL',
                    'return_msg' => ''
                );
                echo array2xml($result);
                exit;
            }
            if ($data['result_code'] != 'SUCCESS' || $data['return_code'] != 'SUCCESS') {
                $result = array(
                    'return_code' => 'FAIL',
                    'return_msg' => empty($data['return_msg']) ? $data['err_code_des'] : $data['return_msg']
                );
                echo array2xml($result);
                exit;
            }
            $get = $data;
        } else {
            $isxml = false;
            $get = $_GET;
        }
        //测试
        $d["content"]=$get;
        pdo_insert("ims_ewei_shop_member_cs",$d);
        $order_sn=$get["out_trade_no"];
        $order=pdo_get("ewei_shop_member_acceleration_order",array("ordersn"=>$order_sn));
        if ($order&&$order["status"]==0){
           if (pdo_update("ewei_shop_member_acceleration_order",array("status"=>1),array("ordersn"=>$order_sn))){
           $da["accelerate_start"]=date("Y-m-d");
           $da["accelerate_end"]=date("Y-m-d",strtotime("+".$order["accelerate_day"]." day"));
           $da["duihuan"]=$order["duihuan"];
           pdo_update("ewei_shop_member",$da,array("openid"=>$order["openid"]));
               echo success;
           }else {
               echo false;
           }
        }
//         echo date("Y-m-d",strtotime("+1 day"));
        
        echo false;
    }
    
   public function wx_back(){
       global $_W;
       global $_GPC;
       $order_id=$_GPC["order_id"];
       $order=pdo_get("ewei_shop_member_acceleration_order",array("id"=>$order_id));
       if ($order&&$order["status"]==0){
           if (pdo_update("ewei_shop_member_acceleration_order",array("status"=>1),array("id"=>$order_id))){
               $data["accelerate_start"]=date("Y-m-d");
               $data["accelerate_end"]=date("Y-m-d",strtotime("+".$order["accelerate_day"]." day"));
               $data["duihuan"]=$order["duihuan"];
               pdo_update("ewei_shop_member",$data,array("openid"=>$order["openid"]));
               app_error(0,"成功");
           }else {
               app_error(1,"失败");
           }
       }
       
       app_error(1,"不可重复更改");
   }
   
    //成功后
    public function back_message(){
        global $_W;
        global $_GPC;
        $order_id=$_GPC["order_id"];
        $openid=$_GPC["openid"];
        $order=pdo_get("ewei_shop_member_acceleration_order",array("id"=>$order_id));
        $member=pdo_get("ewei_shop_member",array("openid"=>$openid));
        $log["accelerate_end"]=$member["accelerate_end"];
        $log["money"]=$order["money"];
        app_error(0,$log);
    }
}