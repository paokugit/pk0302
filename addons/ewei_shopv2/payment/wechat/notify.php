<?php  error_reporting(0);
define("IN_MOBILE", true);
$input = file_get_contents("php://input");
libxml_disable_entity_loader(true);
if( !empty($input) && empty($_GET["out_trade_no"]) ) 
{
	$obj = simplexml_load_string($input, "SimpleXMLElement", LIBXML_NOCDATA);
	$data = json_decode(json_encode($obj), true);
	if( empty($data) ) 
	{
		exit( "fail" );
	}
	if( empty($data["version"]) && ($data["result_code"] != "SUCCESS" || $data["return_code"] != "SUCCESS") ) 
	{
		$result = array( "return_code" => "FAIL", "return_msg" => (empty($data["return_msg"]) ? $data["err_code_des"] : $data["return_msg"]) );
		echo array2xml($result);
		exit();
	}
	if( !empty($data["version"]) && ($data["result_code"] != "0" || $data["status"] != "0") ) 
	{
		exit( "fail" );
	}
	$get = $data;
}
else 
{
	$get = $_GET;
}
require(dirname(__FILE__) . "/../../../../framework/bootstrap.inc.php");
require(IA_ROOT . "/addons/ewei_shopv2/defines.php");
require(IA_ROOT . "/addons/ewei_shopv2/core/inc/functions.php");
require(IA_ROOT . "/addons/ewei_shopv2/core/inc/plugin_model.php");
require(IA_ROOT . "/addons/ewei_shopv2/core/inc/com_model.php");
new EweiShopWechatPay($get);
exit( "fail" );
class EweiShopWechatPay 
{
	public $get = NULL;
	public $type = NULL;
	public $total_fee = NULL;
	public $set = NULL;
	public $setting = NULL;
	public $sec = NULL;
	public $sign = NULL;
	public $isapp = false;
	public $is_jie = false;
	public function __construct($get) 
	{
		global $_W;
		$this->get = $get;
		$strs = explode(":", $this->get["attach"]);
		$this->type = intval($strs[1]);
		$this->total_fee = round($this->get["total_fee"] / 100, 2);
		$GLOBALS["_W"]["uniacid"] = intval($strs[0]);
		$_W["uniacid"] = intval($strs[0]);
		$this->init();
	}
	public function success() 
	{
		$result = array( "return_code" => "SUCCESS", "return_msg" => "OK" );
		echo array2xml($result);
		exit();
	}
	public function fail() 
	{
		$result = array( "return_code" => "FAIL", "return_msg" => "签名失败" );
		echo array2xml($result);
		exit();
	}
	public function init() 
	{
		if( $this->type == "0" ) 
		{
			$this->order();
		}
		else 
		{
			if( $this->type == "1" ) 
			{
				$this->recharge();
			}
			else 
			{
				if( $this->type == "2" ) 
				{
					$this->creditShop();
				}
				else 
				{
					if( $this->type == "3" ) 
					{
						$this->creditShopFreight();
					}
					else 
					{
						if( $this->type == "4" ) 
						{
							$this->coupon();
						}
						else 
						{
							if( $this->type == "5" ) 
							{
								$this->groups();
							}
							else 
							{
								if( $this->type == "6" ) 
								{
									$this->threen();
								}
								else 
								{
									if( $this->type == "10" ) 
									{
										$this->mr();
									}
									else 
									{
										if( $this->type == "11" ) 
										{
											$this->pstoreCredit();
										}
										else 
										{
											if( $this->type == "12" ) 
											{
												$this->pstore();
											}
											else 
											{
												if( $this->type == "13" ) 
												{
													$this->cashier();
												}
												else 
												{
													if( $this->type == "14" ) 
													{
														$this->wxapp_order();
													}
													else 
													{
														if( $this->type == "15" ) 
														{
															$this->wxapp_recharge();
														}
														else 
														{
															if( $this->type == "16" ) 
															{
																$this->wxapp_coupon();
															}
															else 
															{
																if( $this->type == "17" ) 
																{
																	$this->grant();
																}
																else 
																{
																	if( $this->type == "18" ) 
																	{
																		$this->plugingrant();
																	}
																	else 
																	{
																		if( $this->type == "19" ) 
																		{
																			$this->wxapp_groups();
																		}
																		else 
																		{
																			if( $this->type == "20" ) 
																			{
																				$this->wxapp_membercard();
																			}
																			else 
																			{
																				if( $this->type == "21" ) 
																				{
																					$this->membercard();
																				}
																				else
																				{
                                                                                    if($this->type == "30"){
                                                                                        $this->shopCode();
                                                                                    }
                                                                                    else{
                                                                                        if($this->type == "31"){
                                                                                            $this->myown();
                                                                                        }
                                                                                        else{
                                                                                            if($this->type == "32"){
                                                                                                $this->level();
                                                                                            }
                                                                                            else{
                                                                                                if($this->type == "33"){
                                                                                                    $this->level_express();
                                                                                                }
                                                                                                else{
                                                                                                    if($this->type == "34"){
                                                                                                        $this->limit();
                                                                                                    }elseif ($this->type=="99"){
                                                                                                        $this->acceleration();
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
																			}
																		}
																	}
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		$this->success();
	}
	public function order() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "order" );
		}
		$ordersn = $tid = $this->get["out_trade_no"];
		$count_ordersn = m("order")->countOrdersn($tid);
		$isborrow = 0;
		$borrowopenid = "";
		if( strpos($tid, "_borrow") !== false ) 
		{
			$tid = str_replace("_borrow", "", $tid);
			$isborrow = 1;
			$borrowopenid = $this->get["openid"];
		}
		if( strpos($tid, "_B") !== false ) 
		{
			$tid = str_replace("_B", "", $tid);
			$isborrow = 1;
			$borrowopenid = $this->get["openid"];
		}
		if( strexists($tid, "GJ") ) 
		{
			$tids = explode("GJ", $tid);
			list($tid, $ordersn2) = $tids;
			$sub_openid = $this->get["sub_openid"];
			$openid = $this->get["openid"];
			$openid = (empty($sub_openid) ? $openid : $sub_openid);
			if( 100 <= $ordersn2 ) 
			{
				pdo_update("ewei_shop_order", array( "ordersn2" => $ordersn2 ), array( "ordersn" => $tid, "openid" => $openid ));
			}
		}
		$ispeerpay = 0;
		if( 22 < strlen($tid) && $count_ordersn != 2 ) 
		{
			$tid2 = $tid;
			$ispeerpay = 1;
		}
		$paytype = 21;
		if( strexists($borrowopenid, "2088") || is_numeric($borrowopenid) ) 
		{
			$paytype = 22;
		}
		$tid = substr($tid, 0, 22);
		$order = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_order") . " WHERE ordersn = :ordersn AND uniacid = :uniacid", array( ":ordersn" => $tid, ":uniacid" => $_W["uniacid"] ));
		$sql = "SELECT * FROM " . tablename("core_paylog") . " WHERE `module`=:module AND `tid`=:tid  limit 1";
		$params = array( );
		$params[":tid"] = $tid;
		$params[":module"] = "ewei_shopv2";
		$log = pdo_fetch($sql, $params);
		if( !empty($log) && ($log["status"] == "0" || $ispeerpay) && ($log["fee"] == $this->total_fee || $ispeerpay) ) 
		{
			$transaction_id = $this->get["transaction_id"];
			$out_transaction_id = $this->get["out_transaction_id"];
			$transaction_id = (empty($transaction_id) ? $out_transaction_id : $transaction_id);
			if( $count_ordersn == 2 ) 
			{
				pdo_update("ewei_shop_order", array( "tradepaytype" => 21, "isborrow" => $isborrow, "borrowopenid" => $borrowopenid, "apppay" => ($this->isapp ? 1 : 0), "transid" => $transaction_id ), array( "ordersn_trade" => $log["tid"], "uniacid" => $log["uniacid"] ));
			}
			else 
			{
				pdo_update("ewei_shop_order", array( "paytype" => 21, "isborrow" => $isborrow, "borrowopenid" => $borrowopenid, "apppay" => ($this->isapp ? 1 : 0), "transid" => $transaction_id ), array( "ordersn" => $log["tid"], "uniacid" => $log["uniacid"] ));
			}
			$site = WeUtility::createModuleSite($log["module"]);
			m("order")->setOrderPayType($order["id"], $paytype);
			if( !empty($ispeerpay) ) 
			{
				$ispeerpay = m("order")->checkpeerpay($order["id"]);
			}
			if( !empty($ispeerpay) ) 
			{
				$openid = $this->get["openid"];
				$member = m("member")->getInfo($openid);
				m("order")->peerStatus(array( "pid" => $ispeerpay["id"], "uid" => $member["id"], "uname" => $member["nickname"], "usay" => "支持一下，么么哒!", "price" => $this->total_fee, "createtime" => time(), "openid" => $openid, "headimg" => $member["avatar"], "tid" => $tid2 ));
				if( $_W["config"]["db"]["slave_status"] == true ) 
				{
					sleep(1);
				}
				$peerpay_info = (double) pdo_fetchcolumn("select SUM(price) from " . tablename("ewei_shop_order_peerpay_payinfo") . " where pid=:pid limit 1", array( ":pid" => $ispeerpay["id"] ));
				if( $peerpay_info < $ispeerpay["peerpay_realprice"] ) 
				{
					$this->success();
				}
			}
			if( !is_error($site) ) 
			{
				$method = "payResult";
				if( method_exists($site, $method) ) 
				{
					$ret = array();
					$ret["acid"] = $log["acid"];
					$ret["uniacid"] = $log["uniacid"];
					$ret["result"] = "success";
					$ret["type"] = $log["type"];
					$ret["from"] = "return";
					$ret["tid"] = $log["tid"];
					$ret["user"] = $log["openid"];
					$ret["fee"] = $log["fee"];
					$ret["tag"] = $log["tag"];
					$result = $site->$method($ret);
					if( $result ) 
					{
						$log["tag"] = iunserializer($log["tag"]);
						$log["tag"]["transaction_id"] = $this->get["transaction_id"];
						$record = array( );
						$record["status"] = "1";
						$record["tag"] = iserializer($log["tag"]);
						pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
					}
				}
			}
		}
		else 
		{
			$this->fail();
		}
	}

	public function recharge() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "recharge" );
		}
		$logno = trim($this->get["out_trade_no"]);
		$isborrow = 0;
		$borrowopenid = "";
		if( strpos($logno, "_borrow") !== false ) 
		{
			$logno = str_replace("_borrow", "", $logno);
			$isborrow = 1;
			$borrowopenid = $this->get["openid"];
		}
		if( empty($logno) ) 
		{
			$this->fail();
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_member_log") . " WHERE `uniacid`=:uniacid and `logno`=:logno limit 1", array( ":uniacid" => $_W["uniacid"], ":logno" => $logno ));
		$OK = !empty($log) && empty($log["status"]) && $log["money"] == $this->total_fee;
		if( $OK ) 
		{
			pdo_update("ewei_shop_member_log", array( "status" => 1, "rechargetype" => "wechat", "isborrow" => $isborrow, "borrowopenid" => $borrowopenid, "apppay" => ($this->isapp ? 1 : 0) ), array( "id" => $log["id"] ));
			$shopset = m("common")->getSysset("shop");
			m("member")->setCredit($log["openid"], "credit2", $log["money"], array( 0, $shopset["name"] . "会员充值:wechatnotify:credit2:" . $log["money"] ));
			m("member")->setRechargeCredit($log["openid"], $log["money"]);
			com_run("sale::setRechargeActivity", $log);
			com_run("coupon::useRechargeCoupon", $log);
			m("notice")->sendMemberLogMessage($log["id"]);
			$member = m("member")->getMember($log["openid"]);
			$params = array( "nickname" => (empty($member["nickname"]) ? "未更新" : $member["nickname"]), "price" => $log["money"], "paytype" => "微信支付", "paytime" => date("Y-m-d H:i:s", time()) );
			com_run("printer::sendRechargeMessage", $params);
		}
	}
	public function creditShop() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "creditShop" );
		}
		$logno = trim($this->get["out_trade_no"]);
		if( empty($logno) ) 
		{
			exit();
		}
		$logno = str_replace("_borrow", "", $logno);
		if( p("creditshop") ) 
		{
			p("creditshop")->payResult($logno, "wechat", $this->total_fee, ($this->isapp ? true : false));
		}
	}
	public function creditShopFreight() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "creditShopFreight" );
		}
		$dispatchno = trim($this->get["out_trade_no"]);
		$dispatchno = str_replace("_borrow", "", $dispatchno);
		if( empty($dispatchno) ) 
		{
			exit();
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_creditshop_log") . " WHERE `dispatchno`=:dispatchno and `uniacid`=:uniacid  limit 1", array( ":uniacid" => $_W["uniacid"], ":dispatchno" => $dispatchno ));
		if( !empty($log) && $log["dispatchstatus"] < 0 ) 
		{
			pdo_update("ewei_shop_creditshop_log", array( "dispatchstatus" => 1 ), array( "dispatchno" => $dispatchno ));
		}
	}
	public function coupon() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "coupon" );
		}
		$logno = str_replace("_borrow", "", $this->get["out_trade_no"]);
		$log = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_coupon_log") . " WHERE `logno`=:logno and `uniacid`=:uniacid  limit 1", array( ":uniacid" => $_W["uniacid"], ":logno" => $logno ));
		$coupon = pdo_fetchcolumn("select money from " . tablename("ewei_shop_coupon") . " where id=:id limit 1", array( ":id" => $log["couponid"] ));
		if( $coupon == $this->total_fee ) 
		{
			com_run("coupon::payResult", $logno);
		}
	}
	public function wxapp_coupon() 
	{
		global $_W;
		$logno = str_replace("_borrow", "", $this->get["out_trade_no"]);
		$log = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_coupon_log") . " WHERE `logno`=:logno and `uniacid`=:uniacid  limit 1", array( ":uniacid" => $_W["uniacid"], ":logno" => $logno ));
		$coupon = pdo_fetchcolumn("select money from " . tablename("ewei_shop_coupon") . " where id=:id limit 1", array( ":id" => $log["couponid"] ));
		if( $coupon == $this->total_fee ) 
		{
			com_run("coupon::payResult", $logno);
		}
	}
	public function groups() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "groups" );
		}
		$orderno = trim($this->get["out_trade_no"]);
		$orderno = str_replace("_borrow", "", $orderno);
		if( empty($orderno) ) 
		{
			exit();
		}
		if( $this->is_jie ) 
		{
			pdo_update("ewei_shop_groups_order", array( "isborrow" => "1", "borrowopenid" => $this->get["openid"] ), array( "orderno" => $orderno, "uniacid" => $_W["uniacid"] ));
		}
		if( p("groups") ) 
		{
			p("groups")->payResult($orderno, "wechat", ($this->isapp ? true : false));
		}
	}
	public function threen() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "threen" );
		}
		$orderno = trim($this->get["out_trade_no"]);
		$orderno = str_replace("_borrow", "", $orderno);
		if( empty($orderno) ) 
		{
			exit();
		}
		if( $this->is_jie ) 
		{
			pdo_update("ewei_shop_threen_log", array( "isborrow" => "1", "borrowopenid" => $this->get["openid"] ), array( "logno" => $orderno, "uniacid" => $_W["uniacid"] ));
		}
		if( p("threen") ) 
		{
			p("threen")->payResult($orderno, "wechat", ($this->isapp ? true : false));
		}
	}
	public function grant() 
	{
		global $_W;
		$setting = pdo_fetch("select * from " . tablename("ewei_shop_system_grant_setting") . " where id = 1 limit 1 ");
		if( 0 < $setting["weixin"] ) 
		{
			ksort($this->get);
			$string1 = "";
			foreach( $this->get as $k => $v ) 
			{
				if( $v != "" && $k != "sign" ) 
				{
					$string1 .= (string) $k . "=" . $v . "&";
				}
			}
			$this->sign = strtoupper(md5($string1 . "key=" . $setting["apikey"]));
			if( $this->sign == $this->get["sign"] ) 
			{
				$order = pdo_fetch("select * from " . tablename("ewei_shop_system_grant_order") . " where logno = '" . $this->get["out_trade_no"] . "'");
				pdo_update("ewei_shop_system_grant_order", array( "paytime" => time(), "paystatus" => 1 ), array( "logno" => $this->get["out_trade_no"] ));
				$plugind = explode(",", $order["pluginid"]);
				$data = array( "logno" => $order["logno"], "uniacid" => $order["uniacid"], "code" => $order["code"], "type" => "pay", "month" => $order["month"], "isagent" => $order["isagent"], "createtime" => time() );
				foreach( $plugind as $key => $value ) 
				{
					$plugin = pdo_fetch("select `identity` from " . tablename("ewei_shop_plugin") . " where id = " . $value . " ");
					$data["identity"] = $plugin["identity"];
					$data["pluginid"] = $value;
					pdo_insert("ewei_shop_system_grant_log", $data);
					$id = pdo_insertid();
					if( m("grant") ) 
					{
						m("grant")->pluginGrant($id);
					}
				}
			}
		}
	}
	public function plugingrant() 
	{
		global $_W;
		$setting = pdo_fetch("select * from " . tablename("ewei_shop_system_plugingrant_setting") . " where 1 = 1 limit 1 ");
		if( 0 < $setting["weixin"] ) 
		{
			ksort($this->get);
			$string1 = "";
			foreach( $this->get as $k => $v ) 
			{
				if( $v != "" && $k != "sign" ) 
				{
					$string1 .= (string) $k . "=" . $v . "&";
				}
			}
			$this->sign = strtoupper(md5($string1 . "key=" . $setting["apikey"]));
			if( $this->sign == $this->get["sign"] ) 
			{
				$order = pdo_fetch("select * from " . tablename("ewei_shop_system_plugingrant_order") . " where logno = '" . $this->get["out_trade_no"] . "'");
				pdo_update("ewei_shop_system_plugingrant_order", array( "paytime" => time(), "paystatus" => 1 ), array( "logno" => $this->get["out_trade_no"] ));
				$plugind = explode(",", $order["pluginid"]);
				$data = array( "logno" => $order["logno"], "uniacid" => $order["uniacid"], "type" => "pay", "month" => $order["month"], "createtime" => time() );
				foreach( $plugind as $key => $value ) 
				{
					$plugin = pdo_fetch("select `identity` from " . tablename("ewei_shop_plugin") . " where id = " . $value . " ");
					$data["identity"] = $plugin["identity"];
					$data["pluginid"] = $value;
					pdo_query("update " . tablename("ewei_shop_system_plugingrant_plugin") . " set sales = sales + 1 where pluginid = " . $value . " ");
					pdo_insert("ewei_shop_system_plugingrant_log", $data);
					$id = pdo_insertid();
					if( p("grant") ) 
					{
						p("grant")->pluginGrant($id);
					}
				}
			}
		}
	}
	public function mr() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "mr" );
		}
		$ordersn = trim($this->get["out_trade_no"]);
		$isborrow = 0;
		$borrowopenid = "";
		if( strpos($ordersn, "_borrow") !== false ) 
		{
			$ordersn = str_replace("_borrow", "", $ordersn);
			$isborrow = 1;
			$borrowopenid = $this->get["openid"];
		}
		if( empty($ordersn) ) 
		{
			exit();
		}
		if( p("mr") ) 
		{
			$price = pdo_fetchcolumn("select payprice from " . tablename("ewei_shop_mr_order") . " where ordersn=:ordersn limit 1", array( ":ordersn" => $ordersn ));
			if( $price == $this->total_fee ) 
			{
				if( $isborrow == 1 ) 
				{
					pdo_update("ewei_shop_order", array( "isborrow" => $isborrow, "borrowopenid" => $borrowopenid ), array( "ordersn" => $ordersn ));
				}
				p("mr")->payResult($ordersn, "wechat");
			}
		}
	}
	public function pstoreCredit() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "pstoreCredit" );
		}
		$ordersn = trim($this->get["out_trade_no"]);
		$ordersn = str_replace("_borrow", "", $ordersn);
		if( empty($ordersn) ) 
		{
			exit();
		}
		if( p("pstore") ) 
		{
			p("pstore")->payResult($ordersn, $this->total_fee);
		}
	}
	public function pstore() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "pstore" );
		}
		$ordersn = trim($this->get["out_trade_no"]);
		$ordersn = str_replace("_borrow", "", $ordersn);
		if( empty($ordersn) ) 
		{
			exit();
		}
		if( p("pstore") ) 
		{
			p("pstore")->wechat_complete($ordersn);
		}
	}
	public function cashier() 
	{
		global $_W;
		$ordersn = trim($this->get["out_trade_no"]);
		if( empty($ordersn) ) 
		{
			exit();
		}
		if( p("cashier") ) 
		{
			p("cashier")->payResult($ordersn);
		}
	}
	public function wxapp_order() 
	{
		$tid = $this->get["out_trade_no"];
		if( strexists($tid, "GJ") ) 
		{
			$tids = explode("GJ", $tid);
			$tid = $tids[0];
		}
		$sql = "SELECT * FROM " . tablename("core_paylog") . " WHERE `module`=:module AND `tid`=:tid  limit 1";
		$params = array( );
		$params[":tid"] = $tid;
		$params[":module"] = "ewei_shopv2";
		$log = pdo_fetch($sql, $params);
		if( !empty($log) && $log["status"] == "0" && $log["fee"] == $this->total_fee ) 
		{
			$site = WeUtility::createModuleSite($log["module"]);
			if( !is_error($site) ) 
			{
				$method = "payResult";
				if( method_exists($site, $method) ) 
				{
					$ret = array( );
					$ret["acid"] = $log["acid"];
					$ret["uniacid"] = $log["uniacid"];
					$ret["result"] = "success";
					$ret["type"] = $log["type"];
					$ret["from"] = "return";
					$ret["tid"] = $log["tid"];
					$ret["user"] = $log["openid"];
					$ret["fee"] = $log["fee"];
					$ret["tag"] = $log["tag"];
					pdo_update("ewei_shop_order", array( "paytype" => 21, "apppay" => 2 ), array( "ordersn" => $log["tid"], "uniacid" => $log["uniacid"] ));
					$result = $site->$method($ret);
					if( $result ) 
					{
						$log["tag"] = iunserializer($log["tag"]);
						$log["tag"]["transaction_id"] = $this->get["transaction_id"];
						$record = array( );
						$record["status"] = "1";
						$record["tag"] = iserializer($log["tag"]);
						pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
					}
				}
			}
		}
		else 
		{
			$this->fail();
		}
	}
	public function wxapp_recharge() 
	{
		global $_W;
		$logno = trim($this->get["out_trade_no"]);
		if( empty($logno) ) 
		{
			$this->fail();
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_member_log") . " WHERE `uniacid`=:uniacid and `logno`=:logno limit 1", array( ":uniacid" => $_W["uniacid"], ":logno" => $logno ));
		$OK = !empty($log) && empty($log["status"]) && $log["money"] == $this->total_fee;
		if( $OK ) 
		{
			pdo_update("ewei_shop_member_log", array( "status" => 1, "rechargetype" => "wechat", "apppay" => 2 ), array( "id" => $log["id"] ));
			$shopset = m("common")->getSysset("shop");
			m("member")->setCredit($log["openid"], "credit2", $log["money"], array( 0, $shopset["name"] . "会员充值:wechatnotify:credit2:" . $log["money"] ));
			m("member")->setRechargeCredit($log["openid"], $log["money"]);
			com_run("sale::setRechargeActivity", $log);
			com_run("coupon::useRechargeCoupon", $log);
			m("notice")->sendMemberLogMessage($log["id"]);
		}
		else 
		{
			if( $log["money"] == $this->total_fee ) 
			{
				pdo_update("ewei_shop_member_log", array( "rechargetype" => "wechat", "apppay" => 2 ), array( "id" => $log["id"] ));
			}
		}
	}
	public function publicMethod() 
	{
		global $_W;
		if( empty($_W["uniacid"]) ) 
		{
			return false;
		}
		list($set, $payment) = m("common")->public_build();
		$this->set = $set;
		if( empty($payment["is_new"]) || $this->get["trade_type"] == "APP" ) 
		{
			$this->setting = uni_setting($_W["uniacid"], array( "payment" ));
			if( is_array($this->setting["payment"]) || $this->set["weixin_jie"] == 1 || $this->set["weixin_sub"] == 1 || $this->set["weixin_jie_sub"] == 1 || $this->get["trade_type"] == "APP" ) 
			{
				$this->is_jie = strpos($this->get["out_trade_no"], "_B") !== false || strpos($this->get["out_trade_no"], "_borrow") !== false;
				$sec_yuan = m("common")->getSec();
				$this->sec = iunserializer($sec_yuan["sec"]);
				if( $this->set["weixin_jie"] == 1 && $this->is_jie || $this->set["weixin_sub"] == 1 || $this->set["weixin_jie_sub"] == 1 && $this->is_jie ) 
				{
					if( $this->set["weixin_sub"] == 1 ) 
					{
						$wechat = array( "version" => 1, "key" => $this->sec["apikey_sub"], "apikey" => $this->sec["apikey_sub"] );
					}
					if( $this->set["weixin_jie"] == 1 && $this->is_jie ) 
					{
						$wechat = array( "version" => 1, "key" => $this->sec["apikey"], "apikey" => $this->sec["apikey"] );
					}
					if( $this->set["weixin_jie_sub"] == 1 && $this->is_jie ) 
					{
						$wechat = array( "version" => 1, "key" => $this->sec["apikey_jie_sub"], "apikey" => $this->sec["apikey_jie_sub"] );
					}
				}
				else 
				{
					if( $this->set["weixin"] == 1 ) 
					{
						$wechat = $this->setting["payment"]["wechat"];
						if( IMS_VERSION <= 0.8 ) 
						{
							$wechat["apikey"] = $wechat["signkey"];
						}
					}
				}
				if( $this->get["trade_type"] == "APP" && $this->set["app_wechat"] == 1 ) 
				{
					$this->isapp = true;
					$wechat = array( "version" => 1, "key" => $this->sec["app_wechat"]["apikey"], "apikey" => $this->sec["app_wechat"]["apikey"], "appid" => $this->sec["app_wechat"]["appid"], "mchid" => $this->sec["app_wechat"]["merchid"] );
				}
				if( !empty($wechat) ) 
				{
					ksort($this->get);
					$string1 = "";
					foreach( $this->get as $k => $v ) 
					{
						if( $v != "" && $k != "sign" ) 
						{
							$string1 .= (string) $k . "=" . $v . "&";
						}
					}
					$wechat["apikey"] = ($wechat["version"] == 1 ? $wechat["key"] : $wechat["apikey"]);
					$this->sign = strtoupper(md5($string1 . "key=" . $wechat["apikey"]));
					$this->get["openid"] = (isset($this->get["sub_openid"]) ? $this->get["sub_openid"] : $this->get["openid"]);
					if( $this->sign == $this->get["sign"] ) 
					{
						return true;
					}
				}
			}
		}
		else 
		{
			if( !is_error($payment) ) 
			{
				if( $this->get["sign_type"] == "RSA_1_1" || $this->get["sign_type"] == "RSA_1_256" ) 
				{
					$signPars = "";
					ksort($this->get);
					foreach( $this->get as $k => $v ) 
					{
						if( "sign" != $k && "" != $v ) 
						{
							$signPars .= $k . "=" . $v . "&";
						}
					}
					$signPars = substr($signPars, 0, strlen($signPars) - 1);
					$res = openssl_pkey_get_public(m("common")->chackKey($payment["app_qpay_public_key"]));
					if( $this->get["sign_type"] == "RSA_1_1" ) 
					{
						$result = (bool) openssl_verify($signPars, base64_decode($this->get["sign"]), $res);
						openssl_free_key($res);
						return $result;
					}
					if( $this->get["sign_type"] == "RSA_1_256" ) 
					{
						$result = (bool) openssl_verify($signPars, base64_decode($this->get["sign"]), $res, OPENSSL_ALGO_SHA256);
						openssl_free_key($res);
						return $result;
					}
				}
				else 
				{
					ksort($this->get);
					$string1 = "";
					foreach( $this->get as $k => $v ) 
					{
						if( $v != "" && $k != "sign" ) 
						{
							$string1 .= (string) $k . "=" . $v . "&";
						}
					}
					$this->sign = strtoupper(md5($string1 . "key=" . $payment["apikey"]));
					$this->get["openid"] = (isset($this->get["sub_openid"]) ? $this->get["sub_openid"] : $this->get["openid"]);
					if( $this->sign == $this->get["sign"] ) 
					{
						return true;
					}
				}
			}
		}
		return false;
	}
	public function wxapp_groups() 
	{
		$orderno = $this->get["out_trade_no"];
		$sql = "SELECT * FROM " . tablename("ewei_shop_groups_paylog") . " WHERE `tid`=:orderno  limit 1";
		$params = array( );
		$params[":orderno"] = $orderno;
		$log = pdo_fetch($sql, $params);
		if( !empty($log) && $log["status"] == "0" && $log["fee"] == $this->total_fee ) 
		{
			if( p("groups") ) 
			{
				pdo_update("ewei_shop_groups_paylog", array( "status" => "1" ), array( "id" => $log["id"] ));
				p("groups")->payResult($orderno, "wxapp");
			}
		}
		else 
		{
			$this->fail();
		}
	}
	public function wxapp_membercard() 
	{
		$orderno = $this->get["out_trade_no"];
		$sql = "SELECT * FROM " . tablename("core_paylog") . " WHERE `module`=:module AND `tid`=:tid  limit 1";
		$params = array( );
		$params[":tid"] = $orderno;
		$params[":module"] = "ewei_shopv2";
		$log = pdo_fetch($sql, $params);
		if( !empty($log) && $log["status"] == "0" && $log["fee"] == $this->total_fee ) 
		{
			$plugin_membercard = p("membercard");
			if( $plugin_membercard ) 
			{
				$log["tag"] = iunserializer($log["tag"]);
				$log["tag"]["transaction_id"] = $this->get["transaction_id"];
				$log["tag"]["pay_time"] = time();
				$record = array( );
				$record["status"] = "1";
				$record["tag"] = iserializer($log["tag"]);
				pdo_update("core_paylog", $record, array( "plid" => $log["plid"] ));
				$plugin_membercard->payResult($orderno, "wechat");
			}
		}
		else 
		{
			$this->fail();
		}
	}
	public function membercard() 
	{
		global $_W;
		if( !$this->publicMethod() ) 
		{
			exit( "membercard" );
		}
		$orderno = trim($this->get["out_trade_no"]);
		$orderno = str_replace("_borrow", "", $orderno);
		if( empty($orderno) ) 
		{
			exit();
		}
		if( $this->is_jie ) 
		{
			pdo_update("ewei_shop_member_card_order", array( "isborrow" => "1", "borrowopenid" => $this->get["openid"] ), array( "orderno" => $orderno, "uniacid" => $_W["uniacid"] ));
		}
		if( p("membercard") ) 
		{
			p("membercard")->payResult($orderno, "wechat", ($this->isapp ? true : false));
		}
	}

	/**
     * 商家收款码的后调
     */
	public function shopCode()
    {
        $input = file_get_contents('php://input');
        $obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_decode(json_encode($obj), true);
        $aa = [
            'log'=>json_encode($obj),
            'createtime'=>date('Y-m-d H:i:s',time()),
        ];
        pdo_insert('log',$aa);
        file_put_contents("../addons/ewei_shopv2/payment/wechat/pay.txt",$data);
        if (!$data) {
            exit("FAIL");
        }
        $res = $this->check_sign($data);
        if (!$res) {
            exit("FAIL");
        }
        $ordersn = $data['out_trade_no'];  //获得订单信息
        //如果失败  先获得分类  cate  == 1  卡路里   cate == 2  折扣宝
        $cate = substr($ordersn,2,1);
        //用ordersn订单号 查订单信息
        $order = pdo_fetch('select * from '.tablename('ewei_shop_order').' where ordersn = "'.$ordersn.'"');
        //计算付款者 是不是第一次给收款码付款
        //$count = pdo_count('ewei_shop_member_log',['openid'=>$order['openid'],'rechargetype'=>"scan",'status'=>1]);
        $count = pdo_count('ewei_shop_member_log',['openid'=>$order['openid'],'rechargetype'=>"wxscan",'status'=>1]);
        //从order里面获得openID 查用户的卡路里和折扣宝余额
        $member = pdo_fetch('select id,agentid,credit1,credit3 from '.tablename('ewei_shop_member').'where openid = "'.$order['openid'].'"');
        if ($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
            pdo_begin();
            try {
                //如果成功  修改订单的status 状态 和 用户日志   还有商户收款日志的  状态为成功
                $a = pdo_update('ewei_shop_order',['status'=>3,'paytime'=>strtotime($data['time_end']),'finishtime'=>strtotime($data['time_end'])],['ordersn'=>$ordersn]);
                //如果商家的收款信息merchid 是数字  就是商家收款  如果是字符串 就是个人收款码
                if(is_numeric($order['merchid'])){
                    //商家收款记录  ordersn
                    $b = pdo_update('ewei_shop_merch_log',['status'=>1],['ordersn'=>$ordersn]);
                }else{
                    //如果是个人收款  改变个人收款日志的状态
                    $b = pdo_update('ewei_shop_member_log',['status'=>1],['logno'=>$ordersn.$order['merchid']]);
                    //然后 查个人的个人资产的余额 个人收款资金加钱
                    $own_member = pdo_get('ewei_shop_member',['id'=>intval($order['merchid']),'uniacid'=>$order['uniacid']]);
                    $c = pdo_update('ewei_shop_member',['credit5'=>bcadd($own_member['credit5'],$order['price'],2)],['openid'=>$own_member['openid'],'uniacid'=>$order['uniacid']]);
                }
                //更改付款人的付款状态
                pdo_update('ewei_shop_member_log',['status'=>1],['logno'=>$ordersn]);
                //支付成功的话 给用户扣除的卡路里  和 折扣宝
                $mem_data = [];
                if(is_numeric($order['merchid'])){
                    //查这个商家的信息
                    $merch = pdo_get('ewei_shop_merch_user',['id'=>$order['merchid']]);
                    if($member['id'] != $merch['member_id']){
                        //商家的信息  openid信息的id
                       $own_id = $merch['member_id']?:0;
                    }
                }else{
                    //个人收款码的  id
                    $own_id = intval($order['merchid']);
                }
                //支付成功的话  且付款者没有上级  锁粉
                if($member['agentid'] == 0){
                    //如果是商家  那么也锁粉  然后把商家对应的member_id  赋值给mem_data
                    $mem_data['agentid'] = $own_id;
                }
                //查找收款码的拥有者的信息
                $own = pdo_get('ewei_shop_member',['id'=>$own_id]);
                //如果绑定的用户信息不为空   添加绑定日志
                if(!empty($own)){
                    $add = ['openid'=>$member['openid'],'item'=>'notify','value'=>'绑定上级:'.$member['openid'].'/'.$member['nickname'].',绑定上级id:'.$own['id'].'-'.$own['nickname'],'create_time'=>date('Y-m-d H:i:s',time())];
                    m('memberoperate')->addlog($add);
                    //粉丝
                    m("member")->fans($member["id"],$own_id);
                }
                //查找爸爸信息  如果有使用折扣宝
                if($member['agentid']){
                    $father = pdo_get('ewei_shop_member',['id'=>$member['agentid']]);
                }
                //查找爷爷信息
                if($father['agentid']){
                    $grandpa = pdo_get('ewei_shop_member',['id'=>$father['agentid']]);
                }
                if($cate == 1){
                    $credit1 = $member['credit1'] - ($order['goodsprice'] - $order['price']);
                    $mem_data['credit1'] = $credit1;
                    $mem_data['credit3'] = $member['credit3'];
                    $this->addmoney($order['openid'],$own,$father,$grandpa,$order['goodsprice'],$order['price'],1,"卡路里付款");
                }elseif ($cate == 2){
                    $credit3 = $member['credit3'] - ($order['goodsprice'] - $order['price']);
                    $mem_data['credit3'] = $credit3;
                    $mem_data['credit1'] = $member['credit1'];
                    $this->addmoney($order['openid'],$own,$father,$grandpa,$order['goodsprice'],$order['price'],3,"折扣宝付款");
                }
                //如果付款码方是第一次给收款码付款  就给他送一定量的折扣宝
                if($count == 0){
                    //首次使用收款码付款  给奖励对应的折扣宝数量
                    $mem_data['credit3'] += $order['price'];
                    m('game')->addCreditLog($order['openid'],3,$order['price'],"首次使用收款码付款奖励折扣宝");
                }
                $f = pdo_update('ewei_shop_member',$mem_data,['openid'=>$order['openid']]);
                //小程序消息发送
                $this->message($own['openid'],$order['price'],$order['merchid']);
                pdo_insert('log',['log'=>$a.$b.$c.$f,'createtime'=>date('Y-m-d H:i:s',time())]);
                pdo_commit();
            }catch(Exception $exception){
                pdo_rollback();
            }
        }
    }

    /**
     * 购买个人收款码的回调
     */
    public function myown()
    {
        $input = file_get_contents('php://input');
        $obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_decode(json_encode($obj), true);
        pdo_insert('log',['log'=>json_encode($data),'createtime'=>date('Y-m-d H:i:s',time())]);
        if (!$data) {
            exit("FAIL");
        }
        $res = $this->check_sign($data);
        if (!$res) {
            exit("FAIL");
        }
        $ordersn = $data['out_trade_no'];  //获得订单信息
        //用ordersn订单号 查订单信息
        $order = pdo_fetch('select * from '.tablename('ewei_shop_order').' where ordersn = "'.$ordersn.'"');
        if ($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
            pdo_begin();
            try {
                //如果成功  修改订单的status 状态 和 用户日志   还有商户收款日志的  状态为成功
                $a = pdo_update('ewei_shop_order',['status'=>3,'paytime'=>strtotime($data['time_end'])],['ordersn'=>$ordersn]);
                $b = pdo_update('ewei_shop_member_log',['status'=>1],['logno'=>$ordersn]);
                //改变用户的状态
                $c = pdo_update('ewei_shop_member',['is_own'=>1],['openid'=>$order['openid']]);
        	pdo_insert('log',['log'=>$a.$b.$c,'createtime'=>date('Y-m-d H:i:s')]);        
		pdo_commit();
            }catch(Exception $exception){
                pdo_rollback();
            }
        }
    }

    /**
     * 购买年卡的回调
     */
    public function level()
    {
        $input = file_get_contents('php://input');
        $obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_decode(json_encode($obj), true);
        if (!$data) {
            exit("FAIL");
        }
        $res = $this->check_sign($data);
        if (!$res) {
            exit("FAIL");
        }
        $ordersn = $data['out_trade_no'];  //获得订单信息
        //用ordersn订单号 查订单信息
        $order = pdo_fetch('select * from '.tablename('ewei_shop_order').' where ordersn = "'.$ordersn.'"');
        $member = pdo_get('ewei_shop_member',['openid'=>$order['openid']]);
        preg_match_all('/\d+/',$order['remark'],$arr);
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$arr[0][0]]);
        pdo_insert('log',['log'=>json_encode($level).$arr[0][0].$order['remark'],'createtime'=>date('Y-m-d H:i:s',time())]);
        if($member['agentid'] != 0){
            $father = pdo_get('ewei_shop_member',['id'=>$member['agentid']]);
        }
        if ($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
            pdo_begin();
            try {
                //如果成功  修改订单的status 状态 和 用户日志   还有商户收款日志的  状态为成功
                pdo_update('ewei_shop_order',['status'=>3,'paytime'=>strtotime($data['time_end']),'finishtime'=>strtotime($data['time_end'])],['ordersn'=>$ordersn]);
                pdo_update('ewei_shop_member_log',['status'=>1],['logno'=>$ordersn]);
                //如果用户已买年卡  则给到期时间加1年   并且到期时间大于当前时间   小于 代表已过期
                if($member['expire_time'] && $member['expire_time'] > time()){
                    $endtime = strtotime('+1 year',$member['expire_time']);
                    $expire_time =  strtotime(date('Y-m',$member['expire_time']));
                }else{
                    $endtime = strtotime('+1 year');
                    $expire_time = strtotime(date('Y-m',time()));
                }
                //改变用户的状态
                $a = $this->add_record($order['openid'],$expire_time,$level);
                $b = pdo_update('ewei_shop_member',['is_open'=>1,'expire_time'=>$endtime],['openid'=>$order['openid']]);
                pdo_insert('log',['log'=>$a.$b,'createtime'=>date('Y-m-d H:i:s',time())]);
                //给购买年卡的会员的上级加150贡献值
                m('member')->setCredit($father['openid'],'credit4',150,'下级购买年卡奖励');
                pdo_commit();
            }catch(Exception $exception){
                pdo_rollback();
            }
        }
    }


    /**
     * 验签
     * @param $arr
     * @return bool
     */
    public function check_sign($arr)
    {
        $sign = $arr['sign'];
        unset($arr['sign']);
        $config = pdo_getcolumn('ewei_shop_payment',['id'=>1],'apikey');
        $skey = $config;
        ksort($arr, SORT_STRING);
        $stringA = '';
        foreach ($arr as $key => $val) {
            if ($val != null) {
                $stringA .= $key . '=' . $val . '&';
            }
        }
        $stringA .= 'key=' . $skey;
        $check_sign = strtoupper(MD5($stringA));
        if ($sign != $check_sign) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 给用户加  年卡发放记录
     * @param $openid
     * @param $time
     * @param $level
     * @return bool
     */
    public function add_record($openid,$time,$level)
    {
        global $_W;
        //给用户加  年卡发放记录
        for ($i=0;$i<12;$i++){
            $data = [
                'uniacid'=>$_W['uniacid'],
                'month'=>date('Ym',strtotime('+'.$i.' month',$time)),
                'openid'=>$openid,
                'level_id'=>$level['id'],
                'level_name'=>$level['level_name'],
                //'createtime'=>$i == 0 ? time() : strtotime(date('Ym',strtotime('+'.$i.' month',$time))."10"),
		'createtime' => strtotime(date('Ym',strtotime('+'.$i.' month',$time))."10"),
            ];
            //如果已经加过发放记录   就继续结束
            if(pdo_exists('ewei_shop_level_record',['openid'=>$openid,'month'=>$data['month'],'level_id'=>$data['level_id']])){
                continue;
            }else{
                pdo_insert('ewei_shop_level_record',$data);
            }
        }
        return true;
    }

    /**
     * @param $openid
     * @param $merch
     * @param $father
     * @param $grandpa
     * @param $goodsprice
     * @param $price
     * @param $type
     * @param $remark
     */
    public function addmoney($openid,$merch,$father,$grandpa,$goodsprice,$price,$type,$remark){
        //order表的goodsprice  减去  price 等于折扣宝的金额  乘以0.5是收款者的奖励  加到收款者的折扣宝  credit1卡路里  2余额  3折扣宝  4贡献值  5个人资产
        if($goodsprice-$price > 0){   //如果用折扣宝付款了
            //添加日志
            m('game')->addCreditLog($openid,$type,-($goodsprice-$price),$remark);
            pdo_update('ewei_shop_member',['credit'.$type=>bcadd($merch['credit'.$type],bcsub($goodsprice,$price,2),2)],['openid'=>$merch['openid']]);
            //写入收款人的日志
            m('game')->addCreditLog($merch['openid'],$type,$goodsprice-$price,0,"用户".$remark."的奖励");
        }
        //如果爸爸存在 给爸爸奖励
        if($father && $price * 0.01 > 0){
            //order表的price是交易金额  乘以0.01是爸爸的奖励  加到爸爸的贡献者
            pdo_update('ewei_shop_member',['credit4'=>bcadd($father['credit4'],bcmul($price,0.01,2),2)],['openid'=>$father['openid']]);
            //写入爸爸的收入日志
            m('game')->addCreditLog($father['openid'],4,$price*0.01,"下级付款奖励贡献值".$price*0.01);
        }
        //如果爷爷存在 给爷爷奖励
       if($grandpa && $price * 0.01 > 0){
           //order表的price是交易金额  乘以0.01是爷爷的奖励  加到爷爷的贡献者
           pdo_update('ewei_shop_member',['credit4'=>bcadd($grandpa['credit4'],bcmul($price,0.01,2),2)],['openid'=>$grandpa['openid']]);
           //写入爷爷的收入日志
           m('game')->addCreditLog($grandpa['openid'],4,$price*0.01,"下级付款奖励贡献值".$price*0.01);
       }
    }

    /**
     * 收款码付款成功后  发消息通知
     * @param $openid
     * @param $money
     * @param $merchid
     */
    public function message($openid,$money,$merchid)
    {
        $postdata=array(
            'keyword1'=>array(
                'value'=>$money,
                'color' => '#ff510'
            ),
            'keyword3'=>array(
                'value'=>date("Y-m-d",time()),
                'color' => '#ff510'
            ),
        );
        if(is_numeric($merchid)){
            $postdata['keyword2'] = array(
                'value'=>'商家收款码收款',
                'color' => '#ff510',
            );
        }else{
            $postdata['keyword2'] = array(
                'value'=>'个人收款码收款',
                'color' => '#ff510',
            );
        }
        $res = p("app")->mysendNotice($openid, $postdata,'', "qN-Wi2Jw8HnheTTuJRFivIKrevwk70m8lvH4mnf_ad0");
        pdo_insert('log',['log'=>$openid.json_encode($res),'createtime'=>date('Y-m-d H:i:s')]);
    }

    /**
     * 年卡的物品的领取支付回调
     */
    public function level_express()
    {
        $input = file_get_contents('php://input');
        $obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_decode(json_encode($obj), true);
        if (!$data) {
            exit("FAIL");
        }
        $res = $this->check_sign($data);
        if (!$res) {
            exit("FAIL");
        }
        $ordersn = $data['out_trade_no'];  //获得订单信息
        //用ordersn订单号 查订单信息
        $order = pdo_fetch('select * from '.tablename('ewei_shop_order').' where ordersn = "'.$ordersn.'"');
        $month = date('Ym');
        $level_id = mb_substr($ordersn,2,1);
        $record = pdo_get('ewei_shop_level_record',['openid'=>$order['openid'],'month'=>$month,'level_id'=>$level_id]);
        if($record['status'] == 0){
            //更新领取记录的状态
            pdo_update('ewei_shop_level_record',['status'=>1,'updatetime'=>time()],['openid'=>$order['openid'],'level_id'=>$level_id,'month'=>$month]);
            //更新订单表信息
            pdo_update('ewei_shop_order',['status'=>1,'paytime'=>strtotime($data['time_end'])],['ordersn'=>$ordersn]);
        }
        //m('member')->setCredit($order['openid'],'credit2',$order['price'],'年卡".$record["month"]."权益');
    }

    /**
     * 购买限额宝的回调
     */
    public function limit()
    {
        $input = file_get_contents('php://input');
        $obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_decode(json_encode($obj), true);
        if (!$data) {
            exit("FAIL");
        }
        $res = $this->check_sign($data);
        if (!$res) {
            exit("FAIL");
        }
        $ordersn = $data['out_trade_no'];  //获得订单信息
        //用ordersn订单号 查订单信息
        $order = pdo_fetch('select * from '.tablename('ewei_shop_member_limit_order').' where ordersn = "'.$ordersn.'"');
        pdo_update('ewei_shop_member_limit_order',['status'=>1],['id'=>$order['id']]);
    }
    //加速回调
    public function acceleration(){
        
        $input = file_get_contents('php://input');
        $obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_decode(json_encode($obj), true);
        if (!$data) {
            exit("FAIL");
        }
        $res = $this->check_sign($data);
        if (!$res) {
            exit("FAIL");
        }
        
        $order_sn = $data['out_trade_no'];  //获得订单信息
        //测试
        $d["content"]=$order_sn; 
        pdo_insert("ims_ewei_shop_member_cs",$d);
        
        //用ordersn订单号 查订单信息
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
        echo false;
        
    }
}
?>