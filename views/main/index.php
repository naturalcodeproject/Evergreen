<h2>High Five</h2>

<p>The framework is running, now get to work. </p>

<br><br>
<?php
	var_dump($_POST);
	echo "<br />";
	var_dump($_GET);
?>
<form name="hello" update="$_POST" method="get" action="[site]/oranges">
	<input type="text" name="name" value="" /><br />
	<input type="submit" value="go" />
</form>

