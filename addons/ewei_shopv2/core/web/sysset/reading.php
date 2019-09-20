<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Reading_EweiShopV2Page extends WebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $_W['uniacid']);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_reading') . (' WHERE 1 ' . $condition.'  ORDER BY create_time DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_reading') . (' WHERE 1 ' . $condition), $params);
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
            $data = array('uniacid' => $_W['uniacid'], 'title' => trim($_GPC['title']), 'img' => save_media($_GPC['thumb']),'music' => save_media($_GPC['music']),'detail_img'=>save_media($_GPC["detail_img"]),'content' => m('common')->html_images_a($_GPC['content']),'music_title'=>trim($_GPC["music_title"]),'create_time' => time());
            if (!empty($id)) {
                pdo_update('ewei_shop_member_reading', $data, array('id' => $id));
                
            }
            else {
                pdo_insert('ewei_shop_member_reading', $data);
                $id = pdo_insertid();
                
            }
            
            show_json(1, array('url' => webUrl('sysset/reading')));
        }
        
        $notice = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_reading') . ' WHERE id =:id and uniacid=:uniacid  limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        include $this->template();
    }
    
    //删除
    public function delete(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        
        
        pdo_delete('ewei_shop_member_reading', array('id' =>$id));
        
        
        show_json(1, array('url' => referer()));
    }
    //评论
    public function comment(){
        
        global $_W;
        global $_GPC;
        $read_id=$_GPC["id"];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and read_id=:read_id';
        $params = array(':read_id' => $read_id);
        
        
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_readcomment') . (' WHERE 1 ' . $condition.'  ORDER BY create_time DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_readcomment') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        foreach ($list as $k=>$v){
            $member=pdo_get("ewei_shop_member",array("openid"=>$v["openid"]));
            $list[$k]["nickname"]=$member["nickname"];
            $list[$k]["avatar"]=$member["avatar"];
        }
        include $this->template();
        
    }
    //回复
    public function replay(){
        global $_W;
        global $_GPC;
        $comment_id=$_GPC["id"];
        $comment=pdo_get("ewei_shop_member_readcomment",array("id"=>$comment_id));
        if( $_W["ispost"] ){
            $data["comment_id"]=$_POST["id"];
            $data["reply"]=$_POST["reply"];
            if (empty($data["reply"])){
                show_json(0,"回复内容不可为空");
            }
            $data["create_time"]=time();
            if (pdo_insert("ewei_shop_member_readreply",$data)){
                show_json(1,array('url'=>webUrl('sysset/reading/replay_list',array('id' => $data["comment_id"]))));
            }else{
                show_json(0);
            }
        }
        include $this->template();
    }
    //评论删除
    public function delete_comment(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        pdo_delete("ewei_shop_member_readcomment",array("id"=>$id));
        
        show_json(1, array('url' => referer()));
    }
    //回复列表
    public function replay_list(){
        global $_W;
        global $_GPC;
        $comment_id=$_GPC["id"];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and comment_id=:comment_id';
        $params = array(':comment_id' =>$comment_id);
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_readreply') . (' WHERE 1 ' . $condition.'  ORDER BY create_time DESC limit ') . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_readreply') . (' WHERE 1 ' . $condition), $params);
        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }
    //删除回复
    public function delete_reply(){
        
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        pdo_delete("ewei_shop_member_readreply",array("id"=>$id));
        
        show_json(1, array('url' => referer()));
        
    }
}