<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "merch/core/inc/page_merch.php");
//fbb
class Shophome_EweiShopV2Page extends MerchWebPage
{
    //商家图片
    public function img(){
        global $_W;
        global $_GPC;
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $_W['uniaccount']['merchid'], ':uniacid' => $_W['uniacid']));
        $piclist=unserialize($item["shopimg"]);
        if ($_POST){
            $thumbs = $_GPC['thumbs'];
            if (empty($thumbs)){
                show_json(0, '请选择图片');
            }
            $thumbs=serialize($thumbs);
            if (pdo_update("ewei_shop_merch_user",array("shopimg"=>$thumbs),array("id"=>$_W['uniaccount']['merchid']))){
                show_json(1);
            }else{
                show_json(0,"更新失败");
            }
        }
        include $this->template();
    }
    //商家视频
    public function video(){
        global $_W;
        global $_GPC;
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $_W['uniaccount']['merchid'], ':uniacid' => $_W['uniacid']));
        include $this->template();
    }
}