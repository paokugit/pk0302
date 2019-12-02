<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class History_EweiShopV2Page extends AppMobilePage
{
	public function get_list()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and f.uniacid = :uniacid and f.openid=:openid and f.deleted=0';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
		$sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_member_history') . (' f where 1 ' . $condition);
		$total = pdo_fetchcolumn($sql, $params);
		$sql = 'SELECT f.id,f.goodsid,g.title,g.thumb,g.marketprice,g.productprice,f.createtime,g.merchid FROM ' . tablename('ewei_shop_member_history') . ' f ' . ' left join ' . tablename('ewei_shop_goods') . ' g on f.goodsid = g.id ' . ' where 1 ' . $condition . ' ORDER BY `id` DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$result = array(
			'list'     => array(),
			'total'    => $total,
			'pagesize' => $psize
			);
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		if (!empty($list) && $merch_plugin && $merch_data['is_openmerch']) {
			$merch_user = $merch_plugin->getListUser($list, 'merch_user');
			$result['openmerch'] = 1;
		}

		foreach ($list as &$row) {
			$row['createtime'] = date('Y-m-d H:i:s', $row['createtime']);
			$row['thumb'] = tomedia($row['thumb']);
			$row['merchname'] = $merch_user[$row['merchid']]['merchname'] ? $merch_user[$row['merchid']]['merchname'] : $_W['shopset']['shop']['name'];
		}

		unset($row);
		$result['list'] = $list;
		app_json($result);
	}

	public function remove()
	{
		global $_W;
		global $_GPC;
		
        $openid=$_GPC["openid"];
        if ($_GPC["type"]==1){
            $member_id=m('member')->getLoginToken($openid);
            if ($member_id==0){
                $d["openid"]=$_GPC["openid"];
                $d["type"]=$_GPC["type"];
                $d["ids"]=$_GPC["ids"];
                apperror(1,"用户不存在",$d);
            }
            $openid=$member_id;
        }
        $member=m("member")->getMember($openid);
        if (empty($member)){
            
            apperror(1,"用户不存在");    
            
        }
        $del=$_GPC["del"];
        if ($del==1){
            $sql = 'delete from ' . tablename('ewei_shop_member_history') . '  where (openid=:openid or user_id=:user_id) ';
        }else{
            
            $ids = $_GPC['ids'];
            if (empty($ids) || !is_array($ids)) {
                    $d["openid"]=$_GPC["openid"];
                    $d["type"]=$_GPC["type"];
                    $d["ids"]=$_GPC["ids"];
                    apperror(1,"ids格式不正确",$d);
            }
            
		$sql = 'delete from ' . tablename('ewei_shop_member_history') . '  where (openid=:openid or user_id=:user_id) and id in (' . implode(',', $ids) . ')';
        }
		pdo_query($sql, array(':openid' => $member["openid"],':user_id'=>$member["id"]));
		
		    apperror(0,"");
		
		
	}
}

?>
