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
    //活动页面商品
    public function good(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $good_id=$_GPC["good_id"];
        $good=pdo_fetch("select id,title,marketprice,total,thumb_url,commission1_pay,commission2_pay,viewcount,forwardcount,description from ".tablename("ewei_shop_goods")."where id=:id",array(":id"=>$good_id));
        
        if (empty($good)){
            show_json(0,"商品不存在");
        }
        if ($good["total"]<=0){
            show_json(0,"已无库存");
        }
        //获取商品其他信息
        $good["other"]=pdo_get("ewei_shop_goods_bribe_expert",array("goods_id"=>$good_id));
        if ($good["other"]["end_time"]<time()){
            show_json(0,"该活动结束");
        }
        $good["thumb_url"]=iunserializer($good["thumb_url"]);
        //获取音乐
        $music=pdo_get("ewei_shop_music",array("id"=>$good["other"]["music"]));
        $good["other"]["music"]=$music["music"];
        
        show_json(1,$good);
    }
    //立即抢购
    public function order(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $good_id=$_GPC["good_id"];
        $good=pdo_fetch("select * from ".tablename("ewei_shop_goods")."where id=:id",array(":id"=>$good_id));
        
        if (empty($good)){
            show_json(0,"商品不存在");
        }
        if ($good["total"]<=0){
            show_json(0,"已无库存");
        }
        //获取商品其他信息
        $good["other"]=pdo_get("ewei_shop_goods_bribe_expert",array("goods_id"=>$good_id));
        if ($good["other"]["end_time"]<time()){
            show_json(0,"该活动结束");
        }
        $openid=$_W["openid"];
        if (empty($openid)){
            $openid=$_GPC["openid"];
        }
        $data["uniacid"]=1;
        $data["openid"]=$openid;
        $data["ordersn"]="RD".date("Ymdhis").rand(100000,999999);
        $data["goodsprice"]=$good["marketprice"];
        $data["order_type"]=1;
        $data["createtime"]=time();
        $data["merchid"]=$good["merchid"];
        if ($good["other"]["pro_type"]==1){
            //物流
            $data["price"]=$good["marketprice"]+$good["other"]["express_price"];
            $data["dispatchprice"]=$good["other"]["express_price"];
            $data["dispatchtype"]=0;
            //收货地址
            $addr["uniacid"]=$_W["uniacid"];
            $addr["openid"]=$openid;
            $addr["realname"]=$_GPC["realname"];
            $addr["mobile"]=$_GPC["mobile"];
            $addr["province"]=$_GPC["province"];
            $addr["city"]=$_GPC["city"];
            $addr["area"]=$_GPC["area"];
            $addr["address"]=$_GPC["address"];
            $res=pdo_insert("ewei_shop_member_address",$addr);
            if (!empty($res)){
            $data["addressid"]=pdo_insertid();
            }else{
               show_json(0,"填写收货人信息");
            }
            
        }elseif ($good["other"]["pro_type"]==2){
            //自取产品
            $data["price"]=$good["marketprice"];
            $data["dispatchtype"]=1;
            //获取自取用户信息
            $carrier["carrier_realname"]=$_GPC["realname"];
            $carrier["carrier_mobile"]=$_GPC["mobile"];
            if (empty($carrier["carrier_mobile"])||empty($carrier["carrier_realname"])){
                show_json(0,"完善购买人信息");
            }
            $data["carrier"]=iserializer($carrier);
        }else{
            //虚拟产品
            $data["price"]=$good["marketprice"];
            $data["isvirtual"]=1;
            //获取自取用户信息
            $carrier["carrier_realname"]=$_GPC["realname"];
            $carrier["carrier_mobile"]=$_GPC["mobile"];
            if (empty($carrier["carrier_mobile"])||empty($carrier["carrier_realname"])){
                show_json(0,"完善购买人信息");
            }
            $data["carrier"]=iserializer($carrier);
        }
        $order=pdo_insert("ewei_shop_order",$data);
        if (!empty($order)){
            $order_id=pdo_insertid();
            $g["orderid"]=$order_id;
            $g["uniacid"]=$_W["uniacid"];
            $g["goodsid"]=$good_id;
            $g["price"]=$good["marketprice"];
            $g["total"]=1;
            $g["createtime"]=time();
            pdo_insert("ewei_shop_order_goods",$g);
            $order_sn["ordersn"]=$data["ordersn"];
            show_json(1,$order_sn);
        }else{
            show_json(0,"生成订单失败");
        }
    }
    //购买凭证
    public function vouchar(){
        header('Access-Control-Allow-Origin:*');
        global $_w;
        global $_GPC;
        $ordersn=$_GPC["ordersn"];
        $order=pdo_get("ewei_shop_order",array("ordersn"=>$ordersn));
        if (empty($order)){
            show_json(0,"不存在该订单");
        }
        if ($order["status"]==0){
            show_json(0,"该订单未支付");
        }
        $order_good=pdo_get("ewei_shop_order_goods",array("orderid"=>$order["id"]));
        $good=pdo_get("ewei_shop_goods",array("id"=>$order_good["goodsid"]));
        $list["title"]=$good["title"];
        $list["paytime"]=date("Y-m-d H:i:s",$order["paytime"]);
        $list["price"]=$order["price"];
        if ($order["addressid"]!=0){
            $addr=pdo_get("ewei_shop_member_address",array("id"=>$order["addressid"]));
            $list["realname"]=$addr["realname"];
            $list["mobile"]=$addr["mobile"];
        }else{
            $carrier=iunserializer($order["carrier"]);
            $list["realname"]=$carrier["carrier_realname"];
            $list["mobile"]=$carrier["carrier_mobile"];
            
        }
        //获取商家信息
        $shop=pdo_get("ewei_shop_merch_user",array("id"=>$good["merchid"]));
        $list["shop_name"]=$shop["merchname"];
        $list["shop_mobile"]=$shop["mobile"];
        $list["shop_address"]=$shop["address"];
        show_json(1,$list);
    }
    
    

}