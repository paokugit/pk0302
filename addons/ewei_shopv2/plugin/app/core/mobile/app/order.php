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
        $optionid = $_GPC['optionid'] ?  $_GPC['optionid'] : 0;
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
        //用户的token   信息的user_id
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        //地址id
        $address_id = $_GPC['address_id'];
        //商品信息
        $goods = $_GPC['goods'];
        //是否从购物车来的  是传1   不是传0
        $fromcart = $_GPC['fromcart'] ? $_GPC['fromcart'] : 0;
        //用户的留言信息
        $remark = $_GPC['remark'] ? $_GPC['remark'] : [];
        //卡路里
        $deduct1 = $_GPC['deduct1'] ? $_GPC['deduct1'] : 0;
        //折扣宝
        $discount1 = $_GPC['discount1'] ? $_GPC['discount1'] : 0;
        //优惠券id  数组
        $couponid = $_GPC['couponid'] ? $_GPC['couponid'] : [];
        //邀请人的信息
        $mid = $_GPC['mid'];
        $cardid = $_GPC['cardid'] ? $_GPC['cardid'] : 0;
        //套餐id
        $packageid = $_GPC['packageid'] ? $_GPC['packageid'] : 0;
        //配送id  和 配送类型
        $dispatchid = $_GPC['dispatchid'] ? $_GPC['dispatchid'] :0;
        $dispatchtype = $_GPC['dispatchtype'] ? $_GPC['dispatchtype'] : 0;
        //到店自取的话   店铺的id
        $carrierid = $_GPC['carrierid'] ? $_GPC['carrierid'] : 0;
        $bargain_id = $_GPC['bargain_id'] ? $_GPC['bargain_id'] : 0;
        $giftid = $_GPC['giftid'] ? $_GPC['giftid'] : 0;
        $gdid = $_GPC['giftid'] ? $_GPC['giftid'] : 0;
        $carrier = $_GPC['carriers'] ? $_GPC['carriers'] : 0;
        $invoicename = $_GPC['invoicename'] ? $_GPC['invoicename'] : 0;
        $fromquick = $_GPC["fromquick"] ? $_GPC["fromquick"] : 0;
        $receipttime = $_GPC['receipttime'] ? $_GPC['receipttime'] : "";
        //余额抵扣
        $deduct2 = $_GPC['deduct2'] ? $_GPC['deduct2'] : 0;
        $diydata = $_GPC['diydata'] ? $_GPC['diydata'] : 0;
        $data = m('app')->order_submit($user_id,$address_id,$goods,$cardid,$packageid,$dispatchid,$dispatchtype,$carrierid,$bargain_id,$giftid,$gdid,$carrier,$mid,$invoicename,$fromquick,$fromcart,$discount1,$remark,$receipttime,$deduct1,$deduct2,$diydata,$couponid);
        app_error1($data['status'],$data['msg'],$data['data']);
    }


    /**
     * 提交订单2222222
     */
    public function order_submit_new()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        //用户的token   信息的user_id
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        //地址id
        $address_id = $_GPC['address_id'];
        //商品信息  merchid  remark  couponid  goods[ id   optionid  total giftid ]
        $goods = $_GPC['goods'];
        //折扣宝
        $discount1 = $_GPC['discount1'] ? $_GPC['discount1'] : 0;
        //邀请人的信息
        $mid = $_GPC['mid'];
        $data = m('app')->order_submit_new($user_id,$address_id,$goods,$discount1,$mid);
        app_error1($data['status'],$data['msg'],$data['data']);
    }

    /**
     *  收银台
     */
    public function order_pay()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        //用户的token   信息的user_id
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        //订单id
        $order_id = $_GPC['order_id'];
        $iswxapp = $this->iswxapp;
        $data = m('app')->order_pay($user_id,$order_id,$iswxapp);
        app_error1($data['status'],$data['msg'],$data['data']);
    }
    
    /**
     * 点击支付
     */
    public function order_complete()
    {
        header("Access-Control-Allow-Origin:*");
        global $_GPC;
        //用户的token   信息的user_id
        $token = $_GPC['token'];
        $user_id = m('app')->getLoginToken($token);
        if(empty($user_id)) app_error1(2,'登录失效',[]);
        //支付类型
        $type = $_GPC['type'] ? $_GPC['type'] : "credit";
        //订单id
        $id = $_GPC['id'];
        $alidata = $_GPC['data'] ? $_GPC['data'] : "";
        $alidata = explode('&',$alidata);
        foreach ($alidata as $ali){
            $ali_param[] = explode('=',$ali);
        }
        $params = array_column($ali_param,'1','0');
        var_dump($params);exit;
        $data = m('app')->order_complete($user_id,$id,$type,$alidata);
        app_error1($data['status'],$data['msg'],$data['data']);
    }
}
?>