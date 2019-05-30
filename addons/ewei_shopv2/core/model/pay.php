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
		$params['out_trade_no'] = $data['out_order'];
		$params['total_fee'] = bcsub($data['money'],$data['rebate'],2) * 100;  //金额减去卡路里或者折扣宝的钱
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
		var_dump($result);exit;
		if($result['return_code'] == "success"){
			$array = array(
				'appId' => $result['appid'],
				'package' => 'prepay_id='.$result['prepay_id'],
				'nonceStr' => $result['nonce_str'],
				'timeStamp' => time(),
				'signType'=>'md5'
			);
			//第二次生成签名
			$string2 = $this->buildParams($array);
			$string2 .= "key=" . $config["apikey"];
			$array["paySign"] = strtoupper(md5(trim($string2)));    //再次签名
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
