<?php
$SELF = dirname(dirname(__FILE__));

require_once("$SELF/lib/markdownextra/markdown.php");

$input = $_SERVER["PATH_TRANSLATED"];

$content = file_get_contents($input);

$content = Markdown($content);

require($SELF."/lib/default_layout.html");


?>
