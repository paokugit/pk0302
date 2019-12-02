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
	    global $_W;
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
        $gifts = pdo_fetchall(' select id,title,levels from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$uniacid.'"');
        $gift = m('game')->get_gift($gifts,$member['openid']);
        $data['gift'] = $gift ? 1 : 0;
		//未领取的气泡
        if (empty($member['agentlevel'])) {
            //普通会员的情况
            //$subscription_ratio = 0.5;   //卡路里
            $subscription_ratio = 0.5 * 2;  //折扣宝
            $exchange = 0.5/1500;
            $exchange_step = m("member")->exchange_step($user_id);
            $bushu = ceil($exchange_step*1500/0.5);
        } else {
            $memberlevel = pdo_get('ewei_shop_commission_level', array('id' => $member['agentlevel']));
            //$subscription_ratio = $memberlevel["subscription_ratio"];    //卡路里
            $subscription_ratio = $memberlevel["subscription_ratio"] * 2;  //折扣宝
            $exchange = $subscription_ratio/1500;
            $exchange_step = m("member")->exchange_step($user_id);
            $bushu = ceil($exchange_step*1500/$subscription_ratio);
        }
        $jinri = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  openid=:openid and type!=:type and status=1 ", array(':today' => $day, ':openid' => $_W['openid'],':type'=>2));
        //获取今日已兑换的卡路里
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $cardtoday = pdo_fetchcolumn("select sum(num) from ".tablename("ewei_shop_member_credit_record")." where `createtime` >= :beginToday and `createtime` <= :endToday and (user_id = :user_id or openid = :openid) and credittype = :credittype and (remark like :remark1 or remark like :remark2)",array(":beginToday"=>$beginToday,":endToday"=>$endToday,":credittype"=>"credit3",":user_id"=>$member['id'],":openid"=>$member['openid'],":remark1"=>'%步数兑换%',":remark2"=>'%好友助力%'));
        $step_number = $jinri = empty($cardtoday) ? 0 : $cardtoday*1500/$subscription_ratio;
        if ($step_number < $bushu) {
            $datault = pdo_fetchall("select * from ".tablename("ewei_shop_member_getstep")." where day = :day and (user_id = :user_id or openid = :openid) and status = 0 order by step asc",array(":day"=>$day,":user_id"=>$member['id'],':openid'=>$member['openid']));
        }else{
            $datault = pdo_fetchall("select * from ".tablename("ewei_shop_member_getstep")." where day = :day and (user_id = :user_id or openid = :openid) and status = 0 and type = 2 order by step asc",array(":day"=>$day,":user_id"=>$member['id'],':openid'=>$member['openid']));
        }
        //var_dump($datault);
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
                            $r[$i]["step"] = $bushu - $step_number;
                            $card1 = ($bushu - $step_number) * $exchange;
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
                            $card1 = $vv["step"] * $exchange;
                            if ($card1 > 0.01){
                                $r[$i]["currency"] = round($card1,2);
                            }else{
                                $r[$i]["currency"] = round($card1,4);
                            }
                            $step_number = $step_number + $vv["step"];
                            $r[$i]["type"] = $vv["type"];
                        }
                        $i = $i+1;
                    }
                }else{
                    $r[$i]["id"] = $vv["id"];
                    $r[$i]["step"] = $vv["step"];
                    //$r[$i]["currency"] = 1;  //卡路里
                    $r[$i]["currency"] = 2;  //折扣宝
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
        $list = pdo_get("ewei_shop_small_set",array("id"=>1));
        $l["backgroup"] = tomedia($list["backgroup"]);
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
        $pageSize = 10;
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
        $list = pdo_fetchall("select id,thumb from ".tablename("ewei_shop_adsense")." where type=:type order by sort desc",array(":type"=>$type));
        foreach ($list as $k=>$v){
            $list[$k]["thumb"]=tomedia($v["thumb"]);
            $list[$k]['url'] = strpos($v['url'],"member_card") == false ? : $member['is_open'] == 1 ? $v['url'] : "/pages/annual_card/equity/equity";
        }
        $data["banner"]=$list;
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
        $memberMerchInfo = $merch = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where member_id = :member_id Limit 1', array(':member_id' => 150));
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
        $args['deduct_type'] = 2;
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
     * @param $page
     * @return array
     */
    public function seckill($type = 1,$page = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $pageSize = 8;
        $pindex = ($page - 1) * $pageSize;
        $condition = "";
        //疯狂抢购中
        $time = time();
        if($type == 1){
            $condition .= " and uniacid = '".$uniacid."' and deleted = 0 and istime = 1 and status > 0 and timestart < '".$time."' and timeend > '".$time."'";
        }else{
            //即将开始
            $condition .= " and uniacid = '".$uniacid."' and istime = 1 and  deleted = 0 and status > 0 and timestart > '".$time."'";
        }
        $total = pdo_fetchcolumn('select count(*) as count from '.tablename('ewei_shop_goods').'where 1' .$condition);
        $list = pdo_fetchall('select id,title,thumb,productprice,marketprice,deduct,deduct_type,istime,timestart,timeend,sales,total,salesreal from '.tablename('ewei_shop_goods').' where 1' . $condition .'order by id desc LIMIT '.$pindex.','.$pageSize);
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
        $pagetotal = ceil($total/$pageSize);
        return ['list'=>$list,'end_time'=>$down_time,'type'=>$type,'total'=>$total,'pagesize'=>$pageSize,'page'=>$page,'pagetotal'=>$pagetotal];
    }

    /**
     * 边看边买
     * @return array
     */
    public function look_buy()
    {
        //查看有视频的  有库存的  在售的所有商品
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_look_buy').'where status = 1 order by displayorder desc,id desc limit 8 ');
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
     * 边看边买的详情
     * @param $user_id
     * @param $id
     * @param $type
     * @return array
     */
    public function look_buy_detail($user_id,$id,$type)
    {
        $member = m('member')->getMember($user_id);
        //如果商品id存在
        if(!empty($id)){
            //没有上看下凑的类型  就是查看  点击进去的商品
            if(empty($type)){
                $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where id = :id  and status = 1',[':id'=>$id]);
            }else{
                $now = pdo_get('ewei_shop_look_buy',['id'=>$id]);
                if($type == "up"){
                    $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where status = 1 and (displayorder < :displayorder or id < :id) order by displayorder desc',[':displayorder'=>$now['displayorder'],':id'=>$now['id']]);
                }elseif($type == "down"){
                    //如果是下一条  就取当前这个商品  倒序  id大于当前商品
                    $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where status = 1 and (displayorder > :displayorder or id >:id) order by displayorder asc',[':displayorder'=>$now['displayorder'],':id'=>$now['id']]);
                }
            }
        }else{
            //如果商品id不存在  就倒序取第一个视频信息
            $detail = pdo_fetch('select * from '.tablename('ewei_shop_look_buy').'where status = 1 order by displayorder desc');
        }
        if(empty($detail)) return ['status'=>1,'msg'=>'视频获取失败','data'=>[]];
        //拿商品信息
        $goods = pdo_get('ewei_shop_goods',['id'=>$detail['goods_id']]);
        //商品价格  marketprice现价   productprice原价
        $detail['marketprice'] = $goods['marketprice'];
        $detail['productprice'] = $goods['productprice'];
        //评论
        $comment = pdo_fetchall('select oc.nickname,oc.content,oc.headimgurl from '.tablename('ewei_shop_order_comment').'oc join '.tablename('ewei_shop_order_goods').('g on g.goodsid = oc.goodsid').' where oc.goodsid = :goods_id and oc.level > 3',[':goods_id'=>$detail['goods_id']]);
        //点赞是否
        //$favorite = pdo_fetch('select * from '.tablename('ewei_shop_look_buy_zan').' where (openid = :openid or user_id = :user_id) and lid = :lid and status = 1 ',[':openid'=>$member['openid'],':user_id'=>$member['id'],':lid'=>$detail['id']]);
        $favorite = pdo_fetch('select * from '.tablename('ewei_shop_goods_zan').' where (openid = :openid or user_id = :user_id) and goodsid = :goodsid and status = 1 ',[':openid'=>$member['openid'],':user_id'=>$member['id'],':goodsid'=>$detail['goods_id']]);
        //如果没有记录  如果 有的话  status  == 0  就是0
        $detail['fav'] = empty($favorite) || $favorite['status'] == 0 ? 0 : 1;
        //点赞人数
        //$detail['fav_count'] = pdo_count('ewei_shop_look_buy_zan',['lid'=>$detail['id'],'status'=>1]);
        $detail['fav_count'] = pdo_count('ewei_shop_goods_zan',['goodsid'=>$detail['goods_id'],'status'=>1]);
        //视频信息
        $detail['video'] = tomedia($detail['video']);
        //边看边买对应的商品的销量
        $detail['sales'] = $goods['sales'] + $goods['salesreal'];
        //转换销量  和  点赞数量
        if($detail['sales'] > 9999){
            $detail['sales'] = $detail['sales']/10000 ."万";
        }
        if($detail['fav_count'] > 9999){
            $detail['fav_count'] = $detail['fav_count']/10000 ."W";
        }
        //视频缩略图的
        $detail['thumb'] = tomedia($detail['thumb']);
        return ['status'=>0,'msg'=>'','data'=>['detail'=>$detail,'comment'=>$comment]];
    }

    /**
     * 边看边买的点赞
     * @param $user_id
     * @param $look_id
     * @return array
     */
    public function look_buy_zan($user_id,$look_id)
    {
        $member = m('member')->getMember($user_id);
        //$zan = pdo_fetch('select * from '.tablename('ewei_shop_look_buy_zan').' where (openid = :openid or user_id = :user_id) and lid = :look_id ',[':openid'=>$member['openid'],':user_id'=>$member['id'],':look_id'=>$look_id]);
        //$look_id  如果是点赞视频就是 视频id   如果是点赞商品 就是商品id
        $zan = pdo_fetch('select * from '.tablename('ewei_shop_goods_zan').' where (openid = :openid or user_id = :user_id) and goodsid = :goodsid ',[':openid'=>$member['openid'],':user_id'=>$member['id'],':goodsid'=>$look_id]);
        if(!empty($zan)){
            $status = $zan['status'] == 1 ? 0 : 1;
            $msg = $zan['status'] == 1 ? "取消点赞成功" : "点赞成功";
            pdo_update('ewei_shop_look_buy_zan',['status'=>$status],['id'=>$zan['id']]);
        }else{
            $status = 1;
            $msg = "点赞成功";
            //点赞视频
            //pdo_insert('ewei_shop_look_buy_zan',['status'=>1,'openid'=>$member['openid'],'user_id'=>$member['id'],'uniacid'=>1,'lid'=>$look_id,'createtime'=>time()]);
            pdo_insert('ewei_shop_goods_zan',['status'=>1,'openid'=>$member['openid'],'user_id'=>$member['id'],'uniacid'=>1,'goodsid'=>$look_id,'createtime'=>time()]);
        }
        return ['status'=>0,'msg'=>$msg,'data'=>[]];
    }
    /**
     * 每日一读
     * @param int $page
     * @return array
     */
    public function every($page = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $pageSize = 8;
        $pindex = ($page - 1) * $pageSize;
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_member_reading').' where uniacid = :uniacid ',[':uniacid'=>$uniacid]);
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_member_reading').'where uniacid = :uniacid order by id desc limit '.$pindex.','.$pageSize,[':uniacid'=>$uniacid]);
        foreach ($list as $key => $item){
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
            $list[$key]['img'] = tomedia($item['img']);
            $list[$key]['detail_img'] = tomedia($item['detail_img']);
            $list[$key]['music'] = tomedia($item['create_time']);
        }
        $pagetotal = ceil($total/$pageSize);
        return ['list'=>$list,'page'=>$page,'pagesize'=>$pageSize,'pagetotal'=>$pagetotal,'total'=>$total];
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
        foreach ($list as $key=>$value) {
            $list[$key]['thumb'] = tomedia($value['thumb']);
            $list[$key]['image'] = tomedia($value['image']);
        }
        return $list;
    }

    /**
     * 领取折扣宝 或者 卡路里
     * @param $user_id
     * @param int $step_id
     * @param $credit  要领取的币种  credit3
     * @return array
     */
    public function getcredit($user_id,$step_id = 0,$credit = "credit3")
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        if(empty($step_id)) return ['status'=>1,'msg'=>'领取失败','data'=>[]];
        //获取用户信息
        $member = m('member')->getMember($user_id);
        //获得用户可以兑换的卡路里
        $exchange_step = m("member")->exchange_step($user_id);
        //获取要领取的卡路里  对应的数据
        $step = pdo_fetch('select * from '.tablename('ewei_shop_member_getstep').'where id = :id and uniacid = :uniacid',[':id'=>$step_id,'uniacid'=>$uniacid]);
        if($step['status'] == 1){
            return ['status'=>1,'msg'=>'已领取不可重复领取','data'=>[]];
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
        $exchange = $subscription_ratio / 1500 * 2;
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
            m('member')->setCredit($user_id, $credit, $keduihuan, "步数兑换");
        }elseif ($step["type"]==1){
            m('member')->setCredit($user_id, $credit, $keduihuan, "好友助力");
        }
        pdo_update('ewei_shop_member_getstep', array('status' => 1), array('id' => $step_id));
        return ['status'=>0,'msg'=>'领取成功','data'=>[]];
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
     * @param $page
     * @param $type
     * @return array
     */
    public function rebate_record($user_id,$page = 1,$type = 1)
    {
        //查该用户是不是有商家
        $merch_user = pdo_get('ewei_shop_merch_user',['member_id'=>$user_id]);
        //$type == 1  个人收款码记录    == 2  商家收款记录
        $mch_id = $type == 1 ? $user_id."own" : $merch_user['id'];
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        if($type == 2){
            $total = pdo_fetch('select count(1) as count,ifnull(sum(price),0) as total_money from '.tablename('ewei_shop_merch_log').' where status = 1 and merchid = "'.$mch_id.'"  and price > 0 and cate = 2 ');
            $list = pdo_fetchall('select id,user_id,openid,price,createtime,cate from '.tablename('ewei_shop_merch_log').' where status = 1 and merchid = "'.$mch_id.'"  and price > 0 and cate = 2 order by createtime desc limit '.$pindex.','.$pageSize);
        }else{
            $total = pdo_fetch('select count(1) as count,ifnull(sum(money),0) as total_money from '.tablename('ewei_shop_member_log').' where status = 1 and rechargetype = "'.$mch_id.'"  and money > 0 ');
            $list = pdo_fetchall('select id,user_id,openid,money as price,createtime from '.tablename('ewei_shop_member_log').' where status = 1 and rechargetype = "'.$mch_id.'"  and money > 0 order by createtime desc limit '.$pindex.','.$pageSize);
        }
        //取出第一个日期
        $time = date('Y年m月d日',$list[0]['createtime']);
        //数量 = 0  金额 = 0
        $count = 0;
        $total_money = 0;
        $i = 0;
        $j = 0;
        $data = [];
        foreach ($list as $key => &$item) {
            //当日期时间
            if($time == date('Y年m月d日',$item['createtime'])){
                $data[$i]['time'] = date('Y年m月d日',$item['createtime']);
                $count += 1;
                $data[$i]['total'] = $count;
                $total_money = bcadd($total_money,$item['price'],2);
                $data[$i]['total_money'] = $total_money;
                $data[$i]['list'][$j]['id'] = $item['id'];
                $data[$i]['list'][$j]['user_id'] = $item['user_id'];
                $data[$i]['list'][$j]['openid'] = $item['openid'];
                $data[$i]['list'][$j]['price'] = $item['price'];
                $data[$i]['list'][$j]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
                $nickname = pdo_fetchcolumn('select nickname from '.tablename('ewei_shop_member').'where openid = :openid or id = :user_id ',[':openid'=>$item['openid'],':user_id'=>$item['user_id']]);
                $data[$i]['list'][$j]['nickname'] = $nickname ? $nickname : "未设置昵称";
                $j += 1;
            }else{
                $count = 0;
                $total_money = 0;
                $j = 0;
                $i+=1;
                $data[$i]['time'] = date('Y年m月d日',$item['createtime']);
                $count += 1;
                $data[$i]['total'] = $count;
                $total_money = bcadd($total_money,$item['price'],2);
                $data[$i]['total_money'] = $total_money;
                $data[$i]['list'][$j]['id'] = $item['id'];
                $data[$i]['list'][$j]['user_id'] = $item['user_id'];
                $data[$i]['list'][$j]['openid'] = $item['openid'];
                $data[$i]['list'][$j]['price'] = $item['price'];
                $data[$i]['list'][$j]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
                $nickname = pdo_fetchcolumn(' select nickname from '.tablename('ewei_shop_member').'where openid = :openid or id = :user_id ',[':openid'=>$item['openid'],':user_id'=>$item['user_id']]);
                $data[$i]['list'][$j]['nickname'] = $nickname ? $nickname : "未设置昵称";
                $time = date('Y年m月d日',$item['createtime']);
                $j += 1;
            }
        }
        $pagetotal = ceil($total['count']/$pageSize);
        return ['list'=>$data,'total'=>$total['count'],'pagesize'=>$pageSize,'page'=>$page,'pagetotal'=>$pagetotal,'total_money'=>$total['total_money']];
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
        return ['status'=>0,'msg'=>$msg,'data'=>[]];
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
        return ['list'=>$list,'pagesize'=>$pageSize,'total'=>$total,'page'=>$page];
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
            return ['status'=>1,'msg'=>'暂无折扣信息','data'=>[]];
        }
        //到这个时候 应该是  折扣信息数大于0  且 输入的金额大于最小金额
        if(!$list){
            return ['status'=>1,'msg'=>"暂无符合的折扣优惠",'data'=>[]];
        }
        return ['status'=>0,'msg'=>'','data'=>$list];
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
            return ['status'=>1,'msg'=>"资金余额不足",'data'=>[]];
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
        return ['status'=>0,'msg'=>'提现成功','data'=>[]];
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
            return ['status'=>1,'msg'=>'商户信息错误','data'=>[]];
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
            return array('status'=>1,'msg'=> '您没有可提现的金额','data'=>[]);
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
        return ['status'=>0,'msg'=>"提现申请成功",'data'=>[]];
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
        $data = ['list'=>$list,'total'=>$total,'page'=>$page,'pagesize'=>$pageSize];
        return ['status'=>0,'msg'=>'','data'=>$data];
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
            return ['status'=>1,'msg'=>'商户信息错误','data'=>[]];
        }
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_merch_bill').'where uniacid = :uniacid and merchid = :merchid',[':uniacid'=>$uniacid,':merchid'=>$merch_user['id']]);
        $list = pdo_getall('ewei_shop_merch_bill','merchid="'.$merchid.'" and uniacid="'.$uniacid.'" and type = 1 order by id desc LIMIT '.$pindex.','.$pageSize,['id','realprice','realpricerate','status','applytime']);
        foreach ($list as $key=>$item){
            $list[$key]['applytime'] = date('Y-m-d H:i:s',$item['applytime']);
            $list[$key]['title'] = "资金提现";
        }
        $data = ['list'=>$list,'total'=>$total,'page'=>$page,'pagesize'=>$pageSize];
        return ['status'=>0,'msg'=>'','data'=>$data];
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
        $fields = "id,num,createtime,remark,openid,user_id";
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
        return ['credit3'=>$credit3,'list'=>$list,'page'=>$page,'pagesize'=>$pageSize,'total'=>$total,'type'=>$type];
    }

    /**
     * @param $user_id
     * @param $id
     * @return bool
     */
    public function rebate_detail($user_id,$id)
    {
        $member = m('member')->getMember($user_id);
        $data = pdo_fetch('select openid,num,createtime,remark,merchid from '.tablename('mc_credits_record').' where id=:id and (openid=:openid or user_id = :user_id) ',[':id'=>$id,':openid'=>$member['openid'],':user_id'=>$member['id']]);
        $data['createtime'] = date('Y-m-d H:i:s',$data['createtime']);
        if($data['merchid'] != 0){
            $data['merch_name'] = pdo_getcolumn('ewei_shop_merch_user',['id'=>$data['merchid']],'merchname');
        }else{
            if(mb_substr($data['remark'],0,2) == "跑库"){
                $data['merch_name'] = "跑库";
                $data['remark'] = "商城订单";
            }elseif(mb_substr($data['remark'],0,2) == "转帐"){
                $mobile = preg_replace('/\D/s','',$data['remark']);
                $data['merch_name'] = pdo_getcolumn('ewei_shop_member',['mobile'=>$mobile],'nickname');
            }elseif (mb_substr($data['remark'],0,2) == "RV" || mb_substr($data['remark'],0,2) == "外部"){
                $data['merch_name'] = "RV钱包";
            }else{
                $data['merch_name'] = pdo_fetchcolumn('select nickname from '.tablename('ewei_shop_member').' where openid = :openid or id = :user_id ',[':openid'=>$member['openid'],':user_id'=>$member['id']]);
            }
        }
        return $data;
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
            return ['status'=>1,'msg'=>'充值金额不能为0','data'=>[]];
        }elseif($money > $member['credit1']){
            return ['status'=>1,'msg'=>'您的卡路里不足','data'=>[]];
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
            return ['status'=>0,'msg'=>'转换成功','data'=>[]];
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
            return ['status'=>1,'msg'=>"提现金额不可小于1元",'data'=>[]];
        }
        if ($member["credit3"] < $money || $member["credit4"] < $money){
            return ['status'=>1,'msg'=>"提现余额或贡献值不足",'data'=>[]];
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
        return ['status'=>0,'msg'=>"成功",'data'=>[]];
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
        if($mobile == "" || $money < 1){
            return ['status'=>1,'msg'=>"参数错误",'data'=>[]];
        }
        //收款人信息
        $to = pdo_get('ewei_shop_member',['mobile'=>$mobile,'uniacid'=>$uniacid]);
        //转账人信息
        $member = m('member')->getMember($user_id);
        if(bccomp($member['credit3'],$money,2) == -1){
            return ['status'=>1,'msg'=>"用户余额不足",'data'=>[]];
        }
        if(!$to){
            return ['status'=>1,'msg'=>"收款人不存在",'data'=>[]];
        }
        if($to['openid'] == $member['openid']){
            return ['status'=>1,'msg'=>'转账者和收款人相同','data'=>[]];
        }
        //更新转账者折扣宝余额   减去  并写入日志
        pdo_update('ewei_shop_member',['credit3'=>bcsub($member['credit3'],$money,2)],['openid'=>$member['openid'],'uniacid'=>$uniacid]);
        m('payment')->addlog($member,$to,$money,1);
        //更新收款者  折扣宝余额   加上  并写入日志
        pdo_update('ewei_shop_member',['credit3'=>bcadd($to['credit3'],$money,2)],['openid'=>$to['openid'],'uniacid'=>$uniacid]);
        m('payment')->addlog($to,$member,$money,2);
        return ['status'=>0,'msg'=>'转账成功','data'=>[]];
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
     * @return array|string
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
        $gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where uniacid = "'.$uniacid.'" ');
        if(empty($gifts)) return ['status'=>1,'msg'=>"活动已关闭",'data'=>[]];
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
        return ['status'=>0,'msg'=>'','data'=>['notice'=>$notice,'share'=>$share,'goods'=>$goods,'all'=>$all,'desc'=>$gift['desc'],'help_count'=>$help_count,'new_member'=>$new,'remain'=>bcsub($target,$help_count) > 0 ? bcsub($target,$help_count) :0,'agent_level'=>$member['agentlevel'],'agentlevel'=>$agentlevel,'avatar'=>$member['avatar'],'gift'=>$gift['title'],'start'=>date('Y-m-d',$gift['starttime']),'end'=>date('Y-m-d',$gift['endtime']),'get_all'=>$get_all,'gets'=>$get,'week_start'=>date('m.d',$week['start']),'week_end'=>date('m.d',strtotime("-1s",$week['end']))]];
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
            return ['status'=>1,'msg'=>"支付金额不正确",'data'=>[]];
        }
        //把礼包的信息查出来  然后 把他的商品转译出来  判断 要领取的商品在不在其中
        $level = pdo_get('ewei_shop_member_memlevel',['id'=>$level_id,'uniacid'=>$uniacid]);
        $goods_id = unserialize($level['goods_id']);
        if(!in_array($good_id,$goods_id)){
            return ['status'=>1,'msg'=>"领取商品有误",'data'=>[]];
        }
        //把年里礼包的商品给查出来
        $goods = pdo_get('ewei_shop_goods','uniacid="'.$uniacid.'" and id="'.$good_id.'" and status = 1 and total > 0',['id','thumb','title','marketprice']);
        //查询该记录的信息
        $record = pdo_fetch('select * from '.tablename('ewei_shop_level_record').' where uniacid = :uniacid and level_id = :level_id and id = :record_id and (openid = :openid or user_id = :user_id)',[':uniacid'=>$uniacid,':level_id'=>$level_id,':record_id'=>$record_id,':openid'=>$member['openid'],':user_id'=>$member['id']]);
        //判断这个月的记录状态
        if($record['status'] > 0){
            return ['status'=>1,'msg'=>$record['month']."权利礼包已领取或过期",'data'=>[]];
        }
        //查询领取记录里面的已领过的状态
        $log = pdo_fetchall('select * from '.tablename('ewei_shop_level_record').'where uniacid = :uniacid and (openid = :openid or user_id = :user_id) and level_id = :level_id and status > 0',[':openid'=>$member['openid'],':user_id'=>$member['id'],':level_id'=>$level_id,':uniacid'=>$uniacid]);
        if(count($log) > 0 && (date('Ymd',time()) < $record['month']."10" || date('Ymd',time()) > $record['month']."21")){
            return ['status'=>1,'msg'=>$record['month']."权益礼包不在领取日期",'data'=>[]];
        }
        //生成订单号
        $order_sn = "LQ".$level_id.date('YmdHis').random(12);
        //添加订单
        $order_id = m('game')->addorder($member['openid'],$order_sn,$money,$address_id,"领取年卡".$record["month"]."权益",$goods);
        //如果是第一次支付   金额为零 不用唤醒支付  直接改变状态   然后 架订单的时候 也判断了  让status=1
        if($money == 0){
            pdo_update('ewei_shop_level_record',['goods_id'=>$good_id,'status'=>1,'updatetime'=>time()],['id'=>$record_id]);
            return ['status'=>0,'msg'=>"领取成功",'data'=>[]];
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
        $address = pdo_fetchall('select id,user_id,openid,realname,province,city,area,address,isdefault from '.tablename('ewei_shop_member_address').'where uniacid = :uniacid and (openid = :openid or user_id = :user_id) and deleted = 0 order by isdefault desc,id desc',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        if(!$address){
            return ['status'=>1,'msg'=>"暂无地址，请去添加地址",'data'=>[]];
        }
        if($type == 1){
            $data = m('game')->change_address($address[0]['id'],$member['openid'],$uniacid);
            return ['status'=>0,'msg'=>'','data'=>['data'=>$data,'address'=>$address]];
        }else{
            $data = m('game')->change_address($address_id,$member['openid'],$uniacid);
            return ['status'=>0,'msg'=>'','data'=>['data'=>$data]];
        }

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
        return ['record'=>$record,'total'=>$total,'page'=>$page,'pagesize'=>$pageSize];
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
        return ['list'=>$list,'total'=>$total,'page'=>$page,'pagesize'=>$pageSize];
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
        return ['total'=>$total,'page'=>$page,'pagesize'=>$pageSize,'list'=>$list];
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
        return ['status'=>0,'msg'=>$msg,'data'=>[]];
    }

    /**
     * 商城首页图标
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
        //头条
        $notice = pdo_fetchall('select id,title from '.tablename('ewei_shop_notice').'where status = 1 limit 5 ');
        return ['top'=>$top,'banner'=>$banner,'cate'=>$cate,'middle'=>$middle,'notice'=>$notice];
    }

    /**
     * 他的店
     * @return array
     */
    public function shop_shop()
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $shop = pdo_fetchall(' select m.id,m.merchname,m.logo,ml.levelname,mc.content,mc.createtime,mc.see from '.tablename('ewei_shop_merch_user').' m join '.tablename('ewei_shop_merch_level').' ml on ml.id = m.merchlevel join '.tablename('ewei_shop_merch_choice').' mc on m.id = mc.mer_id where m.uniacid = "'.$uniacid.'" and m.status = 1 and ml.status = 1 and mc.status = 1 order by m.isrecommand desc,mc.createtime desc limit 6 ');
        foreach ($shop as $key=>$value){
            $shop[$key]['createtime'] = date('Y-m-d',$value['createtime']);
            $shop[$key]['logo'] = tomedia($value['logo']);
            $shop[$key]['see'] = $value['see'] > 9999 ? ($value['see']/10000)."万" : $value['see'];
        }
        return $shop;
    }

    /**
     * 商品信息
     * @param int $type
     * @param string $sort
     * @param int $page
     * @return mixed
     */
    public function shop_shop_goods($type = 3,$sort = 'desc',$page = 1)
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
        $item['pagesize'] = 10;
        $item['pagetotal'] = ceil($item['total']/$item['pagesize']);
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
     * @param $deduct
     * @return array
     */
    public function shop_search($keywords,$cate,$page = 1,$isnew,$ishot,$isrecommand,$isdiscount,$istime,$issendfree,$order,$by,$deduct = 0)
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
                if (isset($deduct)) {
                    $goods_list[$index]["showprice"] = round($goods_list[$index]["minprice"] - $goods_list[$index]["deduct"], 2);
                }
            }
        }
        return array( "list" => $goods_list, "total" => $goods["total"], "pagesize" => $args["pagesize"] );
    }

    /**
     * 商品详情
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
        $goods['comment'] = pdo_fetchall('select * from '.tablename('ewei_shop_order_comment').' where goodsid = :goodsid and uniacid = :uniacid order by level desc limit 6',[':goodsid'=>$id,':uniacid'=>$uniacid]);
        $goods['content'] = htmlspecialchars_decode($goods['content']);
        return ['status'=>0,'msg'=>'','data'=>$goods];
    }

    public function shop_goods_options($user_id,$id)
    {
        global $_W;
        $member = m("member")->getMember($user_id, true);
        if( empty($id) ) return ['status'=>AppError::$ParamsError,'msg'=>'','data'=>[]];
        $seckillinfo = false;
        $seckill = p("seckill");
        if( $seckill )
        {
            $time = time();
            $seckillinfo = $seckill->getSeckill($id);
            if( !empty($seckillinfo) )
            {
                if( $seckillinfo["starttime"] <= $time && $time < $seckillinfo["endtime"] )
                {
                    $seckillinfo["status"] = 0;
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
        }
        $goods = pdo_fetch(" select id,thumb,title,marketprice,total,maxbuy,minbuy,unit,isdiscount,isdiscount_time,isdiscount_discounts,hasoption,showtotal,diyformid,diyformtype,diyfields, `type`, isverify, maxprice, minprice, merchsale,hascommission,nocommission,commission,commission1_rate,marketprice,commission1_pay,preselltimestart,presellovertime,presellover,ispresell,preselltimeend,presellprice from " . tablename("ewei_shop_goods") . " where id=:id and uniacid=:uniacid limit 1", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
        if( empty($goods) )  return ['status'=>AppError::$GoodsNotFound , 'msg'=>'' , 'data'=>[]];
        $goods = set_medias($goods, "thumb");
        $specs = array( );
        $options = array( );
        if( !empty($goods) && $goods["hasoption"] )
        {
            $specs = pdo_fetchall(" select * from " . tablename("ewei_shop_goods_spec") . " where goodsid=:goodsid and uniacid=:uniacid order by displayorder asc", array( ":goodsid" => $id, ":uniacid" => $_W["uniacid"] ));
            foreach( $specs as &$spec )
            {
                $spec["items"] = pdo_fetchall(" select *  from " . tablename("ewei_shop_goods_spec_item") . " where specid=:specid and `show`=1 order by displayorder asc", array( ":specid" => $spec["id"] ));
            }
            unset($spec);
            $options = pdo_fetchall(" select *  from " . tablename("ewei_shop_goods_option") . " where goodsid=:goodsid and uniacid=:uniacid order by displayorder asc", array( ":goodsid" => $id, ":uniacid" => $_W["uniacid"] ));
        }
        $minprice = $goods["minprice"];
        $maxprice = $goods["maxprice"];
        if( $goods["isdiscount"] && time() <= $goods["isdiscount_time"] )
        {
            $goods["oldmaxprice"] = $maxprice;
            $isdiscount_discounts = json_decode($goods["isdiscount_discounts"], true);
            $prices = array( );
            if( !isset($isdiscount_discounts["type"]) || empty($isdiscount_discounts["type"]) )
            {
                $level = m("member")->getLevel($user_id);
                $prices_array = m("order")->getGoodsDiscountPrice($goods, $level, 1);
                $prices[] = $prices_array["price"];
            }
            else
            {
                $goods_discounts = m("order")->getGoodsDiscounts($goods, $isdiscount_discounts, $levelid, $options);
                $prices = $goods_discounts["prices"];
                $options = $goods_discounts["options"];
            }
            $minprice = min($prices);
            $maxprice = max($prices);
            $goods["minprice"] = (double) $minprice;
            $goods["maxprice"] = (double) $maxprice;
        }
        if( $seckillinfo && $seckillinfo["status"] == 0 )
        {
            $goods["marketprice"] = $seckillinfo["price"];
            $minprice = $maxprice = $goods["marketprice"];
            if( 0 < count($seckillinfo["options"]) && !empty($options) )
            {
                foreach( $options as &$option )
                {
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
        }
        else
        {
            $minprice = $goods["minprice"];
            $maxprice = $goods["maxprice"];
        }
        if( 0 < $goods["ispresell"] && ($goods["preselltimeend"] == 0 || time() < $goods["preselltimeend"]) )
        {
            $goods["thistime"] = time();
            if( !empty($options) )
            {
                $presell = pdo_fetch(" select min(presellprice) as minprice,max(presellprice) as maxprice from " . tablename("ewei_shop_goods_option") . " where goodsid = " . $id);
                $minprice = $presell["minprice"];
                $maxprice = $presell["maxprice"];
            }
            $goods["presellstartstatus"] = true;
            $goods["presellendstatus"] = true;
            if( !empty($goods["preselltimestart"]) && time() < $goods["preselltimestart"] )
            {
                $goods["presellstartstatus"] = false;
                $goods["presellstatustitle"] = "预售未开始";
            }
            if( !empty($goods["preselltimeend"]) && $goods["preselltimeend"] < time() )
            {
                $goods["presellendstatus"] = false;
                $goods["presellstatustitle"] = "预售已结束";
            }
        }
        $goods["minprice"] = number_format($minprice, 2);
        $goods["maxprice"] = number_format($maxprice, 2);
        $clevel = pdo_fetch(" select * from " . tablename("ewei_shop_commission_level") . " where uniacid=:uniacid and id=:id limit 1", array( ":uniacid" => $_W["uniacid"], ":id" => $member["agentlevel"] ));
        $set = array( );
        if( p("commission") )
        {
            $set = p("commission")->getSet();
        }
        if( p("seckill") && p("seckill")->getSeckill($goods["id"]) )
        {
            $seecommission = 0;
        }
        if( 0 < $goods["bargain"] )
        {
            $seecommission = 0;
        }
        else
        {
            if( $goods["nocommission"] == 1 )
            {
                $seecommission = 0;
            }
            else
            {
                if( $goods["hascommission"] == 1 && $goods["nocommission"] == 0 && $member["isagent"] && $member["isagent"] )
                {
                    $price = $goods["maxprice"];
                    $levelid = "default";
                    if( $clevel == false )
                    {
                        $seecommission = 0;
                    }
                    else
                    {
                        if( $clevel )
                        {
                            $levelid = "level" . $clevel["id"];
                        }
                        $goods_commission = (!empty($goods["commission"]) ? json_decode($goods["commission"], true) : array( ));
                        if( $goods_commission["type"] == 0 )
                        {
                            $seecommission = (1 <= $set["level"] ? (0 < $goods["commission1_rate"] ? ($goods["commission1_rate"] * $goods["marketprice"]) / 100 : $goods["commission1_pay"]) : 0);
                            if( is_array($options) )
                            {
                                foreach( $options as $k => $v )
                                {
                                    $options[$k]["seecommission"] = $seecommission;
                                }
                            }
                        }
                        else
                        {
                            if( is_array($options) )
                            {
                                foreach( $goods_commission[$levelid] as $key => $value )
                                {
                                    foreach( $options as $k => $v )
                                    {
                                        if( "option" . $v["id"] == $key )
                                        {
                                            if( strexists($value[0], "%") )
                                            {
                                                $options[$k]["seecommission"] = floatval(str_replace("%", "", $value[0]) / 100) * $v["marketprice"];
                                                continue;
                                            }
                                            $options[$k]["seecommission"] = $value[0];
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                else
                {
                    if( $goods["hasoption"] == 1 && $goods["hascommission"] == 0 && $goods["nocommission"] == 0 && $member["isagent"] && $member["isagent"] )
                    {
                        foreach( $options as $ke => $vl )
                        {
                            if( $clevel != "false" && $clevel )
                            {
                                $options[$ke]["seecommission"] = (1 <= $set["level"] ? round(($clevel["commission1"] * $vl["marketprice"]) / 100, 2) : 0);
                            }
                            else
                            {
                                $options[$ke]["seecommission"] = (1 <= $set["level"] ? round(($set["commission1"] * $vl["marketprice"]) / 100, 2) : 0);
                            }
                        }
                    }
                    else
                    {
                        if( $clevel != "false" && $clevel )
                        {
                            $seecommission = (1 <= $set["level"] ? round(($clevel["commission1"] * $goods["marketprice"]) / 100, 2) : 0);
                        }
                        else
                        {
                            $seecommission = (1 <= $set["level"] ? round(($set["commission1"] * $goods["marketprice"]) / 100, 2) : 0);
                        }
                    }
                }
            }
        }
        $goods["cansee"] = $set["cansee"];
        if( !p("commission") )
        {
            $goods["cansee"] = 0;
        }
        $goods["seetitle"] = $set["seetitle"];
        $diyform_plugin = p("diyform");
        if( $diyform_plugin )
        {
            $fields = false;
            if( $goods["diyformtype"] == 1 )
            {
                if( !empty($goods["diyformid"]) )
                {
                    $diyformid = $goods["diyformid"];
                    $formInfo = $diyform_plugin->getDiyformInfo($diyformid);
                    $fields = $formInfo["fields"];
                }
            }
            else
            {
                if( $goods["diyformtype"] == 2 )
                {
                    $diyformid = 0;
                    $fields = iunserializer($goods["diyfields"]);
                    if( empty($fields) )
                    {
                        $fields = false;
                    }
                }
            }
            if( !empty($fields) )
            {
                $inPicker = true;
                $f_data = $diyform_plugin->getLastData(3, 0, $diyformid, $id, $fields, $member);
                $flag = 0;
                if( !empty($f_data) && is_array($f_data) )
                {
                    foreach( $f_data as $k => $v )
                    {
                        if( !empty($v) )
                        {
                            $flag = 1;
                            break;
                        }
                    }
                }
                if( empty($flag) )
                {
                    $f_data = $diyform_plugin->getLastCartData($id);
                }
            }
        }
        if( !empty($specs) )
        {
            foreach( $specs as $key => $value )
            {
                foreach( $specs[$key]["items"] as $k => &$v )
                {
                    $v["thumb"] = tomedia($v["thumb"]);
                }
                unset($v);
            }
        }
        $goods["canAddCart"] = 1;
        if( $goods["isverify"] == 2 || $goods["type"] == 2 || $goods["type"] == 3 )
        {
            $goods["canAddCart"] = 0;
        }
        unset($goods["diyformid"]);
        unset($goods["diyformtype"]);
        unset($goods["diyfields"]);
        if( !empty($options) && is_array($options) )
        {
            foreach( $options as $index => &$option )
            {
                $option_specs = $option["specs"];
                if( !empty($option_specs) )
                {
                    $option_specs_arr = explode("_", $option_specs);
                    array_multisort($option_specs_arr, SORT_ASC);
                    $option["specs"] = implode("_", $option_specs_arr);
                }
            }
        }
        unset($option);
        $appDatas = array( "fields" => array( ), "f_data" => array( ) );
        if( $diyform_plugin )
        {
            $appDatas = $diyform_plugin->wxApp($fields, $f_data, $member);
        }
        var_dump($goods,$seckillinfo,$specs,$options,$appDatas);exit;
        app_json(array( "goods" => $goods, "seckillinfo" => $seckillinfo, "specs" => $specs, "options" => $options, "diyform" => array( "fields" => $appDatas["fields"], "lastdata" => $appDatas["f_data"] ) ));
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
            return ['status'=>1, 'msg'=>'请选择规格!','data'=>[]];
        }
        if ($goods['total'] < $total)
        {
            $total = $goods['total'];
        }
        if (($goods['isverify'] == 2) || ($goods['type'] == 2) || ($goods['type'] == 3) || ($goods['type'] == 5) || !(empty($goods['cannotrefund'])))
        {
            return ['status'=>AppError::$NotAddCart,'msg'=>'','data'=>[]];
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
        //TODO  这个逻辑有点混乱   也不知道他的需求  如果设置了限购 他这个逻辑是错的   如果不限购  那这个逻辑是对的
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
        return ['status'=>0,'msg'=>'','data' =>['cartcount'=>$cartcount]];
    }

    /**
     * @param $id
     * @return array
     */
    public function shop_cate_banner($id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $banner = pdo_fetchall(' select * from '.tablename('ewei_shop_icon_banner').' where uniacid = :uniacid and status = 1 and icon_id = :icon_id',['uniacid'=>$uniacid,'icon_id'=>$id]);
        //set_medias($banner,'thumb');
        foreach ($banner as $key=>$value){
            $banner[$key]['thumb'] = tomedia($value['thumb']);
            $banner[$key]['createtime'] = date('Y-m-d H:i:s',$value['createtime']);
        }
        return ['banner'=>$banner];
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
        return $data;
    }

    /**
     * ta的店   推荐  关注  上新
     * @param $user_id
     * @param $type
     * @param int $page
     * @param int $merch_id
     * @return array
     */
    public function shop_shop_list($user_id,$type = 1,$page = 1,$merch_id  = 0)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        $condition = " m.uniacid = :uniacid and m.status = 1 and m.member_id != 0 and mc.status = 1";
        $params = [":uniacid"=>$uniacid];
        if(!empty($merch_id)){
            $condition .= " and mc.mer_id = :merchid ";
            $params[':merchid'] = $merch_id;
        }
        if($type == 1){
            //店铺名 店铺logo 文章分类  文章标题  文章描述  文章的商品  文章的等级
            $total = pdo_fetchcolumn("select count(1) from ".tablename("ewei_shop_merch_choice")." mc left join ".tablename('ewei_shop_merch_user')." m on m.id = mc.mer_id where ".$condition,$params);
            $list = pdo_fetchall("select m.merchname,m.logo,m.merchlevel,mc.id,mc.cid,mc.title,mc.descript,mc.content,mc.goods_id,mc.createtime,mc.see from ".tablename("ewei_shop_merch_choice")." mc left join ".tablename('ewei_shop_merch_user')." m on m.id = mc.mer_id where ".$condition." order by m.isrecommand desc,mc.createtime desc limit ".$pindex.",".$pageSize,$params);
        }elseif ($type == 2){
            $condition .= " and (f.openid = :openid or f.user_id = :user_id) and f.status = 1";
            $params[':user_id'] = $member['id'];
            $params[':openid'] = $member['openid'];
            $total = pdo_fetchcolumn(" select count(1) from ".tablename("ewei_shop_merch_choice")." mc left join ".tablename('ewei_shop_merch_user')." m on m.id = mc.mer_id join ".tablename('ewei_shop_merch_follow')." f on f.chid = mc.id where ".$condition,$params);
            $list = pdo_fetchall(" select m.merchname,m.logo,m.merchlevel,mc.id,mc.cid,mc.title,mc.descript,mc.content,mc.goods_id,mc.createtime,mc.see from ".tablename("ewei_shop_merch_choice")." mc left join ".tablename('ewei_shop_merch_user')." m on m.id = mc.mer_id join ".tablename('ewei_shop_merch_follow')." f on f.chid = mc.id where ".$condition." order by mc.createtime desc limit ".$pindex.",".$pageSize,$params);
        }elseif ($type == 3){
            $total = pdo_fetchcolumn("select count(1) from ".tablename("ewei_shop_merch_choice")." mc left join ".tablename('ewei_shop_merch_user')." m on m.id = mc.mer_id where ".$condition,$params);
            $list = pdo_fetchall("select m.merchname,m.logo,m.merchlevel,mc.id,mc.cid,mc.title,mc.descript,mc.content,mc.goods_id,mc.createtime,mc.see from ".tablename("ewei_shop_merch_choice")." mc left join ".tablename('ewei_shop_merch_user')." m on m.id = mc.mer_id where ".$condition." order by mc.createtime desc limit ".$pindex.",".$pageSize,$params);
        }
        foreach ($list as $key => $value){
            $list[$key]['logo'] = tomedia($value['logo']);
            $list[$key]['cate'] = $value['cid'] == 0 ? "" : pdo_getcolumn('ewei_shop_merch_choice_cate',['id'=>$value['cid'],'status'=>1],'cate');
            $list[$key]['levelname'] = $value['merchlevel'] == 0 ? "" : pdo_getcolumn('ewei_shop_merch_level',['id'=>$value['merchlevel'],'status'=>1],'levelname');
            $goods_id = explode(',',$value['goods_id']);
            foreach ($goods_id as $item){
                $good = pdo_fetch(' select id,title,marketprice,productprice,thumb from '.tablename('ewei_shop_goods').' where id = "'.$item.'" and status = 1 and deleted = 0');
                $good['thumb'] = tomedia($good['thumb']);
                $list[$key]['goods'][] = $good;
            }
            $list[$key]['fav'] = pdo_fetchcolumn(' select count(1) from '.tablename('ewei_shop_merch_choice_fav').' where chid = :chid and status = 1 and type = 1 and uniacid = :uniacid ',[':uniacid'=>$uniacid,':chid'=>$value['id']]);
            $list[$key]['comment'] = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_merch_choice_comment').' where parent_id = "'.$value['id'].'" and type = 1');
            //$list[$key]['createtime'] = date('Y-m-d H:i:s',$value['createtime']);
            $list[$key]['createtime'] = m('util')->transform_time($value['createtime']);
            $fav = pdo_fetch('select * from '.tablename('ewei_shop_merch_choice_fav').' where chid = :chid and status = 1 and type = 1 and uniacid = :uniacid and (openid = :openid or user_id = :user_id) ',[':uniacid'=>$uniacid,':chid'=>$value['id'],':openid'=>$member['openid'],':user_id'=>$member['id']]);
            $list[$key]['is_fav'] = empty($fav) || $fav['status'] == 0 ? 0 :1;
        }
        $pagetotal = ceil($total/$pageSize);
        return ['total'=>$total,'page'=>$page,'pagesize'=>$pageSize,'pagetotal'=>$pagetotal,'list'=>$list];
    }

    /**
     * 动态信息的详情
     * @param $user_id
     * @param $id
     * @return array
     */
    public function shop_shop_detail($user_id,$id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //当前登录信息
        $member = m('member')->getMember($user_id);
        //查看动态详情
        $choice = pdo_get('ewei_shop_merch_choice',['id'=>$id,'uniacid'=>$uniacid]);
        $choice['createtime'] = date('Y-m-d H:i:s',$choice['createtime']);
        $goods_id = explode(',',$choice['goods_id']);
        foreach ($goods_id as $item){
            $goods = pdo_fetch(' select id,title,thumb,marketprice from '.tablename('ewei_shop_goods').'where uniacid = :uniacid and id = :id and status = 1 and deleted = 0',[':uniacid'=>$uniacid,':id'=>$item]);
            $goods['thumb'] = tomedia($goods['thumb']);
            $choice['goods'][] = $goods;
        }
        //计算评论数  和  计算点赞数
        $choice['comment'] = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_merch_choice_comment').' where parent_id = "'.$id.'" and type = 1');
        $choice['fav'] = pdo_fetchcolumn(' select count(1) from '.tablename('ewei_shop_merch_choice_fav').' where chid = :chid and status = 1 and type = 1 and uniacid = :uniacid ',[':uniacid'=>$uniacid,':chid'=>$id]);
        //更新查看人数
        pdo_update('ewei_shop_merch_choice',['see'=>$choice['see'] + 1],['id'=>$id,'uniacid'=>$uniacid]);
        //上面的店铺信息和关注状态
        $merch = pdo_fetch('select id,merchname,logo from '.tablename('ewei_shop_merch_user').' where id = :id and uniacid = :uniacid',[':id'=>$choice['mer_id'],':uniacid'=>$uniacid]);
        $fav = pdo_fetch('select * from '.tablename('ewei_shop_merch_follow').' where (openid = :openid or user_id = :user_id) and merch_id = :id',[':openid'=>$member['openid'],':user_id'=>$member['id'],':id'=>$id]);
        $merch['fav'] = empty($fav) ? 0 : 1;
        $merch['logo'] = tomedia($merch['logo']);
        return ['merch'=>$merch,'choice'=>$choice];
    }

    /**
     * ta的店的动态的   评论列表
     * @param $user_id
     * @param $id
     * @param int $page
     * @return array
     */
    public function shop_shop_comment($user_id,$id,$page = 1)
    {
        $member = m('member')->getMember($user_id);
        //查看所有的评论
        $pageSize = 10;
        $first=($page-1) * $pageSize;
        //查找一级评论
        $list=pdo_fetchall("select id,openid,user_id,content,comment_count,zan_count,create_time from ".tablename("ewei_shop_merch_choice_comment")." where type = 1 and is_del = 0 and is_view = 0 and parent_id = :parent_id order by create_time desc limit ".$first.",".$pageSize,array(":parent_id"=>$id));
        $total=pdo_fetchcolumn("select count(1) from ".tablename("ewei_shop_merch_choice_comment")." where type=1 and is_del=0 and is_view=0 and parent_id=:parent_id",array(":parent_id"=>$id));
        foreach ($list as $k => $v){
            //查找评论人的信息
            $m = pdo_fetch('select * from '.tablename('ewei_shop_member').'where openid = :openid or user_id = :user_id ',[':openid'=>$v['openid'],':user_id'=>$v['user_id']]);
            //转化时间
            $list[$k]["create_time"] = m('util')->transform_time($v["create_time"]);
            $list[$k]["nickname"] = $m["nickname"];
            $list[$k]["avatar"] = tomedia($m["avatar"]);
            //判断当前登录账户 是否点赞
            $support = pdo_fetch("select * from ".tablename("ewei_shop_merch_choice_fav")." where type = 2 and chid = :chid and (openid=:openid or user_id=:user_id) limit 1",array(":chid"=>$v['id'],":openid"=>$member["openid"],":user_id"=>$member["id"]));
            $list[$k]["support"] = $support ? 1 : 0;
            //获取下级评论
            $list[$k]["comment"]=pdo_fetchall("select  id,openid,comment_openid,user_id,content from ".tablename("ewei_shop_merch_choice_comment")." where type=2 and is_del=0 and is_view=0 and classA_id=:classA_id order by create_time asc limit 2",array(":classA_id"=>$v["id"]));
            foreach ($list[$k]["comment"] as $key => $val){
                //评论人的用户信息
                $mem = pdo_fetch(' select * from '.tablename('ewei_shop_member').'where openid = :openid or user_id = :user_id ',[':openid'=>$val['openid'],':user_id'=>$val['user_id']]);
                $list[$k]["comment"][$key]["nickname"] = $mem["nickname"];
                //被评论者的用户信息
                if ($val["comment_openid"]){
                    $mm = pdo_fetch("select * from ".tablename("ewei_shop_member")." where openid=:openid or user_id=:user_id",array(":openid"=>$val["comment_openid"],":user_id"=>$val["comment_openid"]));
                    $list[$k]["comment"][$key]["bnickname"] = $mm["nickname"];
                }else{
                    $list[$k]["comment"][$key]["bnickname"] = "";
                }
            }
        }
        $pagetotal = ceil($total/$pageSize);
        return ['page'=>$page,'pagesize'=>$pageSize,'pagetotal'=>$pagetotal,'total'=>$total,'list'=>$list];
    }

    /**
     * 动态文章  或者  评论的点赞
     * @param $user_id
     * @param $id
     * @param $type
     * @return array
     */
    public function shop_choice_fav($user_id,$id,$type = 1)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //查找用户信息
        $member = m('member')->getMember($user_id);
        //查看点赞的情况
        $fav = pdo_fetch('select * from '.tablename('ewei_shop_merch_choice_fav').'where uniacid = :uniacid and (openid = :openid or user_id = :user_id) and type = :type and status = 1 and chid = :chid',[':uniacid'=>$uniacid,':openid'=>$member['openid'],':user_id'=>$member['id'],':type'=>$type,':chid'=>$id]);
        $comment = $type == 2 ? pdo_fetch('select * from '.tablename('ewei_shop_merch_comment').'where uniacid = :uniacid and type = 2 and classA_id = :id and is_del = 0 and is_view = 0',[':uniacid'=>$uniacid,':id'=>$id]) : "";
        // 如果有记录  判断是否是点赞  更新
        if($fav){
            $status = $fav['status'] == 1 ? 0 : 1;
            //取消点赞  赞数减1   点赞 赞数加1
            $zan_count = $status == 0 ? $comment['zan_count'] - 1 : $comment['zan_count'] +1 ;
            //更新点赞状态
            pdo_update('ewei_shop_merch_choice_fav',['status'=>$status],['id'=>$fav['id']]);
            //更新评论的点赞次数
            pdo_update('ewei_shop_merch_comment',['zan_count'=>$zan_count],['id'=>$comment['id']]);
        }else{
            //如果没有记录  就加入数据
            $data = [
                'uniacid'=>$uniacid,
                'openid'=>$member['openid'],
                'user_id'=>$member['id'],
                'type'=>$type,
                'chid'=>$id,
                'status'=>1,
                'createtime'=>time(),
            ];
            pdo_insert('ewei_shop_merch_choice_fav',$data);
            $zan_count = $comment['zan_count'] +1 ;
            //更新评论的点赞次数
            pdo_update('ewei_shop_merch_comment',['zan_count'=>$zan_count],['id'=>$comment['id']]);
        }
        //返回信息
        $msg = empty($fav['status']) ? "点赞成功" : "取消点赞成功";
        return ['status'=>0,'msg'=>$msg,'data'=>[]];
    }

    /**
     * 动态文章  评论的 评论
     * @param $user_id
     * @param $parent_id
     * @param $content
     * @param int $type
     * @return array
     */
    public function shop_choice_comment($user_id,$parent_id,$content,$type = 1)
    {
        $member = m('member')->getMember($user_id);
        $data = [
            "parent_id" => $parent_id,
            "openid" => $member["openid"],
            "user_id" => $member["id"],
            "content" => $content,
            "create_time" => time(),
        ];
        //检测评论的信息 是否含有敏感词
        $c = m('util')->sensitives($data["content"]);
        if ($c>0){
            return ['status'=>1,'msg'=>"含有敏感词不可提交",'data'=>[]];
        }
        if ($type == 1){
            $choice = pdo_get("ewei_shop_merch_choice",array("id"=>$parent_id,"status"=>1));
            if (empty($choice)){
                return ['status'=>1,'msg'=>"信息已不存在",'data'=>[]];
            }
            $data["comment_openid"] = 0;
        } elseif ($type == 2){
            $comment = pdo_get("ewei_shop_merch_choice_comment",array("id"=>$parent_id,"is_del"=>0,"is_view"=>0));
            if (empty($comment)){
                return ['status'=>1,'msg'=>"信息已不存在",'data'=>[]];
            }
            $data["comment_openid"] = empty($comment["user_id"]) ? pdo_getcolumn("ewei_shop_member",array("openid"=>$comment["openid"]),'id') : $comment["user_id"];
            if (empty($comment["classA_id"])){
                //回复一级评论
                $data["classA_id"] = $comment["id"];
                $levelid[0] = $comment["id"];
                $data["levelid"] = serialize($levelid);
            }else{
                $data["classA_id"] = $comment["classA_id"];
                $levelid = unserialize($comment["levelid"]);
                $len = sizeof($levelid);
                $levelid[$len] = $comment["id"];
                $data["levelid"] = serialize($levelid);
            }
        }
        $l = "";
        foreach ($levelid as $k=>$v){
            if (empty($l)){
                $l = $v;
            }else{
                $l = $l.",".$v;
            }
        }
        if (pdo_insert("ewei_shop_merch_choice_comment",$data)) {
            if ($type == 2) {
                //更新上级评论数目
                pdo_query("update " . tablename("ewei_shop_member_drcomment") . ' set comment_count = comment_count+1 where id in (' . $l . ')');
            }
        }
        return ['status'=>0,'msg'=>'评论成功','data'=>[]];
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public function shop_rvc($user_id)
    {
        $member=m("member")->getMember($user_id);
        $data['id'] = $member['id'];
        $data['openid'] = $member['openid'];
        $data['RVC'] = $member['RVC'];//RVC余额
        //累计消费
        $sql = "select ifnull(sum(money),0) from ".tablename('ewei_shop_member_RVClog')." where (openid=:openid or user_id=:user_id) and type=2 and status = 1";
        $params = array(':openid' =>$member["openid"],":user_id"=>$member["id"]);
        $data['sale_total'] = number_format(abs(pdo_fetchcolumn($sql, $params)),2);//成功提现金额
        //累计收入
        $comesql = "select ifnull(sum(money),0) from ".tablename('ewei_shop_member_RVClog')." where (openid=:openid or user_id=:user_id) and type=0 and status = 1";
        $comeparams = array(':openid' =>$member["openid"],":user_id"=>$member["id"]);
        $data['come_total'] = pdo_fetchcolumn($comesql, $comeparams);//累计推荐收入
        return $data;
    }

    /**
     * RVC收支记录
     * @param $user_id
     * @param int $pindex
     * @param int $type
     * @return array
     */
    public function shop_rvc_log($user_id,$pindex = 1,$type = 1)
    {
        $member = m("member")->getMember($user_id);
        $psize = 10;
        if($type == 1){// 收入
            $condition = ' and (openid=:openid or user_id=:user_id) and type = 0';
        }else{// 支出
            $condition = ' and (openid=:openid or user_id=:user_id) and type = 2 ';
        }
        $params = array( ':openid' => $member['openid'],':user_id'=>$member["id"]);
        $list = pdo_fetchall('select * from ' . tablename('ewei_shop_member_RVClog') . (' where 1 ' . $condition . ' order by createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_RVClog') . (' where 1 ' . $condition), $params);
        $newList = array();
        if (is_array($list) && !empty($list)) {
            foreach ($list as &$row) {
                if($row['type'] == 1){
                    $row['money'] = $row['realmoney'];
                }
                $newList[] = array('id' => $row['id'], 'title'=>$row['title'],'type' => $row['type'], 'money' => $row['money'], 'status' => $row['status'], 'deductionmoney' => $row['deductionmoney'], 'realmoney' => $row['realmoney'], 'rechargetype' => $row['rechargetype'], 'createtime' => date('Y-m-d H:i', $row['createtime']));
            }
        }
        $pagetotal = ceil($total/$psize);
        return array('list' => $newList, 'total' => $total,'pagetotal'=>$pagetotal, 'pagesize' => $psize, 'page' => $pindex, 'type' => $type);
    }

    /**
     * 添加订单  订单确认页面
     * @param $user_id
     * @param $id
     * @param $goods
     * @param $packageid
     * @param $optionid
     * @param $bargain_id
     * @param $total
     * @param $giftid
     * @param $fromquick
     * @param $selectDate
     * @param $gdid
     * @return array
     */
    public function order_create($user_id,$id,$goods,$packageid,$optionid,$bargain_id,$total,$giftid,$fromquick,$selectDate,$gdid)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //用户信息
        $member = m('member')->getMember($user_id);
        //redis  是否开启
        $open_redis = function_exists("redis") && !is_error(redis());
        $seckillinfo = false;
        $allow_sale = true;
        $canusecard = true;
        if( !$packageid ) {
            $merch_plugin = p("merch");
            $merch_data = m("common")->getPluginset("merch");
            $merchdata = ['is_openmerch' => $merch_plugin && $merch_data["is_openmerch"] ? 1 : 0, "merch_plugin" => $merch_plugin, "merch_data" => $merch_data];
            extract($merchdata);
            $merch_array = array();
            $merchs = array();
            $merch_id = 0;
            $member["carrier_mobile"] = (empty($member["carrier_mobile"]) ? $member["mobile"] : $member["carrier_mobile"]);
            $level = m("member")->getLevel($member['openid']);
            $diyformdata = m('order')->diyformData($member);
            extract($diyformdata);
            //是否是礼包商品
            $flag = m('game')->gift_check($member['openid'], $id);
            $_SESSION["bargain_id"] = NULL;
            if (p("bargain") && !empty($bargain_id)) {
                $_SESSION["bargain_id"] = $bargain_id;
                $bargain_act = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_bargain_actor") . " WHERE id = :id AND (openid = :openid or user_id = :user_id) AND status = 0", array(":id" => $bargain_id, ":openid" => $member["openid"], ':user_id' => $member['id']));
                //商品出错
                if (empty($bargain_act)) {
                    return ['status' => AppError::$OrderCreateNoGoods, 'msg' => '', 'data' => []];
                }
                $bargain_act_id = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_bargain_goods") . " WHERE id = '" . $bargain_act["goods_id"] . "'");
                //商品出错
                if (empty($bargain_act_id)) {
                    return ['status' => AppError::$OrderCreateNoGoods, 'msg' => '', 'data' => []];
                }
                $if_bargain = pdo_fetch("SELECT bargain FROM " . tablename("ewei_shop_goods") . " WHERE id = :id AND uniacid = :uniacid ", array(":id" => $bargain_act_id["goods_id"], ":uniacid" => $_W["uniacid"]));
                //商品出错
                if (empty($if_bargain["bargain"])) {
                    return ['status' => AppError::$OrderCreateNoGoods, 'msg' => '', 'data' => []];
                }
                $id = $bargain_act_id["goods_id"];
            }
            if( $total < 1 )
            {
                $total = 1;
            }
            $buytotal = $total;
            $errcode = 0;
            $isverify = false;
            $isvirtual = false;
            $isforceverifystore = false;
            $isvirtualsend = false;
            $changenum = false;
            $fromcart = 0;
            $hasinvoice = false;
            $invoicename = "";
            $buyagain_sale = true;
            $buyagainprice = 0;
            $isonlyverifygoods = true;
            $iscycel = false;
            $goods = array( );
            $giftGood = array( );
            $gifts = array( );
            if( empty($id) )
            {
                if( !empty($quickid) )
                {
                    $sql = "SELECT c.goodsid,c.total,g.maxbuy,g.type,g.intervalfloor,g.intervalprice,g.issendfree,g.isnodiscount,g.ispresell,g.presellprice as gpprice,o.presellprice,g.preselltimeend,g.presellsendstatrttime,g.presellsendtime,g.presellsendtype" . ",g.weight,o.weight as optionweight,g.title,g.thumb,ifnull(o.marketprice, g.marketprice) as marketprice,o.title as optiontitle,c.optionid," . " g.storeids,g.isverify,g.isforceverifystore,g.deduct,g.deduct_type,g.manydeduct,g.virtual,o.virtual as optionvirtual,discounts," . " g.deduct2,g.ednum,g.edmoney,g.edareas,g.edareas_code,g.diyformtype,g.diyformid,diymode,g.dispatchtype,g.dispatchid,g.dispatchprice,g.is_remote,g.remote_dispatchprice,g.minbuy " . " ,g.isdiscount,g.isdiscount_time,g.isdiscount_discounts,g.cates,g.isfullback, " . " g.virtualsend,invoice,o.specs,g.merchid,g.checked,g.merchsale,g.unite_total," . " g.buyagain,g.buyagain_islong,g.buyagain_condition, g.buyagain_sale, g.hasoption, g.threen" . " FROM " . tablename("ewei_shop_quick_cart") . " c " . " left join " . tablename("ewei_shop_goods") . " g on c.goodsid = g.id " . " left join " . tablename("ewei_shop_goods_option") . " o on c.optionid = o.id " . " where (c.openid=:openid or c.user_id = :user_id) and c.selected=1 and  c.deleted=0 and c.uniacid=:uniacid and c.quickid=" . $quickid . "  order by c.id desc";
                    $goods = pdo_fetchall($sql, array( ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id'] ));
                }
                else
                {
                    $sql = " SELECT c.goodsid,c.total,g.maxbuy,g.type,g.issendfree,g.isnodiscount,g.ispresell,g.presellprice as gpprice,o.presellprice,g.preselltimeend,g.presellsendstatrttime,g.presellsendtime,g.presellsendtype" . ",g.weight,o.weight as optionweight,g.title,g.thumb,ifnull(o.marketprice, g.marketprice) as marketprice,o.title as optiontitle,c.optionid,g.isfullback," . " g.storeids,g.isverify,g.isforceverifystore,g.deduct,g.deduct_type,g.manydeduct,g.virtual,o.virtual as optionvirtual,discounts," . " g.deduct2,g.ednum,g.edmoney,g.edareas,g.diyformtype,g.diyformid,diymode,g.dispatchtype,g.dispatchid,g.dispatchprice,g.is_remote,g.remote_dispatchprice,g.minbuy " . " ,g.isdiscount,g.isdiscount_time,g.isdiscount_discounts,g.cates, " . " g.virtualsend,invoice,o.specs,g.merchid,g.checked,g.merchsale," . " g.buyagain,g.buyagain_islong,g.buyagain_condition, g.buyagain_sale" . " FROM " . tablename("ewei_shop_member_cart") . " c " . " left join " . tablename("ewei_shop_goods") . " g on c.goodsid = g.id " . " left join " . tablename("ewei_shop_goods_option") . " o on c.optionid = o.id " . " where (c.openid=:openid or c.user_id = :user_id) and c.selected=1 and  c.deleted=0 and c.uniacid=:uniacid  order by c.id desc";
                    $goods = pdo_fetchall($sql, array( ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id'] ));
                }
                if( empty($goods) )
                {
                    return ['status'=>AppError::$OrderCreateNoGoods,'msg'=>'','data'=>[]];
                }
                foreach( $goods as $k => $v )
                {
                    if( $is_openmerch == 0 )
                    {
                        //商品出错
                        if( 0 < $v["merchid"] )
                        {
                            return ['status'=>AppError::$OrderCreateNoGoods,'msg'=>'','data'=>[]];
                        }
                    }
                    else
                    {
                        //商品出错
                        if( 0 < $v["merchid"] && $v["checked"] == 1 )
                        {
                            return ['status'=>AppError::$OrderCreateNoGoods,'msg'=>'','data'=>[]];
                        }
                    }
                    if( $k == 0 )
                    {
                        $merch_id = $v["merchid"];
                    }
                    if( $merch_id == $v["merchid"] )
                    {
                        $merch_id = $v["merchid"];
                    }
                    else
                    {
                        $merch_id = 0;
                    }
                    if( !empty($v["specs"]) )
                    {
                        $thumb = m("goods")->getSpecThumb($v["specs"]);
                        if( !empty($thumb) )
                        {
                            $goods[$k]["thumb"] = $thumb;
                        }
                    }
                    if( !empty($v["optionvirtual"]) )
                    {
                        $goods[$k]["virtual"] = $v["optionvirtual"];
                    }
                    if( !empty($v["optionweight"]) )
                    {
                        $goods[$k]["weight"] = $v["optionweight"];
                    }
                    $goods[$k]["seckillinfo"] = plugin_run("seckill::getSeckill", $v["goodsid"], $v["optionid"], true, $member["openid"]);
                    if( !empty($goods[$k]["seckillinfo"]["maxbuy"]) && $goods[$k]["seckillinfo"]["maxbuy"] - $goods[$k]["seckillinfo"]["selfcount"] < $goods[$k]["total"] )
                    {
                        app_error(1, "最多购买" . $goods[$k]["seckillinfo"]["maxbuy"] . "件");
                    }
                    if( 0 < $goods[$k]["ispresell"] && ($goods[$k]["preselltimeend"] == 0 || time() < $goods[$k]["preselltimeend"]) )
                    {
                        $canusecard = false;
                    }
                    if( $goods[$k]["type"] == 4 )
                    {
                        $canusecard = false;
                    }
                    if( $goods[$k]["seckillinfo"] && $goods[$k]["seckillinfo"]["status"] == 0 )
                    {
                        $canusecard = false;
                    }
                    if( $merch_id )
                    {
                        $canusecard = false;
                    }
                }
                $fromcart = 1;
            }
            else
            {
                $sql = "SELECT id as goodsid,type,title,weight,issendfree,isnodiscount,isfullback,ispresell,presellprice,preselltimeend,presellsendstatrttime,presellsendtime,presellsendtype, " . " thumb,marketprice,storeids,isverify,isforceverifystore,deduct,deduct_type," . " manydeduct,`virtual`,maxbuy,usermaxbuy,discounts,total as stock,deduct2,showlevels," . " ednum,edmoney,edareas," . " diyformtype,diyformid,diymode,dispatchtype,dispatchid,dispatchprice,is_remote,remote_dispatchprice,cates,minbuy, " . " isdiscount,isdiscount_time,isdiscount_discounts, " . " virtualsend,invoice,needfollow,followtip,followurl,merchid,checked,merchsale, " . " buyagain,buyagain_islong,buyagain_condition, buyagain_sale" . " FROM " . tablename("ewei_shop_goods") . " where id=:id and uniacid=:uniacid  limit 1";
                $data = pdo_fetch($sql, array( ":uniacid" => $uniacid, ":id" => $id ));
                if( !empty($bargain_act) )
                {
                    $data["marketprice"] = $bargain_act["now_price"];
                }
                if( 0 < $data["ispresell"] && ($data["preselltimeend"] == 0 || time() < $data["preselltimeend"]) )
                {
                    $data["marketprice"] = $data["presellprice"];
                    $canusecard = false;
                }
                if( $data["type"] == 4 )
                {
                    $canusecard = false;
                }
                $merch_id = $data["merchid"];
                $fullbackgoods = array( );
                if( $data["isfullback"] )
                {
                    $fullbackgoods = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_fullback_goods") . " WHERE goodsid = :goodsid and uniacid = :uniacid and status = 1 limit 1 ", array( ":goodsid" => $data["goodsid"], ":uniacid" => $uniacid ));
                }
                if( empty($data) || !empty($data["showlevels"]) && !strexists($data["showlevels"], $member["level"]) || 0 < $data["merchid"] && $data["checked"] == 1 || $is_openmerch == 0 && 0 < $data["merchid"] )
                {
                    return ['status'=>AppError::$OrderCreateNoGoods,'msg'=>'','data'=>[]];
                }
                $follow = m("user")->followed($member['openid']);
                if( 0 < $data["minbuy"] && $total < $data["minbuy"] )
                {
                    $total = $data["minbuy"];
                }
                $data["total"] = $total;
                $data["optionid"] = $optionid;
                if( !empty($optionid) )
                {
                    $option = pdo_fetch("select * from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array( ":uniacid" => $uniacid, ":goodsid" => $id, ":id" => $optionid ));
                    if( !empty($option) )
                    {
                        $data["optionid"] = $optionid;
                        $data["optiontitle"] = $option["title"];
                        $data["marketprice"] = (0 < intval($data["ispresell"]) && (time() < $data["preselltimeend"] || $data["preselltimeend"] == 0) ? $option["presellprice"] : $option["marketprice"]);
                        $data["virtual"] = $option["virtual"];
                        $data["stock"] = $option["stock"];
                        if( !empty($option["weight"]) )
                        {
                            $data["weight"] = $option["weight"];
                        }
                        if( !empty($option["specs"]) )
                        {
                            $thumb = m("goods")->getSpecThumb($option["specs"]);
                            if( !empty($thumb) )
                            {
                                $data["thumb"] = $thumb;
                            }
                        }
                        if( $option["isfullback"] && !empty($fullbackgoods) )
                        {
                            $fullbackgoods["minallfullbackallprice"] = $option["allfullbackprice"];
                            $fullbackgoods["fullbackprice"] = $option["fullbackprice"];
                            $fullbackgoods["minallfullbackallratio"] = $option["allfullbackratio"];
                            $fullbackgoods["fullbackratio"] = $option["fullbackratio"];
                            $fullbackgoods["day"] = $option["day"];
                        }
                    }
                    $cycelbuy_periodic = explode(",", $option["cycelbuy_periodic"]);
                    list($cycelbuy_day, $cycelbuy_unit, $cycelbuy_num) = $cycelbuy_periodic;
                }
                $data["seckillinfo"] = plugin_run("seckill::getSeckill", $data["goodsid"], $data["optionid"], true, $member["openid"]);
                if( !empty($data["seckillinfo"]["maxbuy"]) && $data["seckillinfo"]["maxbuy"] - $data["seckillinfo"]["selfcount"] < $data["total"] )
                {
                    app_error(1, "最多购买" . $data["seckillinfo"]["maxbuy"] . "件");
                }
                if( $giftid )
                {
                    $changenum = false;
                }
                else
                {
                    $changenum = true;
                }
                if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
                {
                    $changenum = false;
                    $canusecard = false;
                }
                $goods[] = $data;
            }
            if( p("bargain") && !empty($bargain_id) )
            {
                $canusecard = false;
            }
            $goods = set_medias($goods, "thumb");
            foreach( $goods as &$g )
            {
                if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                {
                    $g["is_task_goods"] = 0;
                }
                else
                {
                    $rank = intval($_SESSION[$id . "_rank"]);
                    $join_id = intval($_SESSION[$id . "_join_id"]);
                    $task_goods_data = m("goods")->getTaskGoods($member['openid'], $id, $rank, $join_id, $optionid);
                    if( empty($task_goods_data["is_task_goods"]) )
                    {
                        $g["is_task_goods"] = 0;
                    }
                    else
                    {
                        $allow_sale = false;
                        $g["is_task_goods"] = $task_goods_data["is_task_goods"];
                        $g["is_task_goods_option"] = $task_goods_data["is_task_goods_option"];
                        $g["task_goods"] = $task_goods_data["task_goods"];
                    }
                }
                if( $is_openmerch == 1 )
                {
                    $merchid = $g["merchid"];
                    $merch_array[$merchid]["goods"][] = $g["goodsid"];
                }
                if( $g["isverify"] == 2 )
                {
                    $isverify = true;
                }
                if( $g["isforceverifystore"] )
                {
                    $isforceverifystore = true;
                }
                if( !empty($g["virtual"]) || $g["type"] == 2 )
                {
                    $isvirtual = true;
                    if( $g["virtualsend"] )
                    {
                        $isvirtualsend = true;
                    }
                }
                if( $g["invoice"] )
                {
                    $hasinvoice = $g["invoice"];
                }
                if( $g["type"] != 5 )
                {
                    $isonlyverifygoods = false;
                }
                if( $g["type"] == 9 )
                {
                    $iscycel = true;
                }
                $totalmaxbuy = $g["stock"];
                if( !empty($g["seckillinfo"]) && $g["seckillinfo"]["status"] == 0 )
                {
                    $seckilllast = 0;
                    if( 0 < $g["seckillinfo"]["maxbuy"] )
                    {
                        $seckilllast = $g["seckillinfo"]["maxbuy"] - $g["seckillinfo"]["selfcount"];
                    }
                    $g["totalmaxbuy"] = $g["total"];
                }
                else
                {
                    if( 0 < $g["maxbuy"] )
                    {
                        if( $totalmaxbuy != -1 )
                        {
                            if( $g["maxbuy"] < $totalmaxbuy )
                            {
                                $totalmaxbuy = $g["maxbuy"];
                            }
                        }
                        else
                        {
                            $totalmaxbuy = $g["maxbuy"];
                        }
                    }
                    if( 0 < $g["usermaxbuy"] )
                    {
                        $order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and (o.openid=:openid or o.user_id = :user_id)  and og.uniacid=:uniacid ", array( ":goodsid" => $g["goodsid"], ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id'] ));
                        $last = $data["usermaxbuy"] - $order_goodscount;
                        if( $last <= 0 )
                        {
                            $last = 0;
                        }
                        if( $totalmaxbuy != -1 )
                        {
                            if( $last < $totalmaxbuy )
                            {
                                $totalmaxbuy = $last;
                            }
                        }
                        else
                        {
                            $totalmaxbuy = $last;
                        }
                    }
                    if( !empty($g["is_task_goods"]) && $g["task_goods"]["total"] < $totalmaxbuy )
                    {
                        $totalmaxbuy = $g["task_goods"]["total"];
                    }
                    $g["totalmaxbuy"] = $totalmaxbuy;
                    if( $g["totalmaxbuy"] < $g["total"] && !empty($g["totalmaxbuy"]) )
                    {
                        $g["total"] = $g["totalmaxbuy"];
                    }
                    if( 0 < floatval($g["buyagain"]) && empty($g["buyagain_sale"]) && m("goods")->canBuyAgain($g) )
                    {
                        $buyagain_sale = false;
                    }
                }
            }
            unset($g);
            $invoice_arr = array( "entity" => false, "company" => false, "title" => false, "number" => false );
            if( $hasinvoice )
            {
                $invoicename = pdo_fetchcolumn("select invoicename from " . tablename("ewei_shop_order") . " where (openid = :openid or user_id = :user_id) and uniacid=:uniacid and ifnull(invoicename,'')<>'' order by id desc limit 1", array( ":openid" => $member['openid'],":user_id" => $member['id'], ":uniacid" => $uniacid ));
                $invoice_arr = m("sale")->parseInvoiceInfo($invoicename);
                if( $invoice_arr["title"] === false )
                {
                    $invoicename = "";
                }
                $invoice_type = m("common")->getSysset("trade");
                $invoice_type = (int) $invoice_type["invoice_entity"];
                if( $invoice_type === 0 )
                {
                    $invoicename = str_replace("电子", "纸质", $invoicename);
                }
                else
                {
                    if( $invoice_type === 1 )
                    {
                        $invoicename = str_replace("纸质", "电子", $invoicename);
                    }
                }
            }
            if( $merch_id )
            {
                $canusecard = false;
            }
            if( $is_openmerch == 1 )
            {
                foreach( $merch_array as $key => $value )
                {
                    if( 0 < $key )
                    {
                        $merch_id = $key;
                        $merch_array[$key]["set"] = $merch_plugin->getSet("sale", $key);
                        $merch_array[$key]["enoughs"] = $merch_plugin->getEnoughs($merch_array[$key]["set"]);
                    }
                }
            }
            $weight = 0;
            $total = 0;
            $goodsprice = 0;
            $goodsdeduct = 0;
            //折扣宝
            $discount=0;
            $realprice = 0;
            $deductprice = 0;
            $taskdiscountprice = 0;
            $discountprice = 0;
            $isdiscountprice = 0;
            $deductprice2 = 0;
            $stores = array( );
            $address = false;
            $carrier = false;
            $carrier_list = array( );
            $store_list = array( );
            $dispatch_list = false;
            $dispatch_price = 0;
            $seckill_dispatchprice = 0;
            $seckill_price = 0;
            $seckill_payprice = 0;
            $ismerch = 0;
            if( $is_openmerch == 1 && !empty($merch_array) && 1 < count($merch_array) )
            {
                $ismerch = 1;
            }
            if( !$isverify && !$isvirtual && !$ismerch )
            {
                if( 0 < $merch_id )
                {
                    $carrier_list = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(1,3) order by displayorder desc,id desc", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merch_id ));
                }
                else
                {
                    $carrier_list = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where  uniacid=:uniacid and status=1 and type in(1,3) order by displayorder desc,id desc", array( ":uniacid" => $_W["uniacid"] ));
                }
            }
            $sale_plugin = com("sale");
            $saleset = false;
            if( $sale_plugin && $buyagain_sale && $allow_sale )
            {
                $saleset = $_W["shopset"]["sale"];
                $saleset["enoughs"] = $sale_plugin->getEnoughs();
            }
            foreach( $goods as &$g )
            {
                //折扣宝
                if ($g['deduct_type']==1){
                    $goodsdeduct += $g['deduct'];
                }
                if( empty($g["total"]) || intval($g["total"]) < 1 )
                {
                    $g["total"] = 1;
                }
                //if( $taskcut || $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                {
                    $gprice = $g["marketprice"] * $g["total"];
                    $g["ggprice"] = $g["seckillinfo"]["price"] * $g["total"];
                    $seckill_payprice += $g["seckillinfo"]["price"] * $g["total"];
                    $seckill_price += $g["marketprice"] * $g["total"] - $seckill_payprice;
                }
                else
                {
                    $gprice = $g["marketprice"] * $g["total"];
                    $prices = m("order")->getGoodsDiscountPrice($g, $level);
                    if( empty($bargain_id) )
                    {
                        $g["ggprice"] = $prices["price"];
                    }
                    else
                    {
                        $g["ggprice"] = $gprice;
                    }
                    $g["unitprice"] = $prices["unitprice"];
                }
                if( $is_openmerch == 1 )
                {
                    $merchid = $g["merchid"];
                    $merch_array[$merchid]["ggprice"] += $g["ggprice"];
                    $merchs[$merchid] += $g["ggprice"];
                }
                $g["dflag"] = intval($g["ggprice"] < $gprice);
                if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 || $_SESSION["taskcut"] )
                {
                }
                else
                {
                    if( empty($bargain_id) )
                    {
                        $taskdiscountprice += $prices["taskdiscountprice"];
                        $g["taskdiscountprice"] = $prices["taskdiscountprice"];
                        $g["discountprice"] = $prices["discountprice"];
                        $g["isdiscountprice"] = $prices["isdiscountprice"];
                        $g["discounttype"] = $prices["discounttype"];
                        $g["isdiscountunitprice"] = $prices["isdiscountunitprice"];
                        $g["discountunitprice"] = $prices["discountunitprice"];
                        $buyagainprice += $prices["buyagainprice"];
                        if( $prices["discounttype"] == 1 )
                        {
                            $isdiscountprice += $prices["isdiscountprice"];
                        }
                        else
                        {
                            if( $prices["discounttype"] == 2 )
                            {
                                $discountprice += $prices["discountprice"];
                            }
                        }
                    }
                }
                $realprice += $g["ggprice"];
                if( $g["ggprice"] < $gprice )
                {
                    $goodsprice += $gprice;
                }
                else
                {
                    $goodsprice += $g["ggprice"];
                }
                $total += $g["total"];
                if( empty($bargain_id) )
                {
                    if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                    {
                        $g["deduct"] = 0;
                    }
                    else
                    {
                        if( 0 < floatval($g["buyagain"]) && empty($g["buyagain_sale"]) && m("goods")->canBuyAgain($g) )
                        {
                            $g["deduct"] = 0;
                        }
                    }
                    if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                    {
                    }
                    else
                    {
                        if( $open_redis )
                        {
                            if( $g["manydeduct"] )
                            {
                                //添加判断  折扣宝
                                if ($g["deduct_type"]==1){
                                    //卡路里
                                    $deductprice += $g["deduct"] * $g["total"];

                                }else{
                                    //折扣宝
                                    $discount+=$g["deduct"]*$g["total"];
                                }
                            }
                            else
                            {
                                //添加判断
                                if ($g["deduct_type"]==1){
                                    //卡路里
                                    $deductprice += $g["deduct"];
                                }else{
                                    //折扣宝
                                    $discount+=$g["deduct"];
                                }

                            }
                            if( $g["deduct2"] == 0 )
                            {
                                $deductprice2 += $g["ggprice"];
                            }
                            else
                            {
                                if( 0 < $g["deduct2"] )
                                {
                                    if( $g["ggprice"] < $g["deduct2"] )
                                    {
                                        $deductprice2 += $g["ggprice"];
                                    }
                                    else
                                    {
                                        $deductprice2 += $g["deduct2"];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            unset($g);
            $storeids = array( );
            if( $isverify )
            {
                $merchid = 0;
                foreach( $goods as $g )
                {
                    if( !empty($g["storeids"]) )
                    {
                        $merchid = $g["merchid"];
                        $storeids = array_merge(explode(",", $g["storeids"]), $storeids);
                    }
                }
                if( empty($storeids) )
                {
                    if( 0 < $merchid )
                    {
                        $stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merchid ));
                    }
                    else
                    {
                        $stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where  uniacid=:uniacid and status=1 and type in(2,3)", array( ":uniacid" => $_W["uniacid"] ));
                    }
                }
                else
                {
                    if( 0 < $merchid )
                    {
                        $stores = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merchid ));
                    }
                    else
                    {
                        $stores = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where id in (" . implode(",", $storeids) . ") and uniacid=:uniacid and status=1 and type in(2,3)", array( ":uniacid" => $_W["uniacid"] ));
                    }
                }
                if( $isforceverifystore )
                {
                    $storeids_condition = "";
                    if( !empty($storeids) )
                    {
                        $storeids_condition = "  id in (" . implode(",", $storeids) . ") and ";
                    }
                    if( 0 < $merch_id )
                    {
                        $store_list = pdo_fetchall("select * from " . tablename("ewei_shop_merch_store") . " where " . $storeids_condition . "  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3) order by displayorder desc,id desc", array( ":uniacid" => $_W["uniacid"], ":merchid" => $merch_id ));
                    }
                    else
                    {
                        $store_list = pdo_fetchall("select * from " . tablename("ewei_shop_store") . " where  " . $storeids_condition . "  uniacid=:uniacid and status=1 and type in(2,3) order by displayorder desc,id desc", array( ":uniacid" => $_W["uniacid"] ));
                    }
                }
            }
            else
            {
                $address = pdo_fetch("select * from " . tablename("ewei_shop_member_address") . " where (openid=:openid or user_:user_id) and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1", array( ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id'] ));
                if( !empty($carrier_list) )
                {
                    $carrier = $carrier_list[0];
                }
                if( !$isvirtual && !$isonlyverifygoods )
                {
                    $dispatch_array = m("order")->getOrderDispatchPrice($goods, $member, $address, $saleset, $merch_array, 0);
                    $dispatch_price = $dispatch_array["dispatch_price"] - $dispatch_array["seckill_dispatch_price"];
                    $seckill_dispatchprice = $dispatch_array["seckill_dispatch_price"];
                    $isdispatcharea = $dispatch_array['isdispatcharea'];
                }
            }
            $card_info = array( );
            $plugin_membercard = p("membercard");
            if( !$plugin_membercard )
            {
                $canusecard = false;
            }
            $availablecard_count = 0;
            $default_cardid = 0;
            $carddiscountprice = 0;
            $pure_totalprice = $realprice;
            $card_free_dispatch = false;
            if( $canusecard )
            {
                $mycard = $plugin_membercard->get_Mycard($member['openid']);
                if( $mycard["list"] )
                {
                    $all_mycardlist = $mycard["list"];
                    $card_info["all_mycardlist"] = $all_mycardlist;
                    $availablecard_count = $mycard["total"];
                    $c_discount = array( );
                    $a_discount = array( );
                    foreach( $all_mycardlist as $ckey => $cvalue )
                    {
                        if( empty($cvalue["member_discount"]) )
                        {
                            continue;
                        }
                        $c_discount[$cvalue["id"]] = (string) $cvalue["discount_rate"];
                    }
                    foreach( $all_mycardlist as $akey => $avalue )
                    {
                        if( empty($avalue["member_discount"]) || $avalue["discount"] == 0 )
                        {
                            continue;
                        }
                        $a_discount[$avalue["id"]] = (string) $avalue["discount_rate"];
                    }
                    $max_discount_cardid = 0;
                    if( !empty($a_discount) )
                    {
                        $max_discount = min($a_discount);
                        $ex_discount = @array_flip($a_discount);
                        $max_discount_cardid = $ex_discount[$max_discount];
                    }
                    else
                    {
                        if( !empty($c_discount) )
                        {
                            $max_discount = min($c_discount);
                            $ex_discount = @array_flip($c_discount);
                            $max_discount_cardid = $ex_discount[$max_discount];
                        }
                    }
                    $default_cardid = (empty($max_discount_cardid) ? $all_mycardlist[0]["id"] : $max_discount_cardid);
                }
                $card_info["availablecard_count"] = $availablecard_count;
                $card_info["cardid"] = $default_cardid;
                if( $default_cardid )
                {
                    $card_result = m('order')->caculatecard($default_cardid, $dispatch_price, $pure_totalprice, $discountprice, $isdiscountprice);
                    if( $card_result )
                    {
                        $card_info["dispatch_price"] = $dispatch_price;
                        $dispatch_price = $card_result["dispatch_price"];
                        $carddiscountprice = $card_result["carddiscountprice"];
                        $card_info["old_discountprice"] = $discountprice;
                        $discountprice = $card_result["discountprice"];
                        $card_info["old_isdiscountprice"] = $isdiscountprice;
                        $isdiscountprice = $card_result["isdiscountprice"];
                        $card_info["cardname"] = $card_result["cardname"];
                        $card_info["carddiscount_rate"] = $card_result["carddiscount_rate"];
                        $card_info["carddiscountprice"] = $carddiscountprice;
                        $card_info["beforeprice"] = $pure_totalprice;
                    }
                }
                $card_info["carddiscountprice"] = $carddiscountprice;
            }
            if( 0 < $card_info["dispatch_price"] && $dispatch_price == 0 )
            {
                $card_free_dispatch = true;
            }
            if( 0 < $card_info["old_discountprice"] && $discountprice == 0 )
            {
                $realprice += $card_info["old_discountprice"];
            }
            if( 0 < $card_info["old_isdiscountprice"] && $isdiscountprice == 0 )
            {
                $realprice += $card_info["old_isdiscountprice"];
            }
            $realprice -= $carddiscountprice;
            if( $is_openmerch == 1 )
            {
                $merch_enough = m("order")->getMerchEnough($merch_array);
                $merch_array = $merch_enough["merch_array"];
                $merch_enough_total = $merch_enough["merch_enough_total"];
                $merch_saleset = $merch_enough["merch_saleset"];
                if( 0 < $merch_enough_total )
                {
                    $realprice -= $merch_enough_total;
                }
            }
            if( $saleset )
            {
                foreach( $saleset["enoughs"] as $e )
                {
                    if( floatval($e["enough"]) <= $realprice - $seckill_payprice && 0 < floatval($e["money"]) )
                    {
                        $saleset["showenough"] = true;
                        $saleset["enoughmoney"] = $e["enough"];
                        $saleset["enoughdeduct"] = $e["money"];
                        $realprice -= floatval($e["money"]);
                        break;
                    }
                }
                if( empty($saleset["dispatchnodeduct"]) )
                {
                    $deductprice2 += $dispatch_price;
                }
            }
            if( $iscycel )
            {
                $realprice += $dispatch_price * $cycelbuy_num;
            }
            else
            {
                $realprice += $dispatch_price + $seckill_dispatchprice;
            }
            $deductcredit = 0;
            $deductmoney = 0;
            $deductcredit2 = 0;
            if( !empty($saleset) )
            {
                if( !empty($saleset["creditdeduct"]) )
                {
                    //个人卡路里
                    $credit = $member["credit1"];
                    $pcredit = intval($saleset["credit"]);
                    $pmoney = round(floatval($saleset["money"]), 2);

                    if( 0 < $pcredit && 0 < $pmoney )
                    {
                        if( $credit % $pcredit == 0 )
                        {
                            $deductmoney = round(intval($credit / $pcredit) * $pmoney, 2);
                        }
                        else
                        {
                            $deductmoney = round((intval($credit / $pcredit) + 1) * $pmoney, 2);
                        }
                    }
                    if( $deductprice < $deductmoney )
                    {
                        $deductmoney = $deductprice;
                    }
                    if( $realprice - $seckill_payprice < $deductmoney )
                    {
                        $deductmoney = $realprice - $seckill_payprice;
                    }
                    if( $pmoney * $pcredit != 0 )
                    {
                        $deductcredit = ceil($deductmoney / $pmoney * $pcredit);
                    }
                }
                if( !empty($saleset["moneydeduct"]) )
                {
                    $deductcredit2 = m("member")->getCredit($member['openid'], "credit2");
                    if( $realprice - $seckill_payprice < $deductcredit2 )
                    {
                        $deductcredit2 = $realprice - $seckill_payprice;
                    }
                    if( $deductprice2 < $deductcredit2 )
                    {
                        $deductcredit2 = $deductprice2;
                    }
                }
            }
            $goodsdata = array( );
            $goodsdata_temp = array( );
            $remote_dispatchprice = 0;
            $is_remote = 1;
            foreach( $goods as $g )
            {
                if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                {
                }
                else
                {
                    if( 0 < floatval($g["buyagain"]) )
                    {
                        if( !m("goods")->canBuyAgain($g) || !empty($g["buyagain_sale"]) )
                        {
                            $goodsdata_temp[] = array( "goodsid" => $g["goodsid"], "total" => $g["total"], "optionid" => $g["optionid"], "marketprice" => $g["marketprice"], "merchid" => $g["merchid"], "cates" => $g["cates"], "discounttype" => $g["discounttype"], "isdiscountprice" => $g["isdiscountprice"], "discountprice" => $g["discountprice"], "isdiscountunitprice" => $g["isdiscountunitprice"], "discountunitprice" => $g["discountunitprice"] );
                        }
                    }
                    else
                    {
                        $goodsdata_temp[] = array( "goodsid" => $g["goodsid"], "total" => $g["total"], "optionid" => $g["optionid"], "marketprice" => $g["marketprice"], "merchid" => $g["merchid"], "cates" => $g["cates"], "discounttype" => $g["discounttype"], "isdiscountprice" => $g["isdiscountprice"], "discountprice" => $g["discountprice"], "isdiscountunitprice" => $g["isdiscountunitprice"], "discountunitprice" => $g["discountunitprice"] );
                    }
                }
                $goodsdata[] = array( "goodsid" => $g["goodsid"], "total" => $g["total"], "optionid" => $g["optionid"], "marketprice" => $g["marketprice"], "merchid" => $g["merchid"], "cates" => $g["cates"], "discounttype" => $g["discounttype"], "isdiscountprice" => $g["isdiscountprice"], "discountprice" => $g["discountprice"], "isdiscountunitprice" => $g["isdiscountunitprice"], "discountunitprice" => $g["discountunitprice"] );
                $remote_dispatchprice += $g['remote_dispatchprice'];
                if($g['is_remote'] == 0){
                    $is_remote = 0;
                }
            }
            if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
            {
            }
            else
            {
                if( $g["isverify"] == 2 )
                {
                }
                else
                {
                    $gifttitle = "";
                    if( $giftid )
                    {
                        $gift = array( );
                        $giftdata = pdo_fetch("select giftgoodsid,activity,orderprice from " . tablename("ewei_shop_gift") . " where uniacid = " . $uniacid . " and id = " . $giftid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " ");
                        if( $giftdata["giftgoodsid"] )
                        {
                            $giftgoodsid = explode(",", $giftdata["giftgoodsid"]);
                            foreach( $giftgoodsid as $key => $value )
                            {
                                $gift[$key] = pdo_fetch("select id as goodsid,title,thumb from " . tablename("ewei_shop_goods") . " where uniacid = " . $uniacid . " and status = 2 and id = " . $value . " and deleted = 0 ");
                                $gift[$key]["total"] = $total;
                            }
                            $gift = set_medias($gift, array( "thumb" ));
                            $goodsdata = array_merge($goodsdata, $gift);
                        }
                    }
                    else
                    {
                        $isgift = 0;
                        $gifts = array( );
                        $giftgoods = array( );
                        $gifts = pdo_fetchall("select id,goodsid,giftgoodsid,thumb,title,orderprice from " . tablename("ewei_shop_gift") . "\r\n                    where uniacid = " . $uniacid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " and orderprice <= " . $realprice . " and activity = 1 ");
                        foreach( $gifts as $key => $value )
                        {
                            $isgift = 1;
                            $giftgoods = explode(",", $value["giftgoodsid"]);
                            foreach( $giftgoods as $k => $val )
                            {
                                $gifts[$key]["gift"][$k] = pdo_fetch("select id,title,thumb,marketprice from " . tablename("ewei_shop_goods") . " where uniacid = " . $uniacid . " and status = 2 and id = " . $val . " ");
                            }
                            $gifts[$key]["gift"] = set_medias($gifts[$key]["gift"], array( "thumb" ));
                            $gifttitle = $gifts[$key]["gift"][0]["title"];
                        }
                        $gifts = set_medias($gifts, array( "thumb" ));
                    }
                }
            }
            $couponcount = com_run("coupon::consumeCouponCount", $member['openid'], $realprice, $merch_array, $goodsdata_temp);
            if( empty($goodsdata_temp) || !$allow_sale )
            {
                $couponcount = 0;
            }
            $mustbind = 0;
            if( !empty($_W["shopset"]["wap"]["open"]) && !empty($_W["shopset"]["wap"]["mustbind"]) && empty($member["mobileverify"]) )
            {
                $mustbind = 1;
            }
            if( $is_openmerch == 1 )
            {
                $merchs = $merch_plugin->getMerchs($merch_array);
            }
            $token = md5(microtime());
            $_SESSION["order_token"] = $token;
            $goods_list = array( );
            $i = 0;
            if( $ismerch )
            {
                $getListUser = $merch_plugin->getListUser($goods);
                $merch_user = $getListUser["merch_user"];
                foreach( $getListUser["merch"] as $k => $v )
                {
                    if( empty($merch_user[$k]["merchname"]) )
                    {
                        $goods_list[$i]["shopname"] = $_W["shopset"]["shop"]["name"];
                    }
                    else
                    {
                        $goods_list[$i]["shopname"] = $merch_user[$k]["merchname"];
                    }
                    $goods_list[$i]["goods"] = $v;
                    $i++;
                }
            }
            else
            {
                if( $merchid == 0 )
                {
                    $goods_list[$i]["shopname"] = $_W["shopset"]["shop"]["name"];
                }
                else
                {
                    $merch_data = $merch_plugin->getListUserOne($merchid);
                    $goods_list[$i]["shopname"] = $merch_data["merchname"];
                }
                $goods_list[$i]["goods"] = $goods;
            }
            $createInfo = array( "id" => $id, "gdid" => intval($gdid), "fromcart" => $fromcart, "addressid" => (!empty($address) && !$isverify && !$isvirtual ? $address["id"] : 0), "storeid" => (!empty($carrier_list) && !$isverify && !$isvirtual ? $carrier_list[0]["id"] : 0), "couponcount" => $couponcount, "isvirtual" => $isvirtual, "isverify" => $isverify, "goods" => $goodsdata, "merchs" => $merchs, "orderdiyformid" => $orderdiyformid, "mustbind" => $mustbind );
            $buyagain = $buyagainprice;
        }
        else
        {
            $merchdata = m('order')->merchData();
            extract($merchdata);
            $merch_array = array( );
            $merchs = array( );
            $g = $goods;
            $package = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_package") . " WHERE uniacid = " . $uniacid . " and id = " . $packageid . " ");
            $package = set_medias($package, array( "thumb" ));
            if( time() < $package["starttime"] )
            {
                return ['status'=>AppError::$OrderCreatePackageTimeNotStart,'msg'=>'','data'=>[]];
            }
            if( $package["endtime"] < time() )
            {
                return ['status'=>AppError::$OrderCreatePackageTimeEnd,'msg'=>'','data'=>[]];
            }
            $goods = array( );
            $goodsprice = 0;
            $goodsdeduct = 0;
            //折扣
            $discount=0;
            $marketprice = 0;
            $goods_list = array( );
            foreach( $g as $key => $value )
            {
                $goods[$key] = pdo_fetch("select id,title,thumb,marketprice,merchid,dispatchtype,dispatchid,dispatchprice,deduct,deduct_type from " . tablename("ewei_shop_goods") . "\r\n                            where id = " . $value["goodsid"] . " and uniacid = " . $uniacid . " ");
                //if( $is_openmerch == 1 )
                //{
                //    $merchid = $goods[$key]["merchid"];
                //    $merch_array[$merchid]["goods"][] = $goods[$key]["id"];
                //}
                $option = array( );
                $packagegoods = array( );
                if( 0 < $value["optionid"] )
                {
                    $option = pdo_fetch("select title,packageprice from " . tablename("ewei_shop_package_goods_option") . "\r\n                            where optionid = " . $value["optionid"] . " and goodsid=" . $value["goodsid"] . " and uniacid = " . $uniacid . " and pid = " . $packageid . " ");
                    $goods[$key]["packageprice"] = $option["packageprice"];
                }
                else
                {
                    $packagegoods = pdo_fetch("select title,packageprice from " . tablename("ewei_shop_package_goods") . "\r\n                            where goodsid=" . $value["goodsid"] . " and uniacid = " . $uniacid . " and pid = " . $packageid . " ");
                    $goods[$key]["packageprice"] = $packagegoods["packageprice"];
                }
                $goods[$key]["optiontitle"] = (!empty($option["title"]) ? $option["title"] : "");
                $goods[$key]["optionid"] = (!empty($value["optionid"]) ? $value["optionid"] : 0);
                $goods[$key]["goodsid"] = $value["goodsid"];
                $goods[$key]["total"] = 1;
                //如果有标签属性  那么取属性的价格
                $goods[$key]["packageprice"] = $option ? $option["packageprice"] : $goods[$key]["packageprice"];
                //if( $is_openmerch == 1 )
                //{
                //    $merch_array[$merchid]["ggprice"] += $goods[$key]["packageprice"];
                //}
                $goodsprice += price_format($goods[$key]["packageprice"]);
                //折扣
                if ($goods[$key]["deduct_type"]==1){
                    $goodsdeduct += price_format($goods[$key]["deduct"]);
                }else{
                    $discount+=price_format($goods[$key]["deduct"]);
                }

                $marketprice += price_format($goods[$key]["marketprice"]);
            }
            $address = pdo_fetch("select * from " . tablename("ewei_shop_member_address") . " where (openid=:openid or user_id = :user_id) and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1", array( ":uniacid" => $uniacid, ":openid" => $member['openid'],":user_id" => $member['id'] ));
            $total = count($goods);
            $dispatch_price = $package["freight"];
            $realprice = $goodsprice + $package["freight"];
            if( 0 < $package["dispatchtype"] )
            {
                $dispatch_array = m("order")->getOrderDispatchPrice($goods, $member, $address, false, $merch_array, 0);
                $dispatch_price = $dispatch_array["dispatch_price"] - $dispatch_array["seckill_dispatch_price"];
            }
            else
            {
                $dispatch_price = $package["freight"];
            }
            $realprice = $goodsprice + $dispatch_price;
            $packprice = $goodsprice;
            $token = md5(microtime());
            $_SESSION["order_token"] = $token;
            $createInfo = array( "id" => 0, "gdid" => intval($gdid), "fromcart" => 0, "packageid" => $packageid, "addressid" => $address["id"], "storeid" => 0, "couponcount" => 0, "isvirtual" => 0, "isverify" => 0, "goods" => $goods, "merchs" => $merchs, "orderdiyformid" => 0, "token" => $token, "mustbind" => 0 );
            $goods_list = array( );
            $goods_list[0]["shopname"] = $_W["shopset"]["shop"]["name"];
            $goods_list[0]["goods"] = $goods;
            $card_info = array( );
            $plugin_membercard = p("membercard");
            if( !$plugin_membercard )
            {
                $canusecard = false;
            }
            $availablecard_count = 0;
            $carddiscountprice = 0;
            if( $canusecard )
            {
                $mycard = $plugin_membercard->get_Mycard($member['openid']);
                if( $mycard["list"] )
                {
                    $all_mycardlist = $mycard["list"];
                    $card_info["all_mycardlist"] = $all_mycardlist;
                    $availablecard_count = $mycard["total"];
                }
            }
            $card_info["availablecard_count"] = $availablecard_count;
            $card_info["cardid"] = 0;
            $card_info["carddiscountprice"] = 0;
        }
        $_W["shopshare"]["hideMenus"] = array( "menuItem:share:qq", "menuItem:share:QZone", "menuItem:share:email", "menuItem:copyUrl", "menuItem:openWithSafari", "menuItem:openWithQQBrowser", "menuItem:share:timeline", "menuItem:share:appMessage" );
        $allgoods = array( );
        foreach( $goods_list as $k => $v )
        {
            $allgoods[$k]["shopname"] = $v["shopname"];
            foreach( $v["goods"] as $g )
            {
                $allgoods[$k]["goods"][] = array( "id" => $g["goodsid"], "goodsid" => $g["goodsid"], "title" => $g["title"], "thumb" => tomedia($g["thumb"]), "optionid" => (int) $g["optionid"], "optiontitle" => $g["optiontitle"],"is_remote"=>$isdispatcharea == 1 && $is_remote == 0 ? 0 : 1, "hasdiscount" => empty($g["isnodiscount"]) && !empty($g["dflag"]), "total" => $g["total"], "price" => ($g["unitprice"] < $g["marketprice"] ? (double) $g["marketprice"] : (double) $g["unitprice"]), "marketprice" => (double) $g["marketprice"], "merchid" => $g["merchid"], "cates" => $g["cates"], "unit" => $g["unit"], "totalmaxbuy" => $g["totalmaxbuy"], "minbuy" => $g["minbuy"], "promotionprice" => (($g["unitprice"] < $g["marketprice"] ? (double) $g["marketprice"] : (double) $g["unitprice"])) - $g["isdiscountprice"] );
            }
        }
        $sysset = m("common")->getSysset("trade");

        //折扣宝
        $credit3=$member["credit3"];
        if ($credit3<$discount){
            $discount=$credit3;
        }
        $result = array( "member" => array( "realname" => $member["realname"], "mobile" => $member["carrier_mobile"] ), "showTab" => 0 < count($carrier_list) && !$isverify && !$isvirtual, "showAddress" => !$isverify && !$isvirtual, "isverify" => $isverify, "isvirtual" => $isvirtual, "set_realname" => $sysset["set_realname"], "set_mobile" => $sysset["set_mobile"], "carrierInfo" => (!empty($carrier_list) ? $carrier_list[0] : false), "storeInfo" => false, "address" => $address, "goods" => $allgoods, "merchid" => $merch_id, "packageid" => $packageid, "fullbackgoods" => $fullbackgoods, "giftid" => $giftid, "gift" => $gift, "gifts" => $gifts, "gifttitle" => $gifttitle, "changenum" => $changenum, "hasinvoice" => (bool) $hasinvoice, "invoicename" => $invoicename, "couponcount" => (int) $couponcount, "deductcredit" => $deductcredit, "deductmoney" => $deductmoney, "discount"=>$discount,"deductcredit2" => $deductcredit2, "stores" => $stores, "storeids" => implode(",", $storeids), "fields" => (!empty($order_formInfo) ? $fields : false), "f_data" => (!empty($order_formInfo) ? $f_data : false), "dispatch_price" => $dispatch_price, "goodsprice" =>$flag == true? 0 :$goodsprice,"goodsdeduct"=>$goodsdeduct ,"taskdiscountprice" => $taskdiscountprice, "discountprice" => $discountprice, "isdiscountprice" => $isdiscountprice, "showenough" => (empty($saleset["showenough"]) ? false : true), "enoughmoney" => $saleset["enoughmoney"], "enoughdeduct" => $saleset["enoughdeduct"], "merch_showenough" => (empty($merch_saleset["merch_showenough"]) ? false : true), "merch_enoughmoney" => (double) $merch_saleset["merch_enoughmoney"], "merch_enoughdeduct" => (double) $merch_saleset["merch_enoughdeduct"], "merchs" => (array) $merchs, "realprice" => $flag == true ? $dispatch_price :round($realprice, 2), "total" => $total, "buyagain" => round($buyagain, 2), "fromcart" => (int) $fromcart, "isonlyverifygoods" => $isonlyverifygoods, "isforceverifystore" => $isforceverifystore, "city_express_state" => (empty($dispatch_array["city_express_state"]) ? 0 : $dispatch_array["city_express_state"]), "canusecard" => $canusecard, "card_info" => $card_info, "carddiscountprice" => $carddiscountprice, "card_free_dispatch" => $card_free_dispatch );
        if( $iscycel )
        {
            $cycelset = m("common")->getSysset("cycelbuy");
            $selectDate = date("Ymd", $selectDate);
            $result["selectDate"] = $selectDate;
            $result["cycelComboUnit"] = $cycelbuy_unit;
            $result["cycelComboDay"] = $cycelbuy_day;
            $result["cycelComboPeriods"] = $cycelbuy_num;
            $result["iscycelbuy"] = $iscycel;
            $result["receipttime"] = $selectDate;
            $result["scope"] = $cycelset["days"];
        }
        $result["fromquick"] = intval($fromquick);
        $result["fullbacktext"] = m("sale")->getFullBackText();
        $result["seckill_dispatchprice"] = intval($seckill_dispatchprice);
        $result["seckill_price"] = intval($seckill_price);
        $result["seckill_payprice"] = intval($seckill_payprice);
        $result['isdispatcharea'] = $isdispatcharea;
        $result['remote_dispatchprice'] = $remote_dispatchprice;
        //当是偏远地区  外加不支持发货的时候  才为0  其他  都为1
        $result['is_remote'] = $isdispatcharea == 1 && $is_remote == 0 ? 0 : 1;
        $result['is_gift'] = $flag ? 1 : 0;
        if( $hasinvoice )
        {
            $result["invoice_info"] = $invoice_arr;
            $result["invoice_type"] = $invoice_type;
        }
        return ['status'=>0,'msg'=>'','data'=>$result];
    }

    /**
     * 确认订单页  切换地址
     * @param $user_id
     * @param $addressid
     * @param $goods
     * @param $packageid
     * @param $totalprice
     * @param $dflag
     * @param $cardid
     * @param $bargain_id
     * @param $couponid
     * @return array
     */
    public function order_caculate($user_id,$addressid,$goods,$packageid,$totalprice,$dflag,$cardid,$bargain_id,$couponid)
    {
        global $_W;
        $uniacid = $_W["uniacid"];
        $member = m("member")->getMember($user_id, true);
        $ispackage = 0;
        $merchdata = m('order')->merchData();
        extract($merchdata);
        $merch_array = array( );
        $allow_sale = true;
        $realprice = 0;
        $nowsendfree = false;
        $isverify = false;
        $isvirtual = false;
        $taskdiscountprice = 0;
        $discountprice = 0;
        $isdiscountprice = 0;
        $deductprice = 0;
        $deductprice2 = 0;
        $deductcredit2 = 0;
        $buyagain_sale = true;
        $iscycelbuy = false;
        $isonlyverifygoods = true;
        $isgift = true;
        $buyagainprice = 0;
        $seckill_price = 0;
        $seckill_payprice = 0;
        $seckill_dispatchprice = 0;
        $address = pdo_fetch("select * from " . tablename("ewei_shop_member_address") . " where  id=:id and (openid=:openid or user_id = :user_id) and uniacid=:uniacid limit 1", array( ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id'], ":id" => $addressid ));
        $level = m("member")->getLevel($member['openid']);
        $dispatch_price = 0;
        $deductenough_money = 0;
        $deductenough_enough = 0;
        $goodsarr = $goods;
        $zhekou=0;
        $kaluli=0;
        if( is_string($goodsarr) )
        {
            $goodsstring = htmlspecialchars_decode(str_replace("\\", "", $goods));
            $goodsarr = @json_decode($goodsstring, true);
        }
        $flag = false;
        if(count($goodsarr) == 1){
            $flag = m('game')->gift_check($member['openid'],$goodsarr[0]['id']);
        }
        if( $cardid )
        {
            $packageid = 0;
        }
        if( 0 < $packageid )
        {
            $ispackage = 1;
            $isgift = false;
            if( is_array($goodsarr) )
            {
                $package = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_package") . " WHERE uniacid = " . $uniacid . " and id = " . $packageid . " ");
                $package = set_medias($package, array( "thumb" ));
                //套餐未开始
                if( time() < $package["starttime"] )
                {
                    return ['status'=>AppError::$OrderCreatePackageTimeNotStart,'msg'=>'','data'=>[]];
                }
                if( $package["endtime"] < time() )
                {
                    return ['status'=>AppError::$OrderCreatePackageTimeEnd,'msg'=>'','data'=>[]];
                }
                $goods = array( );
                $goodsprice = 0;
                $marketprice = 0;
                $goods_list = array( );
                foreach( $goodsarr as $key => $value )
                {
                    $goods[$key] = pdo_fetch("select id,title,thumb,marketprice from " . tablename("ewei_shop_goods") . "\r\n                            where id = " . $value["goodsid"] . " and uniacid = " . $uniacid . " ");
                    $option = array( );
                    $packagegoods = array( );
                    if( 0 < $value["optionid"] )
                    {
                        $option = pdo_fetch("select title,packageprice from " . tablename("ewei_shop_package_goods_option") . "\r\n                            where optionid = " . $value["optionid"] . " and goodsid=" . $value["goodsid"] . " and uniacid = " . $uniacid . " and pid = " . $packageid . " ");
                        $goods[$key]["packageprice"] = $option["packageprice"];
                    }
                    else
                    {
                        $packagegoods = pdo_fetch("select title,packageprice from " . tablename("ewei_shop_package_goods") . "\r\n                            where goodsid=" . $value["goodsid"] . " and uniacid = " . $uniacid . " and pid = " . $packageid . " ");
                        $goods[$key]["packageprice"] = $packagegoods["packageprice"];
                    }
                    $goods[$key]["optiontitle"] = (!empty($option["title"]) ? $option["title"] : "");
                    $goods[$key]["optionid"] = (!empty($value["optionid"]) ? $value["optionid"] : 0);
                    $goods[$key]["goodsid"] = $value["goodsid"];
                    $goods[$key]["total"] = 1;
                    $goods[$key]["packageprice"] = $option ? $option["packageprice"] : $goods[$key]["packageprice"];
                    $goodsprice += price_format($goods[$key]["packageprice"]);
                    $marketprice += price_format($goods[$key]["marketprice"]);
                }
                $address = pdo_fetch("select * from " . tablename("ewei_shop_member_address") . " where (openid=:openid or user_id = :user_id) and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1", array( ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id']));
                $total = count($goods);
                $dispatch_price = $package["freight"];
                $realprice = $goodsprice + $package["freight"];
            }
            $plugin_membercard = p("membercard");
            $card_info = array( );
            $availablecard_count = 0;
            $carddiscountprice = 0;
            if( $plugin_membercard )
            {
                $mycard = $plugin_membercard->get_Mycard($member['openid']);
                if( $mycard["list"] )
                {
                    $all_mycardlist = $mycard["list"];
                    $card_info["all_mycardlist"] = $all_mycardlist;
                    $availablecard_count = $mycard["total"];
                }
            }
            $card_info["availablecard_count"] = $availablecard_count;
            $card_info["cardid"] = 0;
            $card_info["cardname"] = "未选择会员卡";
            $card_info["carddiscount_rate"] = 0;
            $card_info["carddiscountprice"] = $carddiscountprice;
        }
        else
        {
            if( is_array($goodsarr) )
            {
                $weight = 0;
                $allgoods = array( );
                foreach( $goodsarr as &$g )
                {
                    if( empty($g) )
                    {
                        continue;
                    }
                    $goodsid = $g["goodsid"];
                    $optionid = $g["optionid"];
                    $goodstotal = $g["total"];
                    if( $goodstotal < 1 )
                    {
                        $goodstotal = 1;
                    }
                    if( empty($goodsid) )
                    {
                        $nowsendfree = true;
                    }
                    if( 0 < $bargain_id )
                    {
                        $data = $g;
                    }
                    else
                    {
                        $sql = "SELECT id as goodsid,title,type, weight,total,issendfree,isnodiscount, thumb,marketprice,cash,isverify,isforceverifystore,goodssn,productsn,sales,istime," . " timestart,timeend,usermaxbuy,maxbuy,unit,buylevels,buygroups,deleted,status,deduct,deduct_type,ispresell,presellprice,preselltimeend,manydeduct,`virtual`," . " discounts,deduct2,ednum,edmoney,edareas,diyformid,diyformtype,diymode,dispatchtype,dispatchid,dispatchprice,is_remote,remote_dispatchprice," . " isdiscount,isdiscount_time,isdiscount_discounts ,virtualsend,merchid,merchsale," . " buyagain,buyagain_islong,buyagain_condition, buyagain_sale,bargain" . " FROM " . tablename("ewei_shop_goods") . " where id=:id and uniacid=:uniacid  limit 1";
                        $data = pdo_fetch($sql, array( ":uniacid" => $uniacid, ":id" => $goodsid ));
                        $data["seckillinfo"] = plugin_run("seckill::getSeckill", $goodsid, $optionid, true, $_W["openid"]);
                        if( 0 < $data["ispresell"] && ($data["preselltimeend"] == 0 || time() < $data["preselltimeend"]) )
                        {
                            $data["marketprice"] = $data["presellprice"];
                        }
                        if ($data["deduct_type"]==1){
                            $kaluli+=$data["deduct"];
                        }else{
                            $zhekou+=$data["deduct"];
                        }
                    }
                    if( empty($data) )
                    {
                        $nowsendfree = true;
                    }
                    if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
                    {
                        $data["is_task_goods"] = 0;
                        $isgift = false;
                    }
                    else
                    {
                        $rank = intval($_SESSION[$goodsid . "_rank"]);
                        $join_id = intval($_SESSION[$goodsid . "_join_id"]);
                        $task_goods_data = m("goods")->getTaskGoods($member['openid'], $goodsid, $rank, $join_id, $optionid);
                        if( empty($task_goods_data["is_task_goods"]) )
                        {
                            $data["is_task_goods"] = 0;
                        }
                        else
                        {
                            $allow_sale = false;
                            $data["is_task_goods"] = $task_goods_data["is_task_goods"];
                            $data["is_task_goods_option"] = $task_goods_data["is_task_goods_option"];
                            $data["task_goods"] = $task_goods_data["task_goods"];
                        }
                    }
                    $data["stock"] = $data["total"];
                    $data["total"] = $goodstotal;
                    if( !empty($optionid) )
                    {
                        $option = pdo_fetch("select id,title,marketprice,presellprice,goodssn,productsn,stock,`virtual`,weight,cycelbuy_periodic from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array( ":uniacid" => $uniacid, ":goodsid" => $goodsid, ":id" => $optionid ));
                        if( !empty($option) )
                        {
                            $data["optionid"] = $optionid;
                            $data["optiontitle"] = $option["title"];
                            $data["marketprice"] = (0 < intval($data["ispresell"]) && (time() < $data["preselltimeend"] || $data["preselltimeend"] == 0) ? $option["presellprice"] : $option["marketprice"]);
                            if( !empty($option["weight"]) )
                            {
                                $data["weight"] = $option["weight"];
                            }
                        }
                        $cycelbuy_periodic = explode(",", $option["cycelbuy_periodic"]);
                        list($cycelbuy_day, $cycelbuy_unit, $cycelbuy_num) = $cycelbuy_periodic;
                    }
                    if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
                    {
                        $data["ggprice"] = $data["seckillinfo"]["price"] * $g["total"];
                        $seckill_payprice += $data["ggprice"];
                        $seckill_price += $data["marketprice"] * $g["total"];
                    }
                    else
                    {
                        $prices = m("order")->getGoodsDiscountPrice($data, $level);
                        $data["ggprice"] = $prices["price"];
                    }
                    if( $is_openmerch == 1 )
                    {
                        $merchid = $data["merchid"];
                        $merch_array[$merchid]["goods"][] = $data["goodsid"];
                        $merch_array[$merchid]["ggprice"] += $data["ggprice"];
                    }
                    if( $data["isverify"] == 2 )
                    {
                        $isverify = true;
                        $isgift = false;
                    }
                    if( !empty($data["virtual"]) || $data["type"] == 2 )
                    {
                        $isvirtual = true;
                    }
                    if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
                    {
                        $g["taskdiscountprice"] = 0;
                        $g["lotterydiscountprice"] = 0;
                        $g["discountprice"] = 0;
                        $g["isdiscountprice"] = 0;
                        $g["discounttype"] = 0;
                    }
                    else
                    {
                        $g["taskdiscountprice"] = $prices["taskdiscountprice"];
                        $g["discountprice"] = $prices["discountprice"];
                        $g["isdiscountprice"] = $prices["isdiscountprice"];
                        $g["discounttype"] = $prices["discounttype"];
                        $taskdiscountprice += $prices["taskdiscountprice"];
                        $buyagainprice += $prices["buyagainprice"];
                    }
                    if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 || $_SESSION["taskcut"] )
                    {
                    }
                    else
                    {
                        if( $prices["discounttype"] == 1 )
                        {
                            $isdiscountprice += $prices["isdiscountprice"];
                        }
                        else
                        {
                            if( $prices["discounttype"] == 2 )
                            {
                                $discountprice += $prices["discountprice"];
                            }
                        }
                    }
                    if( !empty($bargain_id) && p("bargain") )
                    {
                        $discountprice = 0;
                    }
                    $realprice += $data["ggprice"];
                    $allgoods[] = $data;
                    if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
                    {
                    }
                    else
                    {
                        if( 0 < floatval($g["buyagain"]) && empty($g["buyagain_sale"]) && m("goods")->canBuyAgain($g) )
                        {
                            $buyagain_sale = false;
                        }
                    }
                }
                unset($g);
                if( $is_openmerch == 1 )
                {
                    foreach( $merch_array as $key => $value )
                    {
                        if( 0 < $key )
                        {
                            $merch_array[$key]["set"] = $merch_plugin->getSet("sale", $key);
                            $merch_array[$key]["enoughs"] = $merch_plugin->getEnoughs($merch_array[$key]["set"]);
                        }
                    }
                }
                $sale_plugin = com("sale");
                $saleset = false;
                if( $sale_plugin && $buyagain_sale && $allow_sale )
                {
                    $saleset = $_W["shopset"]["sale"];
                    $saleset["enoughs"] = $sale_plugin->getEnoughs();
                }
                foreach( $allgoods as $g )
                {
                    if( $g["type"] != 5 && $isonlyverifygoods == true )
                    {
                        $isonlyverifygoods = false;
                    }
                    if( $g["type"] == 9 )
                    {
                        $iscycelbuy = true;
                    }
                    if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                    {
                        $g["deduct"] = 0;
                    }
                    else
                    {
                        if( 0 < floatval($g["buyagain"]) && empty($g["buyagain_sale"]) && m("goods")->canBuyAgain($g) )
                        {
                            $g["deduct"] = 0;
                        }
                    }
                    if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                    {
                    }
                    else
                    {
                        if( $g["manydeduct"] )
                        {
                            $deductprice += $g["deduct"] * $g["total"];
                        }
                        else
                        {
                            $deductprice += $g["deduct"];
                        }
                        if( $g["deduct2"] == 0 )
                        {
                            $deductprice2 += $g["ggprice"];
                        }
                        else
                        {
                            if( 0 < $g["deduct2"] )
                            {
                                if( $g["ggprice"] < $g["deduct2"] )
                                {
                                    $deductprice2 += $g["ggprice"];
                                }
                                else
                                {
                                    $deductprice2 += $g["deduct2"];
                                }
                            }
                        }
                    }
                }
                if( $isverify || $isvirtual )
                {
                    $nowsendfree = true;
                }
                if( !empty($allgoods) && !$nowsendfree && !$isonlyverifygoods )
                {
                    $dispatch_array = m("order")->getOrderDispatchPrice($allgoods, $member, $address, $saleset, $merch_array, 1);
                    $dispatch_price = $dispatch_array["dispatch_price"] - $dispatch_array["seckill_dispatch_price"];
                    $nodispatch_array = $dispatch_array["nodispatch_array"];
                    $seckill_dispatchprice = $dispatch_array["seckill_dispatch_price"];
                    $isdispatcharea = $dispatch_array['isdispatcharea'];
                }
                $plugin_membercard = p("membercard");
                $card_info = array( );
                $carddiscountprice = 0;
                $carddiscount_rate = 0;
                $card_free_dispatch = false;
                $pure_totalprice = $realprice;
                $cardname = "未选择会员卡";
                $select_cardid = 0;
                if( $plugin_membercard && $cardid )
                {
                    $card_result = m('order')->caculatecard($cardid, $dispatch_price, $pure_totalprice, $discountprice, $isdiscountprice);
                    if( $card_result )
                    {
                        $card_info["dispatch_price"] = $dispatch_price;
                        $dispatch_price = $card_result["dispatch_price"];
                        $carddiscountprice = $card_result["carddiscountprice"];
                        $card_info["old_discountprice"] = $discountprice;
                        $discountprice = $card_result["discountprice"];
                        $card_info["old_isdiscountprice"] = $isdiscountprice;
                        $isdiscountprice = $card_result["isdiscountprice"];
                        $cardname = $card_result["cardname"];
                        $carddiscount_rate = $card_result["carddiscount_rate"];
                        $select_cardid = $cardid;
                    }
                }
                $card_info["cardname"] = $cardname;
                $card_info["carddiscount_rate"] = $carddiscount_rate;
                $card_info["carddiscountprice"] = $carddiscountprice;
                $card_info["cardid"] = $select_cardid;
                if( 0 < $card_info["dispatch_price"] && $dispatch_price == 0 )
                {
                    $card_free_dispatch = true;
                }
                if( 0 < $card_info["old_discountprice"] && $discountprice == 0 )
                {
                    $realprice += $card_info["old_discountprice"];
                }
                if( 0 < $card_info["old_isdiscountprice"] && $isdiscountprice == 0 )
                {
                    $realprice += $card_info["old_isdiscountprice"];
                }
                $realprice -= $carddiscountprice;
                if( $is_openmerch == 1 )
                {
                    $merch_enough = m("order")->getMerchEnough($merch_array);
                    $merch_array = $merch_enough["merch_array"];
                    $merch_enough_total = $merch_enough["merch_enough_total"];
                    $merch_saleset = $merch_enough["merch_saleset"];
                    if( 0 < $merch_enough_total )
                    {
                        $realprice -= $merch_enough_total;
                    }
                }
                if( $saleset )
                {
                    foreach( $saleset["enoughs"] as $e )
                    {
                        if( floatval($e["enough"]) <= $realprice - $seckill_payprice && 0 < floatval($e["money"]) )
                        {
                            $deductenough_money = floatval($e["money"]);
                            $deductenough_enough = floatval($e["enough"]);
                            $realprice -= floatval($e["money"]);
                            break;
                        }
                    }
                }
                if( empty($dflag) )
                {
                    if( empty($saleset["dispatchnodeduct"]) )
                    {
                        $deductprice2 += $dispatch_price;
                    }
                }
                else
                {
                    $dispatch_price = 0;
                }
                $goodsdata_coupon = array( );
                $remote_dispatchprice = 0;
                $is_remote = 1;
                foreach( $allgoods as $g )
                {
                    if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
                    {
                    }
                    else
                    {
                        if( 0 < floatval($g["buyagain"]) )
                        {
                            if( !m("goods")->canBuyAgain($g) || !empty($g["buyagain_sale"]) )
                            {
                                $goodsdata_coupon[] = array( "goodsid" => $g["goodsid"], "total" => $g["total"], "optionid" => $g["optionid"], "marketprice" => $g["marketprice"], "merchid" => $g["merchid"], "cates" => $g["cates"], "discounttype" => $g["discounttype"], "isdiscountprice" => $g["isdiscountprice"], "discountprice" => $g["discountprice"], "isdiscountunitprice" => $g["isdiscountunitprice"], "discountunitprice" => $g["discountunitprice"] );
                            }
                        }
                        else
                        {
                            $goodsdata_coupon[] = array( "goodsid" => $g["goodsid"], "total" => $g["total"], "optionid" => $g["optionid"], "marketprice" => $g["marketprice"], "merchid" => $g["merchid"], "cates" => $g["cates"], "discounttype" => $g["discounttype"], "isdiscountprice" => $g["isdiscountprice"], "discountprice" => $g["discountprice"], "isdiscountunitprice" => $g["isdiscountunitprice"], "discountunitprice" => $g["discountunitprice"] );
                        }
                    }
                    $remote_dispatchprice += $g['remote_dispatchprice'];
                    if($g['is_remote'] == 0){
                        $is_remote = 0;
                    }
                }
                $couponcount = com_run("coupon::consumeCouponCount", $member['openid'], $realprice - $seckill_payprice, $merch_array, $goodsdata_coupon);
                if( empty($goodsdata_coupon) || !$allow_sale )
                {
                    $couponcount = 0;
                }
                if( $iscycelbuy )
                {
                    $realprice += $dispatch_price * $cycelbuy_num;
                }
                else
                {
                    $realprice += $dispatch_price + $seckill_dispatchprice;
                }
                $deductcredit = 0;
                $deductmoney = 0;
                //折扣宝

                $discountmoney=0;
                if( !empty($saleset) )
                {
                    $credit = $member["credit1"];
                    if( !empty($saleset["creditdeduct"]) )
                    {
                        $pcredit = intval($saleset["credit"]);
                        $pmoney = round(floatval($saleset["money"]), 2);
                        if( 0 < $pcredit && 0 < $pmoney )
                        {
                            if( $credit % $pcredit == 0 )
                            {
                                $deductmoney = round(intval($credit / $pcredit) * $pmoney, 2);
                            }
                            else
                            {
                                $deductmoney = round((intval($credit / $pcredit) + 1) * $pmoney, 2);
                            }
                        }
                        if( $deductprice < $deductmoney )
                        {
                            $deductmoney = $deductprice;
                        }
                        if( $realprice - $seckill_payprice < $deductmoney )
                        {
                            $deductmoney = $realprice - $seckill_payprice;
                        }
                        $deductcredit = ($pmoney * $pcredit == 0 ? 0 : $deductmoney / $pmoney * $pcredit);
                    }
                    if( !empty($saleset["moneydeduct"]) )
                    {
                        $deductcredit2 = $member["credit2"];
                        if( $realprice - $seckill_payprice < $deductcredit2 )
                        {
                            $deductcredit2 = $realprice - $seckill_payprice;
                        }
                        if( $deductprice2 < $deductcredit2 )
                        {
                            $deductcredit2 = $deductprice2;
                        }
                    }
                }
            }
        }
        if( $is_openmerch == 1 )
        {
            $merchs = $merch_plugin->getMerchs($merch_array);
        }
        $coupon_deductprice = 0;
        if( $couponid )
        {
            $express_fee = $dispatch_price + $seckill_dispatchprice;
            $coupon_price = m('order')->caculatecoupon($couponid, $goodsdata_coupon, $totalprice, $discountprice, $isdiscountprice, 0, array( ), 0, $realprice - $express_fee);
            $coupon_deductprice = $coupon_price["deductprice"];
            //lihanwen
            $sql = "SELECT d.id,d.couponid,c.enough,c.backtype,c.deduct,c.discount,c.backmoney,c.backcredit,c.backredpack,c.merchid,c.limitgoodtype,c.limitgoodcatetype,c.limitgoodids,c.limitgoodcateids,c.limitdiscounttype  FROM " . tablename("ewei_shop_coupon_data") . " d";
            $sql .= " left join " . tablename("ewei_shop_coupon") . " c on d.couponid = c.id";
            $sql .= " where d.id=:id and d.uniacid=:uniacid and (d.openid=:openid or d.user_id = :user_id) and d.used=0  limit 1";
            $coupondata = pdo_fetch($sql, array( ":uniacid" => $uniacid, ":id" => $couponid, ":openid" => $member['openid'], ":user_id" => $member['id']));

            $deductcredit2 -= $coupon_deductprice;
            $deductmoney -= $coupon_deductprice;
            $deductcredit = ($pmoney * $pcredit == 0 ? 0 : $deductmoney / $pmoney * $pcredit);
            if( !empty($coupondata) && $coupondata['couponid']==2)
            {
                $deductcredit = $deductcredit2 = -$realprice;
                $coupon_deductprice = $realprice;
            }
        }
        $gifts = array( );
        if( $isgift )
        {
            $all_price = $realprice - $dispatch_price - $coupon_deductprice;
            $gifts = pdo_fetchall("select id,goodsid,giftgoodsid,thumb,title,orderprice from " . tablename("ewei_shop_gift") . "\r\n                    where uniacid = " . $uniacid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " and orderprice <= " . $all_price . " and activity = 1 ");
            $giftgoods = array( );
            foreach( $gifts as $key => $value )
            {
                $giftgoods = explode(",", $value["giftgoodsid"]);
                foreach( $giftgoods as $k => $val )
                {
                    $gifts[$key]["gift"][$k] = pdo_fetch("select id,title,thumb,marketprice from " . tablename("ewei_shop_goods") . " where uniacid = " . $uniacid . " and status = 2 and id = " . $val . " ");
                }
                $gifts[$key]["gift"] = set_medias($gifts[$key]["gift"], array( "thumb" ));
                $gifttitle = $gifts[$key]["gift"][0]["title"];
            }
            $gifts = set_medias($gifts, array( "thumb" ));
        }
        $return_array = array( );
        $return_array["price"] = $dispatch_price + $seckill_dispatchprice;
        $return_array["couponcount"] = (int) $couponcount;
        $return_array["realprice"] = $flag == true ? $dispatch_price :round($realprice, 2);
        $return_array["deductenough_money"] = $deductenough_money;
        $return_array["deductenough_enough"] = $deductenough_enough;
        $return_array["deductcredit2"] = $deductcredit2;
        if ($member["credit1"]<$kaluli){
            $kaluli=$member["credit1"];
        }
        if ($member["credit3"]<$zhekou){
            $zhekou=$member["credit3"];
        }
        $return_array["deductcredit"]=ceil($kaluli);
        $return_array["deductmoney"]=$kaluli;
        $return_array["discount"]=$zhekou;

        $return_array["taskdiscountprice"] = $taskdiscountprice;
        $return_array["discountprice"] = $discountprice;
        $return_array["isdiscountprice"] = $isdiscountprice;
        $return_array["merch_showenough"] = (double) $merch_saleset["merch_showenough"];
        $return_array["merch_deductenough_money"] = (double) $merch_saleset["merch_enoughdeduct"];
        $return_array["merch_deductenough_enough"] = (double) $merch_saleset["merch_enoughmoney"];
        $return_array["merchs"] = (array) $merchs;
        $return_array["buyagain"] = round($buyagainprice, 2);
        $return_array["seckillprice"] = $seckill_price - $seckill_payprice;
        $return_array["city_express_state"] = (empty($dispatch_array["city_express_state"]) ? 0 : $dispatch_array["city_express_state"]);
        $return_array["card_info"] = $card_info;
        $return_array["carddiscountprice"] = $carddiscountprice;
        $return_array["card_free_dispatch"] = $card_free_dispatch;
        $return_array["coupon_deductprice"] = $coupon_deductprice;
        $return_array["gifts"] = $gifts;
        $return_array['isdispatcharea'] = $isdispatcharea;
        $return_array['remote_dispatchprice'] = $remote_dispatchprice;
        //当是偏远地区  外加不支持发货的时候  才为0  其他  都为1
        $return_array['is_remote'] = $is_remote == 0 && $isdispatcharea == 1 ? 0 :1;
        $return_array['is_gift'] = $flag ? 1 : 0;
        if( !empty($nodispatch_array["isnodispatch"]) )
        {
            $return_array["isnodispatch"] = 1;
            $return_array["nodispatch"] = $nodispatch_array["nodispatch"];
        }
        else
        {
            $return_array["isnodispatch"] = 0;
            $return_array["nodispatch"] = "";
        }
        return ['status'=>0,'msg'=>'','data'=>$return_array];
    }

    /**
     * @param $user_id
     * @param $addressid
     * @param $goods
     * @param $cardid
     * @param $packageid
     * @param $dispatchid
     * @param $dispatchtype
     * @param $carrierid
     * @param null $bargain_id
     * @param $giftid
     * @param $gdid
     * @param $carrier
     * @param $mid
     * @param $invoicename
     * @param $fromquick
     * @param $fromcart
     * @param $discount
     * @param $remark
     * @param $receipttime
     * @param $deduct1
     * @param $deduct2
     * @param $diydata
     * @param $couponid
     * @return array
     */
    public function order_submit($user_id,$addressid,$goods,$cardid,$packageid,$dispatchid,$dispatchtype,$carrierid,$bargain_id = null,$giftid,$gdid,$carrier,$mid,$invoicename,$fromquick,$fromcart,$discount1,$remark,$receipttime,$deduct1,$deduct2,$diydata,$couponid)
    {
        global $_W;
        $uniacid = $_W["uniacid"];
        $member = m("member")->getMember($user_id);
        //用户是否加入黑名单
        if( $member["isblack"] == 1 )
        {
            return ['status'=>AppError::$UserIsBlack,'msg'=>'','data'=>[]];
        }
        if( p("quick") && !empty($fromquick) )
        {
            $fromcart = 0;
        }
        $allow_sale = true;
        $packageid = intval($packageid);
        $package = array( );
        $packgoods = array( );
        $packageprice = 0;
        if( $cardid )
        {
            $packageid = 0;
        }
        if( !empty($packageid) )
        {
            $package = pdo_fetch("SELECT id,title,price,freight,cash,starttime,endtime,dispatchtype FROM " . tablename("ewei_shop_package") . "\r\n                    WHERE uniacid = " . $uniacid . " and id = " . $packageid . " and deleted = 0 and status = 1  ORDER BY id DESC");
            if( empty($package) )
            {
                return ['status'=>AppError::$OrderCreateNoPackage,'msg'=>'','data'=>[]];
            }
            if( time() < $package["starttime"] )
            {
                return ['status'=>AppError::$OrderCreatePackageTimeNotStart,'msg'=>'','data'=>[]];
            }
            if( $package["endtime"] < time() )
            {
                return ['status'=>AppError::$OrderCreatePackageTimeEnd,'msg'=>'','data'=>[]];
            }
            $packgoods = pdo_fetchall("SELECT id,title,thumb,packageprice,`option`,goodsid FROM " . tablename("ewei_shop_package_goods") . "\r\n                    WHERE uniacid = " . $uniacid . " and pid = " . $packageid . "  ORDER BY id DESC");
            if( empty($packgoods) )
            {
                return ['status'=>AppError::$OrderCreateNoPackage,'msg'=>'','data'=>[]];
            }
        }
        $data = m('order')->diyformData($member);
        extract($data);
        $merchdata = m('order')->merchData();
        extract($merchdata);
        $merch_array = array( );
        $ismerch = 0;
        $discountprice_array = array( );
        $level = m("member")->getLevel($member['openid']);
        $dispatchid = intval($dispatchid);
        $dispatchtype = intval($dispatchtype);
        $carrierid = intval($carrierid);
        if( is_string($goods) )
        {
            $goodsstring = htmlspecialchars_decode(str_replace("\\", "", $goods));
            $goods = @json_decode($goodsstring, true);
        }
        $goods_tmp = array( );
        foreach( $goods as $val )
        {
            $goods_tmp[] = $val;
        }
        $goods = $goods_tmp;
        $goods[0]["bargain_id"] = $bargain_id;
        if( !empty($goods[0]["bargain_id"]) )
        {
            $allow_sale = false;
        }
        if( empty($goods) || !is_array($goods) )
        {
            return ['status'=>AppError::$OrderCreateNoGoods,'msg'=>'','data'=>[]];
        }
        $allgoods = array( );
        $tgoods = array( );
        $totalprice = 0;
        $goodsprice = 0;
        $grprice = 0;
        $weight = 0;
        $taskdiscountprice = 0;
        $discountprice = 0;
        $isdiscountprice = 0;
        $merchisdiscountprice = 0;
        $cash = 1;

        $deductprice = 0;
        //折扣宝
        $discount=0;

        $deductprice2 = 0;
        $virtualsales = 0;
        $dispatch_price = 0;
        $seckill_price = 0;
        $seckill_payprice = 0;
        $seckill_dispatchprice = 0;
        $buyagain_sale = true;
        $buyagainprice = 0;
        $sale_plugin = com("sale");
        $saleset = false;
        if( $sale_plugin && $allow_sale )
        {
            $saleset = $_W["shopset"]["sale"];
            if( $packageid )
            {
                $saleset = false;
            }
            else
            {
                $saleset["enoughs"] = $sale_plugin->getEnoughs();
            }
        }
        $isvirtual = false;
        $isverify = false;
        $isonlyverifygoods = true;
        $iscycelbuy = false;
        $isendtime = 0;
        $endtime = 0;
        $verifytype = 0;
        $isvirtualsend = false;
        $couponmerchid = 0;

        if( $giftid )
        {
            $gift = array( );
            $giftdata = pdo_fetch("select giftgoodsid from " . tablename("ewei_shop_gift") . " where uniacid = " . $uniacid . " and id = " . $giftid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " ");
            if( $giftdata["giftgoodsid"] )
            {
                $giftgoodsid = explode(",", $giftdata["giftgoodsid"]);
                foreach( $giftgoodsid as $key => $value )
                {
                    $gift[$key] = pdo_fetch("select id as goodsid,title,thumb from " . tablename("ewei_shop_goods") . " where uniacid = " . $uniacid . " and status = 2 and id = " . $value . " and deleted = 0 ");
                }
                $goods = array_merge($goods, $gift);
            }
        }
        $flag = false;
        if(count($goods) == 1){
            $flag = m('game')->gift_check($member['openid'],$goods[0]['id']);
        }
        $is_remote = 1;
        foreach( $goods as $g )
        {
            if( empty($g) )
            {
                continue;
            }
            $goodsid = intval($g["id"]);
            $optionid = intval($g["optionid"]);
            $goodstotal = intval($g["total"]);
            if( $goodstotal < 1 )
            {
                $goodstotal = 1;
            }
            if( empty($goodsid) )
            {
                return ['status'=>AppError::$ParamsError,"msg"=>"ID=". $goodsid ."不存在!",'data'=>[]];
            }
            $sql = "SELECT id as goodsid,title,type, weight,total,issendfree,isnodiscount, thumb,marketprice,cash,isverify,isforceverifystore,verifytype," . " goodssn,productsn,sales,istime,timestart,timeend,isendtime,usetime,endtime,ispresell,presellprice,preselltimeend," . " usermaxbuy,minbuy,maxbuy,unit,buylevels,buygroups,deleted," . " status,deduct,deduct_type,manydeduct,`virtual`,discounts,deduct2,ednum,edmoney,edareas,diyformtype,diyformid,diymode," . " dispatchtype,dispatchid,dispatchprice,is_remote,remote_dispatchprice,merchid,merchsale,cates," . " isdiscount,isdiscount_time,isdiscount_discounts, virtualsend," . " buyagain,buyagain_islong,buyagain_condition, buyagain_sale,verifygoodsdays,verifygoodslimittype,verifygoodslimitdate" . " FROM " . tablename("ewei_shop_goods") . " where id=:id and uniacid=:uniacid  limit 1";
            $data = pdo_fetch($sql, array( ":uniacid" => $uniacid, ":id" => $goodsid ));
            if($data['is_remote'] == 0){
                $is_remote = 0;
            }
            $data["seckillinfo"] = plugin_run("seckill::getSeckill", $goodsid, $optionid, true, $member["openid"]);
            if( 0 < $data["ispresell"] && ($data["preselltimeend"] == 0 || time() < $data["preselltimeend"]) )
            {
                $data["marketprice"] = $data["presellprice"];
            }
            if( $data["type"] == 9 )
            {
                $iscycelbuy = true;
            }
            if( $data["type"] == 5 )
            {
                if( $data["verifygoodslimittype"] == 1 )
                {
                    if( $data["verifygoodslimitdate"] <= time() )
                    {
                        return ['status'=>AppError::$GoodsNotFound, 'msg'=>$data["title"]."商品使用时间已失效,无法购买 !",'data'=>[]];
                    }
                    if( $data["verifygoodslimitdate"] - 1800 <= time() )
                    {
                        return ['status'=>AppError::$GoodsNotFound, 'msg'=>$data["title"]."商品的使用时间即将失效,无法购买 !",'data'=>[]];
                    }
                    else
                    {
                        if( $data["verifygoodslimittype"] == 0 )
                        {
                            if( $data["verifygoodsdays"] * 3600 * 24 <= time() )
                            {
                                return ['status'=>AppError::$GoodsNotFound, 'msg'=>$data["title"] . "商品使用时间已失效,无法购买 !",'data'=>[]];
                            }
                            if( $data["verifygoodsdays"] * 3600 * 24 - 1800 <= time() )
                            {
                                return ['status'=>AppError::$GoodsNotFound, "msg"=>$data["title"]."商品的使用时间即将失效,无法购买 !",'data'=>[]];
                            }
                        }
                    }
                }
            }
            else
            {
                $isonlyverifygoods = false;
            }
            if( empty($data["status"]) || !empty($data["deleted"]) )
            {
                return ['status'=>AppError::$GoodsNotFound, 'msg'=>$data["title"]." 已下架!",'data'=>[]];
            }
            if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
            {
                $data["is_task_goods"] = 0;
                $tgoods = false;
            }
            else
            {
                $rank = intval($_SESSION[$goodsid . "_rank"]);
                $join_id = intval($_SESSION[$goodsid . "_join_id"]);
                $task_goods_data = m("goods")->getTaskGoods($member['openid'], $goodsid, $rank, $join_id, $optionid);
                if( empty($task_goods_data["is_task_goods"]) )
                {
                    $data["is_task_goods"] = 0;
                }
                else
                {
                    $allow_sale = false;
                    $tgoods["title"] = $data["title"];
                    $tgoods["openid"] = $member['openid'];
                    $tgoods["goodsid"] = $goodsid;
                    $tgoods["optionid"] = $optionid;
                    $tgoods["total"] = $goodstotal;
                    $data["is_task_goods"] = $task_goods_data["is_task_goods"];
                    $data["is_task_goods_option"] = $task_goods_data["is_task_goods_option"];
                    $data["task_goods"] = $task_goods_data["task_goods"];
                }
            }
            $merchid = $data["merchid"];
            $merch_array[$merchid]["goods"][] = $data["goodsid"];
            if( 0 < $merchid )
            {
                $ismerch = 1;
            }
            $virtualid = $data["virtual"];
            $data["stock"] = $data["total"];
            $data["total"] = $goodstotal;
            if( $data["cash"] != 2 )
            {
                $cash = 0;
            }
            if( !empty($packageid) )
            {
                $cash = $package["cash"];
            }
            $unit = (empty($data["unit"]) ? "件" : $data["unit"]);
            if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
            {
                $check_buy = plugin_run("seckill::checkBuy", $data["seckillinfo"], $data["title"], $data["unit"]);
                if( is_error($check_buy) )
                {
                    $message = str_replace("<br/>", "", $check_buy["message"]);
                    return ['status'=>1,'msg'=>$message,'data'=>[]];
                }
            }
            else
            {
                if( 0 < $data["minbuy"] && $goodstotal < $data["minbuy"] )
                {
                    return ['status'=>AppError::$OrderCreateMinBuyLimit, 'msg'=>$data["title"] . "<br/> " . $data["minbuy"] . $unit . "起售!",'data'=>[]];
                }
                if( 0 < $data["maxbuy"] && $data["maxbuy"] < $goodstotal )
                {
                    return ['status'=>AppError::$OrderCreateOneBuyLimit,'msg'=> $data["title"] . "<br/> 一次限购 " . $data["maxbuy"] . $unit . "!",'data'=>[]];
                }
                if( 0 < $data["usermaxbuy"] )
                {
                    $order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and (o.openid=:openid or o.user_id = :user_id) and og.uniacid=:uniacid ", array( ":goodsid" => $data["goodsid"], ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id'] ));
                    if( $data["usermaxbuy"] <= $order_goodscount )
                    {
                        return ['status'=>AppError::$OrderCreateMaxBuyLimit,'msg'=> $data["title"] . "<br/> 最多限购 " . $data["usermaxbuy"] . $unit . "!",'data'=>[]];
                    }
                }
                if( !empty($data["is_task_goods"]) && $data["task_goods"]["total"] < $goodstotal )
                {
                    return ['status'=>AppError::$OrderCreateMaxBuyLimit, 'msg'=>$data["title"] . "<br/> 任务活动优惠限购 " . $data["task_goods"]["total"] . $unit . "!",'data'=>[]];
                }
                if( $data["istime"] == 1 )
                {
                    if( time() < $data["timestart"] )
                    {
                        return ['status'=>AppError::$OrderCreateTimeNotStart, 'msg'=>$data["title"] . "<br/> 限购时间未到!",'data'=>[]];
                    }
                    if( $data["timeend"] < time() )
                    {
                        return ['status'=>AppError::$OrderCreateTimeEnd, 'msg'=>$data["title"] . "<br/> 限购时间已过!",'data'=>[]];
                    }
                }
                $levelid = intval($member["agentlevel"]);
                $groupid = intval($member["groupid"]);
                if( $data["buylevels"] != "" )
                {
                    $buylevels = explode(",", $data["buylevels"]);
                    if( !in_array($levelid, $buylevels) )
                    {
                        return ['status'=>AppError::$OrderCreateMemberLevelLimit, 'msg'=>"您的会员等级无法购买<br/>" . $data["title"] . "!",'data'=>[]];
                    }
                }
                if( $data["buygroups"] != "" )
                {
                    $buygroups = explode(",", $data["buygroups"]);
                    if( !in_array($groupid, $buygroups) )
                    {
                        return ['status'=>AppError::$OrderCreateMemberGroupLimit,'msg'=> "您所在会员组无法购买<br/>" . $data["title"] . "!",'data'=>[]];
                    }
                }
            }
            if( !empty($optionid) )
            {
                $option = pdo_fetch("select id,title,marketprice,presellprice,goodssn,productsn,stock,`virtual`,weight,cycelbuy_periodic from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array( ":uniacid" => $uniacid, ":goodsid" => $goodsid, ":id" => $optionid ));
                if( !empty($option) )
                {
                    if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
                    {
                    }
                    else
                    {
                        if( $option["stock"] != -1 && empty($option["stock"]) )
                        {
                            return ['status'=>AppError::$OrderCreateStockError, 'msg'=>$data["title"] . "<br/>" . $option["title"] . " 库存不足!",'data'=>[]];
                        }
                    }
                    $data["optionid"] = $optionid;
                    $data["optiontitle"] = $option["title"];
                    $data["marketprice"] = (0 < intval($data["ispresell"]) && (time() < $data["preselltimeend"] || $data["preselltimeend"] == 0) ? $option["presellprice"] : $option["marketprice"]);
                    $packageoption = array( );
                    if( $packageid )
                    {
                        $packageoption = pdo_fetch("select packageprice from " . tablename("ewei_shop_package_goods_option") . "\r\n                                where uniacid = " . $uniacid . " and goodsid = " . $goodsid . " and optionid = " . $optionid . " and pid = " . $packageid . " ");
                        $data["marketprice"] = $packageoption["packageprice"];
                        $packageprice += $packageoption["packageprice"];
                    }
                    $virtualid = $option["virtual"];
                    if( !empty($option["goodssn"]) )
                    {
                        $data["goodssn"] = $option["goodssn"];
                    }
                    if( !empty($option["productsn"]) )
                    {
                        $data["productsn"] = $option["productsn"];
                    }
                    if( !empty($option["weight"]) )
                    {
                        $data["weight"] = $option["weight"];
                    }
                    $cycelbuy_periodic = explode(",", $option["cycelbuy_periodic"]);
                    list($cycelbuy_day, $cycelbuy_unit, $cycelbuy_num) = $cycelbuy_periodic;
                }
            }
            else
            {
                if( $packageid )
                {
                    $pg = pdo_fetch("select packageprice from " . tablename("ewei_shop_package_goods") . "\r\n                                where uniacid = " . $uniacid . " and goodsid = " . $goodsid . " and pid = " . $packageid . " ");
                    $data["marketprice"] = $pg["packageprice"];
                    $packageprice += $pg["packageprice"];
                }
                if( $data["stock"] != -1 && empty($data["stock"]) )
                {
                    return ['status'=>AppError::$OrderCreateStockError, 'msg'=>$data["title"] . "<br/> 库存不足!",'data'=>[]];
                }
            }
            $data["diyformdataid"] = 0;
            $data["diyformdata"] = iserializer(array( ));
            $data["diyformfields"] = iserializer(array( ));
            if( intval($fromcart) == 1 )
            {
                if( $diyform_plugin )
                {
                    $cartdata = pdo_fetch("select id,diyformdataid,diyformfields,diyformdata from " . tablename("ewei_shop_member_cart") . " " . " where goodsid=:goodsid and optionid=:optionid and (openid=:openid or user_id = :user_id) and deleted=0 order by id desc limit 1", array( ":goodsid" => $data["goodsid"], ":optionid" => intval($data["optionid"]), ":openid" => $member['openid'] , ":user_id" => $member['id'] ));
                    if( !empty($cartdata) )
                    {
                        $data["diyformdataid"] = $cartdata["diyformdataid"];
                        $data["diyformdata"] = $cartdata["diyformdata"];
                        $data["diyformfields"] = $cartdata["diyformfields"];
                    }
                }
            }
            else
            {
                if( 0 < $fromquick )
                {
                    $cartdata = pdo_fetch("select id,diyformdataid,diyformfields,diyformdata from " . tablename("ewei_shop_quick_cart") . " " . " where goodsid=:goodsid and optionid=:optionid and (openid=:openid or user_id= :user_id) and deleted=0 order by id desc limit 1", array( ":goodsid" => $data["goodsid"], ":optionid" => intval($data["optionid"]), ":openid" => $member['openid'] , ":user_id" => $member['id'] ));
                    if( !empty($cartdata) )
                    {
                        $data["diyformdataid"] = $cartdata["diyformdataid"];
                        $data["diyformdata"] = $cartdata["diyformdata"];
                        $data["diyformfields"] = $cartdata["diyformfields"];
                    }
                }
                else
                {
                    if( !empty($data["diyformtype"]) && $diyform_plugin )
                    {
                        $temp_data = $diyform_plugin->getOneDiyformTemp($gdid, 0);
                        $data["diyformfields"] = $temp_data["diyformfields"];
                        $data["diyformdata"] = $temp_data["diyformdata"];
                        $data["diyformid"] = $data["diyformtype"] == 2 ? 0 : $data["diyformid"];
                    }
                }
            }
            if( $data["status"] == 2 )
            {
                $data["marketprice"] = 0;
            }
            if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
            {
                $data["ggprice"] = $gprice = $data["seckillinfo"]["price"] * $goodstotal;
                $seckill_payprice += $gprice;
                $seckill_price += $data["marketprice"] * $goodstotal - $gprice;
                $goodsprice += $data["marketprice"] * $goodstotal;
                $data["taskdiscountprice"] = 0;
                $data["lotterydiscountprice"] = 0;
                $data["discountprice"] = 0;
                $data["discountprice"] = 0;
                $data["discounttype"] = 0;
                $data["isdiscountunitprice"] = 0;
                $data["discountunitprice"] = 0;
                $data["price0"] = 0;
                $data["price1"] = 0;
                $data["price2"] = 0;
                $data["buyagainprice"] = 0;
            }
            else
            {
                $gprice = $data["marketprice"] * $goodstotal;
                $goodsprice += $gprice;
                $prices = m("order")->getGoodsDiscountPrice($data, $level);
                if( empty($packageid) )
                {
                    $data["ggprice"] = $prices["price"];
                }
                else
                {
                    $data["ggprice"] = $data["marketprice"];
                    $prices = array( );
                }
                $data["taskdiscountprice"] = $prices["taskdiscountprice"];
                $data["discountprice"] = $prices["discountprice"];
                $data["discountprice"] = $prices["discountprice"];
                $data["discounttype"] = $prices["discounttype"];
                $data["isdiscountunitprice"] = $prices["isdiscountunitprice"];
                $data["discountunitprice"] = $prices["discountunitprice"];
                $data["price0"] = $prices["price0"];
                $data["price1"] = $prices["price1"];
                $data["price2"] = $prices["price2"];
                $data["buyagainprice"] = $prices["buyagainprice"];
                $buyagainprice += $prices["buyagainprice"];
                $taskdiscountprice += $prices["taskdiscountprice"];
                if( $prices["discounttype"] == 1 )
                {
                    $isdiscountprice += $prices["isdiscountprice"];
                    $discountprice += $prices["discountprice"];
                    if( !empty($data["merchsale"]) )
                    {
                        $merchisdiscountprice += $prices["isdiscountprice"];
                        $discountprice_array[$merchid]["merchisdiscountprice"] += $prices["isdiscountprice"];
                    }
                    $discountprice_array[$merchid]["isdiscountprice"] += $prices["isdiscountprice"];
                }
                else
                {
                    if( $prices["discounttype"] == 2 )
                    {
                        $discountprice += $prices["discountprice"];
                        $discountprice_array[$merchid]["discountprice"] += $prices["discountprice"];
                    }
                }
                $discountprice_array[$merchid]["ggprice"] += $prices["ggprice"];
            }
            $merch_array[$merchid]["ggprice"] += $data["ggprice"];
            $totalprice += $data["ggprice"];
            if( $data["isverify"] == 2 )
            {
                $isverify = true;
                $verifytype = $data["verifytype"];
                $isendtime = $data["isendtime"];
                if( $isendtime == 0 )
                {
                    if( 0 < $data["usetime"] )
                    {
                        $endtime = time() + 3600 * 24 * intval($data["usetime"]);
                    }
                    else
                    {
                        $endtime = 0;
                    }
                }
                else
                {
                    $endtime = $data["endtime"];
                }
            }
            if( !empty($data["virtual"]) || $data["type"] == 2 )
            {
                $isvirtual = true;
                if( $data["virtualsend"] )
                {
                    $isvirtualsend = true;
                }
            }
            if( $data["seckillinfo"] && $data["seckillinfo"]["status"] == 0 )
            {
            }
            else
            {
                if( 0 < floatval($data["buyagain"]) && empty($data["buyagain_sale"]) && m("goods")->canBuyAgain($data) )
                {
                    $data["deduct"] = 0;
                    $saleset = false;
                }
                if( $data["manydeduct"] )
                {
                    //折扣卡
                    if ($data["deduct_type"]==1){
                        $deductprice += $data["deduct"] * $data["total"];
                    }else{
                        $discount+=$data["deduct"]*$data["total"];
                    }

                }
                else
                {
                    //折扣卡
                    if ($data["deduct_type"]==1){
                        $deductprice += $data["deduct"];
                    }else{
                        $discount+=$data["deduct"];
                    }

                }
                if( $data["deduct2"] == 0 )
                {
                    $deductprice2 += $data["ggprice"];
                }
                else
                {
                    if( 0 < $data["deduct2"] )
                    {
                        if( $data["ggprice"] < $data["deduct2"] )
                        {
                            $deductprice2 += $data["ggprice"];
                        }
                        else
                        {
                            $deductprice2 += $data["deduct2"];
                        }
                    }
                }
            }
            $virtualsales += $data["sales"];
            $allgoods[] = $data;
        }
        $grprice = $totalprice;
        if( 1 < count($goods) && !empty($tgoods) )
        {
            return ['status'=>AppError::$OrderCreateTaskGoodsCart, 'msg'=>$tgoods["title"] . "不能放入购物车下单,请单独购买!",'data'=>[]];
        }
        if( empty($allgoods) )
        {
            return ['status'=>AppError::$OrderCreateNoGoods,'msg'=>'','data'=>[]];
        }
        $addressid = intval($addressid);
        $address = false;
        if( !empty($addressid) && $dispatchtype == 0 && !$isonlyverifygoods )
        {
            $address = pdo_fetch("select * from " . tablename("ewei_shop_member_address") . " where id=:id and (openid=:openid or user_id = :user_id) and uniacid=:uniacid   limit 1", array( ":uniacid" => $uniacid, ":openid" => $member['openid'], ":user_id" => $member['id'], ":id" => $addressid ));
            if( empty($address) )
            {
                return ['status'=>AppError::$AddressNotFound,'msg'=>'','data'=>[]];
            }
        }
        if( !$isvirtual && !$isverify && $dispatchtype == 0 && !$isonlyverifygoods )
        {
            $dispatch_array = m("order")->getOrderDispatchPrice($allgoods, $member, $address, $saleset, $merch_array, 2);
            $dispatch_price = $dispatch_array["dispatch_price"] - $dispatch_array["seckill_dispatch_price"];
            $seckill_dispatchprice = $dispatch_array["seckill_dispatch_price"];
            $nodispatch_array = $dispatch_array["nodispatch_array"];
            $isdispatcharea = $dispatch_array['isdispatcharea'];
            if( !empty($nodispatch_array["isnodispatch"]) )
            {
                return ['status'=>AppError::$OrderCreateNoDispatch,'msg'=>$nodispatch_array["nodispatch"],'data'=>[]];
            }
        }
        //偏远地区是1   不支持发货是0
        if($isdispatcharea == 1 && $is_remote == 0){
            return ['status'=>AppError::$DispatchError,'msg'=>'','data'=>[]];
        }
        $cardid = intval($cardid);
        $plugin_membercard = p("membercard");
        $card_free_dispatch = false;
        $card_info = array( );
        $carddiscountprice = 0;
        $pure_totalprice = $totalprice;
        if( $plugin_membercard && $cardid )
        {
            $card_result = m('order')->caculatecard($cardid, $dispatch_price, $pure_totalprice, $discountprice, $isdiscountprice);
            if( $card_result )
            {
                $card_info["dispatch_price"] = $dispatch_price;
                $dispatch_price = $card_result["dispatch_price"];
                $carddiscountprice = $card_result["carddiscountprice"];
                $card_info["old_discountprice"] = $discountprice;
                $discountprice = $card_result["discountprice"];
                $card_info["old_isdiscountprice"] = $isdiscountprice;
                $isdiscountprice = $card_result["isdiscountprice"];
                $card_info["cardname"] = $card_result["cardname"];
                $card_info["carddiscount_rate"] = $card_result["carddiscount_rate"];
                $card_info["carddiscountprice"] = $carddiscountprice;
                $card_info["cardid"] = $cardid;
            }
        }
        if( 0 < $card_info["old_discountprice"] && $discountprice == 0 )
        {
            $totalprice += $card_info["old_discountprice"];
        }
        if( 0 < $card_info["old_isdiscountprice"] && $isdiscountprice == 0 )
        {
            $totalprice += $card_info["old_isdiscountprice"];
        }
        if( 0 < $card_info["dispatch_price"] && $dispatch_price == 0 )
        {
            $card_free_dispatch = true;
        }
        $totalprice -= $carddiscountprice;
        if( $is_openmerch == 1 )
        {
            foreach( $merch_array as $key => $value )
            {
                if( 0 < $key && !$packageid )
                {
                    $merch_array[$key]["set"] = $merch_plugin->getSet("sale", $key);
                    $merch_array[$key]["enoughs"] = $merch_plugin->getEnoughs($merch_array[$key]["set"]);
                }
            }
            if( $allow_sale )
            {
                $merch_enough = m("order")->getMerchEnough($merch_array);
                $merch_array = $merch_enough["merch_array"];
                $merch_enough_total = $merch_enough["merch_enough_total"];
                $merch_saleset = $merch_enough["merch_saleset"];
                if( 0 < $merch_enough_total )
                {
                    $totalprice -= $merch_enough_total;
                }
            }
        }
        $deductenough = 0;
        if( $saleset && $allow_sale )
        {
            foreach( $saleset["enoughs"] as $e )
            {
                if( floatval($e["enough"]) <= $totalprice - $seckill_payprice && 0 < floatval($e["money"]) )
                {
                    $deductenough = floatval($e["money"]);
                    if( $totalprice - $seckill_payprice < $deductenough )
                    {
                        $deductenough = $totalprice - $seckill_payprice;
                    }
                    break;
                }
            }
        }
        $totalprice -= $deductenough;
        //$couponid = intval($couponid);
        $goodsdata_coupon = array( );
        $goodsdata_coupon_temp = array( );
        foreach( $allgoods as $g )
        {
            if( $g["seckillinfo"] && $g["seckillinfo"]["status"] == 0 )
            {
                $goodsdata_coupon_temp[] = $g;
            }
            else
            {
                if( 0 < floatval($g["buyagain"]) )
                {
                    if( !m("goods")->canBuyAgain($g) || !empty($g["buyagain_sale"]) )
                    {
                        $goodsdata_coupon[] = $g;
                    }
                    else
                    {
                        $goodsdata_coupon_temp[] = $g;
                    }
                }
                else
                {
                    $goodsdata_coupon[] = $g;
                }
            }
        }
        $return_array = m('order')->caculatecoupon($couponid, $goodsdata_coupon, $totalprice, $discountprice, $isdiscountprice, 1, $discountprice_array, $merchisdiscountprice, $totalprice);
        $couponprice = 0;
        $coupongoodprice = 0;
        if( !empty($return_array) )
        {
            $isdiscountprice = $return_array["isdiscountprice"];
            $discountprice = $return_array["discountprice"];
            $couponprice = $return_array["deductprice"];
            $totalprice = $return_array["totalprice"];
            $discountprice_array = $return_array["discountprice_array"];
            $merchisdiscountprice = $return_array["merchisdiscountprice"];
            $coupongoodprice = $return_array["coupongoodprice"];
            $couponmerchid = $return_array["couponmerchid"];
            $allgoods = $return_array["goodsarr"];
            $allgoods = array_merge($allgoods, $goodsdata_coupon_temp);
        }
        if( $isonlyverifygoods )
        {
            $addressid = 0;
        }
        if( $iscycelbuy )
        {
            $totalprice += $dispatch_price * $cycelbuy_num;
        }
        else
        {
            $totalprice += $dispatch_price + $seckill_dispatchprice;
        }
        if( $saleset && empty($saleset["dispatchnodeduct"]) )
        {
            $deductprice2 += $dispatch_price;
        }
        if( empty($goods[0]["bargain_id"]) || !p("bargain") )
        {
            $deductcredit = 0;
            $deductmoney = 0;
            $deductcredit2 = 0;
            if( $sale_plugin )
            {
                //判断卡路里
                if( !empty($deduct1) )
                {
                    $credit = $member["credit1"];
                    if( !empty($saleset["creditdeduct"]) )
                    {
                        $pcredit = intval($saleset["credit"]);
                        $pmoney = round(floatval($saleset["money"]), 2);
                        if( 0 < $pcredit && 0 < $pmoney )
                        {
                            if( $credit % $pcredit == 0 )
                            {
                                $deductmoney = round(intval($credit / $pcredit) * $pmoney, 2);
                            }
                            else
                            {
                                $deductmoney = round((intval($credit / $pcredit) + 1) * $pmoney, 2);
                            }
                        }
                        if( $deductprice < $deductmoney )
                        {
                            $deductmoney = $deductprice;
                        }
                        if( $totalprice - $seckill_payprice < $deductmoney )
                        {
                            $deductmoney = $totalprice - $seckill_payprice;
                        }
                        $deductcredit = round($deductmoney / $pmoney * $pcredit, 2);
                    }
                }

                $totalprice -= $deductmoney;

            }
            if( !empty($saleset["moneydeduct"]) )
            {
                if( !empty($deduct2) && $deduct2 != "false" )
                {
                    $deductcredit2 = $member["credit2"];
                    if( $totalprice - $seckill_payprice < $deductcredit2 )
                    {
                        $deductcredit2 = $totalprice - $seckill_payprice;
                    }
                    if( $deductprice2 < $deductcredit2 )
                    {
                        $deductcredit2 = $deductprice2;
                    }
                }
                $totalprice -= $deductcredit2;
            }
        }
        $verifyinfo = array( );
        $verifycode = "";
        $verifycodes = array( );
        if( ($isverify || $dispatchtype) && !$isonlyverifygoods )
        {
            if( $isverify )
            {
                if( $verifytype == 0 || $verifytype == 1 )
                {
                    $verifycode = random(8, true);
                    while( 1 )
                    {
                        $count = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where verifycode=:verifycode and uniacid=:uniacid limit 1", array( ":verifycode" => $verifycode, ":uniacid" => $_W["uniacid"] ));
                        if( $count <= 0 )
                        {
                            break;
                        }
                        $verifycode = random(8, true);
                    }
                }
                else
                {
                    if( $verifytype == 2 )
                    {
                        $totaltimes = intval($allgoods[0]["total"]);
                        if( $totaltimes <= 0 )
                        {
                            $totaltimes = 1;
                        }
                        for( $i = 1; $i <= $totaltimes; $i++ )
                        {
                            $verifycode = random(8, true);
                            while( 1 )
                            {
                                $count = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where concat(verifycodes,'|' + verifycode +'|' ) like :verifycodes and uniacid=:uniacid limit 1", array( ":verifycodes" => "%" . $verifycode . "%", ":uniacid" => $_W["uniacid"] ));
                                if( $count <= 0 )
                                {
                                    break;
                                }
                                $verifycode = random(8, true);
                            }
                            $verifycodes[] = "|" . $verifycode . "|";
                            $verifyinfo[] = array( "verifycode" => $verifycode, "verifyopenid" => "", "verifytime" => 0, "verifystoreid" => 0 );
                        }
                    }
                }
            }
            else
            {
                if( $dispatchtype )
                {
                    $verifycode = random(8, true);
                    while( 1 )
                    {
                        $count = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_order") . " where verifycode=:verifycode and uniacid=:uniacid limit 1", array( ":verifycode" => $verifycode, ":uniacid" => $_W["uniacid"] ));
                        if( $count <= 0 )
                        {
                            break;
                        }
                        $verifycode = random(8, true);
                    }
                }
            }
        }
        if( is_string($carrier) )
        {
            $carrierstring = htmlspecialchars_decode(str_replace("\\", "", $carrier));
            $carrier = @json_decode($carrierstring, true);
        }
        $carriers = (is_array($carrier) ? iserializer($carrier) : iserializer(array( )));
        if( $totalprice <= 0 )
        {
            $totalprice = 0;
        }
        if( $ismerch == 0 || $ismerch == 1 && count($merch_array) == 1 )
        {
            $multiple_order = 0;
        }
        else
        {
            $multiple_order = 1;
        }
        if( 0 < $ismerch )
        {
            $ordersn = m("common")->createNO("order", "ordersn", "ME");
        }
        else
        {
            $ordersn = m("common")->createNO("order", "ordersn", "SH");
        }
        if( !empty($goods[0]["bargain_id"]) && p("bargain") )
        {
            $bargain_act = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_bargain_actor") . " WHERE id = :id AND (openid = :openid or user_id = :user_id) ", array( ":id" => $goods[0]["bargain_id"], ":openid" => $member["openid"] , ":user_id" => $member["id"] ));
            if( empty($bargain_act) )
            {
                return ['status'=>AppError::$OrderCreateNoGoods,'msg'=>'','data'=>[]];
            }
            $totalprice = $bargain_act["now_price"] + $dispatch_price;
            if( !pdo_update("ewei_shop_bargain_actor", array( "status" => 1 ), array( "id" => $bargain_act['id'] )) )
            {
                return ['status'=>AppError::$OrderCreateFalse,'msg'=>'','data'=>[]];
            }
            $ordersn = substr_replace($ordersn, "KJ", 0, 2);
        }
        $is_package = 0;
        if( !empty($packageid) )
        {
            $goodsprice = price_format($packageprice);
            if( $package["dispatchtype"] == 1 )
            {
                $dispatch_array = m("order")->getOrderDispatchPrice($allgoods, $member, $address, false, $merch_array, 0);
                $dispatch_price = $dispatch_array["dispatch_price"] - $dispatch_array["seckill_dispatch_price"];
            }
            else
            {
                $dispatch_price = $package["freight"];
            }
            $totalprice = $packageprice + $dispatch_price;
            $is_package = 1;
            $discountprice = 0;
        }
        $order = array( );

        $order["share_id"] = $mid ? $mid : $member['id'];

        //判断折扣宝
        $credit3=$member["credit3"];
        if (!empty($discount1)){
            if ($credit3<$discount1){
                $discount1=$credit3;
            }
            $totalprice-=$discount1;
        }

        $order["ismerch"] = $ismerch;
        $order["parentid"] = 0;
        $order["uniacid"] = $uniacid;
        $order["openid"] = $member['openid'];
        $order["ordersn"] = $ordersn;
        //如果是礼包活动  就。。。。
        $order["price"] = $flag == true ? $dispatch_price : $totalprice;
        $order["oldprice"] = $totalprice;
        $order["grprice"] = $grprice;
        $order["taskdiscountprice"] = $taskdiscountprice;
        $order["discountprice"] = $discountprice;
        if( !empty($goods[0]["bargain_id"]) && p("bargain") )
        {
            $order["discountprice"] = 0;
        }
        $order["isdiscountprice"] = $isdiscountprice;
        $order["merchisdiscountprice"] = $merchisdiscountprice;
        $order["cash"] = $cash;
        $order["status"] = 0;
        $order["iswxappcreate"] = 1;
        $order["remark"] = $flag == true ? "免费领10人礼包____".trim($remark) : trim($remark);
        $order["addressid"] = (empty($dispatchtype) ? $addressid : 0);
        $order["goodsprice"] = $goodsprice;
        $order["dispatchtype"] = $dispatchtype;
        $order["dispatchid"] = $dispatchid;
        $order["storeid"] = $carrierid;
        $order["carrier"] = $carriers;
        $order["createtime"] = time();
        $order["olddispatchprice"] = $dispatch_price + $seckill_dispatchprice;
        $order["couponid"] = $couponid;
        $order["couponmerchid"] = $couponmerchid;
        $order["paytype"] = 0;
        $order["deductprice"] = $deductmoney;
        //抵扣宝
        if (!empty($is_discount)){
            $order["discount_price"]=$discount1;
        }
        $order["deductcredit"] = $deductcredit;
        $order["deductcredit2"] = $deductcredit2;
        $order["deductenough"] = $deductenough;
        $order["merchdeductenough"] = $merch_enough_total;
        $order["couponprice"] = $couponprice;
        $order["merchshow"] = 0;
        $order["buyagainprice"] = $buyagainprice;
        $order["ispackage"] = $is_package;
        $order["packageid"] = $packageid;
        $order["seckilldiscountprice"] = $seckill_price;
        $order["quickid"] = intval($fromquick);
        $order["coupongoodprice"] = $coupongoodprice;
        if( $iscycelbuy )
        {
            $order["iscycelbuy"] = 1;
            $order["cycelbuy_periodic"] = implode(",", $cycelbuy_periodic);
            $order["dispatchprice"] = $dispatch_price * $cycelbuy_num;
            $order["cycelbuy_predict_time"] = $receipttime;
        }
        else
        {
            $order["dispatchprice"] = $dispatch_price + $seckill_dispatchprice;
        }
        $author = p("author");
        if( $author )
        {
            $author_set = $author->getSet();
            if( !empty($member["agentid"]) && !empty($member["authorid"]) )
            {
                $order["authorid"] = $member["authorid"];
            }
            if( !empty($author_set["selfbuy"]) && !empty($member["isauthor"]) && !empty($member["authorstatus"]) )
            {
                $order["authorid"] = $member["id"];
            }
        }
        if( $multiple_order == 0 )
        {
            $order_merchid = current(array_keys($merch_array));
            $order["merchid"] = intval($order_merchid);
            $order["isparent"] = 0;
            $order["transid"] = "";
            $order["isverify"] = ($isverify ? 1 : 0);
            $order["verifytype"] = $verifytype;
            $order["verifyendtime"] = $endtime;
            $order["verifycode"] = $verifycode;
            $order["verifycodes"] = implode("", $verifycodes);
            $order["verifyinfo"] = iserializer($verifyinfo);
            $order["virtual"] = $virtualid;
            $order["isvirtual"] = ($isvirtual ? 1 : 0);
            $order["isvirtualsend"] = ($isvirtualsend ? 1 : 0);
            $order["invoicename"] = trim($invoicename);
            $order["city_express_state"] = (empty($dispatch_array["city_express_state"]) == true ? 0 : $dispatch_array["city_express_state"]);
        }
        else
        {
            $order["isparent"] = 1;
            $order["merchid"] = 0;
        }
        if( $diyform_plugin )
        {
            if( is_string($diydata) )
            {
                $diyformdatastring = htmlspecialchars_decode(str_replace("\\", "", $diydata));
                $diydata = @json_decode($diyformdatastring, true);
            }
            if( is_array($diydata) && !empty($order_formInfo) )
            {
                $diyform_data = $diyform_plugin->getInsertData($fields, $diydata, true);
                $idata = $diyform_data["data"];
                $order["diyformfields"] = $diyform_plugin->getInsertFields($fields);
                $order["diyformdata"] = $idata;
                $order["diyformid"] = $order_formInfo["id"];
            }
        }
        if( !empty($address) )
        {
            $order["address"] = iserializer($address);
        }
        //lihanwen
        $couponid_id = 0;
        if($couponid>0){
            $coupon_info = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_coupon_data") . " WHERE `id`=:id  limit 1", array(":id" => $couponid));
            if($coupon_info['couponid']==2){//店主会员免费商品
                $order["price"] = 0.00;
            }
            $couponid_id = $coupon_info['couponid'];
        }
        pdo_insert("ewei_shop_order", $order);
        $orderid = pdo_insertid();
        //如果符合领取礼包 就给他加日志
        if($flag){
            m('game')->add_log($member['openid'],$goodsid,$order["ordersn"]);
        }
        if( !empty($goods[0]["bargain_id"]) && p("bargain") )
        {
            pdo_update("ewei_shop_bargain_actor", array( "order" => $orderid ), array( "id" => $goods[0]["bargain_id"], "openid" => $member["openid"] ));
        }
        if( !empty($card_info) && $orderid )
        {
            $plugin_membercard->member_card_use_record($orderid, $cardid, $carddiscountprice, $member['openid'], $card_free_dispatch);
        }
        if( $multiple_order == 0 )
        {
            foreach( $allgoods as $goods )
            {
                $order_goods = array( );
                if( !empty($bargain_act) && p("bargain") )
                {
                    $goods["total"] = 1;
                    $goods["ggprice"] = $bargain_act["now_price"];
                    pdo_query("UPDATE " . tablename("ewei_shop_goods") . " SET sales = sales + 1 WHERE id = :id AND uniacid = :uniacid", array( ":id" => $goods["goodsid"], ":uniacid" => $uniacid ));
                }
                $order_goods["merchid"] = $goods["merchid"];
                $order_goods["merchsale"] = $goods["merchsale"];
                $order_goods["uniacid"] = $uniacid;
                $order_goods["orderid"] = $orderid;
                $order_goods["goodsid"] = $goods["goodsid"];
                $order_goods["price"] = $goods["marketprice"] * $goods["total"];
                $order_goods["total"] = $goods["total"];
                $order_goods["optionid"] = $goods["optionid"];
                $order_goods["createtime"] = time();
                $order_goods["optionname"] = $goods["optiontitle"];
                $order_goods["goodssn"] = $goods["goodssn"];
                $order_goods["productsn"] = $goods["productsn"];
                $order_goods["realprice"] = $goods["ggprice"];
                $order_goods["oldprice"] = $goods["ggprice"];
                if( $goods["discounttype"] == 1 )
                {
                    $order_goods["isdiscountprice"] = $goods["isdiscountprice"];
                }
                else
                {
                    $order_goods["isdiscountprice"] = 0;
                }
                $order_goods["openid"] = $member['openid'];
                if( $diyform_plugin )
                {
                    if( $goods["diyformtype"] == 2 )
                    {
                        $order_goods["diyformid"] = 0;
                    }
                    else
                    {
                        $order_goods["diyformid"] = $goods["diyformid"];
                    }
                    $order_goods["diyformdata"] = $goods["diyformdata"];
                    $order_goods["diyformfields"] = $goods["diyformfields"];
                }
                if( 0 < floatval($goods["buyagain"]) && !m("goods")->canBuyAgain($goods) )
                {
                    $order_goods["canbuyagain"] = 1;
                }
                if( $goods["seckillinfo"] && $goods["seckillinfo"]["status"] == 0 )
                {
                    $order_goods["seckill"] = 1;
                    $order_goods["seckill_taskid"] = $goods["seckillinfo"]["taskid"];
                    $order_goods["seckill_roomid"] = $goods["seckillinfo"]["roomid"];
                    $order_goods["seckill_timeid"] = $goods["seckillinfo"]["timeid"];
                }
                pdo_insert("ewei_shop_order_goods", $order_goods);
                if( $goods["seckillinfo"] && $goods["seckillinfo"]["status"] == 0 )
                {
                    plugin_run("seckill::setSeckill", $goods["seckillinfo"], $goods, $member["openid"], $orderid, 0, $order["createtime"]);
                }
            }
        }
        else
        {
            $og_array = array( );
            $ch_order_data = m("order")->getChildOrderPrice($order, $allgoods, $dispatch_array, $merch_array, $sale_plugin, $discountprice_array);
            foreach( $merch_array as $key => $value )
            {
                $order["ordersn"] = m("common")->createNO("order", "ordersn", "ME");
                $merchid = $key;
                $order["merchid"] = $merchid;
                $order["parentid"] = $orderid;
                $order["isparent"] = 0;
                $order["merchshow"] = 1;
                $order["dispatchprice"] = $dispatch_array["dispatch_merch"][$merchid];
                $order["olddispatchprice"] = $dispatch_array["dispatch_merch"][$merchid];
                if( empty($packageid) )
                {
                    $order["merchisdiscountprice"] = $discountprice_array[$merchid]["merchisdiscountprice"];
                    $order["isdiscountprice"] = $discountprice_array[$merchid]["isdiscountprice"];
                    $order["discountprice"] = $discountprice_array[$merchid]["discountprice"];
                }
                $order["price"] = $ch_order_data[$merchid]["price"];
                $order["grprice"] = $ch_order_data[$merchid]["grprice"];
                $order["goodsprice"] = $ch_order_data[$merchid]["goodsprice"];
                $order["deductprice"] = $ch_order_data[$merchid]["deductprice"];
                $order["deductcredit"] = $ch_order_data[$merchid]["deductcredit"];
                $order["deductcredit2"] = $ch_order_data[$merchid]["deductcredit2"];
                $order["merchdeductenough"] = $ch_order_data[$merchid]["merchdeductenough"];
                $order["deductenough"] = $ch_order_data[$merchid]["deductenough"];
                $order["coupongoodprice"] = $discountprice_array[$merchid]["coupongoodprice"];
                $order["couponprice"] = $discountprice_array[$merchid]["deduct"];
                if( empty($order["couponprice"]) )
                {
                    $order["couponid"] = 0;
                    $order["couponmerchid"] = 0;
                }
                else
                {
                    if( 0 < $couponmerchid )
                    {
                        if( $merchid == $couponmerchid )
                        {
                            $order["couponid"] = $couponid;
                            $order["couponmerchid"] = $couponmerchid;
                        }
                        else
                        {
                            $order["couponid"] = 0;
                            $order["couponmerchid"] = 0;
                        }
                    }
                }
                pdo_insert("ewei_shop_order", $order);
                $ch_orderid = pdo_insertid();
                //如果符合领取礼包 就给他加日志
                if($flag){
                    m('game')->add_log($member['openid'],$goodsid,$order["ordersn"]);
                }
                $merch_array[$merchid]["orderid"] = $ch_orderid;
                if( 0 < $couponmerchid && $merchid == $couponmerchid )
                {
                    $couponorderid = $ch_orderid;
                }
                foreach( $value["goods"] as $k => $v )
                {
                    $og_array[$v] = $ch_orderid;
                }
            }
            foreach( $allgoods as $goods )
            {
                $goodsid = $goods["goodsid"];
                $order_goods = array( );
                $order_goods["parentorderid"] = $orderid;
                $order_goods["merchid"] = $goods["merchid"];
                $order_goods["merchsale"] = $goods["merchsale"];
                $order_goods["orderid"] = $og_array[$goodsid];
                $order_goods["uniacid"] = $uniacid;
                $order_goods["goodsid"] = $goodsid;
                $order_goods["price"] = $goods["marketprice"] * $goods["total"];
                $order_goods["total"] = $goods["total"];
                $order_goods["optionid"] = $goods["optionid"];
                $order_goods["createtime"] = time();
                $order_goods["optionname"] = $goods["optiontitle"];
                $order_goods["goodssn"] = $goods["goodssn"];
                $order_goods["productsn"] = $goods["productsn"];
                $order_goods["realprice"] = $goods["ggprice"];
                $order_goods["oldprice"] = $goods["ggprice"];
                $order_goods["isdiscountprice"] = $goods["isdiscountprice"];
                $order_goods["openid"] = $member['openid'];
                if( $diyform_plugin )
                {
                    if( $goods["diyformtype"] == 2 )
                    {
                        $order_goods["diyformid"] = 0;
                    }
                    else
                    {
                        $order_goods["diyformid"] = $goods["diyformid"];
                    }
                    $order_goods["diyformdata"] = $goods["diyformdata"];
                    $order_goods["diyformfields"] = $goods["diyformfields"];
                }
                if( 0 < floatval($goods["buyagain"]) && !m("goods")->canBuyAgain($goods) )
                {
                    $order_goods["canbuyagain"] = 1;
                }
                pdo_insert("ewei_shop_order_goods", $order_goods);
            }
        }
        if( $data["type"] == 3 )
        {
            $order_v = pdo_fetch("select id,ordersn, price,openid,dispatchtype,addressid,carrier,status,isverify,deductcredit2,`virtual`,isvirtual,couponid,isvirtualsend,isparent,paytype,merchid,agentid,createtime,buyagainprice,istrade,tradestatus from " . tablename("ewei_shop_order") . " where uniacid=:uniacid and id = :id limit 1", array( ":uniacid" => $_W["uniacid"], ":id" => $orderid ));
            com("virtual")->pay_befo($order_v);
        }
        if( com("coupon") && !empty($orderid) )
        {
            com("coupon")->addtaskdata($orderid);
        }
        if( is_array($carrier) )
        {
            $up = array( "realname" => $carrier["carrier_realname"], "carrier_mobile" => $carrier["carrier_mobile"] );
            pdo_update("ewei_shop_member", $up, array( "id" => $member["id"], "uniacid" => $_W["uniacid"] ));
            if( !empty($member["uid"]) )
            {
                load()->model("mc");
                mc_update($member["uid"], $up);
            }
        }
        if( $fromcart == 1 )
        {
            pdo_query("update " . tablename("ewei_shop_member_cart") . " set deleted=1 where (openid=:openid or user_id :user_id) and uniacid=:uniacid and selected=1 ", array( ":uniacid" => $uniacid, ":openid" => $member['openid'],':user_id'=>$member['id'] ));
        }
        if( p("quick") && !empty($fromquick) )
        {
            pdo_update("ewei_shop_quick_cart", array( "deleted" => 1 ), array( "quickid" => intval($fromquick), "uniacid" => $_W["uniacid"], "openid" => $member["openid"] ));
        }
        if( 0 < $deductcredit )
        {
            m("member")->setCredit($member["openid"], "credit1", 0 - $deductcredit, array( "0", $_W["shopset"]["shop"]["name"] . "购物卡路里抵扣 消费卡路里: " . $deductcredit . " 抵扣金额: " . $deductmoney . " 订单号: " . $ordersn ));
        }
        //添加折扣宝记录
        if( 0 < $discount1&&$discount1)
        {
            m("member")->setCredit($member['openid'], "credit3", 0 - $discount1, array( "0", $_W["shopset"]["shop"]["name"] . "购物折扣宝抵扣 消费折扣宝: " . $discount1 . " 抵扣金额: " . $discount1 . " 订单号: " . $ordersn ));
        }
        if( 0 < $buyagainprice )
        {
            m("goods")->useBuyAgain($orderid);
        }
        if( 0 < $deductcredit2 )
        {
            m("member")->setCredit($member['openid'], "credit2", 0 - $deductcredit2, array( "0", $_W["shopset"]["shop"]["name"] . "购物余额抵扣: " . $deductcredit2 . " 订单号: " . $ordersn ));
        }
        if( empty($virtualid) )
        {
            m("order")->setStocksAndCredits($orderid, 0);
        }
        else
        {
            if( isset($allgoods[0]) )
            {
                $vgoods = $allgoods[0];
                pdo_update("ewei_shop_goods", array( "sales" => $vgoods["sales"] + $vgoods["total"] ), array( "id" => $vgoods["goodsid"] ));
            }
        }
        $plugincoupon = com("coupon");
        if( $plugincoupon )
        {
            if( 0 < $couponmerchid && $multiple_order == 1 )
            {
                $oid = $couponorderid;
            }
            else
            {
                $oid = $orderid;
            }
            $plugincoupon->useConsumeCoupon($oid);
        }
        if( !empty($tgoods) )
        {
            $rank = intval($_SESSION[$tgoods["goodsid"] . "_rank"]);
            $join_id = intval($_SESSION[$tgoods["goodsid"] . "_join_id"]);
            m("goods")->getTaskGoods($tgoods["openid"], $tgoods["goodsid"], $rank, $join_id, $tgoods["optionid"], $tgoods["total"]);
            $_SESSION[$tgoods["goodsid"] . "_rank"] = 0;
            $_SESSION[$tgoods["goodsid"] . "_join_id"] = 0;
        }
        m("notice")->sendOrderMessage($orderid);
        com_run("printer::sendOrderMessage", $orderid);
        $pluginc = p("commission");
        if( $pluginc )
        {
            if( $multiple_order == 0 )
            {
                $pluginc->checkOrderConfirm($orderid);
            }
            else
            {
                if( !empty($merch_array) )
                {
                    foreach( $merch_array as $key => $value )
                    {
                        $pluginc->checkOrderConfirm($value["orderid"]);
                    }
                }
            }
        }
        $dividend = p("dividend");
        if( $dividend )
        {
            if( $multiple_order == 0 )
            {
                $a = $dividend->checkOrderConfirm($orderid);
            }
            else
            {
                if( !empty($merch_array) )
                {
                    foreach( $merch_array as $key => $value )
                    {
                        $dividend->checkOrderConfirm($value["orderid"]);
                    }
                }
            }
        }
        unset($_SESSION[$member['openid'] . "_order_create"]);
        return ["orderid" => $orderid,'couponid_id'=>$couponid_id,'couponid' =>$couponid];
    }

    /**
     * @param $user_id
     * @param $orderid
     * @return array
     */
    public function order_pay($user_id,$orderid)
    {
        global $_W;
		$uniacid = $_W["uniacid"];
		$member = m("member")->getMember($user_id, true);
		if( empty($orderid) )
		{
			return ['status'=>AppError::$ParamsError,'msg'=>'','data'=>[]];
		}
		$order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and (openid=:openid or user_id = :user_id) limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $member['openid'] , ':user_id'=>$member['id'] ));
		if( empty($order) )
		{
			return ['status'=>AppError::$OrderNotFound,'msg'=>'','data'=>[]];
		}
		if( $order["status"] == -1 )
		{
			return ['status'=>AppError::$OrderCannotPay,'msg'=>'','data'=>[]];
		}
		else
		{
			if( 1 <= $order["status"] )
			{
				return ['status'=>AppError::$OrderAlreadyPay,'msg'=>'','data'=>[]];
			}
		}
		$log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array( ":uniacid" => $uniacid, ":module" => "ewei_shopv2", ":tid" => $order["ordersn"] ));
		if( !empty($log) && $log["status"] != "0" )
		{
			return ['status'=>AppError::$OrderAlreadyPay,'msg'=>'','data'=>[]];
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
			$log = array( "uniacid" => $uniacid, "openid" => $member["openid"], "module" => "ewei_shopv2", "tid" => $order["ordersn"], "fee" => $order["price"], "status" => 0 );
			pdo_insert("core_paylog", $log);
			$plid = pdo_insertid();
		}
		$set = m("common")->getSysset(array( "shop", "pay" ));
		$credit = array( "success" => false );
		if( isset($set["pay"]) && $set["pay"]["credit"] == 1 )
		{
			$credit = array( "success" => true, "current" => $member["credit2"] );
		}
		//RVC支付
		$RVC = array( "success" => true, "current" => $member["RVC"] );

		//微信支付
        $wechat = "";
		if( !empty($order["addressid"]) )
		{
			$cash = array( "success" => $order["cash"] == 1 && isset($set["pay"]) && $set["pay"]["cash"] == 1 && $order["isverify"] == 0 && $order["isvirtual"] == 0 );
		}
		//支付宝支付
        $alipay = "";
		return ["order" => ["id" => $order["id"], "ordersn" => $order["ordersn"], "price" => $order["price"], "title" => $set["shop"]["name"] . "订单"], "credit" => $credit,"RVC" => $RVC, "wechat" => $wechat, "alipay" => $alipay, "cash" => $cash ];
    }

    /**
     * @param $user_id
     * @param $orderid
     * @param $type
     * @return array
     */
    public function order_complete($user_id,$orderid,$type)
    {
        global $_W;
        $uniacid = $_W["uniacid"];
        $member = m("member")->getMember($user_id, true);
        if( empty($orderid) )
        {
            return ['status'=>AppError::$ParamsError,'msg'=>'','data'=>[]];
        }
        if( !in_array($type, array( "wechat", "alipay", "credit", "cash", "RVC" )) )
        {
            return ['status'=>AppError::$OrderPayNoPayType,'msg'=>'','data'=>[]];
        }
        if( $type == "alipay" && empty($_GPC["alidata"]) )
        {
            return ['status'=>AppError::$ParamsError, "支付宝返回数据错误",'msg'=>'','data'=>[]];
        }
        $set = m("common")->getSysset(array( "shop", "pay" ));
        $set["pay"]["weixin"] = (!empty($set["pay"]["weixin_sub"]) ? 1 : $set["pay"]["weixin"]);
        $set["pay"]["weixin_jie"] = (!empty($set["pay"]["weixin_jie_sub"]) ? 1 : $set["pay"]["weixin_jie"]);
        $order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and (openid=:openid or user_id = :user_id) limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $member['openid'],':user_id'=>$member['id'] ));
        if( empty($order) )
        {
            return ['status'=>AppError::$OrderNotFound,'msg'=>'','data'=>[]];
        }
        if( 1 <= $order["status"] )
        {
            $this->success($orderid);
        }
        $log = pdo_fetch("SELECT * FROM " . tablename("core_paylog") . " WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1", array( ":uniacid" => $uniacid, ":module" => "ewei_shopv2", ":tid" => $order["ordersn"] ));
        if( empty($log) )
        {
            return ['status'=>AppError::$OrderPayFail,'msg'=>'','data'=>[]];
        }
        $order_goods = pdo_fetchall("select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf from  " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on og.goodsid = g.id " . " where og.orderid=:orderid and og.uniacid=:uniacid ", array( ":uniacid" => $_W["uniacid"], ":orderid" => $orderid ));
        foreach( $order_goods as $data )
        {
            if( empty($data["status"]) || !empty($data["deleted"]) )
            {
                return ['status'=>AppError::$OrderPayFail, 'msg'=>$data["title"]."<br/> 已下架!",'data'=>[]];
            }
            $unit = (empty($data["unit"]) ? "件" : $data["unit"]);
            if( 0 < $data["minbuy"] && $data["buycount"] < $data["minbuy"] )
            {
                return ['status'=>AppError::$OrderCreateMinBuyLimit, 'msg'=>$data["title"] . "<br/> " . $data["min"] . $unit . "起售!",'data'=>[]];
            }
            if( 0 < $data["maxbuy"] && $data["maxbuy"] < $data["buycount"] )
            {
                return ['status'=>AppError::$OrderCreateOneBuyLimit, 'msg'=>$data["title"] . "<br/> 一次限购 " . $data["maxbuy"] . $unit . "!",'data'=>[]];
            }
            if( 0 < $data["usermaxbuy"] )
            {
                $order_goodscount = pdo_fetchcolumn("select ifnull(sum(og.total),0)  from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_order") . " o on og.orderid=o.id " . " where og.goodsid=:goodsid and  o.status>=1 and (o.openid=:openid or o.user_id = :user_id)  and og.uniacid=:uniacid ", array( ":goodsid" => $data["goodsid"], ":uniacid" => $uniacid, ":openid" => $member['openid'] ,':user_id'=>$member['id'] ));
                if( $data["usermaxbuy"] <= $order_goodscount )
                {
                    return ['status'=>AppError::$OrderCreateMaxBuyLimit, 'msg'=>$data["title"] . "<br/> 最多限购 " . $data["usermaxbuy"] . $unit,'data'=>[]];
                }
            }
            if( $data["istime"] == 1 )
            {
                if( time() < $data["timestart"] )
                {
                    return ['status'=>AppError::$OrderCreateTimeNotStart, 'msg'=>$data["title"] . "<br/> 限购时间未到!",'data'=>[]];
                }
                if( $data["timeend"] < time() )
                {
                    return ['status'=>AppError::$OrderCreateTimeEnd, 'msg'=>$data["title"] . "<br/> 限购时间已过!",'data'=>[]];
                }
            }
            if( $data["buylevels"] != "" )
            {
                $buylevels = explode(",", $data["buylevels"]);
                if( !in_array($member["agentlevel"], $buylevels) )
                {
                    return ['status'=>AppError::$OrderCreateMemberLevelLimit, 'msg'=>"您的会员等级无法购买<br/>" . $data["title"] . "!",'data'=>[]];
                }
            }
            if( $data["buygroups"] != "" )
            {
                $buygroups = explode(",", $data["buygroups"]);
                if( !in_array($member["groupid"], $buygroups) )
                {
                    return ['status'=>AppError::$OrderCreateMemberGroupLimit, 'msg'=>"您所在会员组无法购买<br/>" . $data["title"] . "!", 'data'=>[]];
                }
            }
            if( $data["totalcnf"] == 1 )
            {
                if( !empty($data["optionid"]) )
                {
                    $option = pdo_fetch("select id,title,marketprice,goodssn,productsn,stock,`virtual` from " . tablename("ewei_shop_goods_option") . " where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1", array( ":uniacid" => $uniacid, ":goodsid" => $data["goodsid"], ":id" => $data["optionid"] ));
                    if( !empty($option) && $option["stock"] != -1 && empty($option["stock"]) )
                    {
                        return ['status'=>AppError::$OrderCreateStockError, 'msg'=>$data["title"] . "<br/>" . $option["title"] . " 库存不足!", 'data'=>[]];
                    }
                }
                else
                {
                    if( $data["stock"] != -1 && empty($data["stock"]) )
                    {
                        return ['status'=>AppError::$OrderCreateStockError, 'msg'=>$data["title"] . "<br/>" . " 库存不足!", 'data'=>[]];
                    }
                }
            }
        }
        if( $type == "cash" )
        {
            if( empty($set["pay"]["cash"]) )
            {
                return ['status'=>AppError::$OrderPayFail, 'msg'=>"未开启货到付款",'data'=>[]];
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
                return ['status'=>AppError::$OrderPayFail, 'msg'=>"未开启余额支付", 'data'=>[]];
            }
            if( $ps["fee"] < 0 )
            {
                return ['status'=>AppError::$OrderPayFail, 'msg'=>"金额错误", 'data'=>[]];
            }
            $credits = $member["credit2"];
            if( $credits < $ps["fee"] )
            {
                return [''=>AppError::$OrderPayFail, 'msg'=>"余额不足,请充值",'data'=>[]];
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
            $result = m("member")->setCredit($member['openid'], "credit2", 0 - $fee, array( $_W["member"]["uid"], $shopset["name"] . "APP 消费" . $fee ));
            m('pay')->creditpay_log($openid, $fee, $orderid,'credit');
            if( is_error($result) )
            {
                return ['status'=>AppError::$OrderPayFail, 'msg'=>$result["message"] ,'data'=>[]];
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
        else {
            if ($type == "RVC") {
                if ($ps["fee"] < 0) {
                    return ['status'=>AppError::$OrderPayFail, 'msg'=>"金额错误" ,'data'=>[]];
                }
                $credits = $member["RVC"];
                if ($credits < $ps["fee"]) {
                    return ['status'=>AppError::$OrderPayFail, 'msg'=>"RVC不足,请充值",'data'=>[]];
                }
                //礼包商品的支付
                if (pdo_exists('ewei_shop_gift_log', ['order_sn' => $order['ordersn']])) {
                    $gift = pdo_get('ewei_shop_gift_log', ['order_sn' => $order['ordersn']]);
                    //查看这个礼包记录的周始末
                    $week = m('util')->week($gift['createtime']);
                    //如果当前时间在周始末  则可以领取  并更新状态为2 领取成功 如果不在也更新状态  为0 取消
                    if (time() >= $week['start'] && $week['end'] >= time()) {
                        pdo_update('ewei_shop_gift_log', ['status' => 2], ['order_sn' => $order['ordersn']]);
                    } else {
                        pdo_update('ewei_shop_gift_log', ['status' => 0], ['order_sn' => $order['ordersn']]);
                        app_error(AppError::$OrderPayFail, "该订单是" . $week['start'] . "--" . $week['end'] . "礼包的商品，已经过了购买期");
                    }
                }
                $fee = floatval($ps["fee"]);
                $shopset = m("common")->getSysset("shop");
                $result = m("member")->setCredit($openid, "RVC", 0 - $fee, array($_W["member"]["uid"], $shopset["name"] . "APP 消费" . $fee));
                m('pay')->creditpay_log($openid, $fee, $orderid,'RVC');
                if (is_error($result)) {
                    return ['status'=>AppError::$OrderPayFail, 'msg'=>$result["message"],'data'=>[]];
                }
                $record = array();
                $record["status"] = "1";
                $record["type"] = "cash";
                pdo_update("core_paylog", $record, array("plid" => $log["plid"]));
                $ret = array();
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
                m("order")->setOrderPayType($order["id"], 6);
                $pay_result = m("order")->payResult($ret);
                $this->success($orderid);
            } else {
                if ($type == "wechat") {
                    if (empty($set["pay"]["wxapp"]) && $iswxapp) {
                        return ['status'=>AppError::$OrderPayFail, 'msg'=>"未开启微信支付",'data'=>[]];
                    }
                    $ordersn = $order["ordersn"];
                    if (!empty($order["ordersn2"])) {
                        $ordersn .= "GJ" . sprintf("%02d", $order["ordersn2"]);
                    }
                    //订单查询接口
                    $payquery = m('pay')->isWeixinPay($ordersn, $order["price"]);
                    if (!is_error($payquery)) {
                        $record = array();
                        $record["status"] = "1";
                        $record["type"] = "wechat";
                        pdo_update("core_paylog", $record, array("plid" => $log["plid"]));
                        m("order")->setOrderPayType($order["id"], 21);
                        $ret = array();
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
                        pdo_update("ewei_shop_order", array("apppay" => 2), array("id" => $order["id"]));
                        $this->success($orderid);
                    }
                    return ['status'=>AppError::$OrderPayFail,'msg'=>'','data'=>[]];
                } else {
                    if ($type == "alipay") {
                        if (empty($set["pay"]["nativeapp_alipay"])) {
                            return ['status'=>AppError::$OrderPayFail, 'msg'=>"未开启支付宝支付",'data'=>[]];
                        }
                        $sec = m("common")->getSec();
                        $sec = iunserializer($sec["sec"]);
                        $public_key = $sec["nativeapp"]["alipay"]["public_key"];
                        if (empty($public_key)) {
                            return ['status'=>AppError::$OrderPayFail, 'msg'=>"支付宝公钥为空",'data'=>[]];
                        }
                        $alidata = htmlspecialchars_decode($_GPC["alidata"]);
                        $alidata = json_decode($alidata, true);
                        $newalidata = $alidata["alipay_trade_app_pay_response"];
                        $newalidata["sign_type"] = $alidata["sign_type"];
                        $newalidata["sign"] = $alidata["sign"];
                        $alisign = m("finance")->RSAVerify($newalidata, $public_key, false, true);
                        if ($alisign) {
                            $record = array();
                            $record["status"] = "1";
                            $record["type"] = "alipay";
                            pdo_update("core_paylog", $record, array("plid" => $log["plid"]));
                            $ret = array();
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
                            pdo_update("ewei_shop_order", array("apppay" => 2), array("id" => $order["id"]));
                            $this->success($order["id"]);
                        }
                    }
                }
            }
        }
    }
}

?>
