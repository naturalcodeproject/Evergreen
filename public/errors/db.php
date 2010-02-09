<?php
if (Config::read("System.mode") != "development") {
	echo Config::read("Error.generalErrorMessage");
	exit;
}
?>

<h3><?php echo Error::getMessage(); ?></h3>

<?php 

$params = Error::getTriggerParams();

$dbMsg = $params['db_message'];
$dbTrace = $params['db_trace'];
$dbModel = $params['db_model'];

?>

<p><b>Model Failed:</b> <?php echo $dbModel; ?></p>
<p><b>Failure:</b> <?php echo $dbMsg; ?></p>
<p><b>Trace:</b><br/><PRE><?php echo $dbTrace; ?></PRE></p>
