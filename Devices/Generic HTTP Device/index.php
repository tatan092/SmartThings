<?php //v1.0.20160405

$perform_authentication=false;

if ($perform_authentication) {
	$valid_passwords = array ("gate" => "gate1");
	$valid_users = array_keys($valid_passwords);
	$user = $_SERVER['PHP_AUTH_USER'];
	$pass = $_SERVER['PHP_AUTH_PW'];
	$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);
	if (!$validated) {
		header('WWW-Authenticate: Basic realm="Generic HTTP Device"');
		header('HTTP/1.0 401 Unauthorized');
		if (isset($_POST['Test'])) {
			echo "Test=Failed : ";
		}
		if (isset($_POST['GateTrigger'])) {
			echo "GateTrigger=Failed : ";
		}
		if (isset($_POST['CustomTrigger'])) {
			echo "CustomTrigger=Failed : ";
		}
		if (isset($_POST['RebootNow'])) {
			echo "RebootNow=Failed : ";
		}
		die ("Authentication Required!");
	}
}
// If code arrives here, this would be a valid user.

//BUILD ARRAY VALUES
date_default_timezone_set('America/Los_Angeles');
$rpi = array(
	"Date" => date("M jS, Y h:i:s A"),
	"Space Used" => shell_exec('df -h|grep /dev/root | awk \'{print $(NF-1)}\' | tr -d \'\n\''),
	"UpTime" => trim(substr(shell_exec('uptime'),strpos(shell_exec('uptime'), 'up')+2, strpos(shell_exec('uptime'), ',')-strpos(shell_exec('uptime'), 'up')-2)),
	"CPU" => shell_exec('grep \'cpu \' /proc/stat | awk \'{usage=($2+$4)*100/($2+$4+$5)} END {print usage "%"}\' | sed \'s/\(\.[0-9]\).*$/\1%/g\' | tr -d \'\n\''),
	"CPU Temp" => shell_exec('sudo vcgencmd measure_temp | sed "s/temp=//g" | tr -d \'\n\'').' '.round(trim(substr(shell_exec('sudo vcgencmd measure_temp | sed "s/temp=//g" | tr -d \'\n\''),0, strpos(shell_exec('sudo vcgencmd measure_temp | sed "s/temp=//g" | tr -d \'\n\''), '\'')-6))*9/5+32,1) . '\'F',
	"Free Mem" => shell_exec('free -t -h | tr -s " " | grep "Total:" | awk -F " " \'{print $4 " of " $2}\' | tr -d \'\n\'')
);

if (isset($_POST['GateTrigger'])) {
	exec("sudo gpio -g mode 4 out ; gpio -g write 4 0 ; sleep 1 ; gpio -g write 4 1");
	$rpi = $rpi + array("GateTrigger" => "Success");
}
if (isset($_POST['Test'])) {
	$rpi = $rpi + array("Test" => "Success");
}
if (isset($_POST['CustomTrigger'])) {
	$rpi = $rpi + array("CustomTrigger" => "Success");
}
if (isset($_POST['RebootNow'])) {
	shell_exec("sudo shutdown -r now");
	$rpi = $rpi + array("RebootNow" => "Success");
}

if (isset($_POST['UseJSON'])) {
	header('Content-type: application/json');
	echo json_encode($rpi, JSON_PRETTY_PRINT);
	die ();
}
?>

<html>
<head>
<meta charset="UTF-8" />
<meta name=viewport content='width=700'>
<style type='text/css'>
body, pre	 {
	max-width: 640px;
	margin: 0 auto;
	font-family: Calibri,Arial,Helvetica,sans-serif;
	background-color: #E3E3E3;
	font-size: 1.2em;
	line-height: 1.5em;
}
.btn {
	font-family: 'Open Sans';
	font-weight: bold;
	font-size: 1.2em;
	foreground-color: white;
	line-height: 3em;
	margin: 10px 0px;
	width: 240px;
	border-top: 1px solid #969696;
	background: #000000;
	background: -webkit-gradient(linear, left top, left bottom, from(#545454), to(#000000));
	background: -webkit-linear-gradient(top, #545454, #000000);
	background: -moz-linear-gradient(top, #545454, #000000);
	background: -ms-linear-gradient(top, #545454, #000000);
	background: -o-linear-gradient(top, #545454, #000000);
	padding: 5px 10px;
	-webkit-border-radius: 8px;
	-moz-border-radius: 8px;
	border-radius: 8px;
	-webkit-box-shadow: rgba(0,0,0,1) 0 1px 0;
	-moz-box-shadow: rgba(0,0,0,1) 0 1px 0;
	box-shadow: rgba(0,0,0,1) 0 1px 0;
	text-shadow: rgba(0,0,0,.4) 0 1px 0;
	color: #e3e3e3 !important;
	text-decoration: none;
	vertical-align: middle;
}
.btn:hover {
   border-top-color: #4f4f4f;
   background: #4f4f4f;
   color: #ccc;
   text-decoration:none;
}
.center {
	margin: auto;
	width: 60%;
	border: 3px solid #000000;
	padding: 10px;
	text-align: center;
}
</style>
</head>

<body>
<div class="center">
<pre>
<?php
//DATE
echo "Date=".$rpi['Date']."\n";
//SPACE USED
echo "Space Used=".$rpi['Space Used']."\n";
//CPU
echo "CPU=".$rpi['CPU']."\n";
//UPTIME
echo "UpTime=".$rpi['UpTime']."\n";
//TEMPERATURE
echo "CPU Temp=".str_replace("'","°",$rpi['CPU Temp'])."\n";
//FREE MEMORY
echo "Free Mem=".$rpi['Free Mem']."\n";

if (isset($_POST['GateTrigger'])) {
	echo "GateTrigger=Success\n";
}
if (isset($_POST['Test'])) {
	echo "Test=Success\n";
}
if (isset($_POST['CustomTrigger'])) {
	echo "CustomTrigger=Success\n";
}
if (isset($_POST['RebootNow'])) {
	echo "RebootNow=Success\n";
}
?>
</pre>

<form method="post">
	<button class="btn" name="GateTrigger">Gate Trigger</button>
	<br/>
	<button class="btn" name="CustomTrigger">Custom Trigger</button>
	<br/>
	<button class="btn" name="RebootNow" OnClick='return (confirm("Are you sure you want to reboot?"));'>Reboot Now</button>
	<br/>
	<button class="btn" name="Test">Test</button>
	<br/>
	<div class="center" style="transform: scale(1.3); -webkit-transform: scale(1.3); margin-top:30px; width:150px;border:1px solid;"><input type="checkbox" name="UseJSON" value="">&nbsp;&nbsp;&nbsp;UseJSON</input></div>
	<br/>
</form>
<div>
<a target="_blank" href="https://github.com/JZ-SmartThings/SmartThings/tree/master/Devices/Generic%20HTTP%20Device">Project on GitHub</a>
</div>

</div>
</body>
</html>