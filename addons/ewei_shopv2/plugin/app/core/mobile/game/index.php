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
        $type = $_GPC['type'];
        $list = pdo_getcolumn('ewei_shop_game',['status'=>1,'type'=>$type,'uniacid'=>$_W['uniacid']],'sets');
        $list = iunserializer($list);
        if($type == 1){
            $cate = "credit2";
        }elseif ($type == 2){
            $cate = "credit3";
        }
        $log = pdo_fetchall('select m.nickname,m.mobile,c.num from '.tablename('mc_credits_record').'c join '.tablename('ewei_shop_member').'m on c.openid = m.openid'.' where type = 1 and credittype = "'.$cate.'"');
        show_json(1,['list'=>$list,'log'=>$log]);
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
        $money = $_GPC['money'];
        $game = pdo_get('ewei_shop_game',['uniacid'=>$_W['uniacid'],'type'=>$_GPC['type']]);
        if($game['status'] == 0){
            show_json(0,"该活动已关闭");
        }
        $today = strtotime(date('Y-m-d'));
        $tomorrow = $today + 60*60*24;
        $log = pdo_fetchall('select * from '.tablename('mc_credits_record').' where createtime > "'.$today.'" and createtime < "'.$tomorrow.'" and openid = "'.$openid.'" and type = 2');
        if(count($log) > 5){
            show_json(0,"今日免费抽奖次数已添加");
        }
        $res = m('game')->prize(iunserializer($game['sets']),$type,$openid,$money);

    }
}
?>