<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php");
//fanbeibei
class Index_EweiShopV2Page extends AppMobilePage{
    //获取用户加速判断
    public function accelerate(){
        global $_GPC;
        global $_W;
       
        $openid=$_GPC["openid"];
        if (empty($openid)){
            app_error(AppError::$ParamsError);
        }
        $member=m('member')->getMember($openid);
       
        if (empty($member)){
            app_error(1,"用户不存在");
        }elseif ($member["agentlevel"]==0){
            app_error(1,"用户为普通会员");
        }
        $level=pdo_get('ewei_shop_commission_level',array('id'=>$member["agentlevel"],'uniacid'=>1));
        //加速日期
        $accelerate_day=date("Y-m-d",strtotime("+".$level["accelerate_day"]." day",strtotime($member["agentlevel_time"])));
        
        $day=date("Y-m-d",time());
        
//         //加速剩余天数
//         if ($day>=$accelerate_day){
           
//             $resault["surplus_day"]=0;
//         }else{
//             $count_days=m("member")->count_days($accelerate_day,$day);
            
//             $resault["surplus_day"]=$count_days;
//         }
//         $resault["give_day"]=$level["accelerate_day"];
//         //已加速天数
//         $resault["accelerate_day"]=$level["accelerate_day"]-$resault["surplus_day"];
        
        $dd=m("member")->acceleration($openid);
        //加速剩余天数
        $resault["surplus_day"]=$dd["day"];
        $resault["give_day"]=$dd["give_day"];
        $resault["accelerate_day"]=$dd["accelerate_day"];
        $resault["type"]=$dd["type"];
        
        //获取用户加速期间的卡路里
        if ($dd["type"]==0){
        $starttime=strtotime($member["agentlevel_time"]);
        $endtime=strtotime($accelerate_day);
        }else{
            $starttime=strtotime($member["accelerate_start"]);
            $endtime=strtotime($member["accelerate_end"]);
        }
        
//         var_dump($starttime);
//         var_dump($endtime);
        $credit=pdo_fetchcolumn("select sum(num) from ".tablename('mc_credits_record')."where credittype=:credittype and  openid=:openid and createtime>=:starttime and createtime<=:endtime and (remark like :remark or remark like :cc)",array('credittype'=>"credit1",':openid'=>$openid,':starttime'=>$starttime,':endtime'=>$endtime,':remark'=>'%'.'步数兑换',':cc'=>'好友助力'));
//         $credit=pdo_fetchall("select * from ".tablename('mc_credits_record')."where credittype=:credittype and  openid=:openid and createtime>=:starttime and createtime<=:endtime ",array('credittype'=>"credit1",':openid'=>$openid,':starttime'=>$starttime,':endtime'=>$endtime));
//         var_dump($credit);die;
        if (empty($credit)){
            $resault["credit"]=0;
        }else{
            $resault["credit"]=$credit;
        }
        app_error(0,$resault);
    }
    //scene
    public function scene(){
         global $_W;
         global $_GPC;
         $data["openid"]=$_GPC["openid"];
         $data["scene"]=$_GPC["scene"];
         $data["create_time"]=date("Y-m-d H:i:s");
         pdo_insert("ewei_shop_member_scene",$data);
         show_json(1,"成功");
    }
    
    //首页广告位
    public function adsense(){
        global $_W;
        global $_GPC;
        $type=$_GPC["type"];
        $openid = $_GPC['openid'];
        $member = pdo_get('ewei_shop_member',['openid'=>$openid]);
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_adsense")." where type=:type order by sort desc",array(":type"=>$type));
        foreach ($list as $k=>$v){
            $list[$k]["thumb"]=tomedia($v["thumb"]);
            $list[$k]['url'] = strpos($v['url'],"member_card") == false ? : $member['is_open'] == 1 ? $v['url'] : "/pages/annual_card/equity/equity";
        }
        $l["list"]=$list;
        show_json(1,$l);
    }
    
