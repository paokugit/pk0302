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
       header("Access-Control-Allow-Origin:*");
       global $_GPC;
       $token = $_GPC['token'];
       $user_id = m('app')->getLoginToken($token);
       if(empty($user_id)) app_error1(2,'登录失效',[]);
       //商品id
       $id = $_GPC['id'];
       $goods = $_GPC['goods'] ? $_GPC['goods'] : [];
       $packageid = $_GPC['packageid'] ? $_GPC['packageid'] : 0;
       //商品属性id
       $optionid = $_GPC['optionid'];
       $bargain_id = $_GPC['bargain_id'] ? $_GPC['bargain_id'] : 0;
       //购买数量
       $total = $_GPC['total'];
       $giftid = $_GPC['giftid'] ? $_GPC['giftid'] : 0;
       $fromquick = $_GPC['fromquick'] ? $_GPC['fromquick'] : 0;
       $selectDate = $_GPC['selectDate'] ? $_GPC['selectDate'] : 0;
       $gdid = $_GPC['gdid'] ? $_GPC['gdid'] :0;
       //购物车id
       $cartid = $_GPC['cartid'] ? $_GPC['cardid'] : 0;
       $data = m('app')->order_create($user_id,$id,$goods,$packageid,$optionid,$bargain_id,$total,$giftid,$fromquick,$selectDate,$gdid,$cartid);
       //$data = m('app')->order_create($user_id,$id,$optionid,$total);
       app_error1($data['status'],$data['msg'],$data['data']);
   }

   /**
    * 切换地址
    */
   public function order_caculate()
   {
       header("Access-Control-Allow-Origin:*");
       global $_GPC;
       $token = $_GPC['token'];
       $user_id = m('app')->getLoginToken($token);
       if(empty($user_id)) app_error1(2,'登录失效',[]);
       //要切换的地址id
       $addressid = $_GPC['address_id'];
       //商品信息  goodsid  total  optionid
       $goods = $_GPC['goods'] ? $_GPC['goods'] : [];
       //优惠券id
       $couponid = $_GPC['couponid'] ? $_GPC['couponid'] : 0;
       $packageid = $_GPC['packageid'] ? $_GPC['packageid'] : 0;
       //总价
       $totalprice = $_GPC['totalprice'] ? $_GPC['totalprice'] : 0;
       $dflag = $_GPC['dflag'] ? $_GPC['dflag'] : 0;
       $cardid = $_GPC['cardid'] ? $_GPC['cardid'] : 0;
       $bargain_id = $_GPC['bargain_id'] ? $_GPC['bargain_id'] :0;
       $data = m('app')->order_caculate($user_id,$addressid,$goods,$packageid,$totalprice,$dflag,$cardid,$bargain_id,$couponid);
       app_error1($data['status'],$data['msg'],$data['data']);
   }

   /**
    * 提交支付
    */
   public function order_submit()
   {
       header("Access-Control-Allow-Origin:*");
       global $_GPC;
       $token = $_GPC['token'];
       $user_id = m('app')->getLoginToken($token);
       if(empty($user_id)) app_error1(2,'登录失效',[]);
       $address_id = $_GPC['address_id'];
       $goods = $_GPC['goods'];
       $cardid = $_GPC['cardid'] ? $_GPC['cardid'] : 0;
       $packageid = $_GPC['packageid'] ? $_GPC['packageid'] : 0;
       $dispatchid = $_GPC['dispatchid'];
       $dispatchtype = $_GPC['dispatchtype'];
       $carrierid = $_GPC['carrierid'] ? $_GPC['carrierid'] : 0;
       $bargain_id = $_GPC['bargain_id'] ? $_GPC['bargain_id'] : 0;
       $giftid = $_GPC['giftid'];
       $gdid = $_GPC['giftid'] ? $_GPC['giftid'] : 0;
       $carrier = $_GPC['carriers'];
       $mid = $_GPC['mid'];
       $invoicename = $_GPC['invoicename'];
       $fromcart = $_GPC['fromcart'];
       $fromquick = $_GPC["fromquick"];
       $remark = $_GPC['remark'];
       $discount1 = $_GPC['discount1'];
       $receipttime = $_GPC['receipttime'];
       $deduct1 = $_GPC['deduct1'];
       $deduct2 = $_GPC['deduct2'];
       $diydata = $_GPC['diydata'];
       $couponid = $_GPC['couponid'];
       $data = m('app')->order_submit($user_id,$address_id,$goods,$cardid,$packageid,$dispatchid,$dispatchtype,$carrierid,$bargain_id,$giftid,$gdid,$carrier,$mid,$invoicename,$fromquick,$fromcart,$discount1,$remark,$receipttime,$deduct1,$deduct2,$diydata,$couponid);
       app_error1($data['status'],$data['msg'],$data['data']);
   }

    /**
     *  收银台
     */
    public function order_pay()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        $orderid = $_GPC['orderid'];
        $data = m('app')->order_pay($user_id,$orderid);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     * 点击支付
     */
    public function order_complete()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        $type = $_GPC['type'];
        $id = $_GPC['id'];
        $data = m('app')->order_complete($user_id,$type,$id);
        app_error1($data['status'],$data['msg'],$data['data']);
    }
}
?>