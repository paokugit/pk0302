<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
//fbb
class Help_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_notive_article') . ('ORDER BY sort DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_notive_article') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
    public function add(){
        $this->post();
    }
    public function edit(){
        $this->post();
    }
    
    public function post(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            $data = array('uniacid' => $_W['uniacid'], 'sort' => intval($_GPC['sort']), 'title' => trim($_GPC['title']),  'detail' => m('common')->html_images_a($_GPC['detail']),  'createtime' => time());
            if (!empty($id)) {
                pdo_update('ewei_shop_notive_article', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_notive_article', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('sysset/help')));
        }
        
        $notice = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_notive_article') . ' WHERE id =:id and uniacid=:uniacid  limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        include $this->template();
        
    }
    //删除
    public function delete()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        pdo_delete('ewei_shop_notive_article', array('id' =>$id));
        
        show_json(1, array('url' => referer()));
    }
}