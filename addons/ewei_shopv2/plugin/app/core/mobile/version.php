<?php

if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");


class Version_EweiShopV2Page extends AppMobilePage
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

    public function appversion()
    {
        global $_GPC;

        if($_GPC['versions'] && $_GPC['versions']>=6) app_json(array(
            'app_version' => 0,
        ));

        $referer = $_SERVER['HTTP_REFERER'];
        preg_match('/https:\/\/servicewechat\.com\/(.+?)\/(.+?)\/page-frame\.html/i', $referer,$matches);
        if($matches[1]){
            $res = array(
                'app_id' => $matches[1],
                'app_version' => $matches[2],
            );
            app_json($res);
        }

        app_error(0, "参数错误");
    }




}

?>