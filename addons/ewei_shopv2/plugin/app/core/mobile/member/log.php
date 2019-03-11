<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class Log_EweiShopV2Page extends AppMobilePage
{
	public function get_list()
	{
		global $_W;
		global $_GPC;
		$type = intval($_GPC['type']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$apply_type = array(0 => '微信钱包', 2 => '支付宝', 3 => '银行卡');
		$condition = ' and openid=:openid and uniacid=:uniacid and type=:type';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':type' => intval($_GPC['type']));
		$list = pdo_fetchall('select * from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition . ' order by createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition), $params);
		$newList = array();
		if (is_array($list) && !empty($list)) {
			foreach ($list as $row) {
				$newList[] = array('id' => $row['id'], 'type' => $row['type'], 'money' => $row['money'], 'typestr' => $apply_type[$row['applytype']], 'status' => $row['status'], 'deductionmoney' => $row['deductionmoney'], 'realmoney' => $row['realmoney'], 'rechargetype' => $row['rechargetype'], 'createtime' => date('Y-m-d H:i', $row['createtime']));
			}
		}

		app_json(array('list' => $newList, 'total' => $total, 'pagesize' => $psize, 'page' => $pindex, 'type' => $type, 'isopen' => $_W['shopset']['trade']['withdraw'], 'moneytext' => $_W['shopset']['trade']['moneytext']));
	}

    public function get_list2()
    {
        global $_W;
        global $_GPC;
        $type = intval($_GPC['type']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
       /* $apply_type = array(0 => '微信钱包', 2 => '支付宝', 3 => '银行卡');
        $condition = ' and openid=:openid and uniacid=:uniacid and type=:type';
        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':type' => intval($_GPC['type']));
        $list = pdo_fetchall('select * from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition . ' order by createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition), $params);
        $newList = array();
        if (is_array($list) && !empty($list)) {
            foreach ($list as $row) {
                $newList[] = array('id' => $row['id'], 'type' => $row['type'], 'money' => $row['money'], 'typestr' => $apply_type[$row['applytype']], 'status' => $row['status'], 'deductionmoney' => $row['deductionmoney'], 'realmoney' => $row['realmoney'], 'rechargetype' => $row['rechargetype'], 'createtime' => date('Y-m-d H:i', $row['createtime']));
            }
        }*/

        $addwhere='';

        if (empty($type)){

		}elseif ($type==1){
            $addwhere.=" and remark like '%邀请%'";
        }elseif ($type==2){
            $addwhere.=" and remark like '%捐赠%'";
        }elseif ($type==3){
            $addwhere.=" and remark like '%兑换%'";
        }


        $condition = " and openid=:openid and uniacid=:uniacid and credittype=:credittype and module=:module   ".$addwhere;
        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':credittype' => 'credit1', ':module' => 'ewei_shopv2');
        $list = pdo_fetchall('select * from ' . tablename('mc_credits_record') . ' where 1 ' . $condition . ' order by createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('mc_credits_record') . ' where 1 ' . $condition, $params);

        foreach ($list as &$row) {
            $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
            $row['type']=0;
        }
        unset($row);
        $newList=$list;

        app_json(array('list' => $newList, 'total' => $total, 'pagesize' => $psize, 'page' => $pindex, 'type' => $type, 'isopen' => $_W['shopset']['trade']['withdraw'], 'moneytext' => $_W['shopset']['trade']['moneytext']));
    }
}

?>
