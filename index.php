<?PHP 
ini_set('max_execution_time','120');
error_reporting ( E_ALL ) ;

header( "Content-Type: text/html; charset=UTF-8" );

require  ( './class.commonshelper.php' ) ;
require  ( './class.imageinfo.php' ) ;
require  ( './class.xml2wiki.php' ) ;
require  ( './global_functions.php' ) ;
require  ( './upload_class.php' ) ;
require  ( './commonshelper2.i18n.php' ) ;

// Evil global variables
$tusc_url = "http://toolserver.org/~magnus/tusc.php" ;
$forbidden_commonsense_categories = array (
	'cities in germany',
	'villages in germany',
) ;


// Initialize
ini_set('user_agent','CommonsHelper 2'); # Fake user agent

// Language of user
$user_lang = get_request ( 'user_lang' , 'en' ) ;

$language = get_request ( 'language' , msg( 'standard_language' ) ) ;
$project = get_request ( 'project' , msg( 'standard_project' ) ) ;
$file = get_request ( 'file' ) ;
$target_file = get_request ( 'target_file' ) ;
$stage = get_request ( 'stage' ) ;
$tusc_user = get_request ( 'tusc_user' ) ;
$tusc_password = get_request ( 'tusc_password' ) ;
$transfer_user = get_request ( 'transfer_user' ) ;

$commons_to_project = get_request ( 'commons_to_project' , false ) ;
$use_tusc = get_request ( 'use_tusc' , false ) ;
$use_checkusage = get_request ( 'use_checkusage' , false ) ;
$remove_existing_categories = get_request ( 'remove_existing_categories' , false ) ;
//$overwrite_existing = get_request ( 'overwrite_existing' , false ) ;

$raw = get_request ( 'raw' , 0 ) ;
$raw_error = '';

