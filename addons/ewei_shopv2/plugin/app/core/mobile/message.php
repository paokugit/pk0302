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
     
     public function message(){
         $touser="sns_wa_owRAK43dDy1s6i0_rbVfZUqgx854";
         $template_id="_z-2ZdOYhmyqTEnByOjyWPhkux8Sw0LpUDs9Dwfq2qo";
         
         $postdata=array(
             'keyword1'=>array(
                 'value'=>"11",
                 'color' => '#ff510'
             ),
             'keyword2'=>array(
                 'value'=>"22",
                 'color' => '#ff510'
             ),
             'keyword3'=>array(
                 'value'=>"3",
                 'color' => '#ff510'
             ),
             'keyword4'=>array(
                 'value'=>"4",
                 'color' => '#ff510'
             ),
             'keyword5'=>array(
                 'value'=>"5",
                 'color' => '#ff510'
             ),
             'keyword6'=>array(
                 'value'=>"6",
                 'color' => '#ff510'
             ),
             'keyword6'=>array(
                 'value'=>"6",
                 'color' => '#ff510'
             ),
             
         );
         
         
         $resualt=p("app")->mysendNotice($touser, $postdata,  '', $template_id);
         var_dump($resualt); 
     }
     
    
}