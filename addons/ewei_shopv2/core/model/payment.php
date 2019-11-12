<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Payment_EweiShopV2Model
{
    /**
     * 计算贡献机的数量 有效  和  所有贡献机
     * @param $total
     * @param $uniacid
     * @param $user_id
     * @return array
     */
    public function getlist($total,$uniacid,$user_id)
    {
        $member = m('member')->getMember($user_id);
        $list = [];
        $size = 1;
        for ($i=1;$i<=$total;$i++){
            $key = $i%8 != 0 ? $i%8 : 8;
            $num = ceil(bcdiv($i,8,2));
            $list[$key]['image'] = "https://paokucoin.com/img/backgroup/s-gxserve.gif";
            $id = pdo_fetchcolumn('select id from '.tablename('ewei_shop_devote_record').'where (user_id =:user_id or openid = :openid) and uniacid = "'.$uniacid.'" and status = 1 LIMIT '.($i-1).','.$size,[':user_id'=>$user_id,':openid'=>$member['openid']]);
            //$list[$key]['log'][] = pdo_get('ewei_shop_devote_log',['user_id'=>$user_id,'uniacid'=>$uniacid,'devote_id'=>$id,'status'=>1,'day'=>date('Y-m-d',time())])?1:0;
            $log = pdo_fetch('select * from '.tablename('').'where uniacid = :uniacid and (user_id = :user_id or openid = :openid) and devote_id = :devote_id and status = 1 and day = :day',[':uniacid'=>$uniacid,':user_id'=>$user_id,':openid'=>$member['openid'],':devote_id'=>$id,':day'=>date('Y-m-d')]);
            $list[$key]['log'][] = $log ? 1 : 0;
            $list[$key]['id'][] = $id;
            $list[$key]['count'] = $num;
            $list[$key]['is_open'] = 1;
        }
        if($total < 8){
            for ($i = 0 ; $i < 8-$total; $i++){
                array_push($list,['image'=>"https://paokucoin.com/img/backgroup/n-gxserve@2x.png",'devote'=>0,'count'=>0,'is_open'=>0,'id'=>[]]);
            }
        }
        return $list;
    }

    /**
     * 添加折扣宝增减记录
     * @param $member
     * @param $to
     * @param $money
     * @param int $type   ==1  转账者加日志   ==2  收款者加日志
     */
    public function addlog($member,$to,$money,$type=1)
    {
        global $_W;
        $data = [
            'uniacid'=>$_W['uniacid'],
            'credittype'=>"credit3",
            'openid'=>$member['openid'],
            'user_id'=>$member['id'],
            'module'=>"ewei_shopv2",
            'createtime'=>time(),
        ];
        if($type == 1){
            $data['num'] = -$money;
            $data['remark'] = "转帐给".$to['mobile'];
        }elseif ($type == 2){
            $data['num'] = $money;
            $data['remark'] = $to['nickname']."转入";
        }
        pdo_insert('mc_credits_record',$data);
        pdo_insert('ewei_shop_member_credit_record',$data);
    }

    /**
     * 加日志
     * @param $ordersn
     * @param $openid
     * @param $limit
     * @return bool
     */
    public function limit_add($ordersn,$openid,$limit)
    {
        $member = m('member')->getMember($openid);
        $data = [
            'ordersn'=>$ordersn,
            'openid'=>$member['openid'],
            'user_id'=>$member['id'],
            'lim_id'=>$limit['id'],
            'price'=>$limit['money'],
            'limit'=>$limit['limit'],
            'createtime'=>time(),
        ];
        return pdo_insert('ewei_shop_member_limit_order',$data);
    }

    /**
     * 贡献机的 领取记录
     * @param $ids
     * @param $user_id
     * @return bool
     */
    public function devotelog($ids,$user_id)
    {
        global $_W;
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($user_id);
        $day = date('Y-m-d');
        $i = 0;
        foreach ($ids as $id){
            $log = pdo_fetch('select * from '.tablename('ewei_shop_devote_log').'where devote_id = :id and openid = :openid and uniacid = :uniacid and day = :day',[':id'=>$id,':openid'=>$member['openid'],':user_id'=>$member['id'],':uniacid'=>$uniacid,':day'=>$day]);
            if($log['status'] == 1){
                continue;
            }
            pdo_update('ewei_shop_devote_log',['status'=>1],['id'=>$log['id']]);
            //pdo_query('update'.tablename('ewei_shop_devote_log').' set status = 1 where id = :id ',[':id'=>$log['id']]);
            $i+=100;
        }
        m('member')->setCredit($user_id,'credit4',$i,"贡献机领取");
        return true;
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
}

?>
