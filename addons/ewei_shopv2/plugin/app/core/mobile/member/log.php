<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class Log_EweiShopV2Page extends AppMobilePage
{
	public function get_list()
	{
		global $_W;
		global $_GPC;
		$type = intval($_GPC['type']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$apply_type = array(0 => '微信钱包', 2 => '支付宝', 3 => '银行卡');
		$condition = ' and openid=:openid and uniacid=:uniacid and type=:type';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':type' => intval($_GPC['type']));
		$list = pdo_fetchall('select * from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition . ' order by createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition), $params);
		$newList = array();
		if (is_array($list) && !empty($list)) {
			foreach ($list as $row) {
				$newList[] = array('id' => $row['id'], 'type' => $row['type'], 'money' => $row['money'], 'typestr' => $apply_type[$row['applytype']], 'status' => $row['status'], 'deductionmoney' => $row['deductionmoney'], 'realmoney' => $row['realmoney'], 'rechargetype' => $row['rechargetype'], 'createtime' => date('Y-m-d H:i', $row['createtime']));
			}
		}

		app_json(array('list' => $newList, 'total' => $total, 'pagesize' => $psize, 'page' => $pindex, 'type' => $type, 'isopen' => $_W['shopset']['trade']['withdraw'], 'moneytext' => $_W['shopset']['trade']['moneytext']));
	}
//卡路里明细
    public function get_list2()
    {
        global $_W;
        global $_GPC;
        $type = intval($_GPC['type']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
       /* $apply_type = array(0 => '微信钱包', 2 => '支付宝', 3 => '银行卡');
        $condition = ' and openid=:openid and uniacid=:uniacid and type=:type';
        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':type' => intval($_GPC['type']));
        $list = pdo_fetchall('select * from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition . ' order by createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition), $params);
        $newList = array();
        if (is_array($list) && !empty($list)) {
            foreach ($list as $row) {
                $newList[] = array('id' => $row['id'], 'type' => $row['type'], 'money' => $row['money'], 'typestr' => $apply_type[$row['applytype']], 'status' => $row['status'], 'deductionmoney' => $row['deductionmoney'], 'realmoney' => $row['realmoney'], 'rechargetype' => $row['rechargetype'], 'createtime' => date('Y-m-d H:i', $row['createtime']));
            }
        }*/

        $addwhere='';

        if (empty($type)){

		}elseif ($type==1){
		   //好友助力
            $addwhere.=" and remark like '%好友%'";
            $addwhere.=" or remark like '%邀请%'";
        }elseif ($type==2){
            $addwhere.=" and remark like '%签到%'";
        }elseif ($type==3){
            //步数兑换
            $addwhere.=" and remark like '%步数%'";
        }elseif ($type==4){
            //订单消费
            $addwhere.=" and remark like '%消费%'";
        }


        $condition = " and openid=:openid and uniacid=:uniacid and credittype=:credittype and module=:module   ".$addwhere;
        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':credittype' => 'credit1', ':module' => 'ewei_shopv2');

        $list = pdo_fetchall('select createtime,remark,num from ' . tablename('mc_credits_record') . ' where 1 ' . $condition . ' order by createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
       // $total = pdo_fetchcolumn('select count(*) from ' . tablename('mc_credits_record') . ' where 1 ' . $condition, $params);
        $total=100;
        foreach ($list as &$row) {
            $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
            $row['type']=0;
            if(mb_substr($row['remark'],0,4) == "跑库购物"){
                $row['remark'] = "商城购物";
            }
        }
        unset($row);
        $newList=$list;

        app_json(array('list' => $newList, 'total' => $total, 'pagesize' => $psize, 'page' => $pindex, 'type' => $type, 'isopen' => $_W['shopset']['trade']['withdraw'], 'moneytext' => $_W['shopset']['trade']['moneytext']));
    }

    /**
     * 会员资金信息首页
     */
    public function member_money()
    {
        global $_W;
        global $_GPC;
        $member = $this->member;
        
        $data['id'] = $member['id'];
        $data['openid'] = $member['openid'];
        $data['credit2'] = $member['credit2'];//账户余额
        $data['frozen_credit2']=$member["frozen_credit2"];
        //已经提现
        $sql = "select ifnull(sum(money),0) from ".tablename('ewei_shop_member_log')." where openid=:openid and type=1 and status = 1";
        $params = array(':openid' => $_W['openid']);
        $data['balance_total'] = pdo_fetchcolumn($sql, $params);//成功提现金额

        //累计收入
        $comesql = "select ifnull(sum(money),0) from ".tablename('ewei_shop_member_log')." where openid=:openid and type=3 and status = 1";
        $comeparams = array(':openid' => $_W['openid']);
        $data['come_total'] = pdo_fetchcolumn($comesql, $comeparams);//累计推荐收入

        app_json(array('info' => $data));
    }

    public function money_log(){
        global $_W;
        global $_GPC;
        $type = intval($_GPC['type']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $apply_type = array(0 => '微信钱包', 2 => '支付宝', 3 => '银行卡');
        if($_GPC['type']==1){// 收入
            $condition = ' and openid=:openid and type in (0,3)';
        }else{// 支出
            $condition = ' and openid=:openid and type in (1,2) and (title="余额提现" or title="小程序商城消费")';
        }
        $params = array( ':openid' => $_W['openid']);
        $list = pdo_fetchall('select * from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition . ' order by createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_log') . (' where 1 ' . $condition), $params);
        $newList = array();
        if (is_array($list) && !empty($list)) {
            foreach ($list as $row) {
                if($row['type'] == 1){
                    $row['money'] = -$row['money'];
                    $row['realmoney'] = -$row['realmoney'];
                }
                $newList[] = array('id' => $row['id'], 'title'=>$row['title'],'type' => $row['type'], 'money' => $row['money'], 'typestr' => $apply_type[$row['applytype']], 'status' => $row['status'], 'deductionmoney' => $row['deductionmoney'], 'realmoney' => $row['realmoney'], 'rechargetype' => $row['rechargetype'], 'createtime' => date('Y-m-d H:i', $row['createtime']),'refuse_reason'=>$row["refuse_reason"]);
            }
        }
        app_json(array('list' => $newList, 'total' => $total, 'pagesize' => $psize, 'page' => $pindex, 'type' => $type, 'isopen' => $_W['shopset']['trade']['withdraw'], 'moneytext' => $_W['shopset']['trade']['moneytext']));
    }

}

?>
