<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
//fanbeibei
class Index_EweiShopV2Page extends AppMobilePage{
    //获取用户加速判断
    public function accelerate(){
        global $_GPC;
        global $_W;
       
        $openid=$_GPC["openid"];
        if (empty($openid)){
            app_error(AppError::$ParamsError);
        }
        $member=m('member')->getMember($openid);
       
        if (empty($member)){
            app_error(1,"用户不存在");
        }elseif ($member["agentlevel"]==0){
            app_error(1,"用户为普通会员");
        }
        $level=pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
        //加速日期
        $accelerate_day=date("Y-m-d",strtotime("+".$level["accelerate_day"]." day",strtotime($member["agentlevel_time"])));
        
        $day=date("Y-m-d",time());
        
        //加速剩余天数
        if ($day>=$accelerate_day){
           
            $resault["surplus_day"]=0;
        }else{
            $count_days=m("member")->count_days($accelerate_day,$day);
            
            $resault["surplus_day"]=$count_days;
        }
        $resault["give_day"]=$level["accelerate_day"];
        //已加速天数
        $resault["accelerate_day"]=$level["accelerate_day"]-$resault["surplus_day"];
        //获取用户加速期间的卡路里
        
        $starttime=strtotime($member["agentlevel_time"]);
        $endtime=strtotime($accelerate_day);
//         var_dump($starttime);
//         var_dump($endtime);
        $credit=pdo_fetchcolumn("select sum(num) from ".tablename('mc_credits_record')."where credittype=:credittype and  openid=:openid and createtime>=:starttime and createtime<=:endtime and (remark like :remark or remark like :cc)",array('credittype'=>"credit1",':openid'=>$openid,':starttime'=>$starttime,':endtime'=>$endtime,':remark'=>'%'.'步数兑换',':cc'=>'好友助力'));
//         $credit=pdo_fetchall("select * from ".tablename('mc_credits_record')."where credittype=:credittype and  openid=:openid and createtime>=:starttime and createtime<=:endtime ",array('credittype'=>"credit1",':openid'=>$openid,':starttime'=>$starttime,':endtime'=>$endtime));
//         var_dump($credit);die;
        if (empty($credit)){
            $resault["credit"]=0;
        }else{
            $resault["credit"]=$credit;
        }
        app_error(0,$resault);
    }
    //scene
    public function scene(){
         global $_W;
         global $_GPC;
         $data["openid"]=$_GPC["openid"];
         $data["scene"]=$_GPC["scene"];
         $data["create_time"]=date("Y-m-d H:i:s");
         pdo_insert("ewei_shop_member_scene",$data);
         show_json(1,"成功");
    }
    
    //首页广告位
    public function adsense(){
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_adsense")." order by sort desc");
        foreach ($list as $k=>$v){
            $list[$k]["thumb"]=tomedia($v["thumb"]);
        }
        show_json(1,$list);
    }
}

?>