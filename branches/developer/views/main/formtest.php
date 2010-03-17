<?php
//{preg_replace("/^([^(\[)]+)([^(\[\])]*)([(\[\])]*)/", "['$1']$2", $properties['name'])}
//print_r($_POST{preg_replace("/^([^(\[)]+)([^(\[\])]*)([(\[\])]*)/", "['$1']$2", "boxes[]")});
//$stuff = preg_replace("/^([^(\[)]+)([^(\[\])]*)([(\[\])]*)/", "['$1']$2", "boxes[]");
//echo preg_replace("", ,$stuff);
//eval("$value = $_POST{$stuff};");
/*if (eval("return $_POST".$stuff.";") === false)
{
	echo "error<br />";
}*/
//$stuff = str_replace("[]", "", "boxes[]");
//echo (count(array("boxes", "0"))-1);

//$parsed_name = str_replace("]", "", str_replace("\"", "", str_replace("'", "", str_replace("[]", "", "boxes[0][]"))));
//echo "<br />";
//var_dump(explode("[", $parsed_name));
//echo get_form_name_value($_POST, explode("[", $parsed_name));


?>

<form method="post" action="[branch]/formtest">
	<input type="text" name="hello[name]" value="" /><br />
	<input type="password" name="hello[password]" autopopulate="true" value="" /><br />
	<select name="hello[something][]" multiple="multiple" size="5">
		<option value="1">1</option>
		<option value="2">2</option>
		<option value="3">3</option>
		<option value="4">4</option>
		<option value="5">5</option>
		<option value="6">6</option>
		<option value="7">7</option>
		<option value="8">8</option>
		<option value="9">9</option>
		<option value="0">0</option>
	</select><br />
	<input type="checkbox" name="hello[boxes][]" value="1" /><br />
	<input type="checkbox" name="hello[boxes][]" value="2" /><br />
	<input type="checkbox" name="hello[boxes][]" value="3" /><br />
	<input type="submit" value="go" />
</form>

<pre>
	<?php var_dump($_POST); ?>
</pre>
