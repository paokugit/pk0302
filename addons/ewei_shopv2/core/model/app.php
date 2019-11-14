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
        $token = base64_encode(implode(',',[$user_id,$salt]));
        return str_replace('=','',$token);
    }

    /**
     * APP鉴权校验
     * @param $token
     * @return int
     */
    public function getLoginToken($token)
    {
        ;
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
	public function get_icon($user_id,$type = 1)
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
        $user_id = $user_id ? $user_id : 150;
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        //获得当前用户的店铺
        $memberMerchInfo = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where member_id = :member_id Limit 1', array(':member_id' => $member['id']));
        $data = array();
        //如果当前用户有上级  查他的上级的店铺
        if($member['agentid'] > 0){
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
    public function near($user_id,$lat,$lng,$range = 1000,$cateid = 0,$sorttype = "desc",$keyword = "")
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
    public function seckill($type = 1)
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
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
        }
        return $list;
    }

    /**
     * 消息弹窗
     * @param $user_id
     * @param $no_id  1是年卡
     * @return int
     */
    public function notice($user_id,$no_id = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $notice = pdo_fetch('select * from '.tablename('notice').' where openid = :openid or user_id = :user_id and uniacid = :uniacid and status = 1 and no_id = :no_id',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid,':no_id'=>$no_id]);
        if($notice){
            return 0;
        }else{
            pdo_insert('notice',['uniacid'=>$uniacid,'openid'=>$member['openid'],'user_id'=>$member['id'],'status'=>1,'no_id'=>$no_id,'createtime'=>time()]);
            return 1;
        }
    }


    /**
     * 跑库精选
     */
    public function choice()
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_choice').'where uniacid = :uniacid and status = 1 order by displayorder desc,id desc limit 6',[':uniacid'=>$uniacid]);
        return $list;
    }

    /**
     * 领取折扣宝 或者 卡路里
     * @param $user_id
     * @param int $step_id
     * @return array
     */
    public function getcredit($user_id,$step_id = 0)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        if(empty($step_id)) return ['status'=>1,'msg'=>'领取失败'];
        //获取用户信息
        $member = m('member')->getMember($user_id);
        //获得用户可以兑换的卡路里
        $exchange_step = m("member")->exchange_step($user_id);
        //获取要领取的卡路里  对应的数据
        $step = pdo_fetch('select * from '.tablename('ewei_shop_member_getstep').'where id = :id and uniacid = :uniacid',[':id'=>$step_id,'uniacid'=>$uniacid]);
        if($step['status'] == 1){
            return ['status'=>1,'msg'=>'领取失败'];
        }
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
        return ['status'=>0,'msg'=>'领取成功'];
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
        $member = m('member')->getMember($user_id);
        //如果是贡献机用户
        $devote = pdo_fetchall('select * from '.tablename('ewei_shop_devote_record').' where uniacid = :uniacid and (user_id = :user_id or openid = :openid) and status = 1',[':uniacid'=>$uniacid,':user_id'=>$user_id,':openid'=>$member['openid']]);
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
        $list = m('payment')->getlist($total,$uniacid,$user_id);
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
     * 是否绑定微信手机  贡献值
     * @param $user_id
     * @return mixed
     */
    public function devote($user_id)
    {
        $member = m('member')->getMember($user_id);
        $data["weixin"]=$member["weixin"] ? $member['weixin'] : "";
        $data["mobile"]=$member["mobile"] ? $member["mobile"] : "";
        $data["credit4"]=$member["credit4"] ? $member['credit4'] : 0;
        $data["bind"] = empty($member["weixin"])||empty($member["mobile"]) ? 0 : 1;
        //折扣宝提现金额
        $data["tixian"] = pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where (user_id = :user_id or openid = :openid) and credittype = :credittype and remark like :remark",array(":user_id"=>$user_id,":openid"=>$member['openid'],":credittype"=>"credit3",":remark"=>'折扣宝提现%'));
        $data["tixian"] = $data["tixian"] < 0 ? abs($data["tixian"]) : 0;
        return $data;
    }

    /**
     * 会员等级  或者没有token  或者等级0
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

    /**
     * 达人中心
     * @param $user_id
     * @param $id
     * @return array
     */
    public function get_list1($user_id,$id = 2)
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
        return ['icon'=>$list,'accelerate'=>$resault,'member'=>['avatar'=>$member['avatar'],'nickname'=>$member['nickname'],'levelname'=>$member['levelname']]];
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
                $list[$time]['list'] = pdo_fetchall('select id,openid,price,createtime,cate from '.tablename('ewei_shop_merch_log').' where createtime between "'.$start.'" and "'.$end.'" and status = 1 and merchid = "'.$mch_id.'"  and price > 0 and cate = 2');
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
        return ['list'=>$list,'total'=>$total,'total_money'=>number_format($total_money,2)];
    }

    /**
     * @param $user_id
     * @param $money
     * @param $fee
     * @param $id
     * @param int $cate
     * @return array
     */
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
            $list = pdo_fetchall('select id,money,user_id,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where merchid = :merchid and cate = :cate order by money asc LIMIT '.$spage.','.$pageSize,array(':merchid'=>$mch_id,':cate'=>$cate));
        }elseif (strpos($mch_id,"own")){
            $member = pdo_get('ewei_shop_member',['id'=>intval($mch_id)]);
            $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_deduct_setting').'where (openid = :openid or user_id = :user_id) and cate=:cate',[':openid' => $member['openid'],':user_id'=>$member['id'],':cate'=>$cate]);
            $list = pdo_fetchall('select id,money,user_id,merchid,deduct,cate,openid from '.tablename('ewei_shop_deduct_setting').'where (user_id = :user_id or openid = :openid) and cate = :cate order by money asc LIMIT '.$spage.','.$pageSize,array(':openid'=>$member['openid'],':user_id'=>$user_id,':cate'=>$cate));
        }
        return ['list'=>$list,'pageSize'=>$pageSize,'total'=>$total,'page'=>$page];
    }

    /**
     * 输入金额  获得可用折扣
     * @param $user_id
     * @param $merchid
     * @param int $money
     * @param int $cate
     * @return array
     */
    public function rebate_deduct($user_id,$merchid,$money = 0,$cate = 2)
    {
        //查用户信息
        $member = m('member')->getMember($user_id);
        //获取收款人的信息   如果是整形的话  就是商家  不是的话 就取出他的openid   user_id 直接用intval()
        $mch_id = is_numeric($merchid) ? $merchid : pdo_getcolumn('ewei_shop_member',['id'=>intval($merchid)],'openid');
        //查询可用的最大优惠
        if(is_numeric($mch_id)){
            $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$money.'" and cate = "'.$cate.'" and deduct <="'.$member['credit3'].'" and merchid = "'.$mch_id.'" order by money desc');
        }else{
            $list = pdo_fetch('select * from '.tablename('ewei_shop_deduct_setting').' where money<="'.$money.'" and cate = "'.$cate.'" and deduct <="'.$member['credit3'].'" and (openid = :merchid or user_id = :user_id) order by money desc',[':merchid'=>$mch_id,':user_id'=>intval($merchid)]);
        }
        //查下这个商家这个类型的  所有折扣信息
        if(is_numeric($mch_id)){
            $array = pdo_fetchall('select * from '.tablename('ewei_shop_deduct_setting').' where cate = "'.$cate.'" and merchid = "'.$mch_id.'" order by money asc');
        }else{
            $array = pdo_fetchall('select * from '.tablename('ewei_shop_deduct_setting').' where cate = "'.$cate.'" and (openid = :merchid or user_id = :user_id) order by money asc',[':merchid'=>$mch_id,':user_id'=>intval($merchid)]);
        }
        //如果商家折扣信息数量小于等于0  等于说没有折扣信息
        if(count($array) <= 0){
            return ['status'=>1,'msg'=>'暂无折扣信息'];
        }
        //到这个时候 应该是  折扣信息数大于0  且 输入的金额大于最小金额
        if(!$list){
            return ['status'=>1,'msg'=>"暂无符合的折扣优惠"];
        }
        return ['status'=>0,'msg'=>$list];
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
        $data = ['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize];
        return ['status'=>0,'msg'=>$data];
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
        $data = ['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize];
        return ['status'=>0,'msg'=>$data];
    }

    /**
     * 折扣宝收支明细
     * @param $user_id
     * @param int $type
     * @param int $page
     * @return array
     */
    public function rebate_log($user_id,$type = 1,$page = 1)
    {
        $member = m('member')->getMember($user_id);
        $pageSize = 8;
        $psize = ($page-1)*$pageSize;
        $credit3 = $member['credit3'];
        $fields = "id,num,createtime,remark,openid";
        if($type == 1){
            $condition = ' and num > 0';
        }elseif ($type == 2){
            $condition = ' and num < 0';
        }
        $list = pdo_fetchall('select '.$fields.' from '.tablename('mc_credits_record').' where credittype ="credit3" and (openid = :openid or user_id = :user_id)'.$condition  .' order by createtime desc LIMIT '.$psize .','.$pageSize,[':openid'=>$member['openid'],':user_id'=>$user_id]);
        $total = pdo_fetchcolumn('select count(*) from '.tablename('mc_credits_record').' where credittype = "credit3" and (openid = :openid or user_id = :user_id)'.$condition,[':openid'=>$member['openid'],':user_id'=>$user_id]);
        foreach ($list as $key=>$item){
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            if(mb_substr($item['remark'],0,2) == "跑库"){
                if($item['num'] < 0){
                    $list[$key]['remark'] = "商城订单支付";
                }else{
                    $list[$key]['remark'] = "商城订单返还";
                }
            }
        }
        return ['credit3'=>$credit3,'list'=>$list,'page'=>$page,'pageSize'=>$pageSize,'total'=>$total,'type'=>$type];
    }

    /**
     * 卡路里转折扣宝
     * @param $user_id
     * @param $money
     * @return array
     */
    public function rebate_exchange($user_id,$money)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //查用户的卡路里和折扣宝的信息
        $member = m('member')->getMember($user_id);
        //判断要转换的卡路里和用户的卡路里的多少
        if($money == 0){
            return ['status'=>1,'msg'=>'充值金额不能为0'];
        }elseif($money > $member['credit1']){
            return ['status'=>1,'msg'=>'您的卡路里不足'];
        }else {
            //计算转换后的用户的卡路里和折扣宝的余额
            $credit1 = $member['credit1'] - $money;
            $credit3 = $member['credit3'] + $money * 2;
            //更新用户的卡路里和折扣宝的余额
            pdo_update('ewei_shop_member', ['credit1' => $credit1, 'credit3' => $credit3], ['id' => $user_id]);
            $data = [
                'openid' => $member['openid'],
                'user_id'=>$user_id,
                'uniacid' => $uniacid,
                'credittype' => 'credit1',
                'num' => -$money,
                'createtime' => time(),
                'remark' => "卡路里转换折扣宝",
                'module' => "ewei_shopv2",
            ];
            $add = [
                'openid' => $member['openid'],
                'user_id'=>$user_id,
                'uniacid' => $_W['uniacid'],
                'credittype' => 'credit3',
                'num' => $money * 2,
                'createtime' => time(),
                'remark' => "卡路里转换折扣宝",
                'module' => "ewei_shopv2",
            ];
            pdo_insert('mc_credits_record', $data);
            pdo_insert('mc_credits_record', $add);
            pdo_insert('ewei_shop_member_credit_record', $data);
            pdo_insert('ewei_shop_member_credit_record', $add);
            return ['status'=>0,'msg'=>'转换成功'];
        }
    }

    /**
     * 折扣宝提现
     * @param $user_id
     * @param $money
     * @return array
     */
    public function rebate_withdraw($user_id,$money)
    {
        $member = m('member')->getMember($user_id);
         if ($money < 1){
            return ['status'=>1,'msg'=>"提现金额不可小于1元"];
        }
        if ($member["credit3"] < $money || $member["credit4"] < $money){
            return ['status'=>1,'msg'=>"提现余额或贡献值不足"];
        }
        //添加提现记录
        $log["uniacid"]=1;
        $log["openid"]=$member['openid'];
        $log["user_id"]=$member['id'];
        $log["type"]=1;
        $log["logno"]="CA".date("YmdHis").rand(100000,999999);
        $log["title"]="折扣宝提现";
        $log["createtime"]=time();
        $log["status"]=0;
        $log["money"]=$money;
        $log["realmoney"]=$money;
        $log["deductionmoney"]=bcmul($money,0.03,2);
        $log["realmoney"]=bcsub($money,$log['deductionmoney'],2);
        $log["remark"]="折扣宝提现";
        $log['draw_type'] = 2;
        m('member')->setCredit($member['openid'], 'credit3', -$money, "折扣宝提现:提现编号".$log["logno"]);
        m('member')->setCredit($user_id, 'credit4', -$money, "折扣宝提现扣除:提现编号".$log["logno"]);
        return ['staus'=>0,'msg'=>"成功"];
    }

    /**
     * 折扣宝转账
     * @param $user_id
     * @param $money
     * @param $mobile
     * @return array
     */
    public function rebate_change($user_id,$money,$mobile)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //收款人信息
        $to = pdo_get('ewei_shop_member',['mobile'=>$mobile,'uniacid'=>$uniacid]);
        //转账人信息
        $member = m('member')->getMember($user_id);
        if(bccomp($member['credit3'],$money,2) == -1){
            return ['status'=>1,'msg'=>"用户余额不足"];
        }
        if(!$to){
            return ['status'=>1,'msg'=>"收款人不存在"];
        }
        if($to['openid'] == $member['openid']){
            return ['status'=>1,'msg'=>'转账者和收款人相同'];
        }
        //更新转账者折扣宝余额   减去  并写入日志
        pdo_update('ewei_shop_member',['credit3'=>bcsub($member['credit3'],$money,2)],['openid'=>$member['openid'],'uniacid'=>$uniacid]);
        m('payment')->addlog($member,$to,$money,1);
        //更新收款者  折扣宝余额   加上  并写入日志
        pdo_update('ewei_shop_member',['credit3'=>bcadd($to['credit3'],$money,2)],['openid'=>$to['openid'],'uniacid'=>$uniacid]);
        m('payment')->addlog($to,$member,$money,2);
        return ['status'=>0,'msg'=>'转账成功'];
    }

    /**
     * RV额度限制
     * @param $user_id
     * @return array
     */
    public function rebate_limit($user_id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //查找用户信息
        $member = m('member')->getMember($user_id);
        //计算用户的额度
        $limit = m('payment')->checklimit($member['openid'],$member['agentlevel']);
        //计算用户已经消费的额度
        $sale = pdo_fetchall('select * from '.tablename('mc_credits_record').' where (openid = :openid or user_id = :user_id) and remark = "RV钱包充值" and createtime > 1570776300',[':openid'=>$member['openid'],':user_id'=>$user_id]);
        $sale_sum = abs(array_sum(array_column($sale,'num')));
        $remian = bcsub($limit,$sale_sum,2) >= 10000 ? bcsub($limit,$sale_sum,2)/10000 ."万" : bcsub($limit,$sale_sum,2);
        $list = pdo_getall('ewei_shop_member_limit',['uniacid' => $uniacid,'status'=>1],['id','money','limit']);
        foreach ($list as $key=>$item){
            $list[$key]['limit'] = $item['limit'] >= 10000 ? $item['limit'] / 10000 ."万" : $item['limit'];
        }
        return ['list'=>$list,'remain'=>$remian];
    }

    /**
     * 跑库年卡
     * @param $user_id
     * @return array
     */
    public function index_level($user_id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $user['expire'] = date('Y-m-d H:i:s',$member['expire_time']);
        $user['nickname'] = $member['nickname'];
        $user['realname'] = $member['realname'];
        $user['is_open'] = $member['is_open'];
        //待领取的优惠券  两个
        $coupon = pdo_fetchall('select cd.id,cd.used,co.deduct,co.enough,co.couponname from '.tablename('ewei_shop_coupon_data').'cd join '.tablename('ewei_shop_coupon').'co on co.id=cd.couponid'.' where (cd.openid = :openid or cd.user_id = :user_id) and co.timeend > "'.time().'" order by id desc LIMIT 0,2',[':openid'=>$member['openid'],':user_id'=>$member['id']]);
        //特权产品列表
        $goods = pdo_getall('ewei_shop_goods','status = 1 and is_right = 1 and total > 0 order by id desc LIMIT 0,8',['id','title','thumb','total','productprice','marketprice','bargain']);
        foreach ($goods as $key=>$item){
            $goods[$key]['thumb'] = tomedia($item['thumb']);
        }
        //本月的权益礼包
        $month = date('Ym',time());
        $level = pdo_fetch(' select id,openid,level_name,level_id,goods_id,status,month,FROM_UNIXTIME(updatetime) as updatetime,user_id from '.tablename('ewei_shop_level_record').'where (openid = :openid or user_id = :user_id) and uniacid = :uniacid and month = :month',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid,':month'=>$month]);
        $good = pdo_get('ewei_shop_goods',['id'=>$level['goods_id'],'uniacid'=>$uniacid],['thumb','productprice']);
        $level = array_merge($level,['thumb'=>tomedia($good['thumb']),'price'=>$good['productprice']]);
        //查询我的第一条记录
        $log = pdo_fetch('select * from '.tablename('ewei_shop_level_record').' where uniacid = "'.$uniacid.'" and level_id = "'.$level['level_id'].'" and (openid = :openid or user_id = :user_id) order by month asc',[':openid'=>$openid,':user_id'=>$member['id']]);
        //如果今天的年月份  大于记录中的 则更新他为失效   或者  月份相同  日期大于20  并把更新时间改成当月的21号为失效时间   并且状态为未领取
        $level['month'] = $level['month'] == $log['month'] ? date("Y年m月d日",strtotime($month."01"."+1 month -1 day")) : date("Y年m月20日",strtotime($month."01"));
        $record = pdo_fetchall('select * from '.tablename('ewei_shop_level_record').'where (openid = :openid or user_id = :user_id) and uniacid = "'.$uniacid.'" order by id desc',[':openid'=>$openid,':user_id'=>$user_id]);
        foreach ($record as $key => $item){
            //如果状态 == 0
            if($item['status'] == 0){
                //如果是第一个月  不更改状态  并继续
                if($item['month'] == $log['month']){
                    continue;
                    //break;
                }
                //当前年月 大于循环的年月  则改变状态为失效
                if(date('Ym',time()) > $item['month'] || (date('Ym',time()) == $item['month'] && date('d',time()) > 21)){
                    pdo_update('ewei_shop_level_record',['status'=>2,'updatetime'=>strtotime($item['month']."21")],['uniacid'=>$uniacid,'id'=>$item['id']]);
                }
            }
        }
        return ['member'=>$user,'coupon'=>$coupon,'goods'=>$goods,'level'=>$level];
    }

    /**
     * 礼包信息
     * @param $user_id
     * @return array
     */
    public function index_gift($user_id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //该用户的用户
        $member = m('member')->getMember($user_id);
        //本周开始结束时间
        $week = m('util')->week(time());
        //礼包总和
        $gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$uniacid.'"');
        //礼包对应的商品信息
        $goods = m('game')->gift($gifts);
        //该用户对应的礼包
        $gift = m('game')->get_gift($gifts,$user_id);
        //已助力的人数
        $help_count = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'"');
        //邀请新人记录
        $new = pdo_fetchall('select id,nickname,avatar,openid from '.tablename('ewei_shop_member').' where agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'" order by createtime desc LIMIT 10');
        $new_count = count($new);
        //如果新邀请的人数  不达需要邀请的人数  追加空数据
        if($new_count < $gift['member']){
            $new = m('game')->addnew($new,$gift['member'],$new_count,'https://paokucoin.com/img/backgroup/touxiang02.png');
        }
        $agentlevel = $member['agentlevel'] == 0 ? "普通会员" : pdo_getcolumn('ewei_shop_commission_level',['id'=>$member['agentlevel'],'uniacid'=>$uniacid],'levelname');
        //累计助力人数
        $all = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime > "'.$gift['starttime'].'"');
        //目标人数
        $target = m('game')->count($member['agentlevel'],$gifts);
        if($member['agentlevel'] == 5){
            $get_all = 3;
        }elseif ($member['agentlevel'] == 2){
            $get_all = 2;
        }else{
            $get_all = 1;
        }
        //这周领取礼包数
        $get = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_gift_log').'where (openid = :openid or user_id = :user_id) and status = 2 and createtime between "'.$week['start'].'" and "'.$week['end'].'"',[':openid'=>$member['openid'],':user_id'=>$member['id']]);
        //分享信息
        $share = ['title'=>'免费领礼包啦，商品免费领到手','thumb'=>"https://www.paokucoin.com/img/backgroup/free.jpg"];
        //礼包领取快报
        $notice = pdo_fetchall('select m.nickname,m.avatar,l.gift_id from '.tablename('ewei_shop_gift_log')."l join ".tablename('ewei_shop_member').'m on l.openid = m.openid or l.user_id = m.id'.' where l.uniacid = "'.$uniacid.'" and l.status = 2 order by l.id desc LIMIT 66');
        foreach ($notice as $key=>$item){
            $notice[$key]['gift'] = m('game')->check($item['gift_id']);
        }
        return ['notice'=>$notice,'share'=>$share,'goods'=>$goods,'all'=>$all,'desc'=>$gift['desc'],'help_count'=>$help_count,'new_member'=>$new,'remain'=>bcsub($target,$help_count) > 0 ? bcsub($target,$help_count) :0,'agent_level'=>$member['agentlevel'],'agentlevel'=>$agentlevel,'avatar'=>$member['avatar'],'gift'=>$gift['title'],'start'=>date('Y-m-d',$gift['starttime']),'end'=>date('Y-m-d',$gift['endtime']),'get_all'=>$get_all,'gets'=>$get,'week_start'=>date('m.d',$week['start']),'week_end'=>date('m.d',strtotime("-1s",$week['end']))];
    }

    /**
     * 领取年卡礼包
     * @param $user_id
     * @param $level_id  5是默认  是年卡
     * @param $address_id
     * @param $money
     * @param $record_id
     * @param $good_id
     * @return array
     */
    public function index_getLevel($user_id,$level_id = 5,$address_id = "",$money = 0,$record_id = 0,$good_id = 0)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //查找用户信息
        $member = m('member')->getMember($user_id);
        //判断支付金额  是否正确
        $price = m('game')->change_address($address_id,$member['openid'],$uniacid);
        if($price['price'] != $money){
            return ['status'=>1,'msg'=>"支付金额不正确"];
        }
        //把礼包的信息查出来  然后 把他的商品转译出来  判断 要领取的商品在不在其中
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $goods_id = unserialize($level['goods_id']);
        if(!in_array($good_id,$goods_id)){
            return ['status'=>1,'msg'=>"领取商品有误"];
        }
        //把年里礼包的商品给查出来
        $goods = pdo_get('ewei_shop_goods','uniacid="'.$uniacid.'" and id="'.$good_id.'" and status = 1 and total > 0',['id','thumb','title','marketprice']);
        //查询该记录的信息
        $record = pdo_fetch('select * from '.tablename('ewei_shop_level_record').' where uniacid = :uniacid and level_id = :level_id and id = :record_id and (openid = :openid or user_id = :user_id)',[':uniacid'=>$uniacid,':level_id'=>$level_id,':record_id'=>$record_id,':openid'=>$member['openid'],':user_id'=>$member['id']]);
        //判断这个月的记录状态
        if($record['status'] > 0){
            return ['status'=>1,'msg'=>$record['month']."权利礼包已领取或过期"];
        }
        //查询领取记录里面的已领过的状态
        $log = pdo_fetchall('select * from '.tablename('ewei_shop_level_record').'where uniacid = :uniacid and (openid = :openid or user_id = :user_id) and level_id = :level_id and status > 0',[':openid'=>$member['openid'],':user_id'=>$member['id'],':level_id'=>$level_id,':uniacid'=>$uniacid]);
        if(count($log) > 0 && (date('Ymd',time()) < $record['month']."10" || date('Ymd',time()) > $record['month']."21")){
            return ['status'=>1,'msg'=>$record['month']."权益礼包不在领取日期"];
        }
        //生成订单号
        $order_sn = "LQ".$level_id.date('YmdHis').random(12);
        //添加订单
        $order_id = m('game')->addorder($member['openid'],$order_sn,$money,$address_id,"领取年卡".$record["month"]."权益",$goods);
        //如果是第一次支付   金额为零 不用唤醒支付  直接改变状态   然后 架订单的时候 也判断了  让status=1
        if($money == 0){
            pdo_update('ewei_shop_level_record',['goods_id'=>$good_id,'status'=>1,'updatetime'=>time()],['id'=>$record_id]);
            return ['status'=>0,'msg'=>"领取成功"];
        }
    }

    /**
     * 地址列表  和  切换地址
     * @param $user_id
     * @param int $address_id
     * @param int $type
     * @return array
     */
    public function index_address($user_id,$address_id = 0,$type = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $address = pdo_fetchall('select * from '.tablename('ewei_shop_member_address').'where uniacid = :uniacid and (openid = :openid or user_id = :user_id) and deleted = 0 order by isdefault desc,id desc',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        if(!$address){
            return ['status'=>1,'msg'=>"暂无地址，请去添加地址"];
        }
        if($type == 1){
            $data = m('game')->change_address($address[0]['id'],$member['openid'],$uniacid);
            $data = array_merge($address,$data);
        }else{
            $data = m('game')->change_address($address_id,$member['openid'],$uniacid);
        }
        return ['status'=>0,'msg'=>$data];
    }

    /**
     * 礼包商品
     * @param $user_id
     * @param $level_id  5是默认  是年卡
     * @return array
     */
    public function index_level_goods($user_id,$level_id = 5)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $goods_id = unserialize($level['goods_id']);
        $img = unserialize($level['thumb_url']);
        array_unshift($img,$level['thumb']);
        $goods = [];
        $month = date('Ym');
        $record = pdo_fetch('select * from '.tablename('ewei_shop_level_record').' where (openid = :openid or user_id = :user_id) and month = :month and status = 1',[':openid'=>$member['openid'],':user_id'=>$member['id'],':month'=>$month]);
        foreach ($goods_id as $key=>$item){
            $good = pdo_get('ewei_shop_goods',['uniacid'=>$uniacid,'id'=>$item],['id','title','thumb','total','productprice','marketprice','bargain']);
            $good['thumb'] = tomedia($good['thumb']);
            $good['image'] = tomedia($img[$key]);
            $good['is_get'] = !empty($record) ? $record['goods_id'] == $item ? 1 :2 : 0;
            $goods[] = $good;
        }
        return ['get'=>empty($record)?0:1,'goods'=>$goods];
    }

    /**
     * 年卡礼包领取记录
     * @param $user_id
     * @param int $page
     * @return array
     */
    public function index_level_record($user_id,$page = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        //计算记录总数
        $year_month = strtotime(date('Ym',time())."10");      //当前的年月份
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_level_record').'where (openid = :openid or user_id = :user_id) and uniacid = :uniacid and  (createtime < "'.$year_month.'" or status > 0)',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        //查询记录以及分页
        $record = pdo_fetchall('select * from '.tablename('ewei_shop_level_record').' where (openid = :openid or user_id = :user_id) and uniacid = :uniacid and (createtime < "'.$year_month.'" or status > 0) order by id desc LIMIT '.$pindex.','.$pageSize,[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        foreach ($record as $key=>$item) {
            $record[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            $record[$key]['updatetime'] = date('Y年m月d日',$item['updatetime']);
            $record[$key]['month'] = date('Y年m月',$item['createtime']);
            $record[$key]['thumb'] = tomedia(pdo_getcolumn('ewei_shop_goods',['id'=>$item['goods_id']],'thumb'));
        }
        return ['record'=>$record,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize];
    }

    /**
     * 十人礼包助力记录
     * @param $user_id
     * @param int $page
     * @return array
     */
    public function index_gift_help($user_id,$page = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $week = m('util')->week(time());
        //用户信息
        $member = m('member')->getMember($user_id);
        $pageSize = 20;
        $pindex = ($page - 1) * $pageSize;
        //礼包总和
        $gifts = pdo_fetchall(' select id,title,levels,starttime from '.tablename('ewei_shop_gift_bag').' where uniacid = "'.$uniacid.'"');
        //该用户对应的礼包
        $gift = m('game')->get_gift($gifts,$member['openid']);
        $record = pdo_fetchall('select * from '.tablename('ewei_shop_gift_record').' where (bang = :openid or user_id = :user_id) and createtime between "'.$week['start'].'" and "'.$week['end'].'" order by id desc LIMIT '.$pindex.','.$pageSize,[':openid'=>$member['openid'],':user_id'=>$member['id']]);
        $new = pdo_fetchall('select id,nickname,avatar,openid,createtime from '.tablename('ewei_shop_member').' where agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'" order by createtime desc LIMIT 10');
        $record = array_merge($record,$new);
        $list = m('game')->isvalid($record,$week['start'],$member['id']);
        $list = m('util')->array_unique_unset($list,"openid","share");
        $total = count($list);
        return ['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize];
    }

    /**
     * 十人礼包的领取记录
     * @param $user_id
     * @param int $page
     * @return array
     */
    public function index_gift_record($user_id,$page = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_gift_log').'where uniacid = :uniacid and (openid = :openid or user_id = :user_id) and status = 2',[':uniacid'=>$uniacid,':openid'=>$member['openid'],':user_id'=>$member['id']]);
        $list = pdo_fetchall('select g.thumb,l.gift_id,l.createtime,l.status from '.tablename('ewei_shop_gift_log').'l join '.tablename('ewei_shop_goods').'g on g.id = l.goods_id'.' where l.uniacid = :uniacid and (l.openid = :openid or l.user_id = :user_id) and l.status = 2 LIMIT '.$pindex.','.$pageSize,[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        foreach($list as $key => $item){
            $week = m('util')->week($item['createtime']);
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            $gift = m('game')->check($item['gift_id']);
            $list[$key]['title'] = date('m.d',$week['start'])."--".date('m.d',$week['end'])."周领取".$gift;
            $list[$key]['thumb'] = tomedia($item['thumb']);
        }
        return ['total'=>$total,'page'=>$page,'pageSize'=>$pageSize,'list'=>$list];
    }

    /**
     *  跑库精选详情
     * @param $user_id
     * @param $id
     * @return bool
     */
    public function index_choice_detail($user_id,$id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //用户信息
        $member = m('member')->getMember($user_id);
        //跑库精选
        $detail = pdo_fetch('select * from '.tablename('ewei_shop_choice').' where id = :id and uniacid = :uniacid and status = 1',[':uniacid'=>$uniacid,':id'=>$id]);
        $goodsid = explode(',',$detail['goodsids']);
        //商品信息
        foreach ($goodsid as $item){
            $good = pdo_get('ewei_shop_goods',['id'=>$item],['id','title','productprice','marketprice','thumb']);
            $good['thumb'] = tomedia($good['thumb']);
            $detail['goods'][] = $good;
        }
        $detail['createtime'] = date('Y-m-d H:i:s',$detail['createtime']);
        $detail['thumb'] = tomedia($detail['thumb']);
        $detail['image'] = tomedia($detail['image']);
        //关注人数
        $detail['count'] = pdo_count('ewei_shop_choice_fav',['ch_id' => $detail['id'],'status' => 1,'uniacid' => $uniacid]);
        //当前用户是否关注
        $fav = pdo_fetch('select * from '.tablename('ewei_shop_choice_fav').'where (openid = :openid or user_id = :user_id) and uniacid = :uniacid',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        $detail['is_fav'] = empty($fav) || $fav['status'] == 0 ? 0 : 1;
        return $detail;
    }

    /**
     * 跑库精选  ----   关注和取消关注
     * @param $user_id
     * @param $id
     * @return array
     */
    public function index_choice_fav($user_id,$id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        if(!pdo_exists('ewei_shop_choice',['uniacid'=>$uniacid,'status'=>1,'id'=>$id])) return ['status'=>1,'msg'=>"文章信息错误"];
        //用户信息
        $member = m('member')->getMember($user_id);
        //查有没有这个人的关注记录
        $fav = pdo_fetch('select * from '.tablename('ewei_shop_choice_fav').'where (openid = :openid or user_id = :user_id) and uniacid = :uniacid',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        //没有记录 加入记录 有记录  改变状态
        if(empty($fav)){
            pdo_insert('ewei_shop_choice_fav',['uniacid'=>$uniacid,'ch_id'=>$id,'openid'=>$member['openid'],'user_id'=>$member['id'],'status'=>1,'createtime'=>time()]);
        }else{
            $status = $fav['status'] == 0 ? 1 : 0;
            pdo_update('ewei_shop_choice_fav',['status'=>$status],['id'=>$fav['id']]);
        }
        $msg = empty($fav) || $fav['status'] == 0 ? "关注成功" : "取消关注成功";
        return ['status'=>0,'msg'=>$msg];
    }

    /**
     * @return array
     */
    public function shop_adv()
    {
        //最上面的天天跑
        $top = m('shop')->get_icon(1);
        //轮播
        $banner = m('shop')->get_icon(2);
        //分类
        $cate = m('shop')->get_icon(3);
        //中间的图标
        $middle = m('shop')->get_icon(4);
        return ['top'=>$top,'banner'=>$banner,'cate'=>$cate,'middle'=>$middle];
    }

    /**
     * @return array
     */
    public function shop_shop()
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $shop = pdo_fetchall('select * from '.tablename('ewei_shop_merch_user').'where uniacid = "'.$uniacid.'" and status = 1 and member_id != 0 order by isrecommand desc,id desc limit 6');
        return $shop;
    }

    /**
     * 商品信息
     * @param int $type
     * @param string $sort
     * @param int $page
     * @return mixed
     */
    public function shop_goods($type = 3,$sort = 'desc',$page = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        if($type == 3){    //总和
            $args = array( "pagesize" =>9, "page" => $page,"deduct_type"=>2,"from" => "miniprogram", "order" =>'displayorder desc,(minprice-deduct) asc,deduct desc,sales desc' );
        }elseif ($type==2){   //价格
            $args = array( "pagesize" =>9, "page" => $page,"deduct_type"=>2,"from" => "miniprogram", "order" =>'(minprice-deduct)'.$sort.',deduct'.$sort);
        }elseif ($type==1){   //销量
            $args = array( "pagesize" =>9, "page" => $page,"deduct_type"=>2,"from" => "miniprogram", "order" =>'sales'.$sort.',(minprice-deduct)'.$sort.',deduct'.$sort );
        }else{   //最新
            $args = array( "pagesize" =>9, "page" => $page,"deduct_type"=>2,"from" => "miniprogram", "order" =>'id'.$sort.',(minprice-deduct)'.$sort.',deduct'.$sort );
        }
        $item['data'] = array();
        $item['data'] = m('goods')->getList($args);
        $item['total'] = $item['data']['total'];
        $item['pagesize'] = 9;
        $item['data'] = m('shop')->getGoodsList($item['data'],$page);
        $count = pdo_count('ewei_shop_choice',['uniacid'=>$uniacid,'status'=>1]);
        $pindex = rand(0,$count-1);
        $choice = pdo_fetch('select * from '.tablename('ewei_shop_choice').' where uniacid = "'.$uniacid.'" and status = 1 limit '.$pindex.', 1');
        $choice["thumb"] = tomedia($choice['thumb']);
        $choice["image"] = tomedia($choice['image']);
        array_push($item['data'],$choice);
        return $item;
    }

    /**
     * 商城分类
     * @return array
     */
    public function shop_cate()
    {
        //获取所有分类
        $category = m('shop')->getCategory();
        $recommands = array();
        //遍历二级分类
        foreach ($category['children'] as $k => $v) {
            foreach ($v as $r) {
                //获得推荐的分类
                if ($r['isrecommand'] == 1) {
                    $r['thumb'] = tomedia($r['thumb']);
                    $rec = array(
                        'id'     => $r['id'],
                        'name'   => $r['name'],
                        'thumb'  => $r['thumb'],
                        'advurl' => $r['advurl'],
                        'advimg' => $r['advimg'],
                        'child'  => array(),
                        'level'  => $r['level']
                    );
                    if (isset($category['children'][$r['id']])) {
                        foreach ($category['children'][$r['id']] as $c) {
                            $c['thumb'] = tomedia($c['thumb']);
                            $child = array(
                                'id'     => $c['id'],
                                'name'   => $c['name'],
                                'thumb'  => $c['thumb'],
                                'advurl' => $c['advurl'],
                                'advimg' => $c['advimg'],
                                'child'  => array()
                            );
                            $rec['child'][] = $child;
                        }
                    }
                    $recommands[] = $rec;
                }
            }
        }
        $allcategory = array();
        foreach ($category['parent'] as $p) {
            //一级分类
            $p['thumb'] = tomedia($p['thumb']);
            $p['advimg'] = tomedia($p['advimg']);
            $parent = array(
                'id'     => $p['id'],
                'name'   => $p['name'],
                'thumb'  => $p['thumb'],
                'advurl' => $p['advurl'],
                'advimg' => $p['advimg'],
                'child'  => array()
            );
            //二级分类
            if (is_array($category['children'][$p['id']])) {
                foreach ($category['children'][$p['id']] as $c) {
                    if (!empty($c['thumb'])) {
                        $c['thumb'] = tomedia($c['thumb']);
                    }
                    if (!empty($c['thumb'])) {
                        $c['advimg'] = tomedia($c['advimg']);
                    }
                    if (!empty($c['id'])) {
                        $child = array(
                            'id'     => $c['id'],
                            'name'   => $c['name'],
                            'thumb'  => $c['thumb'],
                            'advurl' => $c['advurl'],
                            'advimg' => $c['advimg'],
                            'child'  => array(),
                            'level'  => $c['level']
                        );
                    }
                    //三级分类
                    if (is_array($category['children'][$c['id']])) {
                        foreach ($category['children'][$c['id']] as $t) {
                            if (!empty($t['thumb'])) {
                                $t['thumb'] = tomedia($t['thumb']);
                            }
                            if (!empty($t['id'])) {
                                $child['child'][] = array('id' => $t['id'], 'name' => $t['name'], 'thumb' => $t['thumb'], 'advurl' => $t['advurl'], 'advimg' => $t['advimg']);
                            }
                        }
                    }
                    $parent['child'][] = $child;
                }
            }
            $allcategory[] = $parent;
        }
        return array( 'recommands' => $recommands, 'category' => $allcategory);
    }

    /**
     * 商品搜索
     * @param $keywords
     * @param $cate
     * @param int $page
     * @param $isnew
     * @param $ishot
     * @param $isrecommand
     * @param $isdiscount
     * @param $istime
     * @param $issendfree
     * @param $merchid
     * @param $order
     * @param $by
     * @return array
     */
    public function shop_search($keywords,$cate,$page = 1,$isnew,$ishot,$isrecommand,$isdiscount,$istime,$issendfree,$order,$by)
    {
        global $_W;
        //查询的筛选条件
        $args = array( "pagesize" => 10, "page" => intval($page), "isnew" => trim($isnew), "ishot" => trim($ishot), "isrecommand" => trim($isrecommand), "isdiscount" => trim($isdiscount), "istime" => trim($istime), "keywords" => trim($keywords), "cate" => intval($cate), "order" => trim($order), "by" => trim($by), "issendfree"=>trim($issendfree),"from" => "miniprogram" );
        //获得查询到商品
        $goods = m("goods")->getList($args);
        //获得售罄图标
        $saleout = (!empty($_W["shopset"]["shop"]["saleout"]) ? tomedia($_W["shopset"]["shop"]["saleout"]) : "/static/images/saleout-2.png");
        $goods_list = array( );
        //当查到的有商品  遍历商品信息
        if( 0 < $goods["total"] ) {
            $goods_list = $goods["list"];
            foreach ($goods_list as $index => $item) {
                //如果分类等于 4  跑库会员
                if ($cate == 4) {
                    if (in_array($item['id'], array(3, 4, 5, 7))) {
                        $goods_list[$index]['memberthumb'] = $goods_list[$index]['thumb'];
                        $goods_list[$index]['thumb'] = m('goods')->levelurlup($item['id']);
                    }
                    $goods_list[$index]['salesreal'] = $goods_list[$index]['sales'] = $goods_list[$index]['salesreal'] * 21 + rand(0, 10);
                }
                if ($cate == 4) {//会员产品获取有效期
                    $agentlevel = pdo_fetch("select * from " . tablename("ewei_shop_commission_level") . " where id=:id limit 1", array(":id" => $item['agentlevel']));
                    $goods_list[$index]['available'] = $agentlevel['available'];
                    $goods_list[$index]['content'] = strip_tags($item['content']);
                }
                //如果是促销商品  并且结束时间小于当前时间  也就是促销结束
                if ($goods_list[$index]["isdiscount"] && time() > $goods_list[$index]["isdiscount_time"]) {
                    $goods_list[$index]["isdiscount"] = 0;
                }
                //商品的最低价
                $goods_list[$index]["minprice"] = (double)$goods_list[$index]["minprice"];
                unset($goods_list[$index]["marketprice"]);
                unset($goods_list[$index]["maxprice"]);
                unset($goods_list[$index]["isdiscount_discounts"]);
                unset($goods_list[$index]["description"]);
                unset($goods_list[$index]["discount_time"]);
                //如果已售罄  把售罄图标显示
                if ($item["total"] < 1) {
                    $goods_list[$index]["saleout"] = $saleout;
                }
                //如果有折扣信息   显示价格 等于最低价减去折扣价
                if (isset($_GPC["deduct"])) {
                    $goods_list[$index]["showprice"] = round($goods_list[$index]["minprice"] - $goods_list[$index]["deduct"], 2);
                }
            }
        }
        return array( "list" => $goods_list, "total" => $goods["total"], "pagesize" => $args["pagesize"] );
    }

    /**
     * @param $user_id
     * @param $id
     * @param $merch_user
     * @return array
     */
    public function shop_goods_detail($user_id,$id,$merch_user)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //获取用户信息
        $member = m("member")->getMember($user_id);
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
        //查找商品信息
        $goods = pdo_fetch("select * from " . tablename("ewei_shop_goods") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
        //会员浏览权限   会员组浏览权限
        $showlevels = ($goods["showlevels"] != "" ? explode(",", $goods["showlevels"]) : array( ));
        $showgroups = ($goods["showgroups"] != "" ? explode(",", $goods["showgroups"]) : array( ));
        $showgoods = 0;
        if( !empty($member) )
        {
            if( !empty($showlevels) && in_array($member["agentlevel"], $showlevels) || !empty($showgroups) && in_array($member["groupid"], $showgroups) || empty($showlevels) && empty($showgroups) )
            {
                $showgoods = 1;
            }
        }
        else
        {
            if( empty($showlevels) && empty($showgroups) )
            {
                $showgoods = 1;
            }
        }
        //没商品 或者没浏览权限  报错
        if( empty($goods) || empty($showgoods) )
        {
            return ['status'=>AppError::$GoodsNotFound];
        }
        //获得商品的商户信息
        $merchid = $goods["merchid"];
        //商品已销售
        $goods["sales"] = $goods["sales"] + $goods["salesreal"];
        $goods["buycontentshow"] = 0;
        //buyshow  购买后可见
        if( $goods["buyshow"] == 1 )
        {
            $sql = "select o.id from " . tablename("ewei_shop_order") . " o left join " . tablename("ewei_shop_order_goods") . " g on o.id = g.orderid" ." where (o.openid=:openid or o.user_id = :user_id) and g.goodsid=:id and o.status>0 and o.uniacid=:uniacid limit 1";
            $buy_goods = pdo_fetch($sql, array( ":openid" => $member['openid'],":user_id" => $member['id'], ":id" => $id, ":uniacid" => $_W["uniacid"] ));
            if( !empty($buy_goods) )
            {
                $goods["buycontentshow"] = 1;
                $goods["buycontent"] = m("common")->html_to_images($goods["buycontent"]);
            }
        }
        //单位和城市
        $goods["unit"] = (empty($goods["unit"]) ? "件" : $goods["unit"]);
        $citys = m("dispatch")->getNoDispatchAreas($goods);
        if( !empty($citys) && is_array($citys) )
        {
            $has_city = 1;
        }
        else
        {
            $has_city = 0;
        }
        $goods["citys"] = $citys;
        $goods["has_city"] = $has_city;
        $goods["seckillinfo"] = false;
        $seckill = p("seckill");
        //秒杀
        $seckillinfo = [];
        if( $seckill )
        {
            $time = time();
            $seckillinfo = $seckill->getSeckill($goods["id"], 0, false);
            if( !empty($seckillinfo) )
            {
                if( $seckillinfo["starttime"] <= $time && $time < $seckillinfo["endtime"] )
                {
                    $seckillinfo["status"] = 0;
                    unset($_SESSION[$id . "_log_id"]);
                    unset($_SESSION[$id . "_task_id"]);
                    unset($log_id);
                }
                else
                {
                    if( $time < $seckillinfo["starttime"] )
                    {
                        $seckillinfo["status"] = 1;
                    }
                    else
                    {
                        $seckillinfo["status"] = -1;
                    }
                }
            }
            $goods["seckillinfo"] = $seckillinfo;
        }
        //获得商品的运费
        $goods["dispatchprice"] = m('shop')->getGoodsDispatchPrice($goods, $seckillinfo);
        $goods["city_express_state"] = 1;
        $city_express = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_city_express") . " WHERE uniacid=:uniacid and merchid=0 limit 1", array( ":uniacid" => $_W["uniacid"] ));
        if( empty($city_express) || $city_express["enabled"] == 0 || 0 < $goods["merchid"] || $goods["type"] != 1 )
        {
            $goods["city_express_state"] = 0;
        }
        else
        {
            if( empty($city_express["is_dispatch"]) )
            {
                $goods["dispatchprice"] = array( "min" => $city_express["start_fee"], "max" => $city_express["fixed_fee"] );
            }
        }
        //商品图集
        $thumbs = iunserializer($goods["thumb_url"]);
        if( empty($thumbs) )
        {
            //商品图
            $thumbs = array( $goods["thumb"] );
            if( !empty($goods["thumb_first"]) && !empty($goods["thumb"]) )
            {
                $thumbs = array_merge(array( $goods["thumb"] ), $thumbs);
            }
            if( is_array($thumbs) && count($thumbs) == 2 )
            {
                $thumbs = array_unique($thumbs);
            }
            $thumbs = array_values($thumbs);
        }
        else
        {
            if( !empty($goods["thumb_first"]) && !empty($goods["thumb"]) )
            {
                $thumbs = array_merge(array( $goods["thumb"] ), $thumbs);
            }
            $thumbs = array_values($thumbs);
        }
        $goods["thumbs"] = set_medias($thumbs);
        $goods["thumbMaxWidth"] = 750;
        $goods["thumbMaxHeight"] = 750;
        //商品的视频
        $goods["video"] = tomedia($goods["video"]);
        if( strexists($goods["video"], "v.qq.com/iframe/player.html") )
        {
            $videourl = m('shop')->getQVideo($goods["video"]);
            if( !is_error($videourl) )
            {
                $goods["video"] = $videourl;
            }
        }
        if( !empty($goods["thumbs"]) && is_array($goods["thumbs"]) )
        {
            $new_thumbs = array( );
            foreach( $goods["thumbs"] as $i => $thumb )
            {
                $new_thumbs[] = $thumb;
            }
            $goods["thumbs"] = $new_thumbs;
        }
        //商品的规格
        $specs = pdo_fetchall("select * from " . tablename("ewei_shop_goods_spec") . " where goodsid=:goodsid and  uniacid=:uniacid order by displayorder asc", array( ":goodsid" => $id, ":uniacid" => $_W["uniacid"] ));
        $spec_titles = array( );
        foreach( $specs as $key => $spec )
        {
            if( 2 <= $key )
            {
                break;
            }
            $spec_titles[] = $spec["title"];
        }
        if( 0 < $goods["hasoption"] )
        {
            $goods["spec_titles"] = implode("、", $spec_titles);
        }
        else
        {
            $goods["spec_titles"] = "";
        }
        //商品的参数
        $goods["params"] = pdo_fetchall("SELECT * FROM " . tablename("ewei_shop_goods_param") . " WHERE uniacid=:uniacid and goodsid=:goodsid order by displayorder asc", array( ":uniacid" => $uniacid, ":goodsid" => $goods["id"] ));
        $goods = set_medias($goods, "thumb");
        //可否购买
        $goods["canbuy"] = (!empty($goods["status"]) && empty($goods["deleted"]) ? 1 : 0);
        //不可购买的原因
        $goods["cannotbuy"] = "";
        if( $goods["total"] <= 0 )
        {
            $goods["canbuy"] = 0;
            $goods["cannotbuy"] = "商品库存不足";
        }
        if( 0 < $goods["isendtime"] && 0 < $goods["endtime"] && $goods["endtime"] < time() )
        {
            $goods["canbuy"] = 0;
            $goods["cannotbuy"] = "商品已过期";
        }
        $goods["timestate"] = "";
        $goods["userbuy"] = "1";
        //usermaxbuy   用户可购最大数量
        if( 0 < $goods["usermaxbuy"] )
        {
            //mysql语句中  ifnull(expression_1,expression_2)   表示 如果不是null  输出expression_1   是null  expression_2
            $order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and (o.openid=:openid or o.user_id = :user_id)  and og.uniacid=:uniacid ", array( ":goodsid" => $goods["id"], ":uniacid" => $uniacid, ":openid" => $member['openid'],":user_id" => $member['id'] ));
            if( $goods["usermaxbuy"] <= $order_goodscount )
            {
                $goods["userbuy"] = 0;
                $goods["canbuy"] = 0;
                $goods["cannotbuy"] = "超出最大购买数量";
            }
        }
        //用户的等级id  和  用户组id
        $levelid = $member["agentlevel"];
        $groupid = $member["groupid"];
        //商品的购买等级权限
        $goods["levelbuy"] = "1";
        if( $goods["buylevels"] != "" )
        {
            $buylevels = explode(",", $goods["buylevels"]);
            if( !in_array($levelid, $buylevels) )
            {
                $goods["levelbuy"] = 0;
                $goods["canbuy"] = 0;
                //不可购买的原因
                $goods["cannotbuy"] = m('shop')->canByLevels($buylevels);
            }
        }
        //商品的会员组购买等级权限
        $goods["groupbuy"] = "1";
        if( $goods["buygroups"] != "" )
        {
            $buygroups = explode(",", $goods["buygroups"]);
            if( !in_array($groupid, $buygroups) )
            {
                $goods["groupbuy"] = 0;
                $goods["canbuy"] = 0;
                $goods["cannotbuy"] = "所在会员组无法购买";
            }
        }
        //商品的时间购买   0不是时间购买  -1限时购未开始  1限时购已结束
        $goods["timebuy"] = "0";
        if( $goods["istime"] == 1 )
        {

            if( time() < $goods["timestart"] )
            {
                $goods["timebuy"] = "-1";
                $goods["canbuy"] = 0;
                $goods["cannotbuy"] = "限时购未开始";
            }
            else
            {
                if( $goods["timeend"] < time() )
                {
                    $goods["timebuy"] = "1";
                    $goods["canbuy"] = 0;
                    $goods["cannotbuy"] = "限时购已结束";
                }
            }
        }
        $goods["timeout"] = false;
        $goods["access_time"] = false;
        //如果是计时计次商品  verifygoodslimittype有效期类型   0购买后有效  1指定过期日期
        if( $goods["type"] == 5 && $goods["verifygoodslimittype"] == 1 )
        {
            //verifygoodslimitdate  过期时间
            $limittime = $goods["verifygoodslimitdate"];
            $now = time();
            if( $limittime < time() )
            {
                $goods["timeout"] = true;
                $goods["hint"] = "您选择的记次时商品的使用时间已经失效，无法购买！";
            }
            else
            {
                //如果还有半小时或者2小时的有效期
                if( 1800 < $limittime - $now && $limittime - $now < 7200 )
                {
                    $goods["access_time"] = true;
                    $goods["hint"] = "您选择的记次时商品到期日期是" . date("Y-m-d H:i:s", $limittime) . ",请确保有足够的时间抵达核销门店进行核销，以免耽误您的使用。";
                }
                else
                {
                    //如果核销期  不足半小时   不可购买
                    if( $limittime - $now < 1800 )
                    {
                        $goods["timeout"] = true;
                        $goods["hint"] = "您选择的记次时商品的使用时间即将失效，无法购买！";
                    }
                }
            }
        }
        //是否是全返商品
        $isfullback = false;
        if( $goods["isfullback"] )
        {
            $isfullback = true;
            $fullbackgoods = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_fullback_goods") . " WHERE uniacid = :uniacid and goodsid = :goodsid limit 1 ", array( ":uniacid" => $uniacid, ":goodsid" => $id ));
            if( $goods["hasoption"] == 1 )
            {
                $fullprice = pdo_fetch("select min(allfullbackprice) as minfullprice,max(allfullbackprice) as maxfullprice,min(allfullbackratio) as minfullratio\r\n                            ,max(allfullbackratio) as maxfullratio,min(fullbackprice) as minfullbackprice,max(fullbackprice) as maxfullbackprice\r\n                            ,min(fullbackratio) as minfullbackratio,max(fullbackratio) as maxfullbackratio,min(`day`) as minday,max(`day`) as maxday\r\n                            from " . tablename("ewei_shop_goods_option") . " where goodsid = :goodsid", array( ":goodsid" => $id ));
                $fullbackgoods["minallfullbackallprice"] = $fullprice["minfullprice"];
                $fullbackgoods["maxallfullbackallprice"] = $fullprice["maxfullprice"];
                $fullbackgoods["minallfullbackallratio"] = $fullprice["minfullratio"];
                $fullbackgoods["maxallfullbackallratio"] = $fullprice["maxfullratio"];
                $fullbackgoods["minfullbackprice"] = $fullprice["minfullbackprice"];
                $fullbackgoods["maxfullbackprice"] = $fullprice["maxfullbackprice"];
                $fullbackgoods["minfullbackratio"] = $fullprice["minfullbackratio"];
                $fullbackgoods["maxfullbackratio"] = $fullprice["maxfullbackratio"];
                $fullbackgoods["fullbackratio"] = $fullprice["minfullbackratio"];
                $fullbackgoods["fullbackprice"] = $fullprice["minfullbackprice"];
                $fullbackgoods["minday"] = $fullprice["minday"];
                $fullbackgoods["maxday"] = $fullprice["maxday"];
            }
            else
            {
                $fullbackgoods["maxallfullbackallprice"] = $fullbackgoods["minallfullbackallprice"];
                $fullbackgoods["maxallfullbackallratio"] = $fullbackgoods["minallfullbackallratio"];
                $fullbackgoods["minday"] = $fullbackgoods["day"];
            }
        }
        $goods["isfullback"] = $isfullback;
        $goods["fullbackgoods"] = $fullbackgoods;
        $goods["fullbacktext"] = m("sale")->getFullBackText();
        //是否赠品
        $isgift = 0;
        $gifts = array( );
        $giftgoods = array( );
        $grftarray = array( );
        $i = 0;
        $gifts = pdo_fetchall("select id,goodsid,giftgoodsid,thumb,title from " . tablename("ewei_shop_gift") . " where uniacid = :uniacid and activity = 2 and status = 1 and starttime <= :starttime and endtime >= :endtime ", array( ":uniacid" => $uniacid, ":starttime" => time(), ":endtime" => time() ));
        foreach( $gifts as $key => $value )
        {
            $gid = explode(",", $value["goodsid"]);
            foreach( $gid as $ke => $val )
            {
                if( $val == $id )
                {
                    //赠品id
                    $giftgoods = explode(",", $value["giftgoodsid"]);
                    foreach( $giftgoods as $k => $v )
                    {
                        $isgift = 1;
                        $gifts[$key]["gift"][$k] = pdo_fetch("select id,title,thumb,marketprice from " . tablename("ewei_shop_goods") . " where uniacid = :uniacid and deleted = 0 and total > 0 and status = 2 and id = :id ", array( ":uniacid" => $uniacid, ":id" => $val ));
                        $gifttitle = (!empty($gifts[$key]["gift"][$k]["title"]) ? $gifts[$key]["gift"][$k]["title"] : "赠品");
                        $gifts[$key]["gift"][$k] = set_medias($gifts[$key]["gift"][$k], array( "thumb" ));
                    }
                }
            }
            if( empty($gifts[$key]["gift"]) )
            {
                unset($gifts[$key]);
            }
            else
            {
                $grftarray[$i] = $gifts[$key];
                $i++;
            }
        }
        $grftarray = set_medias($grftarray, array( "thumb" ));
        $goods["isgift"] = $isgift;
        //这个商品携带的赠品
        $goods["gifts"] = $grftarray;
        //是否可以加入购物车
        $goods["canAddCart"] = 1;
        //支持线下核销  或者  虚拟商品 或者 虚拟物品 或者 存在赠品   不可以加入购物车
        if( $goods["isverify"] == 2 || $goods["type"] == 2 || $goods["type"] == 3 || !empty($grftarray) )
        {
            $goods["canAddCart"] = 0;
        }
        //没懂这是干嘛的
        $enoughs = com_run("sale::getEnoughs");
        $enoughfree = com_run("sale::getEnoughFree");
        $goods_nofree = com_run("sale::getEnoughsGoods");
        if( $is_openmerch == 1 && 0 < $goods["merchid"] )
        {
            $merch_set = $merch_plugin->getSet("sale", $goods["merchid"]);
            if( $merch_set["enoughfree"] )
            {
                $enoughfree = $merch_set["enoughorder"];
                if( $merch_set["enoughorder"] == 0 )
                {
                    $enoughfree = -1;
                }
            }
        }
        if( $enoughfree && $enoughfree < $goods["minprice"] && empty($seckillinfo) )
        {
            $goods["dispatchprice"] = 0;
        }
        //没懂结束
        $goods["hasSales"] = 0;
        //满件包邮  满额包邮  存在 可以销售
        if( 0 < $goods["ednum"] || 0 < $goods["edmoney"] )
        {
            $goods["hasSales"] = 1;
        }
        if( $enoughfree || $enoughs && 0 < count($enoughs) )
        {
            $goods["hasSales"] = 1;
        }
        if( !empty($goods_nofree) && in_array($id, $goods_nofree) )
        {
            $enoughfree = 0;
        }
        $goods["enoughfree"] = $enoughfree;
        $goods["enoughs"] = $enoughs;
        //多规格中的最小价格  和  最大价格
        $minprice = $goods["minprice"];
        $maxprice = $goods["maxprice"];
        //获取用户的等级
        //$level = m("member")->getLevel($openid);
        $level = $member['agentlevel'];
        $memberprice = m("goods")->getMemberPrice($goods, $level);
        //isdiscount_time  促销结束时间
        if( $goods["isdiscount"] && time() <= $goods["isdiscount_time"] )
        {
            $goods["oldmaxprice"] = $maxprice;
            $isdiscount_discounts = json_decode($goods["isdiscount_discounts"], true);
            $prices = array( );
            if( !isset($isdiscount_discounts["type"]) || empty($isdiscount_discounts["type"]) )
            {
                //$level = m("member")->getLevel($openid);
                $level = $member['agentlevel'];
                //获得会员等级的折扣金额信息
                $prices_array = m("order")->getGoodsDiscountPrice($goods, $level, 1);
                $prices[] = $prices_array["price"];
            }
            else
            {
                $goods_discounts = m("order")->getGoodsDiscounts($goods, $isdiscount_discounts, $levelid);
                $prices = $goods_discounts["prices"];
            }
            //获得最小价格  和  最大价格
            $minprice = min($prices);
            $maxprice = max($prices);
        }
        $goods["minprice"] = (double) $minprice;
        $goods["maxprice"] = (double) $maxprice;
        $goods["getComments"] = empty($_W["shopset"]["trade"]["closecommentshow"]);
        $goods["hasServices"] = $goods["cash"] || $goods["seven"] || $goods["repair"] || $goods["invoice"] || $goods["quality"];
        //获得  售后服务的信息  cash 货到付款  quality  正品保证  seven 7天无理由退款  invoice  发票  repair 保修
        $goods["services"] = array( );
        if( $goods["cash"] )
        {
            $goods["services"][] = "货到付款";
        }
        if( $goods["quality"] )
        {
            $goods["services"][] = "正品保证";
        }
        if( $goods["seven"] )
        {
            $goods["services"][] = "7天无理由退换";
        }
        if( $goods["invoice"] )
        {
            $goods["services"][] = "发票";
        }
        if( $goods["repair"] )
        {
            $goods["services"][] = "保修";
        }
        //商品标签风格
        $labelstyle = pdo_fetch("SELECT id,uniacid,style FROM " . tablename("ewei_shop_goods_labelstyle") . " WHERE uniacid=:uniacid LIMIT 1", array( ":uniacid" => $uniacid ));
        if( json_decode($goods["labelname"], true) )
        {
            $labelname = json_decode($goods["labelname"], true);
        }
        else
        {
            $labelname = unserialize($goods["labelname"]);
        }
        $goods["labelname"] = $labelname;
        $goods["labelstyle"] = $labelstyle;
        //商品的售后服务
        $labellist = $goods["services"];
        if( is_array($labelname) )
        {
            $labellist = array_merge($labellist, $labelname);
        }
        $goods["labels"] = array( "style" => (is_array($labelstyle) ? intval($labelstyle["style"]) : 0), "list" => $labellist );
        //是否商品收藏
        $goods["isfavorite"] = m("goods")->isFavorite($id,$user_id);
        //购物车数量
        $goods["cartcount"] = m("goods")->getCartCount($user_id);
        //加入浏览足迹
        m("goods")->addHistory($user_id,$id);
        $shop = set_medias(m("common")->getSysset("shop"), "logo");
        $shop["url"] = mobileUrl("", NULL);
        $mid = $user_id;
        $opencommission = false;
        if( p("commission") && empty($member["agentblack"]) )
        {
            $cset = p("commission")->getSet();
            $opencommission = 0 < intval($cset["level"]);
            if( $opencommission )
            {
                if( empty($mid) && $member["isagent"] == 1 && $member["status"] == 1 )
                {
                    $mid = $member["id"];
                }
                if( !empty($mid) && empty($cset["closemyshop"]) )
                {
                    $shop = set_medias(p("commission")->getShop($mid), "logo");
                    $shop["url"] = mobileUrl("commission/myshop", array( "mid" => $mid ), true);
                }
            }
        }
        //查找店铺
        if( empty($merch_user) )
        {
            $merch_flag = 0;
            if( $is_openmerch == 1 && 0 < $goods["merchid"] )
            {
                $merch_user = pdo_fetch( " select * from " . tablename("ewei_shop_merch_user") . "  where id=:id limit 1", array( ":id" => intval($goods["merchid"]) ));
                if( !empty($merch_user) )
                {
                    $shop = $merch_user;
                    $merch_flag = 1;
                }
            }
            if( $merch_flag == 1 )
            {
                $shopdetail = array( "logo" => (!empty($goods["detail_logo"]) ? tomedia($goods["detail_logo"]) : tomedia($shop["logo"])), "shopname" => (!empty($goods["detail_shopname"]) ? $goods["detail_shopname"] : $shop["merchname"]), "description" => (!empty($goods["detail_totaltitle"]) ? $goods["detail_totaltitle"] : $shop["desc"]), "btntext1" => trim($goods["detail_btntext1"]), "btnurl1" => (!empty($goods["detail_btnurl1"]) ? $goods["detail_btnurl1"] : mobileUrl("goods")), "btntext2" => trim($goods["detail_btntext2"]), "btnurl2" => (!empty($goods["detail_btnurl2"]) ? $goods["detail_btnurl2"] : mobileUrl("merch", array( "merchid" => $goods["merchid"] ))) );
            }
            else
            {
                $shopdetail = array( "logo" => (!empty($goods["detail_logo"]) ? tomedia($goods["detail_logo"]) : $shop["logo"]), "shopname" => (!empty($goods["detail_shopname"]) ? $goods["detail_shopname"] : $shop["name"]), "description" => (!empty($goods["detail_totaltitle"]) ? $goods["detail_totaltitle"] : $shop["description"]), "btntext1" => trim($goods["detail_btntext1"]), "btnurl1" => (!empty($goods["detail_btnurl1"]) ? $goods["detail_btnurl1"] : mobileUrl("goods")), "btntext2" => trim($goods["detail_btntext2"]), "btnurl2" => (!empty($goods["detail_btnurl2"]) ? $goods["detail_btnurl2"] : $shop["url"]) );
            }
            $param = array( ":uniacid" => $_W["uniacid"] );
            if( $merch_flag == 1 )
            {
                $sqlcon = " and merchid=:merchid";
                $param[":merchid"] = $goods["merchid"];
            }
            if( empty($shop["selectgoods"]) )
            {
                $statics = array( "all" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid " . $sqlcon . " and status=1 and deleted=0", $param), "new" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid " . $sqlcon . " and isnew=1 and status=1 and deleted=0", $param), "discount" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid " . $sqlcon . " and isdiscount=1 and status=1 and deleted=0", $param) );
            }
            else
            {
                $goodsids = explode(",", $shop["goodsids"]);
                $statics = array( "all" => count($goodsids), "new" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid " . $sqlcon . " and id in( " . $shop["goodsids"] . " ) and isnew=1 and status=1 and deleted=0", $param), "discount" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid " . $sqlcon . " and id in( " . $shop["goodsids"] . " ) and isdiscount=1 and status=1 and deleted=0", $param) );
            }
        }
        else
        {
            $shop = $merch_user;
            $shopdetail = array( "logo" => (!empty($goods["detail_logo"]) ? tomedia($goods["detail_logo"]) : tomedia($shop["logo"])), "shopname" => (!empty($goods["detail_shopname"]) ? $goods["detail_shopname"] : $shop["merchname"]), "description" => (!empty($goods["detail_totaltitle"]) ? $goods["detail_totaltitle"] : $shop["desc"]), "btntext1" => trim($goods["detail_btntext1"]), "btnurl1" => (!empty($goods["detail_btnurl1"]) ? $goods["detail_btnurl1"] : mobileUrl("goods")), "btntext2" => trim($goods["detail_btntext2"]), "btnurl2" => (!empty($goods["detail_btnurl2"]) ? $goods["detail_btnurl2"] : mobileUrl("merch", array( "merchid" => $goods["merchid"] ))) );
            if( empty($shop["selectgoods"]) )
            {
                $statics = array( "all" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid and merchid=:merchid and status=1 and deleted=0", array( ":uniacid" => $_W["uniacid"], ":merchid" => $goods["merchid"] )), "new" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid and merchid=:merchid and isnew=1 and status=1 and deleted=0", array( ":uniacid" => $_W["uniacid"], ":merchid" => $goods["merchid"] )), "discount" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid and merchid=:merchid and isdiscount=1 and status=1 and deleted=0", array( ":uniacid" => $_W["uniacid"], ":merchid" => $goods["merchid"] )) );
            }
            else
            {
                $goodsids = explode(",", $shop["goodsids"]);
                $statics = array( "all" => count($goodsids), "new" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid and merchid=:merchid and id in( " . $shop["goodsids"] . " ) and isnew=1 and status=1 and deleted=0", array( ":uniacid" => $_W["uniacid"], ":merchid" => $goods["merchid"] )), "discount" => pdo_fetchcolumn("select count(1) from " . tablename("ewei_shop_goods") . " where uniacid=:uniacid and merchid=:merchid and id in( " . $shop["goodsids"] . " ) and isdiscount=1 and status=1 and deleted=0", array( ":uniacid" => $_W["uniacid"], ":merchid" => $goods["merchid"] )) );
            }
        }
        //商品描述 或者短标题
        $goodsdesc = (!empty($goods["description"]) ? $goods["description"] : $goods["subtitle"]);
        //商品分享  标题  图片  描述 链接
        $_W["shopshare"] = array( "title" => (!empty($goods["share_title"]) ? $goods["share_title"] : $goods["title"]), "imgUrl" => (!empty($goods["share_icon"]) ? tomedia($goods["share_icon"]) : tomedia($goods["thumb"])), "desc" => (!empty($goodsdesc) ? $goodsdesc : $_W["shopset"]["shop"]["name"]), "link" => mobileUrl("app/share", array( "type" => "goods", "id" => $goods["id"] ), true) );
        $com = p("commission");
        if( $com )
        {
            $cset = $_W["shopset"]["commission"];
            if( !empty($cset) )
            {
                if( $member["isagent"] == 1 && $member["status"] == 1 )
                {
                    $_W["shopshare"]["link"] = mobileUrl("app/share", array( "type" => "goods", "id" => $goods["id"], "mid" => $member["id"] ), true);
                }
                else
                {
                    if( !empty($member['id']) )
                    {
                        $_W["shopshare"]["link"] = mobileUrl("app/share", array( "type" => "goods", "id" => $goods["id"], "mid" => $member["id"]), true);
                    }
                }
            }
            if( $goods["nocommission"] == 0 )
            {
                $glevel = m('shop')->getLevel($user_id);
                if( p("seckill") && p("seckill")->getSeckill($goods["id"]) )
                {
                    $goods["seecommission"] = 0;
                }
                if( 0 < $goods["bargain"] )
                {
                    $goods["seecommission"] = 0;
                }
                $goods["seecommission"] = m('shop')->getCommission($goods, $glevel, $cset);
                if( 0 < $goods["seecommission"] )
                {
                    $goods["seecommission"] = round($goods["seecommission"], 2);
                }
            }
            else
            {
                $goods["seecommission"] = 0;
            }
            $goods["cansee"] = $cset["cansee"];
            $goods["seetitle"] = $cset["seetitle"];
        }
        else
        {
            $goods["cansee"] = 0;
        }
        //获取线下门店信息
        $stores = array( );
        //支持线下核销
        if( $goods["isverify"] == 2 )
        {
            $storeids = array( );
            //线下门店id
            if( !empty($goods["storeids"]) )
            {
                $storeids = array_merge(explode(",", $goods["storeids"]), $storeids);
            }
            //如果这个商品对应的门店不存在
            if( empty($storeids) )
            {
                if( 0 < $merchid )
                {
                    //多商家门店信息
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where  uniacid=:uniacid and merchid=:merchid and status=1 ", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merchid ));
                }
                else
                {
                    //商店表
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where  uniacid=:uniacid and status=1", array( ":uniacid" => $_W["uniacid"] ));
                }
            }
            else
            {
                if( 0 < $merchid )
                {
                    //查找商品对应的门店
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and merchid=:merchid and status=1", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merchid ));
                }
                else
                {
                    //对应的商店
                    $stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and status=1", array( ":uniacid" => $_W["uniacid"] ));
                }
            }
        }
        //把一级分类 二级分类  三级分类  成本  减库存方式  淘宝id  淘宝链接(淘宝助手)
        unset($goods["pcate"]);
        unset($goods["ccate"]);
        unset($goods["tcate"]);
        unset($goods["costprice"]);
        //unset($goods["originalprice"]);   原价  废弃
        unset($goods["totalcnf"]);
        //unset($goods["salesreal"]);   真实销量
        //unset($goods["score"]);  得分 废弃
        unset($goods["taobaoid"]);
        unset($goods["taobaourl"]);
        unset($goods["updatetime"]);
        unset($goods["noticeopenid"]);
        unset($goods["noticetype"]);
        unset($goods["ccates"]);
        unset($goods["pcates"]);
        unset($goods["tcates"]);
        unset($goods["cates"]);
        unset($goods["artid"]);
        unset($goods["allcates"]);
        unset($goods["hascommission"]);
        unset($goods["commission1_rate"]);
        unset($goods["commission1_pay"]);
        unset($goods["commission2_rate"]);
        unset($goods["commission2_pay"]);
        unset($goods["commission3_rate"]);
        unset($goods["commission3_pay"]);
        unset($goods["commission_thumb"]);
        unset($goods["commission"]);
        unset($goods["needfollow"]);
        unset($goods["followurl"]);
        unset($goods["followtip"]);
        unset($goods["sharebtn"]);
        unset($goods["keywords"]);
        unset($goods["timestate"]);
        unset($goods["nocommission"]);
        unset($goods["hidecommission"]);
        unset($goods["diysave"]);
        unset($goods["diysaveid"]);
        unset($goods["deduct2"]);
        unset($goods["shopid"]);
        unset($goods["shorttitle"]);
        unset($goods["diyformtype"]);
        unset($goods["diyformid"]);
        unset($goods["diymode"]);
        unset($goods["discounts"]);
        unset($goods["verifytype"]);
        unset($goods["diyfields"]);
        unset($goods["groupstype"]);
        unset($goods["merchsale"]);
        unset($goods["manydeduct"]);
        unset($goods["checked"]);
        unset($goods["goodssn"]);
        unset($goods["productsn"]);
        unset($goods["isdiscount_discounts"]);
        unset($goods["isrecommand"]);
        unset($goods["dispatchtype"]);
        unset($goods["dispatchid"]);
        unset($goods["storeids"]);
        //unset($goods["thumb_url"]);
        unset($goods["share_icon"]);
        unset($goods["share_title"]);
        //商品图库
        if( !empty($goods["thumb_url"]) )
        {
            $goods["thumb_url"] = iunserializer($goods["thumb_url"]);
        }
        //门店
        $goods["stores"] = $stores;
        if( !empty($shopdetail) )
        {
            $shopdetail["btntext1"] = (!empty($shopdetail["btntext1"]) ? $shopdetail["btntext1"] : "全部商品");
            $shopdetail["btntext2"] = (!empty($shopdetail["btntext2"]) ? $shopdetail["btntext2"] : "进店逛逛");
            $shopdetail["btnurl1"] = m('shop')->getUrl($shopdetail["btnurl1"]);
            $shopdetail["btnurl2"] = m('shop')->getUrl($shopdetail["btnurl2"]);
            $shopdetail["static_all"] = $statics["all"];
            $shopdetail["static_new"] = $statics["new"];
            $shopdetail["static_discount"] = $statics["discount"];
        }
        $shopdetail = set_medias($shopdetail, "logo");
        $goods["shopdetail"] = $shopdetail;
        $goods["share"] = $_W["shopshare"];
        $goods["memberprice"] = "";
        if( (empty($goods["isdiscount"]) || !empty($goods["isdiscount"]) && $goods["isdiscount_time"] < time()) && !empty($memberprice) && $memberprice != $goods["minprice"] && !empty($level) )
        {
            $goods["memberprice"] = array( "levelname" => $level["levelname"], "price" => $memberprice );
        }
        $goods["coupons"] = array( );
        if( com("coupon") )
        {
            $goods["coupons"] = m('shop')->getCouponsbygood($goods["id"]);
        }
        //预售发货时间
        $goods["presellsendstatrttime"] = date("m月d日", $goods["presellsendstatrttime"]);
        //使用有效期
        $goods["endtime"] = date("Y-m-d H:i:s", $goods["endtime"]);
        $goods["isdiscount_date"] = date("Y-m-d H:i:s", $goods["isdiscount_time"]);
        $goods["productprice"] = (double) $goods["productprice"];
        $goods["credittext"] = $_W["shopset"]["trade"]["credittext"];
        $goods["moneytext"] = $_W["shopset"]["trade"]["moneytext"];
        //图文详情
        $goods["content"] = m("common")->html_to_images($goods["content"]);
        $goods["navbar"] = intval($_W["shopset"]["app"]["navbar"]);
        $goods["customer"] = intval($_W["shopset"]["app"]["customer"]);
        $goods["phone"] = intval($_W["shopset"]["app"]["phone"]);
        if( !empty($goods["customer"]) )
        {
            $goods["customercolor"] = (empty($_W["shopset"]["app"]["customercolor"]) ? "#ff5555" : $_W["shopset"]["app"]["customercolor"]);
        }
        if( !empty($goods["phone"]) )
        {
            $goods["phonecolor"] = (empty($_W["shopset"]["app"]["phonecolor"]) ? "#ff5555" : $_W["shopset"]["app"]["phonecolor"]);
            $goods["phonenumber"] = (empty($_W["shopset"]["app"]["phonenumber"]) ? "#ff5555" : $_W["shopset"]["app"]["phonenumber"]);
        }
        //是否是预售商品
        if( !empty($goods["ispresell"]) )
        {
            $goods["ispresellshow"] = 1;
            if( !empty($goods["preselltimestart"]) )
            {
                if( time() < $goods["preselltimestart"] )
                {
                    $goods["canbuy"] = 0;
                    $goods["preselltitle"] = "距离预售开始";
                }
                else
                {
                    if( $goods["preselltimestart"] < time() && time() < $goods["preselltimeend"] || $goods["preselltimestart"] < time() && empty($goods["preselltimeend"]) )
                    {
                        $goods["canbuy"] = 1;
                        $goods["preselltitle"] = "距离预售结束";
                    }
                    else
                    {
                        if( $goods["preselltimeend"] < time() && !empty($goods["preselltimeend"]) )
                        {
                            $times = $goods["presellovertime"] * 60 * 60 * 24 + $goods["preselltimeend"];
                            if( 0 < $goods["presellover"] && $times <= time() )
                            {
                                $goods["canbuy"] = 1;
                                $goods["ispresellshow"] = 0;
                            }
                            else
                            {
                                $goods["ispresellshow"] = 0;
                                $goods["canbuy"] = 0;
                            }
                        }
                    }
                }
            }
            //预售商品  （预售结束时间为0  表示没结束时间  或者结束时间大于当前时间）  启用了商品规则
            if( 0 < $goods["ispresell"] && ($goods["preselltimeend"] == 0 || time() < $goods["preselltimeend"]) && !empty($goods["hasoption"]) )
            {
                $presell = pdo_fetch("select min(presellprice) as minprice,max(presellprice) as maxprice from " . tablename("ewei_shop_goods_option") . " where goodsid = " . $id);
                $goods["minpresellprice"] = $presell["minprice"];
                $goods["maxpresellprice"] = $presell["maxprice"];
            }
            $goods["preselldatestart"] = (empty($goods["preselltimestart"]) ? 0 : date("Y-m-d H:i:s", $goods["preselltimestart"]));
            $goods["preselldateend"] = (empty($goods["preselltimeend"]) ? 0 : date("Y-m-d H:i:s", $goods["preselltimeend"]));
        }
        $package_goods = array( );
        //查找是否有商品套餐的组合商品
        $package_goods = pdo_fetch("select pg.id,pg.pid,pg.goodsid,p.displayorder,p.title from " . tablename("ewei_shop_package_goods") . " as pg\r\n                        left join " . tablename("ewei_shop_package") . " as p on pg.pid = p.id\r\n                        where pg.uniacid = " . $uniacid . " and pg.goodsid = " . $id . " and  p.starttime <= " . time() . " and p.endtime >= " . time() . " and p.deleted = 0 and p.status = 1 ORDER BY p.displayorder desc,pg.id desc limit 1 ");
        if( $package_goods["pid"] )
        {
            $packages = pdo_fetchall("SELECT id,title,thumb,packageprice FROM " . tablename("ewei_shop_package_goods") . "\r\n                    WHERE uniacid = " . $uniacid . " and pid = " . $package_goods["pid"] . "  ORDER BY id DESC");
            $packages = set_medias($packages, array( "thumb" ));
        }
        $goods["packagegoods"] = $package_goods;
        $hasSales = false;
        if( 0 < $goods["ednum"] || 0 < $goods["edmoney"] )
        {
            $hasSales = true;
        }
        if( $enoughfree || $enoughs && 0 < count($enoughs) )
        {
            $hasSales = true;
        }
        //活动信息
        $activity = array( );
        if( $enoughs && 0 < count($enoughs) && empty($seckillinfo) )
        {
            $activity["enough"] = $enoughs;
        }
        if( !empty($merch_set["enoughdeduct"]) && empty($seckillinfo) )
        {
            $one = array( array( "enough" => $merch_set["enoughmoney"], "give" => $merch_set["enoughdeduct"] ) );
            $merch_set["enoughs"] = array_merge_recursive($one, $merch_set["enoughs"]);
            $activity["merch_enough"] = $merch_set["enoughs"];
        }
        if( $hasSales && empty($seckillinfo) && (!is_array($goods["dispatchprice"]) && $goods["type"] == 1 && $goods["isverify"] != 2 && $goods["dispatchprice"] == 0 || $enoughfree && $enoughfree == -1 || 0 < $enoughfree || 0 < $goods["ednum"] || 0 < $goods["edmoney"]) )
        {
            if( !is_array($goods["dispatchprice"]) && $goods["type"] == 1 && $goods["isverify"] != 2 && $goods["dispatchprice"] == 0 )
            {
                $activity["postfree"]["goods"] = true;
            }
            if( 0 < $enoughfree && $goods["minprice"] < $enoughfree )
            {
                $activity["postfree"]["goods"] = false;
            }
            if( 0 < $goods["edmoney"] && $goods["minprice"] < $goods["edmoney"] )
            {
                $activity["postfree"]["goods"] = false;
            }
            if( $enoughfree && $enoughfree == -1 )
            {
                if( !empty($merch_set["enoughfree"]) )
                {
                    $activity["postfree"]["scope"] = "本店";
                }
                else
                {
                    $activity["postfree"]["scope"] = "全场";
                }
            }
            else
            {
                if( 0 < $goods["ednum"] )
                {
                    $activity["postfree"]["num"] = $goods["ednum"];
                    $activity["postfree"]["unit"] = (empty($goods["unit"]) ? "件" : $goods["unit"]);
                }
                if( 0 < $goods["edmoney"] )
                {
                    $activity["postfree"]["price"] = $goods["edmoney"];
                }
                if( $enoughfree )
                {
                    if( !empty($merch_set["enoughfree"]) )
                    {
                        $activity["postfree"]["scope"] = "本店";
                    }
                    else
                    {
                        $activity["postfree"]["scope"] = "全场";
                    }
                }
                $activity["postfree"]["enoughfree"] = $enoughfree;
            }
        }
        //如果商品的折扣存在 且不为空   活动的折扣等于商品的折扣
        if( !empty($goods["deduct"]) && $goods["deduct"] != "0.00" )
        {
            $activity["credit"]["deduct"] = $goods["deduct"];
        }
        //赠送卡路里  活动给
        if( !empty($goods["credit"]) )
        {
            $activity["credit"]["give"] = $goods["credit"];
        }
        if( 0 < floatval($goods["buyagain"]) && empty($seckillinfo) )
        {
            $activity["buyagain"]["discount"] = $goods["buyagain"];
            $activity["buyagain"]["buyagain_sale"] = $goods["buyagain_sale"];
        }
        if( !empty($fullbackgoods) && $isfullback )
        {
            if( 0 < $fullbackgoods["type"] )
            {
                if( 0 < $goods["hasoption"] )
                {
                    if( $fullbackgoods["minallfullbackallratio"] == $fullbackgoods["maxallfullbackallratio"] )
                    {
                        $activity["fullback"]["all_enjoy"] = $fullbackgoods["minallfullbackallratio"] . "%";
                    }
                    else
                    {
                        $activity["fullback"]["all_enjoy"] = $fullbackgoods["minallfullbackallratio"] . "% ~ " . $fullbackgoods["maxallfullbackallratio"] . "%";
                    }
                    if( $fullbackgoods["minfullbackratio"] == $fullbackgoods["maxfullbackratio"] )
                    {
                        $activity["fullback"]["enjoy"] = price_format($fullbackgoods["minfullbackratio"], 2) . "%";
                    }
                    else
                    {
                        $activity["fullback"]["enjoy"] = price_format($fullbackgoods["minfullbackratio"], 2) . "% ~ " . price_format($fullbackgoods["maxfullbackratio"], 2) . "%";
                    }
                }
                else
                {
                    $activity["fullback"]["all_enjoy"] = $fullbackgoods["minallfullbackallratio"] . "%";
                    $activity["fullback"]["enjoy"] = price_format($fullbackgoods["fullbackratio"], 2) . "%";
                }
            }
            else
            {
                if( 0 < $goods["hasoption"] )
                {
                    if( $fullbackgoods["minallfullbackallprice"] == $fullbackgoods["maxallfullbackallprice"] )
                    {
                        $activity["fullback"]["all_enjoy"] = "￥" . $fullbackgoods["minallfullbackallprice"];
                    }
                    else
                    {
                        $activity["fullback"]["all_enjoy"] = "￥" . $fullbackgoods["minallfullbackallprice"] . " ~ ￥" . $fullbackgoods["maxallfullbackallprice"];
                    }
                    if( $fullbackgoods["minfullbackprice"] == $fullbackgoods["maxfullbackprice"] )
                    {
                        $activity["fullback"]["enjoy"] = "￥" . price_format($fullbackgoods["minfullbackprice"], 2);
                    }
                    else
                    {
                        $activity["fullback"]["enjoy"] = "￥" . price_format($fullbackgoods["minfullbackprice"], 2) . " ~ ￥" . price_format($fullbackgoods["maxfullbackprice"], 2);
                    }
                }
                else
                {
                    $activity["fullback"]["all_enjoy"] = "￥" . $fullbackgoods["minallfullbackallprice"];
                    $activity["fullback"]["enjoy"] = "￥" . price_format($fullbackgoods["fullbackprice"], 2);
                }
            }
            if( 0 < $goods["hasoption"] )
            {
                if( $fullbackgoods["minday"] == $fullbackgoods["maxday"] )
                {
                    $activity["fullback"]["day"] = $fullbackgoods["minday"];
                }
                else
                {
                    $activity["fullback"]["day"] = $fullbackgoods["minday"] . " ~ " . $fullbackgoods["maxday"];
                }
            }
            else
            {
                $activity["fullback"]["day"] = $fullbackgoods["day"];
            }
            if( 0 < $fullbackgoods["startday"] )
            {
                $activity["fullback"]["startday"] = $fullbackgoods["startday"];
            }
        }
        //商品活动
        $goods["activity"] = $activity;
        //城市配送状态
        $goods["city_express_state"] = 1;
        $city_express = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_city_express") . " WHERE uniacid=:uniacid and merchid=0 limit 1", array( ":uniacid" => $_W["uniacid"] ));
        if( empty($city_express) || $city_express["enabled"] == 0 || 0 < $goods["merchid"] || $goods["type"] != 1 )
        {
            $goods["city_express_state"] = 0;
        }
        //type  == 9  我也不知道是啥意思
        if( $goods["type"] == 9 )
        {
            $cycelset = m("common")->getSysset("cycelbuy");
            $goods["ahead_goods"] = $cycelset["ahead_goods"];
            $goods["scope"] = $cycelset["days"];
            $ahead = $cycelset["ahead_goods"] * 86400;
            $goods["showDate"] = date("Ymd", time() + $ahead);
        }
        //最小价格 和 最大价格
        $minprice = $goods["minprice"];
        $maxprice = $goods["maxprice"];
        if( 0 < $goods["hasoption"] )
        {
            //商品  的 原价
            $productprice = pdo_fetchcolumn("select max(productprice) as productprice from " . tablename("ewei_shop_goods_option") . " where goodsid = :goodsid", array( ":goodsid" => $id ));
            if( !empty($productprice) )
            {
                $goods["productprice"] = $productprice;
            }
        }
        //秒杀的话
        if( $seckillinfo && $seckillinfo["status"] == 0 && 0 < count($seckillinfo["options"]) && !empty($options) )
        {
            foreach( $options as &$option )
            {
                //秒杀的规格价格 等于商品现价
                foreach( $seckillinfo["options"] as $so )
                {
                    if( $option["id"] == $so["optionid"] )
                    {
                        $option["marketprice"] = $so["price"];
                    }
                }
            }
            unset($option);
        }
        $goods["minprice"] = number_format($minprice, 2);
        $goods["maxprice"] = number_format($maxprice, 2);
        //判断是否在赏金任务内
        $merchid=$goods["merchid"];
        if ($merchid==0){
            $goods["reward"]=0;
            $goods["share_price"]=0;
            $goods["click_price"]=0;
            $goods["commission"]=0;
        }else{
            $merch=pdo_get("ewei_shop_merch_user",array('id'=>$merchid));
            if ($merch["reward_type"]==0){
                $goods["reward"]=0;
                $goods["share_price"]=0;
                $goods["click_price"]=0;
                $goods["commission"]=0;
            }else{
                if ($merch["reward_type"]==1){
                    //指定商品
                    //获取商家赏金
                    $reward=pdo_fetchall('select * from'.tablename('ewei_shop_merch_reward').'where is_end=0 and type=1 and merch_id=:merchid',array(':merchid'=>$merchid));

                    $g=array();
                    if (!empty($reward)){
                        foreach ($reward as $k=>$v){
                            $g[$k]["reward_id"]=$v["id"];
                            $g[$k]["goodsid"]=unserialize($v["goodid"]);
                        }
                    }
                    if (!empty($g)){
                        $reward_id=m("merch")->order_good($g,$id);
                        if ($reward_id){
                            $r=pdo_get("ewei_shop_merch_reward",array('id'=>$reward_id));
                            $goods["reward"]=1;
                            $goods["share_price"]=$r["share_price"];
                            $goods["click_price"]=$r["click_price"];
                            $goods["commission"]=$r["commission"]*$goods["maxprice"]/100;
                        }else{
                            $goods["reward"]=0;
                            $goods["share_price"]=0;
                            $goods["click_price"]=0;
                            $goods["commission"]=0;
                        }

                    }else{
                        $goods["reward"]=0;
                    }
                }else{
                    //全部商品
                    $reward=pdo_get("ewei_shop_merch_reward",array("merch_id"=>$merchid,"is_end"=>0,"type"=>2));
                    if ($reward){
                        $goods["reward"]=1;
                        $goods["share_price"] = $reward["share_price"];
                        $goods["click_price"] = $reward["click_price"];
                        $goods["commission"] = $reward["commission"]*$goods["maxprice"]/100;
                    }else{
                        $goods["reward"]=0;
                        $goods["share_price"]=0;
                        $goods["click_price"]=0;
                        $goods["commission"]=0;
                    }
                }
            }
        }
        $goods['showshare'] = 0;
        //商品的展示价格
        $goods['showprice'] = sprintf('%.2f',$minprice-$goods['deduct']);
        //商品虚拟销量和真实销量
        $goods['sales'] = intval($goods['sales']);
        $goods['salesreal'] = intval($goods['salesreal']);
        //s商品库存
        $goods['total'] = intval($goods['total']);
        return ['status'=>0,'msg'=>$goods];
    }

    /**
     * 加入购物车
     * @param $user_id
     * @param $id
     * @param $optionid
     * @param int $total
     * @return array
     */
    public function shop_add_cart($user_id,$id,$optionid,$total = 1)
    {
        global $_W;
        $member = m('member')->getMember($user_id);
        $goods = pdo_fetch('select id,marketprice,`type`,total,diyformid,diyformtype,diyfields, isverify,merchid,cannotrefund,hasoption from ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        if (empty($goods))
        {
            return ['status'=>AppError::$GoodsNotFound];
        }
        if ((0 < $goods['hasoption']) && empty($optionid))
        {
            return ['status'=>1, 'msg'=>'请选择规格!'];
        }
        if ($goods['total'] < $total)
        {
            $total = $goods['total'];
        }
        if (($goods['isverify'] == 2) || ($goods['type'] == 2) || ($goods['type'] == 3) || ($goods['type'] == 5) || !(empty($goods['cannotrefund'])))
        {
            return ['status'=>AppError::$NotAddCart];
        }
        $diyform_plugin = p('diyform');
        $diyformfields = iserializer(array());
        if ($diyform_plugin)
        {
            $diyformfields = false;
            if ($goods['diyformtype'] == 1)
            {
                $diyformid = intval($goods['diyformid']);
                $formInfo = $diyform_plugin->getDiyformInfo($diyformid);
                if (!(empty($formInfo)))
                {
                    $diyformfields = $formInfo['fields'];
                }
            }
            else if ($goods['diyformtype'] == 2)
            {
                $diyformfields = iunserializer($goods['diyfields']);
            }
            if (!(empty($diyformfields)))
            {
                $diyformfields = iserializer($diyformfields);
            }
        }
        $data = pdo_fetch('select id,total,diyformid from ' . tablename('ewei_shop_member_cart') . ' where goodsid=:id and (openid=:openid or user_id = :user_id) and optionid=:optionid and deleted=0 and uniacid=:uniacid   limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid'],  ':user_id' => $member['id'], ':optionid' => $optionid, ':id' => $id));
        if (empty($data))
        {
            $data = array('uniacid' => $_W['uniacid'], 'merchid' => $goods['merchid'], 'openid' => $member['openid'],'user_id' => $member['id'], 'goodsid' => $id, 'optionid' => $optionid, 'marketprice' => $goods['marketprice'], 'total' => $total, 'selected' => 1,  'diyformfields' => $diyformfields, 'createtime' => time());
            pdo_insert('ewei_shop_member_cart', $data);
        }
        else
        {
            $data['diyformfields'] = $diyformfields;
            $data['total'] += $total;
            pdo_update('ewei_shop_member_cart', $data, array('id' => $data['id']));
        }
        $cartcount = pdo_fetchcolumn('select sum(total) from ' . tablename('ewei_shop_member_cart') . ' where (openid=:openid or user_id = :user_id) and deleted=0 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid'], ':user_id' => $member['id']));
        return ['status'=>0,'msg' =>['cartcount'=>$cartcount]];
    }


    public function shop_goods_comments()
    {

    }

    /**
     * 活动分类的列表
     * @param $id
     * @param $keywords
     * @param int $page
     * @param int $type
     * @param string $sort
     * @return array
     */
    public function shop_cate_list($id,$keywords,$page = 1,$type = 3,$sort = "desc")
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $banner = pdo_fetchall(' select * from '.tablename('ewei_shop_icon_banner').' where uniacid = :uniacid and status = 1 and icon_id = :icon_id',['uniacid'=>$uniacid,'icon_id'=>$id]);
        if($type == 3){    //综合
            $args = array( "pagesize" =>9, "page" => $page, "order" =>'displayorder desc,(minprice-deduct) asc,deduct desc,sales desc' );
        }elseif ($type==2){   //价格
            $args = array( "pagesize" =>9, "page" => $page, "order" =>'(minprice-deduct) '.$sort.',deduct '.$sort);
        }elseif ($type==1){   //销量
            $args = array( "pagesize" =>9, "page" => $page, "order" =>'sales '.$sort.',(minprice-deduct) '.$sort.',deduct '.$sort );
        }else{   //最新
            $args = array( "pagesize" =>9, "page" => $page, "order" =>'id '.$sort.',(minprice-deduct) '.$sort.',deduct '.$sort );
        }
        $data = m('shop')->get_cate_list($id,$keywords,$args);
        return ['data'=>$data,'banner'=>$banner];
    }

    public function shop_shop_list($user_id,$type,$page = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $pageize = 10;
        $pindex = ($page - 1) * $pageize;
        $condition = " m.uniacid = :uniacid and m.status = 1 and m.member_id != 0 and mc.status = 1";
        $params = [":uniacid"=>$uniacid];
        if($type == 1){
            //店铺名 店铺logo 文章分类  文章标题  文章描述  文章的商品  文章的等级
            $list = pdo_fetchall("select m.merchname,m.logo,m.merchlevel,mc.id,mc.cid,mc.title,mc.descript,mc.content,mc.goods_id from ".tablename("ewei_shop_merch_choice")." mc left join ".tablename('ewei_shop_merch_user')." m on m.id = mc.mer_id where ".$condition." order by m.isrecommand desc,mc.createtime desc limit ".$pindex.",".$pageize,$params);
            set_medias($list,'logo');
            foreach ($list as $key => $value){
                $list[$key]['cate'] = $value['cid'] == 0 ? "" : pdo_getcolumn('ewei_shop_merch_choice_cate',['id'=>$value['cid'],'status'=>1],'cate');
                $list[$key]['levelname'] = $value['merchlevel'] == 0 ? "" : pdo_getcolumn('ewei_shop_merch_level',['id'=>$value['merchlevel'],'status'=>1],'levelname');
                $goods_id = explode(',',$value['goods_id']);
                foreach ($goods_id as $item){
                    $list[$key]['goods'][] = pdo_fetch('select title,marketprice,productprice,thumb from '.tablename('ewei_shop_goods').' where id = "'.$item.'" and status = 1 and deleted = 0');
                }
                $list[$key]['fav'] = pdo_fetchcolumn(' select count(1) from '.tablename('ewei_shop_merch_choice_fav').' where (openid = :openid or user_id = :user_id) and chid = :chid and status = 1 and uniacid = :uniacid',[':uniacid'=>$uniacid,':openid'=>$member['openid'],':user_id'=>$member['id'],':chid'=>$value['id']]);
                $list[$key]['comment'] = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_merch_choice_comment').' where parent_id = "'.$value['id'].'" and type = 1');
            }
        }elseif ($type == 2){
            $condition .= " and (f.openid = :openid or f.user_id = :user_id)";
            $sql = "";
        }elseif ($type == 3){
            $condition .= "";
            $sql = "";
        }

    }
}

?>
