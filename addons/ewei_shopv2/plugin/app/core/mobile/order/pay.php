<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Pay_EweiShopV2Page extends AppMobilePage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$openid = $_W["openid"];
		$uniacid = $_W["uniacid"];
		$member = m("member")->getMember($openid, true);
		$orderid = intval($_GPC["id"]);
		if( empty($orderid) ) 
		{
			app_error(AppError::$ParamsError);
		}
		$order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid ));
		if( empty($order) ) 
		{
			app_error(AppError::$OrderNotFound);
			exit();
		}
		if( $order["status"] == -1 ) 
		{
			app_error(AppError::$OrderCannotPay);
		}
		else 
		{
			if( 1 <= $order["status"] ) 
			{
				app_error(AppError::$OrderAlreadyPay);
			}
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array( ":uniacid" => $uniacid, ":module" => "ewei_shopv2", ":tid" => $order["ordersn"] ));
		if( !empty($log) && $log["status"] != "0" ) 
		{
			app_error(AppError::$OrderAlreadyPay);
		}
		if( !empty($log) && $log["status"] == "0" ) 
		{
			pdo_delete("core_paylog", array( "plid" => $log["plid"] ));
			$log = NULL;
		}
		//lihanwen
        if($order['couponid']>0){
            $coupon_info = pdo_fetch("SELECT couponid FROM " . tablename("ims_ewei_shop_coupon_data") . " WHERE `uniacid`=:uniacid AND `id`=:id limit 1", array( ":uniacid" => $uniacid, ":id" => $order['couponid']));
            if($coupon_info['couponid']==2){//店主会员免费商品
                $order["price"] = 0.00;
            }
        }
		if( empty($log) ) 
		{
			$log = array( "uniacid" => $uniacid, "openid" => $member["uid"], "module" => "ewei_shopv2", "tid" => $order["ordersn"], "fee" => $order["price"], "status" => 0 );
			pdo_insert("core_paylog", $log);
			$plid = pdo_insertid();
		}
		$set = m("common")->getSysset(array( "shop", "pay" ));
		$credit = array( "success" => false );
		if( isset($set["pay"]) && $set["pay"]["credit"] == 1 ) 
		{
			$credit = array( "success" => true, "current" => $member["credit2"] );
		}
		$wechat = array( "success" => false );
		if( !empty($set["pay"]["wxapp"]) && 0 < $order["price"] && $this->iswxapp ) 
		{
			$tid = $order["ordersn"];
			if( !empty($order["ordersn2"]) ) 
			{
				$var = sprintf("%02d", $order["ordersn2"]);
				$tid .= "GJ" . $var;
			}
			$payinfo = array( "openid" => $_W["openid_wa"], "title" => $set["shop"]["name"] . "订单", "tid" => $tid, "fee" => $order["price"] );
			$res = $this->model->wxpay($payinfo, 14);
			if( !is_error($res) ) 
			{
				$wechat = array( "success" => true, "payinfo" => $res );
				if( !empty($res["package"]) && strexists($res["package"], "prepay_id=") ) 
				{
					$prepay_id = str_replace("prepay_id=", "", $res["package"]);
					pdo_update("ewei_shop_order", array( "wxapp_prepay_id" => $prepay_id ), array( "id" => $orderid, "uniacid" => $_W["uniacid"] ));
				}
			}
			else 
			{
				$wechat["payinfo"] = $res;
			}
		}
		if( !empty($order["addressid"]) ) 
		{
			$cash = array( "success" => $order["cash"] == 1 && isset($set["pay"]) && $set["pay"]["cash"] == 1 && $order["isverify"] == 0 && $order["isvirtual"] == 0 );
		}
		$alipay = array( "success" => false );
		if( !empty($set["pay"]["nativeapp_alipay"]) && 0 < $order["price"] && !$this->iswxapp ) 
		{
			$params = array( "out_trade_no" => $log["tid"], "total_amount" => $order["price"], "subject" => $set["shop"]["name"] . "订单", "body" => $_W["uniacid"] . ":0:NATIVEAPP" );
			$sec = m("common")->getSec();
			$sec = iunserializer($sec["sec"]);
			$alipay_config = $sec["nativeapp"]["alipay"];
			if( !empty($alipay_config) ) 
			{
				$res = $this->model->alipay_build($params, $alipay_config);
				$alipay = array( "success" => true, "payinfo" => $res );
			}
		}
		app_json(array( "order" => array( "id" => $order["id"], "ordersn" => $order["ordersn"], "price" => $order["price"], "title" => $set["shop"]["name"] . "订单" ), "credit" => $credit, "wechat" => $wechat, "alipay" => $alipay, "cash" => $cash ));
	}
	public function complete() 
	{
		global $_W;
		global $_GPC;
		$orderid = intval($_GPC["id"]);
		$uniacid = $_W["uniacid"];
		$openid = $_W["openid"];
		if( empty($orderid) ) 
		{
			app_error(AppError::$ParamsError);
		}
		$type = trim($_GPC["type"]);
		if( !in_array($type, array( "wechat", "alipay", "credit", "cash" )) ) 
		{
			app_error(AppError::$OrderPayNoPayType);
		}
		if( $type == "alipay" && empty($_GPC["alidata"]) ) 
		{
			app_error(AppError::$ParamsError, "支付宝返回数据错误");
		}
		$set = m("common")->getSysset(array( "shop", "pay" ));
		$set["pay"]["weixin"] = (!empty($set["pay"]["weixin_sub"]) ? 1 : $set["pay"]["weixin"]);
		$set["pay"]["weixin_jie"] = (!empty($set["pay"]["weixin_jie_sub"]) ? 1 : $set["pay"]["weixin_jie"]);
		$member = m("member")->getMember($openid, true);
		$order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid ));
		if( empty($order) ) 
		{
			app_error(AppError::$OrderNotFound);
		}
		if( 1 <= $order["status"] ) 
		{
			$this->success($orderid);
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array( ":uniacid" => $uniacid, ":module" => "ewei_shopv2", ":tid" => $order["ordersn"] ));
		if( empty($log) ) 
		{
			app_error(AppError::$OrderPayFail);
		}
		$order_goods = pdo_fetchall("select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf from  " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on og.goodsid = g.id " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array( ":uniacid" => $_W["uniacid"], ":orderid" => $orderid ));
		foreach( $order_goods as $data )
		{
			if( empty($data["status"]) || !empty($data["deleted"]) ) 
			{
				app_error(AppError::$OrderPayFail, $data["title"] . "<br/> 已下架!");
			}
			$unit = (empty($data["unit"]) ? "件" : $data["unit"]);
			if( 0 < $data["minbuy"] && $data["buycount"] < $data["minbuy"] ) 
			{
				app_error(AppError::$OrderCreateMinBuyLimit, $data["title"] . "<br/> " . $data["min"] . $unit . "起售!");
			}
			if( 0 < $data["maxbuy"] && $data["maxbuy"] < $data["buycount"] ) 
			{
				app_error(AppError::$OrderCreateOneBuyLimit, $data["title"] . "<br/> 一次限购 " . $data["maxbuy"] . $unit . "!");
			}
			if( 0 < $data["usermaxbuy"] ) 
			{
				$order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ", array( ":goodsid" => $data["goodsid"], ":uniacid" => $uniacid, ":openid" => $openid ));
				if( $data["usermaxbuy"] <= $order_goodscount ) 
				{
					app_error(AppError::$OrderCreateMaxBuyLimit, $data["title"] . "<br/> 最多限购 " . $data["usermaxbuy"] . $unit);
				}
			}
			if( $data["istime"] == 1 ) 
			{
				if( time() < $data["timestart"] ) 
				{
					app_error(AppError::$OrderCreateTimeNotStart, $data["title"] . "<br/> 限购时间未到!");
				}
				if( $data["timeend"] < time() ) 
				{
					app_error(AppError::$OrderCreateTimeEnd, $data["title"] . "<br/> 限购时间已过!");
				}
			}
			if( $data["buylevels"] != "" ) 
			{
				$buylevels = explode(",", $data["buylevels"]);
				if( !in_array($member["agentlevel"], $buylevels) ) 
				{
					app_error(AppError::$OrderCreateMemberLevelLimit, "您的会员等级无法购买<br/>" . $data["title"] . "!");
				}
			}
			if( $data["buygroups"] != "" ) 
			{
				$buygroups = explode(",", $data["buygroups"]);
				if( !in_array($member["groupid"], $buygroups) ) 
				{
					app_error(AppError::$OrderCreateMemberGroupLimit, "您所在会员组无法购买<br/>" . $data["title"] . "!");
				}
			}
			if( $data["totalcnf"] == 1 ) 
			{
				if( !empty($data["optionid"]) ) 
				{
					$option = pdo_fetch("select id,title,marketprice,goodssn,productsn,stock,`virtual` from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array( ":uniacid" => $uniacid, ":goodsid" => $data["goodsid"], ":id" => $data["optionid"] ));
					if( !empty($option) && $option["stock"] != -1 && empty($option["stock"]) ) 
					{
						app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>" . $option["title"] . " 库存不足!");
					}
				}
				else 
				{
					if( $data["stock"] != -1 && empty($data["stock"]) ) 
					{
						app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>" . " 库存不足!");
					}
				}
			}
		}
		if( $type == "cash" )
		{
			if( empty($set["pay"]["cash"]) )
			{
				app_error(AppError::$OrderPayFail, "未开启货到付款");
			}
			m("order")->setOrderPayType($order["id"], 3);
			$ret = array( );
			$ret["result"] = "success";
			$ret["type"] = "cash";
			$ret["from"] = "return";
			$ret["tid"] = $log["tid"];
			$ret["user"] = $order["openid"];
			$ret["fee"] = $order["price"];
			$ret["weid"] = $_W["uniacid"];
			$ret["uniacid"] = $_W["uniacid"];
			$pay_result = m("order")->payResult($ret);
			m("notice")->sendOrderMessage($orderid);
			$this->success($orderid);
		}
		$ps = array( );
		$ps["tid"] = $log["tid"];
		$ps["user"] = $openid;
		$ps["fee"] = $log["fee"];
		$ps["title"] = $log["title"];
		if( $type == "credit" )
		{
			if( empty($set["pay"]["credit"]) && 0 < $ps["fee"] )
			{
				app_error(AppError::$OrderPayFail, "未开启余额支付");
			}
			if( $ps["fee"] < 0 ) 
			{
				app_error(AppError::$OrderPayFail, "金额错误");
			}
			$credits = $this->member["credit2"];
			if( $credits < $ps["fee"] ) 
			{
				app_error(AppError::$OrderPayFail, "余额不足,请充值");
			}
            //礼包商品的支付
            if(pdo_exists('ewei_shop_gift_log',['order_sn'=>$order['ordersn']])){
                $gift = pdo_get('ewei_shop_gift_log',['order_sn'=>$order['ordersn']]);
                //查看这个礼包记录的周始末
                $week = m('util')->week($gift['createtime']);
                //如果当前时间在周始末  则可以领取  并更新状态为2 领取成功 如果不在也更新状态  为0 取消
                if(time() >= $week['start'] && $week['end'] >= time()){
                        pdo_update('ewei_shop_gift_log',['status'=>2],['order_sn'=>$order['ordersn']]);
                }else{
                    pdo_update('ewei_shop_gift_log',['status'=>0],['order_sn'=>$order['ordersn']]);
                    app_error(AppError::$OrderPayFail, "该订单是".$week['start']."--".$week['end']."礼包的商品，已经过了购买期");
                }
            }
			$fee = floatval($ps["fee"]);
			$shopset = m("common")->getSysset("shop");
			$result = m("member")->setCredit($openid, "credit2", 0 - $fee, array( $_W["member"]["uid"], $shopset["name"] . "APP 消费" . $fee ));
			$this->creditpay_log($openid, $fee, $orderid);
			if( is_error($result) ) 
			{
				app_error(AppError::$OrderPayFail, $result["message"]);
			}
			$record = array( );
			$record["status"] = "1";
			$record["type"] = "cash";
			pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
			$ret = array( );
			$ret["result"] = "success";
			$ret["type"] = $log["type"];
			$ret["from"] = "return";
			$ret["tid"] = $log["tid"];
			$ret["user"] = $log["openid"];
			$ret["fee"] = $log["fee"];
			$ret["weid"] = $log["weid"];
			$ret["uniacid"] = $log["uniacid"];
			@session_start();
			$_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
			m("order")->setOrderPayType($order["id"], 1);
			$pay_result = m("order")->payResult($ret);
			$this->success($orderid);
		}
		else 
		{
			if( $type == "wechat" ) 
			{
				if( empty($set["pay"]["wxapp"]) && $this->iswxapp ) 
				{
					app_error(AppError::$OrderPayFail, "未开启微信支付");
				}
				$ordersn = $order["ordersn"];
				if( !empty($order["ordersn2"]) ) 
				{
					$ordersn .= "GJ" . sprintf("%02d", $order["ordersn2"]);
				}
				$payquery = $this->model->isWeixinPay($ordersn, $order["price"]);
				if( !is_error($payquery) ) 
				{
					$record = array( );
					$record["status"] = "1";
					$record["type"] = "wechat";
					pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
					m("order")->setOrderPayType($order["id"], 21);
					$ret = array( );
					$ret["result"] = "success";
					$ret["type"] = "wechat";
					$ret["from"] = "return";
					$ret["tid"] = $log["tid"];
					$ret["user"] = $log["openid"];
					$ret["fee"] = $log["fee"];
					$ret["weid"] = $log["weid"];
					$ret["uniacid"] = $log["uniacid"];
					$ret["deduct"] = intval($_GPC["deduct"]) == 1;
					$pay_result = m("order")->payResult($ret);
					@session_start();
					$_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
					pdo_update("ewei_shop_order", array( "apppay" => 2 ), array( "id" => $order["id"] ));
					$this->success($orderid);
				}
				app_error(AppError::$OrderPayFail);
			}
			else 
			{
				if( $type == "alipay" ) 
				{
					if( empty($set["pay"]["nativeapp_alipay"]) ) 
					{
						app_error(AppError::$OrderPayFail, "未开启支付宝支付");
					}
					$sec = m("common")->getSec();
					$sec = iunserializer($sec["sec"]);
					$public_key = $sec["nativeapp"]["alipay"]["public_key"];
					if( empty($public_key) ) 
					{
						app_error(AppError::$OrderPayFail, "支付宝公钥为空");
					}
					$alidata = htmlspecialchars_decode($_GPC["alidata"]);
					$alidata = json_decode($alidata, true);
					$newalidata = $alidata["alipay_trade_app_pay_response"];
					$newalidata["sign_type"] = $alidata["sign_type"];
					$newalidata["sign"] = $alidata["sign"];
					$alisign = m("finance")->RSAVerify($newalidata, $public_key, false, true);
					if( $alisign ) 
					{
						$record = array( );
						$record["status"] = "1";
						$record["type"] = "alipay";
						pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
						$ret = array( );
						$ret["result"] = "success";
						$ret["type"] = "alipay";
						$ret["from"] = "return";
						$ret["tid"] = $log["tid"];
						$ret["user"] = $log["openid"];
						$ret["fee"] = $log["fee"];
						$ret["weid"] = $log["weid"];
						$ret["uniacid"] = $log["uniacid"];
						$ret["deduct"] = intval($_GPC["deduct"]) == 1;
						m("order")->setOrderPayType($order["id"], 22);
						$pay_result = m("order")->payResult($ret);
						pdo_update("ewei_shop_order", array( "apppay" => 2 ), array( "id" => $order["id"] ));
						$this->success($order["id"]);
					}
				}
			}
		}
	}
	protected function success($orderid) 
	{
		global $_W;
		global $_GPC;
		$openid = $_W["openid"];
		$uniacid = $_W["uniacid"];
		$member = m("member")->getMember($openid, true);
		if( empty($orderid) ) 
		{
			app_error(AppError::$ParamsError);
		}
		//订单绑定推荐会员
        if($_GPC['mid'] && !$member['agentid']){
            $agentmemberInfo = pdo_get('ewei_shop_member', array('id' =>$_GPC['mid']));
            if($agentmemberInfo)  m('member')->setagent(array('agentopenid'=>trim($agentmemberInfo["openid"]),'openid'=>$member['openid']));
        }
		$order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid ));
		$merchid = $order["merchid"];
		$goods = pdo_fetchall("select og.goodsid,g.cates,og.price,g.title,g.thumb,og.total,g.credit,og.optionid,og.optionname as optiontitle,g.isverify,g.storeids from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array( ":uniacid" => $uniacid, ":orderid" => $orderid ));
//        if($order['status']==3){
//            m('order')->reward($goods,$order['openid'],$order);//lihanwen 会员推荐返佣金
//        }

		$address = false;
		if( !empty($order["addressid"]) ) 
		{
			$address = iunserializer($order["address"]);
			if( !is_array($address) ) 
			{
				$address = pdo_fetch("select * from  " . tablename("ewei_shop_member_address") . " where id=:id limit 1", array( ":id" => $order["addressid"] ));
			}
		}
		$carrier = @iunserializer($order["carrier"]);
		if( !is_array($carrier) || empty($carrier) ) 
		{
			$carrier = false;
		}
		$store = false;
		if( !empty($order["storeid"]) ) 
		{
			if( 0 < $merchid ) 
			{
				$store = pdo_fetch("select * from  " . tablename("ewei_shop_merch_store") . " where id=:id limit 1", array( ":id" => $order["storeid"] ));
			}
			else 
			{
				$store = pdo_fetch("select * from  " . tablename("ewei_shop_store") . " where id=:id limit 1", array( ":id" => $order["storeid"] ));
			}
		}
		$stores = false;
		if( $order["isverify"] ) 
		{
			$storeids = array( );
			foreach( $goods as $g ) 
			{
				if( !empty($g["storeids"]) ) 
				{
					$storeids = array_merge(explode(",", $g["storeids"]), $storeids);
				}
			}
			if( empty($storeids) ) 
			{
				if( 0 < $merchid ) 
				{
					$stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where  uniacid=:uniacid and merchid=:merchid and status=1 and `type` in (2,3)", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merchid ));
				}
				else 
				{
					$stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where  uniacid=:uniacid and status=1 and `type` in (2,3)", array( ":uniacid" => $_W["uniacid"] ));
				}
			}
			else 
			{
				if( 0 < $merchid ) 
				{
					$stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and merchid=:merchid and status=1", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merchid ));
				}
				else 
				{
					$stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and status=1", array( ":uniacid" => $_W["uniacid"] ));
				}
			}
		}
		$text = "";
		if( !empty($address) ) 
		{
			$text = "您的包裹整装待发";
		}
		if( !empty($order["dispatchtype"]) && empty($order["isverify"]) ) 
		{
			$text = "您可以到您选择的自提点取货了";
		}
		if( !empty($order["isverify"]) ) 
		{
			$text = "您可以到适用门店去使用了";
		}
		if( !empty($order["virtual"]) ) 
		{
			$text = "您购买的商品已自动发货";
		}
		if( !empty($order["isvirtual"]) && empty($order["virtual"]) ) 
		{
			if( !empty($order["isvirtualsend"]) ) 
			{
				$text = "您购买的商品已自动发货";
			}
			else 
			{
				$text = "您已经支付成功";
			}
		}
		if( $_GPC["result"] == "seckill_refund" ) 
		{
			$icon = "e75a";
		}
		else 
		{
			if( !empty($address) ) 
			{
				$icon = "e623";
			}
			if( !empty($order["dispatchtype"]) && empty($order["isverify"]) ) 
			{
				$icon = "e7b9";
			}
			if( !empty($order["isverify"]) ) 
			{
				$icon = "e7b9";
			}
			if( !empty($order["virtual"]) ) 
			{
				$icon = "e7a1";
			}
			if( !empty($order["isvirtual"]) && empty($order["virtual"]) ) 
			{
				if( !empty($order["isvirtualsend"]) ) 
				{
					$icon = "e7a1";
				}
				else 
				{
					$icon = "e601";
				}
			}
		}
		$seckill_color = "";
		if( 0 < $order["seckilldiscountprice"] ) 
		{
			$where = " WHERE uniacid=:uniacid AND type = 5";
			$params = array( ":uniacid" => $_W["uniacid"] );
			$page = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_wxapp_page") . $where . " LIMIT 1 ", $params);
			if( !empty($page) ) 
			{
				$data = base64_decode($page["data"]);
				$diydata = json_decode($data, true);
				$seckill_color = $diydata["page"]["seckill"]["color"];
			}
		}
		$result = array("goods"=>$goods[0], "order" => array( "id" => $orderid, "isverify" => $order["isverify"], "virtual" => $order["virtual"], "isvirtual" => $order["isvirtual"], "isvirtualsend" => $order["isvirtualsend"], "virtualsend_info" => $order["virtualsend_info"], "virtual_str" => $order["virtual_str"], "status" => ($order["paytype"] == 3 ? "订单提交支付" : "订单支付成功"), "text" => $text, "price" => $order["price"] ), "paytype" => ($order["paytype"] == 3 ? "需到付" : "实付金额"), "carrier" => $carrier, "address" => $address, "stores" => $stores, "store" => $store, "icon" => $icon, "seckill_color" => $seckill_color );
		$result['goods'] = $goods[0];
		if( !empty($order["virtual"]) && !empty($order["virtual_str"]) )
		{
			$result["ordervirtual"] = m("order")->getOrderVirtual($order);
			$result["virtualtemp"] = pdo_fetch("SELECT linktext, linkurl FROM " . tablename("ewei_shop_virtual_type") . " WHERE id=:id AND uniacid=:uniacid LIMIT 1", array( ":id" => $order["virtual"], ":uniacid" => $_W["uniacid"] ));
		}
		app_json($result);
	}
	protected function str($str) 
	{
		$str = str_replace("\"", "", $str);
		$str = str_replace("'", "", $str);
		return $str;
	}
	protected function creditpay_log($openid = "", $fee = 0, $orderid = 0) 
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W["uniacid"];
		if( empty($openid) ) 
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
		$log_data = array( "uniacid" => $uniacid, "openid" => $openid, "type" => 2, "logno" => $order["ordersn"], "title" => "小程序商城消费", "createtime" => TIMESTAMP, "status" => 1, "money" => 0 - $fee, "rechargetype" => "wxapp", "remark" => "小程序端余额支付" );
		pdo_insert("ewei_shop_member_log", $log_data);
	}

    public function complete2()
    {
        global $_W;
        global $_GPC;
        $orderid = intval($_GPC["id"]);
        $uniacid = $_W["uniacid"];
        $openid = $_W["openid"];
        if( empty($orderid) )
        {
            echo 1;
            app_error(AppError::$ParamsError);
        }
        $type = trim($_GPC["type"]);
        if( !in_array($type, array( "wechat", "alipay", "credit", "cash" )) )
        {
            echo 2;
            app_error(AppError::$OrderPayNoPayType);
        }
        if( $type == "alipay" && empty($_GPC["alidata"]) )
        {
            echo 3;
            app_error(AppError::$ParamsError, "支付宝返回数据错误");
        }
        $set = m("common")->getSysset(array( "shop", "pay" ));
        echo 4;
        $set["pay"]["weixin"] = (!empty($set["pay"]["weixin_sub"]) ? 1 : $set["pay"]["weixin"]);
        $set["pay"]["weixin_jie"] = (!empty($set["pay"]["weixin_jie_sub"]) ? 1 : $set["pay"]["weixin_jie"]);
        $member = m("member")->getMember($openid, true);
        echo 5;
        $order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid ));
        if( empty($order) )
        {
            echo 6;
            app_error(AppError::$OrderNotFound);
        }
        if( 1 <= $order["status"] )
        {
            echo 7;
            $this->success($orderid);
        }
        $log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array( ":uniacid" => $uniacid, ":module" => "ewei_shopv2", ":tid" => $order["ordersn"] ));
        if( empty($log) )
        {
            echo 8;
            app_error(AppError::$OrderPayFail);
        }
        $order_goods = pdo_fetchall("select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf from  " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on og.goodsid = g.id " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array( ":uniacid" => $_W["uniacid"], ":orderid" => $orderid ));
        echo 9;
        foreach( $order_goods as $data )
        {
            if( empty($data["status"]) || !empty($data["deleted"]) )
            {
                echo 10;
                app_error(AppError::$OrderPayFail, $data["title"] . "<br/> 已下架!");
            }
            $unit = (empty($data["unit"]) ? "件" : $data["unit"]);
            if( 0 < $data["minbuy"] && $data["buycount"] < $data["minbuy"] )
            {
                echo 11;
                app_error(AppError::$OrderCreateMinBuyLimit, $data["title"] . "<br/> " . $data["min"] . $unit . "起售!");
            }
            if( 0 < $data["maxbuy"] && $data["maxbuy"] < $data["buycount"] )
            {
                echo 12;
                app_error(AppError::$OrderCreateOneBuyLimit, $data["title"] . "<br/> 一次限购 " . $data["maxbuy"] . $unit . "!");
            }
            if( 0 < $data["usermaxbuy"] )
            {
                echo 13;
                $order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ", array( ":goodsid" => $data["goodsid"], ":uniacid" => $uniacid, ":openid" => $openid ));
                if( $data["usermaxbuy"] <= $order_goodscount )
                {
                    app_error(AppError::$OrderCreateMaxBuyLimit, $data["title"] . "<br/> 最多限购 " . $data["usermaxbuy"] . $unit);
                }
            }
            if( $data["istime"] == 1 )
            {
                echo 14;
                if( time() < $data["timestart"] )
                {
                    app_error(AppError::$OrderCreateTimeNotStart, $data["title"] . "<br/> 限购时间未到!");
                }
                if( $data["timeend"] < time() )
                {
                    app_error(AppError::$OrderCreateTimeEnd, $data["title"] . "<br/> 限购时间已过!");
                }
            }
            if( $data["buylevels"] != "" )
            {
                echo 15;
                $buylevels = explode(",", $data["buylevels"]);
                if( !in_array($member["level"], $buylevels) )
                {
                    app_error(AppError::$OrderCreateMemberLevelLimit, "您的会员等级无法购买<br/>" . $data["title"] . "!");
                }
            }
            if( $data["buygroups"] != "" )
            {
                echo 16;
                $buygroups = explode(",", $data["buygroups"]);
                if( !in_array($member["groupid"], $buygroups) )
                {
                    app_error(AppError::$OrderCreateMemberGroupLimit, "您所在会员组无法购买<br/>" . $data["title"] . "!");
                }
            }
            if( $data["totalcnf"] == 1 )
            {
                if( !empty($data["optionid"]) )
                {
                    echo 17;
                    $option = pdo_fetch("select id,title,marketprice,goodssn,productsn,stock,`virtual` from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array( ":uniacid" => $uniacid, ":goodsid" => $data["goodsid"], ":id" => $data["optionid"] ));
                    if( !empty($option) && $option["stock"] != -1 && empty($option["stock"]) )
                    {
                        app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>" . $option["title"] . " 库存不足!");
                    }
                }
                else
                {
                    if( $data["stock"] != -1 && empty($data["stock"]) )
                    {
                        echo 18;
                        app_error(AppError::$OrderCreateStockError, $data["title"] . "<br/>"  . " 库存不足!");
                    }
                }
            }
        }
        if( $type == "cash" )
        {
            echo 19;
            if( empty($set["pay"]["cash"]) )
            {
                echo 20;
                app_error(AppError::$OrderPayFail, "未开启货到付款");
            }
            echo 21;
            m("order")->setOrderPayType($order["id"], 3);
            $ret = array( );
            $ret["result"] = "success";
            $ret["type"] = "cash";
            $ret["from"] = "return";
            $ret["tid"] = $log["tid"];
            $ret["user"] = $order["openid"];
            $ret["fee"] = $order["price"];
            $ret["weid"] = $_W["uniacid"];
            $ret["uniacid"] = $_W["uniacid"];
            echo 22;
            $pay_result = m("order")->payResult($ret);
            echo 23;
            m("notice")->sendOrderMessage($orderid);
            echo 24;
            $this->success($orderid);
            echo 25;
        }
        $ps = array( );
        $ps["tid"] = $log["tid"];
        $ps["user"] = $openid;
        $ps["fee"] = $log["fee"];
        $ps["title"] = $log["title"];
        if( $type == "credit" )
        {
            if( empty($set["pay"]["credit"]) && 0 < $ps["fee"] )
            {
                echo 26;
                app_error(AppError::$OrderPayFail, "未开启余额支付");
            }
            if( $ps["fee"] < 0 )
            {
                echo 27;
                app_error(AppError::$OrderPayFail, "金额错误");
            }
            $credits = $this->member["credit2"];
            if( $credits < $ps["fee"] )
            {
                echo 28;
                app_error(AppError::$OrderPayFail, "余额不足,请充值");
            }
            $fee = floatval($ps["fee"]);
            $shopset = m("common")->getSysset("shop");
            $result = m("member")->setCredit($openid, "credit2", 0 - $fee, array( $_W["member"]["uid"], $shopset["name"] . "APP 消费" . $fee ));
            $this->creditpay_log($openid, $fee, $orderid);
            if( is_error($result) )
            {
                echo 29;
                app_error(AppError::$OrderPayFail, $result["message"]);
            }
            $record = array( );
            $record["status"] = "1";
            $record["type"] = "cash";
            echo 30;
            pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
            $ret = array( );
            $ret["result"] = "success";
            $ret["type"] = $log["type"];
            $ret["from"] = "return";
            $ret["tid"] = $log["tid"];
            $ret["user"] = $log["openid"];
            $ret["fee"] = $log["fee"];
            $ret["weid"] = $log["weid"];
            $ret["uniacid"] = $log["uniacid"];
            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            echo 31;
            m("order")->setOrderPayType($order["id"], 1);
            echo 32;
            $pay_result = m("order")->payResult($ret);
            echo 33;
            $this->success($orderid);
        }
        else
        {
            if( $type == "wechat" )
            {
                if( empty($set["pay"]["wxapp"]) && $this->iswxapp )
                {
                    app_error(AppError::$OrderPayFail, "未开启微信支付");
                }
                $ordersn = $order["ordersn"];
                if( !empty($order["ordersn2"]) )
                {
                    $ordersn .= "GJ" . sprintf("%02d", $order["ordersn2"]);
                }
                $payquery = $this->model->isWeixinPay($ordersn, $order["price"]);
                if( !is_error($payquery) )
                {
                    $record = array( );
                    $record["status"] = "1";
                    $record["type"] = "wechat";
                    pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
                    m("order")->setOrderPayType($order["id"], 21);
                    $ret = array( );
                    $ret["result"] = "success";
                    $ret["type"] = "wechat";
                    $ret["from"] = "return";
                    $ret["tid"] = $log["tid"];
                    $ret["user"] = $log["openid"];
                    $ret["fee"] = $log["fee"];
                    $ret["weid"] = $log["weid"];
                    $ret["uniacid"] = $log["uniacid"];
                    $ret["deduct"] = intval($_GPC["deduct"]) == 1;
                    $pay_result = m("order")->payResult($ret);
                    @session_start();
                    $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
                    pdo_update("ewei_shop_order", array( "apppay" => 2 ), array( "id" => $order["id"] ));
                    $this->success($orderid);
                }
                app_error(AppError::$OrderPayFail);
            }
            else
            {
                if( $type == "alipay" )
                {
                    if( empty($set["pay"]["nativeapp_alipay"]) )
                    {
                        app_error(AppError::$OrderPayFail, "未开启支付宝支付");
                    }
                    $sec = m("common")->getSec();
                    $sec = iunserializer($sec["sec"]);
                    $public_key = $sec["nativeapp"]["alipay"]["public_key"];
                    if( empty($public_key) )
                    {
                        app_error(AppError::$OrderPayFail, "支付宝公钥为空");
                    }
                    $alidata = htmlspecialchars_decode($_GPC["alidata"]);
                    $alidata = json_decode($alidata, true);
                    $newalidata = $alidata["alipay_trade_app_pay_response"];
                    $newalidata["sign_type"] = $alidata["sign_type"];
                    $newalidata["sign"] = $alidata["sign"];
                    $alisign = m("finance")->RSAVerify($newalidata, $public_key, false, true);
                    if( $alisign )
                    {
                        $record = array( );
                        $record["status"] = "1";
                        $record["type"] = "wechat";
                        pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
                        $ret = array( );
                        $ret["result"] = "success";
                        $ret["type"] = "alipay";
                        $ret["from"] = "return";
                        $ret["tid"] = $log["tid"];
                        $ret["user"] = $log["openid"];
                        $ret["fee"] = $log["fee"];
                        $ret["weid"] = $log["weid"];
                        $ret["uniacid"] = $log["uniacid"];
                        $ret["deduct"] = intval($_GPC["deduct"]) == 1;
                        m("order")->setOrderPayType($order["id"], 22);
                        $pay_result = m("order")->payResult($ret);
                        pdo_update("ewei_shop_order", array( "apppay" => 2 ), array( "id" => $order["id"] ));
                        $this->success($order["id"]);
                    }
                }
            }
        }
    }

    public function  shop_reward(){
	    $res = m('member')->shop_reward("sns_wa_owRAK49usbCooCJGp-81VKLAFJME",5);
	    var_dump($res);
    }


}
?>