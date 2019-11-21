<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
class Order_EweiShopV2Page extends AppMobilePage
{
    /**
     * 创建订单
     */
   public function order_create()
   {
       global $_GPC;
       $token = $_GPC['token'];
       $user_id = m('app')->getLoginToken($token);
       if(empty($user_id)) app_error1(2,'登录失效',[]);
       //商品id
       $id = $_GPC['id'];
       $goods = $_GPC['goods'];
       $packageid = $_GPC['packageid'];
       //商品属性id
       $optionid = $_GPC['optionid'];
       $bargain_id = $_GPC['bargain_id'];
       //购买数量
       $total = $_GPC['total'];
       $giftid = $_GPC['giftid'];
       $fromquick = $_GPC['fromquick'];
       $selectDate = $_GPC['selectDate'];
       $gdid = $_GPC['gdid'];
       $data = m('app')->order_create($user_id,$id,$goods,$packageid,$optionid,$bargain_id,$total,$giftid,$fromquick,$selectDate,$gdid);
       app_error1($data['status'],$data['msg'],$data['data']);
   }

   /**
    * 切换地址
    */
   public function order_caculate()
   {
       global $_GPC;
       $token = $_GPC['token'];
       $user_id = m('app')->getLoginToken($token);
       if(empty($user_id)) app_error1(2,'登录失效',[]);
       //要切换的地址id
       $addressid = $_GPC['address_id'];
       //商品信息
       $goods = $_GPC['goods'];
       //优惠券id
       $couponid = $_GPC['couponid'];
       $packageid = $_GPC['packageid'];
       //总价
       $totalprice = $_GPC['totalprice'];
       $dflag = $_GPC['dflag'];
       $cardid = $_GPC['cardid'];
       $bargain_id = $_GPC['bargain_id'];
       $data = m('app')->order_caculate($user_id,$addressid,$goods,$packageid,$totalprice,$dflag,$cardid,$bargain_id,$couponid);
       app_error1($data['status'],$data['msg'],$data['data']);
   }

   /**
    * 提交支付  收银台
    */
   public function order_submit()
   {
       global $_GPC;
       $token = $_GPC['token'];
       $user_id = m('app')->getLoginToken($token);
       if(empty($user_id)) app_error1(2,'登录失效',[]);
   }

    /**
     * 订单支付
     */
    public function order_pay()
    {
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
    }
}
?>