if( $raw == 0 ) {
header('Content-type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html> 
<head> 
<title>CommonsHelper 2</title> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<?PHP if( $user_lang == 'he' ) { echo '<span lang="he" dir="rtl">'; } ?>
<table style='background-color:#BAD0EF'>
<tr>
<td rowspan='2' nowrap>
<h1 style='margin-top:0px;margin-bottom:0px;padding-bottom:0px;padding-right:5px'><?PHP echo msg( 'commonshelper2' ); ?></h1>
</td>
<td width='65%' valign='bottom'>
<b><small><?PHP echo msg( 'description' ); ?></small></b>
<br />
<small><i><?PHP echo msg( 'change_meta', "<a href='http://meta.wikipedia.org/wiki/CommonsHelper2/Data_".$language.".".$project."'>", '</a>', $language.'.'.$project ); ?></i></small>
<br />
<small>
<a href="index.php?user_lang=en">English</a> - <a href="index.php?user_lang=he">Hebrew</a>
</small>
</td>
<td align="right" width='50%' valign='bottom'>
<small><a href='https://jira.toolserver.org/browse/CHTWO'><?PHP echo msg( 'jira_link' ); ?></a></small>
</td>
</tr>
</table>
<?PHP
}

$thumbnail_size = 128 ;
if ( $target_file == '' ) $target_file = $file ;

// Initialize - do not query API or wiki2xml yet
if( !$commons_to_project ) {
	$ch = new CommonsHelper ( $language , $project , $file ) ;
	$ii_local = new ImageInfo ( $language , $project , $file ) ;
	$ii_commons = new ImageInfo ( 'commons' , 'wikimedia' , $target_file ) ;
} else {
	$ch = new CommonsHelper ( 'commons' , 'wikimedia' , $file ) ;
	$ii_local = new ImageInfo ( 'commons' , 'wikimedia' , $file ) ;
	$ii_commons = new ImageInfo ( $language , $project , $target_file ) ;
}



// Show initial form
if ( $stage == '' ) {
	$use_checkusage = get_request( 'use_checkusage', true ) ;
	show_main_form () ;
	endthis() ;
}

// Controll Username
if( $transfer_user == "" ) {
	show_error ( msg( 'error_transfer_usr' ) ) ;
	endthis();
}

// Check if source file exists
if ( !$ii_local->file_exists() ) {
	show_error ( msg( 'error_not_exists' ) ) ;
	show_main_form() ;
	endthis() ;
}

// Check if images exists at Commons under other name
if( !$commons_to_project ) {
	$alt = $ii_local->exists_elsewhere ( 'commons' , 'wikimedia' ) ;
	if ( $alt != '' ) {
		$alt2 = array_pop ( explode ( ':' , $alt , 2 ) ) ;
		$ii_commons = new ImageInfo ( 'commons' , 'wikimedia' , $alt2 ) ;
		show_error ( msg( 'error_file_exists', "<a href='http://commons.wikimedia.org/wiki/$alt'>", "</a>", 'Commons', $alt ) );
		print "<table><tr><td>" ;
		print $ii_local->get_thumbnail_img ( $thumbnail_size ) ;
		print "</td><td>" ;
		print $ii_commons->get_thumbnail_img ( $thumbnail_size ) ;
		print "</td></tr></table>" ;
		endthis() ;
	}
} else {
	$alt = $ii_local->exists_elsewhere ( $language , $project ) ;
	if ( $alt != '' ) {
		$alt2 = array_pop ( explode ( ':' , $alt , 2 ) ) ;
		$ii_commons = new ImageInfo ( $language , $project , $alt2 ) ;
		show_error ( msg( 'error_file_exists', "<a href='http://commons.wikimedia.org/wiki/$alt'>", "</a>", msg( 'target_wiki' ), $alt ) );
		print "<table><tr><td>" ;
		print $ii_local->get_thumbnail_img ( $thumbnail_size ) ;
		print "</td><td>" ;
		print $ii_commons->get_thumbnail_img ( $thumbnail_size ) ;
		print "</td></tr></table>" ;
		endthis() ;
	}
}

// Check if target file exists
if ( $ii_commons->file_exists() ) {
	show_error ( msg( 'error_diff_exists' ) ) ;
	print "<div style='float:right'>" ;
	print $ii_local->get_thumbnail_img ( $thumbnail_size ) ;
	print $ii_commons->get_thumbnail_img ( $thumbnail_size ) ;
	print "</div>" ;
	// TODO suggest new name
	show_main_form() ;
	endthis() ;
}

$orig_wiki = $ch->get_original_wikitext() ;

$xml = $ch->get_xml () ;
$meta_data = $ch->read_meta_data () ;

if ( !$meta_data['return'] ) {
	show_error ( msg( 'error_no_meta', "<a href='".$meta_data['url']."'>", "</a>" ) ) ;
}

$allow_upload = false ;

$ch->iterate_tree ( $xml , 'TEMPLATE' , 'iterate_template' ) ;
$ch->iterate_tree ( $xml , 'LINK' , 'iterate_link' ) ;

// Using API to find nested templates
$used_templates = $ii_local->get_used_templates() ;
$ch->check_template_list ( $used_templates ) ;

if ( !$ch->seen_good_template && !$commons_to_project ) {
	show_error ( msg( 'error_meta_no_good', "<a href='".$meta_data['url']."'>", "</a>" ) ) ;
} else $allow_upload = true ;

if ( $ch->seen_bad_template && !$commons_to_project ) {
	show_error ( msg( 'error_meta_bad', "<a href='".$meta_data['url']."'>", "</a>" ) ) ;
	$allow_upload = false ;
}

// Use API to check for bad categories
$used_categories = $ii_local->get_used_categories() ;
$ch->check_category_list ( $used_categories ) ;

if ( $ch->seen_bad_category && !$commons_to_project ) {
	show_error ( msg( 'error_meta_bad', "<a href='".$meta_data['url']."'>", "</a>" ) ) ;
	$allow_upload = false ;
}

$x2w = new XML2wiki () ;
$new_wiki = $x2w->convert ( $xml ) ;
$new_wiki .= "\n\n" . $ii_local->get_upload_history () ;


// Append CheckUsage/WikiSense categories
if ( $use_checkusage ) { // UNTESTED
	$categories = $ii_local->common_sense ( $language ) ;
	$new_wiki .= "\n\n<!-- Categories by CheckUsage -->" ;
	foreach ( $categories AS $c ) {
		$new_wiki .= "[[Category:$c]]\n" ;
	}
	$new_wiki = trim ( $new_wiki ) ;
}

// Show accumulated errors
if ( count ( $ch->errors ) > 0 ) {
	foreach ( $ch->errors AS $e ) {
		show_error ( $e ) ;
	}
	$allow_upload = false ;
}

$filterd_wiki = filter( $new_wiki );
$filterd_wiki = htmlspecialchars ( $filterd_wiki );
//$filterd_wiki = add_html ( $filterd_wiki );
$output_wiki = controll_information( $filterd_wiki );
$output_wiki = controll_template( $output_wiki );

// Regenerate wiki text from XML tree
if( !$commons_to_project ) {
	$info_ch2 = "{{BotMoveToCommons|{$language}.{$project}|year={{subst:CURRENTYEAR}}|month={{subst:CURRENTMONTHNAME}}|day={{subst:CURRENTDAY}}}}\n" ;
	if ( $use_tusc ) {
		$info_ch2 .= "The upload bot is [[User:CommonsHelper2 Bot]] which is called by [http://toolserver.org/~commonshelper2/index.php CommonsHelper2].\n" ;
	}
	$info_ch2 .= "The tool and the bot are operated by [[User:Jan Luca]] and [[User:Magnus Manske]].\n\n";
	$output_wiki = $info_ch2.$output_wiki;
}

$limg = $ii_local->get_thumbnail_img ( $thumbnail_size ) ;
$style = "background:#D0E6FF;padding:2px;border:2px solid #DDDDDD;width:100%" ;

if( !$commons_to_project ) $url = "http://commons.wikimedia.org/w/index.php?title=Special:Upload"; 
else $url = "http://{$language}.{$project}.org/w/index.php?title=Special:Upload";

if( $raw == 0 ) {
?>
<form method='post' action='<?PHP echo $url; ?>'>
<table style='width:100%'>
<tr>
<td style='width:100%'>
<h3><?PHP echo msg( 'original_wikitext' ); ?></h3>
<textarea rows='15' cols='125' style='$style;font-size:80%'><?PHP echo htmlspecialchars ( $orig_wiki ); ?></textarea>
<h3><?PHP echo msg( 'new_wikitext' ); ?></h3>
<textarea rows='20' cols='125' style='$style' name='wpUploadDescription'><?PHP echo $output_wiki; ?></textarea>
</td>
<td nowrap valign='top' style='padding-left:10px'><?PHP echo $limg; ?></td>
</tr>
</table>

<?PHP echo msg( 'new_filename' ); ?> <input type='text' name='wpDestFile' size='80' value='<?PHP echo addslashes ( $target_file ); ?>' />
<p><?PHP echo msg( 'output_information', "<a href='".$ii_local->idata['url']."'>", "</a>" ); ?> 
<input type='submit' name='up' value='<?PHP echo msg( 'upload_it' ); ?>'/>.</p>
</form>
<?PHP
} else {
?>
<?PHP if( $raw_error != '' ) echo $raw_error.'<br />'; ?>
New Wikitext:
<br /><br />
<!-- start new wikitext --><?PHP echo htmlspecialchars ( $output_wiki ); ?><!-- end new wikitext -->
<br /><br />
New Filename:
<br /><br /> 
<!-- start new filename --><?PHP echo addslashes ( $target_file ); ?><!-- end new filename -->
<?PHP
}

//$allow_upload = true;
$bot_blocked = true;
// Try direct upload
if ( $use_tusc ) {
	if( !$commons_to_project ) {
		if ( verify_tusc ( $tusc_user , $tusc_password ) ) {
			if ( $allow_upload ) {
				$end = do_direct_upload ( $file , $target_file , $ii_local->idata['url'] , $new_wiki ) ;
				echo $end;
			} elseif( !$allow_upload && $bot_blocked ) {
				show_error ( "Bot is blocked on Commons." ) ;
			} else {
				show_error ( "Cannot upload directly because there are problem with the meta data (see above)!" ) ;
			}
		} else {
			show_error ( "TUSC verification failed!" ) ;
		}
	} else {
		show_error ( "Direct upload works only at commons!" ) ;
	}
}

endthis() ;
?>