<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Share_EweiShopV2Page extends MobilePage
{
    public function index(){
       global $_W;
       global $_GPC;
      
       if (empty($_W["openid"])){
           var_dump("11");
           $result = mc_oauth_userinfo();
           var_dump($result);
           $_W["openid"]=$result["openid"];
           $openid=$result["openid"];
       }else {
           var_dump("22");
           $openid=$_W["openid"];
       }
       var_dump($_W["openid"]);
       var_dump($openid);
    }
}