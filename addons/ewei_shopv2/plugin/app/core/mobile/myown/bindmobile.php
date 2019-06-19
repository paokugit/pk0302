<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

//fbb
class Bindmobile_EweiShopV2Page extends AppMobilePage{
    //获取验证码
    public function send(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $mobile=$_GPC["mobile"];
        if (!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
            app_error(1,"手机号格式不正确");
        }
       
        $code=rand(100000,999999);
        $resault=com_run("sms::mysend", array('mobile'=>$mobile,'tp_id'=>1,'code'=>$code));
        if ($resault["status"]==1){
            $re["code"]=$code;
            $re["mobile"]=$mobile;
            app_error(0,$re);
        }else{
            app_error(1,$resault["message"]);
        }
        
    }
    
    //绑定手机号
    public function bind(){
       global $_W;
       global $_GPC;
       $openid=$_GPC["openid"];
       $mobile=$_GPC["mobile"];
       $member = m('member')->getMember($openid);
       if (empty($member)){
           app_error(1,"openid不正确");
       }else{
           if (pdo_update("ewei_shop_member",array("mobile"=>$mobile),array("openid"=>$openid))){
               //添加卡路里
               m('member')->setCredit($openid, 'credit1', 10, "绑定手机号获取");
               app_error(0,"绑定成功");
           }else{
               app_error(1,"绑定失败");
           }
       }
    }
    
    //手机号是否绑定
    public function isbind(){
        global $_W;
        global $_GPC;
        $openid=$_GPC["openid"];
        $member=m('member')->getMember($openid);
        if (empty($member)){
            app_error(1,"openid不正确");
        }else{
            if ($member["mobile"]){
                $res["bind"]=1;
            }else{
                $res["bind"]=0;
            }
            app_error(0,$res);
        }
    }
    
}