<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Choice_EweiShopV2Page extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid';
		$params = array(':uniacid' => $_W['uniacid']);

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and `name`  like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_choice') . (' WHERE 1 ' . $condition . '  ORDER BY id DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_choice') . (' WHERE 1 ' . $condition), $params);
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
		$uniacid = $_W['uniacid'];
		$id = intval($_GPC['id']);
		$cates = pdo_fetchall('select id,title from '.tablename('ewei_shop_icon').'where cate in (1,2) and status = 1');
		if (!empty($id)) {
			$item = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_choice') . ' WHERE id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id,':uniacid'=>$uniacid));
			if (!empty($item['goodsids'])) {
				$item['goodsids'] = trim($item['goodsids'], ',');
				$goods = pdo_fetchall('select id,title,thumb from ' . tablename('ewei_shop_goods') . (' where id in (' . $item['goodsids'] . ') and status=1 and deleted=0 and uniacid=' . $_W['uniacid'] . ' order by instr(\'' . $item['goodsids'] . '\',id)'));
			}
		}
		if ($_W['ispost']) {
			$title = trim($_GPC['title']);
			$displayorder = trim($_GPC['displayorder']);
			$icon_id = trim($_GPC['icon_id']);
			$content = $_GPC['content'];
			$goodsids = $_GPC['goodsids'];
			$status = $_GPC['status'];
			$thumb = $_GPC['thumb'];
			$image = $_GPC['image'];
			$data = array('title' => $title, 'goodsids' => implode(',', $goodsids), 'status' => $status ,'displayorder' => $displayorder ,'icon_id' => $icon_id , 'content' => $content , 'thumb'=>$thumb , 'image'=>$image);

			if (!empty($item)) {
				pdo_update('ewei_shop_choice', $data, array('id' => $item['id']));
				plog('goods.group.edit', '修改跑库精选 ID: ' . $id);
			}
			else {
				$data['uniacid'] = $_W['uniacid'];
				$data['createtime'] = time();
				pdo_insert('ewei_shop_choice', $data);
				$id = pdo_insertid();
				plog('goods.group.add', '添加跑库精选 ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('goods/choice')));
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

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_choice') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_choice', array('id' => $item['id']));
			plog('goods.group.delete', '删除跑库精选 ID: ' . $item['id'] . ' 标题: ' . $item['title'] . ' ');
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

        $items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_choice') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_choice', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
            plog('member.level.edit', '修改跑库精选状态<br/>ID: ' . $item['id'] . '<br/>标题: ' . $item['level_name'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '启用' : '禁用');
        }

        show_json(1, array('url' => referer()));
    }
}

?>