    //页面优化
    public function opt(){
        global $_W;
        global $_GPC;
        $id=$_GPC["id"];
        $list=pdo_get("ewei_shop_small_set",array("id"=>$id));
        $list["icon"]=unserialize($list["icon"]);
        $list["backgroup"]=tomedia($list["backgroup"]);
        $list["banner"]=tomedia($list["banner"]);
        foreach ($list["icon"] as $k=>$v){
            $list["icon"][$k]["img"]=tomedia($v["img"]);
            $list["icon"][$k]["icon"]=tomedia($v["icon"]);
        }
        show_json(1,$list);
    }
    //首页
    public function optindex(){
        global $_W;
        global $_GPC;
        $list=pdo_get("ewei_shop_small_set",array("id"=>1));
        $l["backgroup"]=tomedia($list["backgroup"]);
        $l["banner"]=tomedia($list["banner"]);
        //获取icon
        $l["icon"]=pdo_fetchall("select id,olddata from ".tablename("ewei_shop_small_setindex")." where status=0 order by sort asc");
        foreach ($l["icon"] as $k=>$v){
            $d=unserialize($v["olddata"]);
            $l["icon"][$k]["img"]=tomedia($d["img"]);
            if ($v["icon"]){
            $l["icon"][$k]["icon"]=tomedia($d["icon"]);
            }else{
                $l["icon"][$k]["icon"]="";
            }
            $l["icon"][$k]["url"]=$d["url"];
            $l["icon"][$k]["title"]=$d["title"];
        }
        show_json(1,$l);
    }
    
    //页面优化ceshi
    public function optt(){
        global $_W;
        global $_GPC;
        $id=$_GPC["id"];
        $list=pdo_get("ewei_shop_small_set",array("id"=>$id));
        $list["icon"]=unserialize($list["icon"]);
        $list["backgroup"]=tomedia($list["backgroup"]);
        $list["banner"]=tomedia($list["banner"]);
        foreach ($list["icon"] as $k=>$v){
            $list["icon"][$k]["img"]=tomedia($v["img"]);
            $list["icon"][$k]["icon"]=tomedia($v["icon"]);
        }
        $list["icon"][4]["title"]="达人圈";
        $list["icon"][4]["img"]="https://www.paokucoin.com/img/backgroup/gif-kt@2x.png";
        $list["icon"][4]["url"]="/pages/expert/circle/circle";
        $list["icon"][4]["icon"]="";
        $list["icon"][5]["title"]="每日必读";
        $list["icon"][5]["img"]="https://www.paokucoin.com/img/backgroup/quan-kt@2x.png";
        $list["icon"][5]["url"]="/packageA/pages/skyread/read/read";
        $list["icon"][5]["icon"]="";
        show_json(1,$list);
    }
    
    public function cd(){
        $openid="sns_wa_owRAK467jWfK-ZVcX2-XxcKrSyng";
        //卡路里
        //获取今日已兑换的卡路里
        $starttime=strtotime(date("Y-m-d 23:59:59",strtotime('-1 day')));
        $endtime=strtotime(date("Y-m-d 00:00:00",strtotime('+1 day')));
        $count_list=pdo_fetchall("select num from ".tablename("mc_credits_record")." where openid=:openid and credittype=:credittype and createtime>=:starttime and createtime<=:endtime and num>0 and (remark like :remark1 or remark like :remark2) order by id desc",array(':openid'=>$openid,':credittype'=>"credit1",":starttime"=>$starttime,':endtime'=>$endtime,':remark1'=>'%步数兑换%',':remark2'=>'%好友助力%'));
        var_dump($count_list);
        $count=array_sum(array_column($count_list, 'num'));
        var_dump($count);
        $order=array('26707','26773','27216','27866','27937','28285','28389','28399');
        pdo_update("ewei_shop_merch_bill",array("orderids"=>iserializer($order)),array("id"=>169));
        $d=pdo_get("ewei_shop_merch_bill",array("id"=>157));
        var_dump(iunserializer($d["orderids"])); 
    }
    
    public function mycenter(){
        $list=pdo_get("ewei_shop_small_set",array("id"=>3));
        $l=unserialize($list["icon"]);
        foreach ($l["order"] as $k=>$v){
            if (!empty($v)){
            $l["order"][$k]=tomedia($v);
            }
        }
        foreach ($l["server"] as $k=>$v){
            if (!empty($v)){
                $l["server"][$k]=tomedia($v);
            }
        }
        show_json(1,$l);
    }
    
