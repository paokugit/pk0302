<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "merch/core/inc/page_merch.php");
//fbb
class Shophome_EweiShopV2Page extends MerchWebPage
{
    //商家图片
    public function img(){
        global $_W;
        global $_GPC;
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $_W['uniaccount']['merchid'], ':uniacid' => $_W['uniacid']));
        $piclist=unserialize($item["shopimg"]);
        if ($_POST){
            $thumbs = $_GPC['thumbs'];
            if (empty($thumbs)){
                show_json(0, '请选择图片');
            }
            $thumbs=serialize($thumbs);
            if (pdo_update("ewei_shop_merch_user",array("shopimg"=>$thumbs),array("id"=>$_W['uniaccount']['merchid']))){
                show_json(1);
            }else{
                show_json(0,"更新失败");
            }
        }
        include $this->template();
    }
    //商家视频
    public function video(){
        global $_W;
        global $_GPC;
        $item = pdo_fetch('select * from ' . tablename('ewei_shop_merch_user') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $_W['uniaccount']['merchid'], ':uniacid' => $_W['uniacid']));
        $video=tomedia($item["shopvideo"]);
        if ($_POST){
            $data["shopvideo"]=$_GPC["video"];
            $data["img"]=$_GPC["img"];
            if (pdo_update("ewei_shop_merch_user",array("shopvideo"=>$data["shopvideo"],"shopvideo_img"=>$data["img"]),array("id"=>$_W['uniaccount']['merchid']))){
                $res["status"]=0;
                $res["message"]=tomedia($data["shopvideo"]);
                echo json_encode($res);die;
            }else{
                $res["status"]=1;
                $res["message"]="失败";
                echo json_encode($res);die;
            }
        }
        //上传视频连接
        $submitUrl = $_W['siteroot'] . ('web/merchant.php?c=site&a=entry&i=' . $_COOKIE[$_W['config']['cookie']['pre'] . '__uniacid'] . '&m=ewei_shopv2&do=web&r=sysset.shophome.upload_video');
        //更新数据库
        $update=$_W['siteroot'] . ('web/merchant.php?c=site&a=entry&i=' . $_COOKIE[$_W['config']['cookie']['pre'] . '__uniacid'] . '&m=ewei_shopv2&do=web&r=sysset.shophome.video');
        include $this->template();
    }
    
    public function  upload_video(){
        
        $field = $_FILES["file"];
        
        $resault=$this->upload_file($field);
        //成功
        if ($resault["status"]==0){
            //获取封面图
            
            //视频绝对路径
            $lujing=ATTACHMENT_ROOT.$resault["message"];
            $t=time().rand(10,99);
            $pt_lj="videos/img/".$t.".jpg";
            //保存图片绝对路径
            $pt=ATTACHMENT_ROOT.$pt_lj;
            $root=ATTACHMENT_ROOT."videos/ffmpeg.exe";
            //视频截图
            $cmd="$root -i $lujing -f image2 -ss 0.01 -s 400*300 -vframes 1 $pt";
            file_put_contents('./1.txt', $cmd);
            exec($cmd);
            file_put_contents('./1.txt', exec($cmd));
            //             if (is_file($pt)) {
            $resault["img"]=$pt_lj;
            //             }
            
        }
        echo json_encode($resault);
        
    }
    
    function upload_file($files, $path = "./attachment",$imagesExt=['rm', 'rmvb', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4'])
    
    {
        
        $path = "videos/";
        //   mkdirs(ATTACHMENT_ROOT . '/' . $path);
        
        
        // 判断错误号
        
        if (@$files['error'] == 00) {
            
            // 判断文件类型
            
            $ext = strtolower(pathinfo(@$files['name'],PATHINFO_EXTENSION));
            
            if (!in_array($ext,$imagesExt)){
                $resault["status"]=1;
                $resault["message"]="非法文件类型";
                return $resault;
                
            }
            
            // 判断是否存在上传到的目录
            
            if (!is_dir(ATTACHMENT_ROOT . '/' .$path)){
                
                mkdir($path,0777,true);
                
            }
            
            // 生成唯一的文件名
            
            $fileName = md5(uniqid(microtime(true),true)).'.'.$ext;
            
            // 将文件名拼接到指定的目录下
            
            $destName =ATTACHMENT_ROOT.'/'.$path.$fileName;
            
            // 进行文件移动
            
            if (!move_uploaded_file($files['tmp_name'],$destName)){
                
                $resault["status"]=1;
                $resault["message"]="文件上传失败！";
                return $resault;
                
            }
            $resault["status"]=0;
            $resault["message"]=$path.$fileName;
            return $resault;
            
        } else {
            
            // 根据错误号返回提示信息
            
            switch (@$files['error']) {
                
                case 1:
                    
                    echo "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                    
                    break;
                    
                case 2:
                    
                    echo "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                    
                    break;
                    
                case 3:
                    
                    echo "文件只有部分被上传";
                    
                    break;
                    
                case 4:
                    
                    echo "没有文件被上传";
                    
                    break;
                    
                case 6:
                    
                case 7:
                    
                    echo "系统错误";
                    
                    break;
                    
            }
            
        }
        
    }
}