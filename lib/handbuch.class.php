<?php
require_once("$LIB/phpQuery-onefile.php");
require_once("$LIB/markdownextra/markdown.php");


class Handbuch{
   
   public $meta=array();
	public $doc_meta=array();
	
   public $src;
   public $html;
   public $dom;
   public $tocL;
   public $toc;
   public $docID;
   public $mediapath;
   public $source_dir;
   public $levels;
   
   function __construct($src, $mp=null, $src_dir=null){
      $this->mediapath = $mp;
      $this->source_dir = $src_dir;
      
      list($meta, $src) = explode("\n\n", $src, 2);
      foreach(explode("\n", $meta) as $line){
         list($k, $v) = explode(":", $line, 2);
			list($dk, $mk) = explode('-', trim($k), 2);
			if($dk=='doc'){
				$this->doc_meta[$mk] = trim($v);
			}else{
				$this->meta[$dk.$mk] = trim($v);
			}
      }

      $src = $this->fetch_includes($src);

		$layout = "default_layout.html";
      if(true){
         $this->html = $this->layout($layout, array("content"=>Markdown($src)));
      }else{
          $this->html = '<div id="body"><div id="cont">'.Markdown($src)."</div></div>";
      }
      
      if ($this->doc_meta['levels']) {
         $this->levels = explode(',', $this->doc_meta['levels']);
      } else {
         $this->levels = array('h1', 'h2');
      }
      
     
      $doc=phpQuery::newDocument($this->html);
      $this->docID=$doc->getDocumentID();
      
   }
   
	function layout($layout, $vars){
		extract($vars);
		ob_start();
		include($layout);
		return ob_get_clean();
	}
	
   function fetch_includes($src){
		$dir = $this->source_dir;
		if(preg_match("/^::include_scan(\s+)?([\w.]+)?/sm", $src, $mat)){
#			print_r($mat);
			$src = str_replace($mat[0], $this->scan_directory($dir), $src);
		}
		
      preg_match_all("/^::include ([\w.]+)/sm", $src, $mat, PREG_PATTERN_ORDER);
#      print_r($mat);
      
      if($mat[1]) foreach($mat[1] as $m){
         $file = $m;
         $info = pathinfo($file);
         if(!$info['extension']) $file.=".md";
         $inc = file($dir."/$file");
         if($info['extension']=='php'){
            $inc="    ".join("     ", $inc);
         }else{
            $inc=join("", $inc);
         }
         # eine ebene tiefer
         $inc = $this->fetch_includes($inc);
         $src = str_replace("::include $m", $inc, $src);
      }
      return $src;
   }
   
	function scan_directory($dir){
		return join("\n", array_map(function($f){
			return "::include ".basename($f)."\n";
		}, glob("$dir/[0-9][0-9]_*.md")));
	}
	
   function html(){
      pq("title")->html($this->meta['title']);    
/*
<meta name="author" content="Anna Lyse">
*/
      foreach($this->meta as $k=>$v){
         pq("head")->append(pq("<meta />")->attr("name", $k)->attr("content", $v));
      }
/*
<link rel="stylesheet" href="css/md-style.css" type="text/css" media="screen,print" title="no title" charset="utf-8">
*/
		if($this->doc_meta['css']){
			$css_links = explode(' ', $this->doc_meta['css']);
			foreach($css_links as $css){
				list($css, $media) = explode(';', $css, 2);
				pq("head")->append(pq("<link />")
					->attr("href", $css)
				#	->attr("media", $media)
					->attr('rel', 'stylesheet'));
			}
		}
		
		$this->assets_rewrite();
      return phpQuery::getDocument($this->docID);
   }
   
	function assets_rewrite(){
		if(!$this->doc_meta['assets']) return;
		$base = $this->doc_meta['assets'];
		
		foreach(pq("[src]") as $url){
	      $url=pq($url);
	      $url->attr("src", $base.$url->attr("src"));
	   }
	   
	   foreach(pq("link[href]") as $url){
	      $url=pq($url);
	      $url->attr("href", $base.$url->attr("href"));
	   }
	}
	
   function img(){
/*
      <div class="figure" id="fig-loaded">
        <p class="caption">The image has been loaded.</p>
        <p class="art"><img alt="[image]" src="html-18.png" style="width: 100%"/></p>
      </div>
*/      
      pq("img")->parent()->wrap('<div class="figure" />');
      
      foreach(pq("div.figure") as $img){
         $div=pq($img);
         $img=$div->find("img");
         $cap=pq('<p class="caption" />');
         $cap->html($img->attr("alt"));
         $div->find("p")->addclass("art");
         $div->prepend($cap);
         $img->attr("src", $img->attr("src"));
      }
   }
   
