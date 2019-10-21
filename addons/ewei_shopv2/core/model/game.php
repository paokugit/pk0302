<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
class Game_EweiShopV2Model{
    /**
     * @param $data
     * @param $type
     * @param $openid
     * @param $money
     * @return int|string
     */
      public function prize($data,$type,$openid,$money)
      {
            $array = [];
            foreach (iunserializer($data['sets']) as $key=>$val){
                $array[$key.'_'.$val['reward'.($key+1)]] = $val['rate'.($key+1)] *100;
            }
            asort($array);
            //计算总额
            $num = array_sum($array);
            $rand = rand(1,$num);
            //扣除抽奖的钱的日志  $type == 2免费  $type == 0 花钱
            $this->addlog($openid,$type,-$money,$data['game_type'],'幸运转盘抽奖');
            //抽奖  就减去对应的东西
            $this->addcredit($openid,$money,'sub',$data['game_type']);
            foreach ($array as $key=>$value){
                if($rand <= $value){
                    $item = explode('_',$key);
                    preg_match('/\d+/',$item[1],$arr);
                    //添加中奖日志
                    $this->addlog($openid,1,$arr[0],$data['game_type'],"抽中".$item[1]);
                    //如果$data['type']  == 1 就是卡路里   == 2 就是折扣宝
                    $this->addcredit($openid,$arr[0],"add",$data['game_type']);
                    return ['location'=>$item[0],'num'=>$arr[0]];
                    break;
                }else{
                    $rand -= $value;
                }
            }
      }

    /**
     * 更新用户的卡路里或者折扣宝
     * @param $openid
     * @param $money
     * @param $add
     * @param $game_type
     */
      public function addcredit($openid,$money,$add,$game_type){
          $member = pdo_get('ewei_shop_member',['openid'=>$openid]);
          //如果是减得话  说明是抽奖  抽奖只能用卡路里  所以 减卡路里
          if($add == "sub"){
              $credit = $member['credit1'] - $money;
              pdo_update('ewei_shop_member',['credit1'=>$credit],['openid'=>$openid]);
          }elseif($add == "add"){
              //如果是加的话  就是中奖 中奖分卡路里 和 折扣宝
              if($game_type == 1){
                  $credit = $member["credit1"] + $money;
                  pdo_update('ewei_shop_member',['credit1'=>$credit],['openid'=>$openid]);
              }elseif ($game_type == 2){
                  $credit = $member["credit3"] + $money;
                  pdo_update('ewei_shop_member',['credit3'=>$credit],['openid'=>$openid]);
              }
          }
      }

    /**
     * 添加日志
     * @param $openid
     * @param $type  $type == 0普通的开支   1中奖   2免费抽奖
     * @param $money
     * @param $datatype
     * @param $remark
     */
      public function addlog($openid,$type,$money,$datatype,$remark)
      {
          global $_W;
          $add = [
              'openid'=>$openid,
              'type'=>$type,
              'module'=>'ewei_shopv2',
              'num'=>$money==0?0:$money,
              'uniacid'=>$_W['uniacid'],
              'createtime'=>time(),
              'remark'=>$remark,
              'credittype'=>"credit1"
          ];
          //如果是中奖的话  给加中奖日志 也就是加如果转盘是折扣宝转盘 就加折扣宝  卡路里转盘 就加卡路里
          if($type == 1){
              if($datatype == 1){
                  $add['credittytpe'] = "credit1";
              }elseif ($datatype == 2){
                  $add['credittype'] = "credit3";
              }
          }
          pdo_insert('mc_credits_record',$add);
          pdo_insert('ewei_shop_member_credit_record',$add);
      }

    /**
     * @param $openid
     * @param $credittype
     * @param $money
     * @param $remark
     */
      public function addCreditLog($openid,$credittype,$money,$remark)
      {
          global $_W;
          $add = [
              'openid'=>$openid,
              'module'=>'ewei_shopv2',
              'num'=>$money==0?0:$money,
              'uniacid'=>$_W['uniacid'],
              'createtime'=>time(),
              'remark'=>$remark,
              'credittype'=>"credit".$credittype,
          ];
          pdo_insert('mc_credits_record',$add);
          pdo_insert('ewei_shop_member_credit_record',$add);
      }

