<?PHP



class XML2wiki {
	public $prelist ;
	public $pretemplate ;
	public $remove_empty_fields ;
	
	function __construct () {
		$this->prelist = '' ;
		$this->pretemplate = '' ;
		$this->remove_empty_fields = false ;
	}
	
	function getsub ( $xml ) {
		if ( !isset ( $xml['s'] ) ) return '' ;
		return $this->convert ( $xml['s'] ) ;
	}
	
	function strip_attr_quotes ( $attr ) {
		if ( substr ( $attr , 0 , 1 ) != '"' && substr ( $attr , 0 , 1 ) != "'" ) return $attr ;
		if ( substr ( $attr , -1 , 1 ) != '"' && substr ( $attr , -1 , 1 ) != "'" ) return $attr ;
		if ( substr ( $attr , 0 , 1 ) != substr ( $attr , -1 , 1 ) ) return $attr ;
		return substr ( $attr , 1 , -1 ) ;
	}
	
	function get_link_or_template ( $xml , $is_template , $parent = NULL ) {
		if ( $is_template ) $pt = substr ( $this->pretemplate , 0 , -1 ) ;
		$ret = Array() ;
		$trail = '' ;
		$ext_url = '' ;
		$ext_name = '' ;
		$is_external_link = false ;
		if ( !$is_template && isset ( $parent ) && isset ( $parent['a']['type'] ) && $this->strip_attr_quotes($parent['a']['type']) == 'external' ) {
			$is_external_link = 1 ;
			$ext_url = $this->strip_attr_quotes ( $parent['a']['href'] ) ;
		}
		foreach ( $xml AS $x ) {
			$sub = $this->getsub ( $x ) ;
			if( !isset( $x['n'] ) ) $x['n'] = '';
			if ( $x['n'] == 'TARGET' ) {
				if ( $is_template ) $ret[] = "{{" . $sub ;
				else $ret[] = "[[$sub" ;
			} elseif ( $x['n'] == 'ARG' || $x['n'] == 'PART' ) {
				$sub = trim ( $sub ) ;
				if ( $is_template ) {
					$r = "|" ;
					if ( isset ( $x['a']['name'] ) ) {
						$keyname = $this->strip_attr_quotes ( $x['a']['name'] ) ;
						if ( !is_numeric ( $keyname ) ) $r .= $keyname . ' = ' ;
					}
					$r .= "$sub" ;
					if ( $sub != '' or !$this->remove_empty_fields ) $ret[] = $r ;
				} else {
					$ret[] = "|$sub" ;
				}
			} elseif ( $x['n'] == 'TRAIL' ) {
				$trail = $sub ;
			} elseif ( $is_external_link ) {
			} else print "OH-OH #1 : !" . $x['n'] . "!<br/>" ;
		}
		
		if ( $is_external_link ) {
			$ext_name = $this->getsub ( $parent ) ;
			if ( $ext_name != '' ) $ret[] = "[$ext_url $ext_name]" ;
			else $ret[] = $ext_url ;
		}
		
		if ( $is_template ) {
			$r = implode ( '' , $ret ) ;
			if ( strlen ( $r ) < 80 ) $ret = implode ( '' , $ret ) ;
			else $ret = implode ( "\n$pt" , $ret ) . "\n$pt" ;
		} else $ret = implode ( '' , $ret ) ;
		
		if ( $is_template ) $ret .= "}}" ;
		else if ( !$is_external_link ) $ret .= "]]" ;
		return $ret . $trail ;
	}
	
