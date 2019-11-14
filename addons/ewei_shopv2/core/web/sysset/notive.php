<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
//fbb
class Notive_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
        
       
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_notive') . ('ORDER BY sort DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_notive') . (' WHERE 1 ' . $condition), $params);
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
            $data = array('uniacid' => $_W['uniacid'], 'sort' => intval($_GPC['sort']), 'title' => trim($_GPC['title']), 'photo' => save_media($_GPC['thumb']),  'detail' => m('common')->html_images_a($_GPC['detail']), 'type' =>0, 'createtime' => time());
            if (!empty($id)) {
                pdo_update('ewei_shop_notive', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_notive', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('sysset/notive')));
        }
        
        $notice = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_notive') . ' WHERE id =:id and uniacid=:uniacid  limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        include $this->template();
    }
    //视频添加
    public function add_video(){
        $this->post_video();
    }
    
    public function edit_video(){
        $this->post_video();
    }
    public function post_video(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        if ($_W['ispost']) {
            $data = array('uniacid' => $_W['uniacid'], 'sort' => intval($_GPC['sort']), 'title' => trim($_GPC['title']), 'photo' => save_media($_GPC['thumb']),   'type' =>1, 'createtime' => time(),'video'=>$_GPC["video"],'time'=>$_GPC["time"]);
            if (!empty($id)) {
                pdo_update('ewei_shop_notive', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_notive', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('sysset/notive')));
        }
        
        $notice = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_notive') . ' WHERE id =:id and uniacid=:uniacid  limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        $notice["video"]=tomedia($notice['video']);
        //上传视频连接
        $submitUrl = $_W['siteroot'] . ('/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=sysset.index.upload_video');
        include $this->template();
        
    }
    
    public function delete()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
       
       
            pdo_delete('ewei_shop_notive', array('id' =>$id));
        
        
        show_json(1, array('url' => referer()));
    }
    
}