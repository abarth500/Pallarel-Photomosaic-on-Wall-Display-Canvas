<?php
/*
 * Copyright (c) 2012 Shohei Yokoyama
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation 
 * files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, 
 * modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software 
 * is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES 
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT 
 * OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

$DEBUG = 0;
//$DEBUG = 1; // Display metadata of the resized image
//$DEBUG = 2; // Display the resized image
//$DEBUG = 3; // Display vector as table format
//$DEBUG = 4;// Display diff clustering
//$DEBUG = 5; // Display R command
//$DEBUG = 6; //Display k-means result as text
$DEBUG = 7; //Display k-means result as image
//$DEBUG = 8; //Display all knn queries
//$DEBUG = 9; //Display query bucket
//$DEBUG = 10; //knn Debug mode
if(isset($_REQUEST["DEBUG"])){
	$DEBUG = $_REQUEST["DEBUG"];
}
$debug = 0;//internal var of debug system

$TIME = FALSE;
$TIME = TRUE; //Display time

if($TIME){
	$time[] = array(
		"key"=>"STARTEXECUTION",
		"t" => microtime(true)
	);
}
function getTime($time,$html = true){
	$prev = 0;
	$rtn = "";
	if($html){
		$rtn .= "<table>";
	}
	foreach($time as $key => $t){
		if($prev==0){
			$prev = $t["t"];
			continue;
		}else{
			$rtn .= ($html?"<tr><td>":"").$t["key"].($html?"</td><td><b>":":").($t["t"] - $prev).($html?"</b>sec.</td></tr>":"\n");
			$prev = $t["t"];
		}
	}
	if($html){
		$rtn .= "</table>";
	}
	return $rtn;
}
if(   !isset($_REQUEST["img"])
   or !isset($_REQUEST["W"])
   or !isset($_REQUEST["H"])
   or !isset($_REQUEST["X"])
   or !isset($_REQUEST["Y"])
   or !isset($_REQUEST["M"])
   or !isset($_REQUEST["C"])
   or !isset($_REQUEST["K"])
){
	echo "No arg";
	exit(1);
}
$nParallel = 8;
if(isset($_REQUEST["P"]) and is_numeric($_REQUEST["P"])){
	$nParallel = intval($_REQUEST["P"]);
}
if(!isset($_REQUEST["urls"])){
	$urls = array("ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166");
}elseif(is_array($_REQUEST["urls"])){
	$urls = $_REQUEST["urls"];
}elseif(is_string($_REQUEST["urls"])){
	$urls = explode("|",$_REQUEST["urls"]);
	if(count($urls)==1){
		$url = $urls[0];
		for($c = 0;$c < ($_REQUEST["X"] * $_REQUEST["Y"]);$c++){
			$urls[$c] = $url;
		}
	}
}else{
	echo "No URL";
	exit(1);
}
$bucket = count($urls);
//usage: photomosaic_source_creator.php?K=100&C=3&M=75&Y=4&X=4&H=1080&W=1920&img=http://farm8.staticflickr.com/7147/6651530861_84bb405030.jpg&urls[]=ws:192.168.1.1:6166&urls[]=.......
if(is_numeric($_REQUEST["K"])){
	$K = intval($_REQUEST["K"]);
	if($K <= 1){
		echo "K must be more than 1";
			exit(1);
	}
}else{
	echo "K isn't numeric";
	exit(1);
}
$tarW = $_REQUEST["X"] * $_REQUEST["C"] * ceil($_REQUEST["W"] / $_REQUEST["M"]);
$tarH = $_REQUEST["Y"] * $_REQUEST["C"] * ceil($_REQUEST["H"] / $_REQUEST["M"]);
$filename=array_search('uri', @array_flip(stream_get_meta_data($GLOBALS[mt_rand()]=tmpfile()))); 
$img = file_get_contents($_REQUEST["img"]);
if(FALSE === $img){
	echo "No file";
	exit(1);
}
file_put_contents($filename,$img);
if($TIME){
	$time[] = array(
		"key"=>"save image file",
		"t" => microtime(true)
	);
}
$size = @getimagesize($filename);
if($size['mime'] == "image/gif"){
	$org = @imagecreatefromgif($filename);
}elseif($size['mime'] == "image/png") {
	$org = @imagecreatefrompng($filename);
}elseif($size['mime'] == "image/jpg" or $size['mime'] == "image/jpeg") {
	$org = @imagecreatefromjpeg($filename);
}else{
	echo "Wrong type";
	exit(1);
}
if(!$org){
	echo "cannot open";
	exit(1);
}
$orgW = imagesx($org);
$orgH = imagesy($org);
$r = $tarW / $orgW;
$distW = $tarW;
$distH = floor($orgH * $r);
$distX = 0;
$distY = round(($tarH - $distH) / 2);
if($distY < 0){
	$r = $tarH / $orgH;
	$distW = floor($orgW * $r);
	$distH = $tarH;
	$distX = round(($tarW - $distW) / 2);
	$distY = 0;
}

$tar = imagecreatetruecolor($tarW,$tarH);
imagecopyresized($tar,$org,$distX,$distY,0,0,$distW,$distH,$orgW,$orgH);
imagedestroy($org);
if($TIME){
	$time[] = array(
		"key"=>"resize image file",
		"t" => microtime(true)
	);
}
if($DEBUG == ++$debug){// Display metadata of the resized image
	echo "tarW:$tarW<br/>tarH:$tarH<br/>distX:$distX<br/>distY:$distY<br/>distW:$distW<br/>distH:$distH<br/>orgW:$orgW<br/>orgH:$orgH<br/>";
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		echo "<br/><br/><b>time</b><br/>";
		echo getTime($time);
	}
	exit(0);
}
if($DEBUG == ++$debug){// Display the resized image
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		$times = explode("\n",getTime($time,false));
		$c = 0;
		foreach($times as $t){
			imagestring ($tar,3,5,5 + $c++ * 15,$t,imagecolorallocate($tar,255,255,255));
		}
	}
	header('Content-Type: image/jpeg');
	imagejpeg($tar);
	exit(0);
}
$vectorR = array();
$vector = array();
$diff = "";
/**kmeans振り分けテスト仮変数*/
$KsR = array();
for($p=0;$p<$nParallel;$p++){
	$KsR[] = $K / $nParallel;
}

