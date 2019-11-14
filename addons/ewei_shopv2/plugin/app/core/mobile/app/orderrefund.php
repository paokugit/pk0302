<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Orderrefund_EweiShopV2Page extends AppMobilePage
{
    //退款申请
    public function refund_mes(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                app_error(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            app_error(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            app_error(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                app_error(1,"无权限访问此订单");
            }
        }
        $res["order_id"]=$order_id;
        if ($order["status"]==1){
        $res["price"]=$order["price"];
        $res["dispatchprice"]=$order["dispatchprice"];
        }else{
            $res["price"]=$order["goodsprice"];
            $res["dispatchprice"]=0;
        }
        //获取地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        $res["mobile"]=$address["mobile"];
        $res["realname"]=$address["realname"];
        //获取商品
        $goods = pdo_fetchall("select og.goodsid,g.title,g.thumb,og.optionname as optiontitle from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid " . " where og.orderid=:order_id ", array("order_id"=>$order_id));
        $goods = set_medias($goods, array( "thumb" ));
        $res["goods"]=$goods;
        app_error(0,$res);
    }
    //退款||退货退款
    public function refund_submit(){
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                app_error(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            app_error(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            app_error(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                app_error(1,"无权限访问此订单");
            }
        }
        if ($order['status'] == '-1') {
            app_error(AppError::$OrderCanNotRefund, '订单已经处理完毕');
        }
        
        $rtype = intval($_GPC['rtype']);//0退款 1退款退货
        $refund = array('uniacid' => $_W['uniacid'], 'merchid' => $order['merchid'], 'rtype' => $rtype, 'reason' => trim($_GPC['reason']), 'content' =>trim($_GPC['content']));

            $refund['createtime'] = time();
            $refund['orderid'] = $order_id;
            if ($order["status"]==1){
            $refund["applyprice"]=$order["price"];
            $refund['orderprice'] = $order['price'];
            
            }else{
            $refund["applyprice"]=$order["goodsprice"];
            $refund['orderprice'] = $order['goodsprice'];
            
            }
            
            $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
            if (pdo_insert('ewei_shop_order_refund', $refund)){
            $refundid = pdo_insertid();
            pdo_update('ewei_shop_order', array('refundid' => $refundid, 'refundstate' => 1), array('id' => $order_id));
            app_error(0,"提交成功");
            }else {
                app_error(1,"提交失败");
            }
        
    }
    //换货
    public function exchange_goods(){
        
        global $_GPC;
        global $_W;
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                app_error(1,"无此用户");
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            app_error(1,"无此用户");
        }
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_order",array("id"=>$order_id));
        if (empty($order)){
            app_error(1,"订单不存在");
        }else{
            if ($order["openid"]!=$member["openid"]&&$order["user_id"]!=$member["id"]){
                app_error(1,"无权限访问此订单");
            }
        }
        $res["order_id"]=$order_id;
        $goods_id=$_GPC["goods_id"];
        //获取地址
        $address=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
        $res["mobile"]=$address["mobile"];
        $res["realname"]=$address["realname"];
        $res["address"]=$address["province"].$address["city"].$address["area"].$address["address"];
        //获取商品
        $goods = pdo_fetch("select og.goodsid,og.total,g.title,g.thumb,og.optionname as optiontitle from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id=og.goodsid " . " where og.orderid=:order_id and og.goodsid=:goods_id", array("order_id"=>$order_id,":goods_id"=>$goods_id));
        if (empty($goods)){
            app_error(1,"订单商品不存在");
        }
        $goods = set_medias($goods, array( "thumb" ));
        $res["goods"]=$goods;
        //获取规格
        $spec=pdo_fetchall("select * from ".tablename("ewei_shop_goods_spec")." where goodsid=:goodsid",array(":goodsid"=>$goods_id));
//          var_dump($spec);
        $res["spec"]=array();
        foreach ($spec as $k=>$v){
            $res["spec"][$k]["id"]=$v["id"];
            $res["spec"][$k]["title"]=$v["title"];
            $value=unserialize($v["content"]);
            $res["spec"][$k]["value"]=array();
            foreach ($value as $kk=>$vv){
                $spec_item=pdo_get("ewei_shop_goods_spec_item",array("id"=>$vv));
                $res["spec"][$k]["value"][$kk]["item_id"]=$vv;
                $res["spec"][$k]["value"][$kk]["item_name"]=$spec_item["title"];
            }
        }
        app_error(0,$res);
        
    }
}