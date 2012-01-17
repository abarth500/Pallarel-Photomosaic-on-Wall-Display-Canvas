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
//$DEBUG = 4; // Display R command
//$DEBUG = 5; //Display k-means result as text
//$DEBUG = 6; //Display k-means result as image
//$DEBUG = 7; //Display all knn queries
$DEBUG = 8; //Display query bucket
if(isset($_REQUEST["DEBUG"])){
	$DEBUG = $_REQUEST["DEBUG"];
}

$TIME = FALSE;
$TIME = TRUE; //Display time

if($TIME){
	$time = array(
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
if(!isset($_REQUEST["urls"])){
	$urls = array("ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166","ws://localhost:6166");
}elseif(is_array($_REQUEST["urls"])){
	$urls = $_REQUEST["urls"];
}elseif(is_string($_REQUEST["urls"])){
	$urls = explode("|",$_REQUEST["urls"]);
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
if($DEBUG == 1){
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
if($DEBUG == 2){
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
$vectorR = "";
$vector = array();
for($y = 0; $y + 2 < $tarH;$y += $_REQUEST["C"]){
	for($x = 0; $x + 2 < $tarW;$x += $_REQUEST["C"]){
		$v = array();
		for($cx = 0; $cx < $_REQUEST["C"]; $cx++){
			for($cy = 0; $cy < $_REQUEST["C"]; $cy++){
				$rgb = imagecolorat($tar, $x + $cx, $y + $cy);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$v[]=$r;$v[]=$g;$v[]=$b;
				$vectorR .= $r." ".$g." ".$b." ";
			}
		}
		$vector[] = $v;
		$vectorR .= "\n";
	}
}
if($DEBUG == 3){
	nl2br($vectorR);
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
$fileR = array_search('uri', @array_flip(stream_get_meta_data($GLOBALS[mt_rand()]=tmpfile())));
file_put_contents($fileR,$vectorR);
$cmdR = array_search('uri', @array_flip(stream_get_meta_data($GLOBALS[mt_rand()]=tmpfile())));
$exeR = "R --slave --vanilla --quiet --file=".$cmdR." --args ".$K." ".$fileR." > /dev/null";

file_put_contents($cmdR , <<< END_RCMD
library(RJSONIO)
args <- commandArgs(TRUE)
vect <- read.table(args[2])
k <- kmeans(vect,as.integer(args[1]))
cnt  <- round(k\$centers)
dist <- cnt[k\$cluster[],]-vect[]
dir  <- t(apply(dist,1,function(dist){dist>=0}))
dir[dir==FALSE] <- 0
result <- as.list(NULL)
result[["center"]]   <- cnt
result[["size"]]     <- k\$size
result[["cluster"]]  <- k\$cluster
result[["distance"]] <- sqrt(apply(dist^2, 1, sum))
result[["direction"]]<- dir
result_j <- toJSON(result)
write(result_j,args[2])
END_RCMD
);
if($DEBUG == 4){
	echo $exeR."<br/><br/>".nl2br(file_get_contents($cmdR));
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
exec($exeR);
if($TIME){
	$time[] = array(
		"key"=>"kmeans",
		"t" => microtime(true)
	);
}
$result_j = file_get_contents($fileR);
$result = json_decode($result_j,true);
if($DEBUG == 5){
	echo $result_j;
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
if($DEBUG == 6){
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
		$x = $idx % ($tarW / $_REQUEST["C"]);
		$y = floor($idx / ($tarW / $_REQUEST["C"]));
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
		"direction"=> implode("",array_values($result["direction"][$c]))
	);
}
if($TIME){
	$time[] = array(
		"key"=>"create knn query",
		"t" => microtime(true)
	);
}
if($DEBUG == 7){
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
		echo "<tr><td colspan=3 bgcolor=\"#c7dc68\">Cluster:".$cl."</td></tr>";
		echo "<tr><td colspan=3><input type=\"button\" value=\"Query\" onclick=\"knn('".count($val["cells"]).",".$val["query"]."');\"/>:<b>".count($val["cells"])."</b>,".$val["query"]."</td></tr>";
		foreach($val["cells"] as $cls => $cell){
			echo "<tr bgcolor=\"".$bgcol."\">";
			if($cls == 0){
				echo "<td rowspan=".count($val["cells"])." bgcolor=\"#475c28\">&nbsp;</td>";
			}
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
		"key"=>"create bucker",
		"t" => microtime(true)
	);
}
if($DEBUG == 8){
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
		//alert("Server: \\n" + e.data);
	};
	function knn(msg){
		if (ws === undefined || ws.readyState != 1) {
			alert("Client: Websocket is not avaliable for writing");
			return;
		}
		ws.send(msg);
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
		echo "<tr><td colspan=3 bgcolor=\"#000000\" style=\"color:#ffffff;\"><input type=\"button\" value=\"Query\" onclick=\"knn('".$bquery."');\"/></td></tr>";
		echo "</table>";
	}
	echo $c."<br/>";
	
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
imagedestroy($tar);
if($TIME){
	$time[] = array(
		"key"=>"FINISH",
		"t" => microtime(true)
	);
}
?>