/*kmeans振り分けテスト仮変数*/$pp = 0;
function getDiff($v,$c){
	$center = round(($c - 1) / 2);
	$ctR = $v[($center + $center * $c) * 3];
	$ctG = $v[($center + $center * $c) * 3 + 1];
	$ctB = $v[($center + $center * $c) * 3 + 2];
	$poi = array(
		array(0,0),
		array($center,0),
		array($c-1,0),
		array($c-1,$center),
		array($c-1,$c-1),
		array($center,$c-1),
		array(0,$c-1),
		array(0,$center)
	);
	$diff = "";
	foreach($poi as $p){
		if($diff != ""){
			$diff .= " ";
		}
		$poR = $v[($p[0] + $p[1] * $c) * 3];
		$poG = $v[($p[0] + $p[1]  * $c) * 3 + 1];
		$poB = $v[($p[0] + $p[1]  * $c) * 3 + 2];
		$diff .= $ctR - $poR." ";
		$diff .= $ctG - $poG." ";
		$diff .= $ctB - $poB;
	}
	return $diff;
}
for($y = 0; $y + $_REQUEST["C"] - 1 < $tarH;$y += $_REQUEST["C"]){
	for($x = 0; $x + $_REQUEST["C"] - 1 < $tarW;$x += $_REQUEST["C"]){
		$v = array();
		if(!isset($vectorR[$pp%$nParallel])){
			$vectorR[$pp%$nParallel] = "";
		}
		for($cy = 0; $cy < $_REQUEST["C"]; $cy++){
			for($cx = 0; $cx < $_REQUEST["C"]; $cx++){
				$rgb = imagecolorat($tar, $x + $cx, $y + $cy);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$v[]=$r;$v[]=$g;$v[]=$b;
				$vectorR[$pp%$nParallel] .= $r." ".$g." ".$b." ";
			}
		}
		$diff .= getDiff($v,$_REQUEST["C"])."\n";
		$vector[] = $v;
		$vectorR[$pp%$nParallel] .= "\n";
		$pp++;
	}
}
if($DEBUG == ++$debug){// Display vector as table format
	nl2br(implode("<hr>",$vectorR));
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		echo "<br/><br/><b>time</b><br/>";
		echo getTime($time);
	}
	exit(0);
}
$cmdDiff = array_search('uri', @array_flip(stream_get_meta_data($GLOBALS[mt_rand()]=tmpfile())));
file_put_contents($cmdDiff,'library(RJSONIO)
args <- commandArgs(TRUE)
vect <- read.table("stdin")
k <- kmeans(vect,as.integer(args[1]),iter.max=30)
write(toJSON(k$cluster),"")
');
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin は、子プロセスが読み込むパイプです。
   1 => array("pipe", "w"),  // stdout は、子プロセスが書き込むパイプです。
   2 => array("pipe", "w") // はファイルで、そこに書き込みます。
);
$procDiff = proc_open("R --slave --vanilla --quiet --file=".$cmdDiff." --args ".($nParallel), $descriptorspec, $pipes, "/tmp", NULL);
//$procDiff = proc_open("R --file=".$cmdDiff." --args ".$nParallel, $descriptorspec, $pipes, "/tmp", NULL);
if (is_resource($procDiff)) {
	fwrite($pipes[0],$diff);
	fclose($pipes[0]);
	$stdout_j = stream_get_contents($pipes[1]);
	$stderr_j = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	proc_close($procDiff);
}else{
	echo "[ERROR] R command";
	exit(1);
}
$preCluster = json_decode($stdout_j,true);
$preCHist = array();
$preCSum = 0;
$vectorR = array();
$indexR = array();
foreach($preCluster as $c => $preC){
	if(!isset($vectorR[$preC - 1])){
		$vectorR[$preC - 1] = "";
		$idxR[$preC - 1] = array();
	}
	$vectorR[$preC - 1] .= implode(" ",$vector[$c])."\n";
	$indexR[$preC - 1][] = $c;
	if(!isset($preCHist[$preC - 1])){
		$preCHist[$preC - 1] = 1;
		$preCSum++;
	}else{
		$preCHist[$preC - 1]++;
		$preCSum++;
	}
}
$baseK = round($K * 0.7 / $nParallel);
$restK = $K - ($baseK * $nParallel);

