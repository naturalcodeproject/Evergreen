<?php

$problems = array();

if (!version_compare(phpversion(), '5.3.2', '>=')) {
    $version = phpversion();
    $problems[] = <<<EOF
        You are running PHP version "<strong>$version</strong>", but Evergreen
        needs at least PHP "<strong>5.3.2</strong>" to run.
EOF;
}


if ($problems):
?>
	<!DOCTYPE html>
	<html>
	    <head>
	        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
	        <title>Evergreen Issues</title>
	    </head>
	    <body>
			<h1>There are some issues that need to be addressed before you can run Evergreen.</h1>
			<ol>
				<?php foreach($problems as $problem): ?>
					<li><?php echo $problem; ?></li>
				<?php endforeach; ?>
			</ol>
		</body>
	</html>
<?php
	die();
endif;