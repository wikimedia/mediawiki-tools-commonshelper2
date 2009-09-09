<?PHP 

error_reporting ( E_ALL ) ;

require  ( './class.commonshelper.php' ) ;
require  ( './class.imageinfo.php' ) ;
require  ( './class.xml2wiki.php' ) ;
require  ( './global_functions.php' ) ;

// Evil global variables
$tusc_url = "http://toolserver.org/~magnus/tusc.php" ;
$forbidden_commonsense_categories = array (
	'cities in germany',
	'villages in germany',
) ;


// Initialize
ini_set('user_agent','CommonsHelper 2'); # Fake user agent

$language = get_request ( 'language' , 'en' ) ;
$project = get_request ( 'project' , 'wikipedia' ) ;
$file = get_request ( 'file' ) ;
$target_file = get_request ( 'target_file' ) ;
$stage = get_request ( 'stage' ) ;
$tusc_user = get_request ( 'tusc_user' ) ;
$tusc_password = get_request ( 'tusc_password' ) ;

$commons_to_project = get_request ( 'commons_to_project' , false ) ;
$use_tusc = get_request ( 'use_tusc' , false ) ;
$use_checkusage = get_request ( 'use_checkusage' , false ) ;
$remove_existing_categories = get_request ( 'remove_existing_categories' , false ) ;
//$overwrite_existing = get_request ( 'overwrite_existing' , false ) ;

header('Content-type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html> 
<head> 
<title>CommonsHelper 2</title> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<table style='background-color:#BAD0EF'>
<tr>
<td rowspan='2' nowrap>
<h1 style='margin-top:0px;margin-bottom:0px;padding-bottom:0px;padding-right:5px'>CommonsHelper 2</h1>
</td>
<td width='50%' valign='bottom'>
<b><small>A tool to transfer files from Wikimedia projects to Wikimedia Commons</small></b>
<br />
<small><i>Change the <a href='http://meta.wikipedia.org/wiki/CommonsHelper2/Data_<?PHP echo $language.'.'.$project; ?>'>category and template settings</a> for <?PHP echo $language.'.'.$project; ?></i></small>
</td>
<td align="right" width='50%' valign='bottom'>
<small><a href='https://jira.toolserver.org/browse/CHTWO'>Report an Bug or Suggest Feature</a></small>
</td>
</tr>
</table>
<?PHP


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

// Check if source file exists
if ( !$ii_local->file_exists() ) {
	show_error ( "Source file does not exist!" ) ;
	show_main_form() ;
	endthis() ;
}


// Check if images exists at Commons under other name
if( !$commons_to_project ) {
	$alt = $ii_local->exists_elsewhere ( 'commons' , 'wikimedia' ) ;
	if ( $alt != '' ) {
		$alt2 = array_pop ( explode ( ':' , $alt , 2 ) ) ;
		$ii_commons = new ImageInfo ( 'commons' , 'wikimedia' , $alt2 ) ;
		show_error ( "File already exists on Commons as \"<a href='http://commons.wikimedia.org/wiki/$alt'>$alt</a>\"!" ) ;
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
		show_error ( "File already exists on the target wiki as \"<a href='http://{$language}.{$project}.org/wiki/$alt'>$alt</a>\"!" ) ;
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
	show_error ( "Different target file exists on the target wiki under the same name!" ) ;
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
	show_error ( 'No meta data found for the source wiki! <a href="'.$meta_data['url'].'">Link</a>' ) ;
}

$allow_upload = false ;

$ch->iterate_tree ( $xml , 'TEMPLATE' , 'iterate_template' ) ;
$ch->iterate_tree ( $xml , 'LINK' , 'iterate_link' ) ;

// Using API to find nested templates
$used_templates = $ii_local->get_used_templates() ;
$ch->check_template_list ( $used_templates ) ;

if ( !$ch->seen_good_template ) {
	show_error ( "No good templates found!" ) ;
} else $allow_upload = true ;

if ( $ch->seen_bad_template ) {
	show_error ( "Bad template found!" ) ;
	$allow_upload = false ;
}

// Use API to check for bad categories
$used_categories = $ii_local->get_used_categories() ;
$ch->check_category_list ( $used_categories ) ;

if ( $ch->seen_bad_category ) {
	show_error ( "Bad categories found!" ) ;
	$allow_upload = false ;
}


// Regenerate wiki text from XML tree
if( !$commons_to_project ) {
	$new_wiki = "{{BotMoveToCommons|{$language}.{$project}|year={{subst:CURRENTYEAR}}|month={{subst:CURRENTMONTHNAME}}|day={{subst:CURRENTDAY}}}}\n" ;
} else {
	$new_wiki = "" ;
}

$x2w = new XML2wiki () ;
$new_wiki .= $x2w->convert ( $xml ) ;
$new_wiki .= "\n\n" . $ii_local->get_upload_history () ;


// Append CheckUsage/WikiSense categories
if ( $use_checkusage ) { // UNTESTED
	$categories = $ii_local->common_sense ( $language , $image ) ;
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

$new_wiki = preg_replace("#\[\[Category:Hidden categories\]\]#", "", $new_wiki);

$limg = $ii_local->get_thumbnail_img ( $thumbnail_size ) ;
$style = "background:#D0E6FF;padding:2px;border:2px solid #DDDDDD;width:100%" ;

if( !$commons_to_project ) $url = 'http://commons.wikimedia.org/w/index.php?title=Special:Upload'; 
else $url = 'http://commons.wikimedia.org/w/index.php?title=Special:Upload';

?>
<form method='post' action='<?PHP echo $url; ?>'>
<table style='width:100%'>
<tr>
<td style='width:100%'>
<h3>Original wikitext</h3>
<textarea rows='15' cols='125' style='$style;font-size:80%'><?PHP echo htmlspecialchars ( $orig_wiki ); ?></textarea>
<h3>New wikitext</h3>
<textarea rows='20' cols='125' style='$style' name='wpUploadDescription'><?PHP echo htmlspecialchars ( $new_wiki ); ?></textarea>
</td>
<td nowrap valign='top' style='padding-left:10px'><?PHP echo $limg; ?></td>
</tr>
</table>

New filename : <input type='text' name='wpDestFile' size='80' value='<?PHP echo addslashes ( $target_file ); ?>' />
<p>For manual upload, edit the above text (if necessary), save <a href='<?PHP echo $ii_local->idata['url'] ?>'>the file</a> on your computer, then 
<input type='submit' name='up' value='upload it'/>.</p>
</form>
<?PHP


// Try direct upload
if ( $use_tusc ) {
	if( !$commons_to_project ) {
		if ( verify_tusc ( $tusc_user , $tusc_password ) ) {
			if ( $allow_upload ) {
				$end = do_direct_upload ( $language , $project , $file , $target_file , $ii_local->idata['url'] , $new_wiki ) ;
				echo $end;
			} else {
				show_error ( "Cannot upload directly due to errors!" ) ;
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
