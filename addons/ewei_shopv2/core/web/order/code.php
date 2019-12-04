<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}

 class Code_EweiShopV2Page extends WebPage {
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and status > 1 and type = :type and merchid != 0';
        $params = array(':type'=>1);
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_order') . (' WHERE 1 ' . $condition.'  ORDER BY createtime DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $k=>$v){
            $list[$k]["createtime"]=date("Y-m-d H:i:s",$v["createtime"]);
            $list[$k]["finishtime"]=date("Y-m-d H:i:s",$v["finishtime"]);
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["nickname"] = $member["nickname"];
            $list[$k]['mobile'] = $member['mobile'];
            if(is_numeric($v['merchid'])){
                $list[$k]['merch'] = pdo_fetch('select merchname,realname,mobile from '.tablename('ewei_shop_merch_user').'where id = :id ',[':id'=>$v['merchid']]);
                $list[$k]['merch_type'] = 1;
            }else{
                $list[$k]['merch'] = pdo_fetch('select nickname as merchname,realname,mobile from '.tablename('ewei_shop_member').'where id = :id ',[':id'=>intval($v['merchid'])]);
                $list[$k]['merch_type'] = 0;
            }
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_order') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        
        include $this->template();
    }
     
}