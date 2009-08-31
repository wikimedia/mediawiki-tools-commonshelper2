<?PHP


class CommonsHelper {
	public $language , $project , $file ;
	public $used_templates ;
	public $meta_tl , $meta_cat ;
	public $namespaces ;
	public $errors ;
	public $namespace_prefix ;
	public $seen_good_template ;
	public $seen_bad_template ;
	public $seen_bad_category ;
	
	function __construct ( $l , $p , $f ) {
		$this->language = $l ;
		$this->project = $p ;
		$this->file = $f ;
		$this->meta_tl = Array () ;
		$this->meta_cat = Array () ;
		$this->errors = Array () ;
		$this->namespaces = Array () ;
		$this->namespace_prefix = "File:" ;
		$this->seen_good_template = false ;
		$this->seen_bad_template = false ;
		$this->seen_bad_category = false ;
	}
	
	function get_original_wikitext () {
		$url = "http://{$this->language}.{$this->project}.org/w/index.php?action=raw&title=" . urlencode ( $this->namespace_prefix . $this->file ) ;
		return file_get_contents ( $url ) ;
	}

	function get_xml_url () {
		$url = "http://toolserver.org/~jan/wiki2xml/w2x.php?doit=1&whatsthis=articlelist&site={$this->language}.{$this->project}.org/w&output_format=xml&use_templates=none&text=" . urlencode ( $this->namespace_prefix . $this->file ) ;
		return $url ;
	}
	
	function get_xml ( $url = '' ) {
		if ( $url == '' ) $url = $this->get_xml_url() ;
		
		$xml = file_get_contents( $url ) ;

		$cnt = 0 ;
		$t = '' ;
		$xml_array = explode ( '<' , $xml ) ;
		$ret = $this->parse_xml ( $xml_array , $cnt , $t , false ) ;
		while ( count ( $ret ) == 1 && $ret[0]['?'] == 'x' && ( $ret[0]['n'] == 'ARTICLES' || $ret[0]['n'] == 'ARTICLE' ) ) $ret = $ret[0]['s'] ;

		return $ret ;
	}
	
	function parse_xml_attributes ( $attrs ) {
		$ret = Array () ;
		$attrs = trim ( $attrs ) ;
		if ( $attrs == '' ) return $ret ;
		
		$attrs .= ' ' ;
		$parts = Array () ;
		$q = '' ;
		$last_was_backslash = false ;
		$p = 0 ;
		while ( $attrs != '' ) {
			$n = substr ( $attrs , $p , 1 ) ;
			
			if ( $n == ' ' && $q == '' ) {
				$parts[] = substr ( $attrs , 0 , $p ) ;
				$attrs = trim ( substr ( $attrs , $p ) ) ;
				if ( $attrs != '' ) $attrs .= ' ' ;
				$p = 0 ;
				continue ;
			}
			
			$p++ ;
			if ( ( $n == '"' || $n == "'" ) && !$last_was_backslash ) {
				if ( $q == '' ) $q = $n ;
				elseif ( $q == $n ) $q = '' ;
				continue ;
			}
			
			if ( $n == '\\' ) {
				if ( $last_was_backslash ) $last_was_backslash = false ;
				else $last_was_backslash = true ;
				continue ;
			}
		}
		
		foreach ( $parts AS $p ) {
			$n = explode ( '=' , $p , 2 ) ;
			$key = trim ( strtolower ( $n[0] ) ) ;
			if ( count ( $n ) == 1 ) {
				$ret[$key] = '\\' ; # Impossible value to indicate missing value
			} else {
				$value = trim ( $n[1] ) ;
				$ret[$key] = $value ;
			}
		}
		
		return $ret ;
	}
	
