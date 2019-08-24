<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
$auth = get_auth();
$result = auth_checkauth($auth);
/*if($result['status'] != 1 && $_GET['r']!='system.auth'){
    echo '<script>window.location.href="index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=system.auth"</script>';
    exit;
}*/
class Index_EweiShopV2Page extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		if (empty($_W['shopversion'])) {
			header('location:' . webUrl('shop'));
			exit();
		}

		$shop_data = m('common')->getSysset('shop');
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		if ($merch_plugin && $merch_data['is_openmerch']) {
			$is_openmerch = 1;
		}
		else {
			$is_openmerch = 0;
		}

		$hascommission = false;

		if (p('commission')) {
			$hascommission = 0 < intval($_W['shopset']['commission']['level']);
		}

		$ordercol = 6;
		if (cv('goods') && cv('order')) {
			$ordercol = 6;
		}
		else {
			if (cv('goods') && !cv('order')) {
				$ordercol = 12;
			}
			else {
				if (cv('order') && !cv('goods')) {
					$ordercol = 12;
				}
				else {
					$ordercol = 0;
				}
			}
		}

		$pluginnum = m('plugin')->getCount();
		$no_left = true;
		$member = pdo_fetchall(' select credit2 from '.tablename('ewei_shop_member').' where credit2 > 0 and uniacid = "'.$_W['uniacid'].'"');
		$credit2_all = array_sum(array_map(create_function('$val','return $val["credit2"];'),$member));
		$ceshi = pdo_fetchall(' select credit2 from '.tablename('ewei_shop_member').' where credit2 > 0 and uniacid = "'.$_W['uniacid'].'" and id in (12,15,44,83,89,90,1590,4164,27925,41683)');
		$credit2 = array_sum(array_map(create_function('$val','return $val["credit2"];'),$ceshi));
		$withdraw = $this->todayinfo();
		//$withdraw = m('finance')->todayinfo();
		include $this->template();
	}

	public function searchlist()
	{
		global $_W;
		global $_GPC;
		$return_arr = array();
		$menu = m('system')->getSubMenus(true, true);
		$keyword = trim($_GPC['keyword']);
		if (empty($keyword) || empty($menu)) {
			show_json(1, array('menu' => $return_arr));
		}

		foreach ($menu as $index => $item) {
			if (strexists($item['title'], $keyword) || strexists($item['desc'], $keyword) || strexists($item['keywords'], $keyword) || strexists($item['topsubtitle'], $keyword)) {
				if (cv($item['route'])) {
					$return_arr[] = $item;
				}
			}
		}

		show_json(1, array('menu' => $return_arr));
	}

	public function search()
	{
		global $_W;
		global $_GPC;
		$keyword = trim($_GPC['keyword']);
		$list = array();
		$history = $_GPC['history_search'];

		if (empty($history)) {
			$history = array();
		}
		else {
			$history = htmlspecialchars_decode($history);
			$history = json_decode($history, true);
		}

		if (!empty($keyword)) {
			$submenu = m('system')->getSubMenus(true, true);

			if (!empty($submenu)) {
				foreach ($submenu as $index => $submenu_item) {
					$top = $submenu_item['top'];
					if (strexists($submenu_item['title'], $keyword) || strexists($submenu_item['desc'], $keyword) || strexists($submenu_item['keywords'], $keyword) || strexists($submenu_item['topsubtitle'], $keyword)) {
						if (cv($submenu_item['route'])) {
							if (!is_array($list[$top])) {
								$title = (!empty($submenu_item['topsubtitle']) ? $submenu_item['topsubtitle'] : $submenu_item['title']);

								if (strexists($title, $keyword)) {
									$title = str_replace($keyword, '<b>' . $keyword . '</b>', $title);
								}

								$list[$top] = array('title' => $title,'items' => array());
							}

							if (strexists($submenu_item['title'], $keyword)) {
								$submenu_item['title'] = str_replace($keyword, '<b>' . $keyword . '</b>', $submenu_item['title']);
							}

							if (strexists($submenu_item['desc'], $keyword)) {
								$submenu_item['desc'] = str_replace($keyword, '<b>' . $keyword . '</b>', $submenu_item['desc']);
							}

							$list[$top]['items'][] = $submenu_item;
						}
					}
				}
			}

			if (empty($history)) {
				$history_new = array($keyword);
			}
			else {
				$history_new = $history;

				foreach ($history_new as $index => $key) {
					if ($key == $keyword) {
						unset($history_new[$index]);
					}
				}

				$history_new = array_merge(array($keyword), $history_new);
				$history_new = array_slice($history_new, 0, 20);
			}

			isetcookie('history_search', json_encode($history_new), 7 * 86400);
			$history = $history_new;
		}

		include $this->template();
	}

	public function clearhistory()
	{
		global $_W;
		global $_GPC;

		if ($_W['ispost']) {
			$type = intval($_GPC['type']);

			if (empty($type)) {
				isetcookie('history_url', '', -7 * 86400);
			}
			else {
				isetcookie('history_search', '', -7 * 86400);
			}
		}

		show_json(1);
	}

	public function switchversion()
	{
		global $_W;
		global $_GPC;
		$route = trim($_GPC['route']);
		$id = intval($_GPC['id']);
		$set = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_version') . ' WHERE uid=:uid AND `type`=0', array(':uid' => $_W['uid']));
		$data = array('version' => !empty($_W['shopversion']) ? 0 : 1);

		if (empty($set)) {
			$data['uid'] = $_W['uid'];
			pdo_insert('ewei_shop_version', $data);
		}
		else {
			pdo_update('ewei_shop_version', $data, array('id' => $set['id']));
		}

		$params = array();

		if (!empty($id)) {
			$params['id'] = $id;
		}

		load()->model('cache');
		cache_clean();
		cache_build_template();
		header('location: ' . webUrl($route, $params));
		exit();
	}
	
	
	/**
	 * 获取今日提现信息
	 */
	public function todayinfo(){
	    //获取今天的开始结束时间
		$startoday = strtotime(date('Y-m-d'));
		$endtoday = $startoday+3600*24;
		//今天余额提现的已处理金额
		$ssql = pdo_fetch("SELECT sum(money) as wmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and title='余额提现' and draw_type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		//今天余额提现的待处理金额
		$wsql = pdo_fetch("SELECT sum(money) as wmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and title='余额提现' and draw_type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$data['swmoney'] = $ssql['wmoney']?$ssql['wmoney']:0;
		$data['wwmoney'] = $wsql['wmoney']?$wsql['wmoney']:0;
		//今日余额提现的已处理的条数
		$cssql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and title='余额提现' and draw_type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		//今天余额提现的待处理的条数   S是已处理   W是待处理
		$cwsql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and title='余额提现' and draw_type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$data['scount'] = $cssql['cwmoney']?$cssql['cwmoney']:0;
		$data['wcount'] = $cwsql['cwmoney']?$cwsql['cwmoney']:0;
		//今天余额提现的已处理人数
		$rcssql = pdo_fetch("SELECT count(distinct openid) as rcwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and title='余额提现' and draw_type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		// 今天余额提现的待处理的人数
		$rcwsql = pdo_fetch("SELECT count(distinct openid) as rcwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and title='余额提现' and draw_type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$data['srcount'] = $rcssql['rcwmoney']?$rcssql['rcwmoney']:0;
		$data['wsrcount'] = $rcwsql['rcwmoney']?$rcwsql['rcwmoney']:0;

		//折扣宝提现
		$zssql = pdo_fetch("SELECT sum(money) as wmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and title='折扣宝提现' and draw_type=2", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$zwsql = pdo_fetch("SELECT sum(money) as wmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and title='折扣宝提现' and draw_type=2", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$zdata['swmoney'] = $zssql['wmoney']?$zssql['wmoney']:0;
		$zdata['wwmoney'] = $zwsql['wmoney']?$zwsql['wmoney']:0;
		$zcssql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and title='折扣宝提现' and draw_type=2", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$zcwsql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and title='折扣宝提现' and draw_type=2", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$zdata['scount'] = $zcssql['cwmoney']?$zcssql['cwmoney']:0;
		$zdata['wcount'] = $zcwsql['cwmoney']?$zcwsql['cwmoney']:0;
		$zrcssql = pdo_fetch("SELECT count(distinct openid) as rcwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and title='折扣宝提现' and draw_type=2", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$zrcwsql = pdo_fetch("SELECT count(distinct openid) as rcwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and title='折扣宝提现' and draw_type=2", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$zdata['srcount'] = $zrcssql['rcwmoney']?$zrcssql['rcwmoney']:0;
		$zdata['wsrcount'] = $zrcwsql['rcwmoney']?$zrcwsql['rcwmoney']:0;

		//商户收款码提现
		$sssql = pdo_fetch("SELECT sum(realprice) as wmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=3 and type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$swsql = pdo_fetch("SELECT sum(realprice) as wmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=0 and type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$sdata['swmoney'] = $sssql['wmoney']?$sssql['wmoney']:0;
		$sdata['wwmoney'] = $swsql['wmoney']?$swsql['wmoney']:0;
		$scssql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=3 and type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$scwsql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=0 and type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$sdata['scount'] = $scssql['cwmoney']?$scssql['cwmoney']:0;
		$sdata['wcount'] = $scwsql['cwmoney']?$scwsql['cwmoney']:0;
		$srcssql = pdo_fetch("SELECT count(distinct merchid) as rcwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=3 and type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$srcwsql = pdo_fetch("SELECT count(distinct merchid) as rcwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=0 and type=1", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$sdata['srcount'] = $srcssql['rcwmoney']?$srcssql['rcwmoney']:0;
		$sdata['wsrcount'] = $srcwsql['rcwmoney']?$srcwsql['rcwmoney']:0;


		//个人收款码提现
		$gzssql = pdo_fetch("SELECT sum(money) as wmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and draw_type=3", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$gzwsql = pdo_fetch("SELECT sum(money) as wmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and draw_type=3", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$gzdata['swmoney'] = $gzssql['wmoney']?$gzssql['wmoney']:0;
		$gzdata['wwmoney'] = $gzwsql['wmoney']?$gzwsql['wmoney']:0;
		$gzcssql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and draw_type=3", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$gzcwsql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and draw_type=3", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$gzdata['scount'] = $gzcssql['cwmoney']?$gzcssql['cwmoney']:0;
		$gzdata['wcount'] = $gzcwsql['cwmoney']?$gzcwsql['cwmoney']:0;
		$gzrcssql = pdo_fetch("SELECT count(distinct openid) as rcwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=1 and draw_type=3", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$gzrcwsql = pdo_fetch("SELECT count(distinct openid) as rcwmoney FROM " . tablename("ewei_shop_member_log") . " WHERE createtime >=:startoday AND createtime<=:endtoday AND status=0 and draw_type=3", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$gzdata['srcount'] = $gzrcssql['rcwmoney']?$gzrcssql['rcwmoney']:0;
		$gzdata['wsrcount'] = $gzrcwsql['rcwmoney']?$gzrcwsql['rcwmoney']:0;


		//商户店铺收益提现
		$hsssql = pdo_fetch("SELECT sum(realprice) as wmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=3 and type=0", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$hswsql = pdo_fetch("SELECT sum(realprice) as wmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=0 and type=0", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$hsdata['swmoney'] = $hsssql['wmoney']?$hsssql['wmoney']:0;
		$hsdata['wwmoney'] = $hswsql['wmoney']?$hswsql['wmoney']:0;
		$hscssql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=3 and type=0", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$hscwsql = pdo_fetch("SELECT count(*) as cwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=0 and type=0", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$hsdata['scount'] = $hscssql['cwmoney']?$hscssql['cwmoney']:0;
		$hsdata['wcount'] = $hscwsql['cwmoney']?$hscwsql['cwmoney']:0;
		$hsrcssql = pdo_fetch("SELECT count(distinct merchid) as rcwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=3 and type=0", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$hsrcwsql = pdo_fetch("SELECT count(distinct merchid) as rcwmoney FROM " . tablename("ewei_shop_merch_bill") . " WHERE applytime >=:startoday AND applytime<=:endtoday AND status=0 and type=0", array( ":startoday" => $startoday, ":endtoday" => $endtoday));
		$hsdata['srcount'] = $hsrcssql['rcwmoney']?$hsrcssql['rcwmoney']:0;
		$hsdata['wsrcount'] = $hsrcwsql['rcwmoney']?$hsrcwsql['rcwmoney']:0;

		//总计今天已处理提现金额
		$allcount['countmoney'] = $data['swmoney']+$zdata['swmoney']+$sdata['swmoney']+$gzdata['swmoney']+$hsdata['swmoney'];
		//总计今天待处理提现金额
		$allcount['wcountmoney'] = $data['wwmoney']+$zdata['wwmoney']+$sdata['wwmoney']+$gzdata['wwmoney']+$hsdata['wwmoney'];

		//总计今天已处理提现申请条数
		$allcount['sumcount'] = $data['scount']+$zdata['scount']+$sdata['scount']+$gzdata['scount']+$hsdata['scount'];
		//总计今天待处理提现申请条数
		$allcount['wsumcount'] = $data['wcount']+$zdata['wcount']+$sdata['wcount']+$gzdata['wcount']+$hsdata['wcount'];

		//总计今天已处理提现申请人数
		$allcount['rsumcount'] = $data['srcount']+$zdata['srcount']+$sdata['srcount']+$gzdata['srcount']+$hsdata['srcount'];
        	//总计今天待处理提现申请人数
		$allcount['rwsumcount'] = $data['wsrcount']+$zdata['wsrcount']+$sdata['wsrcount']+$gzdata['wsrcount']+$hsdata['wsrcount'];
		return array('data'=>$data,'zdata'=>$zdata,'sdata'=>$sdata,'gzdata'=>$gzdata,'hsdata'=>$hsdata,'allcount'=>$allcount);
	}
}

?>
