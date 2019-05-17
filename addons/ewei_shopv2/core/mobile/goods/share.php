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
           mc_oauth_account_userinfo();
       }
           var_dump("22");
           $openid=$_W["openid"];
      
       var_dump($openid);
      
    }
}