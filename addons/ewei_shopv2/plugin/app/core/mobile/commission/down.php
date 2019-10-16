<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require __DIR__ . '/base.php';
class Down_EweiShopV2Page extends Base_EweiShopV2Page
{
	public function get_set()
	{
		global $_W;
		global $_GPC;
		$member = $this->model->getInfo($_W['openid']);
		$levelcount1 = $member['level1'];
		$levelcount2 = $member['level2'];
		$levelcount3 = $member['level3'];
		$level1 = $level2 = $level3 = 0;
		$levels = array();
		$level1 = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where agentid=:agentid and uniacid=:uniacid limit 1', array(':agentid' => $member['id'], ':uniacid' => $_W['uniacid']));
		$levels[0] = array('level' => 1, 'name' => $this->set['texts']['c1'], 'total' => $level1);

		if (2 <= $this->set['level']) {
			$levels[1] = array('level' => 2, 'name' => $this->set['texts']['c2'], 'total' => 0);

			if (0 < $levelcount1) {
				$levels[1]['total'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where agentid in( ' . implode(',', array_keys($member['level1_agentids'])) . ') and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
			}
		}

		if (3 <= $this->set['level']) {
			$levels[2] = array('level' => 3, 'name' => $this->set['texts']['c3'], 'total' => 0);

			if (0 < $levelcount2) {
				$levels[2]['total'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . ') and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
			}
		}

		$total = $level1 + $level2 + $level3;
		app_json(array('total' => $total, 'levels' => $levels, 'textdown' => $this->set['texts']['mydown'], 'textagent' => $this->set['texts']['agent'], 'textyuan' => $this->set['texts']['yuan']));
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$member = $this->model->getInfo($openid);
		$total_level = 0;
		$level = intval($_GPC['level']);
		(3 < $level || $level <= 0) && ($level = 1);
		$condition = '';
		$levelcount1 = $member['level1'];
		$levelcount2 = $member['level2'];
		$levelcount3 = $member['level3'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;

		if ($level == 1) {
			$condition = ' and agentid=' . $member['id'];
			$hasangent = true;
			$total_level = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where agentid=:agentid and uniacid=:uniacid limit 1', array(':agentid' => $member['id'], ':uniacid' => $_W['uniacid']));
		}
		else if ($level == 2) {
			if (empty($levelcount1)) {
				app_json(array(
				'list'     => array(),
				'total'    => 0,
				'pagesize' => $psize
				));
			}

			$condition = ' and agentid in( ' . implode(',', array_keys($member['level1_agentids'])) . ')';
			$hasangent = true;
			$total_level = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where agentid in( ' . implode(',', array_keys($member['level1_agentids'])) . ') and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
		}
		else {
			if ($level == 3) {
				if (empty($levelcount2)) {
					app_json(array(
						'list'     => array(),
						'total'    => 0,
						'pagesize' => $psize
					));
				}

				$condition = ' and agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . ')';
				$hasangent = true;
				$total_level = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . ') and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
			}
		}
		//按手机号   昵称  真实姓名搜索好友 
		if($_GPC['nickname']){
			$condition .= ' and (nickname like "%'.$_GPC['nickname'].'%" or mobile = "'.$_GPC['nickname'].'" or realname like "%'.$_GPC['nickname'].'%")';
		}
		$list = pdo_fetchall('select * from ' . tablename('ewei_shop_member') . ' where uniacid = ' . $_W['uniacid'] . (' ' . $condition . '  ORDER BY id desc,isagent desc limit ') . ($pindex - 1) * $psize . ',' . $psize);
		if (!is_array($list) || empty($list)) {
			$list = array();
		}

		foreach ($list as &$row) {
			$info = $this->model->getInfo($row['openid'], array('total'));
			$row['commission_total'] = $info['commission_total'];//累计佣金
			$row['agentcount'] = $this->getagentcount($row['openid']);
			$row['agenttime'] = date('Y-m-d H:i', $row['agenttime']);
			//获取会员等级
			$level = m("member")->agentlevel($row['openid']);
			$row['levelname'] = $level['levelname']?$level['levelname']:'普通会员';
			$ordercount = pdo_fetchcolumn('select count(id) from ' . tablename('ewei_shop_order') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $row['openid']));
			$row['ordercount'] = number_format(intval($ordercount), 0);
			$moneycount = pdo_fetchcolumn('select sum(og.realprice) from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id where o.openid=:openid  and o.status>=1 and o.uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $row['openid']));
			$row['moneycount'] = number_format(floatval($moneycount), 2);
			$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
		}
		unset($row);
		//总推荐
		//$allcount = m('member')->allAgentCount($member['id']);

		$agentcount = pdo_fetch("select * from " . tablename("ewei_shop_member_agentcount") . " where openid=:openid limit 1", array( ":openid" => $member['openid'] ));

		if($member['agentid']==1){
			$frommember = '跑库';
		}elseif($member['agentid']==0){
			$frommember = '-';
		}else{
			$fromMemberInfo = $this->model->getInfo($member['agentid']);
			$frommember = $fromMemberInfo['nickname'];
		}

		app_json(array('list' => $list, 'total' => $total_level,'frommember'=>$frommember, 'pagesize' => $psize,'agentcount' => $agentcount));
	}

	public function getagentcount($openid){
		$agentCountInfo = pdo_fetch("select * from " . tablename("ewei_shop_member_agentcount") . " where openid=:openid limit 1", array(":openid" => $openid ));
		if($agentCountInfo) return $agentCountInfo['agentcount'];
		return 0;

	}

    /**
     * 搜索好友
     */
	public function search(){
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$keyword = $_GPC['keywords'];
		$openid = $_GPC['openid'];
		$page = max(1,$_GPC['page']);
		if($keyword == "" || $openid == "" || $page == "" ){
			show_json(0,"参数不完整");
		}
		$pageSize = 10;
		$pindex = ($page - 1) * $pageSize;
		$member = pdo_get('ewei_shop_member',['uniacid'=>$uniacid,'openid'=>$openid]);
		$total = pdo_count('ewei_shop_member','uniacid = "'.$uniacid.'" and (mobile = "'.$keyword.'" or nickname like "%'.$keyword.'%" or realname like "%'.$keyword.'%")');
		$list = pdo_getall('ewei_shop_member','uniacid = "'.$uniacid.'" and (mobile = "'.$keyword.'" or nickname like "%'.$keyword.'%" or realname like "%'.$keyword.'%") order by id desc LIMIT '.$pindex.','.$pageSize,['id','openid','nickname','realname','mobile','createtime','avatar','agentid','agentlevel']);
		foreach ($list as $key => $item) {
			$list[$key]['agentnickname'] = $item['agentid'] == 0 ? "暂无上级" :pdo_getcolumn('ewei_shop_member',['uniacid'=>$uniacid,'id'=>$item['agentid']],'nickname');
			$list[$key]['is_push'] = $item['agentid'] == $member['id'] ? 1 : 0;
			$list[$key]['createtime'] = date('Y-m-d H:i',$item['createtime']);
			$list[$key]['agentname'] = $item['agentlevel'] == 0 ? "普通会员" : pdo_getcolumn('ewei_shop_commission_level',['id'=>$item['agentlevel'],'uniacid'=>$uniacid],'levelname');
		}
		show_json(1,['list'=>$list,'page'=>$page,'pageSize'=>$pageSize,'total'=>$total]);
	}
}

?>
