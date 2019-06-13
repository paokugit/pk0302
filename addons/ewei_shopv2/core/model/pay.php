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
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

?>
