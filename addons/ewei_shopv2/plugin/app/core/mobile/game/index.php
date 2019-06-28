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
        show_json(1,['list'=>$list,'log'=>$log,'num'=>count($user)-count($free) > 0 ? :0]);
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
        show_json(1,$res);
    }
}
?>