<?php
if(isset($_SERVER['REQUEST_METHOD'])){
	echo "HELLO!\n";
	exit(0);
}else if($argc < 3){
	echo "Hello!\n";
	exit(0);
}
$DEBUG = true;
function debuglog($msg){
	global $DEBUG;
	if($DEBUG == 0){
		return;
	}else if($DEBUG == 1){
		file_put_contents("/tmp/kmeans.log","Loggin start!\n\n",LOCK_EX);
		$DEBUG = 2;
	}
	file_put_contents("/tmp/kmeans.log",$msg,FILE_APPEND|LOCK_EX);
}
debuglog("COMMAND START with ".implode(",",$argv)."\n\n");
array_shift($argv);
$nPallarel = array_shift($argv);

$cmdR = array_search('uri', @array_flip(stream_get_meta_data($GLOBALS[mt_rand()]=tmpfile())));
file_put_contents($cmdR,'library(RJSONIO)
args <- commandArgs(TRUE)
vect <- read.table(args[3])
k <- kmeans(vect,as.integer(args[1]),iter.max=30)
cluster <- k$cluster[] + as.integer(args[2])
cnt  <- round(k$centers)
dist <- cnt[k$cluster[],]-vect[]
dir  <- t(apply(dist,1,function(dist){dist>=0}))
dir[dir==FALSE] <- 0
result <- as.list(NULL)
result[["center"]]   <- cnt
result[["size"]]     <- k$size
result[["cluster"]]  <- cluster
result[["distance"]] <- sqrt(apply(dist^2, 1, sum))
result[["direction"]]<- dir
result_j <- toJSON(result)
write(result_j,args[3])
');
$PID = array();
$npallarel = $nPallarel;
while ($npallarel-- > 0) {
	$pid = pcntl_fork();
	if ($pid == -1) {
		echo "FORK失敗\n";
		exit;
	}else if ($pid){
		debuglog("I am Parent(".$pid.":".$npallarel.")\n");
		$Iam = "Parent";
		$PID[] = $pid;
		debuglog("PID[]=".json_encode($PID)."\n");
		continue;
	}else{
		debuglog("I am Child(".$pid.":".$npallarel.")\n");
		$Iam = "Child";
		break;
	}
}
if ($Iam == "Parent"){
	echo pcntl_wait($status);
	debuglog("Join\n");
}else{
	$K = $argv[$npallarel + $nPallarel];
	$offsetK = 0;
	for($k = $nPallarel;$k < $npallarel + $nPallarel;$k++){
		$offsetK += $argv[$k];
	}
	$descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin は、子プロセスが読み込むパイプです。
	   1 => array("pipe", "w"),  // stdout は、子プロセスが書き込むパイプです。
	   2 => array("pipe", "w") // はファイルで、そこに書き込みます。
	);
	debuglog(          "R --slave --vanilla --quiet --file=".$cmdR." --args ".$K." ".$offsetK." ".$argv[$npallarel]."\n");
	$procR = proc_open("R --slave --vanilla --quiet --file=".$cmdR." --args ".$K." ".$offsetK." ".$argv[$npallarel], $descriptorspec, $pipes, "/tmp", NULL);
	if (is_resource($procR)) {
		fwrite($pipes[0],$vectorR[$npallarel]);
		fclose($pipes[0]);
		$stdout_j = stream_get_contents($pipes[1]);
		$stderr_j = stream_get_contents($pipes[2]);
		debuglog("stdout(".$npallarel."):\n".$stdout_j."\nstderr(".$npallarel."):\n".$stderr_j);
		fclose($pipes[1]);
		fclose($pipes[2]);
		proc_close($procR);
	}else{
		echo "[ERROR] R command";
		exit(1);
	}
	exit(0);
}
?>