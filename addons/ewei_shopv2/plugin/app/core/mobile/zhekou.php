<?php

if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");


class Zhekou_EweiShopV2Page extends AppMobilePage
{
    /**
     * 卡路里折扣列表
     */
    public function codeList()
    {
        global $_GPC;
        $page = intval($_GPC['page']);
        $pageSize = 8;
        $spage = ($page-1)*$pageSize;
        $list = pdo_fetchall('select id,money,merchid,deduct,cate from '.tablename('ewei_shop_deduct_setting').'where merchid=:merchid and cate=:cate order by money asc LIMIT '.$spage.','.$pageSize,array(':merchid'=>$_GPC['merchid'],':cate'=>$_GPC['cate']));
        show_json(1,['list'=>$list]);
    }

}
?>