<?php
if( !defined("IN_IA") )
{
    exit( "Access Denied" );
}
class Jdgoods_EweiShopV2Model
{
    //商品主图地址
    public function homeaddr(){
        $url="http://img13.360buyimg.com/n0/";
        return $url;
    }
    //商品详情图
    public function imgaddr(){
        $url="http://img13.360buyimg.com/n1/";
        return $url;
    }
    //批量获取价格
    public function batch_price($sku){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getSellPrice";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["sku"]=$sku;
        $res=$this->posturl($url, $data);
        return $res;
        
    }
    //获取可售验证
    public function sale($skuIds){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/check";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["skuIds"]=$skuIds;
        $res=$this->posturl($url, $data);
        return $res;
    }
    //获取商品上架否
    public function onsale($sku){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/skuState";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["sku"]=$sku;
        $res=$this->posturl($url, $data);
        if ($res["success"]){
            return $res["result"];
        }else{
            return false;
        }
    }
    //获取图片集
    public function img($sku){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/skuImage";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["sku"]=$sku;
        $res=$this->posturl($url, $data);
        if ($res["success"]){
            return $res["result"];
        }else{
            return false;
        }
    }
    //获取收货地址
    public function address(){
        
        $url="http://www.juheyuncang.com/api/jd/getProvince";
        
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
     
        $res=$this->posturl($url, $data);
        if ($res["success"]){
            return $res["result"];
        }else{
            return false;
        }
        
    }
    //获取市
    public function city($id){
        
        
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getCity";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["Id"]=$id;
        $res=$this->posturl($url, $data);
        if ($res["success"]){
            return $res["result"];
        }else{
            return false;
        }
      
    }
    //获取区域
    public function area($id){
        
        
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getCounty";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $data["Id"]=$id;
        $res=$this->posturl($url, $data);
        if ($res["success"]){
            return $res["result"];
        }else{
            return false;
        }
        
    }
    //查询运费
    public function freight($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getFreight";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
       return $res;
        
    }
    //下单
    public function order($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/submitOrder";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
//         var_dump($res);
        return $res;
    }
    //取消未确认订单
    public function cancel($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/cancel";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
//                  var_dump($res);
        return $res["success"];
    }
    //确认订单
    public function confirmOrder($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/confirmOrder";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        //                  var_dump($res);
        return $res;
    }
    //确认收货
    public function confirmReceived($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/confirmReceived";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        //                  var_dump($res);
        return $res;
    }
    //订单详情
    public function selectJdOrder($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/selectJdOrder";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        //                  var_dump($res);
        return $res;
    }
    //订单状态
    public function status($data){
        switch ($data){
            case 1:
                return "新单";
                break;
            case 2:
                return "等待支付";
                break;
            case 3:
                return "等待支付确认";
                break;
            case 4:
                return "延迟付款确认";
                break;
            case 5:
                return "订单暂停";
                break;
            case 6:
                return "店主最终审核";
                break;
            case 7:
                return "等待打印";
                break;
            case 8:
                return "等待出库";
                break;
            case 9:
                return "等待打包";
                break;
            case 10:
                return "等待发货";
                break;
            case 11:
                return "自提途中";
                break;
            case 12:
                return "上门提货";
                break;
            case 13:
                return "自提退款";
                break;
            case 14:
                return "确认字体";
                break;
            case 16:
                return "等待确认收货";
                break;
            case 17:
                return "配送退货";
                break;
            case 18:
                return "货到付款确认";
                break;
            case 19:
                return "已完成";
                break;
            case 21:
                return "收款确认";
                break;
            case 22:
                return "锁定";
                break;
            case 29:
                return "等待三方出库";
                break;
            case 30:
                return "等待三方发货";
                break;
            case 31:
                return "等待三方发货完成";
                break;
            default:
                return "暂时未查询到"; 
        }
    }
    //物流
    public function orderTrack($data){
        
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/orderTrack";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        //                  var_dump($res);
        return $res;
        
    }
    //检验某订单中某商品是否可以提交售后服务
    public function asCustomerDto($data){
        
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getAvailableNumberComp";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        //                  var_dump($res);
        return $res;
    }
    //根据订单号、商品编号查询支持的服务类型
    public function getCustomerExpectComp($data){
        
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getCustomerExpectComp";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        //                  var_dump($res);
        return $res;
    }
    //商品返回京东方式
    public function getWareReturnJdComp($data){
        
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getWareReturnJdComp";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        //                  var_dump($res);
        return $res;
    }
    //申请售后
    public function createAfsApply($data){
        
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/createAfsApply";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
         return $res;
    }
    //根据订单号分页查询服务单概要信息
    public function getServiceListPage($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/getServiceListPage";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        return $res;
        
    }
    //填写客户发运信息
    public function updateSendSku($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/updateSendSku";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        return $res;
    }
    //取消服务单
    public function auditCancel($data){
        $url="http://www.juheyuncang.com";
        $url=$url."/api/jd/auditCancel";
        $data["key"]="5R8f1Nb42YFn0ou2";
        $data["secret"]="F5nuTL9IRtBHcOwRFgGvEzAnvH9wvlxH";
        $res=$this->posturl($url, $data);
        return $res;
    }
    public function posturl($url,$data){
        $data  = json_encode($data);
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }
    
}