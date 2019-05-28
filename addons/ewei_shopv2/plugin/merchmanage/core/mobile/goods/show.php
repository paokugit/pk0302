<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';
class Show_EweiShopV2Page extends MerchmanageMobilePage
{
    /**
     * 橱窗页面
     */
    public function main(){
        include $this->template('merchmanage/goods/shop');
    }

    /**
     * 进入这个页面自加载接口
     */
    public function getlist()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        $offset = intval($_GPC['offset']);
        //页数
        $page = max(1,($_GPC['page']));
        //每页显示条数
        $pageSize = 20;
        //第几页从第几个显示
        $psize = ($page-1)*$pageSize-$offset;
        //店铺信息
        $store = pdo_fetch('select id,address,uniacid,merchname,salecate,logo,realname from ' . tablename('ewei_shop_merch_user') . ' where id=:merchid and uniacid=:uniacid Limit 1', array(':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchmanage']['merchid']));
        //销量salesreal  和热度ishot  以及 抵扣额度 deduct 倒序
        // 还有排序倒序（默认排序都是0  所以就没作用，当手动进行上移排序的时候才操作） 取前三个
        $list =  pdo_fetchall('select id,merchid,title,shorttitle,marketprice,deduct,total,salesreal,sort,thumb,share_title,share_icon from ' . tablename('ewei_shop_goods') . ' where uniacid = uniacid ="'.$_W['uniacid'].'" and merchid = "'.$_W['merchmanage']['merchid'].'" ORDER BY `sort` desc , `ishot` DESC, `salesreal` desc , `deduct` DESC LIMIT ' . $psize . ',' . $pageSize);
        $list = set_medias($list, 'thumb');
        foreach ($list as $key =>$val){
            $list[$key]["isreward"] =  m('reward')->good($val['id']);
        }
        $store['logo'] = tomedia($store['logo']);;
        show_json(1,[ 'store'=>$store,'list'=>$list]);
    }

    /**
     * 上移调排序
     */
    public function changesort()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        //获取参数  去除前后空格
        $id= trim($_GPC['ids']);
        if($id == NULL){
            show_json(0,"参数不正确");
        }
        //把参数分解
        $ids= explode(',',$id);
        pdo_begin();
        try{
            foreach ($ids as $item){
                $item = explode(':',$item);
                pdo_update('ewei_shop_goods',['sort'=>0],['sort'=>$item[1],'merchid'=>$_W['merchmanage']['merchid']]);
                pdo_update('ewei_shop_goods',['sort'=>$item[1]],['id'=>$item[0],'merchid'=>$_W['merchmanage']['merchid']]);
            }
            pdo_commit();
        }catch (Exception $exception){
            pdo_rollback();
        }
        show_json(1);
    }

    /**
     * 如果是编辑商品
     */
    public function editgoods(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        if(isset($_GPC['id'])){
            //查询goods部分字段
            $fields = "title,description,total,marketprice,thumb,thumb_url,commission1_pay,commission2_pay";
            $item1 = pdo_fetch(' SELECT ' .$fields. ' FROM '. tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid',[':id'=>$_GPC['id'],':uniacid'=>$_W['uniacid']]);
            //查询红包引流的全部字段
            $item2 = pdo_fetch('select * from' .tablename('ewei_shop_goods_bribe_expert').' where goods_id =:id',[':id'=>$_GPC['id']]);
            $item = array_merge($item1,$item2);
            //获取某个字段
            $item['music'] = pdo_getcolumn('ewei_shop_music',array('id'=>$item['music']),'title');
            show_json(1,['item'=>$item]);
        }else{
            show_json(0,'参数错误');
        }
    }

    /**
     * 添加紅包引流商品
     */
    public function addgoods(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        //判断提交方式  post才成功
        if (!$_W['ispost']) {
            app_error(AppError::$RequestError);
        }
        //判断权限
        if (!cv('goods.add') && cv('goods.edit')) {
            app_error(AppError::$PermError, '您无操作权限');
        }
        $data = [
            'title'=>$_GPC['title'],
            'description'=>$_GPC['desc'],
            'merchid'=>$_W['merchmanage']['merchid'],
            'uniacid'=>$_W['uniacid'],
            'total'=>$_GPC['total'],
            'marketprice'=>$_GPC['marketprice'],
            'commission1_pay'=>$_GPC['commission1_pay'],   //一级分销固定金额
            'commission2_pay'=>$_GPC['commission2_pay'],   //二级分销固定金额
        ];
        $add = [
            'music'=>$_GPC['music'],                    //背景音乐
            'pro_type'=>$_GPC['pro_type'],                 //产品类型
            'express_name'=>$_GPC['express_name']?:"",     //快递名字
            'express_price'=>$_GPC['express_price']?:0,    //运费
            'main'=>$_GPC['main'],                         //主办方
            'principal'=>$_GPC['principal'],               //负责人
            'address'=>$_GPC['address'],
            'end_time'=>strtotime($_GPC['end_time']),
            'tel'=>$_GPC['tel'],
            'isdraft'=>$_GPC['isdraft'],
        ];
        //商品主图  详情页图  奖励规则
        $thumbs = $_GPC['thumb'];
        if (is_array($thumbs)) {
            $thumb_url = array();
            foreach ($thumbs as $th) {
                $thumb_url[] = trim($th);
            }
            $data['thumb'] = save_media($thumb_url[0]);
            unset($thumb_url[0]);
            $data['thumb_url'] = serialize(m('common')->array_images($thumb_url));
        }
        if(isset($_GPC['id'])){
            pdo_update('ewei_shop_goods',$data,['id'=>$_GPC['id']]);
            pdo_update('ewei_shop_goods_bribe_expert',$add,['goods_id'=>$_GPC['id']]);
        }else{
            $data['createtime'] = time();
            pdo_insert('ewei_shop_goods',$data);
            $add['goods_id'] = pdo_insertid();
            pdo_insert('ewei_shop_goods_bribe_expert',$add);
        }
        show_json(1);
    }

    /**
     * 添加红包引流图片
     */
    public function thumb()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
    }

    /**
     * 添加背景音乐
     */
    public function addmusic()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        //判断提交方式  post才成功
        if (!$_W['ispost']) {
            app_error(AppError::$RequestError);
        }
        $data = [
            'uniacid'=>$_W['uniacid'],
            'merchid'=>$_W['merchmanage']['merchid'],
            'title'=>$_GPC['title'],
            'music'=>save_media($_GPC['music']),
            'created_at'=>time(),
        ];
        pdo_insert('ewei_shop_music',$data);
        show_json(1);
    }

    /**
     * 背景音乐列表
     */
    public function getmusic()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
       // $list = pdo_fetchall(' select * from '.tablename('ewei_shop_music').'where uniacid = :uniacid and merchid = :merchid',[':uniacid'=>$_W['uniacid'],':merchid'=>$_W['merchmanage']['merchid']]);
        $list = pdo_fetchall(' select * from '.tablename('ewei_shop_music').'where uniacid = :uniacid and status = :status',[':uniacid'=>$_W['uniacid'],':status'=>1]);
        foreach ($list as $key=>$item){
            $list[$key]['music'] = tomedia($item['music']);
        }
        show_json(1,['list'=>$list]);
    }

    /**
     * 测试
     */
    public function ceshi(){
        global $_W;
        global $_GPC;
        list(, $payment) = m('common')->public_build();
        if( is_error($payment) )
        {
            return $payment;
        }
        //查找订单状态
        $query = pdo_fetch('select * from '.tablename('ewei_shop_goods_red_log').' where order_sn="'.$_GPC['order_sn'].'"');
        if($query['status'] == 2){
            show_json(0,'该订单已处理');
        }
        $params = [
            'desc'=>'测试提现备注',
            'order_sn'=>$query['order_sn'],
            'user_name'=>'郝成程',
            'fee'=>$query['money'],
            'openid'=>$query['openid'],
        ];
        //请求微信发送支付
        $res = m('user')->get_transfers($params);
        if($res['return_code'] == "SUCCESS" && $res['result_code'] == "SUCCESS"){
            pdo_begin();
            try{
                //更新订单状态
                pdo_update('ewei_shop_goods_red_log',['status'=>2],['order_sn'=>$params['order_sn']]);
                $openid = 'sns_wa_'.$params['openid'];
                $credit = pdo_getcolumn('ewei_shop_member',['openid'=>$openid],'credit2');
                $credit2 = bcmul($credit,$params['fee'],2);
                //更新用户余额
                pdo_update('ewei_shop_member',['credit2'=>$credit2],['openid'=>$openid]);
                //添加操作日志
                $logno = "RW".date(YmdHis).random(6,true);
                $data = [
                    'uniacid'=>$_W['uniacid'],
                    'type'=>1,
                    'openid'=>$openid,
                    'title'=>'会员提现',
                    'logno'=>$logno,
                    'status'=>1,
                    'createtime'=>time(),
                    'money'=>$params['fee'],
                ];
                pdo_insert('ewei_shop_member_log',$data);
                pdo_commit();
            }catch (Exception $exception){
                pdo_rollback();
            }
        }
    }

    /**
     * 草稿箱和已发布
     */
    public function email()
    {
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        //end_time  小于  当前时间  就是一结束  isdraft  = 1
        $end = pdo_fetchall('select g.title as gtitle,g.description,marketprice,g.total,g.thumb,g.thumb_url,
            g.commission1_pay,commission2_pay,g.createtime,g.forwardcount,b.*,m.title from '. tablename('ewei_shop_goods').
            'g join'.tablename('ewei_shop_goods_bribe_expert'). ('b on b.goods_id=g.id').' join'.
            tablename('ewei_shop_music').('m on m.id=b.music').' where isdraft= 1 and end_time < "'.time().'"');
        //end_time  大于  当前时间  进行中  isdraft  = 1
        $ing = pdo_fetchall('select g.title as gtitle,g.description,marketprice,g.total,g.thumb,g.thumb_url,
            g.commission1_pay,commission2_pay,g.createtime,g.forwardcount,b.*,m.title from '. tablename('ewei_shop_goods').
            'g join'.tablename('ewei_shop_goods_bribe_expert'). ('b on b.goods_id=g.id').' join'.
            tablename('ewei_shop_music').('m on m.id=b.music').' where isdraft= 1 and `end_time` > "'.time().'"');
        //  isdraft  = 0  草稿箱
        $draft = pdo_fetchall('select g.title as gtitle,g.description,marketprice,g.total,g.thumb,g.thumb_url,
            g.commission1_pay,commission2_pay,g.createtime,b.*,m.title from '. tablename('ewei_shop_goods').
            'g join'.tablename('ewei_shop_goods_bribe_expert'). ('b on b.goods_id=g.id').' join'.
            tablename('ewei_shop_music').('m on m.id=b.music').' where isdraft= 0');
        //计算参与人数和交易订单数
        $ing = m('goods')->count($ing);
        $end = m('goods')->count($end);
        show_json(1,['end'=>$end,'ing'=>$ing,'draft'=>$draft]);
    }


}
?>