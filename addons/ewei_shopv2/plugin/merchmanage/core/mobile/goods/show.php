<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'merchmanage/core/inc/page_merchmanage.php';
class Show_EweiShopV2Page extends MerchmanageMobilePage
{
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
        $pageSize = 6;
        //第几页从第几个显示
        $psize = ($page-1)*$pageSize-$offset;
        //店铺信息
        //$store = pdo_fetch('select id,address,uniacid,merchname,salecate,logo,realname from ' . tablename('ewei_shop_merch_user') . ' where id=:merchid and uniacid=:uniacid Limit 1', array(':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchmanage']['merchid']));
        $store = pdo_fetch('select id,address,uniacid,merchname,salecate,logo,realname from ' . tablename('ewei_shop_merch_user') . ' where id=:merchid and uniacid=:uniacid Limit 1', array(':uniacid' => $_W['uniacid'], ':merchid' => 31));
        //销量salesreal  和热度ishot  以及 抵扣额度 deduct 倒序
        // 还有排序倒序（默认排序都是0  所以就没作用，当手动进行上移排序的时候才操作） 取前三个
//        $show = pdo_fetchall('select id,title,shorttitle,marketprice,deduct,total,salesreal,sort,thumb,share_title,share_icon from ' . tablename('ewei_shop_goods') .' where uniacid ="'.$_W['uniacid'].'" and merchid = 31 ORDER BY  `sort` desc ,`ishot` DESC  , `salesreal` desc , `deduct` DESC  Limit 3' );        //获取商品列表
//        $show = set_medias($show, 'thumb');
        //$list =  pdo_fetchall('select id,merchid,title,shorttitle,marketprice,deduct,total,salesreal,sort,thumb,share_title,share_icon from ' . tablename('ewei_shop_goods') . 'where uniacid = uniacid ="'.$_W['uniacid'].'" and merchid = "'.$_W['merchmanage']['merchid'].'" ORDER BY `sort` desc , `ishot` DESC, `salesreal` desc , `deduct` DESC LIMIT ' . $psize . ',' . $pageSize);
        $list =  pdo_fetchall('select id,merchid,title,shorttitle,marketprice,deduct,total,salesreal,sort,thumb,share_title,share_icon from ' . tablename('ewei_shop_goods') . 'where uniacid = uniacid ="'.$_W['uniacid'].'" and merchid = 31 ORDER BY `sort` desc , `ishot` DESC, `salesreal` desc , `deduct` DESC LIMIT ' . $psize . ',' . $pageSize);
        $list = set_medias($list, 'thumb');
        foreach ($list as $key =>$val){
            $list[$key]["isreward"] =  m('reward')->good($val['id']);
        }
        show_json(1,[ 'store'=>$store,'show'=>$show,'list'=>$list]);
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
                pdo_update('ewei_shop_goods',['sort'=>$item[1]],['id'=>$item[0],'merchid'=>31]);
            }
            pdo_commit();
        }catch (Exception $exception){
            pdo_rollback();
        }
        show_json(1);
    }

    /**
     * 添加紅包引流商品
     */
    public function addgoods(){
        header('Access-Control-Allow-Origin:*');
        global $_W;
        global $_GPC;
        if (!$_W['ispost']) {
            app_error(AppError::$RequestError);
        }
        if (!cv('goods.add') && cv('goods.edit')) {
            app_error(AppError::$PermError, '您无操作权限');
        }
        if(isset($_GPC['id'])){
            $fields = 'id, title, desc, music, total, marketprice, commission1_pay, commission2_pay, pro_type, express_name, express_price,  main, principal , address,tel,merchid,isdraft';
            $item = pdo_fetch('SELECT ' . $fields . ' FROM ' . tablename('ewei_shop_goods') . ' WHERE id = :id and uniacid = :uniacid LIMIT 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        }
        $data = [
            'title'=>$_GPC['title'],
            'desc'=>$_GPC['desc'],
            'merchid'=>$_W['merchmanage']['merchid'],
            'music'=>$_GPC['music_id'],   //背景音乐
            'total'=>$_GPC['total'],
            'marketprice'=>$_GPC['market_price'],
            'commission1_pay'=>$_GPC['commission1_pay'],   //一级分销固定金额
            'commission2_pay'=>$_GPC['commission2_pay'],   //二级分销固定金额
            'pro_type'=>$_GPC['pro_type'],                 //产品类型
            'express_name'=>$_GPC['express_name']?:"",     //快递名字
            'express_price'=>$_GPC['express_price']?:0,    //运费
            'main'=>$_GPC['main'],                         //主办方
            'principal'=>$_GPC['principal'],               //负责人
            'address'=>$_GPC['address'],
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
        pdo_insert('ewei_shop_goods',$data);
        show_json(1);
    }
}


?>