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
        
//         //加速剩余天数
//         if ($day>=$accelerate_day){
           
//             $resault["surplus_day"]=0;
//         }else{
//             $count_days=m("member")->count_days($accelerate_day,$day);
            
//             $resault["surplus_day"]=$count_days;
//         }
//         $resault["give_day"]=$level["accelerate_day"];
//         //已加速天数
//         $resault["accelerate_day"]=$level["accelerate_day"]-$resault["surplus_day"];
        
        $dd=m("member")->acceleration($openid);
        //加速剩余天数
        $resault["surplus_day"]=$dd["day"];
        $resault["give_day"]=$dd["give_day"];
        $resault["accelerate_day"]=$dd["accelerate_day"];
        $resault["type"]=$dd["type"];
        
        //获取用户加速期间的卡路里
        if ($dd["type"]==0){
        $starttime=strtotime($member["agentlevel_time"]);
        $endtime=strtotime($accelerate_day);
        }else{
            $starttime=strtotime($member["accelerate_start"]);
            $endtime=strtotime($member["accelerate_end"]);
        }
        
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
        global $_W;
        global $_GPC;
        $type=$_GPC["type"];
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_adsense")." where type=:type order by sort desc",array(":type"=>$type));
        foreach ($list as $k=>$v){
            $list[$k]["thumb"]=tomedia($v["thumb"]);
        }
        $l["list"]=$list;
        show_json(1,$l);
    }
    
    //页面优化
    public function opt(){
        global $_W;
        global $_GPC;
        $id=$_GPC["id"];
        $list=pdo_get("ewei_shop_small_set",array("id"=>$id));
        $list["icon"]=unserialize($list["icon"]);
        $list["backgroup"]=tomedia($list["backgroup"]);
        $list["banner"]=tomedia($list["banner"]);
        foreach ($list["icon"] as $k=>$v){
            $list["icon"][$k]["img"]=tomedia($v["img"]);
            $list["icon"][$k]["icon"]=tomedia($v["icon"]);
        }
        show_json(1,$list);
    }
    
    public function cd(){
        $openid="sns_wa_owRAK467jWfK-ZVcX2-XxcKrSyng";
        //卡路里
        //获取今日已兑换的卡路里
        $starttime=strtotime(date("Y-m-d 23:59:59",strtotime('-1 day')));
        $endtime=strtotime(date("Y-m-d 00:00:00",strtotime('+1 day')));
        $count_list=pdo_fetchall("select num from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and createtime>=:starttime and createtime<=:endtime and num>0 and (remark like :remark1 or remark like :remark2) order by id desc",array(':openid'=>$openid,':credittype'=>"credit1",":starttime"=>$starttime,':endtime'=>$endtime,':remark1'=>'%步数兑换%',':remark2'=>'%好友助力%'));
        var_dump($count_list);
        $count=array_sum(array_column($count_list, 'num'));
        var_dump($count);
        $order=array('26707','26773','27216','27866','27937','28285','28389','28399');
        pdo_update("ewei_shop_merch_bill",array("orderids"=>iserializer($order)),array("id"=>169));
        $d=pdo_get("ewei_shop_merch_bill",array("id"=>157));
        var_dump(iunserializer($d["orderids"])); 
    }
    
    public function mycenter(){
        $list=pdo_get("ewei_shop_small_set",array("id"=>3));
        $l=unserialize($list["icon"]);
        foreach ($l["order"] as $k=>$v){
            if (!empty($v)){
            $l["order"][$k]=tomedia($v);
            }
        }
        foreach ($l["server"] as $k=>$v){
            if (!empty($v)){
                $l["server"][$k]=tomedia($v);
            }
        }
        show_json(1,$l);
    }
    
}

?>