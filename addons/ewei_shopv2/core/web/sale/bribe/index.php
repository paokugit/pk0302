<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage
{
	public function main(){
		$this->index();
	}
	/**
	 * 添加页面
	 */
	public function add()
	{
		$this->post();
	}

	/**
	 * 编辑页面
	 */
	public function edit()
	{
		$this->post();
	}

	/**
	 * 添加背景音乐  修改背景音乐
	 */
	public function post()
	{
		header('Access-Control-Allow-Origin:*');
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$type = trim($_GPC['type']);
		//判断提交方式  post才成功
		if ($_W['ispost']) {
			$data = [
				'uniacid'=>$_W['uniacid'],
				'merchid'=>$_W['merchmanage']['merchid'],
				'title'=>$_GPC['title'],
				'music'=>save_media($_GPC['music']),
				'created_at'=>time(),
			];
			if($id){
				pdo_update('ewei_shop_music',$data,['id'=>$id]);
				show_json(1,['url'=>webUrl('sale/bribe/edit',['type'=>$type,'id'=>$id])]);
			}else{
				pdo_insert('ewei_shop_music',$data);
				show_json(1,['url'=>webUrl('sale/bribe/add',['type'=>$type])]);
			}
		}else{
			$item = pdo_fetch(' select * from ' . tablename('ewei_shop_music') . ' where id=:id',[':id'=>$id]);
		}
		include $this->template();
	}

	/**
	 * 修改标题
	 */
	public function change()
	{
		header('Access-Control-Allow-Origin:*');
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$title = trim($_GPC['value']);
		if(!pdo_fetch('select * from ' .tablename('ewei_shop_music') . ' where id=:id',array(':id'=>$id))){
			show_json(0,'参数错误');
		}
		pdo_update('ewei_shop_music',['title'=>$title],['id'=>$id]);
		show_json(1);
	}

	/**
	 * 背景音乐列表
	 */
	public function index()
	{
		header('Access-Control-Allow-Origin:*');
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and uniacid=:uniacid';
		$params = array(':uniacid' => $uniacid);
		$type = trim($_GPC['type']);
		if ($type == 'ing') {
			$condition .= ' and status = 1 ';
		} else {
			if ($type == 'done') {
				$condition .= ' and status= 0 ';
			}
		}
		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' AND title LIKE :title';
			$params[':title'] = '%' . trim($_GPC['keyword']) . '%';
		}
		$packages = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_music') . "\r\n                    WHERE 1 " . $condition . ' ORDER BY id DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('ewei_shop_music') . ' WHERE 1 ' . $condition . ' ', $params);
		$pager = pagination2($total, $pindex, $psize);
		include $this->template();
	}

	/**
	 * 修改背景音乐状态
	 */
	public function delete(){
		header('Access-Control-Allow-Origin:*');
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}
		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_music') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
		foreach ($items as $item) {
			pdo_update('ewei_shop_music', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('sale.bribe.music', '修改背景音乐<br/>ID: ' . $item['id'] . '<br/>背景音乐名称: ' . $item['title'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '上架' : '下架');
		}
		show_json(1, array('url' => referer()));
	}

	/**
	 * 删除背景音乐
	 */
	public function delete1()
	{
		header('Access-Control-Allow-Origin:*');
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}
		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_music') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
		foreach ($items as $item) {
			pdo_delete('ewei_shop_music',array('id' => $item['id']));
			plog('sale.bribe.music', '删除背景音乐id<br/>ID: ' . $item['id'] . '<br/>背景音乐名称: ' . $item['title'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '上架' : '下架');
		}
		show_json(1, array('url' => referer()));
	}
}

?>
