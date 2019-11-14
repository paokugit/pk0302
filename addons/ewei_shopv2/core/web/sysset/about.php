<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class About_EweiShopV2Page extends WebPage
{
    public function index(){
        
        global $_W;
        global $_GPC;
        $notice=pdo_get("ewei_shop_member_devote",array("id"=>3));
        if ($_W['ispost']){
            $detail=$_GPC["detail"];
            pdo_update("ewei_shop_member_devote",array("content"=>$detail),array("id"=>3));
            show_json(1, array('url' => webUrl('sysset/index')));
            
        }
        include $this->template();
        
    }
    //软著
    public function software(){
        global $_W;
        global $_GPC;
        $notice=pdo_get("ewei_shop_member_devote",array("id"=>4));
        if ($_W['ispost']){
            $detail=$_GPC["detail"];
            pdo_update("ewei_shop_member_devote",array("content"=>$detail),array("id"=>4));
            show_json(1, array('url' => webUrl('sysset/software')));
            
        }
        include $this->template();
    }
}