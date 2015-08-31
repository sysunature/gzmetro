<?php
 class MGraph{
    public $vexs=array(); 
    public $vpyF=array();
    public $edges=array();
    public $adjMatrix;
    public $infinity = 65535;
    public $metros=array();
    public $joints=array();
    public $allRoute=array();
    public function MGraph(){
          $this->iniVex();
          $this->iniEdge();
          $this->crtAdjMatrix();
          $this->iniJoints();
		}
	public function iniVex(){
			$xml = simplexml_load_file('./metro/sites.xml');
            for($i=0;$i<count($xml->station);$i++){
                $station=trim($xml->station[$i]->name);
			    $vpf=trim($xml->station[$i]->pinyin);
			    array_push($this->vexs,$station);
			    if(!isset($this->vpyF[$vpf]))$this->vpyF[$vpf]=array();
			    array_push($this->vpyF[$vpf],$station);
            }
		}
	public function iniEdge(){
			$metros =  array('./metro/01.xml','./metro/02.xml','./metro/03.xml','./metro/03N.xml','./metro/04.xml','./metro/05.xml','./metro/06.xml','./metro/08.xml','./metro/APM.xml','./metro/gf.xml');
        	for($i=0;$i<count($metros);$i++){
        		$xml = simplexml_load_file($metros[$i]);
        		$line=array();
        		array_push($line,trim($xml->line->name));
        		$currStation='';
        		$nextStation='';
        		for($index =0;$index<count($xml->line->stations->station)-1;$index++){
        			$currStation=trim($xml->line->stations->station[$index]->name);
        			$nextStation=trim($xml->line->stations->station[$index+1]->name);
        			$this->edges["{$currStation}#{$nextStation}"]='1';
        			array_push($line,$currStation);
        		}
        		array_push($line,$nextStation);
        		array_push($this->metros,$line);
        	}
		}
	public function iniJoints(){
    	for($i=0;$i<count($this->metros);$i++){
    		for($j=$i+1;$j<count($this->metros);$j++){
    			$inter=array_intersect($this->metros[$i],$this->metros[$j]);
    			foreach($inter as $transfer){
    				array_push($this->joints,$transfer);
    			}
    		}
    	}
    	$this->joints=array_flip(array_flip($this->joints));
	}
	public function crtAdjMatrix(){
		foreach($this->vexs as $value){
			foreach($this->vexs as $cValue){
				$this->adjMatrix[$value][$cValue] = ($value == $cValue ? 0 : $this->infinity);
			}
		}
		foreach($this->edges as $key=>$value){
     	 	 $strArr=preg_split('/#/',$key);
     	 	 $startEdge=$strArr[0];
     	 	 $endEdge=$strArr[1];
     	 	 $this->adjMatrix[$startEdge][$endEdge]=$value;
     	 	 $this->adjMatrix[$endEdge][$startEdge]=$value;
     	 }
     }
 	public function traverse($pre,$dest){
		$tree_data=$dest;
		$tree_list=array();
		$list=$pre[$dest];
		foreach($list as $listVal){
			$vm=$this->traverse($pre,$listVal);
			array_push($tree_list,$vm);
		}
		return new TreeNode($tree_data,$tree_list);
	}
	public function printPaths($root){
		$path=array();
		$this->printPathsEx($root,$path);
	}
	public function printPathsEx($node,$path){
		array_push($path,$node->data);
		if(count($node->list)==0){
			array_push($this->allRoute,$path);
		}
		else{
			foreach($node->list as $bro){
				$this->printPathsEx($bro,$path);
			}
		}
	}
    public function dijkstraEx($source){
    	$srcKey=array_search($source,$this->vexs);
     	$temp=$this->vexs[$srcKey];
     	$this->vexs[$srcKey]=$this->vexs[0];
     	$this->vexs[0]=$temp;
        $final = array();
        $pre = array();
        $weight = array();
        foreach($this->adjMatrix[$this->vexs[0]] as $k=>$v){
        	if($k==$this->vexs[0])continue;
            $final[$k] = 0;
            $pre[$k] = array();
            array_push($pre[$k],$this->vexs[0]);
            $weight[$k] = $v;
        }
        $final[$this->vexs[0]] = 1;
        for($i = 0; $i<count($this->vexs); $i++){
                 $key = 0;  
                 $minDist = $this->infinity;  
                 for($j = 1; $j <count($this->vexs); $j++){  
                    $temp = $this->vexs[$j];  
                    if($final[$temp]!=1 && $weight[$temp]<$minDist){  
                        $key = $temp;  
                        $minDist = $weight[$temp];   
                    }  
                }  
                $final[$key] = 1;  
            	for($j = 0; $j <count($this->vexs); $j++){
                	$temp = $this->vexs[$j];
                	if($final[$temp]!=1){
                		if($minDist+$this->adjMatrix[$key][$temp]<$weight[$temp]){
                			while(count($pre[$temp])!=0)array_pop($pre[$temp]);
	                    	array_push($pre[$temp],$key);
	                    	$weight[$temp] = $minDist+$this->adjMatrix[$key][$temp];
	                    	continue;
                		}
                		else if($minDist+$this->adjMatrix[$key][$temp] == $weight[$temp]){
                			array_push($pre[$temp],$key);
                		}
                	}
            }
        }
        return $pre;
      }
	public function getEftvs($route){
		$viaTransfers=array();
    	array_push($viaTransfers,$route[count($route)-1]);
    	for($i=count($route)-2;$i>0;$i--){
    		$station=$route[$i];
    		if(in_array($station,$this->joints)){
    			array_push($viaTransfers,$station);
    		}
    	}
    	array_push($viaTransfers,$route[0]);
    	$viaMetros=array();
    	$viaMetrosEx=array();
    	for($i=0;$i<count($viaTransfers)-1;$i++){
    		for($j=0;$j<count($this->metros);$j++){
    			if(in_array($viaTransfers[$i],$this->metros[$j])&&in_array($viaTransfers[$i+1],$this->metros[$j])){
    				array_push($viaMetros,$this->metros[$j][0]);
    				array_push($viaMetrosEx,$this->metros[$j]);
    				break;	
    			}
    		}
    	}
    	$viaEftvMetros=array();
    	$viaEftvTrns=array();
    	$viaEftvMetrosEx=array();
    	array_push($viaEftvMetros,$viaMetros[0]);
    	array_push($viaEftvMetrosEx,$viaMetrosEx[0]);
    	$stenTrns=array();
    	array_push($stenTrns,$route[count($route)-1]);
    	for($i=0;$i<count($viaMetros)-1;$i++){
    		$curLine=$viaMetros[$i];
    		$nextLine=$viaMetros[$i+1];
    		if($curLine != $nextLine){
    			array_push($viaEftvTrns,$viaTransfers[$i+1]);
    			array_push($stenTrns,$viaTransfers[$i+1]);
    			array_push($viaEftvMetros,$nextLine);
    			array_push($viaEftvMetrosEx,$viaMetrosEx[$i+1]);
    		}
    	}
    	array_push($stenTrns,$route[0]);
    	$direction=array();
    	for($i=0;$i<count($stenTrns)-1;$i++){
    		$dire=$this->lineDirection($viaEftvMetrosEx[$i],$stenTrns[$i],$stenTrns[$i+1]);
    		array_push($direction,$dire);
    	}
    	return array($route,$viaEftvTrns,$viaEftvMetros,$direction);
	}
	public function lineDirection($realLine,$lst,$led){
		$left=-1;
		$right=-1;
		$len=count($realLine);
		for($i=1;$i<$len;$i++){
			if($realLine[$i]==$lst)
				$left=$i;
			if($realLine[$i]==$led)
				$right=$i;
		}
		if($left<$right)
			return $realLine[$len-1];
		else 
			return $realLine[1];
			
	}
    public function presentRoad($via){
     	$route=$via[0];
     	$viaEftvTrns=$via[1];
     	$viaEftvMetros=$via[2];
     	$direction=$via[3];
     	$startStation=$route[count($route)-1];
    	$endStation=$route[0];
    	$arrow="->";
    	$routeLen=count($route);
    	$trnLen=count($viaEftvTrns);
    	$road="[{$startStation}]{$arrow}[{$endStation}], 途经{$routeLen}站, 换乘{$trnLen}次."."\n"."(1)换乘路线如下:"."\n";
    	$road=$road."从[{$startStation}]乘坐";
    	$inter=$viaEftvMetros[0];
    	$road=$road."{$inter}";
    	$interStart=$startStation;
    	$interEnd='';
    	for($i=0;$i<count($viaEftvTrns);$i++){
    		$interEnd=$viaEftvTrns[$i];
    		$dire=$direction[$i];
    		$road=$road."({$dire}方向)到[{$interEnd}], 换乘";
    		$inter=$viaEftvMetros[$i+1];
    		$road=$road."$inter";
    		$interStart=$interEnd;
    	}
    	$dire=$direction[count($viaEftvTrns)];
    	$road=$road."({$dire}方向)";
    	$road=$road."到{$endStation}.";
    	$road=$road."\n"."(2)详细路线如下:"."\n";
    	$road=$road.$route[count($route)-1];
  		for($i=count($route)-2;$i>=0;$i--)
  			$road=$road."{$arrow}".$route[$i];
  		$road=$road.".\n";
    	return $road;
     }
	public function crtRoadEx($source,$destination){
    	$pre=$this->dijkstraEx($source);
    	$dstnode=$this->traverse($pre,$destination);
    	$this->printPaths($dstnode);
    }
    public function segment($keyword){
    	$pa=new PhpAnalysis();
		$pa->SetSource($keyword);
		$pa->resultType=2;
		$pa->differMax=true;
		$pa->StartAnalysis();
		return trim($pa->GetFinallyResult());
 	}
 	public function exploit($original){
 		$seg=preg_split("/[ \t]+/",$original);
 		$segn=array();
 		foreach($seg as $elem){
 			if(mb_strlen($elem,'utf8')>1 && in_array(trim($elem),$this->vexs))
 				array_push($segn,trim($elem));
 		}
 		if(isset($segn[0]) && isset($segn[1])){
 			return $segn; 
 		}else{
 			while(count($segn)!=0)array_pop($segn);
 			foreach($seg as $elem){
 				$temp=$this->py2hz($elem);
 				if($temp!=false)array_push($segn,$temp);
 			}
 			return $segn;
 		}
 	}
 	
 	public function py2hz($pronun){
 		if(isset($this->vpyF[$pronun]))
 			return $this->vpyF[$pronun];
 		else 
 			return false;
 	}
	public function allRoadEx($keyword){
		$segn=$this->exploit($this->segment($keyword));
		$source='';
		$destination='';
		$resultStr='';
		if(count($segn)<2){
			$resultStr='查询线路缺少出发站或目的站, 请纠正输入错误, 例如体育西路简化为体育西等.'."\n"."查询办法如下:\n[1]中文输入查询地铁换乘方案, 如输入:机场南到公园前"."\n"."[2]首字母输入查询地铁换乘方案, 如查询机场南到公园前, 输入:jcn去gyq";
			return $resultStr;
		}
		if(isset($segn[0])){
			if(!is_array($segn[0])){
				$source=$segn[0];
			}else{
				if(count($segn[0])>1){
					$resultStr='出发站或目的站不唯一:';
					foreach($segn[0] as $val){
						$resultStr=$resultStr.$val.'、';
					}
					$resultStr=mb_substr($resultStr,0,-1,'utf8').'. ';
					$resultStr=$resultStr.'以上地铁站读音首字母组合相同,请采用中文输入查询.';
					return $resultStr;
				}else{
					foreach($segn[0] as $val)$source=$val;
				}
			}
		}
		if(isset($segn[1])){
			if(!is_array($segn[1])){
				$destination=$segn[1];
			}else{
				if(count($segn[1])>1){
					$resultStr='出发站或目的站不唯一:';
					foreach($segn[1] as $val){
						$resultStr=$resultStr.$val.'、';
					}
					$resultStr=mb_substr($resultStr,0,-1,'utf8').'. ';
					$resultStr=$resultStr.'以上地铁站读音首字母组合相同,请采用中文输入查询.';
					return $resultStr;
				}else{
					foreach($segn[1] as $val)$destination=$val;
				}
			}
		}
    	$this->crtRoadEx($source,$destination);
    	$numTrns=$this->infinity;
    	$viaEftv=array();
    	foreach($this->allRoute as $route){
    		$via=$this->getEftvs($route);
    		$viaEftvTrns=$via[1];
    		if(count($viaEftvTrns)<$numTrns){
    			while(count($viaEftv)!=0)array_pop($viaEftv);
    			array_push($viaEftv,$via);
    			$numTrns=count($viaEftvTrns);
    			continue;
    		}
    		else if(count($viaEftvTrns)==$numTrns){
    			array_push($viaEftv,$via);
    		}
    	}
    	$road='';
    	if(count($viaEftv)>1){
    		$plan=1;
    		foreach($viaEftv as $via){
	    		$road=$road."第{$plan}换乘方案:\n";
	    		$road=$road.$this->presentRoad($via);
	    		$road=$road."\n";
	    		$plan++;
    		}
    	}else{
    		foreach($viaEftv as $via){
			    $road=$road.$this->presentRoad($via);
			    $road=$road."\n";
    		}
    	}
    	$road=mb_substr($road,0,-1,"UTF-8");
    	return $road;
    }
 }
?>