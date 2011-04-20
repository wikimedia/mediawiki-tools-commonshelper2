<?PHP



class ImageInfo {
	public $data , $idata ;
	public $language , $project , $image ;
	public $thumbnail_width ;
	public $thumbnail_height ;

	function __construct ( $l , $p , $i ) {
		$this->language = $l ;
		$this->project = $p ;
		$this->image = $i ;
		$this->thumbnail_width = 128 ;
		$this->thumbnail_height = 128 ;
	}

	
	function load_data () {
		if ( isset ( $this->data ) ) return ;
		$i = urlencode ( $this->image ) ;
		$url = "http://{$this->language}.{$this->project}.org/w/api.php?action=query&titles=File:$i&prop=imageinfo&iiprop=timestamp|user|comment|url|size|sha1|mime|metadata|archivename|bitdepth|url&iilimit=500&format=php" ;
		$url .= "&iiurlwidth={$this->thumbnail_width}&iiurlheight={$this->thumbnail_height}" ;
//		print "<hr/>$url<br/>" ;
		$d = unserialize ( file_get_contents ( $url ) ) ;
		$d = $d['query']['pages'] ;
		
		$n = array_keys ( $d ) ;
		if ( $n[0] != -1 ) {
			$this->idata = $d[$n[0]]['imageinfo'][0] ;
		}
		
//		print "<pre>" ; print_r ( $this->idata ) ; print "</pre><hr/>" ;
		$this->data = $d ;
	}
	
	function exists_elsewhere ( $language , $project ) {
		$this->load_data () ;
		$size = $this->idata['size'] ;
		$sha1 = $this->idata['sha1'] ;
		$url = "http://$language.$project.org/w/api.php?action=query&generator=allimages&gailimit=1&gaiminsize=$size&gaimaxsize=$size&gaisha1=$sha1&prop=imageinfo&format=php" ;
//		print $sha1 . "<br/>" . htmlspecialchars ( $url ) . "<hr/>" ;
		$d = unserialize ( file_get_contents ( $url ) ) ;
		if ( !isset ( $d['query'] ) ) return '' ; // Does not exist
		$d = array_shift ( $d['query']['pages'] ) ;
		return $d['title'] ;
//		print "<pre>" ; print_r ( $d ) ; print "</pre><hr/>" ;
	}
	

	function file_exists () {
		$this->load_data () ;
		if ( !isset ( $this->data ) ) return false ;
		if ( isset ( $this->data[-1] ) ) return false ;
		return true ;
	}
	
	function get_thumbnail_img ( $tw = 128 , $th = -1 ) {
		$this->load_data () ;
		if ( $th == -1 ) $th = $tw ;
		if ( !$this->file_exists() ) return 'ERR' ; // FIXME some error image
		$ow = $this->idata['width'] ;
		$oh = $this->idata['height'] ;
		if ( !isset ( $ow ) or $ow == 0 or !isset ( $oh ) or $oh == 0 ) return 'DEF' ; // FIXME some default icon

		$desc_url = $this->get_description_page_url() ;
		$url = $this->idata['thumburl'] ;
		$img = "<img border='0' src='$url' />" ;
		$img = "<a href='$desc_url'>$img</a>" ;
		$text = "{$this->language}" ;
		if ( $this->language != 'commons' ) $text .= ".{$this->project}" ;
		$text .= "<br/>" . $this->idata['size'] . " bytes" ;
		$text .= "<br/>" . $this->idata['width'] . "&times;"  . $this->idata['height'] ;
		$div = "<div style='margin:2px;padding:2px;border:2px solid #DDDDDD;background:#EEEEEE'>$img<br/>$text</div>" ;
		return $div ;
	}
	
	function is_identical ( $i2 ) {
		if ( $this->idata['size'] != $i2->idata['size'] ) return false ;
		if ( $this->idata['sha1'] != $i2->idata['sha1'] ) return false ;
		return true ;
	}
	
	function get_description_page_url () {
		return "http://{$this->language}.{$this->project}.org/wiki/File:" . urlencode ( str_replace ( ' ' , '_' , $this->image ) ) ;
	}
	
	function get_upload_history () {
		$i = urlencode ( $this->image ) ;
		$url = "http://{$this->language}.{$this->project}.org/w/api.php?action=query&titles=File:$i&prop=imageinfo&iilimit=500&iiprop=timestamp|user|comment|size&format=php" ;
		$d = unserialize ( file_get_contents ( $url ) ) ;
		$d = $d['query']['pages'] ;
		$d = array_shift ( $d ) ;
		$d = $d['imageinfo'] ;
		
		$source_name = ($this->language == 'commons' && $this->project == 'wikimedia')? 'Commons' : $this->language.'.'.$this->project;
		$target_name = ($this->language == 'commons' && $this->project == 'wikimedia')? 'this project' : 'Commons';
		
		$desc_url = $this->get_description_page_url() ;
		$w = "== {{Original upload log}} ==\n" ;
		$w .= "This file was originally uploaded at {$source_name} as [$desc_url {$this->image}], before it was transferred to {$target_name}.\n\n" ;
		$w .= "Upload date | User | Bytes | Dimensions | Comment\n\n" ;
		
		foreach ( $d AS $u ) {
			$date = str_replace ( 'Z' , '' , $u['timestamp'] ) ;
			$date = str_replace ( 'T' , ' ' , $date ) ;
			$user = ':' . $this->language . ':User:' . $u['user'] . '|' . $u['user'] ;
			if ( $this->project != 'wikipedia' ) $user = ':' . $this->project . $user ;
			$w .= "*" ;
			$w .= $date.' | ' ;
			$w .= '[[' . $user . ']] | ' ;
			$w .= $u['size'].' | ' ;
			$w .= $u['width'] . '&times;' . $u['height'].' | ' ;
			$w .= '<small><nowiki>' . str_replace ( "\n" , ' ' , $u['comment'] ) ;
			$w .= "</nowiki></small>\n" ;
		}
		
		$w .= "\n" ;
		return $w ;
	}
	
