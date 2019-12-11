<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Team_EweiShopV2Page extends AppMobilePage
{
    //下单
    public function order(){
        global $_W;
        global $_GPC;
       
        $goods_id=$_GPC["goods_id"];
        $good=pdo_fetch("select * from ".tablename("ewei_shop_groups_goods")." where id=:id",array(":id"=>$goods_id));
        if (empty($good)){
            apperror(1,"","商品id不正确");
        }
        if ($good["status"]==0){
            apperror(1,"","该商品已下架");
        }
        $option_id=$_GPC["option_id"];
        if ($good["more_spec"]==1&&empty($option_id)){
            apperror(1,"","该商品是多规格商品");
        }
        $total=$_GPC["total"]?$_GPC["total"]:1;
        if ($good["more_spec"]==0&&$good["stock"]<$total){
            apperror(1,"","库存数量不足");
        }
        if ($option_id){
            $option=pdo_get("ewei_shop_groups_goods_option",array("id"=>$option_id));
            if (empty($option)){
                apperror(1,"","规格不存在");
            }
            if ($option["stock"]<$total){
                apperror(1,"","该规格库存数量不足");
            }
        }   
        $single=$_GPC["single"]?$_GPC["single"]:0;
        if ($single==1&&$good["single"]==0){
            apperror(1,"","该商品不支持单独购买");
        }
        $team_id=$_GPC["team_id"]?$_GPC["team_id"]:0;
        $openid=$_GPC["openid"];
        if (empty($openid)){
            apperror(1,"","用户信息不可为空");
        }
        $type=$_GPC["type"]?$_GPC["type"]:0;
        $member=m("appnews")->member($openid,$type);
        if (!$member){
            apperror(1,"","不存在用户");
        }
        if ($team_id!=0){
            $order=pdo_get("ewei_shop_groups_order",array("id"=>$team_id));
            if (empty($order)){
                apperror(1,"","团队id不正确");
            }
            if ($order["success"]==1){
                apperror(1,"","该团队已组团成功，请选择其他团队");
            }
        }
        $data["uniacid"]=$_W["uniacid"];
        $data["openid"]=$member["openid"];
        $data["user_id"]=$member["id"];
        $data["orderno"]=m("common")->createNO("groups_order", "orderno", "PT");
        if (empty($_GPC["addressid"])){
            apperror(1,"","收货地址id未传");
        }
        $data["addressid"]=$_GPC["addressid"];
        if ($single==1){
            //单购
            if ($option_id){
                $price=$option["single_price"];
            }else{
                $price=$good["singleprice"];
            }
            $data["price"]=$price*$total;
            $data["groupnum"]=$total;
        }else{
            if ($option_id){
                $price=$option["price"];
            }else{
                $price=$good["groupsprice"];
            }
            $data["price"]=$price;
            $data["is_team"]=1;
            if ($team_id){
            $data["teamid"]=$team_id;
            }else{
            $data["heads"]=1;
            $data["starttime"]=time();
            $time=date('Y-m-d H:i:s', strtotime('+'.$good["endtime"].'hour'));
            $data["endtime"]=strtotime($time);
            }
            $data["groupnum"]=$good["groupnum"];
        }
        $data["freight"]=$good["freight"];
        $data["addressid"]=$_GPC["addressid"];
        $data["goodid"]=$goods_id;
        $data["more_spec"]=$good["more_spec"];
        $data["createtime"]=time();
        $data["remark"]=$_GPC["remark"];
        $data["goods_price"]=$price;
        $data["goods_option_id"]=$option_id;
        $data["specs"]=$option["title"];
        $data["merchid"]=$good["merchid"];
        pdo_insert("ewei_shop_groups_order",$data);
        $order_id=pdo_insertid();
        $order_good["uniacid"]=$_W["uniacid"];
        $order_good["goods_id"]=$good["gid"];
        $order_good["groups_goods_id"]=$goods_id;
        $order_good["groups_goods_option_id"]=$option_id;
        $order_good["groups_order_id"]=$order_id;
        $order_good["price"]=$price;
        $order_good["option_name"]=$option["title"];
        $order_good["create_time"]=time();
        $order_good["total"]=$total;
        pdo_insert("ewei_shop_groups_order_goods",$order_good);
        $res["order_id"]=$order_id;
        $res["price"]=$data["price"];
        apperror(0,"",$res);
    }
}