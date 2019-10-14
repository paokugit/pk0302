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

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' AND title LIKE :title';
			$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
		}

		$gifts = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_look_buy') . "\r\n                    WHERE 1 " . $condition . ' ORDER BY displayorder DESC,id DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('ewei_shop_look_buy') . ' WHERE 1 ' . $condition . ' ', $params);
		$pager = pagination2($total, $pindex, $psize);
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
		$id = intval($_GPC['id']);

		if ($_W['ispost']) {

			$data = array('uniacid' => $uniacid, 'displayorder' => intval($_GPC['displayorder']), 'title' => trim($_GPC['title']), 'goods_id' => $_GPC['goodsid'],'thumb'=>$_GPC['thumb'], 'video'=>$_GPC['video'],'status' => intval($_GPC['status']));

			if (empty($data['goods_id'])) {
				show_json(0, '指定商品不能为空！');
			}

			if (!empty($data['goods_id'])) {
				$goodsid = $data['goods_id'];
				$data['goods_id'] = is_array($data['goods_id']) ? implode(',', $data['goods_id']) : 0;
			}

			if (!empty($data['goods_id'])) {
				$goodsArr = explode(',', $data['goods_id']);

				foreach ($goodsArr as $k => $v) {
					$temp = pdo_fetch('select isverify from ' . tablename('ewei_shop_goods') . ' where uniacid = ' . $uniacid . ' and status = 1 and id = ' . $v . ' and deleted = 0 ');

					if ($temp['isverify'] == 2) {
						show_json(0, '指定商品存在核销商品不允许添加礼包');
					}
				}
			}
			//var_dump($data);exit;
			if (!empty($id)) {
				pdo_update('ewei_shop_look_buy', $data, array('id' => $id));
				plog('sale.giftbag.edit', '编辑边看边买 ID: ' . $id . ' <br/>边看边买名称: ' . $data['title']);
			}
			else {
			    $data['createtime'] = time();
				pdo_insert('ewei_shop_look_buy', $data);
				$id = pdo_insertid();
				plog('sale.giftbag.add', '添加边看边买 ID: ' . $id . '  <br/>边看边买名称: ' . $data['title']);
			}

			show_json(1, array('url' => webUrl('sale/lookbuy/edit', array('id' => $id))));
		}

		$item = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_look_buy') . ' WHERE uniacid = ' . $uniacid . ' and id = ' . $id . ' ');

		if (!empty($item['goods_id'])) {
			$goodsid = explode(',', $item['goods_id']);
			$goods = array();

			if ($goodsid) {
				foreach ($goodsid as $key => $value) {
					$goods[$key] = pdo_fetch('select id,title,thumb from ' . tablename('ewei_shop_goods') . ' where uniacid = ' . $uniacid . ' and status = 1 and id = ' . $value . ' and deleted = 0 ');
				}
			}
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

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_look_buy') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_look_buy', array('deleted' => 1, 'status' => 0), array('id' => $item['id']));
			plog('sale.gift.delete', '删除边看边买 ID: ' . $item['id'] . ' 边看边买名称: ' . $item['title'] . ' ');
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

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_look_buy') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_look_buy', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('sale.gift.edit', '修改边看边买状态<br/>ID: ' . $item['id'] . '<br/>边看斌买名称: ' . $item['title'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '上架' : '下架');
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

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_look_buy') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_look_buy', array('id' => $item['id']));
			plog('sale.gift.edit', '彻底删除边看边买<br/>ID: ' . $item['id'] . '<br/>边看边买名称: ' . $item['title']);
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

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_look_buy') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_look_buy', array('deleted' => 0), array('id' => $item['id']));
			plog('sale.gift.edit', '恢复边看边买<br/>ID: ' . $item['id'] . '<br/>边看边买名称: ' . $item['title']);
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

		$gift = pdo_fetch('select id from ' . tablename('ewei_shop_look_buy') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));

		if (empty($gift)) {
			show_json(0, array('message' => '参数错误'));
		}

		pdo_update('ewei_shop_look_buy', array($type => $value), array('id' => $id));
		show_json(1);
	}
}

?>