	function convert ( $xml , $depth = 0 ) {
		$ret = '' ;
		$first_tablerow = true ;
		$first_tablecell = true ;
		foreach ( $xml AS $x ) {
			if ( $x['?'] == 't' ) { // Plain text
				$ret .= $x['t'] ;
				continue ;
			}
			
			if ( $x['?'] != 'x' ) {
				print "BAD INTERNAL TYPE : '" . $x['?'] . "'!<br/>" ;
				continue ;
			}
			
			$tag = $x['n'] ;
			if ( $tag == 'PARAGRAPH' ) {
				$ret .= $this->getsub ( $x ) . "\n\n" ;
			} else if ( $tag == 'LINK' ) {
				$ret .= $this->get_link_or_template ( $x['s'] , false , $x ) ;
			} else if ( $tag == 'TEMPLATE' ) {
				$this->pretemplate .= ' ' ;
				$ret .= $this->get_link_or_template ( $x['s'] , true ) ;
				$this->pretemplate = substr ( $this->pretemplate , 0 , -1 ) ;
			} else if ( $tag == 'BOLD' ) {
				$ret .= "'''" . $this->getsub ( $x ) . "'''" ;
			} else if ( $tag == 'ITALICS' ) {
				$ret .= "''" . $this->getsub ( $x ) . "''" ;
			} else if ( $tag == 'PREBLOCK' ) {
				$ret .= $this->getsub ( $x ) . "\n" ;
			} else if ( $tag == 'PRELINE' ) {
				$ret .= " " . $this->getsub ( $x ) . "\n" ;
			} else if ( $tag == 'HEADING' ) {
				if ( isset ( $x['a']['level'] ) ) $level = $this->strip_attr_quotes ( $x['a']['level'] ) ;
				else $level = 1 ; // Paranoia fallback
				$eq = str_repeat ( '=' , $level ) ;
				$ret .= "\n$eq " . $this->getsub ( $x ) . " $eq\n" ;
			} else if ( substr ( $tag , 0 , 6 ) == 'XHTML:' ) {
				$tag = strtolower ( substr ( $tag , 6 ) ) ;
				$ret .= "<$tag" ;
				$ret .= $this->collapse_attributes ( $x ) ;
				if ( !$x['sc'] ) {
					$ret .= '>' ;
					$ret .= $this->getsub ( $x ) ;
					$ret .= "</$tag>" ;
				} else $ret .= "/>" ;
			} else if ( $tag == 'SPAN' ) {
				$ret .= "<$tag" ;
				$ret .= $this->collapse_attributes ( $x ) ;
				if ( !$x['sc'] ) {
					$ret .= '>' ;
					$ret .= $this->getsub ( $x ) ;
					$ret .= "</$tag>" ;
				} else $ret .= "/>" ;
			} else if ( $tag == 'LIST' ) {
				$type = $this->strip_attr_quotes ( $x['a']['type'] ) ;
				$ret .= "\n" ;
				if ( $type == 'bullet' ) $this->prelist .= '*' ;
				else if ( $type == 'ident' ) $this->prelist .= ':' ;
				else if ( $type == 'numbered' ) $this->prelist .= '#' ;
				else if ( $type == 'def' ) $this->prelist .= ';' ;
				$ret .= $this->getsub ( $x ) . "\n" ;
				$this->prelist = substr ( $this->prelist , 0 , -1 ) ;
				if ( $this->prelist == '' ) $ret .= "\n" ;
			} else if ( $tag == 'LISTITEM' ) {
				$ret .= "\n" . $this->prelist . ' ' . $this->getsub ( $x ) ;
			} else if ( $tag == 'DEFKEY' ) {
				$ret .= $this->getsub ( $x ) ;
			} else if ( $tag == 'DEFVAL' ) {
				$ret .= " : " . $this->getsub ( $x ) ;
			} else if ( $tag == 'TABLE' ) {
				$ret .= "\n{|" . $this->collapse_attributes ( $x ) . "\n" ;
				$ret .= $this->getsub ( $x ) ;
				$ret .= "\n|}\n\n" ;
			} else if ( $tag == 'TABLEROW' ) {
				if ( !$first_tablerow ) $ret .= "\n|-" . $this->collapse_attributes ( $x ) ;
				$ret .= "\n" ;
				$ret .= $this->getsub ( $x ) ;
				$first_tablerow = false ;
			} else if ( $tag == 'TABLECAPTION' ) {
				$ret .= "\n|+ " . $this->collapse_attributes ( $x ) ;
				$ret .= $this->getsub ( $x ) ;
			} else if ( $tag == 'TABLEHEAD' or $tag == 'TABLECELL' ) {
				if ( $tag == 'TABLEHEAD' ) $sep = '!' ;
				else if ( $tag == 'TABLECELL' ) $sep = '|' ;
				$r = $this->getsub ( $x ) ;
				$a = $this->collapse_attributes ( $x ) ;
				if ( strlen ( $r ) < 20 ) {
					if ( !$first_tablecell ) $ret .= $sep ;
					$ret .= $sep ;
				} else {
					$ret .= "\n$sep" ;
				}
				if ( trim ( $a ) != '' ) $ret .= "$a$sep" ;
				$ret .= $r ;
				
				if ( $tag == 'TABLEHEAD' ) {
					$first_tablecell = true ;
					$ret .= "\n" ;
				} else $first_tablecell = false ;
			} else if ( $tag == 'EXTENSION' ) {
				$ext = $this->strip_attr_quotes ( $x['a']['extension_name'] ) ;
				unset ( $x['a']['extension_name'] ) ;
				$ret .= "<$ext" . $this->collapse_attributes ( $x ) ;
				$ret .= '>' . $this->getsub ( $x ) . "</$ext>" ;
			} else if ( $tag == 'MAGIC_VARIABLE' ) {
				$ret .= '__' . $this->getsub ( $x ) . "__" ;
			} else if ( $tag == 'FONT' or $tag == 'SMALL' ) {
				$tag = strtolower ( $tag ) ;
				$ret .= "<$tag " . $this->collapse_attributes ( $x ) ;
				$ret .= '>' . $this->getsub ( $x ) . "</$tag>" ;
			} else {
				print "<b>UNKNOWN TAG : '$tag'! Please note this error, the project, language, and filename to <a href='http://en.wikipedia.org/wiki/User:Magnus_Manske'>the author</a> so it can be fixed!</b><br/>" ;
				$ret .= '{{CommonsHelper2 malfunction}}' ;
				$ret .= $this->getsub ( $x ) ;
			}
			
		}
		if ( $depth == 0 ) $this->post_process_wikitext ( $ret ) ;
		return $ret ;
	}
	
	function collapse_attributes ( $x ) {
		if ( !isset ( $x['a'] ) ) return '' ;
		$ret = '' ;
		foreach ( $x['a'] AS $k => $v ) {
			if ( $v == '\\' ) $ret .= " $k" ;
			else $ret .= " $k=$v" ;
		}
		return $ret ;
	}
	
	function post_process_wikitext ( &$wiki ) {
		$wiki = trim ( $wiki ) ;
		$wiki = str_replace ( '}}{{' , "}}\n{{" , $wiki ) ;
		$wiki = str_replace ( '}}[[' , "}}\n[[" , $wiki ) ;
		$wiki = str_replace ( "=\n\n*" , "=\n*" , $wiki ) ;
		$wiki = str_replace ( '<br></br>' , '<br/>' , $wiki ) ;
		$wiki = str_replace ( '<references></references>' , '<references/>' , $wiki ) ;
		
		$count = 0 ;
		do {
			$wiki = str_replace ( "\n\n\n" , "\n\n" , $wiki , $count ) ;
		} while ( $count > 0 ) ;
	}
	
}

?>