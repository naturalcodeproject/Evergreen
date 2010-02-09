<?php
if (Config::read("System.mode") != "development") {
	echo Config::read("Error.generalErrorMessage");
	exit;
}


echo "Caught exception: ".Error::getMessage();
echo "<br /><PRE>";
print_r(Error::getTrace());
echo "</PRE>";
?>