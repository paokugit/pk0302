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
        $store = pdo_fetch('select id,address,uniacid,merchname,salecate,logo,realname from ' . tablename('ewei_shop_merch_user') . ' where id=:merchid and uniacid=:uniacid Limit 1', array(':uniacid' => $_W['uniacid'], ':merchid' => 31));
        //销量salesreal  和热度ishot  以及 抵扣额度 deduct 倒序
        // 还有排序倒序（默认排序都是0  所以就没作用，当手动进行上移排序的时候才操作） 取前三个
        $show = pdo_fetchall('select id,title,shorttitle,marketprice,deduct,total,salesreal,sort,thumb,share_title,share_icon from ' . tablename('ewei_shop_goods') .' where uniacid ="'.$_W['uniacid'].'" and merchid = 31 ORDER BY  `sort` desc ,`ishot` DESC  , `salesreal` desc , `deduct` DESC  Limit 3' );        //获取商品列表
        $show = set_medias($show, 'thumb');
        $list =  pdo_fetchall('select id,title,shorttitle,marketprice,deduct,total,salesreal,sort,thumb,share_title,share_icon from ' . tablename('ewei_shop_goods') . 'where uniacid = uniacid ="'.$_W['uniacid'].'" and merchid = 31 ORDER BY `sort` desc , `ishot` DESC, `salesreal` desc , `deduct` DESC LIMIT ' . $psize . ',' . $pageSize);
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
}


?>