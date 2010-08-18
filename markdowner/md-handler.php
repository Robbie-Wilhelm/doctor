<?php
$BASE = dirname(dirname(__FILE__));
# $BOOKS = $BASE."/books";
$LIB = $BASE."/lib";

mb_internal_encoding("UTF-8");
require_once("$LIB/handbuch.class.php");

$bookfile = $_SERVER["PATH_TRANSLATED"];

$hb=new Handbuch($bookfile);

$hb->build_toc();
$hb->chapters_and_sections();
$hb->img();

print $hb->html();

?>