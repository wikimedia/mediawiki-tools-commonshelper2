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

$use_tusc = get_request ( 'use_tusc' , false ) ;
$use_checkusage = get_request ( 'use_checkusage' , false ) ;
$remove_existing_categories = get_request ( 'remove_existing_categories' , false ) ;
//$overwrite_existing = get_request ( 'overwrite_existing' , false ) ;

header('Content-type: text/html; charset=utf-8');
print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n\n" ;
print '<html> <head> <title>CommonsHelper 2</title> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body>' ;
print "<table style='background-color:#BAD0EF'><tr><td rowspan='2' nowrap><h1 style='margin-top:0px;margin-bottom:0px;padding-bottom:0px;padding-right:5px'>CommonsHelper 2</h1></td>" ;
print "<td valign='bottom' width='100%'><b><small>A tool to transfer files from Wikimedia projects to Wikimedia Commons</small></b>" ;
print "<br/><small><i>Change the <a href='http://meta.wikipedia.org/wiki/CommonsHelper2/Data_{$language}.{$project}'>category and template settings</a> for {$language}.{$project}</i></small>";
/*print " [<a href='http://meta.wikipedia.org/wiki/CatScan2/{$lil}'><small>{$this->loc['manual']}</small></a>]" ;
print " [<a href='{$this->i18n_url}#{$uil}'><small>{$this->loc['interface_text']}</small></a>]</td></tr><tr><td>" ;
print "{$this->loc['interface_language']} : " ;
foreach ( $this->available_interface_languages AS $l ) {
	if ( $l == $this->interface_language ) print " [<small><b>" . strtoupper ( $l ) . "</b></small></a>]" ;
	else print " [<a href='$script?interface_language=$l'><small>" . strtoupper ( $l ) . "</small></a>]" ;
}*/
print "</td></table>" ;

$thumbnail_size = 128 ;
if ( $target_file == '' ) $target_file = $file ;

// Initialize - do not query API or wiki2xml yet
$ch = new CommonsHelper ( $language , $project , $file ) ;
$ii_local = new ImageInfo ( $language , $project , $file ) ;
$ii_commons = new ImageInfo ( 'commons' , 'wikimedia' , $target_file ) ;


// Show initial form
if ( $stage == '' ) {
	if ( !isset ( $_REQUEST['use_checkusage'] ) ) $use_checkusage = true ;
	show_main_form () ;
	endthis() ;
}

// Check if source file exists
if ( !$ii_local->file_exists() ) {
	show_error ( "Source file does not exist!" ) ;
	show_main_form() ;
	endthis() ;
}


// Check if inages exists at Commons under other name
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

// Check if target file exists
if ( $ii_commons->file_exists() ) {
	show_error ( "Different target file exists on Commons under the same name!" ) ;
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
$ch->read_meta_data () ;

$allow_upload = false ;

$ch->iterate_tree ( $xml , 'TEMPLATE' , iterate_template ) ;
$ch->iterate_tree ( $xml , 'LINK' , iterate_link ) ;

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
$new_wiki = "{BotMoveToCommons|{$language}.{$project}|year={{subst:CURRENTYEAR}}|month={{subst:CURRENTMONTHNAME}}|day={{subst:CURRENTDAY}}}}\n" ;

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


$limg = $ii_local->get_thumbnail_img ( $thumbnail_size ) ;
$style = "background:#D0E6FF;padding:2px;border:2px solid #DDDDDD;width:100%" ;

print "<form method='post' action='http://commons.wikimedia.org/w/index.php?title=Special:Upload'>" ;
print "<table style='width:100%'><tr><td style='width:100%'>" ;
print "<h3>Original wikitext</h3><textarea rows='15' style='$style;font-size:80%'>" . htmlspecialchars ( $orig_wiki ) . "</textarea>" ;
print "<h3>New wikitext</h3><textarea rows='20' style='$style' name='wpUploadDescription'>" . htmlspecialchars ( $new_wiki ) . "</textarea>" ;
print "</td><td nowrap valign='top' style='padding-left:10px'>$limg</td></tr>" ;
print "</table>" ;

print "New filename : <input type='text' name='wpDestFile' size='80' value='" . addslashes ( $target_file ) . "' />" ;
print "<p>For manual upload, edit the above text (if necessary), save <a href='{$ii_local->idata['url']}'>the file</a> on your computer, then " ;
print "<input type='submit' name='up' value='upload it to Commons'/>.</p>" ;
print "</form>" ;


// Try direct upload
if ( $use_tusc ) {
	if ( verify_tusc ( $tusc_user , $tusc_password ) ) {
		if ( $allow_upload ) {
			do_direct_upload ( $language , $project , $file , $target_file , $ii_local->idata['url'] , $new_wiki ) ;
		} else {
			show_error ( "Cannot upload directly due to errors!" ) ;
		}
	} else {
		show_error ( "TUSC verification failed!" ) ;
	}
}




endthis() ;

?>