    function GetTeamMember($members, $mid) {
       
        $mids=array($mid);//第一次执行时候的用户id
        $agentallcount=0;
        $shopkeeperallcount=0;
        do {
            $othermids=array();
            $state=false;
            foreach ($mids as $valueone) {
                foreach ($members as $key => $valuetwo) {
                    if($valuetwo['agentid']==$valueone){
                        $agentallcount+=1;//所有的推荐
                        if ($valuetwo["agentlevel"]==5){
                            $shopkeeperallcount+=1;
                        }
                        $othermids[]=$valuetwo['id'];//将我的下级id保存起来用来下轮循环他的下级
                        array_splice($members,$key,1);//从所有会员中删除他
                        $state=true;
                    }
                }
            }
            $mids=$othermids;//foreach中找到的我的下级集合,用来下次循环
        } while ($state==true);
        $data["agentallcount"]=$agentallcount;
        $data["shopkeeperallcount"]=$shopkeeperallcount;
        return $data;
    }
    
    public function membercount(){
        $member=pdo_fetchall("select id,openid,agentid,agentlevel from ".tablename("ewei_shop_member")." order by id asc");
        $m=pdo_fetchall("select id,openid,agentid,agentlevel from ".tablename("ewei_shop_member")." where id<50 and  id>=1 order by id asc ");
        
        foreach ($m as $k=>$v){
           //获取直推数据
            $data=array();
            $data["agentcount"]=pdo_fetchcolumn("select count(1) from ".tablename("ewei_shop_member")." where agentid=:agentid", array(":agentid"=>$v["id"]));
            $data["shopkeepercount"]=pdo_fetchcolumn("select count(1) from ".tablename("ewei_shop_member")." where agentid=:agentid and agentlevel=5", array(":agentid"=>$v["id"]));
//            $data["starshinecount"]=pdo_getcolumn("select count(*) from ".tablename("ewei_shop_member")." where agentid=:agentid and agentlevel=3", array(":agentid"=>$v["id"]));
             $d=$this->GetTeamMember($member,$v["id"]);
             $data["agentallcount"]=$d["agentallcount"];
             $data["shopkeeperallcount"]=$d["shopkeeperallcount"];
          //  $data["agentallcount"]= m('member')->allAgentCount($v['id']);
            $data["update_time"]=date("Y-m-d H:i:s");
            $c=pdo_get("ewei_shop_member_agentcount",array("openid"=>$v["openid"]));
           if ($c){
               pdo_update("ewei_shop_member_agentcount",$data,array("openid"=>$v["openid"]));
           }else{
               $data["openid"]=$v["openid"];
               pdo_insert("ewei_shop_member_agentcount",$data);
           }
           var_dump($v["openid"]);
           var_dump($data);
           
        }
    }

    function Getparent($mid) {
        $parent_id=array();
        $i=0;
        do {
            $state=false;
//             var_dump($mid);
            $member=pdo_get("ewei_shop_member",array("id"=>$mid));
//             var_dump($member);
            if ($member["agentid"]!=0){
                $agent=pdo_get("ewei_shop_member",array("id"=>$member["agentid"]));
//                 if (empty($agent["parent_id"])){
                   if (!in_array($member["agentid"], $parent_id)){
                    $parent_id[$i]=$member["agentid"];
                    $i+=1;
//                     var_dump("11");
//                     var_dump($member["agentid"]);
                    $state=true;
                   }
//                 }
//                 else{
//                    //获取长度
//                    $parent=unserialize($agent["parent_id"]);
//                    $len=count($parent);
//                    $parent_id=$parent;
//                    $parent_id[$len]=$member["agentid"];
                  
//                 }
            }
           $mid=$member["agentid"];
        } while ($state==true);
        
        return $parent_id;
    }
    
    public function parent(){
        $m=pdo_fetchall("select * from ".tablename("ewei_shop_member")." where id>=65603 order by id asc");
        foreach ($m as $k=>$v){
            if ($v["agentid"]!=0){
            $parent_id=$this->Getparent($v["id"]);
            if (!empty($parent_id)){
                $data["parent_id"]=serialize($parent_id);
                pdo_update("ewei_shop_member",$data,array("id"=>$v["id"]));
            }
            }
            var_dump($v["id"]);
            var_dump($parent_id);
        }
        
    }
    
    

