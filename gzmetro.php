<?php
define("TOKEN", "gzmetro");
include("mgraph.class.php");
include("treenode.class.php");
include("./phpanalysis/phpanalysis.class.php");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

	public function responseMsg()
    {
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		if (!empty($postStr)){
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $RX_TYPE = trim($postObj->MsgType);
                switch($RX_TYPE)
                {
                    case "text":
                        $resultStr = $this->handleText($postObj);
                        break;
                    case "event":
                        $resultStr = $this->handleEvent($postObj);
                        break;
                    default:
                        $resultStr = $this->responseText($postObj,"亲,抱歉,暂不提供此类服务/玫瑰");
                        break;
                }
                echo $resultStr;
        }else {
            echo "";
            exit;
        }
    }
 	
 	public function road($keyword){
 		$mgr=new MGraph();
		return $mgr->allRoadEx($keyword);
    }
    public function handleText($postObj)
    {
        $keyword = trim($postObj->Content);
		if(!empty($keyword))
        {
    		$road=$this->road($keyword);
        	return $this->responseText($postObj,$road);
        }else{
        	echo "Input something...";
        }
    }
    public function handleEvent($object)
    {
        $contentStr = "";
        switch ($object->Event)
        {
            case "subscribe":
                $contentStr = "亲,感谢你关注水墨之间."."\n"."我们为你提供广州地铁乘坐路线规划服务. "."\n"."查询方法如下:\n[1]中文输入查询地铁换乘方案, 如输入:体育西路到广州南站"."\n"."[2]首字母输入查询地铁换乘方案, 如查询机场南到公园前, 输入:jcn去gyq\n[温馨提示]为提高响应速度, 暂不支持模糊查询, 例如体育西路简化为体育西等";
                break;
            default :
                $contentStr = "Unknow Event: ".$object->Event;
                break;
        }
        $resultStr = $this->responseText($object, $contentStr);
        return $resultStr;
    }
    public function responseText($object, $content, $flag=0)
    {
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%d</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
        return $resultStr;
    }
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>