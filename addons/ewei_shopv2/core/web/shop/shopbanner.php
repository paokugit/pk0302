<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Shopbanner_EweiShopV2Page extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid ';
		$params = array(':uniacid' => $_W['uniacid']);

		if ($_GPC['enabled'] != '') {
			$condition .= ' and status =' . intval($_GPC['status']);
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and bannername  like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_icon_banner') . (' WHERE 1 ' . $condition . '  ORDER BY displayorder DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		foreach ($list as $key=>$item){
			$list[$key]['icon'] = pdo_getcolumn('ewei_shop_icon',['id'=>$item['icon_id']],'title');
		}
		$total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_icon_banner') . (' WHERE 1 ' . $condition), $params);
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
		$id = intval($_GPC['id']);

		//活动分类
        $icon = pdo_fetchall('select id,title from '.tablename('ewei_shop_icon').'where cate = 2 ');

		if ($_W['ispost']) {
			$data = array('uniacid' => $_W['uniacid'], 'bannername' => trim($_GPC['bannername']), 'icon_id' => intval($_GPC['icon_id']),'link' => trim($_GPC['link']), 'status' => intval($_GPC['status']), 'displayorder' => intval($_GPC['displayorder']), 'thumb' => save_media($_GPC['thumb']));

			if (!empty($id)) {
				pdo_update('ewei_shop_icon_banner', $data, array('id' => $id));
				plog('shop.shopbanner.edit', '修改banner ID: ' . $id);
			}
			else {
				pdo_insert('ewei_shop_icon_banner', $data);
				$id = pdo_insertid();
				plog('shop.shopbanner.add', '添加banner ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('shop/shopbanner')));
		}

		$item = pdo_fetch('select * from ' . tablename('ewei_shop_icon_banner') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
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

		$items = pdo_fetchall('SELECT id,bannername FROM ' . tablename('ewei_shop_icon_banner') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_icon_banner', array('id' => $item['id']));
			plog('shop.shopbanner.delete', '删除banner ID: ' . $item['id'] . ' 标题: ' . $item['bannername'] . ' ');
		}

		show_json(1, array('url' => referer()));
	}

	public function displayorder()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$displayorder = intval($_GPC['value']);
		$item = pdo_fetchall('SELECT id,bannername FROM ' . tablename('ewei_shop_icon_banner') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		if (!empty($item)) {
			pdo_update('ewei_shop_icon_banner', array('displayorder' => $displayorder), array('id' => $id));
			plog('shop.shopbanner.edit', '修改banner排序 ID: ' . $item['id'] . ' 标题: ' . $item['bannername'] . ' 排序: ' . $displayorder . ' ');
		}

		show_json(1);
	}

	public function enabled()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,bannername FROM ' . tablename('ewei_shop_icon_banner') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_icon_banner', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('shop.shopbanner.edit', '修改banner状态<br/>ID: ' . $item['id'] . '<br/>标题: ' . $item['bannername'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '显示' : '隐藏');
		}

		show_json(1, array('url' => referer()));
	}

	public function setswipe()
	{
		global $_W;
		global $_GPC;
		$shop = $_W['shopset']['shop'];
		$shop['bannerswipe'] = intval($_GPC['bannerswipe']);
		m('common')->updateSysset(array('shop' => $shop));
		plog('shop.banner.edit', '修改手机端广告轮播');
		show_json(1);
	}
}

?>
