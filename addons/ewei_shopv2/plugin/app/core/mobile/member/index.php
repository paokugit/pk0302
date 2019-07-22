<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
//require(EWEI_SHOPV2_PLUGIN . "app/core/error_code.php");
require(EWEI_SHOPV2_PLUGIN . "app/core/wxapp/wxBizDataCrypt.php");

class Index_EweiShopV2Page extends AppMobilePage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$member = $this->member;
		if( empty($member) ) 
		{
			app_error(AppError::$UserNotFound);
		}
		$level = m("member")->getLevel($_W["openid"]);
		$open_creditshop = p("creditshop") && $_W["shopset"]["creditshop"]["centeropen"];
		$params = array( ":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"] );
		$merch_plugin = p("merch");
		$merch_data = m("common")->getPluginset("merch");
		if( $merch_plugin && $merch_data["is_openmerch"] ) 
		{
			$statics = array( "order_0" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and status=0  and uniacid=:uniacid and type = 0 limit 1", $params), "order_1" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and status=1 and refundid=0 and uniacid=:uniacid and type = 0 limit 1", $params), "order_2" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and status=2 and refundid=0 and uniacid=:uniacid and type = 0 limit 1", $params), "order_4" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and refundstate=1 and uniacid=:uniacid and type = 0 limit 1", $params), "cart" => pdo_fetchcolumn("select ifnull(sum(total),0) from " . tablename("ewei_shop_member_cart") . " where uniacid=:uniacid and openid=:openid and deleted=0 ", $params), "favorite" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_member_favorite") . " where uniacid=:uniacid and openid=:openid and deleted=0 ", $params) );
		}
		else 
		{
			$statics = array( "order_0" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and ismr=0 and status=0  and uniacid=:uniacid and isparent=0 and type = 0 limit 1", $params), "order_1" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and ismr=0 and status=1 and refundid=0 and uniacid=:uniacid and isparent=0 and type = 0 limit 1", $params), "order_2" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and ismr=0 and status=2 and refundid=0 and uniacid=:uniacid and isparent=0 and type = 0 limit 1", $params), "order_4" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where openid=:openid and ismr=0 and refundstate=1 and uniacid=:uniacid and isparent=0 and type = 0 limit 1", $params), "cart" => pdo_fetchcolumn("select ifnull(sum(total),0) from " . tablename("ewei_shop_member_cart") . " where uniacid=:uniacid and openid=:openid and deleted=0 and selected = 1", $params), "favorite" => pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_member_favorite") . " where uniacid=:uniacid and openid=:openid and deleted=0 and `type`=0", $params) );
		}
		$hascoupon = false;
		$hascouponcenter = false;
		$plugin_coupon = com("coupon");
		if( $plugin_coupon ) 
		{
			$time = time();
			$sql = "select count(*) from " . tablename("ewei_shop_coupon_data") . " d";
			$sql .= " left join " . tablename("ewei_shop_coupon") . " c on d.couponid = c.id";
			$sql .= " where d.openid=:openid and d.uniacid=:uniacid and  d.used=0 ";
			$sql .= " and (   (c.timelimit = 0 and ( c.timedays=0 or c.timedays*86400 + d.gettime >=" . $time . " ) )  or  (c.timelimit =1 and c.timestart<=" . $time . " && c.timeend>=" . $time . ")) order by d.gettime desc";
			$statics["coupon"] = pdo_fetchcolumn($sql, array( ":openid" => $_W["openid"], ":uniacid" => $_W["uniacid"] ));
			$pcset = $_W["shopset"]["coupon"];
			if( empty($pcset["closemember"]) ) 
			{
				$hascoupon = true;
				$coupon_text = "领取优惠券";
			}
			if( empty($pcset["closecenter"]) ) 
			{
				$hascouponcenter = true;
			}
			$couponcenter_text = "我的优惠券";
		}
		$hasglobonus = false;
		$plugin_globonus = p("globonus");
		if( $plugin_globonus ) 
		{
			$plugin_globonus_set = $plugin_globonus->getSet();
			$hasglobonus = !empty($plugin_globonus_set["open"]) && !empty($plugin_globonus_set["openmembercenter"]);
		}
		$hasabonus = false;
		$plugin_abonus = p("abonus");
		if( $plugin_abonus ) 
		{
			$plugin_abonus_set = $plugin_abonus->getSet();
			$hasabonus = !empty($plugin_abonus_set["open"]) && !empty($plugin_abonus_set["openmembercenter"]);
			if( $hasabonus ) 
			{
				$abonus_text = m("plugin")->getName("abonus");
				if( empty($abonus_text) ) 
				{
					$abonus_text = "区域代理";
				}
			}
		}
		$hasqa = false;
		$plugin_qa = p("qa");
		if( $plugin_qa ) 
		{
			$plugin_qa_set = $plugin_qa->getSet();
			if( !empty($plugin_qa_set["showmember"]) ) 
			{
				$hasqa = true;
				$qa_text = m("plugin")->getName("qa");
				if( empty($qa_text) ) 
				{
					$qa_text = "帮助中心";
				}
			}
		}
		$hassign = false;
		$com_sign = p("sign");
		if( $com_sign ) 
		{
			$com_sign_set = $com_sign->getSet();
			if( !empty($com_sign_set["iscenter"]) ) 
			{
				$hassign = true;
				$sign_text = (empty($_W["shopset"]["trade"]["credittext"]) ? "卡路里" : $_W["shopset"]["trade"]["credittext"]);
				$sign_text .= (empty($com_sign_set["textsign"]) ? "签到" : $com_sign_set["textsign"]);
				$url_sign = mobileUrl("sign", NULL, true);
				$sign_url_arr = explode("?", $url_sign);
				$sign_url_domain = $sign_url_arr[0];
				$sign_url_params = urlencode($sign_url_arr[1]);
				if( empty($sign_text) ) 
				{
					$sign_text = "卡路里签到";
				}
			}
		}
		$commission = false;
		$commission_text = "";
		$commission_url = "";
		if( p("commission") && intval(0 < $_W["shopset"]["commission"]["level"]) && empty($_W["shopset"]["app"]["hidecom"]) ) 
		{
			$commission = true;
			if( !$member["agentblack"] ) 
			{
				if( $member["isagent"] == 1 && $member["status"] == 1 ) 
				{
					$commission_url = "/pages/commission/index";
					$commission_text = (empty($_W["shopset"]["commission"]["texts"]["center"]) ? "分销中心" : $_W["shopset"]["commission"]["texts"]["center"]);
				}
				else 
				{
					$commission_url = "/pages/commission/register/index";
					$commission_text = (empty($_W["shopset"]["commission"]["texts"]["become"]) ? "成为分销商" : $_W["shopset"]["commission"]["texts"]["become"]);
				}
			}
		}
		$copyright = m("common")->getCopyright();
		$hasFullback = true;
		$ishidden = m("common")->getSysset("fullback");
		if( $ishidden["ishidden"] == true ) 
		{
			$hasFullback = false;
		}
		$haveverifygoods = m("verifygoods")->checkhaveverifygoods($_W["openid"]);
		if( !empty($haveverifygoods) ) 
		{
			$verifygoods = m("verifygoods")->getCanUseVerifygoods($_W["openid"]);
		}
		$usemembercard = false;
		$hasmembercard = false;
		$hasbuycardnum = 0;
		$allcardnum = 0;
		$plugin_membercard = p("membercard");
		if( $plugin_membercard ) 
		{
			$usemembercard = true;
			$card_condition = "openid =:openid and uniacid=:uniacid and isdelete=0";
			$params = array( ":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"] );
			$now_time = TIMESTAMP;
			$card_condition .= " and (expire_time=-1 or expire_time>" . $now_time . ")";
			$card_history = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_member_card_history") . " \r\n\t\t\t\tWHERE " . $card_condition . " limit 1", $params);
			if( $card_history ) 
			{
				$hasmembercard = true;
				$hasbuycardnum = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename("ewei_shop_member_card_history") . " \r\n\t\t\t\tWHERE " . $card_condition . " limit 1", $params);
			}
			$allcard_condition = " uniacid = :uniacid ";
			$allcard_params = array( ":uniacid" => $_W["uniacid"] );
			$allcard_condition .= " and status=1 and isdelete=0";
			$allcardnum = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename("ewei_shop_member_card") . "where " . $allcard_condition, $allcard_params);
		}
		if( $usemembercard && $hasbuycardnum == 0 && $allcardnum == 0 ) 
		{
			$usemembercard = false;
		}
		$result = array( "id" => $member["id"], "avatar" => $member["avatar"], "nickname" => $member["nickname"], "moneytext" => $_W["shopset"]["trade"]["moneytext"], "credittext" => $_W["shopset"]["trade"]["credittext"], "credit1" => $member["credit1"], "credit2" => $member["credit2"],"credit3" => $member["credit3"], "open_recharge" => (empty($_W["shopset"]["trade"]["closerecharge"]) ? 1 : 0), "open_creditshop" => intval($open_creditshop), "open_withdraw" => intval($_W["shopset"]["trade"]["withdraw"]), "logtext" => ($_W["shopset"]["trade"]["withdraw"] == 1 ? $_W["shopset"]["trade"]["moneytext"] . "明细" : "充值记录"), "levelurl" => ($_W["shopset"]["shop"]["levelurl"] == NULL ? "" : $_W["shopset"]["shop"]["levelurl"]), "levelname" => (empty($level["id"]) ? (empty($_W["shopset"]["shop"]["levelname"]) ? "普通会员" : $_W["shopset"]["shop"]["levelname"]) : $level["levelname"]), "statics" => $statics, "isblack" => $member["isblack"], "haveverifygoods" => $haveverifygoods, "verifygoods" => $verifygoods, "hascoupon" => $hascoupon, "hasFullback" => $hasFullback, "fullbacktext" => m("sale")->getFullBackText(), "coupon_text" => $coupon_text, "hascouponcenter" => $hascouponcenter, "couponcenter_text" => $couponcenter_text, "usemembercard" => $usemembercard, "hasmembercard" => $hasmembercard, "hasbuycardnum" => $hasbuycardnum, "allcardnum" => $allcardnum, "hasabonus" => $hasabonus, "abonus_text" => $abonus_text, "commission" => $commission, "commission_text" => $commission_text, "commission_url" => $commission_url, "hasqa" => $hasqa, "qa_text" => $qa_text, "hassign" => $hassign, "sign_text" => $sign_text, "sign_url_domain" => $sign_url_domain, "sign_url_params" => $sign_url_params, "hasrank" => intval($_W["shopset"]["rank"]["status"]) == 1, "rank_text" => "卡路里排行", "hasorderrank" => intval($_W["shopset"]["rank"]["order_status"]) == 1, "orderrank_text" => "消费排行", "copyright" => (!empty($copyright) && !empty($copyright["copyright"]) ? $copyright["copyright"] : ""), "customer" => intval($_W["shopset"]["app"]["customer"]), "phone" => intval($_W["shopset"]["app"]["phone"]) );
		if( !empty($result["customer"]) ) 
		{
			$result["customercolor"] = (empty($_W["shopset"]["app"]["customercolor"]) ? "#ff55555" : $_W["shopset"]["app"]["customercolor"]);
		}
		if( !empty($result["phone"]) ) 
		{
			$result["phonecolor"] = (empty($_W["shopset"]["app"]["phonecolor"]) ? "#ff5555" : $_W["shopset"]["app"]["phonecolor"]);
			$result["phonenumber"] = (empty($_W["shopset"]["app"]["phonenumber"]) ? "#ff5555" : $_W["shopset"]["app"]["phonenumber"]);
		}
		if( (empty($member["mobileverify"]) || empty($member["mobile"])))
		    //if( (empty($member["mobileverify"]) || empty($member["mobile"])) && (!empty($_W["shopset"]["app"]["openbind"]) || !empty($_W["shopset"]["wap"]["open"])) )
		{
			$result["needbind"] = 1;
			$result["bindtext"] = (!empty($_W["shopset"]["app"]["openbind"]) && !empty($_W["shopset"]["app"]["bindtext"]) ? $_W["shopset"]["app"]["bindtext"] : "绑定手机号可合并或同步您其他账号数据");
		}
		$cycelbuy = p("cycelbuy");
		$result["iscycelbuy"] = $cycelbuy;
		$plugin_bargain = p("bargain");
		$result["bargain"] = $plugin_bargain;
		$hasdividend = false;
		$dividend = p("dividend");
		if( $dividend ) 
		{
			$plugin_dividend_set = $dividend->getSet();
			if( !empty($plugin_dividend_set["open"]) && !empty($plugin_dividend_set["membershow"]) ) 
			{
				$hasdividend = true;
			}
		}
		$result["hasdividend"] = $hasdividend;
		$result["isheads"] = $member["isheads"];
		$result["headsstatus"] = $member["headsstatus"];
        $level = m("member")->agentlevel($_W["openid"]);
        $result["levelid"] = $level['levelid'];
        $result["levelname"] = $level['levelname'];
        $result["leveltime"] = $level['leveltime'];
        if($level['hasday'] || $level['hasday']==0){
            $result["hasday"] = $level['hasday'];
        }
        if($level['endtime']!=''){
            $result["endtime"] = $level['endtime'];
        }
        $result["levelinfo"] = $this->level_info($level['levelid']);
        $result['banner'] = pdo_fetchall('select * from '.tablename('ewei_shop_adsense').' where uniacid="'.$_W['uniacid'].'" and type=2 order by sort desc');
        foreach ($result['banner'] as $key=>$item){
            $result['banner'][$key]['thumb'] = tomedia($item['thumb']);
        }
        //累计余额收入
