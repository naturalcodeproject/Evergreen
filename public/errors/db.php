<?php
if (Config::read("System.mode") != "development") {
	echo Config::read("Error.generalErrorMessage");
	exit;
}
?>

<h3><?php echo Error::getMessage(); ?></h3>

<?php 

$params = Error::getTriggerParams();

$dbMsg = $params['errorMessage'];
$dbTrace = $params['query'];

?>
<p><b>Failure:</b> <?php echo $dbMsg; ?></p>
<p><b>Query:</b><br/><PRE><?php echo $dbTrace; ?></PRE></p>