    /**
     * 定时任务  计划任务
     * 每小时请求一下
     */
    public function fix_refund()
    {
        //退换货的不处理  系统自动处理
        $refund = pdo_fetchall('select * from '.tablename('ewei_shop_order_refund').'where status in (0,3,4)');
        if(empty($refund)){
            pdo_insert('log',['log'=>'暂无待处理维权信息','createtime'=>date('Y-m-d H:i:s',time())]);
        }
        foreach ($refund as $key=>$item){
            //等于0  申请中  7天商家不处理 无理由退款  等于3   商家处理    4 处理后  需要用户发货
            //rtype  0  退款  1  退款退货  2换货
            $order = pdo_fetch('select * from '.tablename('ewei_shop_order').'where id = :id',[':id'=>$item['orderid']]);
            if($item['status'] == 0){
                //如果是退款  且  超过七天 直接 退款成功  status = 1  并且  维权时间改成当前时间
                if($item['rtype'] == 0 && time() > $item['createtime'] + 3600*7*24){
                    pdo_update('ewei_shop_order_refund',['status'=>1,'refundtime'=>time()],['id'=>$item['id']]);
                    pdo_update('ewei_shop_order',['refundstate'=>0],['id'=>$item['orderid']]);
                    $this->refund_money($order,$item);
                    pdo_insert('log',['log'=>'退款维权订单的orderid:'.$item['orderid'].',维权订单号refundno:'.$item['refundno'].',商家7天未处理，系统自动处理成已退款','createtime'=>date('Y-m-d H:i:s',time())]);
                }elseif($item['rtype'] != 0 && time() > $item['createtime'] + 3600*3*24){
                    //如果不是退款  则 3天  改成  商家已处理  status  = 3
                    pdo_update('ewei_shop_order_refund',['status'=>3,'operatetime'=>time()],['id'=>$item['id']]);
                    pdo_insert('log',['log'=>'退款退货或换货维权订单的orderid:'.$item['orderid'].',维权订单号refundno:'.$item['refundno'].',商家3天未处理，系统自动处理为商家已处理','createtime'=>date('Y-m-d H:i:s',time())]);
                }else{
                    pdo_insert('log',['log'=>'暂无可系统处理的退款维权信息','createtime'=>time()]);
                }
            }elseif ($item['status'] == 3 && time() > $item['operatetime'] + 3600*3*24){
                //如果状态是3  也就是商家已处理  等待用户发货   如果超过三天  则默认维权申请取消  并且把订单表的refundstate字段改变 0
                pdo_update('ewei_shop_order_refund',['status'=>-2,'refundtime'=>time()],['id'=>$item['id']]);
                pdo_update('ewei_shop_order',['refundstate'=>0],['id'=>$item['orderid']]);
                pdo_insert('log',['log'=>'退款退货或换货维权订单的orderid:'.$item['orderid'].',维权订单号refundno:'.$item['refundno'].',用户3天未发货，系统自动处理为维权订单取消','createtime'=>date('Y-m-d H:i:s',time())]);
            }elseif($item['status'] == 4 && time() > $item['sendtime'] + 3600*8*24 ){
                //如果用户已发货  然后  当前时间 已经超过发货时间的7天  就只懂代表维权成功
                pdo_update('ewei_shop_order_refund',['status'=>1,'refundtime'=>time()],['id'=>$item['id']]);
                pdo_update('ewei_shop_order',['refundstate'=>0],['id'=>$item['orderid']]);
                $this->refund_money($order,$item);
                pdo_insert('log',['log'=>'退款退货或换货维权订单的orderid:'.$item['orderid'].',维权订单号refundno:'.$item['refundno'].',商家8天未处理，系统自动处理为退款','createtime'=>date('Y-m-d H:i:s',time())]);
            }else{
                pdo_insert('log',['log'=>'暂无可系统处理的维权信息','createtime'=>date('Y-m-d H:i:s',time())]);
            }
        }
    }