//        $comesql = "select ifnull(sum(money),0) from ".tablename('ewei_shop_member_log')." where openid=:openid and type=3 and status = 1";
//        $comeparams = array(':openid' => $_W['openid']);
//        $result['come_total'] = pdo_fetchcolumn($comesql, $comeparams);//累计卡路里收入
//        //累计余额收入
        //$comesql = "select ifnull(sum(num),0) from ".tablename('mc_credits_record')." where openid=:openid and credittype=:credit and num > 0";
        //$comeparams = array(':openid' => $_W['openid'],':credit'=>'credit1');
        //$result['calorie_total'] = pdo_fetchcolumn($comesql, $comeparams);//累计卡路里收入


        $result['come_total'] = $result['credit2'];
        $result['calorie_total'] = $result['credit1'];
		app_json($result);
	}

	public function level_info($levelid){
        switch ($levelid){
            case 1:
                $data['one']['info'] = '每天步数可兑换20卡路里，连续20天，到期后每天可兑换10卡路里';
                $data['two']['info'] = '赠送10卡路里';
                $data['three']['info'] = '直推奖3元（最高）';
                $data['one']['img'] = '/member/01@2x.png';
                $data['two']['img'] = '/member/02@2x.png';
                $data['three']['img'] = '/member/03@2x.png';
                return $data;break;
            case 2:
                $data['one']['info'] = '每天步数可兑换30卡路里，连续20天，到期后，每天可兑换10卡路里';
                $data['two']['info'] = '赠送99卡路里';
                $data['three']['info'] = '直推奖40元（最高）';
                $data['one']['img'] = '/member/01@2x.png';
                $data['two']['img'] = '/member/02@2x.png';
                $data['three']['img'] = '/member/03@2x.png';
                return $data;break;
            case 3:
                $data['one']['info'] = '每天可兑换30卡路里，连续20天';
                $data['two']['info'] = '赠送350卡路里';
                $data['three']['info'] = '直推奖70元（最高）';
                $data['one']['img'] = '/member/01@2x.png';
                $data['two']['img'] = '/member/02@2x.png';
                $data['three']['img'] = '/member/03@2x.png';
                return $data;break;
            case 5:
                $data['one']['info'] = '开通小程序智能店铺1个';
                $data['two']['info'] = '赠送2000折扣宝';
                $data['three']['info'] = '每天步数可兑换30卡路里，连续30天，到期后，每天可兑换10卡路里';
                $data['fore']['info'] = '赠送1000-5000元礼包';
                $data['one']['img'] = '/member/01@2x.png';
                $data['two']['img'] = '/member/02@2x.png';
                $data['three']['img'] = '/member/03@2x.png';
                $data['fore']['img'] = '/member/04@2x.png';
                return $data;break;
            default:
                return false;

        }
    }


    /**
     * 获取会员手机号
     */
    public function get_mobile(){
        global $_GPC;
        if(empty($_GPC['openid'])) app_error(2,'参数错误');
        $member_info = m('member')->getInfo($_GPC['openid']);
        if(!$member_info) app_error(3,'用户信息不存在');
        $member_info['mobile']?app_error(0,$member_info['mobile']):app_error(1,'未添加手机号');
    }


    /**
     * 添加会员手机号
     */
    public function add_mobile(){
        global $_GPC;
        global $_W;
        $encryptedData = trim($_GPC['encryptedData']);
        $iv = trim($_GPC["iv"]);
        $sessionKey = trim($_GPC["sessionKey"]);
        if (empty($encryptedData) || empty($iv)) {
            app_error(AppError::$ParamsError);
        }
        $appset = m("common")->getSysset("app");
        $pc = new WXBizDataCrypt($appset['appid'], $sessionKey);
        $errCode = $pc->decryptDatas($encryptedData, $iv, $data);
        if ($errCode == 0) {
            $data = json_decode($data, true);
            $member_info = m('member')->getInfo($_GPC['openid']);
            if(!$member_info) app_error(2,'用户信息不存在');
            if($member_info['mobile']) app_error(0,'1手机号更新成功');
            pdo_update('ewei_shop_member',array('mobile'=>$data['phoneNumber']),array('openid'=>$_GPC['openid']));
            app_error(0,'2手机号更新成功');
        }else{
            app_error(0,'3手机号更新成功');
        }
    }

    /**
     * 获取用户信息
     */
    public function member_info(){
        global $_W;
        global $_GPC;
        $member_info = m('member')->getInfo($_GPC['fansopenid']);
        if( empty($member_info) )
        {
            app_error(AppError::$UserNotFound);
        }
        $data['id'] = $member_info['id'];
        $data['openid'] = $member_info['openid'];
        $data['nickname'] = $member_info['nickname'];
        $data['mobile'] = $member_info['mobile'];
        $data['createtime'] = date('Y-m-d',$member_info['createtime']);
        $level = m("member")->agentlevel($_GPC["fansopenid"]);
        $data['levelname'] = $level['levelname'];
        $data['levelid'] = $level['levelid'];
        $data['avatar'] = $member_info['avatar'];
        $agentcount = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename("ewei_shop_member") . " WHERE agentid=:agentid  limit 1", $params = array( ":agentid" => $member_info['id']) );
        $data['agentcount'] = $agentcount;
        app_json($data);
    }


    public function member_info_byid(){
        global $_W;
        global $_GPC;
        $member_info = m('member')->getInfo($_GPC['inviteid']);
        if( empty($member_info) )
        {
            app_error(AppError::$UserNotFound);
        }
        $data['id'] = $member_info['id'];
        $data['openid'] = $member_info['openid'];
        $data['nickname'] = $member_info['nickname'];
        $data['mobile'] = $member_info['mobile'];
        $data['createtime'] = date('Y-m-d',$member_info['createtime']);
        $level = m("member")->agentlevel($_GPC["fansopenid"]);
        $data['levelname'] = $level['levelname'];
        $data['levelid'] = $level['levelid'];
        $data['avatar'] = $member_info['avatar'];
        app_json($data);
    }



    /**
     *
     * 判断是否可购买会员
     * @param $openid
     * @author lihanwne@paokucoin.com
     */
    public function canby_agent(){
        global $_GPC;
        if(!$_GPC['openid'] || !$_GPC['goodsid']) app_error(3,'数据错误');
        $openid = $_GPC['openid'];
        if($_GPC['goodsid']==3) $agentlevel=1;
        if($_GPC['goodsid']==4) $agentlevel=2;
        if($_GPC['goodsid']==7) $agentlevel=5;
        $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where openid=:openid  limit 1", array(  ":openid" => $openid ));
        if(!$memberInfo) app_error(1,'会员信息不存在');
        if($memberInfo['agenttime']){
            $hasday = (time()-$memberInfo['agenttime'])/(3600*24*20);
            if($hasday>20 && $agentlevel!=$memberInfo['agentlevel']) app_json(0,'允许购买');
        }
        app_error(2,'同一会员级别20天内不可重复购买');
    }

}
?>