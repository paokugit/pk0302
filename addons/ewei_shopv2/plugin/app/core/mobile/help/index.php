<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Index_EweiShopV2Page extends AppMobilePage{

    /**
     * 添加助力
     */
    public function addhelp(){
        global $_GPC;
        global $_W;
        if($_GPC['step']>2000 || $_GPC['step']<1) app_error(1,'好友助力步数每日步数范围为：1-2000步');
        $mid = $_GPC['mids'];
        if (!empty($mid) && !empty($_W["openid"])) {
            $pid = m('member')->getMember($mid);
            $iset = pdo_get('ewei_shop_member_getstep', array('bang' => $_W['openid'], 'type' => 1, 'day' => date('Y-m-d'), 'openid' => $pid['openid']));
            if($iset) app_error(0,'助力成功');
            if (!empty($pid)) {
                $data = array(
                    'timestamp' => time(),
                    'openid' => trim($pid["openid"]),
                    'day' => date('Y-m-d'),
                    'uniacid' => $_W['uniacid'],
                    'step' => $_GPC['step'],
                    'type' => 1,
                    'bang' => $_W['openid'],
                    'remark'=>$_GPC['message']
                );
                pdo_insert('ewei_shop_member_getstep', $data);
                app_json('助力成功啦！');
            }else{
                app_error(2,'哎呀，助力人数太多啦，稍后再试哦');
            }
        }
        app_error(3,'mid:'.$mid.'openid'.$_W["openid"]);
    }

    /**
     * 获取助力列表
     */
    public function helplist(){
        global $_GPC;
        global $_W;
        $mid = $_GPC['mids'];//被助力人的mid
        if (!empty($mid) && !empty($_W["openid"])) {
            $memberInfo = m('member')->getMember($mid);
            if(!$memberInfo) app_error(1,'信息不存在');
            if($memberInfo['openid'] == $_W['openid']){//本人查看自己信息
                $data['isonwer'] = 1;
            }
            $helpList = m('getstep')->getHelpList($memberInfo["openid"]);
            if($helpList) app_json(array('helpList'=>$helpList));
            app_error(0,'暂无助力信息');
        }
        app_error(0,'暂无助力信息');
    }

   //今日助力步数
   public function helpstep_today(){
       global $_GPC;
       global $_W;
       $openid=$_W["openid"];
       if (empty($openid)){
           app_error(AppError::$ParamsError);
       }
       $day=date("Y-m-d",time());
       $step_today = pdo_fetchcolumn("select sum(step) from " . tablename('ewei_shop_member_getstep') . " where `day`=:today and  openid=:openid and type=:type", array(':today' => $day, ':openid' => $openid,':type'=>1));
       if (empty($step_today)){
           $m["step"]=0;
       }else{
           $m["step"]=$step_today;
       }
       $m["openid"]=$openid;
       show_json(1, $m);
   }
}
?>