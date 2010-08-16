<?php
$SELF = dirname(__FILE__);

$input = $_SERVER["PATH_TRANSLATED"];

$content = file_get_contents($input);

$content = Markdown($content);

require($SELF."/template.html");


?>
