<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Api_EweiShopV2Page extends WebPage
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

		if ($type == 'normal') {
			$condition .= ' and status = 1 ';
		} else if ($type == 'forbid') {
			$condition .= ' and status = 0 ';
		}

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' AND (mobile LIKE :title or company LIKE :title)';
			$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
		}

		$gifts = pdo_fetchall('SELECT * FROM ' . tablename('core_company') . "\r\n                    WHERE 1 " . $condition . ' ORDER BY id DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('core_company') . ' WHERE 1 ' . $condition . ' ', $params);
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
		$type = trim($_GPC['type']);
		$id = intval($_GPC['id']);

		if ($_W['ispost']) {

			$data = [
			    'uniacid' => $uniacid,
                'company' => trim($_GPC['company']),
                'mobile'=>trim($_GPC['mobile']),
                'principal'=>trim($_GPC['principal']),
                'apikey' => trim($_GPC['apikey']),
                'apisecret' => trim($_GPC['apisecret']),
                'status' => intval($_GPC['status']),
            ];

			if (!empty($id)) {
				pdo_update('core_company', $data, array('id' => $id));
				plog('sale.giftbag.edit', '编辑外接公司 ID: ' . $id . ' <br/>公司名称: ' . $data['company']);
			} else {
                $data['createtime'] = time();
				pdo_insert('core_company', $data);
				$id = pdo_insertid();
				plog('sale.giftbag.add', '添加外接公司 ID: ' . $id . '  <br/>公司名称: ' . $data['company']);
			}

			show_json(1, array('url' => webUrl('sysset/api/edit', array('type' => $type, 'id' => $id))));
		}

		$item = pdo_fetch('SELECT * FROM ' . tablename('core_company') . ' WHERE uniacid = ' . $uniacid . ' and id = ' . $id . ' ');

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

		$items = pdo_fetchall('SELECT id,company FROM ' . tablename('ewei_shop_gift_bag') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('ewei_shop_gift_bag', array('deleted' => 1, 'status' => 0), array('id' => $item['id']));
			plog('sale.gift.delete', '删除外接公司 ID: ' . $item['id'] . ' 公司名称: ' . $item['company'] . ' ');
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

		$items = pdo_fetchall('SELECT id,company FROM ' . tablename('core_company') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('core_company', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('sale.gift.edit', '修改外接公司<br/>ID: ' . $item['id'] . '<br/>公司名称: ' . $item['company'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '正常' : '禁止');
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

		$items = pdo_fetchall('SELECT id,company FROM ' . tablename('core_company') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('core_company', array('id' => $item['id']));
			plog('sale.gift.edit', '彻底删除外接公司<br/>ID: ' . $item['id'] . '<br/>公司名称: ' . $item['company']);
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

		$items = pdo_fetchall('SELECT id,company FROM ' . tablename('core_company') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('core_company', array('deleted' => 0), array('id' => $item['id']));
			plog('sale.gift.edit', '恢复公司<br/>ID: ' . $item['id'] . '<br/>公司名称: ' . $item['company']);
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

		if (!in_array($type, array('title'))) {
			show_json(0, array('message' => '参数错误'));
		}

		$gift = pdo_fetch('select id from ' . tablename('core_company') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));

		if (empty($gift)) {
			show_json(0, array('message' => '参数错误'));
		}

		pdo_update('core_company', array($type => $value), array('id' => $id));
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

    /**
     * 生成apikey
     */
	public function get(){
	    global $_W;
	    global $_GPC;
	    $key = random(16);
	    $mobile = $_GPC['mobile'];
	    if($mobile == ""){
	        show_json(0,"手机号不能为空");
        }
	    $uniacid = $_W['uniacid'];
	    if(pdo_exists('core_company',['apikey'=>$key,'uniacid'=>$uniacid])){
            $key = random(16);
        }
        $apisecret = md5(base64_encode($mobile.$key));
	    show_json(1,['apikey'=>$key,'apisecret'=>$apisecret]);
    }
}

?>
