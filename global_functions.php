<?PHP

function msg ( $msg, $replace1 = "", $replace2 = "", $replace3 = "", $replace4 = "" ) {
	global $message, $user_lang;
	
	$replace = array();
	
	if( isset( $message[$user_lang][$msg] ) && $message[$user_lang][$msg] != "" ) {
		$return = $message[$user_lang][$msg];
	
		if( $replace1 != "" ) {
			$replace['$1'] = $replace1;
		}
		if( $replace2 != "" ) {
			$replace['$2'] = $replace2;
		}
		if( $replace3 != "" ) {
			$replace['$3'] = $replace3;
		}
		if( $replace4 != "" ) {
			$replace['$4'] = $replace4;
		}
		
		$return = strtr( $return, $replace );
		
		return $return;
	}
	else {
		$return = $message['en'][$msg];
	
		if( $replace1 != "" ) {
			$replace['$1'] = $replace1;
		}
		if( $replace2 != "" ) {
			$replace['$2'] = $replace2;
		}
		if( $replace3 != "" ) {
			$replace['$3'] = $replace3;
		}
		if( $replace4 != "" ) {
			$replace['$4'] = $replace4;
		}
		
		$return = strtr( $return, $replace );
		
		return $return;
	}
}

function get_request ( $key , $default = '' ) {
	$request = $_POST + $_GET;
	if ( isset ( $request[$key] ) AND $request[$key] != "" ) return $request[$key] ;
	return $default ;
}

function show_error ( $text ) {
	global $raw, $raw_error;
	if( $raw == 0 ) print "<div style='border:2px solid #888888;margin:2px;padding:5px;font-size:150%;color:red;text-align:center'><b>".msg( 'attention' )."</b> : $text</div>" ;
	else $raw_error .= msg( 'attention' )." : $text<br />" ;
}

function show_main_form () {
	global $language , $project , $file , $target_file ;
	global $tusc_user , $tusc_password , $use_tusc, $transfer_user ;
	global $use_checkusage , $remove_existing_categories, $commons_to_project ;//, $overwrite_existing ;
	global $user_lang;
	
//	$cb_overwrite_existing = $overwrite_existing ? ' checked' : '' ;
	$cb_commons_to_project = $commons_to_project ? ' checked=checked' : '' ;
	$cb_remove_existing_categories = $remove_existing_categories ? ' checked=checked' : '' ;
	$cb_use_checkusage = $use_checkusage ? ' checked=checked' : '' ;
	$cb_use_tusc = $use_tusc ? ' checked=checked' : '' ;
	
	echo "<form method='post' action='./index.php'>
<table border='1'>
<tr><th>".msg( 'language' )."</th><td><input type='text' size='20' name='language' value='$language' /></td></tr>
<tr><th>".msg( 'project' )."</th><td><input type='text' size='20' name='project' value='$project' /></td></tr>
<tr><th>".msg( 'source_file' )."</th><td><input type='text' size='50' name='file' value='$file' /></td></tr>
<tr><th>".msg( 'target_file' )."</th><td><input type='text' size='50' name='target_file' value='$target_file' /></td></tr>
<tr><th>".msg( 'commons_username' )."</th><td><input type='text' size='50' name='transfer_user' value='$transfer_user' /></td></tr>

<tr><th>".msg( 'commons_to_project' )."</th><td><input type='checkbox' name='commons_to_project' id='commons_to_project' value='1' $cb_commons_to_project />
<label for='commons_to_project'>".msg( 'move_file_from_com' )."</label></td></tr>

<tr><th>".msg( 'categories' )."</th><td><input type='checkbox' name='remove_existing_categories' id='remove_existing_categories' value='1' $cb_remove_existing_categories />
<label for='remove_existing_categories'>".msg( 'remove_cats' )."</label></td></tr>

<tr><th>".msg( 'checkusage' )."</th><td><input type='checkbox' name='use_checkusage' id='use_checkusage' value='1' $cb_use_checkusage />
<label for='use_checkusage'>".msg( 'use_checkusage', "<a href='http://toolserver.org/~daniel/WikiSense/CommonSense.php'>", "</a>" )."</label></td></tr>

<tr><th>".msg( 'tusc' )."</th><td><input type='checkbox' name='use_tusc' id='use_tusc' value='1' $cb_use_tusc />
<label for='use_tusc'>".msg( 'use_tusc', "<a href='http://toolserver.org/~magnus/tusc.php?language=commons&project=wikimedia'>", "</a>" )."</label></td></tr>
<tr><th>".msg( 'tusc_user' )."</th><td><input type='text' size='50' name='tusc_user' value='$tusc_user' /></td></tr>
<tr><th>".msg( 'tusc_pass' )."</th><td><input type='password' size='50' name='tusc_password' value='$tusc_password' /></td></tr>


<tr><td /><td><input type='submit' name='doit' value='".msg( 'do_it' )."' /></td></tr>
<input type='hidden' name='stage' value='init' />
<input type='hidden' name='user_lang' value='".$user_lang."' />
</table>
</form>" ;

/*
<tr><th>Overwrite</th><td><input type='checkbox' name='overwrite_existing' id='overwrite_existing' value='1' $cb_overwrite_existing />
<label for='overwrite_existing'>Overwrite existing images</label></td></tr>
*/

}

