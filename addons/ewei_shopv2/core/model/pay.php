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
		$params = [];
		$params['appid'] = $config['sub_appid'];
		$params['mch_id'] = $config['sub_mch_id'];
		$params['nonce_str'] = $data['random'];
		$params['out_trade_no'] = time();
		$params['total_fee'] = $data['money'] * 100;
		$params['body'] = $data['body'];
		$params['spbill_create_ip'] = $data['ip'];
		$params['trade_type'] = 'JSAPI';
		$params['notify_url'] = $data['url'];
		$params['openid'] = $data['openid'];
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
		$string1 .= "key=" . $config["apikey"];
		$params["sign"] = strtoupper(md5(trim($string1)));    //签名
		$dat = array2xml($params);
		$response = ihttp_request("https://api.mch.weixin.qq.com/pay/unifiedorder", $dat);
		if( is_error($response) )
		{
			return $response;
		}
		$xml = simplexml_load_string(trim($response["content"]), "SimpleXMLElement", LIBXML_NOCDATA);
		$result = json_decode(json_encode($xml), true);
		return $result;
	}
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

?>