	function parse_xml ( $xml_array , &$count , &$outer_text , $in_template ) {
		$ret = Array () ;
		if ( $outer_text != '' ) {
			$ret[] = Array (
				'?' => 't' ,
				't' => $outer_text
			) ;
			$outer_text = '' ;
		}

		while ( $count < count ( $xml_array ) ) {
			$x = $xml_array[$count++] ;
			$y = explode ( '>' , $x , 2 ) ;
			if ( count ( $y ) == 0 ) { $y[0] = '' ; $y[1] = '' ; }
			if ( count ( $y ) == 1 ) { $y[1] = '' ; }
			$tag = trim ( $y[0] ) ;
			$text = $y[1] ;

			if ( $tag == '' && $text == '' ) continue ; # Nothing for you to see here, please move along
			if ( substr ( $tag , 0 , 4 ) == '?xml' ) continue ; # Don't care
			
			$is_closing = false ;
			if ( substr ( $tag , 0 , 1 ) == '/' ) {
				$is_closing = true ;
				$tag = trim ( substr ( $tag , 1 ) ) ;
			}
			
			if ( $is_closing ) { # My work here is done
				$outer_text = $text ;
				break ;
			}
			
			$is_self_closing = false ;
			if ( substr ( $tag , -1 , 1 ) == '/' ) {
				$is_self_closing = true ;
				$tag = trim ( substr ( $tag , 0 , -1 ) ) ;
			}
			
			$y = explode ( ' ' , $tag , 2 ) ;
			if ( count ( $y ) == 1 ) $y[] = '' ;
			$tag = strtoupper ( $y[0] ) ;
			$attrs = $y[1] ;
			
			if ( $tag != '' ) {
				$n = Array (
					'?' => 'x' ,
					'n' => $tag ,
					'a' => $this->parse_xml_attributes ( $attrs ) ,
					'sc' => $is_self_closing
				) ;
				if ( !$is_closing && !$is_self_closing ) {
					$n['s'] = $this->parse_xml ( $xml_array , $count , $text , $tag == 'TEMPLATE' ) ;
				}
				$ret[] = $n ;
			}

			if ( $text != '' ) {
				$ret[] = Array (
					'?' => 't' ,
					't' => $text
				) ;
			}

		}
		
		$ret2 = Array () ;
		foreach ( $ret AS $r ) {
			if ( $r['?'] == 'x' && $r['sc'] && $r['n'] == 'SPACE' ) {
				$r = Array (
					'?' => 't' ,
					't' => ' '
				) ;
			}
			
			$cr = count ( $ret2 ) ;
			if ( $cr > 0 && $ret2[$cr-1]['?'] == 't' && $r['?'] == 't' ) {
				$ret2[$cr-1]['t'] .= $r['t'] ;
			} else $ret2[] = $r ;
		}
		
		// Expand template arguments
		if ( $in_template ) {
			foreach ( $ret2 AS $k => $v ) {
				if ( $v['?'] != 'x' ) continue ;
				if ( $v['n'] == 'TARGET' ) {
					$tn = ucfirst ( $v['s'][0]['t'] ) ;
					$v['s'][0]['t'] = $tn ; // Alters tree to enforce ufcirst template names
					$this->used_templates[$tn] = $tn ;
				}
				if ( $v['n'] != 'ARG' ) continue ;
				if ( !isset ( $v['s'] ) ) continue ;
				if ( count ( $v['s'] ) != 1 ) continue ;
				$n = $v['s'][0] ;
				if ( $n['?'] != 't' ) continue ;
				
				$text = trim ( $n['t'] ) ;
				if ( $text == '' ) continue ; // No need to parse this...
				
				$n = $this->fix_template_parameter ( $text ) ;
				if ( count ( $n ) == 1 && $n[0]['?'] == 'x' && $n[0]['n'] == 'PARAGRAPH' && isset ( $n[0]['s'] ) ) $n = $n[0]['s'] ;
				$ret2[$k]['s'] = $n ;
			}
		}
		
		return $ret2 ;
	}

	function is_information_template ( $name ) {
		return false ;
	}
	
	function fix_template_parameter ( $s ) {
		$s = urlencode ( $s ) ;
		$url = "http://toolserver.org/~magnus/wiki2xml/w2x.php?doit=1&whatsthis=wikitext&site={$this->language}.{$this->project}.org/w&output_format=xml&use_templates=none&text=" . $s ;
		return $this->get_xml ( $url ) ;
	}


