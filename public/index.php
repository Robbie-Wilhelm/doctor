<?php
$BASE = dirname(dirname(__FILE__));
$BOOKS = $BASE."/books";
$LIB = $BASE."/lib";

mb_internal_encoding("UTF-8");
require_once("$LIB/handbuch.class.php");

$book = basename($_SERVER['PATH_INFO']);
$bookfile = $BOOKS.'/'.$book;

if(!$book || (!file_exists($bookfile) && !file_exists($bookfile.".md"))){
   header("404 Not Found");
   die("BOOK not found.");
}

if(is_dir($bookfile)){
   $bookfile.="/index.md";
}elseif(is_file($bookfile.".md")){
   $bookfile.=".md";
}

$hb=new Handbuch($bookfile);

$hb->build_toc();
$hb->chapters_and_sections();
$hb->img();

print $hb->html();

?>