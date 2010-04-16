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
$dbQuery = $params['query'];
$dbTrace = $params['trace'];

?>
<p><b>Failure:</b> <?php echo $dbMsg; ?></p>
<p><b>Trace:</b><br/><PRE><?php print_r($dbTrace); ?></PRE></p>
<p><b>Query:</b><br/><PRE><?php echo $dbQuery; ?></PRE></p>
