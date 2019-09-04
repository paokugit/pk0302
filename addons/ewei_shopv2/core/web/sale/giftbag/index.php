<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid';
		$params = array(':uniacid' => $uniacid);
		$type = trim($_GPC['type']);

		if ($type == 'ing') {
			$condition .= ' and starttime <= ' . time() . ' and endtime >= ' . time() . ' and status = 1 ';
		}
		else if ($type == 'none') {
			$condition .= ' and starttime > ' . time() . ' and status = 1 ';
		}
		else {
			if ($type == 'end') {
				$condition .= ' and (endtime < ' . time() . ' or status = 0) ';
			}
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' AND title LIKE :title';
			$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
		}

		$gifts = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_gift_bag') . "\r\n                    WHERE 1 " . $condition . ' ORDER BY displayorder DESC,id DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('ewei_shop_gift_bag') . ' WHERE 1 ' . $condition . ' ', $params);
		$pager = pagination2($total, $pindex, $psize);
		//计算权限
        $levels = pdo_fetchall('select id,levelname from '.tablename('ewei_shop_commission_level').'where status = 1 and uniacid = "'.$_W['uniacid'].'" order by id asc');
        array_unshift($levels,['id'=>0,'levelname'=>"普通会员"]);
        foreach ($gifts as $key=>$item){
        	$level = explode(',',$item['levels']);
        	foreach ($levels as $val){
        		if(in_array($val['id'],$level)){
        			$gifts[$key]['level'][]= $val['levelname'];
				}
			}
            $gifts[$key]['levels'] = implode(',',$gifts[$key]['level']);
		}
		include $this->template();
	}

	public function add()
	{
		$this->post();
	}

	public function edit()
	{
		$this->post();
	}

	protected function post()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$type = trim($_GPC['type']);
		$id = intval($_GPC['id']);

		if ($_W['ispost']) {
			if (empty($id)) {
				$activity = intval($_GPC['activity']);
			}
			else {
				$activity = intval($_GPC['activitytype']);
			}

			$data = array('uniacid' => $uniacid, 'displayorder' => intval($_GPC['displayorder']), 'title' => trim($_GPC['title']),'desc'=>$_GPC['desc'],'member'=>trim($_GPC['member']),'levels'=>$_GPC['levels'], 'activity' => $activity,  'orderprice' => floatval($_GPC['orderprice']), 'goodsid' => $_GPC['goodsid'],  'starttime' => strtotime($_GPC['starttime']), 'endtime' => strtotime($_GPC['endtime']), 'status' => intval($_GPC['status']), 'share_title' => trim($_GPC['share_title']), 'share_icon' => trim($_GPC['share_icon']), 'share_desc' => trim($_GPC['share_desc']));
			if ($activity == 1 && empty($data['orderprice'])) {
				show_json(0, '订单金额不能为空！');
			}

			if ($activity == 2 && empty($data['goodsid'])) {
				show_json(0, '指定商品不能为空！');
			}

			if (!empty($data['goodsid'])) {
				$goodsid = $data['goodsid'];
				$data['goodsid'] = is_array($data['goodsid']) ? implode(',', $data['goodsid']) : 0;
			}

            if (!empty($data['levels'])) {
                $levels = $data['levels'];
                $data['levels'] = is_array($levels) ? implode(',', $levels) : "";
            }

			if (!empty($data['goodsid'])) {
				$goodsArr = explode(',', $data['goodsid']);

				foreach ($goodsArr as $k => $v) {
					$temp = pdo_fetch('select isverify from ' . tablename('ewei_shop_goods') . ' where uniacid = ' . $uniacid . ' and status = 1 and id = ' . $v . ' and deleted = 0 ');

					if ($temp['isverify'] == 2) {
						show_json(0, '指定商品存在核销商品不允许添加礼包');
					}
				}
			}
			//var_dump($data);exit;
			if (!empty($id)) {
				pdo_update('ewei_shop_gift_bag', $data, array('id' => $id));
				plog('sale.giftbag.edit', '编辑礼包 ID: ' . $id . ' <br/>礼包名称: ' . $data['title']);
			}
			else {
				pdo_insert('ewei_shop_gift_bag', $data);
				$id = pdo_insertid();
				plog('sale.giftbag.add', '添加礼包 ID: ' . $id . '  <br/>礼包名称: ' . $data['title']);
			}

			show_json(1, array('url' => webUrl('sale/giftbag/edit', array('type' => $type, 'id' => $id))));
		}

		$item = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_gift_bag') . ' WHERE uniacid = ' . $uniacid . ' and id = ' . $id . ' ');

		$levels = pdo_fetchall('select id,levelname from '.tablename('ewei_shop_commission_level').'where status = 1 and uniacid = "'.$_W['uniacid'].'" order by id asc');
		array_unshift($levels,['id'=>0,'levelname'=>"普通会员"]);

		if (!empty($item['goodsid'])) {
			$goodsid = explode(',', $item['goodsid']);
			$goods = array();

			if ($goodsid) {
				foreach ($goodsid as $key => $value) {
					$goods[$key] = pdo_fetch('select id,title,thumb from ' . tablename('ewei_shop_goods') . ' where uniacid = ' . $uniacid . ' and status = 1 and id = ' . $value . ' and deleted = 0 ');
				}
			}
		}
		if(!empty($item['levels'])){
            $item['levels'] = explode(',',$item['levels']);
		}
		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_gift_bag') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_gift_bag', array('deleted' => 1, 'status' => 0), array('id' => $item['id']));
			plog('sale.gift.delete', '删除礼包 ID: ' . $item['id'] . ' 礼包名称: ' . $item['title'] . ' ');
		}

		show_json(1, array('url' => referer()));
	}

	public function status()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_gift_bag') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_gift_bag', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('sale.gift.edit', '修改礼包状态<br/>ID: ' . $item['id'] . '<br/>礼包名称: ' . $item['title'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '上架' : '下架');
		}

		show_json(1, array('url' => referer()));
	}

	public function delete1()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_gift_bag') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_gift_bag', array('id' => $item['id']));
			plog('sale.gift.edit', '彻底删除礼包<br/>ID: ' . $item['id'] . '<br/>礼包名称: ' . $item['title']);
		}

		show_json(1, array('url' => referer()));
	}

	public function restore()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_gift_bag') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_gift_bag', array('deleted' => 0), array('id' => $item['id']));
			plog('sale.gift.edit', '恢复礼包<br/>ID: ' . $item['id'] . '<br/>礼包名称: ' . $item['title']);
		}

		show_json(1, array('url' => referer()));
	}

	public function change()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			show_json(0, array('message' => '参数错误'));
		}

		$type = trim($_GPC['typechange']);
		$value = trim($_GPC['value']);

		if (!in_array($type, array('title', 'displayorder'))) {
			show_json(0, array('message' => '参数错误'));
		}

		$gift = pdo_fetch('select id from ' . tablename('ewei_shop_gift_bag') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));

		if (empty($gift)) {
			show_json(0, array('message' => '参数错误'));
		}

		pdo_update('ewei_shop_gift_bag', array($type => $value), array('id' => $id));
		show_json(1);
	}

	public function querygoods()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$kwd = trim($_GPC['keyword']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 8;
		$params = array();
		$params[':uniacid'] = $uniacid;
		$condition = ' and status=1 and deleted=0 and uniacid=:uniacid';

		if (!empty($kwd)) {
			$condition .= ' AND (`title` LIKE :keywords OR `keywords` LIKE :keywords)';
			$params[':keywords'] = '%' . $kwd . '%';
		}

		$ds = pdo_fetchall("SELECT id,title,thumb,marketprice,total,goodssn,productsn,`type`,isdiscount,istime,isverify,share_title,share_icon,description,hasoption,nocommission,groupstype\r\n            FROM " . tablename('ewei_shop_goods') . ("\r\n            WHERE 1 " . $condition . ' ORDER BY displayorder DESC,id DESC LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('ewei_shop_goods') . ' WHERE 1 ' . $condition . ' ', $params);
		$pager = pagination($total, $pindex, $psize, '', array('before' => 5, 'after' => 4, 'ajaxcallback' => 'select_page', 'callbackfuncname' => 'select_page'));
		$ds = set_medias($ds, array('thumb'));
		include $this->template();
	}

	public function querygift()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$kwd = trim($_GPC['keyword']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 8;
		$params = array();
		$params[':uniacid'] = $uniacid;
		$condition = ' and status=2 and deleted=0 and uniacid=:uniacid';

		if (!empty($kwd)) {
			$condition .= ' AND (`title` LIKE :keywords OR `keywords` LIKE :keywords)';
			$params[':keywords'] = '%' . $kwd . '%';
		}

		$ds = pdo_fetchall("SELECT id,title,thumb,marketprice,total\r\n            FROM " . tablename('ewei_shop_goods') . ("\r\n            WHERE 1 " . $condition . ' ORDER BY displayorder DESC,id DESC LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('ewei_shop_goods') . ' WHERE 1 ' . $condition . ' ', $params);
		$pager = pagination($total, $pindex, $psize, '', array('before' => 5, 'after' => 4, 'ajaxcallback' => 'select_page', 'callbackfuncname' => 'select_page'));
		$ds = set_medias($ds, array('thumb'));
		include $this->template();
	}
}

?>
