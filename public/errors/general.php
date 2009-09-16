<?php
echo "Caught exception: ".Error::getMessage();
echo "<br /><PRE>";
print_r(Error::getTrace());
echo "</PRE>";
?>