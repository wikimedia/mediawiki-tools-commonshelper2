<?PHP 
ini_set('max_execution_time','120');
error_reporting ( E_ALL ) ;

require_once  ( './global_functions.php' ) ;
require_once( '/home/project/i/n/t/intuition/ToolserverI18N/ToolStart.php' );

$I18N = new TsIntuition();
//$I18N->loadTextdomainFromFile( __DIR__ . '/commonshelper2.i18n.php', 'commonshelper2' );
$I18N->setDomain( 'commonshelper2' );

$language = get_request ( 'language' , msg( 'standard_language' ) ) ;
$project = get_request ( 'project' , msg( 'standard_project' ) ) ;

header('Content-type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html> 
<head> 
<title>CommonsHelper 2</title> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<?PHP 
echo '<dir lang="'.$I18N->getLang().'" dir="'.$I18N->getDir().'">'; 
?>
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
<?PHP echo $I18N->dashboardBacklink(); ?>
</small>
</td>
<td align="right" width='50%' valign='bottom'>
<small><a href='https://jira.toolserver.org/browse/CHTWO'><?PHP echo msg( 'jira_link' ); ?></a></small>
</td>
</tr>
</table>
<?PHP

echo msg( 'help_intro' );
?>
<ul>
<li><?PHP echo msg( 'help_jira', '<a href="https://jira.toolserver.org/secure/CreateIssue!default.jspa">', '</a>', '(Project: Commonshelper2)' );?></li>
<li><?PHP echo msg( 'help_mail', '<a href="mailto:jan@toolserver.org">jan@toolserver.org</a>' );?></a></li>
<li><?PHP echo msg( 'help_commons', 'http://commons.wikimedia.org/wiki/Commons_talk:CommonsHelper_2', '</a>' ); ?></li>
</ul>
</body>
</html>