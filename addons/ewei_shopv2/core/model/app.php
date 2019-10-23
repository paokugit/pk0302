<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class App_EweiShopV2Model
{
    /**
     * APP登录token加密
     * @param $user_id
     * @param $salt
     * @return string
     */
    public function setLoginToken($user_id,$salt)
    {
        return base64_encode(implode(',',[$user_id,$salt]));
    }

    /**
     * APP鉴权校验
     * @param $token
     * @return int
     */
    public function getLoginToken($token)
    {
        $data = explode(',',base64_decode($token));
        //把登录的账户查出来  然后 对比登录产生的随机码  如果一样就是当前登录 不一样就是又被登录
        $member = pdo_get('ewei_shop_member',['id'=>$data[0]]);
        return $member['app_salt'] == $data[1] ? $data[0] : 0;
    }

    /**
	 * 获取卡路里  步数  邀请步数  是否绑定手机号
     * @param $user_id
     * @return array
     */
	public function getbushu($user_id)
	{
		//用户信息
        $member = m('member')->getMember($user_id);
        //用户的折扣宝  卡路里
        $data['credit1'] = $member['credit1'];
        $data['credit3'] = $member['credit3'];
        //今天的时间
        $day = date('Y-m-d');
        //自身步数
        $bushu = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where  `day` = :today and (user_id = :user_id or openid = :openid) and type!=:type", array(':today' => $day, ':user_id' => $user_id,':openid'=>$member['openid'],':type'=>2));
        $data['todaystep'] = empty($bushu) ? 0 : $bushu;
        //邀请步数
        $yaoqing = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where  `day` = :today and (user_id = :user_id  or openid = :openid)", array(':today' => $day, ':user_id' => $user_id,':openid'=>$member['openid']));
        $data['yaoqing'] = empty($yaoqing) ? 0 : $yaoqing;
		//是否绑定手机号
		$data["bind"] = !empty($member["mobile"]) ? 1 : 0;
		//礼包的气泡
        $uniacid = $_W['uniacid'];
        $gift = pdo_fetchall(' select id,title,levels from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$uniacid.'"');
        $data = m('game')->get_gift($gift,$member['openid']);
        $data['gift'] = $data ? 1 : 0;
		//未领取的气泡
        if (empty($member['agentlevel'])) {
            //普通会员的情况
            $subscription_ratio = 0.5;
            $exchange = 0.5/1500;
            $exchange_step = m("member")->exchange_step($user_id);
            $bushu = ceil($exchange_step*1500/0.5);
        } else {
            $memberlevel = pdo_get('ewei_shop_commission_level', array('id' => $member['agentlevel']));
            $subscription_ratio = $memberlevel["subscription_ratio"];
            $exchange = $subscription_ratio/1500;
            $exchange_step = m("member")->exchange_step($user_id);
            $bushu = ceil($exchange_step*1500/$subscription_ratio);
        }
        //获取今日已兑换的卡路里
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $cardtoday = pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where `createtime` >= :beginToday and `createtime` <= :endToday and (user_id = :user_id or openid = :openid) and credittype = :credittype and (remark like :remark1 or remark like :remark2)",array(":beginToday"=>$beginToday,":endToday"=>$endToday,":credittype"=>"credit1",":user_id"=>$user_id,":openid"=>$member['openid'],":remark1"=>'%步数兑换%',":remark2"=>'%好友助力%'));
        $step_number = $jinri = empty($cardtoday) ? 0 : $cardtoday*1500/$subscription_ratio;
        if ($step_number < $bushu) {
            $datault = pdo_fetchall("select * from ".tablename("ewei_shop_member_getstep")." where day = :day and (user_id = :user_id or openid = :openid) and status = 0 order by step asc",array(":day"=>$day,":user_id"=>$user_id,':openid'=>$openid));
        }else{
            $datault = pdo_fetchall("select * from ".tablename("ewei_shop_member_getstep")." where day = :day and (user_id = :user_id or openid = :openid) and status = 0 and type = 2 order by step asc",array(":day"=>$day,":user_id"=>$user_id,':openid'=>$member['openid']));
        }
        $r=array();
        $i=0;
        foreach ($datault as &$vv) {
            if ($i<3){
                if ($vv["type"]!=2){
                    //步数小于今日步数
                    if ($step_number < $bushu){
                        if ($step_number + $vv["step"] >= $bushu){
                            //大于
                            $r[$i]["id"] = $vv["id"];
                            $r[$i]["step"] = $bushu-$step_number;
                            $card1 = ($bushu-$step_number)*$exchange;
                            if ($card1 > 0.01){
                                $r[$i]["currency"] = round($card1,2);
                            }else{
                                $r[$i]["currency"] = round($card1,4);
                            }
                            $r[$i]["type"] = $vv["type"];
                            $step_number = $bushu;
                        }else{
                            //小于
                            $r[$i]["id"] = $vv["id"];
                            $r[$i]["step"] = $vv["step"];
                            $card1 = $vv["step"]*$exchange;
                            if ($card1 > 0.01){
                                $r[$i]["currency"] = round($card1,2);
                            }else{
                                $r[$i]["currency"] = round($card1,4);
                            }
                            $step_number = $step_number+$vv["step"];
                            $r[$i]["type"] = $vv["type"];
                        }
                        $i = $i+1;
                    }
                }else{
                    $r[$i]["id"] = $vv["id"];
                    $r[$i]["step"] = $vv["step"];
                    $r[$i]["currency"] = 1;
                    $r[$i]["type"] = $vv["type"];
                    $i = $i+1;
                }
            }
        }
        unset($vv);
        $data['icon'] = $r;
        //平台总人数
        $id = 61779;
        $new_count = pdo_count('ewei_shop_member','id > "'.$id.'"');
        $data['count'] = $id*11 + $new_count*7;
        return $data;
	}

    /**
     * 小图标  快报  年卡入口
     * @param $user_id
     * @param $type
     * @return mixed
     */
	public function get_icon($user_id,$type)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        //活动小图标入口
        $list=pdo_get("ewei_shop_small_set",array("id"=>1));
        $l["backgroup"]=tomedia($list["backgroup"]);
        $l["banner"]=tomedia($list["banner"]);
        //获取icon
        $l["icon"]=pdo_fetchall("select id,olddata from ".tablename("ewei_shop_small_setindex")." where status=0 order by sort asc");
        foreach ($l["icon"] as $k=>$v){
            $d=unserialize($v["olddata"]);
            $l["icon"][$k]["img"]=tomedia($d["img"]);
            if ($v["icon"]){
                $l["icon"][$k]["icon"]=tomedia($d["icon"]);
            }else{
                $l["icon"][$k]["icon"]="";
            }
            $l["icon"][$k]["url"]=$d["url"];
            $l["icon"][$k]["title"]=$d["title"];
        }
        $data['icon'] = $l;
        //快报
        //计算提现总人数
        $list = pdo_fetchall('select sum(l.money) as sum_money,m.nickname from '.tablename('ewei_shop_member_log').'l join '.tablename('ewei_shop_member').'m on m.openid = l.openid or m.id = l.user_id'.' where l.uniacid = "'.$uniacid.'" and type = 1 and l.status = 1 group by l.openid order by sum_money desc');
        $total = count($list);
        //设置每页数
        $pageSize = 100;
        //随机获取第几页  以及每页的第几个
        $page = rand(1,floor($total/$pageSize));
        $psize = ($page-1)*$pageSize;
        //分页显示
        $log = pdo_fetchall('select sum(l.money) as sum_money,m.nickname,m.id from '.tablename('ewei_shop_member_log').'l join '.tablename('ewei_shop_member').'m on m.openid=l.openid or m.id = l.user_id'.' where l.uniacid = "'.$uniacid.'" and type = 1 and l.status = 1 and m.id NOT IN (4350,9851,9861) group by l.openid order by sum_money desc LIMIT '.$psize.','.$pageSize);
        foreach ($log as &$item){
            //计算昵称的长度
            $length = mb_strlen($item['nickname']);
            //如果昵称长度小于等于3  就截取1位 并拼接***   如果昵称大于4  截取第1位和最后1位
            if($length <= 3){
                $item['nickname'] = mb_substr($item['nickname'],0,1)."***";
            }elseif($length >= 4){
                $item['nickname'] = mb_substr($item['nickname'],0,1)."***".mb_substr($item['nickname'],-1,1);
            }
        }
        $data['rank'] = ['log'=>$log,'page'=>$page,'total'=>$total];
        //年卡入口
        $list = pdo_fetchall("select * from ".tablename("ewei_shop_adsense")." where type=:type order by sort desc",array(":type"=>$type));
        foreach ($list as $k=>$v){
            $list[$k]["thumb"]=tomedia($v["thumb"]);
            $list[$k]['url'] = strpos($v['url'],"member_card") == false ? : $member['is_open'] == 1 ? $v['url'] : "/pages/annual_card/equity/equity";
        }
        $data["list"]=$list;
        return $data;
    }

    /**
     * 门店服务
     * @param $user_id
     * @return array
     */
    public function merch($user_id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        //获得当前用户的店铺
        $memberMerchInfo = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where member_id = :member_id Limit 1', array(':member_id' => $member['id']));
        $data = array();
        //如果当前用户有上级  查他的上级的店铺
        if($member['agentid']>0){
            $agentMerchInfo = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where member_id = :member_id Limit 1', array(':member_id' => $member['agentid']));
        }
        //当前用户是店主
        if($memberMerchInfo) {
            $args['merchid'] = $memberMerchInfo['id'];
            $merchInfo = $memberMerchInfo;
        }elseif($member && $member['from_merchid']>0){
            //当前用户绑定了商户   查他绑定的商户是谁
            $merchInfo = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where id = :merchid and uniacid = :uniacid Limit 1', array(':uniacid' => $_W['uniacid'], ':merchid' => $member['from_merchid']));
            //查绑定商户里面的商品
            $goodsNum = pdo_count("ewei_shop_goods", "deleted = 0 and status = 1 and uniacid = " . $uniacid . " and merchid = " . $member['from_merchid']);
            if($merchInfo){//获取推荐商铺
                $args['merchid'] = $member['from_merchid'];
            }else{//推荐附近商店
                $merchInfo = m('merch')->get_near_merch(1);
                $args['merchid'] = $merchInfo['id'];
            }
            if($goodsNum < 3){//推荐其他商品数量大于三的店铺
                $merchInfo = m('merch')->get_near_merch(1);
                $args['merchid'] = $merchInfo['id'];
            }

        }elseif($agentMerchInfo){//查看推荐人是否有店铺
            $args['merchid'] = $agentMerchInfo['id'];
            $merchInfo = $agentMerchInfo;
        }else{//推荐附近商店
            $merchInfo = m('merch')->get_near_merch(1);
            $args['merchid'] = $merchInfo['id'];
        }
        $args['order'] = 'sort desc,isrecommand';
        $goodList = m('goods')->getList($args);
        //获得商品的logo
        $merchInfo['logo'] = tomedia($merchInfo['logo']);
        $data['merchInfo'] = $merchInfo;
        $data['goodList'] = $goodList;
        return $data;
    }

    /**
     * 附近商家
     * @param $user_id
     * @param $lat
     * @param $lng
     * @param $range
     * @param $cateid
     * @param $sorttype
     * @param $keyword
     * @return array
     */
    public function near($user_id,$lat,$lng,$range=1000,$cateid=0,$sorttype,$keyword)
    {
        global $_W;
        //获取用户的信息
        $member=m('member')->getMember($user_id);
        $merch_plugin = p('merch');
        //获取商家的系统配置
        $merch_data = m('common')->getPluginset('merch');
        $citysel = false;
        $citys = array();
        //is_openmerch  商家的开关
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $data = array();
            $cate = array();
            if (!empty($keyword)) {
                $data['like'] = array('merchname' => $keyword);
            }
            if (!empty($cateid)) {
                $data['cateid'] = $cateid;
            }
            $data = array_merge($data, array('status' => 1, 'field' => 'id,uniacid,merchname,mobile,salecate,logo,groupid,cateid,address,tel,lng,lat,reward_type'));
            if (!empty($sorttype)) {
                $data['orderby'] = array('id' => 'desc');
            }
            //获得符合条件的商家
            $merchuser = $merch_plugin->getMerch($data);
            //商家的商业分类的查询条件
            $cate = array_merge($cate, array('status' => 1, 'orderby' => array('displayorder' => 'desc', 'id' => 'asc')));
            //获得商家所属行业
            $category = $merch_plugin->getCategory($cate);
            if (!(empty($merchuser))) {
                $cate_list = array();
                if (!(empty($category))) {
                    foreach ($category as $k => $v) {
                        $cate_list[$v['id']] = $v;
                    }
                }
                if (!empty($member['agentid'])){
                    //当前登陆用户的上级 是不是
                    $agent = m('member')->getMember($member['agentid']);
                    //上级的商家
                    $isstore = pdo_getall('ewei_shop_merch_user',array('member_id'=>$agent['id']));
                }
                if (!empty($isstore)){
                    //把上级的商家 和 符合条件的商家合并
                    $merchuser = array_merge($isstore,$merchuser);
                }
                foreach ($merchuser as $k => $v) {
                    if (($lat != 0) && ($lng != 0) && !(empty($v['lat'])) && !(empty($v['lng']))) {
                        //计算当前位置   与的经纬度的距离
                        $distance = m('util')->GetDistance($lat, $lng, $v['lat'], $v['lng'], 2);
                        //搜索的范围大于商家与当前位置的范围   去掉这个商家
                        if ((0 < $range) && ($range < $distance)) {
                            unset($merchuser[$k]);
                            continue;
                        }
                        $merchuser[$k]['distance'] = $distance;
                        //如果小于1公里  乘以1000  显示米
                        if ($distance < 1) $disname = ($distance * 1000) . 'm';
                        else $disname = ($distance) . 'km';
                        $merchuser[$k]['disname'] = $disname;
                    } elseif ($range) {
                        unset($merchuser[$k]);
                        continue;
                    } else {
                        $merchuser[$k]['distance'] = 100000;
                        $merchuser[$k]['disname'] = '';
                    }
                    $merchuser[$k]['catename'] = $cate_list[$v['cateid']]['catename'];
                    $merchuser[$k]['logo'] = tomedia($v['logo']);

                    //判断是否有赏金任务
                    if ($v["reward_type"]==0){
                        $merchuser[$k]["is_reward"]=0;
                        $merchuser[$k]["reward_money"]=0;
                    }else{
                        //获取是否有进行中的赏金
                        $reward=pdo_get("ewei_shop_merch_reward",array('is_end'=>0,'merch_id'=>$v["id"]));
                        if ($reward){
                            $merchuser[$k]["is_reward"] = 1;
                            //获取赏金
                            $merchuser[$k]["reward_money"] = m("merch")->reward_money($v["id"],$v["reward_type"]);
                        }else{
                            $merchuser[$k]["is_reward"] = 0;
                            $merchuser[$k]["reward_money"] = 0;
                        }
                    }
                }
            }
            $total = count($merchuser);
            if ($sorttype == 0 && !empty($merchuser)) {
                $merchuser = m('util')->multi_array_sort($merchuser, 'distance');
            }
            if (!(empty($merchuser))) {
                $merchuser = array_slice($merchuser, 0, 6);
            }
            //增加城市选择
            if (pdo_fieldexists('ewei_shop_merch_user', 'city')) {
                $tmp = pdo_fetchall("select distinct(province),city from " . tablename('ewei_shop_merch_user') . " where uniacid=:uniacid and status=1 order by province,city", array(':uniacid' => $_W['uniacid']));
                if (!empty($tmp)) {
                    $citysel = true;
                    foreach ($tmp as $v) $citys[$v['province']][] = $v['city'];
                }
            }
        }
        if (empty($merchuser)) $merchuser = array();
        $disopt = array();
        $disopt[] = array('range' => 1, 'title' => '1KM以内');
        $disopt[] = array('range' => 3, 'title' => '3KM以内');
        $disopt[] = array('range' => 5, 'title' => '5KM以内');
        $disopt[] = array('range' => 10, 'title' => '10KM以内');
        return ['list' => $merchuser,'cates' => $category,'disopt' => $disopt,'citysel' => $citysel,'citys' => $citys];
    }

    /**
     * 秒杀
     * @param $type
     * @return array
     */
    public function seckill($type)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $condition = "";
        //疯狂抢购中
        $time = time();
        if($type == 1){
            $condition .= " and uniacid = '".$uniacid."' and deleted = 0 and istime = 1 and status > 0 and timestart < '".$time."' and timeend > '".$time."'";
        }else{
            //即将开始
            $condition .= " and uniacid = '".$uniacid."' and istime = 1 and  deleted = 0 and status > 0 and timestart > '".$time."'";
        }
        $total = pdo_fetch('select count(*) as count from '.tablename('ewei_shop_goods').'where 1' .$condition);
        $list = pdo_fetchall('select id,title,thumb,productprice,marketprice,deduct,deduct_type,istime,timestart,timeend,sales,total,salesreal from '.tablename('ewei_shop_goods').' where 1' . $condition .'order by id desc LIMIT 6');
        foreach ($list as $key=>$item){
            $list[$key]['thumb'] = tomedia($item['thumb']);
            $list[$key]['sales'] = intval($item["sales"]);
            $list[$key]['total'] = intval($item["total"]);
            $list[$key]['salesreal'] = intval($item["salesreal"]);
            $list[$key]['showprice'] = bcsub($item['marketprice'],$item['deduct'],2);
        }
        if($type == 1){
            $down_time = $list[0]['timeend'];
        }else{
            $down_time = $list[0]['timestart'];
        }
        return ['list'=>$list,'end_time'=>$down_time];
    }

    /**
     * 边看边买
     * @return array
     */
    public function look_buy()
    {
        //查看有视频的  有库存的  在售的所有商品
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_look_buy').'where status = 1 order by displayorder desc limit 5');
        foreach ($list as $key=>$item){
            //音频和图片
            $list[$key]['video'] = tomedia($item['video']);
            $list[$key]['thumb'] = tomedia($item['thumb']);
            //视频对应的商品
            $goods = pdo_get('ewei_shop_goods',['id'=>$item['goods_id']]);
            $list[$key]['marketprice'] = $goods['marketprice'];
            $list[$key]['productprice'] = $goods['productprice'];
            //已销售
            $list[$key]['sales'] = $goods['sales']+$goods['realsales'];
            //商品类型 卡路里  还是  折扣宝
            $list[$key]['deduct'] = $goods['deduct_type'];
        }
        return $list;
    }

    /**
     * 每日一读
     */
    public function every()
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_member_reading').'where uniacid = :uniacid order by id desc limit 6',[':uniacid'=>$uniacid]);
        foreach ($list as $key => $item){
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
        }
        return $list;
    }

    /**
     * 跑库精选
     */
    public function choice()
    {

    }

    /**
     * 领取折扣宝 或者 卡路里
     * @param $user_id
     * @param int $step_id
     * @return bool
     */
    public function getcredit($user_id,$step_id = 0)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        if(empty($step_id)) return false;
        //获取用户信息
        $member = m('member')->getMember($user_id);
        //获得用户可以兑换的卡路里
        $exchange_step = m("member")->exchange_step($user_id);
        //获取要领取的卡路里  对应的数据
        $step = pdo_fetch('select id,step from '.tablename('ewei_shop_member_getstep').'where id = :id and uniacid = :uniacid',[':id'=>$step_id,''=>$uniacid]);
        $add["step"] = [
            'step'=>$step["step"],
            'step_id'=>$step_id,
            'createtime'=>time(),
            'openid'=>$member['openid'],
            'user_id'=>$user_id,
        ];
        //加入领取记录
        pdo_insert("ewei_shop_member_getsteplog",$add);
        //获得用户每1500可以兑换的卡路里
        $subscription_ratio = $member['agentlevel'] == 0 ? 0.5 : pdo_getcolumn('ewei_shop_commission_level', array('id' => $member['agentlevel']),'subscription_ratio');
        //获得兑换率
        $exchange = $subscription_ratio / 1500;
        //用户可以获得卡路里
        $bushu = ceil($exchange_step * 1500 / $subscription_ratio);
        //今天的开始结束时间
        $beginToday = strtotime(date('Y-m-d'));
        $endToday = strtotime(date('Y-m-d',strtotime('+1 days')));
        //步数兑换和好友捐赠  今天用户得到了多少卡路里
        $cardtoday = pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where `createtime` >= :beginToday and `createtime` <= :endToday and (user_id = :user_id or openid = :openid) and credittype = :credittype and (remark like :remark1 or remark like :remark2)",array(":beginToday"=>$beginToday,":endToday"=>$endToday,":credittype"=>"credit1",":user_id"=>$user_id,":openid"=>$member['openid'],":remark1"=>'%步数兑换%',":remark2"=>'%好友助力%'));
        $jinri = empty($cardtoday) ? 0 : $cardtoday * 1500 / $subscription_ratio;
        $keduihuan = $jinri + $step['step'] > $bushu ? ($bushu - $jinri) * $exchange : $step['step'] * $exchange;
        if ($step["type"]==0){
            m('member')->setCredit($user_id, 'credit1', $keduihuan, "步数兑换");
        }elseif ($step["type"]==1){
            m('member')->setCredit($user_id, 'credit1', $keduihuan, "好友助力");
        }
        pdo_update('ewei_shop_member_getstep', array('status' => 1), array('id' => $step_id));
        return true;
    }

    /**
     * 贡献列表
     * @param $user_id
     * @return array
     */
    public function devote_machine($user_id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m()->getMember($user_id);
        //如果是贡献机用户
        $devote = pdo_fetchall('select * from '.tablename('ewei_shop_devote_record').' where uniacid = :uniacid and (user_id = :user_id or openid = :openid) and status = 1'.[':uniacid'=>$uniacid,':user_id'=>$user_id,':openid'=>$member['openid']]);
        foreach ($devote as $key=>$item){
            if($item['expire'] < time()){
                pdo_update('ewei_shop_devote_record',['status'=>0],['id'=>$item['id']]);
            }
            $log = pdo_fetch('select * from '.tablename(ewei_shop_devote_log).'where devote_id = :devote_id and (user_id = :user_id or openid =:openid) and day =:day',[':devote_id'=>$item['id'],':user_id'=>$user_id,':openid'=>$member['openid'],':day'=>date('Y-m-d')]);
            if($log){
                continue;
            }else{
                pdo_insert('ewei_shop_devote_log',['devote_id'=>$item['id'],'openid'=>$member['openid'],'user_id'=>$user_id,'num'=>100,'day'=>date('Y-m-d'),'createtime'=>time()]);
            }
        }
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_devote_record').' where uniacid = "'.$uniacid.'" and (user_id = :user_id or openid = :openid) and status = 1',[':user_id'=>$user_id,':openid'=>$member['openid']]);
        $count = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_devote_record').'where uniacid = "'.$uniacid.'" and (user_id = :user_id or openid = :openid)',[':user_id'=>$user_id,':openid'=>$member['openid']]);
        $list = $this->getlist($total,$uniacid,$user_id);
        foreach ($list as $key=>&$item){
            $item['id'] = implode(',',$item['id']);
            $num = array_count_values($item['log']);
            $item['devote'] = ($item['count'] - $num[1]) * 100;
            if($item['is_open'] != 0){
                $item['is_open'] = $item['devote'] == 0 ? 2 : 1;
            }
            unset($item['log']);
        }
        return ['valid'=>$total,'no_valid'=>$count-$total,'list'=>$list];
    }

    /**
     * 计算贡献机的数量 有效  和  所有贡献机
     * @param $total
     * @param $uniacid
     * @param $user_id
     * @return array
     */
    public function getlist($total,$uniacid,$user_id)
    {
        $member = m('member')->getMember($user_id);
        $list = [];
        $size = 1;
        for ($i=1;$i<=$total;$i++){
            $key = $i%8 != 0 ? $i%8 : 8;
            $num = ceil(bcdiv($i,8,2));
            $list[$key]['image'] = "https://paokucoin.com/img/backgroup/s-gxserve.gif";
            $id = pdo_fetchcolumn('select id from '.tablename('ewei_shop_devote_record').'where (user_id =:user_id or openid = :openid) and uniacid = "'.$uniacid.'" and status = 1 LIMIT '.($i-1).','.$size,[':user_id'=>$user_id,':openid'=>$member['openid']]);
            //$list[$key]['log'][] = pdo_get('ewei_shop_devote_log',['user_id'=>$user_id,'uniacid'=>$uniacid,'devote_id'=>$id,'status'=>1,'day'=>date('Y-m-d',time())])?1:0;
            $log = pdo_fetch('select * from '.tablename('').'where uniacid = :uniacid and (user_id = :user_id or openid = :openid) and devote_id = :devote_id and status = 1 and day = :day',[':uniacid'=>$uniacid,':user_id'=>$user_id,':openid'=>$member['openid'],':devote_id'=>$id,':day'=>date('Y-m-d')]);
            $list[$key]['log'][] = $log ? 1 : 0;
            $list[$key]['id'][] = $id;
            $list[$key]['count'] = $num;
            $list[$key]['is_open'] = 1;
        }
        if($total < 8){
            for ($i = 0 ; $i < 8-$total; $i++){
                array_push($list,['image'=>"https://paokucoin.com/img/backgroup/n-gxserve@2x.png",'devote'=>0,'count'=>0,'is_open'=>0,'id'=>[]]);
            }
        }
        return $list;
    }

    /**
     * 是否绑定微信手机  贡献值
     * @param $user_id
     * @return mixed
     */
    public function devote($user_id)
    {
        $member = m('member')->getMember($user_id);
        $data["weixin"]=$member["weixin"];
        $data["mobile"]=$member["mobile"];
        $data["credit4"]=$member["credit4"];
        $data["bind"] = empty($member["weixin"])||empty($member["mobile"]) ? 0 : 1;
        //折扣宝提现金额
        $data["tixian"] = pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where (user_id = :user_id or openid = :openid) and credittype = :credittype and remark like :remark",array(":user_id"=>$user_id,":openid"=>$member['openid'],":credittype"=>"credit3",":remark"=>'折扣宝提现%'));
        $data["tixian"] = $data["tixian"] < 0 ? abs($data["tixian"]) : 0;
        return $data;
    }

    /**
     * @return array
     */
    public function get_list()
    {
       //$goods = pdo_fetchall('select * from '.tablename('ewei_shop_goods').'where status = 1 and deleted = 0 and id in (3,4,5,7)');
       $goods = pdo_fetchall('select id,thumb,sales,salesreal,agentlevel,content from '.tablename('ewei_shop_goods').'where status = 1 and deleted = 0 and id in (3,4,5,7)');
       foreach ($goods as $key=>$good){
           $goods[$key]['memberthumb'] = tomedia($good['thumb']);
           $goods[$key]['thumb'] = m('goods')->levelurlup($good['id']);
           $goods[$key]['salesreal'] = $goods[$key]['sales'] = $good['salesreal'] * 21 + rand(0,10);
           $agentlevel = pdo_fetch("select * from " . tablename("ewei_shop_commission_level") . " where id=:id limit 1", array( ":id" => $good['agentlevel']));
           $goods[$key]['available'] = $agentlevel['available'];
           $goods[$key]['content'] = strip_tags($good['content']);
       }
       return $goods;
    }

    public function get_list1($user_id,$id)
    {
        //获取用户信息
        $member = m('member')->getMember($user_id);
        //获得达人中心的所有图标
        $list = pdo_get("ewei_shop_small_set",array("id"=>$id));
        $list["icon"] = unserialize($list["icon"]);
        $list["backgroup"] = tomedia($list["backgroup"]);
        $list["banner"] = tomedia($list["banner"]);
        foreach ($list["icon"] as $k=>$v){
            $list["icon"][$k]["img"] = tomedia($v["img"]);
            $list["icon"][$k]["icon"] = tomedia($v["icon"]);
        }

        $level = pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
        //加速日期
        $accelerate_day = date("Y-m-d",strtotime("+".$level["accelerate_day"]." day",strtotime($member["agentlevel_time"])));
        $dd = m("member")->acceleration($user_id);
        //加速剩余天数
        $resault["surplus_day"] = $dd["day"];
        $resault["give_day"] = $dd["give_day"];
        $resault["accelerate_day"] = $dd["accelerate_day"];
        $resault["type"] = $dd["type"];

        //获取用户加速期间的卡路里
        if ($dd["type"] == 0){
            $starttime = strtotime($member["agentlevel_time"]);
            $endtime = strtotime($accelerate_day);
        }else{
            $starttime = strtotime($member["accelerate_start"]);
            $endtime = strtotime($member["accelerate_end"]);
        }
        $credit = pdo_fetchcolumn("select sum(num) from ".tablename('mc_credits_record')."where credittype = :credittype and (user_id = :user_id or openid = :openid) and createtime >= :starttime and createtime <= :endtime and (remark like :remark or remark like :cc)",array('credittype'=>"credit1",':user_id'=>$user_id,':openid'=>$member['openid'],':starttime'=>$starttime,':endtime'=>$endtime,':remark'=>'%'.'步数兑换',':cc'=>'好友助力'));
        if (empty($credit)){
            $resault["credit"]=0;
        }else{
            $resault["credit"]=$credit;
        }
        return ['icon'=>$list,'accelerate'=>$resault];
    }

    /**
     * 收款码的收款记录
     * @param $user_id
     * @return array
     */
    public function rebate_record($user_id)
    {
        //查该用户是不是有商家
        $merch_user = pdo_get('ewei_shop_merch_user',['member_id'=>$user_id]);
        //如果该用户有商家  就是商家id  没有 就是用户id加own
        $mch_id = empty($merch_user) ? $user_id."own" : $merch_user['id'];
        //计算这个店铺成交的第一个订单的日期
        $create = pdo_getcolumn('ewei_shop_order',['status'=>3,'merchid'=>$mch_id],'createtime');
        $start_time = !empty($create) ? $create : time();
        //计算时间
        $day = round((time()-$start_time)/86400);
        $list = [];
        $total = 0;
        $total_money = 0;
        for ($i = 0;$i<=$day;$i++){
            //今天  昨天 前天的每天开始时间
            $start = strtotime(date('Y-m-d',strtotime('-'.$i.'day')));
            //每天的时间键值
            $time = date('Y年m月d日',$start);
            $end = $start + 86400;
            if(is_numeric($mch_id)){
                //商家收款记录
                $list[$time]['list'] = pdo_fetchall('select id,openid,price,createtime,cate from '.tablename('ewei_shop_merch_log').' where createtime between "'.$start.'" and "'.$end.'" and status = 1 and merchid = "'.$mch_id.'"  and price > 0 and cate = "'.$_GPC['cate'].'"');
            }else{
                //个人的收款记录  rechargetype  交易类型  就是个人的id拼接own
                $list[$time]['list'] = pdo_fetchall('select id,openid,money as price,createtime from '.tablename('ewei_shop_member_log').' where createtime between "'.$start.'" and "'.$end.'" and status = 1 and rechargetype = "'.$mch_id.'"  and money > 0');
            }
            //计算每天的收款笔数
            $list[$time]['count'] = count($list[$time]['list']);
            //如果 某天没有收款 去掉他的收款时间的键
            if($list[$time]['count'] == 0){
                unset($list[$time]);
                continue;
            }
            // 把每天的收款钱数  单独组成个一位数组  请求和  保留两位小数
            $money = array_column($list[$time]['list'],'price');
            $list[$time]['total'] = round(array_sum($money),2);
            //换时间格式  和  查出付款人的昵称
            foreach ($list[$time]['list'] as $key=>$item){
                $list[$time]['list'][$key]['createtime'] = date('H:i:s',$item['createtime']);
                $list[$time]['list'][$key]['nickname'] = pdo_getcolumn('ewei_shop_member',['openid'=>$item['openid']],'nickname');
            }
            //计算总收款笔数 和 总钱
            $total+=$list[$time]['count'];
            $total_money += $list[$time]['total'];
        }
        return ['list'=>$list,'total'=>$total,'total_money'=>$total_money];
    }

    public function rebate_set($user_id,$money,$fee,$id,$cate = 2)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        //查该用户是不是有商家
        $merch_user = pdo_get('ewei_shop_merch_user',['member_id'=>$user_id]);
        $data = [
            'uniacid'=>$uniacid,
            'money'=>$money,
            'deduct'=>$fee,
            'cate'=>$cate,
            'openid'=>$member['openid'],
            'user_id'=>$user_id,
        ];
        //如果是商家
        $data['merchid'] = $merch_user ? $merch_user['id'] : 0;
        //有$id 修改 没有添加
        if($id){
            //判断$money金额的满减条件是否存在
            $res = pdo_fetch('select id from '.tablename('ewei_shop_deduct_setting').' where (openid = :openid or user_id = :user_id) and money = "'.$money.'" and cate = "'.$cate.'" and id != "'.$id.'"',[':openid'=>$member['openid'],':user_id'=>$user_id]);
            if($res){
                return ['status'=>1,'msg'=>$money.'的满减条件已存在，请前往修改或者更换满减条件'];
            }
            pdo_update('ewei_shop_deduct_setting',$data,['id'=>$id]);
            $msg = "修改成功";
        }else{
            //判断$money金额的满减条件是否存在
            $res = pdo_fetch('select id from '.tablename('ewei_shop_deduct_setting').' where (openid=:openid or user_id = :user_id) and money=:money and cate=:cate',array(':openid'=>$member['openid'],':user_id'=>$user_id,':money'=>$money,':cate'=>$cate));
            if($res){
                return ['status'=>1,'msg'=>$money.'的满减条件已存在，请前往修改或者更换满减条件'];
            }
            pdo_insert('ewei_shop_deduct_setting',$data);
            $msg = "添加成功";
        }
        return ['status'=>0,'msg'=>$msg];
    }

    /**
     * 获取折扣设置列表
     * @param $user_id
     * @param int $page
     * @param int $cate
     * @return array
     */
    public function rebate_get($user_id,$page = 1,$cate = 2)
    {
        $pageSize = 10;
        $spage = ($page - 1) * $pageSize;
        //查该用户是不是有商家
        $merch_user = pdo_get('ewei_shop_merch_user',['member_id'=>$user_id]);
        //如果该用户有商家  就是商家id  没有 就是用户id加own
        $mch_id = empty($merch_user) ? $user_id."own" : $merch_user['id'];
        //如果是数字  就查商家信息  不是 就查openid
        if(is_numeric($mch_id)){
            $total = pdo_count('ewei_shop_deduct_setting',['merchid'=>$mch_id,'cate'=>$cate]);
            $list = pdo_fetchall('select id,money,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where merchid = :merchid and cate = :cate order by money asc LIMIT '.$spage.','.$pageSize,array(':merchid'=>$mch_id,':cate'=>$cate));
        }elseif (strpos($mch_id,"own")){
            $member = pdo_get('ewei_shop_member',['id'=>intval($mch_id)]);
            $total = pdo_count('ewei_shop_deduct_setting',['openid'=>$member['openid'],'cate'=>$cate]);
            $list = pdo_fetchall('select id,money,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where (user_id = :user_id or openid = :openid) and cate = :cate order by money asc LIMIT '.$spage.','.$pageSize,array(':openid'=>$member['openid'],':user_id'=>$user_id,':cate'=>$cate));
        }
        return ['list'=>$list,'pageSize'=>$pageSize,'total'=>$total,'page'=>$page];
    }

    /**
     * 个人资产提现
     * @param $user_id
     * @param $money
     * @return array
     */
    public function rebate_owndraw($user_id,$money)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $credit5 = $member['credit5'];
        //bccomp  比较 两个精确的小数的大小   == -1  是前者小于后者
        if(bccomp($credit5,$money,2) == -1){
            return ['status'=>1,'msg'=>"资金余额不足"];
        }
        //个人资产提现 logno的  开头是OW  own_withdraw
        $order_sn = "OW".date('YmdHis').random(12);
        $data = [
            'uniacid'=>$uniacid,
            'openid'=>$member['openid'],
            'user_id'=>$user_id,
            'type'=>1,
            'logno'=>$order_sn,
            'title'=>'个人资金提现',
            'createtime'=>time(),
            'status'=>0,
            'money'=>$money,
            'realmoney'=>bcsub($money,bcmul($money,0.03,2),2),
            'deductionmoney'=>bcmul($money,0.03,2),
            'draw_type'=>3,
        ];
        pdo_begin();
        try{
            pdo_insert('ewei_shop_member_log',$data);
            pdo_update('ewei_shop_member',['credit5'=>bcsub($credit5,$money,2)],['id'=>$user_id,'uniacid'=>$uniacid]);
            pdo_commit();
        }catch(Exception $exception){
            pdo_rollback();
        }
        return ['status'=>0,'msg'=>'提现成功'];
    }

    /**
     * 商家提现记录
     * @param $user_id
     * @param $applytype
     * @return array
     */
    public function rebate_merchdraw($user_id,$applytype)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //查该用户是不是有商家
        $merch_user = pdo_get('ewei_shop_merch_user',['member_id'=>$user_id]);
        if(!$merch_user){
            return ['status'=>1,'msg'=>'商户信息错误'];
        }
        $item = p('merch')->getMerchPrice($merch_user['id'],1,1);
        $list = p('merch')->getMerchPriceList($merch_user['id'],0,0,1);
        $order_num = count($list);
        $cansettle = true;
        if ($item['realpricerate'] <= 0) {
            $cansettle = false;
        }
        if (($item['realprice'] <= 0)  || empty($list))
        {
            return array('status'=>1,'msg'=> '您没有可提现的金额');
        }
        if($item['realpricerate'] < 10){
            return ['status'=>1,'msg'=>'提现金额不足'];
        }
        $insert = array();
        $insert['uniacid'] = $uniacid;
        $insert['merchid'] = $merch_user['id'];
        $insert['applyno'] = m('common')->createNO('merch_bill', 'applyno', 'MO');
        $insert['orderids'] = iserializer($item['orderids']);
        $insert['ordernum'] = $order_num;
        $insert['price'] = $item['price'];
        $insert['realprice'] = $item['realprice'];
        $insert['realpricerate'] = $item['realpricerate'];
        $insert['finalprice'] = $item['finalprice'];
        $insert['orderprice'] = $item['orderprice'];
        $insert['payrateprice'] = round(($item['realpricerate'] * $item['payrate']) / 100, 2);
        $insert['payrate'] = $item['payrate'];
        $insert['applytime'] = time();
        $insert['status'] = 1;
        $insert['applytype'] = $applytype;
        $insert['type'] = 1;
        pdo_insert('ewei_shop_merch_bill', $insert);
        $billid = pdo_insertid();
        foreach ($list as $k => $v )
        {
            $orderid = $v['id'];
            $insert_data = array();
            $insert_data['uniacid'] = $uniacid;
            $insert_data['billid'] = $billid;
            $insert_data['orderid'] = $orderid;
            $insert_data['ordermoney'] = $v['realprice'];
            pdo_insert('ewei_shop_merch_billo', $insert_data);
            $change_order_data = array();
            $change_order_data['merchapply'] = 1;
            pdo_update('ewei_shop_order', $change_order_data, array('id' => $orderid));
        }
        p('merch')->sendMessage(array('merchname' => $merch_user['merchname'], 'money' => $insert['realprice'], 'realname' => $merch_user['realname'], 'mobile' => $merch_user['mobile'], 'applytime' => time()), 'merch_apply_money');
        return ['status'=>0,'msg'=>"提现申请成功"];
    }

    /**
     * 个人资产提现记录
     * @param $user_id
     * @param $page
     * @return array
     */
    public function rebate_owndraw_log($user_id,$page)
    {
        $member = m('member')->getMember($user_id);
        $pageSize = 20;
        $psize = ($page - 1)*$pageSize;
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_member_log')." where (openid = :openid or user_id = :user_id) and title = '个人资金提现'",[':openid'=>$member['openid'],':user_id'=>$user_id]);
        //查询提现记录  FROM_UNIXTIIME()    sql语句中 时间戳转换成时间格式
        $list = pdo_fetchall('select id,title,money,FROM_UNIXTIME(createtime) as createtime,status,refuse_reason from '.tablename('ewei_shop_member_log').' where (openid = :openid or user_id = :user_id) and title = "个人资金提现" order by id desc LIMIT '.$psize.','.$pageSize,[':openid'=>$member['openid'],':user_id'=>$user_id]);
        return ['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize];
    }

    /**
     * 商家资产提现记录
     * @param $user_id
     * @param $page
     * @return array
     */
    public function rebate_merchdraw_log($user_id,$page)
    {
        //查该用户是不是有商家
        $merch_user = pdo_get('ewei_shop_merch_user',['member_id'=>$user_id]);
        if(!$merch_user){
            return ['status'=>1,'msg'=>'商户信息错误'];
        }
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_merch_bill').'where uniacid = :uniacid and merchid = :merchid',[':uniacid'=>$uniacid,':merchid'=>$merch_user['id']]);
        $list = pdo_getall('ewei_shop_merch_bill','merchid="'.$merchid.'" and uniacid="'.$uniacid.'" and type = 1 order by id desc LIMIT '.$pindex.','.$pageSize,['id','realprice','realpricerate','status','applytime']);
        foreach ($list as $key=>$item){
            $list[$key]['applytime'] = date('Y-m-d H:i:s',$item['applytime']);
            $list[$key]['title'] = "资金提现";
        }
        return ['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize];
    }
}



?>
