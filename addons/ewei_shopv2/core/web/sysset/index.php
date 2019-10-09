<?php  
if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
class Index_EweiShopV2Page extends WebPage 
{
	public function main() 
	{
		if( cv("sysset.shop") ) 
		{
			header("location: " . webUrl("sysset/shop"));
		}
		else 
		{
			if( cv("sysset.follow") ) 
			{
				header("location: " . webUrl("sysset/follow"));
			}
			else 
			{
				if( cv("sysset.wap") ) 
				{
					header("location: " . webUrl("sysset/wap"));
				}
				else 
				{
					if( cv("sysset.pcset") ) 
					{
						header("location: " . webUrl("sysset/pcset"));
					}
					else 
					{
						if( cv("sysset.notice") ) 
						{
							header("location: " . webUrl("sysset/notice"));
						}
						else 
						{
							if( cv("sysset.trade") ) 
							{
								header("location: " . webUrl("sysset/trade"));
							}
							else 
							{
								if( cv("sysset.payset") ) 
								{
									header("location: " . webUrl("sysset/payset"));
								}
								else 
								{
									if( cv("sysset.templat") ) 
									{
										header("location: " . webUrl("sysset/templat"));
									}
									else 
									{
										if( cv("sysset.member") ) 
										{
											header("location: " . webUrl("sysset/member"));
										}
										else 
										{
											if( cv("sysset.category") ) 
											{
												header("location: " . webUrl("sysset/category"));
											}
											else 
											{
												if( cv("sysset.contact") ) 
												{
													header("location: " . webUrl("sysset/contact"));
												}
												else 
												{
													if( cv("sysset.qiniu") ) 
													{
														header("location: " . webUrl("sysset/qiniu"));
													}
													else 
													{
														if( cv("sysset.sms.set") ) 
														{
															header("location: " . webUrl("sysset/sms/set"));
														}
														else 
														{
															if( cv("sysset.sms.temp") ) 
															{
																header("location: " . webUrl("sysset/sms/temp"));
															}
															else 
															{
																if( cv("sysset.close") ) 
																{
																	header("location: " . webUrl("sysset/close"));
																}
																else 
																{
																	if( cv("sysset.tmessage") ) 
																	{
																		header("location: " . webUrl("sysset/tmessage"));
																	}
																	else 
																	{
																		if( cv("sysset.cover") ) 
																		{
																			header("location: " . webUrl("sysset/cover"));
																		}
																		else 
																		{
																			if( cv("sysset.area") ) 
																			{
																				header("location: " . webUrl("sysset/area"));
																			}
																			else 
																			{
																				if( cv("sysset.notice_redis") ) 
																				{
																					header("location: " . webUrl("sysset/notice_redis"));
																				}
																				else {
                                                                                    if (cv("sysset.game")) {
                                                                                        header("location: " . webUrl("sysset/game"));
                                                                                    } else {
                                                                                        header("location: " . webUrl());
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
	public function shop() 
	{
		global $_W;
		global $_GPC;
		$data = m("common")->getSysset("shop");
		if( $_W["ispost"] ) 
		{
			ca("sysset.shop.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$data["name"] = trim($data["name"]);
			$data["img"] = save_media($data["img"]);
			$data["logo"] = save_media($data["logo"]);
			$data["signimg"] = save_media($data["signimg"]);
			$data["saleout"] = save_media($data["saleout"]);
			$data["loading"] = save_media($data["loading"]);
			$data["diycode"] = $_POST["data"]["diycode"];
			m("common")->updateSysset(array( "shop" => $data ));
			plog("sysset.shop.edit", "修改系统设置-商城设置");
			show_json(1);
		}
		include($this->template("sysset/index"));
	}
	public function follow() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			ca("sysset.follow.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$data["logo"] = save_media($data["icon"]);
			$data["desc"] = str_replace(array( "\r\n", "\r", "\n" ), "", trim($data["desc"]));
			m("common")->updateSysset(array( "share" => $data ));
			plog("sysset.follow.edit", "修改系统设置-分享及关注设置");
			show_json(1);
		}
		$data = m("common")->getSysset("share");
		include($this->template());
	}
	public function settemplateid() 
	{
		global $_W;
		global $_GPC;
		$tag = $_GPC["tag"];
		load()->func("communication");
		$account = m("common")->getAccount();
		$token = $account->fetch_token();
		$url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=" . $token;
		$c = ihttp_request($url);
		$result = json_decode($c["content"], true);
		if( !is_array($result) ) 
		{
			show_json(1, array( "status" => 0, "messages" => "微信接口错误.", "tag" => $tag ));
		}
		if( !empty($result["errcode"]) ) 
		{
			show_json(1, array( "status" => 0, "messages" => $result["errmsg"], "tag" => $tag ));
		}
		$error_message = "";
		$templatenum = count($result["template_list"]);
		$templatetype = pdo_fetch("select `name`,templatecode,content  from " . tablename("ewei_shop_member_message_template_type") . " where typecode=:typecode  limit 1", array( ":typecode" => $tag ));
		if( empty($templatetype) ) 
		{
			show_json(1, array( "status" => 0, "messages" => "默认模板信息错误", "tag" => $tag ));
		}
		$content = str_replace(array( "\r\n", "\r", "\n", " " ), "", $templatetype["content"]);
		$content = str_replace(array( "：" ), ":", $content);
		$issnoet = true;
		foreach( $result["template_list"] as $key => $value ) 
		{
			$valuecontent = str_replace(array( "\r\n", "\r", "\n", " " ), "", $value["content"]);
			$valuecontent = str_replace(array( "：" ), ":", $valuecontent);
			if( $valuecontent == $content ) 
			{
				$issnoet = false;
				$defaulttemp = pdo_fetch("select 1  from " . tablename("ewei_shop_member_message_template_default") . " where typecode=:typecode and uniacid=:uniacid  limit 1", array( ":typecode" => $tag, ":uniacid" => $_W["uniacid"] ));
				if( empty($defaulttemp) ) 
				{
					pdo_insert("ewei_shop_member_message_template_default", array( "typecode" => $tag, "uniacid" => $_W["uniacid"], "templateid" => $value["template_id"] ));
				}
				else 
				{
					pdo_update("ewei_shop_member_message_template_default", array( "templateid" => $value["template_id"] ), array( "typecode" => $tag, "uniacid" => $_W["uniacid"] ));
				}
				show_json(1, array( "status" => 1, "tag" => $tag ));
			}
		}
		if( $issnoet ) 
		{
			if( 25 <= $templatenum ) 
			{
				show_json(1, array( "status" => 0, "messages" => "开启" . $templatetype["name"] . "失败！！您的可用微信模板消息数量达到上限，请删除部分后重试！！", "tag" => $tag ));
			}
			$bb = "{\"template_id_short\":\"" . $templatetype["templatecode"] . "\"}";
			$account = m("common")->getAccount();
			$token = $account->fetch_token();
			$url = "https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token=" . $token;
			$ch1 = curl_init();
			curl_setopt($ch1, CURLOPT_URL, $url);
			curl_setopt($ch1, CURLOPT_POST, 1);
			curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch1, CURLOPT_POSTFIELDS, $bb);
			$c = curl_exec($ch1);
			$result = @json_decode($c, true);
			if( !is_array($result) ) 
			{
				show_json(1, array( "status" => 0, "messages" => "微信接口错误.", "tag" => $tag ));
			}
			if( !empty($result["errcode"]) ) 
			{
				if( strstr($result["errmsg"], "template conflict with industry hint") ) 
				{
					show_json(1, array( "status" => 0, "messages" => "默认模板与公众号所属行业冲突,请将公众平台模板消息所在行业选择为： IT科技/互联网|电子商务， 其他/其他", "tag" => $tag ));
				}
				else 
				{
					if( strstr($result["errmsg"], "system error hint") ) 
					{
						show_json(1, array( "status" => 0, "messages" => "微信接口系统繁忙,请稍后再试!", "tag" => $tag ));
					}
					else 
					{
						if( strstr($result["errmsg"], "invalid industry id hint") ) 
						{
							show_json(1, array( "status" => 0, "messages" => "微信接口系统繁忙,请稍后再试!", "tag" => $tag ));
						}
						else 
						{
							if( strstr($result["errmsg"], "access_token is invalid or not latest hint") ) 
							{
								show_json(1, array( "status" => 0, "messages" => "微信证书无效，请检查微擎access_token设置", "tag" => $tag ));
							}
							else 
							{
								show_json(1, array( "status" => 0, "messages" => $result["errmsg"], "tag" => $tag ));
							}
						}
					}
				}
			}
			else 
			{
				$defaulttemp = pdo_fetch("select 1  from " . tablename("ewei_shop_member_message_template_default") . " where typecode=:typecode and uniacid=:uniacid  limit 1", array( ":typecode" => $tag, ":uniacid" => $_W["uniacid"] ));
				if( empty($defaulttemp) ) 
				{
					pdo_insert("ewei_shop_member_message_template_default", array( "typecode" => $tag, "uniacid" => $_W["uniacid"], "templateid" => $result["template_id"] ));
				}
				else 
				{
					pdo_update("ewei_shop_member_message_template_default", array( "templateid" => $result["template_id"] ), array( "typecode" => $tag, "uniacid" => $_W["uniacid"] ));
				}
			}
		}
		show_json(1, array( "status" => 1, "tag" => $tag ));
	}
	public function notice() 
	{
		global $_W;
		global $_GPC;
		$data = m("common")->getSysset("notice", false);
		$salers = array( );
		if( isset($data["openid"]) && !empty($data["openid"]) ) 
		{
			$openids = array( );
			$strsopenids = explode(",", $data["openid"]);
			foreach( $strsopenids as $openid ) 
			{
				$openids[] = "'" . $openid . "'";
			}
			$salers = pdo_fetchall("select id,nickname,avatar,openid from " . tablename("ewei_shop_member") . " where openid in (" . implode(",", $openids) . ") and uniacid=" . $_W["uniacid"]);
		}
		$salers2 = array( );
		if( isset($data["openid2"]) && !empty($data["openid2"]) ) 
		{
			$openids2 = array( );
			$strsopenids2 = explode(",", $data["openid2"]);
			foreach( $strsopenids2 as $openid2 ) 
			{
				$openids2[] = "'" . $openid2 . "'";
			}
			$salers2 = pdo_fetchall("select id,nickname,avatar,openid from " . tablename("ewei_shop_member") . " where openid in (" . implode(",", $openids2) . ") and uniacid=" . $_W["uniacid"]);
		}
		$salers3 = array( );
		if( isset($data["openid3"]) && !empty($data["openid3"]) ) 
		{
			$openids3 = array( );
			$strsopenids3 = explode(",", $data["openid3"]);
			foreach( $strsopenids3 as $openid3 ) 
			{
				$openids3[] = "'" . $openid3 . "'";
			}
			$salers3 = pdo_fetchall("select id,nickname,avatar,openid from " . tablename("ewei_shop_member") . " where openid in (" . implode(",", $openids3) . ") and uniacid=" . $_W["uniacid"]);
		}
		$salers4 = array( );
		if( isset($data["openid4"]) && !empty($data["openid4"]) ) 
		{
			$openids4 = array( );
			$strsopenids4 = explode(",", $data["openid4"]);
			foreach( $strsopenids4 as $openid4 ) 
			{
				$openids4[] = "'" . $openid4 . "'";
			}
			$salers4 = pdo_fetchall("select id,nickname,avatar,openid from " . tablename("ewei_shop_member") . " where openid in (" . implode(",", $openids4) . ") and uniacid=" . $_W["uniacid"]);
		}
		$opensms = com("sms");
		if( $_W["ispost"] ) 
		{
			ca("sysset.notice.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			if( is_array($_GPC["openids"]) ) 
			{
				$data["openid"] = implode(",", $_GPC["openids"]);
			}
			else 
			{
				$data["openid"] = "";
			}
			if( is_array($_GPC["openids2"]) ) 
			{
				$data["openid2"] = implode(",", $_GPC["openids2"]);
			}
			else 
			{
				$data["openid2"] = "";
			}
			if( is_array($_GPC["openids3"]) ) 
			{
				$data["openid3"] = implode(",", $_GPC["openids3"]);
			}
			else 
			{
				$data["openid3"] = "";
			}
			if( is_array($_GPC["openids4"]) ) 
			{
				$data["openid4"] = implode(",", $_GPC["openids4"]);
			}
			else 
			{
				$data["openid4"] = "";
			}
			if( empty($data["willcancel_close_advanced"]) ) 
			{
				$uniacids = m("cache")->get("willcloseuniacid", "global");
				if( !is_array($uniacids) ) 
				{
					$uniacids = array( );
				}
				if( !in_array($_W["uniacid"], $uniacids) ) 
				{
					$uniacids[] = $_W["uniacid"];
					m("cache")->set("willcloseuniacid", $uniacids, "global");
				}
			}
			else 
			{
				$uniacids = m("cache")->get("willcloseuniacid", "global");
				if( is_array($uniacids) && in_array($_W["uniacid"], $uniacids) ) 
				{
					$datas = array( );
					foreach( $uniacids as $uniacid ) 
					{
						if( $uniacid != $_W["uniacid"] ) 
						{
							$datas[] = $uniacid;
						}
					}
					m("cache")->set("willcloseuniacid", $datas, "global");
				}
			}
			m("common")->updateSysset(array( "notice" => $data ));
			plog("sysset.notice.edit", "修改系统设置-模板消息通知设置");
			show_json(1);
		}
		$template_list = pdo_fetchall("SELECT id,title,typecode FROM " . tablename("ewei_shop_member_message_template") . " WHERE uniacid=:uniacid ", array( ":uniacid" => $_W["uniacid"] ));
		$templatetype_list = pdo_fetchall("SELECT * FROM  " . tablename("ewei_shop_member_message_template_type"));
		$template_group = array( );
		foreach( $templatetype_list as $type ) 
		{
			$templates = array( );
			foreach( $template_list as $template ) 
			{
				if( $template["typecode"] == $type["typecode"] ) 
				{
					$templates[] = $template;
				}
			}
			$template_group[$type["typecode"]] = $templates;
		}
		if( $opensms ) 
		{
			$smsset = com("sms")->sms_set();
			if( empty($smsset["juhe"]) && empty($smsset["dayu"]) && empty($smsset["emay"]) && empty($smsset["aliyun"]) && empty($smsset["aliyun_new"]) ) 
			{
				$opensms = false;
			}
			$template_sms = com("sms")->sms_temp();
		}
		include($this->template());
	}
	public function trade() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			ca("sysset.trade.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			if( $data["maxcredit"] < 0 ) 
			{
				$data["maxcredit"] = 0;
			}
			if( !empty($data["withdrawcharge"]) ) 
			{
				$data["withdrawcharge"] = trim($data["withdrawcharge"]);
				$data["withdrawcharge"] = floatval(trim($data["withdrawcharge"], "%"));
			}
			$data["minimumcharge"] = floatval(trim($data["minimumcharge"]));
			$data["withdrawbegin"] = floatval(trim($data["withdrawbegin"]));
			$data["withdrawend"] = floatval(trim($data["withdrawend"]));
			$data["nodispatchareas"] = serialize($data["nodispatchareas"]);
			$data["nodispatchareas_code"] = serialize($data["nodispatchareas_code"]);
			$data["withdrawcashweixin"] = intval($data["withdrawcashweixin"]);
			$data["withdrawcashalipay"] = intval($data["withdrawcashalipay"]);
			$data["withdrawcashcard"] = intval($data["withdrawcashcard"]);
			if( !empty($data["closeorder"]) ) 
			{
				$data["closeorder"] = intval($data["closeorder"]);
			}
			if( !empty($data["willcloseorder"]) ) 
			{
				$data["willcloseorder"] = intval($data["willcloseorder"]);
			}
			if( is_null($data["invoice_entity"]) ) 
			{
				show_json(0, "请至少选择一种发票类型");
			}
			rsort($data["invoice_entity"]);
			$data["invoice_entity"] = implode("", $data["invoice_entity"]);
			switch( $data["invoice_entity"] ) 
			{
				case "10": $data["invoice_entity"] = 2;
				break;
				case "1": $data["invoice_entity"] = 1;
				break;
				default: $data["invoice_entity"] = 0;
			}
			m("common")->updateSysset(array( "trade" => $data ));
			plog("sysset.trade.edit", "修改系统设置-交易设置");
			show_json(1);
		}
		$areas = m("common")->getAreas();
		$data = m("common")->getSysset("trade");
		$area_set = m("util")->get_area_config_set();
		$new_area = intval($area_set["new_area"]);
		$data["nodispatchareas"] = unserialize($data["nodispatchareas"]);
		$data["nodispatchareas_code"] = unserialize($data["nodispatchareas_code"]);
		include($this->template());
	}
	protected function upload_cert($fileinput) 
	{
		global $_W;
		$path = IA_ROOT . "/addons/ewei_shopv2/cert";
		load()->func("file");
		mkdirs($path);
		$f = $fileinput . "_" . $_W["uniacid"] . ".pem";
		$outfilename = $path . "/" . $f;
		$filename = $_FILES[$fileinput]["name"];
		$tmp_name = $_FILES[$fileinput]["tmp_name"];
		if( !empty($filename) && !empty($tmp_name) ) 
		{
			$ext = strtolower(substr($filename, strrpos($filename, ".")));
			if( $ext != ".pem" ) 
			{
				$errinput = "";
				if( $fileinput == "weixin_cert_file" ) 
				{
					$errinput = "CERT文件格式错误";
				}
				else 
				{
					if( $fileinput == "weixin_key_file" ) 
					{
						$errinput = "KEY文件格式错误";
					}
					else 
					{
						if( $fileinput == "weixin_root_file" ) 
						{
							$errinput = "ROOT文件格式错误";
						}
					}
				}
				show_json(0, $errinput . ",请重新上传!");
			}
			return file_get_contents($tmp_name);
		}
		return "";
	}
	public function payset() 
	{
		global $_W;
		global $_GPC;
		$sec = m("common")->getSec();
		$sec = iunserializer($sec["sec"]);
		if( $_W["ispost"] ) 
		{
			ca("sysset.payset.edit");
			if( $_FILES["app_wechat_cert_file"]["name"] ) 
			{
				$sec["app_wechat"]["cert"] = $this->upload_cert("app_wechat_cert_file");
			}
			if( $_FILES["app_wechat_key_file"]["name"] ) 
			{
				$sec["app_wechat"]["key"] = $this->upload_cert("app_wechat_key_file");
			}
			if( $_FILES["app_wechat_root_file"]["name"] ) 
			{
				$sec["app_wechat"]["root"] = $this->upload_cert("app_wechat_root_file");
			}
			$sec["app_wechat"]["appid"] = trim($_GPC["data"]["app_wechat_appid"]);
			$sec["app_wechat"]["appsecret"] = trim($_GPC["data"]["app_wechat_appsecret"]);
			$sec["app_wechat"]["merchname"] = trim($_GPC["data"]["app_wechat_merchname"]);
			$sec["app_wechat"]["merchid"] = trim($_GPC["data"]["app_wechat_merchid"]);
			$sec["app_wechat"]["apikey"] = trim($_GPC["data"]["app_wechat_apikey"]);
			$sec["alipay_pay"] = (is_array($_GPC["data"]["alipay_pay"]) ? $_GPC["data"]["alipay_pay"] : array( ));
			$sec["app_alipay"]["public_key"] = trim($_GPC["data"]["app_alipay_public_key"]);
			$sec["app_alipay"]["private_key"] = trim($_GPC["data"]["app_alipay_private_key"]);
			$sec["app_alipay"]["public_key_rsa2"] = trim($_GPC["data"]["app_alipay_public_key_rsa2"]);
			$sec["app_alipay"]["private_key_rsa2"] = trim($_GPC["data"]["app_alipay_private_key_rsa2"]);
			$sec["app_alipay"]["appid"] = trim($_GPC["data"]["app_alipay_appid"]);
			pdo_update("ewei_shop_sysset", array( "sec" => iserializer($sec) ), array( "uniacid" => $_W["uniacid"] ));
			$inputdata = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$data = array( );
			$data["weixin_id"] = intval($inputdata["weixin_id"]);
			$data["weixin"] = intval($inputdata["weixin"]);
			$data["weixin_sub"] = intval($inputdata["weixin_sub"]);
			$data["weixin_jie"] = intval($inputdata["weixin_jie"]);
			$data["weixin_jie_sub"] = intval($inputdata["weixin_jie_sub"]);
			$data["alipay"] = intval($inputdata["alipay"]);
			$data["alipay_id"] = intval($inputdata["alipay_id"]);
			$data["credit"] = intval($inputdata["credit"]);
			$data["cash"] = intval($inputdata["cash"]);
			$data["app_wechat"] = intval($inputdata["app_wechat"]);
			$data["app_alipay"] = intval($inputdata["app_alipay"]);
			$data["paytype"] = (isset($inputdata["paytype"]) ? $inputdata["paytype"] : array( ));
			m("common")->updateSysset(array( "pay" => $data ));
			plog("sysset.payset.edit", "修改系统设置-支付设置");
			show_json(1);
		}
		$data = m("common")->getSysset("pay");
		$payments = pdo_fetchall("SELECT id,title FROM " . tablename("ewei_shop_payment") . " WHERE uniacid=:uniacid and paytype = 0 ", array( ":uniacid" => $_W["uniacid"] ));
		$paymentalis = pdo_fetchall("SELECT id,title FROM " . tablename("ewei_shop_payment") . " WHERE uniacid=:uniacid and paytype = 1 ", array( ":uniacid" => $_W["uniacid"] ));
		if( empty($payments) ) 
		{
			$payments = array( );
			$setting = uni_setting($_W["uniacid"], array( "payment" ));
			$payment = $setting["payment"];
			if( !empty($payment["wechat"]["mchid"]) ) 
			{
				if( IMS_VERSION <= 0.8 ) 
				{
					$payment["wechat"]["apikey"] = $payment["wechat"]["signkey"];
				}
				$default = array( "uniacid" => $_W["uniacid"], "title" => "微信支付", "type" => 0, "sub_appid" => $_W["account"]["key"], "sub_appsecret" => $_W["account"]["secret"], "sub_mch_id" => $payment["wechat"]["mchid"], "apikey" => $payment["wechat"]["apikey"], "cert_file" => $sec["cert"], "key_file" => $sec["key"], "root_file" => $sec["root"], "createtime" => TIMESTAMP );
				$payments[] = $default;
				pdo_insert("ewei_shop_payment", $default);
				$default_0 = pdo_insertid();
			}
			if( $data["weixin_sub"] == 1 || !empty($sec["appid_sub"]) ) 
			{
				$default = array( "uniacid" => $_W["uniacid"], "title" => "微信支付子商户", "type" => 1, "appid" => $sec["appid_sub"], "mch_id" => $sec["mchid_sub"], "sub_appid" => $sec["sub_appid_sub"], "sub_appsecret" => $_W["account"]["secret"], "sub_mch_id" => $sec["sub_mchid_sub"], "apikey" => $sec["apikey_sub"], "cert_file" => $sec["sub"]["cert"], "key_file" => $sec["sub"]["key"], "root_file" => $sec["sub"]["root"], "createtime" => TIMESTAMP );
				$payments[] = $default;
				pdo_insert("ewei_shop_payment", $default);
				$default_1 = pdo_insertid();
			}
			if( $data["weixin_jie_sub"] == 1 || !empty($sec["appid"]) ) 
			{
				$default = array( "uniacid" => $_W["uniacid"], "title" => "借用微信支付", "type" => 2, "sub_appid" => $sec["appid"], "sub_appsecret" => $sec["secret"], "sub_mch_id" => $sec["mchid"], "apikey" => $sec["apikey"], "cert_file" => $sec["jie"]["cert"], "key_file" => $sec["jie"]["key"], "root_file" => $sec["jie"]["root"], "createtime" => TIMESTAMP );
				$payments[] = $default;
				pdo_insert("ewei_shop_payment", $default);
				$default_2 = pdo_insertid();
			}
			if( $data["weixin_jie_sub"] == 1 || !empty($sec["appid_jie_sub"]) ) 
			{
				$default = array( "uniacid" => $_W["uniacid"], "title" => "借用微信支付子商户", "type" => 3, "appid" => $sec["appid_jie_sub"], "mch_id" => $sec["mchid_jie_sub"], "sub_appid" => $sec["sub_appid_jie_sub"], "sub_appsecret" => $sec["sub_secret_jie_sub"], "sub_mch_id" => $sec["sub_mchid_jie_sub"], "apikey" => $sec["apikey_jie_sub"], "cert_file" => $sec["jie_sub"]["cert"], "key_file" => $sec["jie_sub"]["key"], "root_file" => $sec["jie_sub"]["root"], "createtime" => TIMESTAMP );
				$payments[] = $default;
				pdo_insert("ewei_shop_payment", $default);
				$default_3 = pdo_insertid();
			}
			if( $data["weixin"] == 1 ) 
			{
				$data["weixin_id"] = $default_0;
			}
			else 
			{
				if( $data["weixin_sub"] == 1 ) 
				{
					$data["weixin_id"] = $default_1;
				}
				else 
				{
					if( $data["weixin_jie"] == 1 ) 
					{
						$data["weixin_id"] = $default_2;
					}
					else 
					{
						if( $data["weixin_jie_sub"] == 1 ) 
						{
							$data["weixin_id"] = $default_3;
						}
					}
				}
			}
			m("common")->updateSysset(array( "pay" => $data ));
		}
		$url = $_W["siteroot"] . "addons/ewei_shopv2/payment/wechat/notify.php";
		load()->func("communication");
		$resp = ihttp_get($url);
		include($this->template());
		exit();
	}
	public function member() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			ca("sysset.member.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$data["levelname"] = trim($data["levelname"]);
			$data["levelurl"] = trim($data["levelurl"]);
			$data["leveltype"] = intval($data["leveltype"]);
			m("common")->updateSysset(array( "member" => $data ));
			$shop = m("common")->getSysset("shop");
			$shop["levelname"] = $data["levelname"];
			$shop["levelurl"] = $data["levelurl"];
			$shop["leveltype"] = $data["leveltype"];
			m("common")->updateSysset(array( "shop" => $shop ));
			plog("sysset.member.edit", "修改系统设置-会员设置");
			show_json(1);
		}
		$data = m("common")->getSysset("member");
		if( !isset($data["levelname"]) ) 
		{
			$shop = m("common")->getSysset("shop");
			$data["levelname"] = $shop["levelname"];
			$data["levelurl"] = $shop["levelurl"];
			$data["leveltype"] = $shop["leveltype"];
		}
		include($this->template());
	}
	public function category() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			ca("sysset.category.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$shop = m("common")->getSysset("shop");
			$shop["level"] = intval($data["level"]);
			$shop["show"] = intval($data["show"]);
			$shop["advimg"] = save_media($data["advimg"]);
			$shop["advurl"] = trim($data["advurl"]);
			m("common")->updateSysset(array( "category" => $data ));
			$shop = m("common")->getSysset("shop");
			$shop["catlevel"] = $data["level"];
			$shop["catshow"] = $data["show"];
			$shop["catadvimg"] = save_media($data["advimg"]);
			$shop["catadvurl"] = $data["advurl"];
			m("common")->updateSysset(array( "shop" => $shop ));
			plog("sysset.category.edit", "修改系统设置-分类层级设置");
			m("shop")->getCategory(true);
			show_json(1);
		}
		$data = m("common")->getSysset("category");
		if( empty($data) ) 
		{
			$shop = m("common")->getSysset("shop");
			$data["level"] = $shop["catlevel"];
			$data["show"] = $shop["catshow"];
			$data["advimg"] = $shop["catadvimg"];
			$data["advurl"] = $shop["catadvurl"];
		}
		include($this->template());
	}
	public function contact() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			ca("sysset.contact.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$data["qq"] = trim($data["qq"]);
			$data["address"] = trim($data["address"]);
			$data["phone"] = trim($data["phone"]);
			m("common")->updateSysset(array( "contact" => $data ));
			$shop = m("common")->getSysset("shop");
			$shop["qq"] = $data["qq"];
			$shop["address"] = $data["address"];
			$shop["phone"] = $data["phone"];
			m("common")->updateSysset(array( "shop" => $shop ));
			plog("sysset.contact.edit", "修改系统设置-联系方式设置");
			show_json(1);
		}
		$data = m("common")->getSysset("contact");
		if( empty($data) ) 
		{
			$shop = m("common")->getSysset("shop");
			$data["qq"] = $shop["qq"];
			$data["address"] = $shop["address"];
			$data["phone"] = $shop["phone"];
		}
		include($this->template());
	}
	public function close() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			ca("sysset.close.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$data["flag"] = intval($data["flag"]);
			$data["detail"] = m("common")->html_images($data["detail"]);
			$data["url"] = trim($data["url"]);
			m("common")->updateSysset(array( "close" => $data ));
			$shop = m("common")->getSysset("shop");
			$shop["close"] = $data["flag"];
			$shop["closedetail"] = $data["detail"];
			$shop["closeurl"] = $data["url"];
			m("common")->updateSysset(array( "shop" => $shop ));
			plog("sysset.close.edit", "修改系统设置-商城关闭设置");
			show_json(1);
		}
		$data = m("common")->getSysset("close");
		$data["detail"] = m("common")->html_to_images($data["detail"]);
		if( empty($data) ) 
		{
			$shop = m("common")->getSysset("shop");
			$data["flag"] = $shop["close"];
			$data["detail"] = m("common")->html_to_images($shop["closedetail"]);
			$data["url"] = $shop["closeurl"];
		}
		include($this->template());
	}
	public function templat() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			ca("sysset.templat.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			m("common")->updateSysset(array( "template" => $data ));
			$shop = m("common")->getSysset("shop");
			$shop["style"] = $data["style"];
			m("common")->updateSysset(array( "shop" => $shop ));
			m("cache")->set("template_shop", $data["style"]);
			plog("sysset.templat.edit", "修改系统设置-模板设置");
			show_json(1);
		}
		$styles = array( );
		$dir = IA_ROOT . "/addons/ewei_shopv2/template/mobile/";
		if( $handle = opendir($dir) ) 
		{
			while( ($file = readdir($handle)) !== false ) 
			{
				if( $file != ".." && $file != "." && is_dir($dir . "/" . $file) ) 
				{
					$styles[] = $file;
				}
			}
			closedir($handle);
		}
		$data = m("common")->getSysset("template", false);
		include($this->template());
	}
	public function goodsprice() 
	{
		global $_W;
		global $_GPC;
		include($this->template());
	}
	public function wap() 
	{
		global $_W;
		global $_GPC;
		$data = m("common")->getSysset("wap");
		$wap = com("wap");
		if( !$wap ) 
		{
			$this->message("您没权限访问!");
			exit();
		}
		$sms = com("sms");
		if( !$sms ) 
		{
			$this->message("开启全网通请先开通短信通知");
			exit();
		}
		$template_sms = com("sms")->sms_temp();
		if( $_W["ispost"] ) 
		{
			ca("sysset.wap.edit");
			$data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$data["open"] = intval($data["open"]);
			$data["loginbg"] = save_media($data["loginbg"]);
			$data["regbg"] = save_media($data["regbg"]);
			$data["sns"]["wx"] = intval($data["sns"]["wx"]);
			$data["sns"]["qq"] = intval($data["sns"]["qq"]);
			m("common")->updateSysset(array( "wap" => $data ));
			plog("sysset.wap.edit", "修改WAP设置");
			show_json(1);
		}
		$styles = array( );
		$dir = IA_ROOT . "/addons/ewei_shopv2/template/account/";
		if( $handle = opendir($dir) ) 
		{
			while( ($file = readdir($handle)) !== false ) 
			{
				if( $file != ".." && $file != "." && is_dir($dir . "/" . $file) ) 
				{
					$styles[] = $file;
				}
			}
			closedir($handle);
		}
		include($this->template("sysset/wap"));
	}
	public function funbar() 
	{
		global $_W;
		global $_GPC;
		if( $_W["ispost"] ) 
		{
			$data = pdo_fetch("select * from " . tablename("ewei_shop_funbar") . " where uid=:uid and uniacid=:uniacid limit 1", array( ":uid" => $_W["uid"], ":uniacid" => $_W["uniacid"] ));
			$funbardata = (is_array($_GPC["funbardata"]) ? $_GPC["funbardata"] : array( ));
			$funbardata = serialize($funbardata);
			if( empty($data) ) 
			{
				pdo_insert("ewei_shop_funbar", array( "uid" => $_W["uid"], "datas" => $funbardata, "uniacid" => $_W["uniacid"] ));
			}
			else 
			{
				pdo_update("ewei_shop_funbar", array( "datas" => $funbardata ), array( "uid" => $data["uid"], "uniacid" => $_W["uniacid"] ));
			}
			show_json(1);
		}
	}
	public function area() 
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W["uniacid"];
		$data = m("util")->get_area_config_data();
		if( $_W["ispost"] ) 
		{
			ca("sysset.area.edit");
			$submit_data = (is_array($_GPC["data"]) ? $_GPC["data"] : array( ));
			$array = array( );
			if( empty($data) || empty($data["new_area"]) ) 
			{
				$array["new_area"] = intval($submit_data["new_area"]);
				if( !empty($array["new_area"]) ) 
				{
					$array["address_street"] = intval($submit_data["address_street"]);
					$change_data = array( );
					$change_data["province"] = "";
					$change_data["city"] = "";
					$change_data["area"] = "";
					pdo_update("ewei_shop_member", $change_data, array( "uniacid" => $uniacid ));
					pdo_update("ewei_shop_member_address", $change_data, array( "uniacid" => $uniacid ));
				}
				else 
				{
					$array["address_street"] = 0;
				}
			}
			else 
			{
				if( !empty($data["new_area"]) ) 
				{
					$array["address_street"] = intval($submit_data["address_street"]);
				}
			}
			if( empty($data) ) 
			{
				$array["uniacid"] = $uniacid;
				$array["createtime"] = time();
				pdo_insert("ewei_shop_area_config", $array);
			}
			else 
			{
				if( !empty($array) ) 
				{
					pdo_update("ewei_shop_area_config", $array, array( "id" => $data["id"], "uniacid" => $uniacid ));
				}
			}
			$data = m("util")->get_area_config_data();
			m("common")->updateSysset(array( "area_config" => $data ));
			plog("sysset.area.edit", "修改系统设置-地址库设置");
			show_json(1);
		}
		include($this->template());
	}
	public function express() 
	{
		global $_W;
		global $_GPC;
		$data = m("common")->getSysset("express");
		if( $_W["ispost"] ) 
		{
			ca("sysset.express.edit");
			$data = array( "apikey" => trim($_GPC["apikey"]), "customer" => trim($_GPC["customer"]), "isopen" => intval($_GPC["isopen"]), "cache" => intval($_GPC["cache"]) );
			m("common")->updateSysset(array( "express" => $data ));
			plog("sysset.express.edit", "修改系统设置-物流信息接口");
			show_json(1);
		}
		include($this->template("sysset/express"));
	}
	public function notice_redis() 
	{
		global $_W;
		global $_GPC;
		m("common")->updateSysset(array( "notice_redis" => array( "notice_redis_click" => 1 ) ));
		if( $_W["ispost"] ) 
		{
			ca("sysset.note_redis.edit");
			if( $_GPC["notice_redis"] == "1" ) 
			{
				$open_redis = function_exists("redis") && !is_error(redis());
				if( !$open_redis ) 
				{
					show_json(0, "请先打开redis");
				}
			}
			$data["notice_redis"] = $_GPC["notice_redis"];
			m("common")->updateSysset(array( "notice_redis" => $data ));
			plog("sysset.note_redis.edit", "修改系统设置-redis消息通知开关");
			show_json(1);
		}
		$data = m("common")->getSysset("notice_redis");
		include($this->template("sysset/notice_redis"));
	}
	public function wxpaycert() 
	{
		global $_W;
		global $_GPC;
		m("common")->updateSysset(array( "wxpaycert_view" => array( "wxpaycert_view_click" => 1 ) ));
		if( $_W["ispost"] ) 
		{
			$mch_id = trim($_GPC["mch_id"]);
			$api_key = trim($_GPC["api_key"]);
			if( empty($mch_id) ) 
			{
				show_json(0, "请填写微信支付商户号");
			}
			if( empty($api_key) ) 
			{
				show_json(0, "请填写微信支付密钥");
			}
			$url = "https://apitest.mch.weixin.qq.com/sandboxnew/pay/getsignkey";
			$post_data = array( "mch_id" => $mch_id, "nonce_str" => random(32) );
			$post_data["sign"] = get_wxpay_sign($post_data, $api_key);
			$xmldata = array2xml($post_data);
			$result = ihttp_post($url, $xmldata);
			if( is_error($result) ) 
			{
				show_json(0, "请求失败");
			}
			if( empty($result["content"]) ) 
			{
				show_json(0, "数据返回失败");
			}
			$content = xml2array($result["content"]);
			if( strval($content["return_code"]) == "FAIL" ) 
			{
				$return_msg = (empty($content["return_msg"]) ? $content["retmsg"] : $content["return_msg"]);
				show_json(0, strval($return_msg));
			}
			show_json(1, "验证成功");
		}
		include($this->template("sysset/wxpaycert"));
	}

     /**
     * 幸运转盘设置
     */
    public function game()
    {
        global $_W;
        global $_GPC;
        $data = pdo_fetch('select * from '.tablename('ewei_shop_game').' where uniacid="'.$_W['uniacid'].'"');
        $data['sets'] = iunserializer($data['sets']);
        if( $_W["ispost"] )
        {
            ca("sysset.game.edit");
            $add = $_GPC;
            $type = $_GPC['game_type'];
            foreach ($add as $key=>$val){
                if(is_array($val)){
                    continue;
                }
                unset($add[$key]);
            }
            pdo_begin();
            try{
                if(empty($data)){
                    pdo_insert('ewei_shop_game',['uniacid'=>$_W['uniacid'],'sets'=>iserializer($add),'createtime'=>time(),'game_type'=>$type]);
                }else{
                    pdo_update('ewei_shop_game',['sets'=>iserializer($add),'updatetime'=>time(),'game_type'=>$type],['id'=>$data['id']]);
                }
                pdo_commit();
            }catch (Exception $exception){
                pdo_rollback();
            }
            show_json(1);
        }
        include $this->template('sysset/game');
    }

    /*
     * 转盘开关
     */
    public function game_open(){
        global $_GPC;
        global $_W;
        $id = $_GPC['id'];
        if(pdo_exists('ewei_shop_game',['id'=>$id,'uniacid'=>$_W['uniacid']])){
            pdo_begin();
            try{
                pdo_update('ewei_shop_game',['status'=>$_GPC['status']],['id'=>$id]);
                pdo_commit();
            }catch (Exception $exception){
                pdo_rollback();
            }
            show_json(1);
        }else{
            show_json(0,'信息错误');
        }
    }

    /**
     * 快递设置
     */
    public function express_set()
    {
        global $_GPC;
        global $_W;
        $express = pdo_fetch('select * from '.tablename('ewei_shop_express_set').' where uniacid="'.$_W['uniacid'].'"');
        $areas = m('common')->getAreas();
        if( $_W["ispost"] )
        {
            ca('sysyset.express_set.edit');
            $express_set = $_GPC['express_set'];
            $data = [
                'uniacid'=>$_W['uniacid'],
                'express_set'=>$express_set,
                'createtime'=>time(),
            ];
            pdo_begin();
            try {
                if (pdo_exists('ewei_shop_express_set', ['uniacid' => $_W['uniacid']])) {
                    pdo_update('ewei_shop_express_set', $data, ['uniacid' => $_W['uniacid']]);
                } else {
                    pdo_insert('ewei_shop_express_set', $data);
                }
                pdo_commit();
            }catch (Exception $exception){
                pdo_rollback();
            }
            show_json(1);
        }
        include $this->template();
    }
    //运动首页--页面设置
    public function sport(){
        global $_W;
        global $_GPC;
       
        if( $_W["ispost"] )
        {
            $data["backgroup"] = save_media($_POST["backgroup"]);
//             $icon[0]["img"]=$_POST["icon1_img"];
//             $icon[0]["title"]=$_POST["icon1_title"];
//             $icon[0]["url"]=$_POST["icon1_url"];
//             $icon[0]["icon"]=save_media($_POST["icon1"]);
            
//             $icon[1]["img"]=$_POST["icon2_img"];
//             $icon[1]["title"]=$_POST["icon2_title"];
//             $icon[1]["url"]=$_POST["icon2_url"];
//             $icon[1]["icon"]=save_media($_POST["icon2"]);
            
//             $icon[2]["img"]=$_POST["icon3_img"];
//             $icon[2]["title"]=$_POST["icon3_title"];
//             $icon[2]["url"]=$_POST["icon3_url"];
//             $icon[2]["icon"]=save_media($_POST["icon3"]);
            
//             $icon[3]["img"]=$_POST["icon4_img"];
//             $icon[3]["title"]=$_POST["icon4_title"];
//             $icon[3]["url"]=$_POST["icon4_url"];
//             $icon[3]["icon"]=save_media($_POST["icon4"]);
            
//             $icon[4]["img"]=$_POST["icon5_img"];
//             $icon[4]["title"]=$_POST["icon5_title"];
//             $icon[4]["url"]=$_POST["icon5_url"];
//             $icon[4]["icon"]=save_media($_POST["icon5"]);
            
//             $data["icon"]=serialize($icon);
            if (pdo_update("ewei_shop_small_set",$data,array("id"=>1))){
            
            show_json(1);
            }else{
                show_json(0);
            }
        }
        $data=pdo_get("ewei_shop_small_set",array("id"=>1));
        $data["icon"]=unserialize($data["icon"]);
//         var_dump($data["icon"]);
        
        //上传视频连接
        $submitUrl = $_W['siteroot'] . ('/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=sysset.index.upload_img');
        include($this->template());
    }
    
    //达人中心
    public function daren(){
        
        global $_W;
        global $_GPC;
        
        if( $_W["ispost"] )
        {
            $data["backgroup"] = save_media($_POST["backgroup"]);
            $data["banner"] = save_media($_POST["banner"]);
            $icon[0]["img"]=$_POST["icon1_img"];
//             $icon[0]["title"]=$_POST["icon1_title"];
//             $icon[0]["url"]=$_POST["icon1_url"];
            
            $icon[1]["img"]=$_POST["icon2_img"];
//             $icon[1]["title"]=$_POST["icon2_title"];
//             $icon[1]["url"]=$_POST["icon2_url"];
            
            $icon[2]["img"]=$_POST["icon3_img"];
//             $icon[2]["title"]=$_POST["icon3_title"];
//             $icon[2]["url"]=$_POST["icon3_url"];
            
            $icon[3]["img"]=$_POST["icon4_img"];
//             $icon[3]["title"]=$_POST["icon4_title"];
//             $icon[3]["url"]=$_POST["icon4_url"];
            $data["icon"]=serialize($icon);
            if (pdo_update("ewei_shop_small_set",$data,array("id"=>2))){
                
                show_json(1);
            }else{
                show_json(0);
            }
        }
        $data=pdo_get("ewei_shop_small_set",array("id"=>2));
        $data["icon"]=unserialize($data["icon"]);
        //         var_dump($data["icon"]);
        
        //上传视频连接
        $submitUrl = $_W['siteroot'] . ('/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=sysset.index.upload_img');
        include($this->template());
        
    }
    //上传图片
    public function upload_img(){
        header('Access-Control-Allow-Origin:*');
        $field = $_FILES["file"];
        $resault=$this->upload_file($field,"./attachment",2);
        if ($resault["status"]==0){
            $resault["addr"]=tomedia($resault["message"]);
        }
        echo json_encode($resault);
    }
    
    public function  upload_video(){
        
        $field = $_FILES["file"];
        
        $resault=$this->upload_file($field,"./attachment",1);
        //成功
        if ($resault["status"]==0){
            //获取封面图
            
            //视频绝对路径
            $lujing=tomedia($resault["message"]);
          
           $resault["lujing"]=$lujing;
            
        }
        echo json_encode($resault);
        
    }
    
    //1表示视频 2表示图片
    function upload_file($files, $path = "./attachment",$type=1)
    
    {
        
        if($type==1){
            $imagesExt=['rm', 'rmvb', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4','mov'];
            $path = "videos/";
        }else{
            $imagesExt=['jpg','jpeg','gif','png'];
            $path = "videos/img/";
        }
        //mkdirs(ATTACHMENT_ROOT . '/' . $path);
        // 判断错误号
        if (@$files['error'] == 00) {
            // 判断文件类型
            $ext = strtolower(pathinfo(@$files['name'],PATHINFO_EXTENSION));
            if (!in_array($ext,$imagesExt)){
                $resault["status"]=1;
                $resault["message"]="非法文件类型";
                return $resault;
            }
            // 判断是否存在上传到的目录
            if (!is_dir(ATTACHMENT_ROOT . '/' .$path)){
                mkdir($path,0777,true);
            }
            // 生成唯一的文件名
            $fileName = md5(uniqid(microtime(true),true)).'.'.$ext;
            // 将文件名拼接到指定的目录下
            $destName =ATTACHMENT_ROOT.'/'.$path.$fileName;
            // 进行文件移动
            if (!move_uploaded_file($files['tmp_name'],$destName)){
                $resault["status"]=1;
                $resault["message"]="文件上传失败！";
                return $resault;
            }
            $resault["status"]=0;
            $resault["message"]=$path.$fileName;
            return $resault;
        } else {
            // 根据错误号返回提示信息
            switch (@$files['error']) {
                case 1:
                    echo "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                    break;
                case 2:
                    echo "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                    break;
                case 3:
                    echo "文件只有部分被上传";
                    break;
                case 4:
                    echo "没有文件被上传";
                    break;
                case 6:
                    
                case 7:
                    echo "系统错误";
                    break;
            }
        }
    }
    //个人中心
    public function my(){
        
        
        global $_W;
        global $_GPC;
        
        if( $_W["ispost"] )
        {
            $d["order"]["payment"] = save_media($_POST["payment"]);
            $d["order"]["send"] = save_media($_POST["send"]);
            $d["order"]["received"] = save_media($_POST["received"]);
            $d["order"]["evaluated"] = save_media($_POST["evaluated"]);
            
            $d["server"]["fans"]=save_media($_POST["fans"]);
            $d["server"]["recommend"]=save_media($_POST["recommend"]);
            $d["server"]["coupon"]=save_media($_POST["coupon"]);
            $d["server"]["coupon_center"]=save_media($_POST["coupon_center"]);
            $d["server"]["cart"]=save_media($_POST["cart"]);
            $d["server"]["concern"]=save_media($_POST["concern"]);
            $d["server"]["track"]=save_media($_POST["track"]);
            $d["server"]["addr"]=save_media($_POST["addr"]);
            $data["icon"]=serialize($d);
            if (pdo_update("ewei_shop_small_set",$data,array("id"=>3))){
                
                show_json(1);
            }else{
                show_json(0,"设置失败");
            }
        }
        $l=pdo_get("ewei_shop_small_set",array("id"=>3));
        $data=unserialize($l["icon"]);
        include($this->template());
        
        
    }


    /**
     * 分享页缩略图设置
     */
    public function share_help(){
       
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' =>$_W['uniacid']);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_share_help') . (' WHERE 1 ' . $condition.'  ORDER BY id  asc limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_share_help') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        foreach ($list as $k=>$v){
            $list[$k]["image"]=tomedia($v["image"]);
            $list[$k]["thumb"]=tomedia($v["thumb"]);
        }
        
        include($this->template());
    }
    //助力海报
    public function postshare(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if( $_W["ispost"] )
        {
            $id = intval($_POST['id']);
            $add['uniacid'] = $_W['uniacid'];
            $add['title'] = trim($_POST['title']);
            $add['thumb'] = save_media($_POST['thumb']);
            $add['image'] = save_media($_POST['image']);
            $add['createtime'] = time();
            if(empty($id)){
                $res = pdo_insert('ewei_shop_share_help',$add);
            }else{
                $res = pdo_update('ewei_shop_share_help',$add,['id'=>$id]);
            }
            if($res){
                show_json(1,array('url' => webUrl('sysset/share_help')));
            }else{
                show_json(0,"操作失败");
            }
        }
        if ($id){
        $data=pdo_get("ewei_shop_share_help",array("id"=>$id));
        }
        include $this->template();
    }
    //助力--添加
    public function addshare(){
        $this->postshare();
    }
    //助力--编辑
    public function editshare(){
        $this->postshare();
    }
    //助力--删除
    public function deleteshare(){
        
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if (pdo_delete("ewei_shop_share_help",array("id"=>$id))){
            show_json(1, array('url' => referer()));
        }else{
            show_json(0,"删除失败");
        }
        
        
    }
    //首页icon
    public function sportindex(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' =>$_W['uniacid']);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_small_setindex') . (' WHERE 1 ' . $condition.'  ORDER BY sort  asc limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_small_setindex') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        foreach ($list as $k=>$v){
            $list[$k]["img"]=tomedia($v["img"]);
        }
        include($this->template());
    }
    //首页icon--添加
    public function addsport(){
        $this->postsport();
    }
    //首页icon--编辑
    public function editsport(){
        $this->postsport();
    }
    public function postsport(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            $data = array('uniacid' => $_W['uniacid'], 'sort' => intval($_GPC['sort']), 'title' => trim($_GPC['title']),'img'=>save_media($_GPC["img"]),'icon'=>save_media($_GPC["icon"]),'url'=>$_GPC["url"],'status'=>intval($_GPC["status"]));
            if (!empty($id)) {
                pdo_update('ewei_shop_small_setindex', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_small_setindex', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('sysset/sportindex')));
        }
        
        $notice = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_small_setindex') . ' WHERE id =:id and uniacid=:uniacid  limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        include $this->template();
        
    }
    //删除
    public function deletesport(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
       if (pdo_delete("ewei_shop_small_setindex",array("id"=>$id))){
            show_json(1, array('url' => referer()));
        }else{
            show_json(0,"删除失败");
        }
        
    }
    //上线
    public function online(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_small_setindex') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
        foreach ($items as $k=>$v){
            $d["icon"]=$v["icon"];
            $d["title"]=$v["title"];
            $d["url"]=$v["url"];
            $d["img"]=$v["img"];
            $data["olddata"]=serialize($d);
            $data["online"]=1;
            pdo_update("ewei_shop_small_setindex",$data,array("id"=>$v["id"]));
        }
        show_json(1, array('url' => referer()));
//         var_dump($id);
    }
}
?>