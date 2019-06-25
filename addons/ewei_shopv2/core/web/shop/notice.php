<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Notice_EweiShopV2Page extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid and iswxapp=0';
		$params = array(':uniacid' => $_W['uniacid']);

		if ($_GPC['status'] != '') {
			$condition .= ' and status=' . intval($_GPC['status']);
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and title  like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_notice') . (' WHERE 1 ' . $condition . '  ORDER BY displayorder DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_notice') . (' WHERE 1 ' . $condition), $params);
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

		if ($_W['ispost']) {
			$data = array('uniacid' => $_W['uniacid'], 'displayorder' => intval($_GPC['displayorder']), 'title' => trim($_GPC['title']), 'thumb' => save_media($_GPC['thumb']), 'link' => trim($_GPC['link']), 'detail' => m('common')->html_images_a($_GPC['detail']), 'status' => intval($_GPC['status']), 'createtime' => time());
			if (!empty($id)) {
				pdo_update('ewei_shop_notice', $data, array('id' => $id));
				plog('shop.notice.edit', '修改公告 ID: ' . $id);
			}
			else {
				pdo_insert('ewei_shop_notice', $data);
				$id = pdo_insertid();
				plog('shop.notice.add', '修改公告 ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('shop/notice')));
		}

		$notice = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_notice') . ' WHERE id =:id and uniacid=:uniacid and iswxapp=0 limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		include $this->template();
	}

	public function displayorder()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$displayorder = intval($_GPC['value']);
		$item = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_notice') . (' WHERE id in( ' . $id . ' ) and iswxapp=0 AND uniacid=') . $_W['uniacid']);

		if (!empty($item)) {
			pdo_update('ewei_shop_notice', array('displayorder' => $displayorder), array('id' => $id));
			plog('shop.notice.edit', '修改公告排序 ID: ' . $item['id'] . ' 标题: ' . $item['advname'] . ' 排序: ' . $displayorder . ' ');
		}

		show_json(1);
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_notice') . (' WHERE id in( ' . $id . ' ) and iswxapp=0 AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('ewei_shop_notice', array('id' => $item['id']));
			plog('shop.notice.delete', '删除公告 ID: ' . $item['id'] . ' 标题: ' . $item['title'] . ' ');
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

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_notice') . (' WHERE id in( ' . $id . ' ) and iswxapp=0 AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_notice', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('shop.notice.edit', '修改公告状态<br/>ID: ' . $item['id'] . '<br/>标题: ' . $item['title'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '显示' : '隐藏');
		}

		show_json(1, array('url' => referer()));
	}

	/**
	 * 私信发送
	 */
	public function email()
	{
		global $_W;
		global $_GPC;
		$id = $_GPC['id'];
		$list = pdo_get('ewei_shop_email',['id'=>$id]);
		if($_POST){
			$data = $_GPC;
			$data['uniacid'] = $_W['uniacid'];
			var_dump($data);exit;
			pdo_begin();
			try{
				if(!$id){
					pdo_insert('ewei_shop_email',$data);
				}else{
					pdo_update('ewei_shop_email',$data,['id'=>$id]);
				}
				pdo_commit();
			}catch(Exception $exception){
				pdo_rollback();
			}
		}
		include $this->template();
	}

	/**
	 * 私信发送记录
	 */
	public function log()
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$page = max(1,$_GPC['page']);
		$pageSize = 20;
		$psize = ($page - 1) * $pageSize;
		$condition = ' e.uniacid = "'.$uniacid.'" and delete = 0';
		if($_GPC['openid']){
			$condition .= ' AND e.openid = "'.$_GPC['openid'].'"';
		}
		if($_GPC['nickname']){
			$condition .= ' AND m.nickname = "'.$_GPC['nickname'].'"';
		}
		if($_GPC['mobile']){
			$condition .= ' AND m.mobile = "'.$_GPC['mobile'].'"';
		}
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('ewei_shop_email').' e join '.tablename('ewei_shop_member').' m on e.openid = m.openid'.' where 1 and '.$condition);
		$list = pdo_fetchall('select m.nickname,m.mobile,m.avatar,e.* from '.tablename('ewei_shop_email').' e join'.tablename('ewei_shop_member').' m on m.openid=e.openid'.' where 1 and '. $condition .' order by e.createtime desc LIMIT '.$psize.','.$pageSize);
		$pager = pagination2($total, $page, $pageSize);
		include $this->template();
	}

	/**
	 * 删除发送记录
	 */
	public function del()
	{
		global $_GPC;
		$id = $_GPC;
		$res = pdo_update('ewei_shop_email',['delete'=>4],['id'=>$id]);
		if($res){
			show_json(1,"已删除");
		}else{
			show_json(0,"删除失败");
		}
	}
}

?>
