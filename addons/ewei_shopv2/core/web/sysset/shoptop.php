<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
//fbb
class Shoptop_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and is_del=:is_del';
        $params = array(':is_del' => 0);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_goodtop') . (' WHERE 1 ' . $condition.' ORDER BY sort DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as $k=>$v){
            $good=pdo_get("ewei_shop_goods",array("id"=>$v["goodid"]));
            $list[$k]["goodname"]=$good["title"];
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_goodtop') . (' WHERE 1 ' . $condition), $params);
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
    
    
    //添加
    public function post(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            $goods_id=$_GPC['goodsid'];
            if (sizeof($goods_id)>1){
                show_json(0, "只可选择一件商品");
            }
            $main_target=trim($_GPC["main_target"]);
            if (mb_strlen($main_target, 'UTF8')>8){
                show_json(0,"主标的长度最长为8");
            }
            $substandard=trim($_GPC["substandard"]);
            if (mb_strlen($substandard, 'UTF8')>10){
                show_json(0,"副标的长度最长为10");
            }
            $day=date("Y-m-d");
            
            if ($id){
                //判断是否有该位置商品
                $g=pdo_fetch("select * from ".tablename("ewei_shop_goodtop")." where end_date>:end_date and is_del=0 and sort=:sort and id!=:id",array(":end_date"=>$day,':sort'=>$_GPC["sort"],':id'=>$id));
            }else {
                $g=pdo_fetch("select * from ".tablename("ewei_shop_goodtop")." where end_date>:end_date and is_del=0 and sort=:sort",array(":end_date"=>$day,':sort'=>$_GPC["sort"]));
            }
            if ($g){
                show_json(0,"该位置已有商品，请先结束再添加");
            }
            $goodid=$goods_id[0];
            $data = array( 'sort' => intval($_GPC['sort']), 'main_target' =>$main_target, 'substandard' =>$substandard,  'goodid' => $goodid, 'start_date' =>$_GPC["start_date"], 'end_date' => $_GPC["end_date"],'create_time'=>time());
            if (!empty($id)) {
                pdo_update('ewei_shop_goodtop', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_goodtop', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('sysset/shoptop')));
        }
        if ($id){
        $detail = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_goodtop') . ' WHERE id =:id  limit 1', array(':id' => $id));
        $goods=pdo_fetchall("select * from ".tablename("ewei_shop_goods")." where id=:id",array(":id"=>$detail["goodid"]));
        }
        include $this->template();
    }
    //删除
    public function delete(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        pdo_update("ewei_shop_goodtop",array("is_del"=>1),array("id"=>$id));
        
        show_json(1, array('url' => referer()));
        
    }
    //统计
    public function census(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        $detail=pdo_get("ewei_shop_goodtop",array("id"=>$id));
        //获取查看次数
        $viewcount=pdo_fetch("select count(*) as total from ".tablename("ewei_shop_goodview")." where goodid=:goodid and time>=:starttime and time<=:endtime",array(":goodid"=>$detail["goodid"],":starttime"=>$detail["start_date"],":endtime"=>$detail["end_date"]));
        
        //获取成交的订单
        $where="a.status>0 and a.status!=4 and o.goodsid=".$detail["goodid"]."  and a.paytime>=".strtotime($detail["start_date"])." and a.paytime<=".strtotime($detail["end_date"]);
        $success_order=pdo_fetch("select count(*) as total from ".tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid where ".$where);
        //未支付
        $where="a.status=0 and o.goodsid=".$detail["goodid"]."  and a.paytime>=".strtotime($detail["start_date"])." and a.paytime<=".strtotime($detail["end_date"]);
        $unpaid_order=pdo_fetch("select count(*) as total from ".tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid where ".$where);
        //今日订单金额
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where="a.status>0 and a.status!=4 and o.goodsid=".$detail["goodid"]."  and a.paytime>=".$beginToday." and a.paytime<=".$endToday;
        $today_money=pdo_fetch("select sum(a.price) as total from ".tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid where ".$where);
        //昨日订单金额
        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $where="a.status>0 and a.status!=4 and o.goodsid=".$detail["goodid"]."  and a.paytime>=".$beginToday." and a.paytime<=".$endYesterday;
        $yesterday_money=pdo_fetch("select sum(a.price) as total from ".tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid where ".$where);
        //累计成交额
        $where="a.status>0 and a.status!=4 and o.goodsid=".$detail["goodid"]."  and a.paytime>=".strtotime($detail["start_date"])." and a.paytime<=".strtotime($detail["end_date"]);
        $count_money=pdo_fetch("select sum(a.price) as total from ".tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid where ".$where);
        
        include $this->template();
        
    }
    //查看记录
    public function view(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        $detail=pdo_get("ewei_shop_goodtop",array("id"=>$id));
        //获取查看次数
       
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and goodid=:goodid and time>=:starttime and time<=:endtime';
        $params = array(":goodid"=>$detail["goodid"],":starttime"=>$detail["start_date"],":endtime"=>$detail["end_date"]);
        
        
        $view = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_goodview') . (' WHERE 1 ' . $condition.'  limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($view as $k=>$v){
            $member=pdo_get("ewei_shop_member",array("openid"=>$detail["openid"]));
            $view[$k]["nickname"]=$member["nickname"];
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_goodview') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        
        include $this->template();
    }
    
    //订单
    public function order(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        $detail=pdo_get("ewei_shop_goodtop",array("id"=>$id));
        $searchtime=$_GPC["searchtime"];
        
        $time=$_GPC["time"];
        if ($time["start"]&&$searchtime){
            
            $starttime=strtotime($time["start"]);
        }else{
            $starttime=time();
        }
        if ($time["end"]&&$searchtime){
           
            $endtime=strtotime($time["end"]);
        }else{
            $endtime=time();
            
        }
        
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
       if ($time&&$searchtime){
            
            $params = array(":goodid"=>$detail["goodid"],":starttime"=>strtotime($time["start"]),":endtime"=>strtotime($time["end"]));
            
       }else{
           $params = array(":goodid"=>$detail["goodid"],":starttime"=>strtotime($detail["start_date"]),":endtime"=>strtotime($detail["end_date"]));
           
         }
        
        //获取成交的订单
        $where="o.goodsid=:goodid  and a.createtime>=:starttime and a.createtime<=:endtime";
        
        $status=$_GPC["status"];
        if ($status){
            $params[":status"]=$status;
            $where.="  and a.status=:status";
        }
        
        $list= pdo_fetchall("select a.ordersn as ordersn, a.price as price,a.openid as openid ,a.status as status,a.paytime as paytime,a.createtime as createtime from ".tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid where ".$where."  limit ". ($pindex - 1) * $psize . ',' . $psize,$params);
//        var_dump($list);
//        var_dump($time);
        foreach ($list as $k=>$v){
            
            $list[$k]["createtime"]=date("Y-m-d H:i:s",$v["createtime"]);
            
            //获取用户
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["nickname"]=$member["nickname"];
            if ($v["status"]==0){
                $list[$k]["status_type"]="待支付";
            }elseif ($v["status"]==-1){
                $list[$k]["status_type"]="取消状态";
            }elseif ($v["status"]==1){
                $list[$k]["status_type"]="待发货";
            }elseif ($v["status"]==2){
                $list[$k]["status_type"]="待收货";
            }elseif ($v["status"]==3){
                $list[$k]["status_type"]="成功";
            }elseif ($v["status"]==4){
                $list[$k]["status_type"]="退款";
            }
        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' .tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid " . (' WHERE  ' . $where), $params);
        //订单金额
        $price=pdo_fetch("select sum(a.price) as total from ".tablename("ewei_shop_order_goods")." o"." left join ".tablename("ewei_shop_order")." a on a.id=o.orderid where ".$where."  limit ". ($pindex - 1) * $psize . ',' . $psize,$params);
        
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
}