	function read_meta_data () {
		if( $this->language == 'commons' && $this->project == 'wikimedia' ) return array( 'return' => true );	
		$this->get_namespaces() ;
		
		$url = "http://meta.wikipedia.org/w/index.php?action=raw&title=CommonsHelper2/Data_" . $this->language . "." . $this->project ;
		$lines = explode ( "\n" , file_get_contents ( $url ) ) ;
		$h = Array ( 2 => '' , 3 => '' ) ;
		foreach ( $lines AS $l ) {
			if ( substr ( $l , 0 , 2 ) == '==' ) {
				$k = 2 ;
				if ( substr ( $l , 0 , 3 ) == '===' ) $k = 3 ;
				else $h[3] = '' ;
				$l = strtolower ( trim ( str_replace ( '=' , '' , $l ) ) ) ;
				$h[$k] = $l ;
				if ( $h[3] != '' ) {
					if ( $h[2] == 'categories' ) $this->meta_cat[$h[3]] = Array () ;
					else if ( $h[2] == 'templates' ) $this->meta_tl[$h[3]] = Array () ;
				}
				continue ;
			}
			if ( $h[3] == '' ) continue ;
			if ( $h[2] == 'categories' ) {
				if ( substr ( $l , 0 , 1 ) != '*' ) continue ;
				$l = ucfirst ( trim ( str_replace ( '_' , ' ' , substr ( $l , 1 ) ) ) ) ;
				$this->meta_cat[$h[3]][$l] = $l ;
//				print $h[2] . ':' . $h[3] . ":: " . $this->meta_cat[$h[3]][$l] . "<br/>" ;
			} else if ( $h[2] == 'templates' ) {
				if ( $h[3] == 'transfer' ) {
					if ( substr ( $l , 0 , 1 ) != ';' ) continue ;
					$l = explode ( ':' , substr ( $l , 1 ) , 2 ) ;
					if ( count ( $l ) != 2 ) continue ;
					$tn_local = ucfirst ( trim ( $l[0] ) ) ;
					$parts = explode ( '|' , $l[1] ) ;
					$tn_commons = ucfirst ( trim ( array_shift ( $parts ) ) ) ;
					$a = Array () ;
					$a['|'] = $tn_commons ; // Commons template name
					foreach ( $parts AS $p ) {
						$p = explode ( '=' , $p ) ;
						if ( count ( $p ) != 2 ) continue ;
						$p_commons = trim ( $p[0] ) ;
						$p_local = trim ( $p[1] ) ;
						$a[$p_local] = $p_commons ;
					}
					
					$this->meta_tl[$h[3]][$tn_local] = $a ;
				} else {
					if ( substr ( $l , 0 , 1 ) != '*' ) continue ;
					$l = ucfirst ( trim ( substr ( $l , 1 ) ) ) ;
					$this->meta_tl[$h[3]][$this->unify_template_name($l)] = $l ;
				}
			}
		}
		if ( ( count( $this->meta_cat ) < 1 ) && ( count( $this->meta_tl ) < 1 ) ) return array( 'return' => false, 'url' => str_replace( 'action=raw&', '', $url ) );
		else if ( ( count( $this->meta_cat ) >= 1 ) && ( count( $this->meta_tl ) >= 1 ) ) return array( 'return' => true );
	}

	function strip_attr_quotes ( $attr ) {
		if ( substr ( $attr , 0 , 1 ) != '"' && substr ( $attr , 0 , 1 ) != "'" ) return $attr ;
		if ( substr ( $attr , -1 , 1 ) != '"' && substr ( $attr , -1 , 1 ) != "'" ) return $attr ;
		if ( substr ( $attr , 0 , 1 ) != substr ( $attr , -1 , 1 ) ) return $attr ;
		return substr ( $attr , 1 , -1 ) ;
	}
	
	
	function get_namespaces () {
		$url = "http://{$this->language}.{$this->project}.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces|namespacealiases&format=php" ;
		$d = unserialize ( file_get_contents ( $url ) ) ;
		$d = $d['query'] ;
		
		foreach ( $d['namespaces'] AS $n ) {
			$id = $n['id'] ;
			if ( !isset ( $this->namespaces[$id] ) ) $this->namespaces[$id] = Array () ;
			$ns = strtolower ( $n['*'] ) ;
			$this->namespaces[$id][$ns] = $ns ;
			$ns = strtolower ( $n['canonical'] ) ;
			$this->namespaces[$id][$ns] = $ns ;
		}
		foreach ( $d['namespacealiases'] AS $n ) {
			$id = $n['id'] ;
			if ( !isset ( $this->namespaces[$id] ) ) $this->namespaces[$id] = Array () ;
			$ns = strtolower ( $n['*'] ) ;
			$this->namespaces[$id][$ns] = $ns ;
		}
	}
	
	function unify_template_name ( $t ) {
		$t = strtolower ( $t ) ;
		$t = str_replace ( '_' , ' ' , $t ) ;
		return $t ;
	}
	
	function check_template_list ( $tl ) {
		foreach ( $tl AS $t ) {
			$t = $this->unify_template_name ( $t ) ;
			if ( isset ( $this->meta_tl['good'][$t] ) ) $this->seen_good_template = true ;
			if ( isset ( $this->meta_tl['bad'][$t] ) ) $this->seen_bad_template = true ;
		}
	}
	
	function check_category_list ( $cl ) {
		foreach ( $cl AS $c ) {
			if ( !isset ( $this->meta_cat['bad'][$c] ) ) continue ;
			$this->seen_bad_category = true ;
		}
	}
	
	
	function iterate_tree ( &$xml , $name , $fn ) {
		foreach ( $xml AS $k => $x ) {
			if ( isset ( $x['s'] ) ) $this->iterate_tree ( $xml[$k]['s'] , $name , $fn ) ;
		}
		foreach ( $xml AS $k => $x ) {
			if ( $x['?'] != 'x' ) continue ;
			if ( $x['n'] != $name ) continue ;
			$this->$fn ( $xml[$k] ) ;
		}
	}

