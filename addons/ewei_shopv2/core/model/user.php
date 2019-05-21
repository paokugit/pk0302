<?php
class User_EweiShopV2Model
{
	private $sessionid;

	public function __construct()
	{
		global $_W;
		$this->sessionid = '__cookie_ewei_shop_201507200000_' . $_W['uniacid'];
	}

	public function getOpenid()
	{
		$userinfo = $this->getInfo(false, true);
		return $userinfo['openid'];
	}

	public function getInfo($base64 = false, $debug = false)
	{
		global $_W;
		global $_GPC;
		$userinfo = array();

		if (EWEI_SHOPV2_DEBUG) {
			$userinfo = array('openid' => 'oT-ihv9XGkJbX9owJiLZcZPAJcog', 'nickname' => '狸小狐', 'headimgurl' => 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png', 'province' => '山东', 'city' => '青岛');
			$userinfo = array('openid' => 'oT-ihv9XGkJbX9owJiLZcZPAJcog', 'nickname' => '狸小狐', 'headimgurl' => 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png', 'province' => '山东', 'city' => '青岛');
			$userinfo = array('openid' => 'oT-ihv9XGkJbX9owJiLZcZPAJcog', 'nickname' => '狸小狐', 'headimgurl' => 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png', 'province' => '山东', 'city' => '青岛');
			$userinfo = array('openid' => 'oT-ihv9XGkJbX9owJiLZcZPAJcog', 'nickname' => '狸小狐', 'headimgurl' => 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png', 'province' => '山东', 'city' => '青岛');
			$userinfo = array('openid' => 'oT-ihv9XGkJbX9owJiLZcZPAJcog', 'nickname' => '狸小狐', 'headimgurl' => 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png', 'province' => '山东', 'city' => '青岛');
		}
		else {
			load()->model('mc');
			$userinfo = mc_oauth_userinfo();
			$need_openid = true;

			if ($_W['container'] != 'wechat') {
				if ($_GPC['do'] == 'order' && $_GPC['p'] == 'pay') {
					$need_openid = false;
				}

				if ($_GPC['do'] == 'member' && $_GPC['p'] == 'recharge') {
					$need_openid = false;
				}

				if ($_GPC['do'] == 'plugin' && $_GPC['p'] == 'article' && $_GPC['preview'] == '1') {
					$need_openid = false;
				}
			}
		}

		if ($base64) {
			return urlencode(base64_encode(json_encode($userinfo)));
		}

		return $userinfo;
	}

	/**
     * 判断是否关注
     * @param type $openid
     * @return type
     */
	public function followed($openid = '')
	{
		global $_W;
		$followed = !empty($openid);

		if ($followed) {
			$mf = pdo_fetch('select follow from ' . tablename('mc_mapping_fans') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':openid' => $openid, ':uniacid' => $_W['uniacid']));
			$followed = $mf['follow'] == 1;
		}

		return $followed;
	}

	/**
	 * 微信小程序 提现到零钱
	 * @param array $params
	 * @return array|false|mixed|resource
	 */
	public function get_transfers($params = [])
	{
		global $_W;
		//获取微信商户配置
		$payment = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_payment") . " WHERE uniacid=:uniacid AND id=:id", array( ":uniacid" => $_W["uniacid"], ":id" => 1));
		$wechat = array(
			"sub_appid" => (!empty($payment["sub_appid"]) ? trim($payment["sub_appid"]) : ""),
			"sub_mch_id" => trim($payment["sub_mch_id"]),
			"apikey" => trim($payment["apikey"]),
		);
		$package = array( );
		$package["mch_appid"] = trim($wechat["sub_appid"]);   //商户账号appid
		$package["mchid"] = trim($wechat["sub_mch_id"]);   //商户号
		$package["openid"] = (empty($params["openid"]) ? trim($_W["openid"]) : trim($params["openid"]));  //用户openid
		$package["nonce_str"] = random(32);    //随机字符串
		$package["desc"] = trim($params["desc"]);    //备注
		$package["device_info"] = "ewei_shopv2";      //设备号
		$package["check_name"] = "FORCE_CHECK";    //校验用户姓名选项
		$package["re_user_name"] = trim($params["user_name"]);    //校验用户姓名  传的姓名和微信实名认证的姓名进行校验
		$package["partner_trade_no"] = trim($params["order_sn"]);    //商户订单号
		$package["amount"] = $params["fee"] * 100;   //金额
		$package["spbill_create_ip"] = CLIENT_IP;     //IP地址
		ksort($package, SORT_STRING);
		$string1 = "";
		foreach( $package as $key => $v )
		{
			if( empty($v) )
			{
				continue;
			}
			$string1 .= (string) $key . "=" . $v . "&";
		}
		$string1 .= "key=" . $wechat["apikey"];
		$package["sign"] = strtoupper(md5(trim($string1)));    //签名
		$dat = array2xml($package);
		$response = ihttp_request("https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers", $dat);
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