    /**
     * 加领取日志
     * @param $openid
     * @param $goods_id
     * @param $order_sn
     * @return bool
     */
    public function add_log($openid,$goods_id,$order_sn)
    {
        global $_W;
        //查找所有开启状态的礼包
        $gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$_W['uniacid'].'"');
        //$gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where uniacid = "'.$_W['uniacid'].'"');
        foreach ($gifts as $item){
            $goods = explode(',',$item['goodsid']);
            if(in_array($goods_id,$goods)){
                $gift = $item;
                break;
            }
        }
        $data = [
            'openid'=>$openid,
            'gift_id'=>$gift['id'],
            'goods_id'=>$goods_id,
            'uniacid'=>$_W['uniacid'],
            'order_sn'=>$order_sn,
            'createtime'=>time(),
        ];
        return pdo_insert('ewei_shop_gift_log',$data);
    }

    /**
     * @param $gift_id
     * @return string
     */
    public function check($gift_id)
    {
        if($gift_id == 1){
            $gift = "初级礼包";
        }elseif ($gift_id == 2){
            $gift = "中级礼包";
        }else{
            $gift = "高级礼包";
        }
        return $gift;
    }

    /**
     * 检测会员的额度
     * @param $openid
     * @param $level
     * @return bool|mixed
     */
    public function checklimit($openid,$level)
    {
        $limit = pdo_getcolumn('ewei_shop_commission_level',['id'=>$level],'limit');
        $all = pdo_fetchall('select * from '.tablename('ewei_shop_member_limit_order').'where openid = :openid and status = 1',[':openid'=>$openid]);
        $sum = array_sum(array_column($all,'limit'));
        return $limit + $sum;
    }

    /**
     * 获得该用户应该获得的礼包
     * @param $gift
     * @param $openid
     * @return mixed
     */
    public function get_gift($gift,$openid)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        //获得用户的信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$uniacid]);
        foreach ($gift as &$item) {
            $level = explode(',',$item['levels']);
            //判断是有此范围内
            if(in_array($member['agentlevel'],$level)){
                return $item;
                break;
            }
        }
    }

    /**
     * 获得该用户应该获得的礼包
     * @param $gift
     * @param $openid
     * @param $goods_id
     * @return mixed
     */
    public function get_gifts($gift,$openid,$goods_id)
    {
        global $_W;
        $levels = [];
        $data = [];
        //获得用户的信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$_W['uniacid']]);
        foreach ($gift as $key=>$item){
            //每个礼包的等级分解下
            $lev = explode(',',$item['levels']);
            //每个礼包的商品分解下
            $goods = explode(',',$item['goodsid']);
            //获得每个等级的最小项
            $l = min($lev);
            //如果的商品在其内  就返回循环项
            if(in_array($goods_id,$goods)){
                //用户等级大于当前的最小的值   然后  合并等级
                if($member['agentlevel'] >= $l){
                    return $item;
                }else{
                    return "不可领取该礼包";
                }
            }
        }
    }

    /**
     * @param $list
     * @param $time
     * @param $uid
     * @return mixed
     */
    public function isvalid($list,$time,$uid)
    {
        foreach($list as $key=>$item){
            //$member = pdo_get('ewei_shop_member',['openid'=>$item['bang']]);
            $member = pdo_get('ewei_shop_member',['openid'=>$item['openid']]);
            $list[$key]['nickname'] = $member['nickname'];
            $list[$key]['avatar'] = $member['avatar'];
            $list[$key]['timestamp'] = date('Y-m-d H:i',$item['createtime']);
            //如果用户的注册时间大于活动开始时间  就有效
            $list[$key]['is_valid'] = $member['createtime'] > $time && $member['agentid'] == $uid ? 1 :0;
        }
        return $list;
    }

    /**
     * 检测用户领取礼包的情况
     * @param $openid
     * @param $goods_id
     * @return bool|string
     */
    public function check_gift($openid,$goods_id)
    {
        global $_W;
        $week = m('util')->week(time());
        //查找所有开启状态的礼包
        $gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where status = 1 and uniacid = "'.$_W['uniacid'].'"');
        //$gifts = pdo_fetchall(' select * from '.tablename('ewei_shop_gift_bag').' where uniacid = "'.$_W['uniacid'].'"');
        //该用户对应的礼包
        $gift = m('game')->get_gifts($gifts,$openid,$goods_id);
        if(!is_array($gift)){
            return $gift;
        }
        //查看会员信息
        $member = pdo_get('ewei_shop_member',['openid'=>$openid,'uniacid'=>$_W['uniacid']]);
        //查看当前时间  是否在礼包的有效期
        if(time() < $gift['starttime'] || time() > $gift['endtime']){
            return "不在活动期间";
        }
        //再查他的领取情况  在本周内  且领状态  是 领了未支付
        $log = pdo_fetchall('select * from '.tablename('ewei_shop_gift_log').'where openid = :openid and uniacid = "'.$_W['uniacid'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'" and status > 0',[':openid'=>$openid]);
        $ids = array_column($log,'gift_id');
        if(in_array($gift['id'],$ids)){
            $glog = pdo_fetch('select * from'.tablename('ewei_shop_gift_log').'where openid = :openid and gift_id = "'.$gift['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'" and status > 0',[':openid'=>$openid]);
            if($glog['status'] == 1){
                return "您已经领".$gift['title']."待支付";
            }else{
                return "您已成功领取".$gift['title'];
            }
        }
        $num = 0;
        //如果他没领取过  需要邀请新人数量等于当前的领取礼包的数量
        if(count($log) == 0){
            $num += $gift['member'];
        }else {
            //如果领取过了  需要加上已经领取过的礼包需要的数量
            foreach ($log as $item) {
                $num += pdo_getcolumn('ewei_shop_gift_bag', ['id' => $item['gift_id'], 'uniacid' => $_W['uniacid']], 'member');
            }
            $num += $gift['member'];
        }
        $count = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'"');
        if($count < $num){
            return "邀请新人数不足";
        }
        //计算用户有没有店主权益兑换券
//        $quan_count = pdo_count('ewei_shop_coupon_data',['openid'=>$openid,'uniacid'=>$_W['uniacid'],'couponid'=>2]);
//        if($quan_count != 0 && $member['agentlevel'] == 5){
//            return "您已领取过店主权益，不能领取高级礼包";
//        }
        return true;
    }

    /**
     * 计算目标数
     * @param $level
     * @param $gifts
     * @return int
     */
    public function count($level,$gifts)
    {
        $num = 0;
        foreach ($gifts as $key=>$item){
            $lev = explode(',',$item['levels']);
            $l = min($lev);
            if($level >= $l){
                $num += $item['member'];
            }
        }
        return $num;
    }

    /**
     * 当邀请人数  少于需要的人数的时候  追加空数据
     * @param $new
     * @param $total
     * @param $count
     * @param $avatar
     * @return mixed
     */
    public function addnew($new,$total,$count,$avatar)
    {
        $new_push = [
            'nickname'=>'待邀请',
            'avatar'=>$avatar,
        ];
        for ($i=0;$i<$total-$count;$i++){
            array_push($new,$new_push);
        }
        return $new;
    }

    /**
     * @param $gifts
     * @param $openid
     * @return array
     */
    public function gift($gifts,$openid)
    {
        $goods = [];
//        //获取本周的始末
//        $week = m('util')->week(time());
//        //获得本周的领取记录
//        $log = pdo_fetchall('ewei_shop_gift_log',"openid = :openid and createtime between '".$week['start']."' and '".$week['end']."'",[':openid'=>$openid]);
//        //把领取礼包的id组成一维数组
//        $log_ids = array_column($log,'gift_id','id');
//        //获得用户的id
//        $id = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'id');
//        //计算本周的邀请人数
//        $num = pdo_count('ewei_shop_member','agentid = "'.$id.'" and createtime between "'.$week['start'].'" and "'.$week['end'].'"');
//        $num = 23;
//        $count = 0;
//        //计算领取过的  已经需要多少人
//        foreach ($log_ids as $item){
//            $count += pdo_getcolumn('ewei_shop_gift_bag',['id'=>$item],'member');
//        }
        foreach ($gifts as $key=>$item){
            $ids = explode(',',$item['goodsid']);
            $levels = explode(',',$item['levels']);
            $goods[$key]['level_id'] = $item['id'];
            $goods[$key]['level'] = pdo_getcolumn('ewei_shop_gift_bag',['id'=>$item['id']],'title');
            foreach ($ids as $id){
                $goods[$key]['thumbs'][] = ['id'=>$id,'thumb'=>tomedia(pdo_getcolumn('ewei_shop_goods',['id'=>$id],'thumb'))];
            }
            foreach ($levels as $level){
                if($level == 0){
                    $goods[$key]['level_name'] .= '普通会员';
                }
                $goods[$key]['level_name'] .= pdo_getcolumn('ewei_shop_commission_level',['id'=>$level],'levelname');
            }
//            //如果领取过的人数  大于
//            if($count > $num){
//                $goods[$key]['is_get'] = 0;
//            }else{
//                $goods[$key]['is_get'] = in_array($item['id'],$log_ids) ? 0 : 1;
//            }
        }
        return $goods;
    }
}