<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Index_EweiShopV2Page extends AppMobilePage 
{
    /**
     * 获取奖项
     */
    public function reward()
    {
        global $_GPC;
        global $_W;
        $openid = $_GPC['openid'];
        $type = $_GPC['type'];
        //奖励奖项
        $sets = pdo_getcolumn('ewei_shop_game',['status'=>1,'game_type'=>$type,'uniacid'=>$_W['uniacid']],'sets');
        $list = iunserializer($sets);
        foreach ($list as $key=>$item){
            preg_match('/\d+/',$item['reward'.($key+1)],$arr);
            $list[$key]['reward'.($key+1)] = $arr[0];
        }
        //如果type == 1 是指卡路里转盘   $type == 2 折扣宝转盘
        if($type == 1){
            $cate = "credit1";
        }elseif ($type == 2){
            $cate = "credit3";
        }
        //今日的邀请的新用户  也就是免费抽奖次数
        $today = strtotime(date('Y-m-d'));
        $tomorrow = $today + 60*60*24;
        $uid = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'id');
        $user = pdo_fetchall('select * from '.tablename('ewei_shop_member').' where agentid = "'.$uid.'" and createtime > "'.$today.'" and createtime < "'.$tomorrow.'" limit 5');
        //免费抽奖记录抽奖次数
        $free = pdo_fetchall('select * from '.tablename('mc_credits_record').' where createtime > "'.$today.'" and createtime < "'.$tomorrow.'" and openid = :openid and type = 2',[':openid'=>$openid]);
        //抽奖记录
        $log = pdo_fetchall('select m.nickname,m.mobile,c.num,c.remark from '.tablename('mc_credits_record').'c join '.tablename('ewei_shop_member').'m on c.openid = m.openid'.' where type = 1 and credittype = "'.$cate.'" order by c.id desc limit 20');
        foreach ($log as $key=>$item) {
            $mobile = substr($item['mobile'],0,3)."****".substr($item['mobile'],7,4);
            $log[$key]['mobile'] = $item['mobile'] == "" ? "" : $mobile;
        }
        $credit1 = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'credit1');
        show_json(1,['list'=>$list,'log'=>$log,'num'=>count($user)-count($free) > 0 ? count($user)-count($free) : 0,'credit1'=>$credit1]);
    }

    /**
     * 点击抽奖
     */
    public function getprize(){
        global $_GPC;
        global $_W;
        $openid = $_GPC['openid'];
        //$type==2  免费抽奖   $type == 0 花钱抽奖
        $type = $_GPC['type'];
        $game_type = $_GPC['game_type'];
        $money = $_GPC['money'];
        $game = pdo_get('ewei_shop_game',['uniacid'=>$_W['uniacid']]);
        if($game['status'] == 0){
            show_json(0,"该活动已关闭");
        }
        $credit1=pdo_getcolumn('ewei_shop_member',["openid"=>$openid],"credit1");
        if($type==0){
            if(bccomp($credit1,$money,2)==-1) show_json(0,"小主的卡路里不足啦，赶快邀请好友助力获取卡路里吧");
        }

        //计算今天的免费抽奖次数
        $today = strtotime(date('Y-m-d'));
        $tomorrow = $today + 60*60*24;
        //获得今天推荐人的个数
        $uid = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'id');
        $user = pdo_fetchall('select * from '.tablename('ewei_shop_member').' where agentid = "'.$uid.'" and createtime > "'.$today.'" and createtime < "'.$tomorrow.'" limit 5');
        $log = pdo_fetchall('select * from '.tablename('mc_credits_record').' where createtime > "'.$today.'" and createtime < "'.$tomorrow.'" and openid = :openid and type = 2',[':openid'=>$openid]);
        if($type == 2){
            //如果今天没有邀请新用户 就提示
            if(count($user) <= 0){
                show_json(0,"您今天还没邀请新用户");
            }elseif(bccomp(count($user),count($log),2) != 1){
                //今天邀请的人数  小于等于  记录数量  就说用完了
                show_json(0,"免费抽奖次数".count($user)."已用完");
            }
        }
        //抽奖的结果
        $res = m('game')->prize($game,$type,$openid,$money);
        $num = count($user)-count($log) > 0 ? count($user)-count($log) : 0;
        if($type == 2) {
            //如果是免费抽奖 他的记录就又加了一条  所以 再减一
            $num = count($user) - count($log) - 1 > 0 ? count($user) - count($log) - 1 : 0;
        }
        $res['remain'] = $num;
        $res['credit1'] = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'credit1');
        show_json(1,$res);
    }


