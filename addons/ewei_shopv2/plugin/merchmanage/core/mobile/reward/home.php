<?php
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}


require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';

 class Home_EweiShopV2Page extends MerchmanageMobilePage{
     
     //赏金任务首页
     public function index(){
         
         global $_W;
         global $_GPC;
         
         include $this->template();
     }
    
    //充值
    public function recharge(){
        global $_W;
        global $_GPC;
       
//         var_dump($openid);
        include $this->template();
    }
    //明细
    public function log(){
        global $_W;
        global $_GPC;
        
        include $this->template();
    }
    //发布
    public function release(){
        global $_W;
        global $_GPC;
        $goodid=$_GPC["goodid"];
        include $this->template();
    }
    //选择商品
    public function good(){
        global $_W;
        global $_GPC;
        $goodid=$_GPC["goodid"];
       
        include $this->template();
    }
}