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
     * @param $credit
     * @return array
     */
      public function prize($data,$type,$openid,$money,$credit = 'credit1')
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
            $this->addlog($openid,$type,-$money,$data['game_type'],'幸运转盘抽奖',$credit);
            //抽奖  就减去对应的东西
            $this->addcredit($openid,$money,'sub',$data['game_type'],$credit);
            foreach ($array as $key=>$value){
                if($rand <= $value){
                    $item = explode('_',$key);
                    preg_match('/\d+/',$item[1],$arr);
                    //添加中奖日志
                    $this->addlog($openid,1,$arr[0],$data['game_type'],"抽中".$item[1],$credit);
                    //如果$data['type']  == 1 就是卡路里   == 2 就是折扣宝
                    $this->addcredit($openid,$arr[0],"add",$data['game_type'],$credit);
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
     * @param $credit
     */
      public function addcredit($openid,$money,$add,$game_type,$credit){
          $member = pdo_get('ewei_shop_member',['openid'=>$openid]);
          //如果是减得话  说明是抽奖  抽奖只能用卡路里  所以 减卡路里
          if($add == "sub"){
              $credit_num = $member[$credit] - $money;
              pdo_update('ewei_shop_member',[$credit=>$credit_num],['openid'=>$openid]);
          }elseif($add == "add"){
              //如果是加的话  就是中奖 中奖分卡路里 和 折扣宝
              if($game_type == 1){
                  $credit_num = $member[$credit] + $money;
                  pdo_update('ewei_shop_member',['credit1'=>$credit_num],['openid'=>$openid]);
              }elseif ($game_type == 2){
                  $credit_num = $member[$credit] + $money;
                  pdo_update('ewei_shop_member',[$credit=>$credit_num],['openid'=>$openid]);
              }
          }
      }

    /**
     * 添加日志
     * @param $openid
     * @param $type  $type == 0普通的开支   1中奖   2免费抽奖
     * @param $money
     * @param $datatype
     * @param $credit
     * @param $remark
     */
      public function addlog($openid,$type,$money,$datatype,$remark,$credit)
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
              'credittype'=>$credit
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
     * @return array
     */
    public function gift($gifts)
    {
        $goods = [];
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
        }
        return $goods;
    }

    /**
     * 判断该商品是否符合领取礼包
     * @param $openid
     * @param $goods_id
     * @return bool
     */
    public function gift_check($openid,$goods_id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $week = m('util')->week(time());
        //查所有的礼包
        $gifts = pdo_getall('ewei_shop_gift_bag',['status'=>1,'uniacid'=>$uniacid]);
        //查找用户信息
        $member = m('member')->getMember($openid);
        //再查他的领取情况
        $log = pdo_fetchall('select * from '.tablename('ewei_shop_gift_log').'where (openid = :openid or user_id = :user_id) and status > 0 and uniacid = "'.$uniacid.'" and createtime between "'.$week['start'].'" and "'.$week['end'].'"',[':openid'=>$member['openid'],':user_id'=>$member['id']]);
        //设置$flag  为 false
        $flag = false;
        $gift = [];
        foreach ($gifts as $item){
            //把每个礼包里面包含的商品解析成数组
            $goods = explode(',',$item['goodsid']);
            //判断该商品是不是在这个礼包里面
            if(in_array($goods_id,$goods)){
                //把这个礼包里面的允许领取等级解析成数组
                $levels = explode(',',$item['levels']);
                //查看本周是否领取过该礼包
                if(!pdo_fetch('select * from '.tablename('ewei_shop_gift_log').' where (openid = :openid or user_id = :user_id) and status > 0 and gift_id = "'.$item['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'"',[':openid'=>$member['openid'],':user_id'=>$member['id']])){
                    //当前等级够不够格领取该礼包
                    if($member['agentlevel'] >= min($levels)){
                        $flag = $item['id'];
                        $gift = $item;
                        break;
                    }else{
                        $flag = false;
                    }
                }
            }
        }
        //设置需要的人数为0  然后 按要求加数量
        $num = 0;
        if(count($log) == 0){
            //如果他没领取过  需要邀请新人数量等于当前的领取礼包的数量
            $num += $gift['member'];
        }else {
            foreach ($log as $item) {
                //如果领取过了  需要加上已经领取过的礼包需要的数量
                $num += pdo_getcolumn('ewei_shop_gift_bag', ['id' => $item['gift_id'], 'uniacid' => $_W['uniacid']], 'member');
            }
            //然后加上的这次领的礼包需要的人数
            $num += $gift['member'];
        }
        //计算他在活动期间的邀请新人数量
        $count = pdo_count('ewei_shop_member','agentid = "'.$member['id'].'" and createtime between "'.$week['start'].'" and "'.$week['end'].'"');
        //如果邀请数量不足  则返回false
        if($count < $num){
            $flag = false;
        }
        return $flag;
    }

    /**
     * 切换地址接口
     * @param $address_id
     * @param $openid
     * @param $uniacid
     * @return int
     */
    public function change_address($address_id,$openid,$uniacid)
    {
        $member = m('member')->getMember($openid);
        $record = pdo_fetch('select * from '.tablename('ewei_shop_level_record').'where (openid = :openid or user_id = :user_id) and uniacid = :uniacid order by id asc',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid]);
        $user_address = pdo_fetch('select * from '.tablename('ewei_shop_member_address').'where (openid = :openid or user_id = :user_id) and uniacid = :uniacid and id = :address_id and deleted = 0',[':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid,':address_id'=>$address_id]);
        if(empty($user_address)){
            show_json(0,"用户地址错误");
        }
        $base_address = pdo_getcolumn('ewei_shop_express_set',['uniacid'=>$uniacid,'id'=>1],'express_set');
        $base_express = explode(';',$base_address);
        if(in_array($user_address['province'],$base_express)){
            $data['is_remote'] = 0;
            //这个人的第一条记录为0的话  说明是第一次领取
            $data['price'] = $record['status'] > 0 ? 10 : 0;
        }else{
            $data['is_remote'] = 1;
            //这个人的第一条记录为0的话  说明是第一次领取
            $data['price'] = $record['status'] > 0 ? 20 : 0;
        }
        return $data;

    }

    /**
     * 添加订单
     * @param $openid
     * @param $order_sn
     * @param $money
     * @param $address_id
     * @param $remark
     * @param $goods
     * @return bool
     */
    public function addorder($openid,$order_sn,$money,$address_id = "",$remark = "",$goods = [])
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid);
        //因为领取的权益是实物产品  所以需要地址
        $address = empty($address_id)?null:serialize(pdo_get('ewei_shop_member_address',['id'=>$address_id,'uniacid'=>$uniacid]));
        $data = [
            'uniacid'=>$uniacid,
            'openid'=>$member['openid'],
            'user_id'=>$member['id'],
            'ordersn'=>$order_sn,
            'goodsprice'=>$goods['marketprice']?:0,
            'price'=>$money,
            'createtime'=>time(),
            'agentid'=>$member['agent_id'],
            'addressid'=>$address_id?:0,
            'address'=>$address,
            'dispatchprice'=>$money,
            'remark'=>$remark,
        ];
        $data['status'] = $money == 0 ? 1 :0;
        pdo_insert('ewei_shop_order',$data);
        $orderid = pdo_insertid();
        if(!empty($goods)){
            $add = [
                'uniacid'=>$uniacid,
                'goodsid'=>$goods['id'],
                'orderid'=>$orderid,
                'price'=>$money,
                'total'=>1,
                'createtime'=>time(),
                'realprice'=>0,
                'changeprice'=>$goods['marketprice'],
                'oldprice'=>$goods['marketprice'],
                'openid'=>$member['openid'],
                'optionname'=>'',
            ];
            pdo_insert('ewei_shop_order_goods',$add);
        }
        return $orderid;
    }

    /**
     * 添加用户日志
     * @param $openid
     * @param $order_sn
     * @param $money
     * @param $remark
     * @return bool
     */
    public function addmemberlog($openid,$order_sn,$money,$remark)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid);
        $data= [
            'uniacid'=>$uniacid,
            'openid'=>$member['openid'],
            'user_id'=>$member['id'],
            'type'=>2,
            'logno'=>$order_sn,
            'title'=>$remark,
            'createtime'=>time(),
            'status'=>0,
            'money'=>-$money,
            'rechargetype'=>'waapp',
        ];
        return pdo_insert('ewei_shop_member_log',$data);
    }

    /**
     * @param $user_id
     * @param $amount
     * @param $type
     * @return array
     */
    public function rvc_pay($user_id,$amount,$type = 0)
    {
        global $_W;
        $member = m('member')->getMember($user_id);
        $data['amount'] = $amount;
        $data['nonce'] = random(16);
        $data['coinType'] = "RVC";
        $data['category'] = "跑库充值";
        $data['privateMemo'] = $data['memo'] = $data['category'].$data['amount'].$data['coinType'];
        $data['redirect'] = "https://www.paokucoin.com/rvc.html?type=".intval($type);
        //排序
        ksort($data);
        $string = "";
        //拼接字符串
        foreach ($data as $key => $item){
            $string .= $key . '=' . urlencode($item) . '&';
        }
        $string = trim($string,'&');
        $data['accessKey'] = "C1V.0MruASD1js_Qa5GNVkCX0IAJL-g2IgclGPG2ZQEfAl7f";
        $SecretKey = "Pr2ZPufPFdu43KCNX64cnGNyxNgmgVNddHYPacTv6Aaum1ZvsW/+1xa8W3Vq4QnP";
        //获得签名
        $data['signature'] = $signature = hash_hmac("sha1",$string,$SecretKey);
        //请求
        $res = $this->curl_post_raw("https://wallet.block-api.dev/api/pay/record",json_encode($data,JSON_UNESCAPED_UNICODE));
        $res = json_decode($res,true);
        $price = $res['data']['price'];
        $add = [
            'uniacid'=>$_W['uniacid'],
            'ordersn'=>$res['data']['uuid'],
            'openid'=>$member['openid'],
            'user_id'=>$member['id'],
            'amount'=>$data['amount'],
            'totalprice'=>bcmul($price,$data['amount'],2),
            'price'=>$price,
            'status'=>0,
            'createtime'=>time(),
        ];
        pdo_insert('ewei_shop_member_rvcorder',$add);
        $status = $res['code'] == "M0000" ? 0 : 1;
        return ['status'=>$status,'msg'=>$res['message'],'data'=>$res['data']];
    }

    /**
     * @param $url
     * @param $rawData
     * @return mixed
     */
    function curl_post_raw($url,$rawData){
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => $rawData
        ));
        return $data = curl_exec($ch);
    }
}