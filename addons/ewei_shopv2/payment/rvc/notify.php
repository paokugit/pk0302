<?php
header('Content-type:text/html;charset=utf-8');
require(dirname(__FILE__) . "/../../../../framework/bootstrap.inc.php");
require(IA_ROOT . "/addons/ewei_shopv2/defines.php");
require(IA_ROOT . "/addons/ewei_shopv2/core/inc/functions.php");
require(IA_ROOT . "/addons/ewei_shopv2/core/inc/plugin_model.php");
require(IA_ROOT . "/addons/ewei_shopv2/core/inc/com_model.php");
global $_GPC;
$uuid = $_GPC['uuid'];
pdo_insert('log', ['log' => $uuid, 'createtime' => date('Y-m-d H:i:s', time())]);
if ($uuid) {
    $rvcorder = pdo_fetch('select * from ' . tablename('ewei_shop_member_rvcorder') . ' where ordersn = :ordersn ', [ ':ordersn' => $uuid]);
    pdo_insert('log',['log' => json_encode($rvcorder), 'createtime' => date('Y-m-d H:i:s', time())]);
    if ($rvcorder && $rvcorder['status'] == 0) {
        pdo_update('ewei_shop_member_rvcorder', ['status' => 1], ['ordersn' => $uuid]);
        $member = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where openid = :openid or id = :id ', [':openid' => $rvcorder['openid'], ':id' => $rvcorder['user_id']]);
        $rvc = bcadd($member['RVC'],$rvcorder['totalprice'],2);
        pdo_update('ewei_shop_member', ['RVC' => $rvc], ['id' => $member['id']]);
        $add = [
            'openid'=>$rvcorder['openid'],
            'type'=>0,
            'logno'=>$uuid,
            'title'=>"rvc充值",
            'createtime'=>time(),
            'status'=>1,
            'money'=>$rvcorder['amount'],
            'rechargetype'=>"rvc",
            'realmoney'=>$rvcorder['totalprice'],
            'remark'=>"充值RVC".$rvcorder['amount']."个，价值".$rvcorder['totalprice']."元",
        ];
        pdo_insert('ewei_shop_member_rvclog',$add);
        exit(json_encode(['status'=>200]));
    }
}


?>