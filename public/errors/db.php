<?php
if (Config::read("System.mode") != "development") {
	echo Config::read("Error.generalErrorMessage");
	exit;
}
?>

<h3><?php echo Error::getMessage(); ?></h3>

<?php 

$params = Error::getParams();

$dbMsg = $params['errorMessage'];
$dbQuery = $params['query'];
$dbTrace = $params['trace'];
$dbValues = $params['queryValues'];

?>
<p><b>Failure:</b> <?php echo $dbMsg; ?></p>
<p><b>Trace:</b><br/><PRE><?php print_r($dbTrace); ?></PRE></p>
<p><b>Query:</b><br/><PRE><?php echo $dbQuery; ?></PRE></p>
<p><b>Values:</b><br/><PRE><?php print_r( $dbValues ); ?></PRE></p>