   function build_toc(){
/*
   <ul class="toc">
   <li class="frontmatter"><a href="#toc-h-1">Table of Contents</a></li>
   <li class="frontmatter"><a href="#preface-h-1">Preface</a></li>
   <li class="chapter"><a href="#html-h-1">The Web and HTML</a>
     <ul>
     <li class="section"><a href="#the-web">The Web</a>

       <ul>
       <li class="section"><a href="#development">Development of the Web</a></li>
       <li class="section"><a href="#images">Adding images</a></li>
            
       </ul>
     </li>
     </ul>
   </li>
*/
      $i=0; $j=0; $k=0; 
      
      $level = $this->levels;
      $clevels = join(',', $level);
      
      foreach(pq($clevels) as $h){
         $h=pq($h);
         
         $type=$h->is($level[0])?$level[0]:$level[1];
         if($h->is($level[0])){
            $i++;
            $h->attr("id", $level[0]."-{$i}")->addclass("chapter")->addclass("tocsection");
         }elseif($h->is($level[1])){
            $j++;
            $h->attr("id", $level[1]."-{$j}")->addclass("section")->addclass("tocsection");
         }else{
            $k++;
            $h->attr("id", $level[2]."-{$k}")->addclass("subsection")->addclass("tocsection");
         }
         
#         print "$type: ".$h->text()."\n";
      }
#      print "---\n";

      $cur=$cur_sub=-1;
      
      foreach(pq(".tocsection") as $h){
         $h=pq($h);

         if($h->is($level[0])){
            $cur++; $cur_sub=-1;
            $this->tocL[]=array(
               "ref"=>$h->attr("id"),
               "name"=>$h->text(),
               "sub"=>array(),
               );
         }elseif($h->is($level[1])){
            #$cur=count($this->tocL)-1;
            $cur_sub++;
            $this->tocL[$cur]['sub'][]=array(
               "ref"=>$h->attr("id"),
               "name"=>$h->text(),
               "sub"=>array(),
               );
         }else{
            $this->tocL[$cur]['sub'][$cur_sub]['sub'][]=array(
               "ref"=>$h->attr("id"),
               "name"=>$h->text(),
               );
         }
      }
#      print_r($this->tocL);
   
      $toc=pq('<ul class="toc" />');
      if($this->tocL) foreach($this->tocL as $h1){
         $li=pq('<li class="chapter"><a href="#'.$h1['ref'].'">'.$h1['name'].'</a></li>');
         # $li=pq('<li class="chapter" />')->append(pq('<a href="#'.$h1['ref'].'" />')->html($h1['name']));
         if($h1['sub']){
            $tocsub=pq("<ul />");
            foreach($h1['sub'] as $sub){
              # print $sub['name']."\n";
               $item=pq('<li class="section"><a href="#'.$sub['ref'].'">'.$sub['name'].'</a></li>');
               
               if($sub['sub']){
                  $toch3=pq("<ul />");
                  foreach($sub['sub'] as $h3){
                    # print $sub['name']."\n";
                     $h3item=pq('<li class="subsection"><a href="#'.$h3['ref'].'">'.$h3['name'].'</a></li>');
                     $toch3->append($h3item);
                  }
                  $item->append($toch3); 
               }
             # $item=pq('<li class="section" />')->append(pq('<a href="#'.$sub['ref'].'" />')->html($sub['name']));
            # print $item;
               $tocsub->append($item);
            }
            $li->append($tocsub);
         }
         $toc->append($li);
      }
      
      $tocdiv=pq('<div class="toc" id="toc-h-1"><h1>Inhalt</h1></div>');
      $tocdiv->append($toc);
      
      pq("div:first")->prepend($tocdiv);
     
      # pq("h1:last,h2:first")->wrapAll("<div id='huhu'>");
      # print pq("div:first");
   }
   
   function chapters_and_sections(){
    #  foreach(pq("h2 ~ :not(h2)") as $ch){
    #     print pq($ch)."\n--\n";
    #  }
    #  exit;
      pq("#cont h1")->wrap('<div class="chapter">');
      pq("#cont h2")->wrap('<div class="section">');
      foreach(pq('#cont > *') as $ch){
         $ch=pq($ch);
         $type=$ch->is("div.chapter")?"ch":($ch->is("div.section")?"sec":"");
#         print "nachbar ($type)\n";
         if($type=="ch"){
            $chapter=$ch;
            $section=null;
            $id=$chapter->find("h1")->attr("id");
#            print "ID $id\n";
            $chapter->find("h1")->removeAttr("id");
            $chapter->attr("id", $id);
         }elseif($type=="sec"){
            $section=$ch;
            $id=$ch->find("h2")->attr("id");
#            print "ID $id\n";
            $ch->find("h2")->removeAttr("id");
            $ch->attr("id", $id);
            if($chapter) $chapter->append($ch);
         }elseif($section){
            $section->append($ch);   
         }elseif($chapter){
            $chapter->append($ch);
         }else{
#            print $ch;
         }
      }
#      print "h2:\n";
#      foreach(pq("div.chapter") as $ch){
#         print pq($ch)."\n--\n";
#      }
   }
}
?>