arsort($preCHist);
$min = min($preCHist);

$KsR = array();
foreach($preCHist as $c => $preCh){
	$addK = round($restK * min(0.7,$preCh / $preCSum));
	$restK -= $addK;
	$preCSum -= $preCh;
	$KsR[$c] = $baseK + $addK;
}
ksort($KsR,SORT_NUMERIC);

if($TIME){
	$time[] = array(
		"key"=>"pre clustering",
		"t" => microtime(true)
	);
}

if($DEBUG == ++$debug){// Display diff clustering
	foreach($preCHist as $pchk => $pchv){
		echo "<b>".$pchk."</b>&nbsp;&nbsp;".$pchv."&nbsp;&nbsp;<b>".$KsR[$pchk]."</b><br/>";
	}
	
	echo "<b>stdout</b>:<br/>".$stdout_j;
	echo "<hr><b>stderr:</b><br/>";
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		echo "<br/><br/><b>time</b><br/>";
		echo getTime($time);
	}
	exit(0);
}
$cmdR = array();
$rooplimit = 100 * $nParallel;
for($nparallel = 0;$nparallel < $nParallel;$nparallel++){
	$cmdR[$nparallel] = sys_get_temp_dir()."/photomosaic_".uniqid();
	if(file_exists($cmdR[$nparallel])){
		if($rooplimit--){
			echo "ループしすぎ！";
			exit(1);
		}
		$nparallel--;
		continue;
	}
	file_put_contents($cmdR[$nparallel] ,$vectorR[$nparallel]);
}
if($DEBUG == ++$debug){ //Display R command
	echo "<b>Commad:</b>php fork.php ".count($cmdR)." ".implode(" ",$cmdR)." ".implode(" ",$KsR)."<hr>";
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		echo "<br/><br/><b>time</b><br/>";
		echo getTime($time);
	}
	exit(0);
}
if($TIME){
	$time[] = array(
		"key"=>"create input vector",
		"t" => microtime(true)
	);
}

exec("php fork.php ".count($cmdR)." ".implode(" ",$cmdR)." ".implode(" ",$KsR));

if($TIME){
	$time[] = array(
		"key"=>"kmeans",
		"t" => microtime(true)
	);
}

