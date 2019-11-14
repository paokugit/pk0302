<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class Notice_EweiShopV2Page extends AppMobilePage
{
	public function get_list()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and `uniacid` =:uniacid and status=1';
		$params = array(':uniacid' => $_W['uniacid']);
		$sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_notice') . (' where 1 ' . $condition);
		$total = pdo_fetchcolumn($sql, $params);
		$sql = 'SELECT * FROM ' . tablename('ewei_shop_notice') . ' where 1 ' . $condition . ' ORDER BY displayorder desc,id desc LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		foreach ($list as $key => &$row) {
			$row['createtime'] = $this->transform_time($row['createtime']);
			$row['thumb'] = empty($row['thumb']) ? tomedia($_W['shopset']['shop']['logo']) : tomedia($row['thumb']);
			$zan = pdo_getcolumn('ewei_shop_notice_log',['openid'=>$_GPC['openid'],'notice_id'=>$row['id']],'status');
			$row['is_zan'] = isset($zan) && $zan == 1 ? 1 : 0;
		}
		unset($row);
		$log = pdo_fetchall('select * from '.tablename('ewei_shop_notice_log').' where openid="'.$_GPC['openid'].'" and uniacid="'.$_W['uniacid'].'"');
		$email = pdo_fetchall('select * from '.tablename('ewei_shop_email').' where openid="'.$_GPC['openid'].'" and num=0 and uniacid="'.$_W['uniacid'].'"');
		app_json(array('list' => $list, 'pagesize' => $psize, 'total' => $total,'notice'=>bcsub($total,count($log)),'email'=>count($email)));
	}

	public function detail()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$merchid = intval($_GPC['merchid']);
		$merch_plugin = p('merch');
		if ($merch_plugin && !empty($merchid)) {
			$notice = pdo_fetch('select * from ' . tablename('ewei_shop_merch_notice') . ' where id=:id and uniacid=:uniacid and merchid=:merchid and status=1', array(':id' => $id, ':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
		}
		else {
			$notice = pdo_fetch('select * from ' . tablename('ewei_shop_notice') . ' where id=:id and uniacid=:uniacid and status=1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
			//浏览下详情 加一次点击浏览次数
			pdo_update('ewei_shop_notice',['click_num'=>bcadd($notice['click_num'],1)],['id'=>$id]);
		}
		app_json(array(
			'notice' => array('title' => $notice['title'], 'createtime' => date('Y-m-d H:i', $notice['createtime']), 'detail' => $notice['detail'])
		));
	}

	/**
	 * 点赞接口
	 */
	public function zan()
	{
		global $_W;
		global $_GPC;
		$openid = $_GPC['openid'];
		$uniacid = $_W['uniacid'];
		//$status = $_GPC['status'] == 1 ? 0 :1;
		$status = $_GPC['status'];
		$id = $_GPC['id'];
		if($id == "" || $openid == "" || $status == ""){
			show_json(0,"请完善参数信息");
		}
		if(pdo_exists('ewei_shop_notice_log',['openid'=>$openid,'uniacid'=>$uniacid,'notice_id'=>$id])){
			pdo_update('ewei_shop_notice_log',['status'=>$status],['openid'=>$openid,'uniacid'=>$uniacid,'notice_id'=>$id]);
		}else{
			pdo_insert('ewei_shop_notice_log',['openid'=>$openid,'uniacid'=>$uniacid,'notice_id'=>$id,'createtime'=>time()]);
		}
		$msg = $status == 0 ? "取消点赞成功" : "点赞成功";
		$notice = pdo_fetchall('select * from '.tablename('ewei_shop_notice').' where `uniacid` ="'.$uniacid.'" and status=1');
		$log = pdo_fetchall('select * from '.tablename('ewei_shop_notice_log').' where openid="'.$_GPC['openid'].'" and uniacid="'.$_W['uniacid'].'"');
		show_json(1,['msg'=>$msg,'notice'=>bcsub(count($notice),count($log))]);
	}

	/**
	 * 私信助手
	 */
	public function email()
	{
		global $_GPC;
		global $_W;
		$uniacid = $_W['uniacid'];
		$openid = $_GPC['openid'];
		//查用户信息
		$member = pdo_get('ewei_shop_member',['openid'=>$openid]);
		//查用户的私信的全部
		$list = pdo_fetchall('select * from '.tablename('ewei_shop_email').' where uniacid = "'.$uniacid.'" and openid="'.$openid.'"');
		//如果没 私信  也就是后台没发 就是第一次进入
		if(count($list) <= 0){
			//加入第一次进去的欢迎语
			$id = $this->addlog($openid,$member['agentlevel']);
			$data = pdo_get('ewei_shop_email',['id'=>$id]);
			//array_push  给尾部加元素   array_unshift  给头部加元素  可以加字符串 也可以加数组
			array_unshift($list,$data);
		}else{
			//如果有信息  也就是后台又发私信  但计算点击数量
			$num = array_sum(array_map(create_function('$val', 'return $val["num"];'), $list));
			//如果点击数总和  小于等于0  第一次进入私信页面  也加入欢迎语
			if($num <= 0){
				$id= $this->addlog($openid,$member['agentlevel']);
				$data = pdo_get('ewei_shop_email',['id'=>$id]);
				array_unshift($list,$data);
			}
		}
		//如果总数大于0  也就是不是第一次进入私信页面  那么把他的私信所有的浏览数加1
		foreach ($list as $key=>$item){
			$item_num = bcadd($item['num'],1);
			pdo_update('ewei_shop_email',['num'=>$item_num,'updatetime'=>time(),'status'=>1],['id'=>$item['id']]);
			$list[$key]['createtime'] = $this->transform_time($item['createtime']);
		}
		$notice = pdo_fetchall('select * from '.tablename('ewei_shop_notice').' where `uniacid` ="'.$uniacid.'" and status=1');
		$log = pdo_fetchall('select * from '.tablename('ewei_shop_notice_log').' where openid="'.$_GPC['openid'].'" and uniacid="'.$_W['uniacid'].'"');
		$email = pdo_fetchall('select * from '.tablename('ewei_shop_email').' where openid="'.$_GPC['openid'].'" and num=0 and uniacid="'.$_W['uniacid'].'"');
		show_json(1,['list'=>$list,'notice'=>bcsub(count($notice),count($log)),'email'=>count($email)]);
	}

	/**
	 *  第一次进入私信页面  加入欢迎语
	 * @param $openid
	 * @param $level
	 * @return array
	 */
	public function addlog($openid,$level){
		global $_W;
		$data = [
			'uniacid'=>$_W['uniacid'],
			'openid'=>$openid,
			'createtime'=>time(),
			'level'=>$level,
			'num'=>0,
			'content'=>"Hi，我是小库私信助手，今后我将推送关于你的个人消息，请多多关照，并及时查看哦~",
		];
		pdo_insert('ewei_shop_email',$data);
		return pdo_insertid();
	}

    /**
    * 时间处理
     * @param $time
     * @return false|string
     */
	public function transform_time($time)
	{
	        $sub_time = time() - $time;
	        $day = floor($sub_time/3600/24);
	        $hour = floor($sub_time/3600);
	        $minute = floor($sub_time/60);
	        if($hour >= 24 && $day >0 && $day <3){
	            return $day."天前";
	        }elseif($hour < 24 && $hour >= 1){
	            return $hour."小时前";
	        }elseif($minute < 60 && $minute > 0){
	            return $minute."分钟前";
	        }elseif($minute <= 0){
	            return "刚刚";
	        }else{
	            return date('Y-m-d H:i:s',$time);
	        }
	}
}

?>
