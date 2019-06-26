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
        global $_GPC;
        $page = $_GPC['page']?$_GPC['page']:0;
        $this->getall($page);
        //$count = pdo_count("ewei_shop_member");
        //$haspage = intval($count/10);
        //var_dump($haspage);
        //$page = 0;
//        while ($page<$haspage){
//            echo "---------".$page."--<br/>";
//            $this->getall($page);
//            $page++;
//            echo "---------".$page."--<br/>";
//        }
        die();
    }

    public function getall($page)
    {
        $limit = $page*100;
        $memberList = pdo_fetchall("select * from " . tablename("ewei_shop_member"). " limit ". $limit.",1000");
        foreach ($memberList as $key=>$row){
            $data = array();
            $data["openid"] = $row['openid'];
            //获取推荐人数和直推人数
            $ztcount = $this->getAgent($row['id'],0);
           // var_dump($ztcount);die();
            $data["agentcount"] = $ztcount['count'];
            $data["agentallcount"] = $ztcount['allcount'];
            //店主
            $shopkeeper = $this->getAgent($row['id'],5);
            $data["shopkeepercount"] = $shopkeeper['count'];
            $data["shopkeeperallcount"] = $shopkeeper['allcount'];
            //星耀
            $starshine = $this->getAgent($row['id'],2);
            $data["starshinecount"] = $starshine['count'];
            $data["starshineallcount"] = $starshine['allcount'];
            //健康
            $healthy = $this->getAgent($row['id'],1);
            $data["healthycount"] = $healthy['count'];
            $data["healthyallcount"] = $healthy['allcount'];
            //$data["createtime"] = date('Y:m:d H-i-s');
            $memberInfo = pdo_fetch("select * from " . tablename("ewei_shop_member_agentcount") . " where openid=:openid  limit 1", array( ":openid" => $row['openid']));
            if($memberInfo){
                $res = pdo_update('ewei_shop_member_agentcount',$data,array('openid'=>$row['openid']));
            }else{
                $res = pdo_insert('ewei_shop_member_agentcount',$data);
                $id = pdo_insertid();
            }
            echo "---------".$row['id']."--".$row['openid']."---".$id."--<br/>";
        }
    }

    public function getAgent($id,$agentlevel){
        if($agentlevel>0){
            $ztcount = pdo_fetchcolumn("select count(*) from" . tablename("ewei_shop_member") ."where agentid=:agentid and agentlevel=:agentlevel",array( ":agentid" => $id,":agentlevel"=>$agentlevel));
        }else{
            $ztcount = pdo_fetchcolumn("select count(*) from" . tablename("ewei_shop_member") ."where agentid=:agentid",array( ":agentid" => $id));
        }
        $data["count"] = $ztcount?$ztcount:0;
        //总推荐
        $data["allcount"] = m('member')->allAgentCount($id,$agentlevel);
        return $data;
    }

    public function aa(){
        $shopOwner = m('reward')->addReward("sns_wa_owRAK49usbCooCJGp-81VKLAFJME");//获取是否有上级店长
        var_dump($shopOwner);
    }

}

?>