/***********************************************助力10人领礼包***********************************************************/
    /*
     * 首页的浮标
     */
    public function icon()
    {
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,"openid不能为空");
        }
        $uniacid = $_W['uniacid'];
        $gift = pdo_fetchall(' select id,title,levels from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$uniacid.'"');
        //$gift = pdo_fetchall(' select id,title,levels from '.tablename('ewei_shop_gift_bag').' where uniacid = "'.$uniacid.'"');
        $res = m('game')->get_gift($gift,$openid);
//        if(!in_array($openid,['sns_wa_owRAK43dDy1s6i0_rbVfZUqgx854','sns_wa_owRAK46JRZDkW6YvErfWRhjNAha0','sns_wa_owRAK44_gHTrMTJMVSxFy-jtNef8','sns_wa_owRAK467jWfK-ZVcX2-XxcKrSyng','sns_wa_owRAK46O_IFxtLx7GnznEPEcAXGE'])){
//            $res = false;
//        }
        show_json(1,['is_show'=>$res?1:0]);
    }

    /**
     * 助力免费领页面
     */
    public function free()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = !empty($_GPC['helpid']) ? pdo_getcolumn('ewei_shop_member',['id'=>$_GPC['helpid']],'openid') : $_GPC['help_openid'];
        $new_openid = trim($_GPC['new_openid']);
        if($openid == ""){
            show_json(0,"openid不能为空");
        }
        $week = m('util')->week(time());
        //该用户的用户ID
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        //新人的信息
        $new_member = [];
	//如果存在被邀请人  并且 被邀请人不是邀请人
        if($new_openid != "" && $new_openid != $openid){
            $new_member = pdo_get('ewei_shop_member',['openid'=>$new_openid,'uniacid'=>$uniacid]);
            $add = ['openid'=>$new_openid,'bang'=>$openid,'createtime'=>time()];
	    //如果被邀请人不为空 
            if(!$new_member){
                //新用户不存在  插入新的用户的openid
                $data = array("uniacid" => $_W["uniacid"],"uid" => 0,'agentid'=>$member['id'], "openid" => $new_openid, 'agentlevel'=>0 ,"openid_wa" => mb_substr($new_openid,7), "comefrom" => "sns_wa","createtime" => time(), "status" => 0);
                pdo_insert('ewei_shop_member',$data);                
                $add['status'] = 1;//添加绑定日志
                $add1 = ['openid'=>$new_openid,'item'=>'game','value'=>'绑定上级:'.$new_openid.'/'.'未获得昵称'.',绑定上级id:'.$member['id'].'-'.$member['nickname'],'create_time'=>date('Y-m-d H:i:s',time())];
                //粉丝
                $my=pdo_get("ewei_shop_member",array("openid"=>$new_openid));
                m("member")->fans($my["id"],$member["id"]);
                
            } elseif ($new_member && $new_member['agentid'] == 0 && $member['agentid'] != $new_member['id']){
                //如果老用户  但是上级   更改上级  但是  老用户   
                pdo_update('ewei_shop_member',['agentid'=>$member['id']],['id'=>$new_member['id']]);
                $add['status'] = 0;
                $add1 = ['openid'=>$new_openid,'item'=>'game','value'=>'绑定上级:'.$new_openid.'/'.$new_member['nickname'].',绑定上级id:'.$member['id'].'-'.$member['nickname'],'create_time'=>date('Y-m-d H:i:s',time())];
                //粉丝
                $my=pdo_get("ewei_shop_member",array("openid"=>$new_openid));
                m("member")->fans($my["id"],$member["id"]);
            }
            m('memberoperate')->addlog($add1);
            if(!pdo_fetch('select * from '.tablename('ewei_shop_gift_record').'where openid = :new_openid and bang = :openid and createtime between "'.$week['start'].'" and "'.$week['end'].'"',[':new_openid'=>$new_openid,':openid'=>$openid])){
                if($new_openid != $openid){
                    pdo_insert('ewei_shop_gift_record',$add);
                }
            }
        }
        //礼包总和
        $gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$uniacid.'"');
        //$gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where uniacid = "'.$uniacid.'"');
        //礼包商品
        $goods = m('game')->gift($gifts,$openid);
        //该用户对应的礼包
        $gift = m('game')->get_gift($gifts,$openid);
        //已助力的人数
        $help_count = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'"');
        //邀请新人记录
        $new = pdo_fetchall('select id,nickname,avatar,openid from '.tablename('ewei_shop_member').' where agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'" order by createtime desc LIMIT 10');
        $new_count = count($new);
        //如果新邀请的人数  不达需要邀请的人数  追加空数据
        if($new_count < $gift['member']){
            $new = m('game')->addnew($new,$gift['member'],$new_count,'https://paokucoin.com/img/backgroup/touxiang02.png');
        }
        //如果用户身份是店主的话   检测他成为 店主时  是否获得了  免费兑换
        $count = pdo_count('ewei_shop_coupon_data',['openid'=>$openid,'uniacid'=>$_W['uniacid'],'couponid'=>2]);
        $is_get = $count > 0 && $member['agentlevel'] == 5 ? 0 :1;
        $agentlevel = $member['agentlevel'] == 0 ? "普通会员" : pdo_getcolumn('ewei_shop_commission_level',['id'=>$member['agentlevel'],'uniacid'=>$uniacid],'levelname');
        //累计助力人数
        $all = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime > "'.$gift['starttime'].'"');
        //目标人数
        $target = m('game')->count($member['agentlevel'],$gifts);
        //show_json(1,['goods'=>$goods,'all'=>$gift['member'],'help_count'=>$help_count,'new_member'=>$new,'remain'=>bcsub($gift['member'],$help_count)?:0,'agent_level'=>$member['agentlevel'],'agentlevel'=>$agentlevel,'avatar'=>$member['avatar'],'gift'=>$gift['title'],'is_get'=>$is_get,'start'=>date('Y-m-d',$gift['starttime']),'end'=>date('Y-m-d',$gift['endtime'])]);
        if($member['agentlevel'] == 5){
            $get_all = 3;
        }elseif ($member['agentlevel'] == 2){
            $get_all = 2;
        }else{
            $get_all = 1;
        }
        $get = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_gift_log').'where openid = :openid and status = 2 and createtime between "'.$week['start'].'" and "'.$week['end'].'"',[':openid'=>$openid]);
        $share = ['title'=>'免费领礼包啦，商品免费领到手','thumb'=>"https://www.paokucoin.com/img/backgroup/free.jpg"];
        show_json(1,['share'=>$share,'goods'=>$goods,'all'=>$all,'desc'=>$gift['desc'],'help_count'=>$help_count,'new_member'=>$new,'remain'=>bcsub($target,$help_count) > 0 ? bcsub($target,$help_count) :0,'agent_level'=>$member['agentlevel'],'agentlevel'=>$agentlevel,'avatar'=>$member['avatar'],'gift'=>$gift['title'],'is_get'=>$is_get,'start'=>date('Y-m-d',$gift['starttime']),'end'=>date('Y-m-d',$gift['endtime']),'get_all'=>$get_all,'gets'=>$get,'week_start'=>date('m.d',$week['start']),'week_end'=>date('m.d',strtotime("-1s",$week['end']))]);
    }

    /**
     * 分享图片  礼包
     */
    public function share()
    {
        $share = ['title'=>'免费领礼包啦，商品免费领到手','thumb'=>"https://www.paokucoin.com/img/backgroup/free.jpg"];
        show_json(1,['share'=>$share]);
    }

    /**
     * 礼包海报
     */
    public function gift_share()
    {
        global $_GPC;
        $mid = $_GPC['mids'];
	$openid = $_GPC['openid'];
        //$member = $this->member;
	$member = pdo_get('ewei_shop_member',['openid'=>$openid]);
        if( empty($member) )
        {
            $member = array( );
        }
        $imgurl = m('qrcode')->HelpPoster($member,$mid,['back'=>'/addons/ewei_shopv2/static/images/gift_share.png','type'=>"giftshare",'title'=>'真的一分钱也不要哟！','desc'=>'快来帮我助力一下吧！','con'=>'周周分享，周周领','url'=>'packageA/pages/gift/gift']);
        if( empty($imgurl))
        {
            app_error(AppError::$PosterCreateFail, "海报生成失败");
        }
        app_json(array( "url" => $imgurl ));
    }
    
    /**
     * 领取礼包
     */
    public function getgift()
    {
        global $_GPC;
        $openid = $_GPC['openid'];
        $goods_id = $_GPC['goodsid'];
        if($openid == "" || $goods_id == "" ){
            show_json(0,"参数不完善");
        }
        //检测用户的情况
        $reason = m('game')->check_gift($openid,$goods_id);
        if($reason !== true){
            show_json(0,$reason);
        }else{
            show_json(1,['is_valid'=>1]);
        }
//        $res = m('game')->add_log($openid,$goods_id);
//        if($res == true){
//            show_json(1,['is_valid'=>1]);
//        }else{
//            show_json(0,['is_valid'=>$res]);
//        }
    }

    /**
     * 助力记录
     */
    public function getstep()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_GPC['openid'];
        $page = max(1,trim($_GPC['page']));
        if($openid == "" || $page == ""){
            show_json(0,"参数不完善");
        }
        $week = m('util')->week(time());
        //用户信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid]);
        $pageSize = 20;
        $pindex = ($page - 1) * $pageSize;
        //礼包总和
        $gifts = pdo_fetchall(' select id,title,levels,starttime from '.tablename('ewei_shop_gift_bag').' where uniacid = "'.$uniacid.'"');
        //该用户对应的礼包
        $gift = m('game')->get_gift($gifts,$openid);
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_gift_record').' where bang = :openid and createtime between "'.$week['start'].'" and "'.$week['end'].'"',[':openid'=>$openid]);
        $record = pdo_fetchall('select * from '.tablename('ewei_shop_gift_record').' where bang = :openid and createtime between "'.$week['start'].'" and "'.$week['end'].'" order by id desc LIMIT '.$pindex.','.$pageSize,[':openid'=>$openid]);
        $new = pdo_fetchall('select id,nickname,avatar,openid,createtime from '.tablename('ewei_shop_member').' where agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'" order by createtime desc LIMIT 10');
        $record = array_merge($record,$new);
        $list = m('game')->isvalid($record,$week['start'],$member['id']);
        $list = m('util')->array_unique_unset($list,"openid");
        if(count($list) > 0){
            show_json(1,['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize]);
        }else{
            show_json(0,"暂无信息");
        }
    }

    /**
     * 领取记录
     */
    public function record()
    {
        global $_W;
        global $_GPC;
        $openid = $_GPC['openid'];
        $uniacid = $_W['uniacid'];
        $page = max(1,$_GPC['page']);
        if($openid == "" || $page == ""){
            show_json(0,"参数不完整");
        }
        $pageSize = 10;
        $pindex = ($page - 1) * $pageSize;
        $total = pdo_count('ewei_shop_gift_log',['uniacid'=>$uniacid,'openid'=>$openid,"status"=>2]);
        $list = pdo_fetchall('select g.thumb,l.gift_id,l.createtime,l.status from '.tablename('ewei_shop_gift_log').'l join '.tablename('ewei_shop_goods').'g on g.id = l.goods_id'.' where l.uniacid = "'.$uniacid.'" and l.openid = :openid and l.status = 2 LIMIT '.$pindex.','.$pageSize,[':openid'=>$openid]);
        foreach($list as $key => $item){
            $week = m('util')->week($item['createtime']);
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
            $gift = m('game')->check($item['gift_id']);
            $list[$key]['title'] = date('m.d',$week['start'])."--".date('m.d',$week['end'])."周领取".$gift;
            $list[$key]['thumb'] = tomedia($item['thumb']);
        }
        if(!empty($list)){
            show_json(1,['total'=>$total,'page'=>$page,'pageSize'=>$pageSize,'list'=>$list]);
        }else{
            show_json(0,"暂无记录");
        }
    }

    /**
     * 领取快报
     */
    public function notice()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $list = pdo_fetchall('select m.nickname,m.avatar,l.gift_id from '.tablename('ewei_shop_gift_log')."l join ".tablename('ewei_shop_member').'m on l.openid = m.openid '.' where l.uniacid = "'.$uniacid.'" and l.status = 2 order by l.id desc LIMIT 66');
        foreach ($list as $key=>$item){
            $list[$key]['gift'] = m('game')->check($item['gift_id']);
        }
        if(!empty($list)) show_json(1,['list'=>$list]);
    }
}
?>
