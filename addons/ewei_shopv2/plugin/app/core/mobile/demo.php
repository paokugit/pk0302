<?php

if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");


class Demo_EweiShopV2Page extends AppMobilePage
{
    public function main()
    {
        exit("Access Denied");
    }

    public function __construct()
    {
        global $_GPC;
        global $_W;
        parent::__construct();
    }

    public function run(){
        $sql = "SELECT mobile from ims_ewei_shop_merch_user where member_id=0";
        $merchList = pdo_fetchall($sql);
        foreach ($merchList as $val){
            $membersql = "SELECT id from ims_ewei_shop_member where mobile=".$val['mobile'];
            $memberinfo = pdo_fetch($membersql);
            if(!$memberinfo) continue;
            //pdo_update('ewei_shop_merch_user', array('member_id' => $memberinfo['id']), array('mobile' => $val['mobile']));
        }
    }

    public function aa(){
        $supperAgentId = m('devote')->getSupperOwnerAgent('89');
        var_dump($supperAgentId);
    }




}

?>