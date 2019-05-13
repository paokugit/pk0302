<?php

if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");

class Index_EweiShopV2Page extends AppMobilePage
{
    public function main()
    {
        exit("Access Denied");
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function getMpId()
    {
        $referer = $_SERVER['HTTP_REFERER'];
        preg_match('/https:\/\/servicewechat\.com\/(.+?)\/(.+?)\/page-frame\.html/i', $referer,$matches);
        if($matches[1]){
            return [
                'app_id' => $matches[1],
                'app_version' => $matches[2],
            ];
        }
        return null;
    }




}

?>