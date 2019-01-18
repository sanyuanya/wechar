<?php
namespace app\controllers;
use Yii;
use yii\web\Controller;
use app\commands\Helper;
class QdController extends Controller{
   
    public function actionIndex(){

           $xml_str = file_get_contents("php://input");
           file_put_contents("./qd/xml.xml", $xml_str);
//         $xml_str = file_get_contents('./qd/xml.xml');
       
           $xml = simplexml_load_string($xml_str);
           switch($xml->MsgType){
               case "event":$this->event($xml);break;
               
           }

        
    }
    
    public function event($xml){
       //修改默认时区
         date_default_timezone_set('PRC');
         switch($xml->Event){
             
             case "CLICK":
                 
                 switch($xml->EventKey){
                 //查询积分执行
                    case "c":
                    //查询签到积分记录表
                        $sql = "select integ from qd where openid='$xml->FromUserName'";
                        $res = yii::$app->db->createCommand($sql)->queryOne();
                        echo $this->fun($xml,'text','你的当前积分为'.$res['integ'].'积分');exit();
                        
                        //签到菜单执行
                    case "q":
                        
                        
                        //生成一个开始时间戳
                        $time = date("Y-m-d H:i:s",time());
                        //根据用户的openid查询签到时间记录表
                        $sql = "select * from qd1 where openid='$xml->FromUserName'";
                        $res = yii::$app->db->createCommand($sql)->queryOne();
                        //查询连续签到的天数
                        $sql1 = "select num from qd where openid='$xml->FromUserName'";
                        $data = yii::$app->db->createCommand($sql1)->queryOne();
                    
                        
                        //判断当前用户是否存在
                        if($res && $data){
                            //如果用户存在取出上一次的签到时间
                            $start = strtotime($res['addtime']);
                            //判断上一次的签到时间小于过期时间并且上一次的签到时间要在当前日期
                            if($start <= strtotime(date('Ymd').'23:59:59') && $start>strtotime(date('Ymd'))){
                                //返回提示语，表示已签到
                                     echo $this->fun($xml,'text','已签到'.$data['num'].'天');exit();
                                
                            }else{
                                //说明还没有签到
                                //当用户点击签到菜单修改签到时间记录表中当前用户的上一次的的签到时间 
                                $sql = "update qd1 set addtime='$time' where openid='$xml->FromUserName'";
                                $res = yii::$app->db->createCommand($sql)->execute();
                                //是否断签 
                                if(strtotime(date('Ymd His',time()))- $start >24*3481){
                                  $sql = "update qd set num = 1  where openid='$xml->FromUserName'";
                                  $data = yii::$app->db->createCommand($sql)->execute();
                
                                }
                               
                                
                                //修改签到积分表中的积分签到一次
                                $sql = "update qd set integ = integ+num+5, num = num+1  where openid='$xml->FromUserName'";
                                $data = yii::$app->db->createCommand($sql)->execute();
                                
                                 echo $this->fun($xml,'text','签到成功');exit();
                                
                            }
                            
                            
                        }else{
                            $sql = "insert qd1(`openid`,`addtime`) values('$xml->FromUserName','$time')";
                            $res = yii::$app->db->createCommand($sql)->execute();
      
                            $sql1 = "insert qd(`openid`,`integ`,`num`) values('$xml->FromUserName','5',1)";
                            $data = yii::$app->db->createCommand($sql1)->execute();
                            if($res && $data){
                                 echo $this->fun($xml,'text','签到成功');exit();
                            }else{
                                 echo $this->fun($xml,'text','签到失败');exit();
                            }
                            
                        }
                      
                 }
                 
         }
        
    }
    public function fun($xml,$type,$data){
    
        $str = "<xml><ToUserName><![CDATA[$xml->FromUserName]]></ToUserName><FromUserName><![CDATA[$xml->ToUserName]]></FromUserName><CreateTime>time()</CreateTime>";
        switch($type){
            case "text": $str .="<MsgType><![CDATA[$type]]></MsgType><Content><![CDATA[$data]]></Content></xml>";break;
               
        }
        return $str;
        
    }
    public function actionCd(){
        
        $button = [
            'button'=>[
                ['name'=>'签到','type'=>'click','key'=>'q'],
                ['name'=>'积分查询','type'=>'click','key'=>'c']    
            ]
        ];
        $data = json_encode($button, JSON_UNESCAPED_UNICODE);
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".Helper::getToken(); 
        Helper::getCurl($url,'post',$data);
       
    }
   
}
