<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
class Member_EweiShopV2Model 
{
	public function getInfo($openid = "") 
	{
		global $_W;
		$uid = intval($openid);
		if( $uid == 0 ) 
		{
			$info = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
			if( empty($info) ) 
			{
				if( strexists($openid, "sns_qq_") ) 
				{
					$openid = str_replace("sns_qq_", "", $openid);
					$condition = " openid_qq=:openid ";
					$bindsns = "qq";
				}
				else 
				{
					if( strexists($openid, "sns_wx_") ) 
					{
						$openid = str_replace("sns_wx_", "", $openid);
						$condition = " openid_wx=:openid ";
						$bindsns = "wx";
					}
					else 
					{
						if( strexists($openid, "sns_wa_") ) 
						{
							$openid = str_replace("sns_wa_", "", $openid);
							$condition = " openid_wa=:openid ";
							$bindsns = "wa";
						}
					}
				}
				if( !empty($condition) ) 
				{
					$info = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where " . $condition . "  and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
					if( !empty($info) ) 
					{
						$info["bindsns"] = $bindsns;
					}
				}
			}
		}
		else 
		{
			$info = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id  and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":id" => $uid ));
		}
		if( !empty($info["uid"]) ) 
		{
			load()->model("mc");
			$uid = mc_openid2uid($info["openid"]);
			$fans = mc_fetch($uid, array( "credit1", "credit2", "birthyear", "birthmonth", "birthday", "gender", "avatar", "resideprovince", "residecity", "nickname" ));
			$info["credit1"] = $fans["credit1"];
			$info["credit2"] = $fans["credit2"];
			$info["birthyear"] = (empty($info["birthyear"]) ? $fans["birthyear"] : $info["birthyear"]);
			$info["birthmonth"] = (empty($info["birthmonth"]) ? $fans["birthmonth"] : $info["birthmonth"]);
			$info["birthday"] = (empty($info["birthday"]) ? $fans["birthday"] : $info["birthday"]);
			$info["nickname"] = (empty($info["nickname"]) ? $fans["nickname"] : $info["nickname"]);
			$info["gender"] = (empty($info["gender"]) ? $fans["gender"] : $info["gender"]);
			$info["sex"] = $info["gender"];
			$info["avatar"] = (empty($info["avatar"]) ? $fans["avatar"] : $info["avatar"]);
			$info["headimgurl"] = $info["avatar"];
			$info["province"] = (empty($info["province"]) ? $fans["resideprovince"] : $info["province"]);
			$info["city"] = (empty($info["city"]) ? $fans["residecity"] : $info["city"]);
		}
		if( !empty($info["birthyear"]) && !empty($info["birthmonth"]) && !empty($info["birthday"]) ) 
		{
			$info["birthday"] = $info["birthyear"] . "-" . ((strlen($info["birthmonth"]) <= 1 ? "0" . $info["birthmonth"] : $info["birthmonth"])) . "-" . ((strlen($info["birthday"]) <= 1 ? "0" . $info["birthday"] : $info["birthday"]));
		}
		if( empty($info["birthday"]) ) 
		{
			$info["birthday"] = "";
		}
		if( !empty($info) ) 
		{
			if( !strexists($info["avatar"], "http://") && !strexists($info["avatar"], "https://") ) 
			{
				$info["avatar"] = tomedia($info["avatar"]);
			}
			if( $_W["ishttps"] ) 
			{
				$info["avatar"] = str_replace("http://", "https://", $info["avatar"]);
			}
		}
		return $info;
	}
	public function getMember($openid = "") 
	{
		global $_W;
		$uid = (int) $openid;
		if( $uid == 0 ) 
		{
		   
			$info = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where  openid=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
           
			if( empty($info) ) 
			{
				if( strexists($openid, "sns_qq_") ) 
				{
					$openid = str_replace("sns_qq_", "", $openid);
					$condition = " openid_qq=:openid ";
					$bindsns = "qq";
				}
				else 
				{
					if( strexists($openid, "sns_wx_") ) 
					{
						$openid = str_replace("sns_wx_", "", $openid);
						$condition = " openid_wx=:openid ";
						$bindsns = "wx";
					}
					else 
					{
						if( strexists($openid, "sns_wa_") ) 
						{
							$openid = str_replace("sns_wa_", "", $openid);
							$condition = " openid_wa=:openid ";
							$bindsns = "wa";
						}
					}
				}
				if( !empty($condition) ) 
				{
					$info = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where " . $condition . "  and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
					if( !empty($info) ) 
					{
						$info["bindsns"] = $bindsns;
					}
				}
			}
		}
		else 
		{
			$info = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":id" => $openid ));
		}
		if( !empty($info) ) 
		{
			if( !strexists($info["avatar"], "http://") && !strexists($info["avatar"], "https://") ) 
			{
				$info["avatar"] = tomedia($info["avatar"]);
			}
			if( $_W["ishttps"] ) 
			{
				$info["avatar"] = str_replace("http://", "https://", $info["avatar"]);
			}
			if(strpos($info['avatar'],'132132')){
				$upgrade2=array();
				$upgrade2['avatar'] = str_replace('132132', '132', $info['avatar']);
				pdo_update('ewei_shop_member', $upgrade2, array('id' => $info['id']));
			}
			
			$info = $this->updateCredits($info);
		}
		
		return $info;
	}
	public function updateCredits($info) 
	{
		global $_W;
		
		$openid = $info["openid"];
		
		if( empty($info["uid"]) ) 
		{
			$followed = m("user")->followed($openid);
			
			if( $followed ) 
			{
				load()->model("mc");
				$uid = mc_openid2uid($openid);
				if( !empty($uid) ) 
				{
					$info["uid"] = $uid;
					$upgrade = array( "uid" => $uid );
					if( 0 < $info["credit1"] ) 
					{
						mc_credit_update($uid, "credit1", $info["credit1"]);
						$upgrade["credit1"] = 0;
					}
					if( 0 < $info["credit2"] ) 
					{
						mc_credit_update($uid, "credit2", $info["credit2"]);
						$upgrade["credit2"] = 0;
					}
					if( !empty($upgrade) ) 
					{
						pdo_update("ewei_shop_member", $upgrade, array( "id" => $info["id"] ));
					}
				}
			}
		}
		
		$credits = $this->getCredits($openid);
		
		$info["credit1"] = $credits["credit1"];
		$info["credit2"] = $credits["credit2"];
		return $info;
	}
	public function getMobileMember($mobile) 
	{
		global $_W;
		$info = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where mobile=:mobile and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":mobile" => $mobile ));
		if( !empty($info) ) 
		{
			$info = $this->updateCredits($info);
		}
		return $info;
	}
	public function getMid() 
	{
		global $_W;
		$openid = $_W["openid"];
		$member = $this->getMember($openid);
		return $member["id"];
	}
	public function setCredit($openid = "", $credittype = "credit1", $credits = 0, $log = array( )) 
	{
		global $_W;
		
		load()->model("mc");
		$uid = mc_openid2uid($openid);
	   
		$member = $this->getMember($openid);
// 		var_dump($openid);
		
		if( empty($uid) ) 
		{
			$uid = intval($member["uid"]);
		}
		if( empty($log) ) 
		{
			$log = array( $uid, "未记录" );
		}
		else 
		{
			if( !is_array($log) ) 
			{
				$log = array( 0, $log );
			}
		}
// 		if( $credittype == "credit1" && empty($log[0]) && 0 < $credits ) 
// 		{
// 			$shopset = m("common")->getSysset("trade");
// 			if( empty($member["diymaxcredit"]) ) 
// 			{
// 				if( 0 < $shopset["maxcredit"] ) 
// 				{
// 					if( $shopset["maxcredit"] <= $member["credit1"] ) 
// 					{
// 						return error(-1, "用户卡路里已达上限");
// 					}
// 					if( $shopset["maxcredit"] < $member["credit1"] + $credits ) 
// 					{
// 						$credits = $shopset["maxcredit"] - $member["credit1"];
// 					}
// 				}
// 			}
// 			else 
// 			{
// 				if( 0 < $member["maxcredit"] ) 
// 				{
// 					if( $member["maxcredit"] <= $member["credit1"] ) 
// 					{
// 						return error(-1, "用户卡路里已达上限");
// 					}
// 					if( $member["maxcredit"] < $member["credit1"] + $credits ) 
// 					{
// 						$credits = $member["maxcredit"] - $member["credit1"];
// 					}
// 				}
// 			}
// 		}
		if( empty($log) ) 
		{
			$log = array( $uid, "未记录" );
		}
		else 
		{
			if( !is_array($log) ) 
			{
				$log = array( 0, $log );
			}
		}
		$log_data = array( "uid" => intval($uid), "credittype" => $credittype, "uniacid" => $_W["uniacid"], "num" => $credits, "createtime" => TIMESTAMP, "module" => "ewei_shopv2", "operator" => intval($log[0]), "remark" => $log[1] );
		if( !empty($uid) ) 
		{
			$value = pdo_fetchcolumn("SELECT " . $credittype . " FROM " . tablename("mc_members") . " WHERE `uid` = :uid", array( ":uid" => $uid ));
			$newcredit = $credits + $value;
			if( $newcredit <= 0 ) 
			{
				$newcredit = 0;
			}
			pdo_update("mc_members", array( $credittype => $newcredit ), array( "uid" => $uid ));
		}
		else 
		{
			$value = pdo_fetchcolumn("SELECT " . $credittype . " FROM " . tablename("ewei_shop_member") . " WHERE  uniacid=:uniacid and openid=:openid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
			$newcredit = $credits + $value;
			if( $newcredit <= 0 ) 
			{
				$newcredit = 0;
			}

			pdo_update("ewei_shop_member", array( $credittype => $newcredit ), array( "uniacid" => $_W["uniacid"], "openid" => $openid ));
			$log_data["remark"] = $log_data["remark"];
            $log_data["openid"]=$openid;
		}
		pdo_insert("mc_credits_record", $log_data);
		$member_log_table_flag = pdo_tableexists("ewei_shop_member_credit_record");
		pdo_insert("ewei_shop_member_credit_record", $log_data);
// 		if( $member_log_table_flag ) 
// 		{
// 			$log_data["openid"] = $openid;
// 			$open_redis = function_exists("redis") && !is_error(redis());
// 			if( $open_redis ) 
// 			{
// 				$redis_key = (string) $_W["uniacid"] . "_member_redit_" . $openid;
// 				$redis = redis();
// 				if( !is_error($redis) ) 
// 				{
// 					if( $redis->setnx($redis_key, time()) ) 
// 					{
// 						pdo_insert("ewei_shop_member_credit_record", $log_data);
// 						$redis->expireAt($redis_key, time() + 1);
// 					}
// 					else 
// 					{
// 						if( $redis->get($redis_key) + 1 < time() ) 
// 						{
// 							pdo_insert("ewei_shop_member_credit_record", $log_data);
// 							$redis->del($redis_key);
// 						}
// 					}
// 				}
// 			}
// 		}
		if( p("task") ) 
		{
			if( $credittype == "credit1" ) 
			{
			}
			else 
			{
				p("task")->checkTaskReward("cost_rechargeenough", $credits, $openid);
				p("task")->checkTaskReward("cost_rechargetotal", $credits, $openid);
			}
		}
		if( p("task") ) 
		{
			p("task")->checkTaskProgress($credits, "recharge_full", 0, $openid);
			p("task")->checkTaskProgress($credits, "recharge_count", 0, $openid);
		}
		com_run("wxcard::updateMemberCardByOpenid", $openid);
	}
	public function getCredit($openid = "", $credittype = "credit1") 
	{
		global $_W;
		$openid = str_replace("sns_wa_", "", $openid);
		load()->model("mc");
		$uid = mc_openid2uid($openid);
		if( !empty($uid) ) 
		{
			return pdo_fetchcolumn("SELECT " . $credittype . " FROM " . tablename("mc_members") . " WHERE `uid` = :uid", array( ":uid" => $uid ));
		}
		$item = pdo_fetch("SELECT " . $credittype . " FROM " . tablename("ewei_shop_member") . " WHERE openid=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
		if( empty($item) ) 
		{
			$item = pdo_fetch("SELECT " . $credittype . " FROM " . tablename("ewei_shop_member") . " WHERE openid_wa=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
		}
		return (empty($item[$credittype]) ? 0 : $item[$credittype]);
	}
	public function getCredits($openid = "", $credittypes = array('credit1', 'credit2')) 
	{
		global $_W;
		$openid = str_replace("sns_wa_", "", $openid);
		load()->model("mc");
		$uid = mc_openid2uid($openid);
		
		$types = implode(",", $credittypes);
		if( !empty($uid) ) 
		{
			return pdo_fetch("SELECT " . $types . " FROM " . tablename("mc_members") . " WHERE `uid` = :uid limit 1", array( ":uid" => $uid ));
		}
		
		$item = pdo_fetch("SELECT " . $types . " FROM " . tablename("ewei_shop_member") . " WHERE openid=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
		
		if( empty($item) ) 
		{
			$item = pdo_fetch("SELECT " . $types . " FROM " . tablename("ewei_shop_member") . " WHERE openid_wa=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $openid ));
		}
		if( empty($item) ) 
		{
			return array( "credit1" => 0, "credit2" => 0 );
		}
		return $item;
	}
	public function checkMember() 
	{
		global $_W;
		global $_GPC;
		$member = array( );
		$shopset = m("common")->getSysset(array( "shop", "wap" ));
		$openid = $_W["openid"];
		
		if( $_W["routes"] == "order.pay_alipay" || $_W["routes"] == "creditshop.log.dispatch_complete" || $_W["routes"] == "threen.register.threen_complete" || $_W["routes"] == "creditshop.detail.creditshop_complete" || $_W["routes"] == "order.pay_alipay.recharge_complete" || $_W["routes"] == "order.pay_alipay.complete" || $_W["routes"] == "newmr.alipay" || $_W["routes"] == "newmr.callback.gprs" || $_W["routes"] == "newmr.callback.bill" || $_W["routes"] == "account.sns" || $_W["plugin"] == "mmanage" || $_W["routes"] == "live.send.credit" || $_W["routes"] == "live.send.coupon" || $_W["routes"] == "index.share_url" ) 
		{
			return NULL;
		}
		if( $shopset["wap"]["open"] && ($shopset["wap"]["inh5app"] && is_h5app() || empty($shopset["wap"]["inh5app"]) && empty($openid)) ) 
		{
			return NULL;
		}
		if( empty($openid) && !EWEI_SHOPV2_DEBUG ) 
		{
			$diemsg = (is_h5app() ? "APP正在维护, 请到公众号中访问" : "请在微信客户端打开链接");
			exit( "<!DOCTYPE html>\r\n                <html>\r\n                    <head>\r\n                        <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>\r\n                        <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>\r\n                    </head>\r\n                    <body>\r\n                    <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>" . $diemsg . "</h4></div></div></div>\r\n                    </body>\r\n                </html>" );
		}
		$member = $this->getMember($openid);
		$followed = m("user")->followed($openid);
		$uid = 0;
		$mc = array( );
		load()->model("mc");
		if( $followed || empty($shopset["shop"]["getinfo"]) || $shopset["shop"]["getinfo"] == 1 ) 
		{
			$uid = mc_openid2uid($openid);
			if( !EWEI_SHOPV2_DEBUG ) 
			{
				$userinfo = mc_oauth_userinfo();
			}
			else 
			{
				$userinfo = array( "openid" => $member["openid"], "nickname" => $member["nickname"], "headimgurl" => $member["avatar"], "gender" => $member["gender"], "province" => $member["province"], "city" => $member["city"] );
			}
			$mc = array( );
			$mc["nickname"] = $userinfo["nickname"];
			$mc["avatar"] = $userinfo["headimgurl"];
			$mc["gender"] = $userinfo["sex"];
			$mc["resideprovince"] = $userinfo["province"];
			$mc["residecity"] = $userinfo["city"];
		}
		if( empty($member) && !empty($openid) ) 
		{
			$member = array( "uniacid" => $_W["uniacid"], "uid" => $uid, "openid" => $openid, "realname" => (!empty($mc["realname"]) ? $mc["realname"] : ""), "mobile" => (!empty($mc["mobile"]) ? $mc["mobile"] : ""), "nickname" => (!empty($mc["nickname"]) ? $mc["nickname"] : ""), "nickname_wechat" => (!empty($mc["nickname"]) ? $mc["nickname"] : ""), "avatar" => (!empty($mc["avatar"]) ? $mc["avatar"] : ""), "avatar_wechat" => (!empty($mc["avatar"]) ? $mc["avatar"] : ""), "gender" => (!empty($mc["gender"]) ? $mc["gender"] : "-1"), "province" => (!empty($mc["resideprovince"]) ? $mc["resideprovince"] : ""), "city" => (!empty($mc["residecity"]) ? $mc["residecity"] : ""), "area" => (!empty($mc["residedist"]) ? $mc["residedist"] : ""), "createtime" => time(), "status" => 0 );
			pdo_insert("ewei_shop_member", $member);
			if( method_exists(m("member"), "memberRadisCountDelete") ) 
			{
				m("member")->memberRadisCountDelete();
			}
			$member["id"] = pdo_insertid();
		}
		else 
		{
			if( $member["isblack"] == 1 ) 
			{
				show_message("暂时无法访问，请稍后再试!");
			}
			$upgrade = array( "uid" => $uid );
			if( isset($mc["nickname"]) && $member["nickname_wechat"] != $mc["nickname"] ) 
			{
				$upgrade["nickname_wechat"] = $mc["nickname"];
			}
			if( isset($mc["nickname"]) && empty($member["nickname"]) ) 
			{
				$upgrade["nickname"] = $mc["nickname"];
			}
			if( isset($mc["avatar"]) && $member["avatar_wechat"] != $mc["avatar"] ) 
			{
				$upgrade["avatar_wechat"] = $mc["avatar"];
			}
			if( isset($mc["avatar"]) && empty($member["avatar"]) ) 
			{
				$upgrade["avatar"] = $mc["avatar"];
			}
			if( isset($mc["gender"]) && $member["gender"] != $mc["gender"] ) 
			{
				$upgrade["gender"] = $mc["gender"];
			}
			if( !empty($upgrade) ) 
			{
				pdo_update("ewei_shop_member", $upgrade, array( "id" => $member["id"] ));
			}
		}
		if( p("commission") ) 
		{
			p("commission")->checkAgent($openid);
		}
		if( p("poster") ) 
		{
			p("poster")->checkScan($openid);
		}
		if( empty($member) ) 
		{
			return false;
		}
		return array( "id" => $member["id"], "openid" => $member["openid"] );
	}
	public function getLevels($all = true) 
	{
		global $_W;
		$condition = "";
		if( !$all ) 
		{
			$condition = " and enabled=1";
		}
		return pdo_fetchall("select * from " . tablename("ewei_shop_member_level") . " where uniacid=:uniacid" . $condition . " order by level asc", array( ":uniacid" => $_W["uniacid"] ));
	}
	public function getLevel($openid) 
	{
		global $_W;
		global $_S;
		if( empty($openid) ) 
		{
			return false;
		}
		$member = m("member")->getMember($openid);
		if( !empty($member) && !empty($member["level"]) ) 
		{
			$level = pdo_fetch("select * from " . tablename("ewei_shop_member_level") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $member["level"], ":uniacid" => $_W["uniacid"] ));
			if( !empty($level) ) 
			{
				return $level;
			}
		}
		return array( "levelname" => (empty($_S["shop"]["levelname"]) ? "普通会员" : $_S["shop"]["levelname"]), "discount" => (empty($_S["shop"]["leveldiscount"]) ? 10 : $_S["shop"]["leveldiscount"]) );
	}
	public function getOneGoodsLevel($openid, $goodsid) 
	{
		global $_W;
		$uniacid = $_W["uniacid"];
		$level_info = $this->getLevel($openid);
		$level = intval($level_info["level"]);
		$data = array( );
		$levels = pdo_fetchall("select * from " . tablename("ewei_shop_member_level") . " where uniacid=:uniacid and buygoods=1 and level and level > :level order by level asc", array( ":uniacid" => $uniacid, ":level" => $level ));
		if( !empty($levels) ) 
		{
			foreach( $levels as $k => $v ) 
			{
				$goodsids = iunserializer($v["goodsids"]);
				if( !empty($goodsids) && in_array($goodsid, $goodsids) ) 
				{
					$data = $v;
				}
			}
		}
		return $data;
	}
	public function getGoodsLevel($openid, $orderid) 
	{
		global $_W;
		$uniacid = $_W["uniacid"];
		$order_goods = pdo_fetchall("select goodsid from " . tablename("ewei_shop_order_goods") . " where orderid=:orderid and uniacid=:uniacid", array( ":uniacid" => $uniacid, ":orderid" => $orderid ));
		$levels = array( );
		$data = array( );
		if( !empty($order_goods) ) 
		{
			foreach( $order_goods as $k => $v ) 
			{
				$item = $this->getOneGoodsLevel($openid, $v["goodsid"]);
				if( !empty($item) ) 
				{
					$levels[$item["level"]] = $item;
				}
			}
		}
		if( !empty($levels) ) 
		{
			$level = max(array_keys($levels));
			$data = $levels[$level];
		}
		return $data;
	}
	public function upgradeLevel($openid, $orderid = 0) 
	{
		global $_W;
		if( empty($openid) ) 
		{
			return NULL;
		}
		$shopset = m("common")->getSysset("shop");
		$leveltype = intval($shopset["leveltype"]);
		$member = m("member")->getMember($openid);
		if( empty($member) ) 
		{
			return NULL;
		}
		$level = false;
		if( empty($leveltype) ) 
		{
			$ordermoney = pdo_fetchcolumn("select ifnull( sum(og.realprice),0) from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on o.id=og.orderid " . " where o.openid=:openid and o.status=3 and o.uniacid=:uniacid ", array( ":uniacid" => $_W["uniacid"], ":openid" => $member["openid"] ));
			$level = pdo_fetch("select * from " . tablename("ewei_shop_member_level") . " where uniacid=:uniacid  and enabled=1 and " . $ordermoney . " >= ordermoney and ordermoney>0  order by level desc limit 1", array( ":uniacid" => $_W["uniacid"] ));
		}
		else 
		{
			if( $leveltype == 1 ) 
			{
				$ordercount = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and status=3 and uniacid=:uniacid ", array( ":uniacid" => $_W["uniacid"], ":openid" => $member["openid"] ));
				$level = pdo_fetch("select * from " . tablename("ewei_shop_member_level") . " where uniacid=:uniacid and enabled=1 and " . $ordercount . " >= ordercount and ordercount>0  order by level desc limit 1", array( ":uniacid" => $_W["uniacid"] ));
			}
		}
		if( !empty($orderid) ) 
		{
			$goods_level = $this->getGoodsLevel($openid, $orderid);
			if( empty($level) ) 
			{
				$level = $goods_level;
			}
			else 
			{
				if( !empty($goods_level) && $level["level"] < $goods_level["level"] ) 
				{
					$level = $goods_level;
				}
			}
		}
		if( empty($level) ) 
		{
			return NULL;
		}
		if( $level["id"] == $member["level"] ) 
		{
			return NULL;
		}
		$oldlevel = $this->getLevel($openid);
		$canupgrade = false;
		if( empty($oldlevel["id"]) ) 
		{
			$canupgrade = true;
		}
		else 
		{
			if( $oldlevel["level"] < $level["level"] ) 
			{
				$canupgrade = true;
			}
		}
		if( $canupgrade ) 
		{
			pdo_update("ewei_shop_member", array( "level" => $level["id"] ), array( "id" => $member["id"] ));
			com_run("wxcard::updateMemberCardByOpenid", $openid);
			m("notice")->sendMemberUpgradeMessage($openid, $oldlevel, $level);
		}
	}
	public function upgradeLevelByLevelId($openid, $LevelID) 
	{
		global $_W;
		if( empty($openid) ) 
		{
			return NULL;
		}
		$member = m("member")->getMember($openid);
		if( empty($member) ) 
		{
			return NULL;
		}
		$level = pdo_fetch("select *  from " . tablename("ewei_shop_member_level") . " where uniacid=:uniacid and enabled=1 and id=:id", array( ":uniacid" => $_W["uniacid"], ":id" => $LevelID ));
		if( empty($level) ) 
		{
			return NULL;
		}
		if( $level["id"] == $member["level"] ) 
		{
			return NULL;
		}
		$oldlevel = $this->getLevel($openid);
		$canupgrade = false;
		if( empty($oldlevel["id"]) ) 
		{
			$canupgrade = true;
		}
		else 
		{
			if( $oldlevel["level"] < $level["level"] ) 
			{
				$canupgrade = true;
			}
		}
		if( $canupgrade ) 
		{
			pdo_update("ewei_shop_member", array( "level" => $level["id"] ), array( "id" => $member["id"] ));
			com_run("wxcard::updateMemberCardByOpenid", $openid);
			m("notice")->sendMemberUpgradeMessage($openid, $oldlevel, $level);
		}
	}
	public function getGroups() 
	{
		global $_W;
		return pdo_fetchall("select * from " . tablename("ewei_shop_member_group") . " where uniacid=:uniacid order by id asc", array( ":uniacid" => $_W["uniacid"] ));
	}
	public function getGroup($openid) 
	{
		if( empty($openid) ) 
		{
			return false;
		}
		$member = m("member")->getMember($openid);
		return $member["groupid"];
	}
	public function setGroups($openid, $group_ids, $reason = "") 
	{
		$is_id = false;
		if( 0 < intval($openid) ) 
		{
			$openid = m("member")->getInfo($openid);
			if( empty($openid["openid"]) ) 
			{
				return false;
			}
			$openid = $openid["openid"];
		}
		$condition = array( "openid" => $openid );
		if( is_array($group_ids) ) 
		{
			$group_arr = $group_ids;
			$group_ids = implode(",", $group_ids);
		}
		else 
		{
			if( is_string($group_ids) || is_numeric($group_ids) ) 
			{
				$group_arr = explode(",", $group_ids);
			}
			else 
			{
				return false;
			}
		}
		$old_group_ids = pdo_getcolumn("ewei_shop_member", $condition, "groupid");
		$diff_ids = explode(",", $group_ids);
		if( !empty($old_group_ids) ) 
		{
			$old_group_ids = explode(",", $old_group_ids);
			$group_ids = array_merge($old_group_ids, $diff_ids);
			$group_ids = array_flip(array_flip($group_ids));
			$group_ids = implode(",", $group_ids);
			$diff_ids = array_diff($diff_ids, $old_group_ids);
		}
		pdo_update("ewei_shop_member", array( "groupid" => $group_ids ), $condition);
		foreach( $diff_ids as $groupid ) 
		{
			pdo_insert("ewei_shop_member_group_log", array( "add_time" => date("Y-m-d H:i:s"), "group_id" => $groupid, "content" => $reason, "mid" => intval($openid), "openid" => ($is_id ? "" : $openid) ));
		}
		return true;
	}
	public function setRechargeCredit($openid = "", $money = 0) 
	{
		if( empty($openid) ) 
		{
			return NULL;
		}
		global $_W;
		$credit = 0;
		$set = m("common")->getSysset(array( "trade", "shop" ));
		if( $set["trade"] ) 
		{
			$tmoney = floatval($set["trade"]["money"]);
			if( !empty($tmoney) ) 
			{
				$tcredit = intval($set["trade"]["credit"]);
				if( $tmoney <= $money ) 
				{
					if( $money % $tmoney == 0 ) 
					{
						$credit = intval($money / $tmoney) * $tcredit;
					}
					else 
					{
						$credit = (intval($money / $tmoney) + 1) * $tcredit;
					}
				}
			}
		}
		if( 0 < $credit ) 
		{
			$this->setCredit($openid, "credit1", $credit, array( 0, $set["shop"]["name"] . "会员充值卡路里:credit2:" . $credit ));
		}
	}
	public function getCalculateMoney($money, $set_array) 
	{
		$charge = $set_array["charge"]?$set_array["charge"]:3;
		$begin = $set_array["begin"];
		$end = $set_array["end"];
		$array = array( );
		$array["deductionmoney"] = round(($money * $charge) / 100, 2);
		if( $begin <= $array["deductionmoney"] && $array["deductionmoney"] <= $end ) 
		{
			$array["deductionmoney"] = 0;
		}
		$array["realmoney"] = round($money - $array["deductionmoney"], 2);
		if( $money == $array["realmoney"] ) 
		{
			$array["flag"] = 0;
		}
		else 
		{
			$array["flag"] = 1;
		}
		return $array;
	}
	public function checkMemberFromPlatform($openid = "", $acc = "") 
	{
		global $_W;
		if( empty($acc) ) 
		{
			$acc = WeiXinAccount::create();
		}
		$userinfo = $acc->fansQueryInfo($openid);
		$userinfo["avatar"] = $userinfo["headimgurl"];
		$redis = redis();
		if( !is_error($redis) ) 
		{
			$member = $redis->get($openid . "_checkMemberFromPlatform");
			if( !empty($member) ) 
			{
				return json_decode($member, true);
			}
		}
		load()->model("mc");
		$uid = mc_openid2uid($openid);
		if( !empty($uid) ) 
		{
			pdo_update("mc_members", array( "nickname" => $userinfo["nickname"], "gender" => $userinfo["sex"], "nationality" => $userinfo["country"], "resideprovince" => $userinfo["province"], "residecity" => $userinfo["city"], "avatar" => $userinfo["headimgurl"] ), array( "uid" => $uid ));
		}
		pdo_update("mc_mapping_fans", array( "nickname" => $userinfo["nickname"] ), array( "uniacid" => $_W["uniacid"], "openid" => $openid ));
		$member = $this->getMember($openid);
		if( empty($member) ) 
		{
			$mc = mc_fetch($uid, array( "realname", "nickname", "mobile", "avatar", "resideprovince", "residecity", "residedist" ));
			$member = array( "uniacid" => $_W["uniacid"], "uid" => $uid, "openid" => $openid, "realname" => $mc["realname"], "mobile" => $mc["mobile"], "nickname" => (!empty($mc["nickname"]) ? $mc["nickname"] : $userinfo["nickname"]), "avatar" => (!empty($mc["avatar"]) ? $mc["avatar"] : $userinfo["avatar"]), "gender" => (!empty($mc["gender"]) ? $mc["gender"] : $userinfo["sex"]), "province" => (!empty($mc["resideprovince"]) ? $mc["resideprovince"] : $userinfo["province"]), "city" => (!empty($mc["residecity"]) ? $mc["residecity"] : $userinfo["city"]), "area" => $mc["residedist"], "createtime" => time(), "status" => 0 );
			pdo_insert("ewei_shop_member", $member);
			if( method_exists(m("member"), "memberRadisCountDelete") ) 
			{
				m("member")->memberRadisCountDelete();
			}
			$member["id"] = pdo_insertid();
			$member["isnew"] = true;
		}
		else 
		{
			$member["nickname"] = $userinfo["nickname"];
			$member["avatar"] = $userinfo["headimgurl"];
			$member["province"] = $userinfo["province"];
			$member["city"] = $userinfo["city"];
			pdo_update("ewei_shop_member", $member, array( "id" => $member["id"] ));
			if( time() - $member["createtime"] < 60 ) 
			{
				$member["isnew"] = true;
			}
			else 
			{
				$member["isnew"] = false;
			}
		}
		if( !is_error($redis) ) 
		{
			$redis->set($openid . "_checkMemberFromPlatform", json_encode($member), 20);
		}
		return $member;
	}
	public function mc_update($mid, $data) 
	{
		global $_W;
		if( empty($mid) || empty($data) ) 
		{
			return NULL;
		}
		$wapset = m("common")->getSysset("wap");
		$member = $this->getMember($mid);
		if( !empty($wapset["open"]) && isset($data["mobile"]) && $data["mobile"] != $member["mobile"] ) 
		{
			unset($data["mobile"]);
		}
		load()->model("mc");
		mc_update($this->member["uid"], $data);
	}
	public function checkMemberSNS($sns) 
	{
		global $_W;
		global $_GPC;
		if( empty($sns) ) 
		{
			$sns = $_GPC["sns"];
		}
		if( empty($sns) ) 
		{
			return NULL;
		}
		if( $sns == "wx" ) 
		{
			load()->func("communication");
			$token = trim($_GPC["token"]);
			$openid = trim($_GPC["openid"]);
			$appid = "wxc3d9d8efae0ae858";
			$secret = "93d4f6085f301c405b5812217e6d5025";
			if( empty($token) && !empty($_GPC["code"]) ) 
			{
				$codeurl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . trim($_GPC["code"]) . "&grant_type=authorization_code";
				$coderesult = $userinfo = ihttp_request($codeurl);
				$coderesult = json_decode($coderesult["content"], true);
				if( empty($coderesult["access_token"]) || empty($coderesult["openid"]) ) 
				{
					return NULL;
				}
				$token = $coderesult["access_token"];
				$openid = $coderesult["openid"];
			}
			if( empty($token) || empty($openid) ) 
			{
				return NULL;
			}
			$snsurl = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $token . "&openid=" . $openid . "&lang=zh_CN";
			$userinfo = ihttp_request($snsurl);
			$userinfo = json_decode($userinfo["content"], true);
			if( empty($userinfo["openid"]) ) 
			{
				return NULL;
			}
			$userinfo["openid"] = "sns_wx_" . $userinfo["openid"];
		}
		else 
		{
			if( $sns == "qq" ) 
			{
				$userinfo = htmlspecialchars_decode($_GPC["userinfo"]);
				$userinfo = json_decode($userinfo, true);
				$userinfo["openid"] = "sns_qq_" . $_GPC["openid"];
				$userinfo["headimgurl"] = $userinfo["figureurl_qq_2"];
				$userinfo["gender"] = ($userinfo["gender"] == "男" ? 1 : 2);
			}
		}
		$data = array( "nickname" => $userinfo["nickname"], "avatar" => $userinfo["headimgurl"], "province" => $userinfo["province"], "city" => $userinfo["city"], "gender" => $userinfo["sex"], "comefrom" => "h5app_sns_" . $sns );
		$openid = trim($_GPC["openid"]);
		if( $sns == "qq" ) 
		{
			$data["openid_qq"] = trim($_GPC["openid"]);
			$openid = "sns_qq_" . trim($_GPC["openid"]);
		}
		if( $sns == "wx" ) 
		{
			$data["openid_wx"] = trim($_GPC["openid"]);
			$openid = "sns_wx_" . trim($_GPC["openid"]);
		}
		$member = $this->getMember($openid);
		if( empty($member) ) 
		{
			$data["openid"] = $userinfo["openid"];
			$data["uniacid"] = $_W["uniacid"];
			$data["comefrom"] = "sns_" . $sns;
			$data["createtime"] = time();
			$data["salt"] = m("account")->getSalt();
			$data["pwd"] = rand(10000, 99999) . $data["salt"];
			pdo_insert("ewei_shop_member", $data);
			if( method_exists(m("member"), "memberRadisCountDelete") ) 
			{
				m("member")->memberRadisCountDelete();
			}
			return pdo_insertid();
		}
		if( empty($member["bindsns"]) || $member["bindsns"] == $sns ) 
		{
			pdo_update("ewei_shop_member", $data, array( "id" => $member["id"], "uniacid" => $_W["uniacid"] ));
			return $member["id"];
		}
	}
	public function compareLevel(array $level, array $levels = array( )) 
	{
		global $_W;
		$levels = (!empty($levels) ? $levels : $this->getLevels());
		$old_key = -1;
		$new_key = -1;
		foreach( $levels as $kk => $vv ) 
		{
			if( $vv["id"] == $level[0] ) 
			{
				$old_key = $vv["level"];
			}
			if( $vv["id"] == $level[1] ) 
			{
				$new_key = $vv["level"];
			}
		}
		return $old_key < $new_key;
	}
	public function wxuser($appid, $secret, $snsapi = "snsapi_base", $expired = "600") 
	{
		global $_W;
		$wxuser = $_COOKIE[$_W["config"]["cookie"]["pre"] . $appid];
		if( $wxuser === NULL ) 
		{
			$code = (isset($_GET["code"]) ? $_GET["code"] : "");
			if( !$code ) 
			{
				$url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
				$oauth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=" . $snsapi . "&state=wxbase#wechat_redirect";
				header("Location: " . $oauth_url);
				exit();
			}
			load()->func("communication");
			$getOauthAccessToken = ihttp_get("https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code");
			$json = json_decode($getOauthAccessToken["content"], true);
			if( !empty($json["errcode"]) && ($json["errcode"] == "40029" || $json["errcode"] == "40163") ) 
			{
				$url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . ((strpos($_SERVER["REQUEST_URI"], "?") ? "" : "?"));
				$parse = parse_url($url);
				if( isset($parse["query"]) ) 
				{
					parse_str($parse["query"], $params);
					unset($params["code"]);
					unset($params["state"]);
					$url = "http://" . $_SERVER["HTTP_HOST"] . $parse["path"] . "?" . http_build_query($params);
				}
				$oauth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=" . $snsapi . "&state=wxbase#wechat_redirect";
				header("Location: " . $oauth_url);
				exit();
			}
			if( $snsapi == "snsapi_userinfo" ) 
			{
				$userinfo = ihttp_get("https://api.weixin.qq.com/sns/userinfo?access_token=" . $json["access_token"] . "&openid=" . $json["openid"] . "&lang=zh_CN");
				$userinfo = $userinfo["content"];
			}
			else 
			{
				if( $snsapi == "snsapi_base" ) 
				{
					$userinfo = array( );
					$userinfo["openid"] = $json["openid"];
				}
			}
			$userinfostr = json_encode($userinfo);
			isetcookie($appid, $userinfostr, $expired);
			return $userinfo;
		}
		return json_decode($wxuser, true);
	}
	public function memberRadisCount($key, $value = false) 
	{
		global $_W;
		$redis = redis();
		if( !is_error($redis) ) 
		{
			if( empty($value) ) 
			{
				if( $redis->get($key) != false ) 
				{
					return $redis->get($key);
				}
				return false;
			}
			$redis->set($key, $value, array( "nx", "ex" => "3600" ));
		}
	}
	public function memberRadisCountDelete() 
	{
		global $_W;
		$open_redis = function_exists("redis") && !is_error(redis());
		if( $open_redis ) 
		{
			$redis = redis();
			
			$redis->del("ewei_" . $_W["uniacid"] . "_member_commission_first");
			$redis->del("ewei_". $_W["uniacid"] ."_member_list");
// 			$keysArr = $redis->keys("ewei_" . $_W["uniacid"] . "_member*");
// 			if( !empty($keysArr) && is_array($keysArr) ) 
// 			{
// 				foreach( $keysArr as $k => $v ) 
// 				{
// 					$redis->del($v);
// 				}
// 			}
		}
	}


    public function agentlevel($openid)
    {
        global $_W;
        if( empty($openid) )
        {
            return false;
        }
        $member = m("member")->getMember($openid);
        if( !empty($member) && !empty($member["agentlevel"]) )
        {
            $level = pdo_fetch("select * from " . tablename("ewei_shop_commission_level") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $member["agentlevel"], ":uniacid" => $_W["uniacid"] ));
            if( !empty($level) )
            {
                $nodate = date('Y-m-d',time());
                $thisdate = date('Y-m-d',$member['agenttime']);
                $data = array();
                $data['thisdate'] = $thisdate;
                $data['levelid'] = $level['id'];
                $data['levelname'] = $level['levelname'];
                $hasdate = $nodate-$thisdate;//剩余天数

                if($level['id']==5){
                    if($hasdate<0) $hasdate=0;
                    $hastime = $nodate-$thisdate;
                    $data['nodate'] = $nodate;
                    $data['leveltime'] = date('Y-m-d',$member['agenttime']);
                    $data['hasday'] = $hastime;
                    $data['endtime'] = date('Y-m-d',$member['agenttime']+365*3600*24);
                }
                return $data;
            }
            return array('levelname'=>'普通会员','leveltime'=>'','levelid'=>0);
        }
        return array('levelname'=>'普通会员','leveltime'=>'','levelid'=>0);
    }
    //fanbeibei
    //获取每天可兑换的卡路里
   public function exchange_step($openid=""){
        
        
        $member=pdo_get('ewei_shop_member',array('openid'=>$openid));
        
        if ($member["agentlevel"]!=0){
            
            $level=pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
            $set=pdo_get('ewei_setting',array('type'=>"level",'type_id'=>$member["agentlevel"]));

            //加速日期
            $accelerate_day=date("Y-m-d",strtotime("+".$level["accelerate_day"]." day",strtotime($member["agentlevel_time"])));
           
           
            //获取加速器剩余时间--加速宝
            $d=$this->acceleration($openid);
            
            $day=date("Y-m-d",time());
            
            if ($d["day"]>0){
                //加速期间
                $ratio=$d["duihuan"];
            }else{ 
                
                /**
                // var_dump("11");
                //获取最新下级
                if ($member["agentlevel"]==5){
                    //店主
                    $subordinate = pdo_fetch("select * from " . tablename("ewei_shop_member") . " WHERE agentid=:agentid and agentlevel>=:agentlevel and agentlevel<:agent order by agentlevel_time desc limit 1", array(":agentid" => $member["id"],":agentlevel"=>3,":agent"=>6));
                }else{
                    
                    $subordinate = pdo_fetch("select * from " . tablename("ewei_shop_member") . " WHERE agentid=:agentid and agentlevel>:agentlevel and agentlevel<:agent order by agentlevel_time desc limit 1", array(':agentid' => $member["id"],":agentlevel"=>0,":agent"=>6));
                }
                 
                if ($accelerate_day>=date("Y-m-d",time())){
                   
                    $count_days=$this->count_days($day, $accelerate_day);
                    // var_dump($subordinate);
                    $round=number_format($count_days/20,2);
                    //  var_dump($round);
                    if ($round>=0&&$round<=1){
                        $ratio=$level["duihuan"];
                    }elseif ($round>1&&$round<=2){
                        $ratio=number_format($level["duihuan"]*0.7,2);
                    }elseif ($round>2&&$round<=3){
                        $ratio=number_format($level["duihuan"]*0.4,2);
                    }else{
                        $ratio=number_format($level["duihuan"]*0.1,2);
                    }
                    
                }else if (!empty($subordinate)&&($subordinate["agentlevel_time"]>=$accelerate_day)){
                    $count_days=$this->count_days($day, $subordinate["agentlevel_time"]);
                    // var_dump($subordinate);
                    $round=number_format($count_days/20,2);
                    //  var_dump($round);
                    if ($round>=0&&$round<=1){
                        $ratio=$level["duihuan"];
                    }elseif ($round>1&&$round<=2){
                        $ratio=number_format($level["duihuan"]*0.7,2);
                    }elseif ($round>2&&$round<=3){
                        $ratio=number_format($level["duihuan"]*0.4,2);
                    }else{
                        $ratio=number_format($level["duihuan"]*0.1,2);
                    }
                    
                }else{
                    
                    $count_days=$this->count_days($day, $accelerate_day);
                    $round=number_format($count_days/20,2);
                    if ($round>=0&&$round<=1){
                        $ratio=$level["duihuan"];
                    }elseif ($round>1&&$round<=2){
                        $ratio=number_format($level["duihuan"]*0.7,2);
                    }elseif ($round>2&&$round<=3){
                        $ratio=number_format($level["duihuan"]*0.4,2);
                    }else{
                        $ratio=number_format($level["duihuan"]*0.1,2);
                    }
                    
                }
                **/
                
                $ratio=10;
                
            }
            
        }else{
           
            $day=date("Y-m-d",time());
            $create_day=date("Y-m-d",$member["createtime"]);
            $subordinate = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member') . ' WHERE agentid=:agentid and agentlevel>:agentlevel order by agentlevel_time desc', array(':agentid' => $member["id"],':agentlevel'=>0));
           
         /**   if (!empty($subordinate)){
                $count_days=$this->count_days($day, $subordinate["agentlevel_time"]);
                $round=number_format($count_days/20,2);
                if ($round>=0&&$round<=1){
                    $ratio=5;
                }elseif ($round>1&&$round<=2){
                    $ratio=number_format(5*0.7,2);
                }elseif ($round>2&&$round<=3){
                    $ratio=number_format(5*0.4,2);
                }else{
                    $ratio=number_format(5*0.1,2);
                }
            }else{
                
                $count_days=$this->count_days($day, $create_day);
                //var_dump($count_days);
                $round=number_format($count_days/20,2);
               // var_dump($round);die;
                if ($round>=0&&$round<=1){
                    $ratio=5;
                }elseif ($round>1&&$round<=2){
                    $ratio=number_format(5*0.7,2);
                }elseif ($round>2&&$round<=3){
                    $ratio=number_format(5*0.4,2);
                }else{
                    $ratio=number_format(5*0.1,2);
                }
            }
            **/
            $ratio=10;
        }
        return $ratio;
        
    }
    //fanbeibei
    //指定日期相差的天数
  public  function count_days($a,$b){
        $a=strtotime($a);
        $b=strtotime($b);
        $a_dt=getdate($a);
        $b_dt=getdate($b);
      
        $a_new=mktime(12,0,0,$a_dt['mon'],$a_dt['mday'],$a_dt['year']);
        $b_new=mktime(12,0,0,$b_dt['mon'],$b_dt['mday'],$b_dt['year']);
        
        return round(abs($a_new-$b_new)/86400);
    }
    //fanbeibei
    //获取兑换比例
    public function exchange($openid=""){
        $member=pdo_get('ewei_shop_member',array('openid'=>$openid));
        
        if ($member["agentlevel"]!=0&&$member["agentlevel"]<6){
            
            $level=pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
            $set=pdo_get('ewei_setting',array('type'=>"level",'type_id'=>$member["agentlevel"]));
            
            //加速日期
            $accelerate_day=date("Y-m-d",strtotime("+".$level["accelerate_day"]." day",strtotime($member["agentlevel_time"])));
            
            $day=date("Y-m-d",time());
            
            if ($accelerate_day>=$day){
                //加速期间
                $ratio=$level["accelerate"]/$set["value"];
                
            }else{
                // var_dump("11");
                //获取最新下级
                if ($member["agentlevel"]==5){
                    //店主
                    $subordinate = pdo_fetch("select * from " . tablename("ewei_shop_member") . " WHERE agentid=:agentid and agentlevel>=:agentlevel and agentlevel<:agent order by agentlevel_time desc limit 1", array(":agentid" => $member["id"],":agentlevel"=>3,":agent"=>6));
                }else{
                    
                    $subordinate = pdo_fetch("select * from " . tablename("ewei_shop_member") . " WHERE agentid=:agentid and agentlevel>:agentlevel and agentlevel<:agent order by agentlevel_time desc limit 1", array(':agentid' => $member["id"],":agentlevel"=>0,":agent"=>6));
                }
                // var_dump($subordinate);
                if (!empty($subordinate)&&($subordinate["agentlevel_time"]>=$accelerate_day)){
                    $count_days=$this->count_days($day, $subordinate["agentlevel_time"]);
                    
                    $round=number_format($count_days/20,2);
                    
                    if ($round>=0&&$round<=1){
                        $ratio=$level["subscription_ratio"]/$set["value"];
                    }elseif ($round>1&&$round<=2){
                        $ratio=$level["subscription_ratio"]/$set["value"]*0.7;
                    }elseif ($round>2&&$round<=3){
                        $ratio=$level["subscription_ratio"]/$set["value"]*0.4;
                    }else{
                        $ratio=$level["subscription_ratio"]/$set["value"]*0.1;
                    }
                }
                else{
                    
                    $count_days=$this->count_days($day, $accelerate_day);
                    $round=number_format($count_days/20,2);
                    if ($round>0&&$round<=1){
                        $ratio=$level["subscription_ratio"]/$set["value"];
                    }elseif ($round>1&&$round<=2){
                        $ratio=$level["subscription_ratio"]/$set["value"]*0.7;
                    }elseif ($round>2&&$round<=3){
                        $ratio=$level["subscription_ratio"]/$set["value"]*0.4;
                    }else{
                        $ratio=$level["subscription_ratio"]/$set["value"]*0.1;
                    }
                    
                }
            }
            
        }else{
            $set=pdo_get('ewei_setting',array('type'=>"level",'type_id'=>0));
            $day=date("Y-m-d",time());
            $create_day=date("Y-m-d",$member["createtime"]);
            $subordinate = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member') . ' WHERE agentid=:agentid and agentlevel>:agentlevel order by agentlevel_time desc', array(':agentid' => $member["id"],':agentlevel'=>0));
            if (!empty($subordinate)){
                $count_days=$this->count_days($day, $subordinate["agentlevel_time"]);
                $round=number_format($count_days/20,2);
                if ($round>0&&$round<=1){
                    $ratio=5/$set["value"];
                }elseif ($round>1&&$round<=2){
                    $ratio=5/$set["value"]*0.7;
                }elseif ($round>2&&$round<=3){
                    $ratio=5/$set["value"]*0.4;
                }else{
                    $ratio=5/$set["value"]*0.1;
                }
            }else{
                
                $count_days=$this->count_days($day, $create_day);
                $round=number_format($count_days/20,2);
                if ($round>0&&$round<=1){
                    $ratio=5/$set["value"];
                }elseif ($round>1&&$round<=2){
                    $ratio=5/$set["value"]*0.7;
                }elseif ($round>2&&$round<=3){
                    $ratio=5/$set["value"]*0.4;
                }else{
                    $ratio=5/$set["value"]*0.1;
                }
            }
        }
        return $ratio;
    }
    

    /**
     * 绑带会员
     */
    public function setagent($info){
        global $_W;
        $memberinfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array(":openid" => $info['openid'] ));
        //助力人的信息
        //主
        if($info['openid']==$info['agentopenid']) return true;
        if(!$memberinfo) return false;
        if($memberinfo['agentid']==0 || $memberinfo['agentid']==''){
            $agentinfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid limit 1", array(":openid" => $info['agentopenid'] ));
            //附 被助力人的信息，来源信息
            $data['agentid'] = $agentinfo['id']?$agentinfo['id']:0;
            $where['openid'] = $info['openid'];
            pdo_update("ewei_shop_member",$data,$where);
            //如果agentID不为0  就添加绑定上级日志
            if($data['agentid'] != 0){
                $add = ['openid'=>$info['openid'],'item'=>'model.member','value'=>'绑定上级:'.$info['openid'].'/'.$memberinfo['nickname'].',绑定上级id:'.$data['agentid'].'-'.$agentinfo['nickname'],'create_time'=>date('Y-m-d H:i:s',time())];
                m('memberoperate')->addlog($add);
                //添加绑定粉丝
                $my=pdo_get("ewei_shop_member",array("openid"=>$info["openid"]));
                $this->fans($my["id"],$data["agentid"]);
            }
            $this->bindFromMerch($info['openid'],$data['agentid']);
            
            //fbb 贡献值
            m("devote")->rewardtwo($agentinfo["id"]);
            m("devote")->rewardfive($agentinfo["id"]);
            
            if(isset($agentinfo['id'])){
                if(isset($info['goodsid'])){
                    $goodsid=$info['goodsid'];
                }else{
                    $goodsid=0;
                }
//                 $this->memberAgentCount($goodsid,$agentinfo['id']);
            }
        }

    }

    /**
     * 推荐会员数据埋点
     * @param $data
     */
    public function memberAgentCount($goodsid=0,$agentid){
        $agentInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where id=:id limit 1", array(":id" => $agentid ));
        if(!$agentInfo) return false;
        $agentCountInfo = pdo_fetch("select * from " . tablename("ewei_shop_member_agentcount") . " where openid=:openid limit 1", array(":openid" => $agentInfo['openid']));
        if(!$agentCountInfo){//添加记录
            $data['openid'] =  $agentInfo['openid'];
            //if($agentid==0 || $agentid==''){
                $data['agentcount'] = 1;
                $data['agentallcount'] = 1;
            //}
            if($goodsid==7){
                $data['shopkeepercount'] = 1;
                $data['shopkeeperallcount'] = 1;
            }
            if($goodsid==4){
                $data['starshinecount'] = 1;
                $data['starshineallcount'] = 1;
            }
            if($goodsid==3){
                $data['healthycount'] = 1;
                $data['healthyallcount'] = 1;
            }
            pdo_insert('ewei_shop_member_agentcount',$data);
        }else{//更新数据
            if($goodsid==0) {
                $data['agentcount'] = $agentCountInfo['agentcount'] + 1;
                $data['agentallcount'] = $agentCountInfo['agentallcount'] + 1;
            }
            if($goodsid==7){
                $data['shopkeepercount'] = $agentCountInfo['shopkeepercount']+1;
                $data['shopkeeperallcount'] = $agentCountInfo['shopkeeperallcount']+1;
            }
            if($goodsid==4){
                $data['starshinecount'] = $agentCountInfo['starshinecount']+1;
                $data['starshineallcount'] = $agentCountInfo['starshineallcount']+1;
            }
            if($goodsid==3){
                $data['healthycount'] = $agentCountInfo['healthycount']+1;
                $data['healthyallcount'] = $agentCountInfo['healthyallcount']+1;
            }
           pdo_update('ewei_shop_member_agentcount',$data,array('openid'=>$agentInfo['openid']));
        }
        return true;
    }


    /**
     * 绑定会员来源店铺
     * @param $openid
     * @return bool
     */
    public function bindFromMerch($openid,$agentid){
        $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid  limit 1", array( ":openid" => $openid ));
        if(!$memberInfo) return false;
        if($memberInfo['from_merchid']>0) return true;

        $merchInfo = pdo_fetch("select * from " . tablename("ewei_shop_merch_user") . " where member_id=:member_id  limit 1", array( ":member_id" => $agentid ));
        if(!$merchInfo) return false;

        return pdo_update("ewei_shop_member",array('from_merchid'=>$merchInfo['id']),array('openid'=>$openid));
    }

    /**
     * 获取推荐人总数量（包含下级的所以推荐）
     * @param $id
     * @return int
     */
    public function allAgentCount($id,$agentlevel=0){
        $res = $this->getBottomUsers($id,'',$agentlevel);
        if(!$res) return 0;
        $idlist = explode(",", $res);
        return count($idlist)-1;
    }

    /**
     * 查找一个粉丝下的所以粉丝
     */
    public function getBottomUsers($id,$uids='',$agentlevel=0){
        if($agentlevel==0){
            $userList = pdo_fetchall("select * from" . tablename("ewei_shop_member") ."where agentid=:agentid ",array( ":agentid" => $id));
            if(!$userList) return false;
            foreach ($userList as $key=>$value){
                $uids .= $value['id'].',';
                $user = pdo_fetchall("select * from" . tablename("ewei_shop_member") ."where agentid=:agentid",array( ":agentid" => $value['id']));
                if($user){
                    $uids = $this->getBottomUsers($value['id'],$uids,0);
                }
            }
            return $uids;
        }else{
            $userList = pdo_fetchall("select * from" . tablename("ewei_shop_member") ."where agentid=:agentid and agentlevel=:agentlevel",array( ":agentid" => $id,":agentlevel"=>$agentlevel));
            if(!$userList) return false;
            foreach ($userList as $key=>$value){
                $uids .= $value['id'].',';
                $user = pdo_fetchall("select * from" . tablename("ewei_shop_member") ."where agentid=:agentid and agentlevel=:agentlevel",array( ":agentid" => $value['id'],":agentlevel"=>$agentlevel));
                if($user){
                    $uids = $this->getBottomUsers($value['id'],$uids,$agentlevel);
                }
            }
            return $uids;
        }


    }

    /**
     * 购买店主 送990折扣宝
     * @param $openid
     * @param $level
     * @param $goods_id
     * @return bool|string
     */
    public function shop_reward($openid="",$level="")
    {

        if($level != 5){
            return "购买等级不正确";
        }
        $user = pdo_get('ewei_shop_member',['openid'=>$openid]);
        if(!$user){
            return "该用户不存在";
        }
        $data = [
            'uniacid'=>1,
            'credittype'=>'credit3',
            'module'=>'ewei_shopv2',
           // 'num'=>2000,
            'num'=>9900,
            'createtime'=>time(),
            'remark'=>'智能员工(店主)',
            'openid'=>$openid,
        ];
       // $credit = bcadd($user['credit3'],2000,2);
        $credit = bcadd($user['credit3'],9900,2);
        pdo_update('ewei_shop_member',['credit3'=>$credit],['openid'=>$openid]);
        pdo_insert('mc_credits_record',$data);
        pdo_insert('ewei_shop_member_credit_record',$data);
        return true;
    }
    //fbb
    //获取剩余加速时间
    public function acceleration($openid=""){
        $member=pdo_get("ewei_shop_member",array("openid"=>$openid));
        //加速剩余天数
        $res["day"]=0;
        $res["duihuan"]=0;
        //加速类型
        $res["type"]=0;
        //已加速时间
        $res["accelerate_day"]=0;
        //加速总天数
        $res["give_day"]=0;
        if ($member["agentlevel"]==0){
            return $res;
        }
        //获取
        $level=pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
        //加速日期
        $accelerate_day=date("Y-m-d",strtotime("+".$level["accelerate_day"]." day",strtotime($member["agentlevel_time"])));
        
        $day=date("Y-m-d",time());
        if ($accelerate_day>$day){
            $res["day"]=$this->count_days($accelerate_day, $day);
            $res["duihuan"]=$level["duihuan"];
            $res["type"]=0;
            $res["accelerate_day"]=$level["accelerate_day"]-$res["day"];
            $res["give_day"]=$level["accelerate_day"];
            return $res;
        }else{
            //判断是否在加速宝内
            if (!empty($member["accelerate_end"])&&$member["accelerate_end"]>$day){
                $res["day"]=$this->count_days($member["accelerate_end"], $day);
                $res["duihuan"]=$member["duihuan"];
                $res["type"]=1;
                $d=$this->count_days($member["accelerate_end"],$member["accelerate_start"]);
                $res["accelerate_day"]=$d-$res["day"];
                $res["give_day"]=$d;
                return $res;
            }
        }
        return $res;
    }
    //会员粉丝数
    //myid用户的id parentid上级id type 0表示绑定 1表示修改 oldparentid修改前的parentid
    public function fans($myid="",$parentid="",$type=0,$oldparentid=""){
        if (empty($myid)){
            return false;
        }
        $my=pdo_get("ewei_shop_member",array("id"=>$myid));
        if (empty($my)){
            return false;
        }
        $parent=pdo_get("ewei_shop_member",array("id"=>$parentid));
      
            //更新用户
            if (empty($parent["parent_id"])){
               $parent_id[0]=$parentid;
            }else{
                $parent_id=unserialize($parent["parent_id"]);
                $len=count($parent_id);
                if (!in_array($parentid, $parent_id)){
                    $parent_id[$len]=$parentid;
                }
            }
            $data["parent_id"]=serialize($parent_id);
            pdo_update("ewei_shop_member",$data,array("id"=>$myid));
            //更新粉丝数量
            foreach ($parent_id as $k=>$v){
                $member=pdo_get("ewei_shop_member",array("id"=>$v));
                if ($member){
                    //获取粉丝总量中数据是否有
                    $agentcount=pdo_get("ewei_shop_member_agentcount",array("openid"=>$member["openid"]));
                    if ($agentcount){
                        if ($v==$parentid){
                            //上级为直推
                            $data["agentcount"]=$agentcount["agentcount"]+1;
                            $data["agentallcount"]=$agentcount["agentallcount"]+1;
                            if ($my["agentlevel"]==5){
                                $data["shopkeepercount"]=$agentcount["shopkeepercount"]+1;
                                $data["shopkeeperallcount"]=$agentcount["shopkeeperallcount"]+1;
                            }
                        }else{
                            $data["agentallcount"]=$agentcount["agentallcount"]+1;
                            if ($my["agentlevel"]==5){
                                $data["shopkeeperallcount"]=$agentcount["shopkeeperallcount"]+1;
                            }
                            
                        }
                        $data["update_time"]=date("Y-m-d H:i:s");
                        pdo_update("ewei_shop_member_agentcount",$data,array("openid"=>$member["openid"]));
                    }else{
                        
                        if ($v==$parentid){
                            $data["agentcount"]=1;
                            $data["agentallcount"]=1;
                            if ($my["agentlevel"]==5){
                                $data["shopkeepercount"]=1;
                                $data["shopkeeperallcount"]=1;
                            }
                        }else{
                            $data["agentallcount"]=1;
                            if ($my["agentlevel"]==5){
                                $data["shopkeeperallcount"]=1;
                            }
                            
                        }
                        $data["openid"]=$member["openid"];
                        $data["create_time"]=date("Y-m-d H:i:s");
                       pdo_insert("ewei_shop_member_agentcount",$data); 
                    }
                }
            }
        //更新老用户数据 
        if ($type==1&&$oldparentid){
            $oldagent=pdo_get("ewei_shop_member",array("id"=>$oldparentid));
            if ($oldagent){
               if ($oldagent["parent_id"]){
                   $oldparent_id=unserialize($oldagent["parent_id"]);
                   $len=count($oldparent_id);
                   $oldparent_id[$len]=$oldparentid;
               }else{
                   $oldparent_id[0]=$oldparentid;
               }
               
               //更新粉丝数量
               foreach ($oldparent_id as $k=>$v){
                   $member=pdo_get("ewei_shop_member",array("id"=>$v));
                   if ($member){
                       //获取粉丝总量中数据是否有
                       $agentcount=pdo_get("ewei_shop_member_agentcount",array("openid"=>$member["openid"]));
                       if ($agentcount){
                           if ($v==$oldparentid){
                               //上级为直推
                               $data["agentcount"]=$agentcount["agentcount"]-1;
                               $data["agentallcount"]=$agentcount["agentallcount"]-1;
                               if ($my["agentlevel"]==5){
                                   $data["shopkeepercount"]=$agentcount["shopkeepercount"]-1;
                                   $data["shopkeeperallcount"]=$agentcount["shopkeeperallcount"]-1;
                               }
                           }else{
                               $data["agentallcount"]=$agentcount["agentallcount"]-1;
                               if ($my["agentlevel"]==5){
                                   $data["shopkeeperallcount"]=$agentcount["shopkeeperallcount"]-1;
                               }
                               
                           }
                           $data["update_time"]=date("Y-m-d H:i:s");
                           pdo_update("ewei_shop_member_agentcount",$data,array("openid"=>$member["openid"]));
                       }
                   }
               }
               
            }
        }
        return true;
    }
}
?>