<?php
class Pay_EweiShopV2Model
{
	private $qpay;

	public function __construct()
	{
		$this->qpay = p('qpay');
	}

	public function __call($method, $args)
	{
		if (!empty($this->qpay) && method_exists($this->qpay, $method)) {
			return call_user_func_array(array($this->qpay, $method), $args);
		}

		return error(-1, '没有全付通支付!');
	}

	/**
	 * 小程序支付
	 * @param $data
	 * @return mixed
	 */
	public function pay($data)
	{
		global $_W;
		$config = pdo_fetch('select * from '.tablename('ewei_shop_payment').' where id=:id and uniacid=:uniacid',[':id'=>1,':uniacid'=>$_W['uniacid']]);
		$wxpay = m('common')->getSysset('app');   //用来获得小程序的APPID
		$params = [];
		$params['appid'] = $wxpay['appid'];
		$params['mch_id'] = $config['sub_mch_id'];
		$params['nonce_str'] = $data['random'];
		$params['out_trade_no'] = $data['out_order'];
		$params['total_fee'] = $data['money'] * 100;
		$params['body'] = $data['body'];
		$params['spbill_create_ip'] = $data['ip'];
		$params['trade_type'] = 'JSAPI';
		$params['notify_url'] = $data['url'];
		$params['openid'] = $data['openid'];
		$string1 = $this->buildParams($params);
		$string1 .= "key=" . $config["apikey"];
		$params["sign"] = strtoupper(md5(trim($string1)));    //签名
		$data = array2xml($params);
		$response = ihttp_request("https://api.mch.weixin.qq.com/pay/unifiedorder", $data);
		if( is_error($response) )
		{
			return $response;
		}
		$xml = simplexml_load_string(trim($response["content"]), "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($xml), true);
		if (strval($result['return_code']) == 'FAIL') {
			return error(-2, strval($result['return_msg']));
		}
		if (strval($result['result_code']) == 'FAIL') {
			return error(-3, strval($result['err_code']) . ': ' . strval($result['err_code_des']));
		}
		if($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS"){
			pdo_update('ewei_shop_order',['wxapp_prepay_id'=>$result['prepay_id']],['ordersn'=>$params['out_trade_no']]);
			$array = array(
				'appId' => $result['appid'],
				'package' => 'prepay_id='.$result['prepay_id'],
				'nonceStr' => $result['nonce_str'],
				'timeStamp' => (string)time(),
				'signType'=>'MD5'
			);
			//第二次生成签名
			$string2 = $this->buildParams($array);
			$string2 .= "key=" . $config["apikey"];
			$array["paySign"] = strtoupper(md5(trim($string2)));    //再次签名
			unset($array['appId']);   //删除数组中的APPID
			return $array;
		}
	}

	/**
	 * @param $params
	 * @return string
	 */
	public function buildParams($params)
	{
		ksort($params, SORT_STRING);
		$string1 = "";
		foreach( $params as $key => $v )
		{
			if( empty($v) )
			{
				continue;
			}
			$string1 .= (string) $key . "=" . $v . "&";
		}
		return $string1;
	}

    /**
     * @param string $openid
     * @param int $fee
     * @param int $orderid
     * @param string $type
     * @return bool
     */
    public function creditpay_log($openid = "", $fee = 0, $orderid = 0,$type = "credit")
    {
        global $_W;
        $member = m('member')->getMember($openid);
        $uniacid = $_W["uniacid"];
        if( empty($member) )
        {
            return false;
        }
        if( empty($fee) )
        {
            return false;
        }
        if( empty($orderid) )
        {
            return false;
        }
        $order = pdo_fetch("select id,ordersn from " . tablename("ewei_shop_order") . " where id=:id AND uniacid=:uniacid LIMIT 1", array( ":id" => $orderid, ":uniacid" => $uniacid ));
        if( empty($order) )
        {
            return false;
        }
        $log_data = array( "uniacid" => $uniacid, "openid" => $member['openid'],"user_id" => $member['id'], "type" => 2, "logno" => $order["ordersn"], "title" => "小程序商城消费", "createtime" => TIMESTAMP, "status" => 1, "money" => 0 - $fee, "rechargetype" => "wxapp", "remark" => $type == "credit" ? "小程序端余额支付" :"小程序端RVC支付");
        if($type == 'credit'){
            pdo_insert("ewei_shop_member_log", $log_data);
        }elseif ($type == 'RVC'){
            pdo_insert("ewei_shop_member_RVClog", $log_data);
        }
    }

    /**
     * APP微信支付
     * @param $data
     * @param $type
     * @return mixed
     */
    public function wxchat_apppay($data,$type = 0)
    {
        global $_W;
        $sec = m("common")->getSec();
        $config = iunserializer($sec["sec"])["app_wechat"];
        $params = [];
        $params['appid'] = $config['appid'];
        $params['mch_id'] = $config['merchid'];
        $params['nonce_str'] = $data['random'];
        $params['out_trade_no'] = $data['out_order'];
        $params['total_fee'] = $data['money'] * 100;
        $params['body'] = $data['body'];
        $package['attach'] = $_W['uniacid'] . ':' . $type;
        $params['spbill_create_ip'] = CLIENT_IP;
        $params['trade_type'] = 'APP';
        $params['notify_url'] = $data['url'];
        $string1 = $this->buildParams($params);
        $string1 .= "key=" . $config["apikey"];
        $params["sign"] = strtoupper(md5(trim($string1)));    //签名
        $data = array2xml($params);
        $response = ihttp_request("https://api.mch.weixin.qq.com/pay/unifiedorder", $data);
        if( is_error($response) )
        {
            return $response;
        }
        $xml = simplexml_load_string(trim($response["content"]), "SimpleXMLElement", LIBXML_NOCDATA);
        $result = json_decode(json_encode($xml), true);
        if (strval($result['return_code']) == 'FAIL') {
            return error(-2, strval($result['return_msg']));
        }
        if (strval($result['result_code']) == 'FAIL') {
            return error(-3, strval($result['err_code']) . ': ' . strval($result['err_code_des']));
        }
        if($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS"){
            pdo_update('ewei_shop_order',['wxapp_prepay_id'=>$result['prepay_id']],['order_sn'=>$params['out_trade_no']]);
            $array = [
                'prepayid' => $result['prepay_id'],
                'appId' => $result['appid'],
                'partnerid' => $result['mch_id'],
                'package' => 'Sign=WXPay',
                'noncestr' => $result['nonce_str'],
                'timestamp' => (string)time(),
            ];
            //第二次生成签名
            $string2 = $this->buildParams($array);
            $string2 .= "key=" . $config["apikey"];
            $array["paySign"] = strtoupper(md5(trim($string2)));    //再次签名
            unset($array['appId']);   //删除数组中的APPID
            return $array;
        }
    }

    /**
     * @param $out_trade_no
     * @param int $money
     * @return array|bool
     */
    public function isWeixinPay($out_trade_no, $money = 0)
    {
        global $_W;
        global $_GPC;
        $data = m('common')->getSysset('app');
        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $pars = array();
        $pars['appid'] = $data['appid'];
        $pars['mch_id'] = $sec['wxapp']['mchid'];
        $pars['nonce_str'] = random(32);
        $pars['out_trade_no'] = $out_trade_no;
        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            $string1 .= $k . '=' . $v . '&';
        }
        $string1 .= 'key=' . $sec['wxapp']['apikey'];
        $pars['sign'] = strtoupper(md5($string1));
        $xml = array2xml($pars);
        load()->func('communication');
        $resp = ihttp_post($url, $xml);
        if (is_error($resp)) {
            return error(-2, $resp['message']);
        }
        if (empty($resp['content'])) {
            return error(-2, '网络错误');
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
        $dom = new DOMDocument();
        if ($dom->loadXML($xml)) {
            $xpath = new DOMXPath($dom);
            $code = $xpath->evaluate('string(//xml/return_code)');
            $ret = $xpath->evaluate('string(//xml/result_code)');
            $trade_state = $xpath->evaluate('string(//xml/trade_state)');
            if ((strtolower($code) == 'success') && (strtolower($ret) == 'success') && (strtolower($trade_state) == 'success')) {
                $total_fee = intval($xpath->evaluate('string(//xml/total_fee)')) / 100;
                if ($total_fee != $money) {
                    return error(-1, '金额出错');
                }
                return true;
            }
            if ($xpath->evaluate('string(//xml/return_msg)') == $xpath->evaluate('string(//xml/err_code_des)')) {
                $error = $xpath->evaluate('string(//xml/return_msg)');
            } else {
                $error = $xpath->evaluate('string(//xml/return_msg)') . ' | ' . $xpath->evaluate('string(//xml/err_code_des)');
            }
            return error(-2, $error);
        }
        return error(-1, '未知错误');
    }

    /**
	 * APP支付宝支付
     * @param $params
     * @param array $config
     * @return array|bool|string
     */
    public function alipay_build($params, $config = array())
    {
        global $_W;
        $arr = array('app_id' => $config['appid'], 'method' => 'alipay.trade.app.pay', 'format' => 'JSON', 'charset' => 'utf-8', 'sign_type' => 'RSA2', 'timestamp' => date('Y-m-d H:i:s', time()), 'version' => '1.0', 'notify_url' => $_W['siteroot'] . 'addons/ewei_shopv2/payment/alipay/notify.php', 'biz_content' => json_encode(array('timeout_express' => '90m', 'product_code' => 'QUICK_MSECURITY_PAY', 'total_amount' => $params['total_amount'], 'subject' => $params['subject'], 'body' => $params['body'], 'out_trade_no' => $params['out_trade_no'])));
        ksort($arr);
        $string1 = '';
        foreach ($arr as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= $key . '=' . $v . '&';
        }
        $string1 = rtrim($string1, '&');
        //$pkeyid = openssl_pkey_get_private(m('common')->chackKey($config['private_key'], false));
        $pkeyid = openssl_pkey_get_private(m('common')->chackKey($config['private_key_rsa2'], false));
        if ($pkeyid === false) {
            return error(-1, '提供的私钥格式不对');
        }
        $signature = '';
        openssl_sign($string1, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
        openssl_free_key($pkeyid);
        $signature = base64_encode($signature);
        $arr['sign'] = $signature;
        return http_build_query($arr);
    }
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

?>
