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
        $free = pdo_fetchall('select * from '.tablename('mc_credits_record').' where createtime > "'.$today.'" and createtime < "'.$tomorrow.'" and openid = "'.$openid.'" and type = 2');
        //抽奖记录
        $log = pdo_fetchall('select m.nickname,m.mobile,c.num,c.remark from '.tablename('mc_credits_record').'c join '.tablename('ewei_shop_member').'m on c.openid = m.openid'.' where type = 1 and credittype = "'.$cate.'" order by c.id desc limit 20');
        foreach ($log as $key=>$item) {
            $mobile = substr($item['mobile'],0,3)."****".substr($item['mobile'],7,4);
            $log[$key]['mobile'] = $item['mobile'] == "" ? "" : $mobile;
        }
        $credit1 = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'credit1');
        show_json(1,['list'=>$list,'log'=>$log,'num'=>count($user)-count($free) > 0 ? :0,'credit1'=>$credit1]);
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
        $log = pdo_fetchall('select * from '.tablename('mc_credits_record').' where createtime > "'.$today.'" and createtime < "'.$tomorrow.'" and openid = "'.$openid.'" and type = 2');
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
        $num = count($user)-count($log)>0?:0;
        if($type == 2) {
            //如果是免费抽奖 他的记录就又加了一条  所以 再减一
            $num = count($user) - count($log) - 1 > 0 ?: 0;
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
        $res = $this->get_gift($gift,$openid);
        //show_json(1,['is_show'=>$res?:0]);
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
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,"openid不能为空");
        }
        //礼包总和
        $gifts = pdo_fetchall(' select id,title,levels,starttime,member from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$uniacid.'"');
        //该用户对应的礼包
        $gift = $this->get_gift($gifts,$openid);
        //该用户的用户ID
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        //已助力的人数
        $help_count = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime > "'.$gift['starttime'].'"');
        //邀请新人记录
        $new = pdo_fetchall('select id,nickname,avatar,openid from '.tablename('ewei_shop_member').' where agentid = "'.$member['id'].'" and createtime > "'.$gift['starttime'].'" order by createtime desc LIMIT 10');
        $new_count = count($new);
        //如果新邀请的人数  不达需要邀请的人数  追加空数据
        if($new_count < $gift['member']){
            $new = $this->addnew($new,$gift['member'],$new_count,'https://paokucoin.com/img/backgroup/touxiang02.png');
        }
        //如果用户身份是店主的话   检测他成为 店主时  是否获得了  免费兑换
        $count = pdo_count('ewei_shop_coupon_data',['openid'=>$openid,'uniacid'=>$_W['uniacid']]);
        $is_get = $count > 0 && $member['agentlevel'] == 5 ? 0 :1;
        $agentlevel = pdo_getcolumn('ewei_shop_commission_level',['id'=>$member['agentlevel'],'uniacid'=>$uniacid],'levelname');
        show_json(1,['all'=>$gift['member'],'help_count'=>$help_count,'new_member'=>$new,'remain'=>bcsub($gift['member'],$help_count),'agent_level'=>$member['agentlevel'],'agentlevel'=>$agentlevel,'gift'=>$gift['title'],'is_get'=>$is_get]);
    }

    /**
     * 领取礼包
     */
    public function getgift()
    {
        global $_GPC;
        $openid = $_GPC['openid'];
        if($openid == ""){
            show_json(0,"参数不完善");
        }
        //检测用户的情况
        $reason = $this->check($openid);
        if($reason !== true){
            show_json(0,$reason);
        }
        $res = $this->addlog($openid);
        if(!is_error($res)){
            show_json(1,'领取成功');
        }
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
        $pageSize = 20;
        $pindex = ($page - 1) * $pageSize;
        //礼包总和
        $gifts = pdo_fetchall(' select id,title,levels,starttime from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$uniacid.'"');
        //该用户对应的礼包
        $gift = $this->get_gift($gifts,$openid);
        $total = pdo_count('ewei_shop_member_getstep','openid = "'.$openid.'" and timestamp > "'.$gift['starttime'].'" and type = 1');
        $step_list = pdo_fetchall('select bang,timestamp,type,step from '.tablename('ewei_shop_member_getstep').'where openid = "'.$openid.'" and timestamp > "'.$gift['starttime'].'" and type = 1 order by id desc LIMIT '.$pindex.','.$pageSize);
        $list = $this->isvalid($step_list,$gift['starttime']);
        if(count($list) > 0){
            show_json(1,['list'=>$list,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize]);
        }else{
            show_json(0,"暂无信息");
        }

    }

    /**
     * 检测用户领取礼包的情况
     * @param $openid
     * @return bool|string
     */
    public function check($openid)
    {
        global $_W;
        //查找所有开启状态的礼包
        $gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$_W['uniacid'].'"');
        //该用户对应的礼包
        $gift = $this->get_gift($gifts,$openid);
        //查看会员信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$_W['uniacid']]);
        $levels = explode(',',$gift['levels']);
        if(!in_array($member['agentlevel'],$levels)){
            return "您的会员身份不符合领取礼包";
        }
        //查看当前时间  是否在礼包的有效期
        if(time() < $gift['starttime'] || time() > $gift['endtime']){
            return "不在活动期间";
        }
        //再查他的领取情况
        $log = pdo_getall('ewei_shop_gift_log',['openid'=>$openid,'uniacid'=>$_W['uniacid']]);
        $ids = array_column($log,'gift_id');
        if(in_array($gift['id'],$ids)){
            return "您已经领过".$gift['title'];
        }
        $num = 0;
        //如果他没领取过  需要邀请新人数量等于当前的领取礼包的数量
        if(count($log) == 0){
            $num += $gift['member'];
        }else{
            //如果领取过了  需要加上已经领取过的礼包需要的数量
            foreach ($log as $item){
                $num += pdo_getcolumn('ewei_shop_gift_bag',['id'=>$item['gift_id'],'uniacid'=>$_W['uniacid']],'member');
            }
            $num += $gift['member'];
        }
        $count = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime > "'.$gift['starttime'].'"');
        if($count < $num){
            return "邀请新人数不足";
        }
        //计算用户有没有会员权益兑换券
        $count = pdo_count('ewei_shop_coupon_data',['openid'=>$openid,'uniacid'=>$_W['uniacid']]);
        if($count != 0 && $member['agentlevel'] == 5){
            return "您已领取过会员权益，不能领取高级礼包";
        }
        return true;
    }

    /**
     * 加领取日志
     * @param $openid
     * @return bool
     */
    public function addlog($openid)
    {
        global $_W;
        //查找所有开启状态的礼包
        $gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$_W['uniacid'].'"');
        //该用户对应的礼包
        $gift = $this->get_gift($gifts,$openid);
        $data = [
            'openid'=>$openid,
            'gift_id'=>$gift['id'],
            'uniacid'=>$_W['uniacid'],
            'createtime'=>time(),
        ];
        return pdo_insert('ewei_shop_gift_log',$data);
    }

    /**
     * 获得该用户应该获得的礼包
     * @param $gift
     * @param $openid
     * @return mixed
     */
    public function get_gift($gift,$openid)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //获得用户的信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        foreach ($gift as &$item) {
            $level = explode(',',$item['levels']);
            //判断是有此范围内
            if(in_array($member['agentlevel'],$level)){
                return $item;
                break;
            }
        }
    }

    /**
     * @param $list
     * @param $time
     * @return mixed
     */
    public function isvalid($list,$time)
    {
        foreach($list as $key=>$item){
            $member = pdo_get('ewei_shop_member',['openid'=>$item['bang']]);
            $list[$key]['nickname'] = $member['nickname'];
            $list[$key]['avatar'] = $member['avatar'];
            $list[$key]['timestamp'] = date('Y-m-d H:i',$item['timestamp']);
            //如果用户的注册时间大于活动开始时间  就有效
            $list[$key]['is_valid'] = $member['createtime'] > $time ? 1 :0;
        }
        return $list;
    }

    /**
     * 当邀请人数  少于需要的人数的时候  追加空数据
     * @param $new
     * @param $total
     * @param $count
     * @param $avatar
     * @return mixed
     */
    public function addnew($new,$total,$count,$avatar)
    {
        $new_push = [
            'nickname'=>'待邀请',
            'avatar'=>$avatar,
        ];
        for ($i=0;$i<$total-$count;$i++){
            array_push($new,$new_push);
        }
        return $new;
    }
}
?>