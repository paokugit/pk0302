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
}