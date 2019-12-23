<?php
class Shop_EweiShopV2Model
{
	/**
	 * 获取商品分类
	 * @global type $_W
	 * @return type
	 */
	public function getCategory($refresh = false)
	{
		global $_W;
		$allcategory = m('cache')->getArray('allcategory');
		if (empty($allcategory) || $refresh) {
			$parents = array();
			$children = array();
			$category = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE uniacid =:uniacid AND enabled=1 ORDER BY parentid ASC, displayorder DESC', array(':uniacid' => $_W['uniacid']));

			foreach ($category as $index => $row) {
				if (!empty($row['parentid'])) {
					if ($row[$row['parentid']]['parentid'] == 0) {
						$row[$row['parentid']]['level'] = 2;
					}
					else {
						$row[$row['parentid']]['level'] = 3;
					}

					$children[$row['parentid']][] = $row;
					unset($category[$index]);
				}
				else {
					$row['level'] = 1;
					$parents[] = $row;
				}
			}

			$allcategory = array('parent' => $parents, 'children' => $children);
			m('cache')->set('allcategory', $allcategory);
		}

		return $allcategory;
	}

	public function getFullCategory($fullname = false, $enabled = false)
	{
		global $_W;
		$allcategorynames = m('cache')->getArray('allcategorynames');
		$shopset = m('common')->getSysset('shop');
		$allcategory = array();
		$sql = 'SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE uniacid=:uniacid ';

		if ($enabled) {
			$sql .= ' AND enabled=1';
		}

		$sql .= ' ORDER BY parentid ASC, displayorder DESC';
		$category = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']));
		$category = set_medias($category, array('thumb', 'advimg'));

		if (empty($category)) {
			return array();
		}

		foreach ($category as &$c) {
			if (empty($c['parentid'])) {
				$allcategory[] = $c;

				foreach ($category as &$c1) {
					if ($c1['parentid'] != $c['id']) {
						continue;
					}

					if ($fullname) {
						$c1['name'] = $c['name'] . '-' . $c1['name'];
					}

					$allcategory[] = $c1;

					foreach ($category as &$c2) {
						if ($c2['parentid'] != $c1['id']) {
							continue;
						}

						if ($fullname) {
							$c2['name'] = $c1['name'] . '-' . $c2['name'];
						}

						$allcategory[] = $c2;

						foreach ($category as &$c3) {
							if ($c3['parentid'] != $c2['id']) {
								continue;
							}

							if ($fullname) {
								$c3['name'] = $c2['name'] . '-' . $c3['name'];
							}

							$allcategory[] = $c3;
						}

						unset($c3);
					}

					unset($c2);
				}

				unset($c1);
			}

			unset($c);
		}

		return $allcategory;
	}

	public function checkClose()
	{
		if (strexists($_SERVER['REQUEST_URI'], '/web/')) {
			return NULL;
		}

		global $_S;
		global $_W;

		if ($_W['plugin'] == 'mmanage') {
			return NULL;
		}

		$close = $_S['close'];

		if (!empty($close['flag'])) {
			if (!empty($close['url'])) {
				header('location: ' . $close['url']);
				exit();
			}

			exit("<!DOCTYPE html>\r\n\t\t\t\t\t<html>\r\n\t\t\t\t\t\t<head>\r\n\t\t\t\t\t\t\t<meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>\r\n\t\t\t\t\t\t\t<title>抱歉，商城暂时关闭</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>\r\n\t\t\t\t\t\t</head>\r\n\t\t\t\t\t\t<body>\r\n\t\t\t\t\t\t<style type='text/css'>\r\n\t\t\t\t\t\tbody { background:#fbfbf2; color:#333;}\r\n\t\t\t\t\t\timg { display:block; width:100%;}\r\n\t\t\t\t\t\t.header {\r\n\t\t\t\t\t\twidth:100%; padding:10px 0;text-align:center;font-weight:bold;}\r\n\t\t\t\t\t\t</style>\r\n\t\t\t\t\t\t<div class='page_msg'>\r\n\t\t\t\t\t\t\r\n\t\t\t\t\t\t<div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span>" . $close['detail'] . "</div></div>\r\n\t\t\t\t\t\t</body>\r\n\t\t\t\t\t</html>");
		}
	}