function endthis () {
	global $user_lang;
	
	if( $user_lang == 'he' ) { 
		echo '</span>';
	}
	
	print "</body></html>" ;
	exit ( 0 ) ;
}


function do_post_request($url, $data, $optional_headers = null) {
	$params = array('http' => array(
			  'method' => 'POST',
			  'content' => http_build_query ( $data ) 
		   ));
	if ($optional_headers !== null) {
		$params['http']['header'] = $optional_headers;
	}
	$ctx = stream_context_create($params);
	$fp = @fopen($url, 'rb', false, $ctx);
	if (!$fp) {
		throw new Exception("Problem with $url, $php_errormsg");
	}
	$response = @stream_get_contents($fp);
	if ($response === false) {
		throw new Exception("Problem reading data from $url, $php_errormsg");
	}
	return $response;
}

function verify_tusc ( $tusc_user , $tusc_password ) {
	global $tusc_url ;
	if ( $tusc_user == '' ) return false ;
	if ( $tusc_password == '' ) return false ;
	$ret = do_post_request ( $tusc_url , 
			array (
				'check' => '1' ,
				'botmode' => '1' ,
				'user' => $tusc_user ,
				'language' => 'commons' ,
				'project' => 'wikimedia' ,
				'password' => $tusc_password ) ) ;

	if ( strpos ( $ret , '1' ) !== false ) return true ;
	return false ;
}


function do_direct_upload ( $image , $newname , $external_url , $desc ) {
	// Make temporary file/dir
	do {
		$temp_name = tempnam ( "/tmp" , "ch2" ) ;
		$temp = @fopen ( $temp_name , "w" ) ;
	} while ( $temp === false ) ;
	$temp_dir = $temp_name . "-dir" ;
	mkdir ( $temp_dir ) ;

    // Upload class
	//$server = $lang.'.'.$project.'.org';
	$server = "wiki.smallbusiness-webdesign.de";
	include_once ( '../upload_bot_key.php' );
	$upload = new Upload( $server, $temp_dir, /* "/w" */ "" );
	$upload->login( 'CommonsHelper2 Bot', $upload_pass );	
	$output = $upload->upload( $external_url, $newname, $desc, $image );

	// Cleanup
	$debug_file = $temp_dir . "/debug.txt" ;
	@unlink ( $debug_file ) ;
	rmdir ( $temp_dir ) ;
	fclose ( $temp ) ;
	unlink ( $temp_name ) ;

	// Output
	$ret = "<h3>Output of upload bot</h3><pre>{$output}</pre>" ;
	$ret .= "<p>The image should now be at <a target='blank' href='" ;
	$ret .= "http://commons.wikimedia.org/w/index.php?title=Image:".urlencode($newname)."'>{$newname}</a>. " ;
	$ret .= "<a href=\"http://commons.wikimedia.org/w/index.php?action=edit&title=Image:$newname\" target=\"_blank\">Edit the new description page</a>." ;
	return $ret ;	
}

