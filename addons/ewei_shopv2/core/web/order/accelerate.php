<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}

 class Accelerate_EweiShopV2Page extends WebPage {
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and status=:status';
        $params = array(':status' => 1);
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_acceleration_order') . (' WHERE 1 ' . $condition.'  ORDER BY create_time DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $k=>$v){
            $list[$k]["create_time"]=date("Y-m-d H:i:s",$v["create_time"]);
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["nickname"]=$member["nickname"];
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_acceleration_order') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        
        include $this->template();
    }
     
}