	public function getAllCategory($refresh = false)
	{
		global $_W;
		$allcategory = m('cache')->getArray('allcategoryarr');
		if (empty($allcategory) || $refresh) {
			$allcategory = pdo_fetchall('SELECT id,parentid,uniacid,name,thumb FROM ' . tablename('ewei_shop_category') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\''), array(), 'id');
			m('cache')->set('allcategoryarr', $allcategory);
		}

		return $allcategory;
	}

    /**
     * @param int $type
     * @return array
     */
    public function get_icon($type = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $icon = pdo_fetchall('select * from '.tablename('ewei_shop_icon').'where uniacid = :uniacid and type = :type order by displayorder asc',[':uniacid'=>$uniacid,':type'=>$type]);
        return set_medias($icon,'image');
    }

    /**
     * @param $goodsList
     * @param $pages
     * @return array
     */
    public function getGoodsList($goodsList,$pages){
        $newGoodsList = array();
        $day=date("Y-m-d");
        $adv=pdo_fetchall("select * from ".tablename("ewei_shop_goodtop")." where is_del=0 and start_date<=:start_date and end_date>=:end_date",array(":start_date"=>$day,":end_date"=>$day));
        if ($pages==1){
            foreach ($goodsList['list'] as $key=>$goods){
                //判断是否包含
                $gid = $this->inarray($adv, $key);
                if ($gid==0){
                    $newGoodsList[$key]['gid'] = $goods['id'];
                    $newGoodsList[$key]['deduct'] = $goods['deduct'];
                    $newGoodsList[$key]['deduct_type'] = $goods['deduct_type'];
                    $newGoodsList[$key]['title'] = $goods['title'];
                    $newGoodsList[$key]['subtitle'] = $goods['subtitle'];
                    $newGoodsList[$key]['price'] = $goods['minprice'];
                    $newGoodsList[$key]['productprice'] = $goods['productprice'];
                    $newGoodsList[$key]['thumb'] = $goods['thumb'];
                    $newGoodsList[$key]['total'] = $goods['total'];
                    $newGoodsList[$key]['ctype'] = $goods['type'];
                    $newGoodsList[$key]['sales'] = $goods['sales'];
                    $newGoodsList[$key]['video'] = $goods['video'];
                    $newGoodsList[$key]['seecommission'] = $goods['seecommission'];
                    $newGoodsList[$key]['cansee'] = $goods['cansee'];
                    $newGoodsList[$key]['seetitle'] = $goods['seetitle'];
                    $newGoodsList[$key]['bargain'] = $goods['bargain'];
                    $newGoodsList[$key]['showprice'] = round($goods['minprice']-$goods['deduct'],2);
                    $newGoodsList[$key]['issendfree'] = $goods['issendfree'];
                    //添加广告表示
                    $newGoodsList[$key]['adv']=0;
                    $newGoodsList[$key]['main_target']="";
                    $newGoodsList[$key]['substandard']="";

                }else{
                    $a=pdo_get("ewei_shop_goodtop",array("id"=>$gid));
                    $gd=pdo_get("ewei_shop_goods",array("id"=>$a["goodid"]));
                    $newGoodsList[$key]['gid'] = $gd['id'];
                    $newGoodsList[$key]['deduct'] = $gd['deduct'];
                    $newGoodsList[$key]['deduct_type'] = $gd['deduct_type'];
                    $newGoodsList[$key]['title'] = $gd['title'];
                    $newGoodsList[$key]['subtitle'] = $gd['subtitle'];
                    $newGoodsList[$key]['price'] = $gd['minprice'];
                    $newGoodsList[$key]['productprice'] = $gd['productprice'];
                    $newGoodsList[$key]['thumb'] = tomedia($gd['thumb']);
                    $newGoodsList[$key]['total'] = $gd['total'];
                    $newGoodsList[$key]['ctype'] = $gd['type'];
                    $newGoodsList[$key]['sales'] = $gd['sales'];
                    $newGoodsList[$key]['video'] = $gd['video'];
                    $newGoodsList[$key]['seecommission'] = $gd['seecommission'];
                    $newGoodsList[$key]['cansee'] = $gd['cansee'];
                    $newGoodsList[$key]['seetitle'] = $gd['seetitle'];
                    $newGoodsList[$key]['bargain'] = $gd['bargain'];
                    $newGoodsList[$key]['showprice'] = round($gd['minprice']-$gd['deduct'],2);
                    $newGoodsList[$key]['issendfree'] = $gd['issendfree'];
                    //添加广告表示
                    $newGoodsList[$key]['adv']=1;
                    $newGoodsList[$key]['main_target']=$a["main_target"];
                    $newGoodsList[$key]['substandard']=$a["substandard"];
                }
            }
        }else{
            foreach ($goodsList['list'] as $key=>$goods){
                $newGoodsList[$key]['gid'] = $goods['id'];
                $newGoodsList[$key]['deduct'] = $goods['deduct'];
                $newGoodsList[$key]['deduct_type'] = $goods['deduct_type'];
                $newGoodsList[$key]['title'] = $goods['title'];
                $newGoodsList[$key]['subtitle'] = $goods['subtitle'];
                $newGoodsList[$key]['price'] = $goods['minprice'];
                $newGoodsList[$key]['productprice'] = $goods['productprice'];
                $newGoodsList[$key]['thumb'] = $goods['thumb'];
                $newGoodsList[$key]['total'] = $goods['total'];
                $newGoodsList[$key]['ctype'] = $goods['type'];
                $newGoodsList[$key]['sales'] = $goods['sales'];
                $newGoodsList[$key]['video'] = $goods['video'];
                $newGoodsList[$key]['seecommission'] = $goods['seecommission'];
                $newGoodsList[$key]['cansee'] = $goods['cansee'];
                $newGoodsList[$key]['seetitle'] = $goods['seetitle'];
                $newGoodsList[$key]['bargain'] = $goods['bargain'];
                $newGoodsList[$key]['showprice'] = round($goods['minprice']-$goods['deduct'],2);
                $newGoodsList[$key]['issendfree'] = $goods['issendfree'];
                //添加广告表示
                $newGoodsList[$key]['adv']=0;
                $newGoodsList[$key]['main_target']="";
                $newGoodsList[$key]['substandard']="";
            }
        }
        return $newGoodsList;
    }

    /**
     * 判断是否包含
     * @param $arary
     * @param $key
     * @return int
     */
    public function inarray($arary,$key){
        foreach ($arary as $k=>$v){
            if ($v["sort"]==$key){
                return $v["id"];
            }
        }
        return 0;
    }

    /**
     * @param null $url
     * @param bool $vid
     * @return array|string|void
     */
    public function getQVideo($url = NULL, $vid = false)
    {
        if (empty($url)) {
            return;
        }
        if (!($vid)) {
            $vid = $this->getQVideoVid($url);
        }
        load()->func('communication');
        $request = ihttp_get('https://h5vv.video.qq.com/getinfo?callback=renrenVideo&otype=json&platform=11001&host=v.qq.com&sphttps=1&vid=' . $vid);
        if (empty($request) || ($request['code'] != 200) || empty($request['content'])) {
            return error(-1, '获取失败-1');
        }
        $content = $request['content'];
        $content = ltrim($content, 'renrenVideo(');
        $content = rtrim($content, ')');
        $array = json_decode($content, true);
        if (!(is_array($array)) || !(isset($array['vl'])) || !(is_array($array['vl']['vi'])) || !(is_array($array['vl']['vi'][0]))) {
            return error(-1, '获取失败-2');
        }
        $fvideo = $array['vl']['vi'][0];
        if (empty($fvideo['fvkey']) || !(isset($fvideo['ul'])) || !(is_array($fvideo['ul']['ui'])) || !(is_array($fvideo['ul']['ui'][0])) || !(isset($fvideo['ul']['ui'][0]['url']))) {
            return error(-1, '获取失败-3');
        }
        $videopath = ((isset($fvideo['ul']['ui'][1]) && !(empty($fvideo['ul']['ui'][1]['url'])) ? $fvideo['ul']['ui'][1]['url'] : $fvideo['ul']['ui'][0]['url']));
        return $videopath . $fvideo['fn'] . '?vkey=' . $fvideo['fvkey'];
    }

    /**
     * @param $url
     * @return string|void
     */
    public function getQVideoVid($url)
    {
        if (empty($url) || !(strexists($url, 'v.qq.com/iframe/'))) {
            return;
        }
        $vid = '';
        $params = parse_url($url);
        parse_str($params['query']);
        return $vid;
    }

    /**
     * 可购买的会员级别
     * @param $cids
     * @return string
     */
    public function canByLevels($cids){
        if(count($cids)>1 && in_array(1,$cids)) return "健康达人以上级别专享";
        if(count($cids)==1 && in_array(1,$cids)) return "健康达人专享";
        if(count($cids)==1 && in_array(2,$cids)) return "星选达人专享";
        if(count($cids)==1 && in_array(5,$cids)) return "店主专享";
        if(count($cids)>1 && in_array(2,$cids)) return "星选达人以上级别专享";
        return "您当前会员等级没有购买权限";
    }

    /**
     * @param $openid
     * @return bool|string
     */
    public function getLevel($openid)
    {
        global $_W;
        $level = "false";
        if( empty($openid) )
        {
            return $level;
        }
        $member = m("member")->getMember($openid);
        if( empty($member["isagent"]) || $member["status"] == 0 || $member["agentblack"] == 1 )
        {
            return $level;
        }
        $level = pdo_fetch("select * from " . tablename("ewei_shop_commission_level") . " where uniacid=:uniacid and id=:id limit 1", array( ":uniacid" => $_W["uniacid"], ":id" => $member["agentlevel"] ));
        return $level;
    }

    /**
     * @param $goods
     * @param $level
     * @param $set
     * @return float|int|mixed
     */
    public function getCommission($goods, $level, $set)
    {
        global $_W;
        $commission = 0;
        if( $level == "false" )
        {
            return $commission;
        }
        if( $goods["hascommission"] == 1 )
        {
            $price = $goods["maxprice"];
            $levelid = "default";
            if( $level )
            {
                $levelid = "level" . $level["id"];
            }
            $goods_commission = (!empty($goods["commission"]) ? json_decode($goods["commission"], true) : array( ));
            if( $goods_commission["type"] == 0 )
            {
                $commission = (1 <= $set["level"] ? (0 < $goods["commission1_rate"] ? ($goods["commission1_rate"] * $goods["marketprice"]) / 100 : $goods["commission1_pay"]) : 0);
            }
            else
            {
                $price_all = array( );
                foreach( $goods_commission[$levelid] as $key => $value )
                {
                    foreach( $value as $k => $v )
                    {
                        if( strexists($v, "%") )
                        {
                            array_push($price_all, floatval(str_replace("%", "", $v) / 100) * $price);
                            continue;
                        }
                        array_push($price_all, $v);
                    }
                }
                $commission = max($price_all);
            }
        }
        else
        {
            if( !empty($level) )
            {
                $commission = (1 <= $set["level"] ? round(($level["commission1"] * $goods["marketprice"]) / 100, 2) : 0);
            }
            else
            {
                $commission = (1 <= $set["level"] ? round(($set["commission1"] * $goods["marketprice"]) / 100, 2) : 0);
            }
        }
        return $commission;
    }

    /**
     * @param $url
     * @return array
     */
    public function getUrl($url)
    {
        global $_W;
        if (empty($url)) {
            return array();
        }
        if (strexists($url, './index.php?') && strexists($url, 'ewei_shopv2') && strexists($url, 'mobile')) {
            $parse = parse_url($url);
            $parse_query = $parse['query'];
            if (empty($parse_query)) {
                return array();
            }
            $vars = explode('&', $parse_query);
            $newVars = array();
            foreach ($vars as $i => $var) {
                $vararr = explode('=', $var);
                $newVars[$vararr[0]] = $vararr[1];
            }
            if (($newVars['m'] != 'ewei_shopv2') || ($newVars['do'] != 'mobile')) {
                return array('url' => $url);
            }
            $route = $newVars['r'] = ((!(empty($newVars['r'])) ? $newVars['r'] : 'index'));
            unset($newVars['i'], $newVars['c'], $newVars['m'], $newVars['do'], $newVars['r']);
            $newUrl = array('url' => $route, 'vars' => $newVars);
            $routes = explode('.', $route);
            if (!(in_array($routes[0], ["index", "shop", "goods", "member", "sale", "account", "commission"]))) {
                $newUrl['url'] = $_W['siteroot'] . 'app/' . str_replace('./', '', $url);
                unset($newUrl['vars']);
            }
            return $newUrl;
        }
        return array('url' => $url);
    }

    /**
     * @param $goodid
     * @param $user_id
     * @return array
     */
    public function getCouponsbygood($goodid,$user_id = 0)
    {
        global $_W;
        global $_GPC;
        $merchdata = $this->merchData();
        extract($merchdata);
        $time = time();
        $time = time();
        $param = array( );
        $param[":uniacid"] = $_W["uniacid"];
        $sql = "select id,timestart,timedays,timeend,couponname,enough,backtype,deduct,getmax,merchid,total from " . tablename("ewei_shop_coupon") . " c ";
        $sql .= " where uniacid=:uniacid and money=0 and credit = 0 and coupontype=0";
        if( $is_openmerch == 0 )
        {
            $sql .= " and merchid=0";
        }
        else
        {
            if( !empty($_GPC["merchid"]) )
            {
                $sql .= " and merchid=:merchid";
                $param[":merchid"] = intval($_GPC["merchid"]);
            }
            else
            {
                $sql .= " and merchid=0";
            }
        }
        $hascommission = false;
        $plugin_com = p("commission");
        if( $plugin_com )
        {
            $plugin_com_set = $plugin_com->getSet();
            $hascommission = !empty($plugin_com_set["level"]);
            if( empty($plugin_com_set["level"]) )
            {
                $sql .= " and ( limitagentlevels = \"\" or  limitagentlevels is null )";
            }
        }
        else
        {
            $sql .= " and ( limitagentlevels = \"\" or  limitagentlevels is null )";
        }
        $hasglobonus = false;
        $plugin_globonus = p("globonus");
        if( $plugin_globonus )
        {
            $plugin_globonus_set = $plugin_globonus->getSet();
            $hasglobonus = !empty($plugin_globonus_set["open"]);
            if( empty($plugin_globonus_set["open"]) )
            {
                $sql .= " and ( limitpartnerlevels = \"\"  or  limitpartnerlevels is null )";
            }
        }
        else
        {
            $sql .= " and ( limitpartnerlevels = \"\"  or  limitpartnerlevels is null )";
        }
        $hasabonus = false;
        $plugin_abonus = p("abonus");
        if( $plugin_abonus )
        {
            $plugin_abonus_set = $plugin_abonus->getSet();
            $hasabonus = !empty($plugin_abonus_set["open"]);
            if( empty($plugin_abonus_set["open"]) )
            {
                $sql .= " and ( limitaagentlevels = \"\" or  limitaagentlevels is null )";
            }
        }
        else
        {
            $sql .= " and ( limitaagentlevels = \"\" or  limitaagentlevels is null )";
        }
        $sql .= " and gettype=1 and (total=-1 or total>0) and ( timelimit = 0 or  (timelimit=1 and timeend>" . $time . "))";
        $sql .= " order by displayorder desc, id desc  ";
        $list = pdo_fetchall($sql, $param);
        //$list = set_medias($list"thumb");
        if( empty($list) )
        {
            $list = array( );
        }
        if( !empty($goodid) )
        {
            $goodparam[":uniacid"] = $_W["uniacid"];
            $goodparam[":id"] = $goodid;
            $sql = "select id,cates,marketprice,merchid   from " . tablename("ewei_shop_goods");
            $sql .= " where uniacid=:uniacid and id =:id order by id desc LIMIT 1 ";
            $good = pdo_fetch($sql, $goodparam);
        }
        $cates = explode(",", $good["cates"]);
        if( !empty($list) )
        {
            foreach( $list as $key => &$row )
            {
                $row = com("coupon")->setCoupon($row, time());
                //$row["thumb"] = tomedia($row["thumb"]);
                $row["timestr"] = "永久有效";
                $row['timestart'] = date('Y-m-d',$row['timestart']);
                $row['timeend'] = date('Y-m-d',$row['timeend']);
                if(!empty($user_id)){
                    $member = m('member')->getMember($user_id);
                    $coupon = pdo_fetch('select * from '.tablename('ewei_shop_coupon_data').' where couponid = :couponid and (openid = :openid or user_id = :user_id) ',[':user_id'=>$member['id'],':openid'=>$member['openid'],':couponid'=>$row['id']]);
                    //状态  如果没有状态  0未领取 used==0  已领取未使用1  其他等于2
                    if(empty($coupon)){
                        $row['coupon_status'] = 0;
                    }elseif ($coupon['used'] == 0){
                        $row['coupon_status'] = 1;
                    }else{
                        $row['coupon_status'] = 2;
                    }
                }
                if( empty($row["timelimit"]) )
                {
                    if( !empty($row["timedays"]) )
                    {
                        $row["timestr"] = "自领取日后" . $row["timedays"] . "天有效";
                    }
                }
                else
                {
                    if( $time <= $row["timestart"] )
                    {
                        $row["timestr"] = "有效期至:" . date("Y-m-d", $row["timestart"]) . "-" . date("Y-m-d", $row["timeend"]);
                    }
                    else
                    {
                        $row["timestr"] = "有效期至:" . date("Y-m-d", $row["timeend"]);
                    }
                }
                if( $row["backtype"] == 0 )
                {
                    $row["backstr"] = "立减";
                    $row["backmoney"] = (double) $row["deduct"];
                    //$row["backpre"] = true;
//                    if( $row["enough"] == "0" )
//                    {
//                        $row["color"] = "org ";
//                    }
//                    else
//                    {
//                        $row["color"] = "blue";
//                    }
                }
                else
                {
                    if( $row["backtype"] == 1 )
                    {
                        $row["backstr"] = "折";
                        $row["backmoney"] = (double) $row["discount"];
                        //$row["color"] = "red ";
                    }
                    else
                    {
                        if( $row["backtype"] == 2 )
                        {
//                            if( $row["coupontype"] == "0" )
//                            {
//                                $row["color"] = "red ";
//                            }
//                            else
//                            {
//                                $row["color"] = "pink ";
//                            }
                            if( 0 < $row["backredpack"] )
                            {
                                $row["backstr"] = "返现";
                                $row["backmoney"] = (double) $row["backredpack"];
                                //$row["backpre"] = true;
                            }
                            else
                            {
                                if( 0 < $row["backmoney"] )
                                {
                                    $row["backstr"] = "返利";
                                    $row["backmoney"] = (double) $row["backmoney"];
                                    //$row["backpre"] = true;
                                }
                                else
                                {
                                    if( !empty($row["backcredit"]) )
                                    {
                                        $row["backstr"] = "返卡路里";
                                        $row["backmoney"] = (double) $row["backcredit"];
                                    }
                                }
                            }
                        }
                    }
                }
                $limitmemberlevels = explode(",", $row["limitmemberlevels"]);
                $limitagentlevels = explode(",", $row["limitagentlevels"]);
                $limitpartnerlevels = explode(",", $row["limitpartnerlevels"]);
                $limitaagentlevels = explode(",", $row["limitaagentlevels"]);
                $p = 0;
                if( $row["islimitlevel"] == 1 )
                {
                    $openid = trim($_W["openid"]);
                    $member = m("member")->getMember($openid);
                    if( !empty($row["limitmemberlevels"]) || $row["limitmemberlevels"] == "0" )
                    {
                        $level1 = pdo_fetchall("select * from " . tablename("ewei_shop_member_level") . " where uniacid=:uniacid and  id in (" . $row["limitmemberlevels"] . ") ", array( ":uniacid" => $_W["uniacid"] ));
                        if( in_array($member["level"], $limitmemberlevels) )
                        {
                            $p = 1;
                        }
                    }
                    if( (!empty($row["limitagentlevels"]) || $row["limitagentlevels"] == "0") && $hascommission )
                    {
                        $level2 = pdo_fetchall("select * from " . tablename("ewei_shop_commission_level") . " where uniacid=:uniacid and id  in (" . $row["limitagentlevels"] . ") ", array( ":uniacid" => $_W["uniacid"] ));
                        if( $member["isagent"] == "1" && $member["status"] == "1" && in_array($member["agentlevel"], $limitagentlevels) )
                        {
                            $p = 1;
                        }
                    }
                    if( (!empty($row["limitpartnerlevels"]) || $row["limitpartnerlevels"] == "0") && $hasglobonus )
                    {
                        $level3 = pdo_fetchall("select * from " . tablename("ewei_shop_globonus_level") . " where uniacid=:uniacid and  id in(" . $row["limitpartnerlevels"] . ") ", array( ":uniacid" => $_W["uniacid"] ));
                        if( $member["ispartner"] == "1" && $member["partnerstatus"] == "1" && in_array($member["partnerlevel"], $limitpartnerlevels) )
                        {
                            $p = 1;
                        }
                    }
                    if( (!empty($row["limitaagentlevels"]) || $row["limitaagentlevels"] == "0") && $hasabonus )
                    {
                        $level4 = pdo_fetchall("select * from " . tablename("ewei_shop_abonus_level") . " where uniacid=:uniacid and  id in (" . $row["limitaagentlevels"] . ") ", array( ":uniacid" => $_W["uniacid"] ));
                        if( $member["isaagent"] == "1" && $member["aagentstatus"] == "1" && in_array($member["aagentlevel"], $limitaagentlevels) )
                        {
                            $p = 1;
                        }
                    }
                }
                else
                {
                    $p = 1;
                }
                if( $p == 1 )
                {
                    $p = 0;
                    $limitcateids = explode(",", $row["limitgoodcateids"]);
                    $limitgoodids = explode(",", $row["limitgoodids"]);
                    if( $row["limitgoodcatetype"] == 0 && $row["limitgoodtype"] == 0 )
                    {
                        $p = 1;
                    }
                    if( $row["limitgoodcatetype"] == 1 )
                    {
                        $result = array_intersect($cates, $limitcateids);
                        if( 0 < count($result) )
                        {
                            $p = 1;
                        }
                    }
                    if( $row["limitgoodtype"] == 1 )
                    {
                        $isin = in_array($good["id"], $limitgoodids);
                        if( $isin )
                        {
                            $p = 1;
                        }
                    }
                    if( $p == 0 )
                    {
                        unset($list[$key]);
                    }
                }
                else
                {
                    unset($list[$key]);
                }
            }
            unset($row);
        }
        return array_values($list);
    }

    /**
     * @return array
     */
    protected function merchData()
    {
        $merch_plugin = p("merch");
        $merch_data = m("common")->getPluginset("merch");
        if( $merch_plugin && $merch_data["is_openmerch"] )
        {
            $is_openmerch = 1;
        }
        else
        {
            $is_openmerch = 0;
        }
        return array( "is_openmerch" => $is_openmerch, "merch_plugin" => $merch_plugin, "merch_data" => $merch_data );
    }

    /**
     * @param $goods
     * @param bool $is_seckill
     * @return array|int
     */
    public function getGoodsDispatchPrice($goods, $is_seckill = false)
    {
        if( !empty($goods["issendfree"]) && empty($is_seckill) )
        {
            return 0;
        }
        if( $goods["type"] == 2 || $goods["type"] == 3 || $goods["type"] == 20 )
        {
            return 0;
        }
        if( $goods["dispatchtype"] == 1 )
        {
            return $goods["dispatchprice"];
        }
        if( empty($goods["dispatchid"]) )
        {
            $dispatch = m("dispatch")->getDefaultDispatch($goods["merchid"]);
        }
        else
        {
            $dispatch = m("dispatch")->getOneDispatch($goods["dispatchid"]);
        }
        if( empty($dispatch) )
        {
            $dispatch = m("dispatch")->getNewDispatch($goods["merchid"]);
        }
        $areas = iunserializer($dispatch["areas"]);
        if( !empty($areas) && is_array($areas) )
        {
            $firstprice = array( );
            foreach( $areas as $val )
            {
                if( empty($dispatch["calculatetype"]) )
                {
                    $firstprice[] = $val["firstprice"];
                }
                else
                {
                    $firstprice[] = $val["firstnumprice"];
                }
            }
            array_push($firstprice, m("dispatch")->getDispatchPrice(1, $dispatch));
            $ret = array( "min" => round(min($firstprice), 2), "max" => round(max($firstprice), 2) );
        }
        else
        {
            $ret = m("dispatch")->getDispatchPrice(1, $dispatch);
        }
        return $ret;
    }

    /**
     * @param $id
     * @param string $keywords
     * @param array $args
     * @return array
     */
    public function get_cate_list($id,$keywords = "",$args = [])
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $page = $args['page'];
        $pageSize = $args['pagesize'];
        $pindex = ($page - 1) * $pageSize;
        $order = $args['order'];
        $condition = '`uniacid` = :uniacid AND `deleted` = 0 and status=1 and icon_id = :id';
        $params = array(':uniacid' => $uniacid,':id'=>$id);
        if(!empty($keywords)){
            $condition .= ' AND (`title` LIKE :keywords OR `keywords` LIKE :keywords)';
            $params[':keywords'] = '%' . trim($keywords) . '%';
        }
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods').' where '.$condition.' ',$params);
        $list = pdo_fetchall('select id,title,thumb,marketprice,productprice,issendfree,total,agent_devote,istime,timestart,timeend from '.tablename('ewei_shop_goods').' where '.$condition.' order by '.$order.' limit '.$pindex.','.$pageSize,$params);
        foreach ($list as $key =>$value){
            $list[$key]['sendfree'] = $value['issendfree'] == 1 ? "包邮" : $value['total'];
            $order_count = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_order').'o join '.tablename('ewei_shop_order_goods').'og on og.orderid = o.id where og.goodsid = :goodsid and o.uniacid = :uniacid and o.status = 1',[':goodsid'=>$value['id'],':uniacid'=>$uniacid]);
            $list[$key]['order'] = $order_count > 9999 ? ($order_count/10000).'万' : $order_count;
            $list[$key]['thumb'] = tomedia($value['thumb']);
            $list[$key]['adv'] = 0;
        }
        $count = pdo_count('ewei_shop_choice',['uniacid'=>$uniacid,'status'=>1,'icon_id'=>$id]);
        $pindex = rand(0,max(0,$count-1));
        $choice = pdo_fetch(' select * from '.tablename('ewei_shop_choice').' where uniacid = "'.$uniacid.'" and status = 1 and icon_id = "'.$id.'" limit '.$pindex.', 1');
//        $choice_ids = pdo_getall('ewei_shop_choice',['uniacid'=>$uniacid,'status'=>1,'icon_id'=>$id],'id');
//        $choice_id = array_column($choice_ids,'id');
//        $chid = $choice_id[array_rand($choice_id,1)];
//        $choice = pdo_fetch(' select thumb,image from '.tablename('ewei_shop_choice').' where uniacid = "'.$uniacid.'" and id = "'.$chid.'" ');
        if(!empty($choice)){
            $choice["thumb"] = tomedia($choice['thumb']);
            $choice["image"] = tomedia($choice['image']);
            $choice["content"] = htmlspecialchars_decode($choice['content']);
            $choice["adv"] = 1;
        }
        if($choice) array_push($list,$choice);
        $pagetotal = ceil(($total + 1)/($pageSize + 1));
        return ['list'=>$list,'page'=>$page,'pageSize'=>$pageSize+1,'total'=>$total+1,'pagetotal'=>$pagetotal];
    }
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

?>
