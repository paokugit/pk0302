<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Task_EweiShopV2Page extends AppMobilePage
{
    public function order(){
        global $_W;
        global $_GPC;
        $order=pdo_fetchall("select * from ".tablename("ewei_shop_groups_order")." where is_team=1 and status=1 and heads=1 and endtime<:endtime and success=0",array(":endtime"=>time()));
        foreach ($order as $k=>$v){
            pdo_update("ewei_shop_groups_order",array("success" => -1,"canceltime" => $time),array("is_team"=>1, "status"=>1,"teamid"=>$v["id"]));
            pdo_update("ewei_shop_groups_order",array("status"=>-1),array("is_team"=>1,"status"=>0,"teamid"=>$v["id"]));
        }
    }
    public function selectorder(){
        global $_W;
        global $_GPC;
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_groups_order")." where is_team=1 and status=1 and success=-1");
        foreach ($list as $k=>$v){
            $realprice=$v["price"]+$v["freight"]-$v["creditmoney"];
            $realprice = round($realprice, 2);
            if ($v["pay_type"]=="wxapp"){
                $result = m("finance")->wxapp_refund($v["openid"], $v["orderno"], $v["orderno"], $realprice * 100, $realprice * 100);
                
            }else{
                $result = m("member")->setCredit($v["openid"], $v["pay_type"], $realprice, array( 0, "拼团失败退款: " . $realprice . "元 订单号: " . $v["orderno"] ));
            }
            if( is_error($result) && $result["message"] != "OK | 订单已全额退款" && $result["message"] != "Refund exists|退款已存在" )
            {
                continue;
            }
            pdo_update("ewei_shop_groups_order", array( "refundstate" => 0, "status" => -1, "refundtime" => time() ), array( "id" => $v["id"], "uniacid" => $_W["uniacid"] ));
            $sales = pdo_fetch("select id,sales,stock from " . tablename("ewei_shop_groups_goods") . " where id = :id and uniacid = :uniacid ", array( ":id" => $v["goodid"], ":uniacid" => $_W["uniacid"] ));
            pdo_update("ewei_shop_groups_goods", array( "sales" => $sales["sales"] - 1, "stock" => $sales["stock"] + 1 ), array( "id" => $sales["id"], "uniacid" => $_W["uniacid"] ));
            if( $v["more_spec"] == 1 )
            {
                $option = pdo_get("ewei_shop_groups_order_goods", array( "uniacid" => $_W["uniacid"], "groups_order_id" => $v["id"] ));
                pdo_update("ewei_shop_groups_goods_option", array( "stock" =>$option["stock"]+1), array( "id" => $option["groups_goods_option_id"] ));
            }
            plog("groups.task.refund", "拼团失败订单退款 ID: " . $v["id"] . " 订单号: " . $v["orderno"]);
        }
    }
    public function finance(){
        global $_W;
        global $_GPC;
        $order_id=$_GPC["order_id"];
        $order=pdo_get("ewei_shop_groups_order",array("id"=>$order_id));
        $realprice=$order["price"]+$order["freight"]-$order["creditmoney"];
        $realprice = round($realprice, 2);
        $result = m("finance")->wxapp_refund($order["openid"], $order["orderno"], $order["orderno"], $realprice * 100, $realprice * 100);
        
        if( is_error($result) && $result["message"] != "OK | 订单已全额退款" && $result["message"] != "Refund exists|退款已存在" )
        {
            continue;
        }
        pdo_update("ewei_shop_groups_order", array( "refundstate" => 0, "status" => -1, "refundtime" => time() ), array( "id" => $order["id"], "uniacid" => $_W["uniacid"] ));
        $sales = pdo_fetch("select id,sales,stock from " . tablename("ewei_shop_groups_goods") . " where id = :id and uniacid = :uniacid ", array( ":id" => $order["goodid"], ":uniacid" => $_W["uniacid"] ));
        pdo_update("ewei_shop_groups_goods", array( "sales" => $sales["sales"] - 1, "stock" => $sales["stock"] + 1 ), array( "id" => $sales["id"], "uniacid" => $_W["uniacid"] ));
        if( $order["more_spec"] == 1 )
        {
            $option = pdo_get("ewei_shop_groups_order_goods", array( "uniacid" => $_W["uniacid"], "groups_order_id" => $order["id"] ));
            pdo_update("ewei_shop_groups_goods_option", array( "stock" =>$option["stock"]+1), array( "id" => $option["groups_goods_option_id"] ));
        }
    }
}