	function iterate_link ( &$xml ) {
/*		print "<pre>" ;
		print_r ( $this->namespaces ) ;
		print "</pre>" ;
		print "<hr/>" ;*/
		
		global $remove_existing_categories ;
		if ( isset ( $xml['a']['type'] ) and $this->strip_attr_quotes($xml['a']['type']) == 'external' ) return ; // Don't touch external links

		if ( $remove_existing_categories ) {
			$x2w = new XML2wiki () ;
			$target = $x2w->convert ( $xml['s'][0]['s'] ) ;
			$n = explode ( ':' , $target , 2 ) ;
			if ( count ( $n ) == 2 and isset ( $this->namespaces[14] ) ) {
				$ns = strtolower ( $n[0] ) ;
				if ( isset ( $this->namespaces[14][$ns] ) ) {
					$xml = Array ( '?' => 't' , 't' => '' ) ;
					return ;
				}
			}
		}
		
		$x2w = new XML2wiki () ;
		$target = strtolower ( $x2w->convert ( $xml['s'][0]['s'] ) ) ;
		
		$lp = ':' . $this->language . ':' ;
		if ( $this->project != 'wikipedia' ) $lp = ':' . $this->project . $lp ;
		$language_prefix = array ( '?' => 't' , 't' => $lp ) ;
		array_unshift ( $xml['s'][0]['s'] , $language_prefix ) ;
	}
	
	function iterate_template ( &$xml ) {
		if ( !isset ( $xml['s'] ) ) return ;
		$argcnt = 0 ;
		foreach ( $xml['s'] AS $k => $x ) {
			if ( $x['n'] == 'TARGET' ) {
				$tn = $x['s'][0]['t'] ;
//				print "!$tn<br/>" ;
				if ( isset ( $this->meta_tl['bad'][$this->unify_template_name($tn)] ) ) {
					$this->errors[] = "Template \"$tn\" is present in original description, preventing transfer to Commons." ;
					$this->seen_bad_template = true ;
					return ;
				} else if ( isset ( $this->meta_tl['good'][$this->unify_template_name($tn)] ) ) {
					$this->seen_good_template = true ;
				} else if ( isset ( $this->meta_tl['remove'][$tn] ) ) {
					$xml = array ( '?' => 't' , 't' => '' ) ; // Replacing template with empty text
					return ;
				} else if ( isset ( $this->meta_tl['transfer'][$tn] ) ) {
					$newname = $this->meta_tl['transfer'][$tn]['|'] ;
					$xml['s'][$k]['s'][0]['t'] = $newname ;
				} else return ; // This template is in no list, and will be kept as-is
			} else if ( $x['n'] == 'ARG' ) {
				if ( count ( $x['a'] ) == 0 ) $argname = ++$argcnt ;
				else $argname = $this->strip_attr_quotes ( $x['a']['name'] ) ;
//				print "Argument : $argname<br/>" ;
				if ( !isset ( $this->meta_tl['transfer'][$tn][$argname] ) ) continue ;
				$newname = $this->meta_tl['transfer'][$tn][$argname] ;
				$translate = false ;
				if ( substr ( $newname , 0 , 1 ) == '@' ) {
					$translate = true ;
					$newname = trim ( substr ( $newname , 1 ) ) ;
				}
				
				$xml['s'][$k]['a']['name'] = "'$newname'" ;
				
				if ( $translate ) {
					$ns = Array ( Array (
						'?' => 'x' ,
						'n' => 'TEMPLATE' ,
						'a' => Array () ,
						'sc' => false ,
						's' => Array (
							Array (
								'?' => 'x' ,
								'n' => 'TARGET' ,
								'a' => Array () ,
								'sc' => false ,
								's' => Array ( Array ( '?' => 't' , 't' => $this->language ) )
							) ,
							Array (
								'?' => 'x' ,
								'n' => 'ARG' ,
								'a' => Array () ,
								'sc' => false ,
								's' => $xml['s'][$k]['s']
							)
						)
					) ) ;
					
					$x2w = new XML2wiki () ;
					$wiki = trim ( $x2w->convert ( $xml['s'][$k]['s'] ) ) ;
					// FIXME don't do that if parameter is empty
					if ( $wiki != '' ) $xml['s'][$k]['s'] = $ns ;
				}
				
//				print "Translating argument $argname into $newname.<br/>" ;
			} else print "BAD NAME : " . $x['n'] . "<br/>" ;
		}
	}
	
}



?>