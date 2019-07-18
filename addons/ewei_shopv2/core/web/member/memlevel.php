<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Memlevel_EweiShopV2Page extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$condition = ' and uniacid=:uniacid';
		$params = array(':uniacid' => $_W['uniacid']);

		if ($_GPC['status'] != '') {
			$condition .= ' and status=' . intval($_GPC['status']);
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and ( level_name like :levelname)';
			$params[':levelname'] = '%' . $_GPC['keyword'] . '%';
		}

		$others = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_memlevel') . (' WHERE 1 ' . $condition . ' ORDER BY id asc'), $params);

		foreach ($others as $key =>$item){
			$goods_id = iunserializer($item['goods_id']);
			foreach ($goods_id as $val){
                $others[$key]['goods_name'] .= pdo_getcolumn('ewei_shop_goods',['id'=>$val],'subtitle');
			}
		}
		$list = $others;

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
		$id = trim($_GPC['id']);

		$level = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_memlevel') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => intval($id)));
		if (!empty($level)) {
			$goodsids = iunserializer($level['goods_id']);
			if (!empty($goodsids)) {
				$goods = pdo_fetchall('SELECT id,uniacid,title,thumb FROM ' . tablename('ewei_shop_goods') . ' WHERE uniacid=:uniacid AND id IN (' . implode(',', $goodsids) . ')', array(':uniacid' => $_W['uniacid']));
			}
		}
		$piclist = iunserializer($level['thumb_url']);
		//array_unshift  在数组 前面加元素   array_push  在数组后面的加元素
		array_unshift($piclist,$level['thumb']);
		if ($_W['ispost']) {
			$status = intval($_GPC['status']);
			$data = array('uniacid' => $_W['uniacid'], 'level_name' => $_GPC['level_name'], 'title' => trim($_GPC['title']),'desc'=>$_GPC['level_name'] , 'price' => $_GPC['price'], 'createtime' => time(), 'status' => $status);
			$goodsids = iserializer($_GPC['goodsid']);
            if (is_array($_GPC['thumbs'])) {
                $thumbs = $_GPC['thumbs'];
                $thumb_url = array();
                foreach ($thumbs as $th) {
                    $thumb_url[] = trim($th);
                }
                $data['thumb'] = save_media($thumb_url[0]);
                unset($thumb_url[0]);
                $data['thumb_url'] = serialize(m('common')->array_images($thumb_url));
            }
			if (!empty($id)) {
				$data['goods_id'] = $goodsids;
				$updatecontent = '<br/>礼包名称: ' . $level['level_name'] . '->' . $data['level_name'] . ('<br/>短标题: ' . $level['title'] . '->' . $data['title'].'礼包价格:' . $level['price'] . '->' . $data['price']);
				pdo_update('ewei_shop_member_memlevel', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
				plog('member.level.edit', '修改会员礼包 ID: ' . $id . $updatecontent);
			}
			else {
				$data['goods_id'] = $goodsids;
				pdo_insert('ewei_shop_member_memlevel', $data);
				$id = pdo_insertid();
				plog('member.memlevel.add', '添加会员礼包 ID: ' . $id);
			}
			show_json(1, array('url' => webUrl('member/memlevel')));
		}

		$level_array = array();
		$i = 0;

		while ($i < 101) {
			$level_array[$i] = $i;
			++$i;
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

		$items = pdo_fetchall('SELECT id,level_name FROM ' . tablename('ewei_shop_member_memlevel') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_member_memlevel', array('id' => $item['id']));
			plog('member.level.delete', '删除等级 ID: ' . $item['id'] . ' 标题: ' . $item['level_name'] . ' ');
		}

		show_json(1, array('url' => referer()));
	}

	public function enabled()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,level_name FROM ' . tablename('ewei_shop_member_memlevel') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_member_memlevel', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('member.level.edit', '修改会员等级状态<br/>ID: ' . $item['id'] . '<br/>标题: ' . $item['level_name'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '启用' : '禁用');
		}

		show_json(1, array('url' => referer()));
	}
}

?>
