<?PHP

function get_request ( $key , $default = '' ) {
	$request = $_POST + $_GET;
	if ( isset ( $request[$key] ) AND $request[$key] != "" ) return $request[$key] ;
	return $default ;
}

function show_error ( $text ) {
	print "<div style='border:2px solid #888888;margin:2px;padding:5px;font-size:150%;color:red;text-align:center'><b>ATTENTION</b> : $text</div>" ;
}

function show_main_form () {
	global $language , $project , $file , $target_file ;
	global $tusc_user , $tusc_password , $use_tusc ;
	global $use_checkusage , $remove_existing_categories ;//, $overwrite_existing ;
	
//	$cb_overwrite_existing = $overwrite_existing ? ' checked' : '' ;
	$cb_remove_existing_categories = $remove_existing_categories ? ' checked' : '' ;
	$cb_use_checkusage = $use_checkusage ? ' checked' : '' ;
	$cb_use_tusc = $use_tusc ? ' checked' : '' ;
	
	print "<form method='post' action='./index.php'>
<table border='1'>
<tr><th>Language</th><td><input type='text' size='20' name='language' value='$language' /></td></tr>
<tr><th>Project</th><td><input type='text' size='20' name='project' value='$project' /></td></tr>
<tr><th>File in project</th><td><input type='text' size='50' name='file' value='$file' /></td></tr>
<tr><th>File on Commons</th><td><input type='text' size='50' name='target_file' value='$target_file' /></td></tr>

<tr><th>Categories</th><td><input type='checkbox' name='remove_existing_categories' id='remove_existing_categories' value='1' $cb_remove_existing_categories />
<label for='remove_existing_categories'>Remove existing categories</label></td></tr>

<tr><th>CheckUsage</th><td><input type='checkbox' name='use_checkusage' id='use_checkusage' value='1' $cb_use_checkusage />
<label for='use_checkusage'>Use <a href='http://toolserver.org/~daniel/WikiSense/CheckUsage.php'>CheckUsage</a> to suggest new categories</label></td></tr>

<tr><th>TUSC</th><td><input type='checkbox' name='use_tusc' id='use_tusc' value='1' $cb_use_tusc />
<label for='use_tusc'>Use <a href='http://toolserver.org/~magnus/tusc.php?language=commons&project=wikimedia'>TUSC</a> to transfer the file directly</label></td></tr>
<tr><th>TUSC user name</th><td><input type='text' size='50' name='tusc_user' value='$tusc_user' /></td></tr>
<tr><th>TUSC password</th><td><input type='password' size='50' name='tusc_password' value='$tusc_password' /></td></tr>


<tr><td /><td><input type='submit' name='doit' value='Do it' /></td></tr>
<input type='hidden' name='stage' value='init' />
</table>
</form>" ;

/*
<tr><th>Overwrite</th><td><input type='checkbox' name='overwrite_existing' id='overwrite_existing' value='1' $cb_overwrite_existing />
<label for='overwrite_existing'>Overwrite existing images</label></td></tr>
*/

}

function endthis () {
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


function do_direct_upload ( $lang , $project , $image , $newname , $external_url , $desc ) {
	$perl_command = 'perl' ;
	
	// Make temporary file/dir
	$cwd = getcwd() ;
	do {
		$temp_name = tempnam ( "/tmp" , "ch" ) ;
		$temp = @fopen ( $temp_name , "w" ) ;
	} while ( $temp === false ) ;
	$temp_dir = $temp_name . "-dir" ;
	mkdir ( $temp_dir ) ;

	// Copy remote file to local file
	$short_file = 'dummy.' . array_pop ( explode ( '.' , $external_url ) ) ;
	$local_file = $temp_dir . "/" . $short_file ;
	if ( !copy($external_url, $local_file) ) {
		rmdir ( $temp_dir ) ;
		fclose ( $temp ) ;
		unlink ( $temp_name ) ;
		return "Error" ;
	}
	
	// Prepare description
	$desc = trim ( str_replace ( "== Summary ==" , "" , $desc ) ) ;
	$desc = str_replace ( "\r" , "" , $desc ) ;
	$desc = str_replace ( "\n>" , "\n >" , $desc ) ;
	$desc = str_replace ( "\n\n\n" , "\n\n" , $desc ) ;
	$desc = "\n{{BotMoveToCommons|$lang.$project|year={{subst:CURRENTYEAR}}|month={{subst:CURRENTMONTHNAME}}|day={{subst:CURRENTDAY}}}}\n$desc" ;
	
	// Create meta file
	$meta_file = $temp_dir . '/meta.txt' ;
	$meta = @fopen ( $meta_file , "w" ) ;
	fwrite ( $meta , $local_file . "\n" ) ;
	fwrite ( $meta , $newname . "\n" ) ;
	fwrite ( $meta , $desc ) ;
	fclose ( $meta ) ;

	// Run upload bot
	$upload_bot = "./upload_bot.pl" ;
	$command = "{$perl_command} {$upload_bot} {$temp_dir}" ;
//	print $command ;
	$output = shell_exec ( $command ) ;

	// Cleanup
	$debug_file = $temp_dir . "/debug.txt" ;
	@unlink ( $debug_file ) ;
	unlink ( $meta_file ) ;
	unlink ( $local_file ) ;
	rmdir ( $temp_dir ) ;
	fclose ( $temp ) ;
	unlink ( $temp_name ) ;

	// Output
	$ret = "<h3>Output of upload bot</h3><pre>{$output}</pre>" ;
	$ret .= "<p>The image should now be at <a target='blank' href='" ;
	$ret .= get_wikipedia_url ( 'commons' , 'Image:'.$newname ) . "'>{$newname}</a>. " ;
	$ret .= "<a href=\"http://commons.wikimedia.org/w/index.php?action=edit&title=Image:$newname\" target=\"_blank\">Edit the new description page</a>." ;
	return $ret ;	
}



?>