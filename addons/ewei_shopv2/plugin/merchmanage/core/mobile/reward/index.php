<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}


require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';

class Index_EweiShopV2Page extends MerchmanageMobilePage
{
    
  //充值--创建订单
  public function order(){
      global $_W;
      global $_GPC;
       $merchid = $_W['merchmanage']['merchid'];
//       var_dump($merchid);die;
      if (empty($merchid)){
          $merchid=$_GPC['merchid'];
      }
      $data["merch_id"]=$merchid;
      $data["purchase_id"]=$_GPC["purchase_id"];
      if ($data["purchase_id"]!=0){
          $purchase=pdo_get("ewei_shop_merch_purchase",array('id'=>$data["purchase_id"]));
          if (empty($purchase)){
              show_json(0,"充值id不正确");
          }
          $data["purchase"]=$purchase["money"];
          $data["give"]=$purchase["give"];
          $data["money"]=$purchase["money"];
      }else{
          $data["money"]=$_GPC["money"];
          $data["purchase"]=$_GPC["money"];
      }
        $data["order_sn"]="GP".date("Ymdhis").rand(100,999).$merchid;
        $data["create_time"]=time();
        if (pdo_insert("ewei_shop_merch_purchaselog",$data)){
            show_json(1,$data["order_sn"]);
        }else{
            show_json(0,"生成订单失败");
        }
      
  }
   //充值--微信支付
   public function order_wx(){
       global $_W;
       global $_GPC;
       $openid=$_W["openid"];
       $order_sn=$_GPC["order_sn"];
       $log=pdo_get("ewei_shop_merch_purchaselog",array('order_sn'=>$order_sn));
       if (empty($log)){
           show_json(0,"订单编号不正确");
       }
//        var_dump($openid);
       $params["openid"]=$openid;
       $params["fee"] =$log["money"];
       $params["title"]="商家充值";
       $params["tid"]=$order_sn;
       load()->model("payment");
       $setting = uni_setting($_W["uniacid"], array( "payment" ));
       if( is_array($setting["payment"]) )
       {
           $options = $setting["payment"]["wechat"];
           $options["appid"] = $_W["account"]["key"];
           $options["secret"] = $_W["account"]["secret"];
       }
       $options["mch_id"]=$options["mchid"];
       // 	    var_dump($options);die;
       
       $wechat = m("common")->fwechat_child_build($params, $options, 0);
       if (is_error($wechat)){
           show_json(0,"生成微信订单失败");
//               var_dump($wechat);
       }
       show_json(1,$wechat);
   }
   
   
  public function cs(){
      var_dump(date("Ymdhis"));
  }  
}