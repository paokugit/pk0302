<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
class Credit_EweiShopV2Page extends WebPage 
{
	protected function main($type = "credit1") 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC["page"]));
		$psize = 20;
		$condition = " and log.uniacid=:uniacid and (log.module=:module1  or log.module=:module2) and log.credittype=:credittype and log.num!=:num";
		$condition1 = " and log.uniacid=:uniacid";
		$params = array( ":uniacid" => $_W["uniacid"], ":module1" => "ewei_shopv2", ":module2" => "ewei_shop", ":credittype" => $type,":num"=>0 );
		if( !empty($_GPC["keyword"]) ) 
		{
			$_GPC["keyword"] = trim($_GPC["keyword"]);
			$condition .= " and (m.realname like :keyword or m.nickname like :keyword or m.mobile like :keyword or u.username like :keyword)";
			$condition1 .= " and (m.realname like :keyword or m.nickname like :keyword or m.mobile like :keyword)";
			$params[":keyword"] = "%" . $_GPC["keyword"] . "%";
		}
		if( empty($starttime) || empty($endtime) ) 
		{
			$starttime = strtotime("-1 month");
			$endtime = time();
		}
		if( !empty($_GPC["time"]["start"]) && !empty($_GPC["time"]["end"]) ) 
		{
			$starttime = strtotime($_GPC["time"]["start"]);
			$endtime = strtotime($_GPC["time"]["end"]);
			$condition .= " AND log.createtime >= :starttime AND log.createtime <= :endtime ";
			$condition1 .= " AND log.createtime >= :starttime AND log.createtime <= :endtime ";
			$params[":starttime"] = $starttime;
			$params[":endtime"] = $endtime;
		}
		if( !empty($_GPC["level"]) ) 
		{
			$condition .= " and m.level=" . intval($_GPC["level"]);
			$condition1 .= " and m.level=" . intval($_GPC["level"]);
		}
		if( !empty($_GPC["groupid"]) ) 
		{
			$condition .= " and m.groupid=" . intval($_GPC["groupid"]);
			$condition1 .= " and m.groupid=" . intval($_GPC["groupid"]);
		}
		$search_flag = 0;
		if( $_GPC["groupid"] || $_GPC["level"] || $_GPC["keyword"] ) 
		{
			$search_flag = 1;
			if( $type == "credit1" ) 
			{
				$table1 = "select log.id,log.num,log.createtime,log.remark,log.credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,u.username,g.groupname,l.levelname from " . tablename("mc_credits_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " left join " . tablename("ewei_shop_member") . " m on m.uid=log.uid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where 1 " . $condition . " and log.uid<>0";
				$table2 = "select log.id,log.num,log.createtime,log.remark,log.credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,u.username,g.groupname,l.levelname  from " . tablename("ewei_shop_member_credit_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " left join " . tablename("ewei_shop_member") . " m on m.openid=log.openid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where 1 " . $condition . " and log.uid=0";
				$sql = "select * from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc";
			}
			else 
			{
				if( $type == "credit2" ) 
				{
					$condition .= " and log.uid<>0";
					$table1 = "select log.id,log.num,log.createtime,log.remark,log.credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,u.username,g.groupname,l.levelname  from " . tablename("mc_credits_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " left join " . tablename("ewei_shop_member") . " m on m.uid=log.uid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where 1 " . $condition;
					$table2 = "select log.id,log.money,log.createtime,log.title as remark,'credit2' as credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,log.rechargetype as username,g.groupname,l.levelname from " . tablename("ewei_shop_member_log") . "as log " . " inner join " . tablename("ewei_shop_member") . " m on m.openid=log.openid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where m.uid=0 and log.status=1 " . $condition1;
					$sql = "select * from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc";
				}elseif($type == "credit3" ){
                    $table1 = "select log.id,log.num,log.createtime,log.remark,log.credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,u.username,g.groupname,l.levelname from " . tablename("mc_credits_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " left join " . tablename("ewei_shop_member") . " m on m.uid=log.uid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where 1 " . $condition . " and log.uid<>0";
                    $table2 = "select log.id,log.num,log.createtime,log.remark,log.credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,u.username,g.groupname,l.levelname  from " . tablename("ewei_shop_member_credit_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " left join " . tablename("ewei_shop_member") . " m on m.openid=log.openid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where 1 " . $condition . " and log.uid=0";
                    $sql = "select * from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc";
                }elseif($type == "credit4" ){
                    $table1 = "select log.id,log.num,log.createtime,log.remark,log.credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,u.username,g.groupname,l.levelname from " . tablename("mc_credits_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " left join " . tablename("ewei_shop_member") . " m on m.uid=log.uid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where 1 " . $condition . " and log.uid<>0";
                    $table2 = "select log.id,log.num,log.createtime,log.remark,log.credittype,m.id as mid,m.openid, m.realname,m.nickname,m.avatar, m.mobile, m.weixin,u.username,g.groupname,l.levelname  from " . tablename("ewei_shop_member_credit_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " left join " . tablename("ewei_shop_member") . " m on m.openid=log.openid" . " left join " . tablename("ewei_shop_member_group") . " g on m.groupid=g.id" . " left join " . tablename("ewei_shop_member_level") . " l on m.level =l.id" . " where 1 " . $condition . " and log.uid=0";
                    $sql = "select * from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc";
                }elseif($type == "RVC" ){
                    $sql = ' select l.*,m.openid,m.nickname,m.avatar,m.mobile,m.realname from '.tablename('ewei_shop_member_rvclog').'l join '.tablename('ewei_shop_member').' m on m.openid = l.openid or m.id = l.user_id '.' where l.uniacid = :uniacid and l.status = 1 and l.createtime between :starttime and :endtime and (m.realname like :keyword or m.nickname like :keyword or m.mobile like :keyword) and m.level='.intval($_GPC["level"]).' and m.groupid=' . intval($_GPC["groupid"]) . ' order by l.createtime desc';
                    $params = [':uniacid'=>$_W['uniacid']];
                    $params[":starttime"] = strtotime($_GPC["time"]["start"]);
                    $params[":endtime"] = strtotime($_GPC["time"]["end"]);
                    $params[":keyword"] = "%" . $_GPC["keyword"] . "%";
                }
			}
		}
		else 
		{
            if($type == "RVC"){
                $sql = 'select l.*,m.openid,m.nickname,m.avatar,m.mobile,m.realname from '.tablename('ewei_shop_member_rvclog').'l join '.tablename('ewei_shop_member').'m on m.openid = l.openid or m.id = l.user_id where l.uniacid = :uniacid and l.status = 1 order by l.createtime desc';
                $params = [':uniacid'=>$_W['uniacid']];
            }else{
                $table1 = "select log.id,log.num,log.createtime,log.remark,log.credittype,u.username,log.uid,'xxx' as openid from " . tablename("mc_credits_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " where 1 " . $condition . " and log.uid<>0";
                $table2 = "select log.id,log.num,log.createtime,log.remark,log.credittype,u.username,'0' as uid,log.openid  from " . tablename("ewei_shop_member_credit_record") . " log " . " left join " . tablename("users") . " u on  log.operator=u.uid and log.operator<>0 and log.operator<>log.uid" . " where 1 " . $condition . " and log.uid=0";
                $sql = "select * from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc";
            }
		}
		if( empty($_GPC["export"]) ) 
		{
			$sql .= " LIMIT " . ($pindex - 1) * $psize . "," . $psize;
		}
		else 
		{
			ini_set("memory_limit", "-1");
		}
		$list = pdo_fetchall($sql, $params);

		if( $list && $search_flag == 0 ) 
		{
			foreach( $list as $key => $val ) 
			{
				$member = array( );
				if( $val["uid"] ) 
				{
					$member = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where uniacid=:uniacid and uid=:uid", array( ":uniacid" => $_W["uniacid"], ":uid" => $val["uid"] ));
				}
				else 
				{
					$member = pdo_fetch("select * from " . tablename("ewei_shop_member") . " where uniacid=:uniacid and openid=:openid", array( ":uniacid" => $_W["uniacid"], ":openid" => $val["openid"] ));
				}
		                $groupname = pdo_fetchcolumn("select groupname from " . tablename("ewei_shop_member_group") . " where uniacid=:uniacid and id=:id", array( ":uniacid" => $_W["uniacid"], ":id" => $member["groupid"] ));
		                $levelname = pdo_fetchcolumn("select levelname from " . tablename("ewei_shop_commission_level") . " where uniacid=:uniacid and id=:id", array( ":uniacid" => $_W["uniacid"], ":id" => $member["agentlevel"] ));
		                $list[$key]["mid"] = $member["id"];
		                $list[$key]["openid"] = $member["openid"];
		                $list[$key]["realname"] = $member["realname"];
		                $list[$key]["nickname"] = $member["nickname"];
		                $list[$key]["avatar"] = $member["avatar"];
		                $list[$key]["mobile"] = $member["mobile"];
		                $list[$key]["weixin"] = $member["weixin"];
		                $list[$key]["createtime"] = date("Y-m-d H:i", $val["createtime"]);
		                $list[$key]["groupname"] = (empty($groupname) ? "无分组" : $groupname);
		                $list[$key]["levelname"] = (empty($levelname) ? "普通会员" : $levelname);
		                if( $val["credittype"] == "credit1" ) {
		                    $list[$key]["credittype"] = "卡路里";
		                } else if( $val["credittype"] == "credit2" ) {
		                    $list[$key]["credittype"] = "余额";
		                } elseif($val['credittype'] == "credit3") {
		                    $list[$key]['credittype'] = "折扣宝";
		                } elseif($val['credittype'] == "credit4") {
		                    $list[$key]['credittype'] = "贡献值";
		                } else {
		                    $list[$key]['credittype'] = "RVC";
		                }
		                if( empty($val["username"]) )
		                {
		                    $list[$key]["username"] = "本人";
		                }
			}
		}
		if(!empty($_GPC['export'])){
            if( $_GPC["export"] == 1 ) {
                plog("finance.credit.credit1.export", "导出卡路里明细");
            }elseif ($_GPC["export"] == 3){
                plog("finance.credit.credit3.export", "导出折扣宝明细");
            }elseif ($_GPC["export"] == 4){
                plog("finance.credit.credit4.export", "导出贡献值明细");
            }elseif ($_GPC["export"] == 5){
                plog("finance.credit.RVC.export", "导出RVC明细");
            } else {
                plog("finance.credit.credit2.export", "导出余额明细");
            }
            foreach( $list as &$row )
            {
                //unset($row);
                $columns = array( );
                $columns[] = array( "title" => "类型", "field" => "credittype", "width" => 12 );
                $columns[] = array( "title" => "昵称", "field" => "nickname", "width" => 12 );
                $columns[] = array( "title" => "姓名", "field" => "realname", "width" => 12 );
                $columns[] = array( "title" => "手机号", "field" => "mobile", "width" => 12 );
                $columns[] = array( "title" => "会员等级", "field" => "levelname", "width" => 12 );
                $columns[] = array( "title" => "会员分组", "field" => "groupname", "width" => 12 );
                $columns[] = array( "title" => ($type == "credit1" ? "卡路里变化" : $type == "credit3" ? "折扣宝明细" : $type == "credit4" ? "贡献值明细" : $type == "RVC" ?  "RVC明细" :"余额变化"), "field" => "num", "width" => 12 );
                $columns[] = array( "title" => "时间", "field" => "createtime", "width" => 12 );
                $columns[] = array( "title" => "备注", "field" => "remark", "width" => 24 );
                $columns[] = array( "title" => "操作人", "field" => "username", "width" => 12 );
                m("excel")->export($list, array( "title" => ($type == "credit1" ? "卡路里变化" : $type == "credit3" ? "折扣宝明细" : $type == "credit4" ? "贡献值明细" : $type == "RVC" ?  "RVC明细" :"余额变化") . date("Y-m-d-H-i", time()), "columns" => $columns ));
            }
        }

		if( $type == "credit1" ) 
		{
			$allcount = pdo_fetch("select count(*) as ccc from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc limit 1", $params);
			$total = $allcount["ccc"];
		}
		else
		{
			if( $type == "credit2" )
			{
				$allcount = pdo_fetch("select count(*) as ccc from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc limit 1", $params);
				$total = $allcount["ccc"];
			}
            if( $type == "credit3" )
            {
                $allcount = pdo_fetch("select count(*) as ccc from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc limit 1", $params);
                $total = $allcount["ccc"];
            }
            if( $type == "credit4" )
            {
                $allcount = pdo_fetch("select count(*) as ccc from (" . $table1 . " UNION ALL " . $table2 . ") as main order by createtime desc limit 1", $params);
                $total = $allcount["ccc"];
            }
            if( $type == "RVC" )
            {
                $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_member_rvclog').'l join '.tablename('ewei_shop_member').'m on m.openid = l.openid or m.id = l.user_id where l.uniacid = "'.$_W['uniacid'].'" and l.status = 1 ');
            }
		}
		$pager = pagination2($total, $pindex, $psize);
		$groups = m("member")->getGroups();
		$levels = m("member")->getLevels();
		//var_dump($list);
		include($this->template("finance/credit"));
	}

	public function credit1() 
	{
		$this->main("credit1");
	}

	public function credit2() 
	{
		$this->main("credit2");
	}

    public function credit3()
    {
        $this->main("credit3");
    }

    public function credit4()
    {
        $this->main("credit4");
    }

    public function RVC()
    {
        $this->main("RVC");
    }

    public function rvc_check()
    {
        global $_GPC;
        $page = max(1,$_GPC['page']);
        $pageSize = 20;
        $pindex = ($page - 1) * $pageSize;
        $list = pdo_fetchall('select FROM_UNIXTIME(createtime," %Y-%m-%d") as time,count(1) as count,sum(amount) as amount,sum(totalprice) as total from '.tablename('ewei_shop_member_rvcorder').' where status = 1 group by time order by time desc limit '.$pindex.','.$pageSize);
        $total = count(pdo_fetchall('select * from '.tablename('ewei_shop_member_rvcorder').' where status = 1 group by FROM_UNIXTIME(createtime," %Y-%m-%d") '));
        foreach ($list as $key=>$val){
            $start = strtotime($val['time']);
            $end = strtotime("+1 day",strtotime($val['time']));
            $count = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_member_rvcorder').' where status = 1 and is_check = 1 and createtime between :start and :endtime ',[':start'=>$start,':endtime'=>$end]);
            $list[$key]['check'] = $val['count'] == $count ? 1 : 0;
        }
        $check = count(pdo_fetchall('select * from '.tablename('ewei_shop_member_rvcorder').' where status = 1 and is_check = 1 group by FROM_UNIXTIME(createtime," %Y-%m-%d") '));
        $check_money = pdo_fetchcolumn('select sum(totalprice) from '.tablename('ewei_shop_member_rvcorder').' where status = 1 and is_check = 1 ');
        $check_money = empty($check_money) ? 0 : $check_money;
        $out_check = count(pdo_fetchall('select * from '.tablename('ewei_shop_member_rvcorder').' where status = 1 and is_check = 0 group by FROM_UNIXTIME(createtime," %Y-%m-%d") '));
        $out_check_money = pdo_fetchcolumn('select sum(totalprice) from '.tablename('ewei_shop_member_rvcorder').' where status = 1 and is_check = 0 ');
        $out_check_money = empty($out_check_money) ? 0 : $out_check_money;
        $pager = pagination2($total, $page, $pageSize);
        include($this->template("finance/check"));
    }

    public function rvc_checklog()
    {
        global $_GPC;
        $time = $_GPC['time'];
        $start = strtotime($time);
        $end = strtotime("+1 day",strtotime($time));
        $page = max(1,$_GPC['page']);
        $pageSize = 20;
        $pindex = ($page - 1) * $pageSize;
        $list = pdo_fetchall('select r.*, m.nickname,m.avatar,m.mobile,m.realname,m.id as mid from '.tablename('ewei_shop_member_rvcorder').' r join '.tablename('ewei_shop_member').'m on m.openid = r.openid or r.user_id = m.id where r.status = 1 and r.createtime between :start and :endtime limit '.$pindex.','.$pageSize,[':start'=>$start,':endtime'=>$end]);
        $total = pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_member_rvcorder').' r join '.tablename('ewei_shop_member').'m on m.openid = r.openid or r.user_id = m.id where r.status = 1 and r.createtime between :start and :endtime ',[':start'=>$start,':endtime'=>$end]);
       	foreach($list as $key=>$val){
            $list[$key]['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            $list[$key]["check_name"] = $val['is_check'] == 1 ? "已对账" : "未对账";
        }
        if(!empty($_GPC['export'])){
             plog("finance.credit.checklog.export", "导出RVC对账明细");
            for ($i = 0; $i < $total ; $i++)
            {
                $columns = array( );
                $columns[] = array( "title" => "昵称", "field" => "nickname", "width" => 12 );
                $columns[] = array( "title" => "订单号", "field" => "ordersn", "width" => 12 );
                $columns[] = array( "title" => "姓名", "field" => "realname", "width" => 12 );
                $columns[] = array( "title" => "手机号", "field" => "mobile", "width" => 12 );
                $columns[] = array( "title" => "对账状态", "field" => "check_name", "width" => 12 );
                $columns[] = array( "title" => "时间", "field" => "createtime", "width" => 12 );
                $columns[] = array( "title" => "RVC总数", "field" => "amount", "width" => 24 );
                $columns[] = array( "title" => "RVC现金", "field" => "totalprice", "width" => 24 );
                m("excel")->export($list, array( "title" => "RVC对账明细" . date("Y-m-d-H-i", time()), "columns" => $columns ));
            }
        }
        $pager = pagination2($total, $page, $pageSize);
        include($this->template("finance/checklog"));
    }

    public function check()
    {
        global $_GPC;
        $time = $_GPC['time'];
        $start = strtotime($time);
        $end = strtotime("+1 day",strtotime($time));
        $list = pdo_fetchall('select * from '.tablename('ewei_shop_member_rvcorder').' where status = 1 and createtime between :start and :endtime ',[':start'=>$start,':endtime'=>$end]);
        foreach ($list as $item){
           pdo_update('ewei_shop_member_rvcorder',['is_check'=>1],['id'=>$item['id']]);
        }
        show_json(1, array( "url" => referer() ));
    }
}
?>