function filter( $wiki ) {
	$blacklist = get_blacklist();
	$new_wiki = preg_replace("#\[\[Category:Hidden categories\]\]#", "", $wiki);
	
	foreach( $blacklist as $value ) {
		$new_wiki = preg_replace($value, "", $new_wiki);
	}
	
	return $new_wiki;
}

function get_blacklist() {
	$url = 'http://commons.wikimedia.org/w/index.php?title=User:Multichill/Category_blacklist&action=raw';
	$query = file_get_contents ( $url ) ;
	
	$query = preg_replace("#\:#", "", $query);
	
	$lines = explode( '\n', $query );
	$output = array();
	
	foreach( $lines as $line ) {
		if( substr( $line, 0, 1 ) != "*" ) continue;
		$link = preg_replace("#\*#", "", $wiki);
		$reg = '#'.$link.'#';
		$output[] = $reg;
	}
	
	return $output;
}


function controll_information( $wiki ) {
	global $ch, $ii_local, $transfer_user, $allow_upload;
	$meta_information = $ch->get_information();
	if( isset( $meta_information['template'] ) && $meta_information['template'] != "" ) {
		$reg = '~\{\{'.$meta_information['template'].'~i';
		if (preg_match($reg, $wiki)) {
			return $wiki;
		}
	}
		
	//echo "<br />".$wiki."<br />"."<br />"."<br />";
		
	$reg = '@==+\s*'.preg_quote( $meta_information['description'] ).'\s*:*\s*==+(.*?)==@is';
	
	if( !preg_match($reg, $wiki, $match) ) {
		show_error( "Cannot get description from the text" );
		$allow_upload = false;
	}
	
	$desc = trim( $match[1] );
	
	$data = $ii_local->get_information_data();
	$orignal_date = '(Original uploaded at '.$data['date'].')';
	
	$tz = date_default_timezone_get();
	date_default_timezone_set('UTC'); 
	$date = date( 'Y-m-d H:i:s' ).'(UTC)';
	date_default_timezone_set($tz);
	
	$orignal_user = 'Original uploaded by [['.$data['user'].']]';
	$transfer = '(Transfered by [[User:'.$transfer_user.'|'.$transfer_user.']])';

	$information = '{{Information
|description='.$desc.'
|date='.$date.' '.$orignal_date .'
|source=Original uploaded on '.$data['lang'].'.'.$data['project'].'
|author='.$orignal_user.' '.$transfer.'
}}

';
	//echo $information;
	$wiki = trim ( $information.$wiki ) ;
	//echo $wiki;
	return $wiki;
}

function controll_template( $wiki ) {
	global $ii_local;
	
	$reg = '@\{\{Pd-self\}\}@is';
	if (!preg_match($reg, $wiki)) {
		return $wiki;
	} else {
		$data = $ii_local->get_information_data();
		
		$user = explode( '|', $data['user'] );
		$user = array_pop( $user );
		
		if( $data['project'] == "wikipedia" ) {
			$replace = "{{Pd-user|".$user."|".$data['lang']."}}";
			str_replace( "{{Pd-self}}", $replace, $wiki );
		} else {
			$replace = "{{Pd-user|".$user."}}";
			$wiki = preg_replace( $reg, $replace, $wiki );
		}
	}
	
	return $wiki;
}

function add_html( $wiki ) {
	$url = "http://meta.wikipedia.org/w/index.php?action=raw&title=CommonsHelper2/HTML";
	$lines = explode ( "\n" , file_get_contents ( $url ) ) ;
	
	foreach( $lines as $l ) {
		if ( substr ( $l , 0 , 1 ) != '*' ) continue ;
		$t = trim( substr ( $l , 1 ) );
		echo '@\&lt\;'.$t.'\&gt\;@i <'.$t.'>';
		$wiki = preg_replace( '@\&lt\;'.$t.'\&gt\;@i', '<'.$t.'>', $wiki );
		//echo '@&lt;'.$t.'&gt;@i <'.$t.'>';
		$wiki = preg_replace( '@&lt;'.$t.'&gt;@i', '<'.$t.'>', $wiki );
	}

	return $wiki;
}

?>