$result = array();
for($nparallel = 0; $nparallel < $nParallel; $nparallel++){
	$R = json_decode(file_get_contents($cmdR[$nparallel]),true);
	if(!isset($result["center"])){
		$result["center"] = array();
	}
	$result["center"] = array_merge($result["center"],$R["center"]);
	if(!isset($result["size"])){
		$result["size"] = array();
	}
	$result["size"] = array_merge($result["size"],$R["size"]);
	if(!isset($result["cluster"])){
		$result["cluster"] = array();
	}
	$result["cluster"] = array_merge($result["cluster"],$R["cluster"]);
	if(!isset($result["distance"])){
		$result["distance"] = array();
	}
	$result["distance"] = array_merge($result["distance"],$R["distance"]);
	if(!isset($result["direction"])){
		$result["direction"] = array();
	}
	$result["direction"] = array_merge($result["direction"],$R["direction"]);
	if(!isset($result["index"])){
		$result["index"] = array();
	}
	$result["index"] = array_merge($result["index"],$indexR[$nparallel]);
	if(file_exists($cmdR[$nparallel])){
		unlink($cmdR[$nparallel]);
	}
}
if($DEBUG == ++$debug){ //Display k-means result as text
	echo json_encode($result)."<hr>";
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		echo "<br/><br/><b>time</b><br/>";
		echo getTime($time);
	}
	exit(0);
}
if($DEBUG == ++$debug){//Display k-means result as image
	function xsqrt($x, $value) {
		$count = 0; 
		do{
			$value=$value/$x; 
			$count++; 
		}while($value>=$x); 
		return $count; 
	}
	$d = floor(255 / floor($K / 7));
	$cols = array(imagecolorallocate($tar,0,0,0));
	$R=0;$G=0;$B=0;
	$mode = array(
		array($d,0,0),
		array(0,$d,0),
		array(0,0,$d),
		array($d,$d,0),
		array(0,$d,$d),
		array($d,0,$d),
		array($d,$d,$d)
	);
	$m = 0;
	for($k = 1;$k < $K; $k++){
		if($R > 255 or $G > 255 or $B > 255){
			$m++;
			$R = 0;
			$G = 0;
			$B = 0;
			$k--;
			continue;
		}
		$R += $mode[$m][0];
		$G += $mode[$m][1];
		$B += $mode[$m][2];
		$cols[] = imagecolorallocate($tar,$R,$G,$B);
	}
	shuffle($cols);
	foreach($result["cluster"] as $idx => $cluster){
		$idxx = $result["index"][$idx];
		$x = $idxx % ($tarW / $_REQUEST["C"]);
		$y = floor($idxx / ($tarW / $_REQUEST["C"]));
		imagefilledrectangle ($tar,$x*3,$y*3,$x*3+3,$y*3+3,$cols[$cluster-1]);
	}
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		$times = explode("\n",getTime($time,false));
		$c = 0;
		foreach($times as $t){
			imagestring ($tar,3,5,5 + $c++ * 15,$t,imagecolorallocate($tar,255,255,255));
		}
	}
	header('Content-Type: image/png');
	imagepng($tar);
	exit(0);
}
//$result["center"]
//$result["size"]
//$result["distance"]
//$result["direction"]
//$result["cluster"]
$knn_query = array();
for($c=0;$c<count($result["center"]);$c++){
	$query = "";
	$q = array_values($result["center"][$c]);
	foreach($q as $qv){
		$query .= sprintf("%02X",$qv);
	}
	$knn_query[] = array(
		"query" => $query,
		"k"     => $result["size"],
		"omit"  => (sprintf("%0".strlen($query)."s","0") == $query)?true:false,
		"cells" => array()
	);
}
for($c=0;$c<count($result["cluster"]);$c++){
	$cluster = $result["cluster"][$c] - 1;
	$knn_query[$cluster]["cells"][] = array(
		"distance" => $result["distance"][$c],
		"direction"=> implode("",array_values($result["direction"][$c])),
		"x" => $c % ($tarW / $_REQUEST["C"]),
		"y" => floor($c / ($tarW / $_REQUEST["C"]))
	);
}
if($TIME){
	$time[] = array(
		"key"=>"create knn query",
		"t" => microtime(true)
	);
}
if($DEBUG == ++$debug){//Display all knn queries
	print <<< ENDOFPRINT
<html>
<head>
<script language="JavaScript">
	var url = "${urls[0]}";
	ws = new WebSocket(url);
	ws.onopen = function(e) {
		alert("Client: A connection to "+ws.URL+" has been opened");
	};
	
	ws.onerror = function(e) {
		alert("Client: An error occured, see console log for more details.");
	};
	
	ws.onclose = function(e) {
		alert("Client: The connection to "+url+" was closed.");
	};
	
	ws.onmessage = function(e) {
		alert("Server: \\n" + e.data);
	};
	function knn(msg){
		if (ws === undefined || ws.readyState != 1) {
			alert("Client: Websocket is not avaliable for writing");
			return;
		}
		ws.send(msg);
	}
</script>
</head>
<body>
ENDOFPRINT;
	echo "<table>";
	$omit = array();
	foreach($knn_query as $cl => $val){
		if($val["omit"]){
			$bgcol = "#333333";
		}else{
			$bgcol = "#ffffff";
		}
		echo "<tr><td colspan=5 bgcolor=\"#c7dc68\">Cluster:".$cl."</td></tr>";
		echo "<tr><td colspan=5><input type=\"button\" value=\"Query\" onclick=\"knn('".count($val["cells"]).",".$val["query"]."');\"/>:<b>".count($val["cells"])."</b>,".$val["query"]."</td></tr>";
		foreach($val["cells"] as $cls => $cell){
			echo "<tr bgcolor=\"".$bgcol."\">";
			if($cls == 0){
				echo "<td rowspan=".count($val["cells"])." bgcolor=\"#475c28\">&nbsp;</td>";
			}
			echo "<td>x=".$cell["x"]."</td>";
			echo "<td>y=".$cell["y"]."</td>";
			echo "<td>".$cell["distance"]."</td>";
			echo "<td>".$cell["direction"]."</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
	if($TIME){
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		echo "<br/><br/><b>time</b><br/>";
		echo getTime($time);
	}
	print <<< ENDOFPRINT
</body>
</html>
ENDOFPRINT;
	exit(0);
}
$size = array();
$BUCKET = array();
$BLACK = array();
for($b = 0;$b < $bucket; $b++){
	$size[$b] = 0;
	$BUCKET[$b] = array();
}
foreach($knn_query as $cl => $val){
	if($val["omit"]){
		$BLACK[] = $val;
		continue;
	}
	$min = PHP_INT_MAX;
	$minidx = 0;
	for($b = 0;$b < $bucket; $b++){
		if($size[$b] == 0){
			$minidx = $b;
		 	break;
		}else{
			if($size[$b] < $min){
				$min = $size[$b];
				$minidx = $b;
			}
		}
	}
	$size[$minidx] += count($val["cells"]);
	$BUCKET[$minidx][$cl] = $val;
}
if($TIME){
	$time[] = array(
		"key"=>"create bucket",
		"t" => microtime(true)
	);
}
if($DEBUG == ++$debug){//Display query bucket
	$urls_js = "['".implode("','",$urls)."']";
	print <<< ENDOFPRINT
<html>
<head>
<script language="JavaScript">
	function knn(msg){
		if (wss[0] === undefined || wss[0].readyState != 1) {
			alert("Client: Websocket is not avaliable for writing");
			return;
		}
		wss[0].send(msg);
	}
	function knn_search(query){
		if(all_connect){
			for(var c in wss){
				wss[c].send(query[c]);
			}
		}
	}
	var urls = ${urls_js};
	var all_connect = false;
	var num_connect = 0;
	var wss = [];
	for(var c in urls){
		wss[c] = new WebSocket(urls[c]);
		wss[c].onopen = function(e){
			alert("Open ("+(num_connect+1)+")");
			if(++num_connect == urls.length){
				all_connect = true;
				alert("ALL CONNECT");
			}
		};
		wss[c].onerror = function(e){
			if(num_connect>0){
				num_connect--;
			}
			alert("error");
		};
		wss[c].onclose = function(e){
			if(num_connect>0){
				num_connect--;
			}
			alert("close");
		};
		wss[c].onmessage = function(e){
			var z = JSON.parse(e.data);
			alert("kNN result:\\n\\n"+e.data);
		};
	}
	function knns(msg){
		if (ws === undefined || ws.readyState != 1) {
			alert("Client: Websocket is not avaliable for writing");
			return;
		}
		var q = msg.split("\\n");
		for(var c in q){
			//alert(q[c]);
			ws.send(q[c]);
		}
	}
</script>
</head>
<body>
ENDOFPRINT;
	$query_all = array();
	foreach($BUCKET as $bid => $bucket){
		echo "<table>";
		echo "<tr><td colspan=3 bgcolor=\"#000000\" style=\"color:#ffffff;\">Bucket:".$bid."</td></tr>";
		$c = 0;
		$bquery = "";
		
		foreach($bucket as $cl => $val){
			if($bquery!=""){
				$bquery .= "\\n";
			}
			echo "<tr><td colspan=3 bgcolor=\"#c7dc68\">Cluster:".$cl."</td></tr>";
			echo "<tr><td colspan=3>Query:<b>".count($val["cells"])."</b>,".$val["query"]."</td></tr>";
			$bquery .= count($val["cells"]).",".$val["query"];
		}
		$query_all[] = $bquery;
		echo "<tr><td colspan=3 bgcolor=\"#000000\" style=\"color:#ffffff;\"><input type=\"button\" value=\"Query\" onclick=\"knn('".$bquery."');\"/></td></tr>";
		echo "</table>";
	}
	echo "<br/><br/><h3>knn Query</h3>";
	echo "<input type=\"button\" value=\"Query to all servers\" onclick=\"knn_search(['".implode("','",$query_all)."']);\"/>";
	if($TIME){
		echo "<h3>Time</h3>";
		$time[] = array(
			"key"=>"finish",
			"t" => microtime(true)
		);
		echo "<br/><br/><b>time</b><br/>";
		echo getTime($time);
	}
	print <<< ENDOFPRINT
</body>
</html>
ENDOFPRINT;
	exit(0);
}
$query_var = array();
$cells = array();
foreach($BUCKET as $bid => $bucket){
	$q_line = array();
	$cells[$bid] = array();
	foreach($bucket as $cl => $val){
		$q_line[] = array(count($val["cells"]),$val["query"]);
		$cells[$bid][$cl] = $val["cells"];
	}
	$query_var[] = $q_line;
}
imagedestroy($tar);
if($TIME){
	$time[] = array(
		"key"=>"k-means Finish",
		"t" => microtime(true)
	);
}
?><html>
<head>
<script language="JavaScript">
<?php if($TIME){ ?>
var time_kmeans = <?php echo $time[count($time)-1]["t"] - $time[0]["t"]; ?>;
<?php } ?>
var time_start = new Date() / 1000.0;
var knn_search = function (query,cells){
	var urls = <?php echo "['".implode("','",$urls)."']"; ?>;
	for(var id in urls){
		var conn = new connector(id,urls[id],query[id],cells[id]);
	}
}
var log = function(msg){
<?php if($DEBUG!=0){ ?>
	document.getElementById("debug").innerHTML += '<p>'+msg+'<p>';
<?php } ?>
}
var connector = function(id,url,query,cells){
	var wss = new WebSocket(url);
	wss.onopen = function(e){
		//log("<b>WebSocket</b>(open:"+id+")");
		var qstring = [];
		for(var c in query){
			qstring.push(query[c].join(","));
		}
		if (wss === undefined || wss.readyState != 1) {
			log("Client: Websocket is not avaliable for writing");
			return;
		}else{
			//console.log(qstring.join("\n"));
			wss.send(qstring.join("\n"));
		}
	};
	wss.onerror = function(e){
		//log("<b>WebSocket</b>(error:"+id+")");
	};
	wss.onclose = function(e){
		//log("<b>WebSocket</b>(close:"+id+")");
	};
	wss.onmessage = function(msg){
		log("<b>TIME</b>(knn:"+id+"): "+((new Date() / 1000.0) -time_start)+" sec.");
		console.log(msg.data);
		var knn_results = JSON.parse(msg.data);
		for(var c in knn_results){
			findNear(cells[c],knn_results[c]);
		}
		wss.close();
	};
}
var findNear = function(cells,knn){
	//alert(cells.length + "\n" + knn.length);
	//log(JSON.stringify(cells) + "<br/><b>" + JSON.stringify(knn)  + "</b>");
}
window.onload = function(){
	log("<b>TIME</b>(kmeans): "+time_kmeans+" sec.");
	knn_search(<?php echo str_replace(",",",\n",json_encode($query_var)); ?>,<?php echo str_replace(",",",\n",json_encode($cells)); ?>);
};
</script>
</head>
<body>&nbsp;<?php echo $DEBUG==0?'':'<div id="debug"></div>'; ?></body>
</html>