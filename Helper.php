<?php

namespace app\commands;

use yii;

class Helper {
    public static function getToken(){
        if(is_readable('./check.txt')){
            $res = file_get_contents('./check.txt');
        } 
        $data = json_decode($res,1);
        if(!$data || time()- filemtime('./check.txt') > 7200){
            $params = yii::$app->params;
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$params['appId']}&secret={$params['appSecret']}";
            $data =  file_get_contents($url);
             if(is_writeable('./check.txt')){
                
                 file_put_contents('./check.txt', $data);    
            }
        }
        return $data['access_token'];
    
    }
    
    public static function getCurl($url,$type='post',$data = []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        switch ($type){
            case "get":
                foreach($data as $key => $val){
                    $url .= "&".$key."=".$val;
                }
                break;
            case "post":
                curl_setopt($ch, CURLOPT_POST,true); curl_setopt($ch, CURLOPT_POSTFIELDS, $data);break;  
        }
        curl_setopt($ch, CURLOPT_URL,$url);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
        
    }
   
    
}
    