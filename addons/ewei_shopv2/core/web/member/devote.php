<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
//fbb 贡献值
class Devote_EweiShopV2Page extends WebPage{
    
    public function main(){
        global $_W;
        global $_GPC;
        $notice=pdo_get("ewei_shop_member_devote",array("id"=>1));
        if ($_W['ispost']){
            $detail=$_GPC["detail"];
            pdo_update("ewei_shop_member_devote",array("content"=>$detail),array("id"=>1));
                show_json(1, array('url' => webUrl('member/devote')));
            
        }
        include $this->template();
    }
    
}