	function get_used_templates () {
		$i = urlencode ( $this->image ) ;
		$url = "http://{$this->language}.{$this->project}.org/w/api.php?action=query&prop=templates&titles=File:$i&tllimit=500&format=php" ;
		$d = unserialize ( file_get_contents ( $url ) ) ;
		$d = $d['query']['pages'] ;
		$d = array_shift ( $d ) ;
		$ret = Array() ;
		foreach ( $d['templates'] AS $t ) {
			if ( $t['ns'] != 10 ) continue ; // Only "real" templates
			$t = strtolower ( array_pop ( explode ( ':' , $t['title'] , 2 ) ) ) ;
			$t = str_replace ( '_' , ' ' , $t ) ;
			$ret[$t] = $t ;
		}
		return $ret ;
	}
	
	function get_used_categories () {
		$i = urlencode ( $this->image ) ;
		$url = "http://{$this->language}.{$this->project}.org/w/api.php?action=query&prop=categories&titles=File:$i&tllimit=500&format=php" ;
		$d = unserialize ( file_get_contents ( $url ) ) ;
		$d = $d['query']['pages'] ;
		$d = array_shift ( $d ) ;
		$ret = Array() ;
		foreach ( $d['categories'] AS $t ) {
			$c = $t['title'] ;
			$c = ucfirst ( array_pop ( explode ( ':' , $c ) ) ) ;
			$ret[] = $c ;
		}
		return $ret ;
	}
	
	// Untested!!!
	function common_sense ( $lang = '', $image = '' ) {
		global $forbidden_commonsense_categories ;
		
		$lang2 = ( $lang == '' )? $this->language : $lang ;
		$go = 'clean' ;
		if ( $this->language != 'commons' ) {
			$lang2 .= '.'.$this->project ;
			$go = 'move' ;
		}
		
		$image = ( $image == '' )? $this->image : $image ;
		
		$url = 'http://tools.wikimedia.de/~daniel/WikiSense/CommonSense.php?u=en&' .
			'i=' . urlencode ( $image ) .
			'&kw=' . 
			'&r=on' .
			'&p=_20' .
			'&cl=' .
			'&w=' .	$lang2 .
			'&go-' . $go . '=Find+Categories' . # was : go-move
			'&v=0' ;
		$text = @file_get_contents ( $url ) ;
		$bot = explode ( "\n" , utf8_decode ( $text ) ) ;
		$group = "" ;
		$cats = array () ;
		foreach ( $bot AS $l ) {
			$l = ucfirst ( trim ( $l ) ) ;
			if ( substr ( $l , 0 , 1 ) == '#' ) { # Set new group
				$group = explode ( ' ' , substr ( $l , 1 ) ) ;
				$group = array_shift ( $group ) ;
				$did_this_category = array() ;
				continue ;
			}
			if ( $group != 'CATEGORIES' ) continue ;
			if ( trim ( $l ) == "" ) continue ; # No blank category
			
			$l2 = $group . '#' . $l ;
			if ( isset ( $did_this_category[$l2] ) ) continue ; # Each category only once
			$l = str_replace ( '_' , ' ' , $l ) ;
			if ( in_array ( trim ( strtolower ( $l ) ) , $forbidden_commonsense_categories ) ) continue ;
			$did_this_category[$l2] = 1 ;
			if ( !isset ( $cats[$group] ) ) $cats[$group] = array () ;
			$cats[$group][] = $l ;
		}
		
		if ( !isset ( $cats['CATEGORIES'] ) ) return array() ;
		return $cats['CATEGORIES'] ;

		// return $cats ;
	}

	public function get_information_data() {
		$i = urlencode ( $this->image ) ;
		$url = "http://{$this->language}.{$this->project}.org/w/api.php?action=query&titles=File:$i&prop=imageinfo&iiprop=timestamp|user&format=php" ;
		$d = unserialize ( file_get_contents ( $url ) ) ;
		$d = $d['query']['pages'] ;
		$d = array_shift ( $d ) ;
		$d = $d['imageinfo'] ;
		$d = array_shift( $d );
		$date = str_replace ( 'Z' , '' , $d['timestamp'] ) ;
		$date = str_replace ( 'T' , ' ' , $date ) ;
		$user = ':' . $this->language . ':User:' . $d['user'] . '|' . $d['user'] ;
		if ( $this->project != 'wikipedia' ) $user = ':' . $this->project . $user ;
		$user_wp = $d['user']; // wp = without prefix
		
		return array( 'date' => $date, 'user' => $user, 'project' => $this->project, 'lang' => $this->language, 'user_wp' => $user_wp );
	}
}



?>