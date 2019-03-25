<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Message_EweiShopV2Page extends AppMobilePage{
     //收集formid
     public function collect(){
         global $_GPC;
         global $_W;
         $data["openid"]=$_GPC["openid"];
         if (empty($data["openid"])){
             app_error(AppError::$ParamsError);
         }
         $data["time"]=strtotime('+7 day');
         $data["formid"]=$_GPC["formid"];
         $data["create_time"]=time();
         if (empty($data['formid'])){
             app_error(-1,"formid不可为空");
         }
         pdo_insert('ewei_shop_member_formid', $data);
         app_error(0,"提交成功");
     }
     
}