    /**
     * 退钱
     * @param  $order
     * @param  $refund
     */
    public function refund_money($order,$refund)
    {
        global $_S;
        global $_W;
        $ispeerpay = m('order')->checkpeerpay($order['id']);
        $shopset = $_S['shop'];

        //全额余额支付
        if ($order['paytype'] == 1)
        {
            m('member')->setCredit($order['openid'], 'credit2', $refund['applyprice'], array(0, $shopset['name'] . '退款: ' . $refund['applyprice'] . '元 订单号: ' . $order['ordersn']));
            $result = true;
            $refundtype = 0;
        }
        else if ($order['paytype'] == 21)
        {//微信支付
            if ($order['apppay'] == 2)
            {
                $result = m('finance')->wxapp_refund($order['openid'], $order['ordersn'], $refund['refundno'], $refund['applyprice'] * 100, $refund['applyprice'] * 100, (!(empty($order['apppay'])) ? true : false));
            }
            else if (!(empty($ispeerpay)))
            {//微信代付
                $pid = $ispeerpay['id'];
                $peerpaysql = 'SELECT * FROM ' . tablename('ewei_shop_order_peerpay_payinfo') . ' WHERE pid = :pid';
                $peerpaylist = pdo_fetchall($peerpaysql, array(':pid' => $pid));
                if (empty($peerpaylist))
                {
                    //show_json(0, '没有人帮他代付过,无需退款');
                    pdo_insert('log',['log'=>'没有人帮他代付过,无需退款','createtime'=>time()]);
                }
                foreach ($peerpaylist as $k => $v )
                {
                    if (empty($v['tid']))
                    {
                        m('member')->setCredit($v['openid'], 'credit2', $v['price'], array(0, $shopset['name'] . '退款: ' . $v['price'] . '元 代付订单号: ' . $order['ordersn']));
                        $result = true;
                        continue;
                    }
                    $result = m('finance')->refund($v['openid'], $v['tid'], $refund['refundno'] . $v['id'], $v['price'] * 100, $v['price'] * 100, (!(empty($order['apppay'])) ? true : false));
                }
            }
            else if (0 < $refund['applyprice'])
            {
                if (empty($order['isborrow']))
                {
                    $result = m('finance')->refund($order['openid'], $order['ordersn'], $refund['refundno'], $order['price'] * 100, $refund['applyprice'] * 100, (!(empty($order['apppay'])) ? true : false));
                }
                else
                {
                    $result = m('finance')->refundBorrow($order['borrowopenid'], $order['ordersn'], $refund['refundno'], $order['price'] * 100, $refund['applyprice'] * 100, (!(empty($order['ordersn2'])) ? 1 : 0));
                }
            }
            $refundtype = 2;
        }
        else if ($order['paytype'] == 22)
        {//支付宝支付
            $sec = m('common')->getSec();
            $sec = iunserializer($sec['sec']);
            if (!(empty($order['apppay'])))
            {
                if (!(empty($sec['app_alipay']['private_key_rsa2'])))
                {
                    $sign_type = 'RSA2';
                    $privatekey = $sec['app_alipay']['private_key_rsa2'];
                }
                else
                {
                    $sign_type = 'RSA';
                    $privatekey = $sec['app_alipay']['private_key'];
                }
                if (empty($privatekey) || empty($sec['app_alipay']['appid']))
                {
                    //show_json(0, '支付参数错误，私钥为空或者APPID为空!');
                    pdo_insert('log',['log'=>'支付参数错误，私钥为空或者APPID为空!','createtime'=>time()]);
                }
                $params = array('out_request_no' => time(), 'out_trade_no' => $order['ordersn'], 'refund_amount' => $refund['applyprice'], 'refund_reason' => $shopset['name'] . '退款: ' . $refund['applyprice'] . '元 订单号: ' . $order['ordersn']);
                $config = array('app_id' => $sec['app_alipay']['appid'], 'privatekey' => $privatekey, 'publickey' => '', 'alipublickey' => '', 'sign_type' => $sign_type);
                $result = m('finance')->newAlipayRefund($params, $config);
            }
            else if (!(empty($sec['alipay_pay'])))
            {//系统配置的支付宝支付
                if (empty($sec['alipay_pay']['private_key']) || empty($sec['alipay_pay']['appid']))
                {
                    //show_json(0, '支付参数错误，私钥为空或者APPID为空!');
                    pdo_insert('log',['log'=>'支付参数错误，私钥为空或者APPID为空!','createtime'=>time()]);
                }
                if ($sec['alipay_pay']['alipay_sign_type'] == 1)
                {
                    $sign_type = 'RSA2';
                }
                else
                {
                    $sign_type = 'RSA';
                }
                $params = array('out_request_no' => time(), 'out_trade_no' => $order['ordersn'], 'refund_amount' => $refund['applyprice'], 'refund_reason' => $shopset['name'] . '退款: ' . $refund['applyprice'] . '元 订单号: ' . $order['ordersn']);
                $config = array('app_id' => $sec['alipay_pay']['appid'], 'privatekey' => $sec['alipay_pay']['private_key'], 'publickey' => '', 'alipublickey' => '', 'sign_type' => $sign_type);
                $result = m('finance')->newAlipayRefund($params, $config);
            }
            else
            {
                if (empty($order['transid']))
                {
                    //show_json(0, '仅支持 升级后此功能后退款的订单!');
                    pdo_insert('log',['log'=>'仅支持 升级后此功能后退款的订单!','createtime'=>time()]);
                }
                $setting = uni_setting($_W['uniacid'], array('payment'));
                if (!(is_array($setting['payment'])))
                {
                    //return error(1, '没有设定支付参数');
                    pdo_insert('log',['log'=>'没有设定支付参数','createtime'=>time()]);
                }
                $alipay_config = $setting['payment']['alipay'];
                $batch_no_money = $refund['applyprice'] * 100;
                $batch_no = date('Ymd') . 'RF' . $order['id'] . 'MONEY' . $batch_no_money;
                $res = m('finance')->AlipayRefund(array('trade_no' => $order['transid'], 'refund_price' => $refund['applyprice'], 'refund_reason' => $shopset['name'] . '退款: ' . $refund['applyprice'] . '元 订单号: ' . $order['ordersn']), $batch_no, $alipay_config);
                if (is_error($res))
                {
                    //show_json(0, $res['message']);
                    pdo_insert('log',['log'=>$res['message'],'createtime'=>time()]);
                }
                show_json(1, array('url' => $res));
            }
            $refundtype = 3;
        }
        else if (($order['paytype'] == 23) && !(empty($order['isborrow'])))
        {
            $result = m('finance')->refundBorrow($order['borrowopenid'], $order['ordersn'], $refund['refundno'], $refund['applyprice'] * 100, $refund['applyprice'] * 100, (!(empty($order['ordersn2'])) ? 1 : 0));
            $refundtype = 4;
        }
        else
        {
            if ($refund['applyprice'] < 1)
            {
                //show_json(0, '退款金额必须大于1元，才能使用微信企业付款退款!');
                pdo_insert('log',['log'=>'退款金额必须大于1元，才能使用微信企业付款退款!','createtime'=>time()]);
            }
            if (0 < $refund['applyprice'])
            {
                $result = m('finance')->pay($order['openid'], 1, $refund['applyprice'] * 100, $refund['refundno'], $shopset['name'] . '退款: ' . $refund['applyprice'] . '元 订单号: ' . $order['ordersn']);
            }
            $refundtype = 1;
        }

        //如果有报错  给报错信息加日志
        if (is_error($result))
        {
            //show_json(0, $result['message']);
            pdo_insert('log',['log'=>$result['message'],'createtime'=>time()]);
        }

        //计算余额抵扣的金额
        $dededuct__refund_price = 0;
        if ($refund['applyprice'] <= $order['price'])
        {
            $dededuct__refund_price = 0;
        }
        else if (($order['price'] < $refund['applyprice'] ) && ($refund['applyprice']  <= $order['price'] + $order['deductcredit2']))
        {
            $dededuct__refund_price = $refund['applyprice'] - $order['price'];
        }
        else
        {
            $dededuct__refund_price = $order['deductcredit2'];
        }
        //给用户加上余额抵扣
        if (0 < $dededuct__refund_price)
        {
            $item['deductcredit2'] = $dededuct__refund_price;
            m('order')->setDeductCredit2($item);
        }
        //用户消费卡路里修改
        if ($order["deductprice"]>0){
            m('member')->setCredit($order['openid'], 'credit1', $order["deductprice"], array(0, $shopset['name'] . '购物返还抵扣卡路里 卡路里' . $order["deductprice"] . '订单号: ' . $order['ordersn']));
        }

        //折扣宝修改
        if ($order["discount_price"]>0){
            m('member')->setCredit($item['openid'], 'credit3', $order["discount_price"], array(0, $shopset['name'] . '购物返还抵扣折扣宝 折扣宝' . $order["discount_price"] . '订单号: ' . $order['ordersn']));
        }
    }

}

?>
