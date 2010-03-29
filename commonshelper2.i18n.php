<?PHP
// Initialize array for the languages
$message = array();

// English
$message['en'] = array(
	// Title
	'commonshelper2'     => 'CommonsHelper 2',
	'description'        => 'A tool to transfer files from Wikimedia projects to Wikimedia Commons',
	'change_meta'        => 'Change the $1category and template settings$2 for $3', // $1 and $2 are for the link tag (<a href=...> and </a>), $3 for target wiki name
	'jira_link'          => 'Report a bug or suggest a feature',
	
	// Main form
	'language'           => 'Language',
	'project'            => 'Project',
	'source_file'        => 'Source-File',
	'target_file'        => 'Target-File',
	'commons_username'   => 'Commons-Username',
	'commons_to_project' => 'Commons to project',
	'move_file_from_com' => 'Move file from commons to project',
	'categories'         => 'Categories',
	'remove_cats'        => 'Remove existing categories',
	'checkusage'         => 'CheckUsage',
	'use_checkusage'     => 'Use $1CheckUsage$2 to suggest new categories', // $1 and $2 are for the link tag (<a href=...> and </a>)
	'tusc'               => 'TUSC',
	'use_tusc'           => 'Use $1TUSC$2 to transfer the file directly', // $1 and $2 are for the link tag (<a href=...> and </a>)
	'tusc_user'          => 'TUSC user name',
	'tusc_pass'          => 'TUSC password',
	
	// Error
	'error_transfer_usr' => 'You have not set a Commons-Username',
	'error_not_exists'   => 'Source file does not exist!',
	'error_file_exists'  => 'File already exists on $3 as "$1$4$2"!', // $1 and $2 are for the link tag (<a href=...> and </a>), $3 'Commons' or message 'target_wiki', $4 is the name of the file
	'error_diff_exists'  => 'Different target file exists on the target wiki under the same name!',
	'error_no_meta'      => 'No meta data found for the source wiki! $1Link$2',	// $1 and $2 are for the link tag (<a href=...> and </a>)
	'error_meta_no_good' => 'Meta-Data: No good templates found! $1Link$2',	// $1 and $2 are for the link tag (<a href=...> and </a>)
	'error_meta_bad'     => 'Meta-Data: Bad template found! $1Link$2',	// $1 and $2 are for the link tag (<a href=...> and </a>)
	
	// Output
	'original_wikitext'  => 'Original wikitext',
	'new_wikitext'       => 'New wikitext',
	'new_filename'       => 'New filename:',
	'output_information' => 'For manual upload, edit the above text (if necessary), save $1the file$2 on your computer, then', // $1 and $2 are for the link tag (<a href=...> and </a>)
	'upload_it'          => 'upload it',
	
	// Misc
	'target_wiki'        => 'the target wiki',
);

// German
$message['de'] = array(
	// Title
	'commonshelper2'     => 'CommonsHelper 2',
	'description'        => 'Ein Tool, um Dateien von Wikimedia Projekten nach Wikimedia Commons zu transportieren',
);

// Hebrew
$message['he'] = array(
	// Title
	'commonshelper2'     => 'CommonsHelper 2',
	'description'        => 'כלי להעברת קבצים ממיזמים של קרן ויקימדיה לוויקישיתוף',
	'change_meta'        => 'לשנות את $1הגדרות הקטגוריות והתבניות$2 עבור $3', // $1 and $2 are for the link tag (<a href=...> and </a>), $3 for target wiki name
	'jira_link'          => 'דיווח באג או הצעת שיפור',
	
	// Main form
	'language'           => 'שפת המיזם',
	'project'            => 'שם המיזם',
	'source_file'        => 'קובץ מקור',
	'target_file'        => 'קובץ יעד',
	'commons_username'   => 'שם משתמש בוויקישיתוף',
	'commons_to_project' => 'מוויקישיתוף למיזם',
	'move_file_from_com' => 'העברת קובץ מוויקישיתוף למיזם מקומי',
	'categories'         => 'קטגוריות',
	'remove_cats'        => 'מחיקת קטגוריות קיימות',
	'checkusage'         => 'CheckUsage',
	'use_checkusage'     => 'להשתמש ב־$1CheckUsage$2 כדי לאתר קטגוריות', // $1 and $2 are for the link tag (<a href=...> and </a>)
	'tusc'               => 'TUSC',
	'use_tusc'           => 'להשתמש ב־$1TUSC$2 כדי להעביר את הקובץ ישירות', // $1 and $2 are for the link tag (<a href=...> and </a>)
	'tusc_user'          => 'שם משתמש ב־TUSC',
	'tusc_pass'          => 'סיסמה ב־TUSC',
	
	// Error
	'error_transfer_usr' => 'לא יצרת שם משתמש בוויקישיתוף',
	'error_not_exists'   => 'קובץ המקור לא קיים!',
	'error_file_exists'  => 'הקובץ כבר קיים ב־$3 בשם "$1$4$2"!', // $1 and $2 are for the link tag (<a href=...> and </a>), $3 'Commons' or message 'target_wiki', $4 is the name of the file
	'error_diff_exists'  => 'קובץ שונה קוום באתר היעד באותו שם!',
	'error_no_meta'      => 'No meta data found for the source wiki! $1Link$2',	// $1 and $2 are for the link tag (<a href=...> and </a>)
	'error_no_meta'      => 'לא נמצאו מטא־נתונים בוויקי המקור! $1Link$2',	// $1 and $2 are for the link tag (<a href=...> and </a>)
	'error_meta_no_good' => 'מטא־נתונים: לא נמצאו תבניות קבילות! $1Link$2',	// $1 and $2 are for the link tag (<a href=...> and </a>)
	'error_meta_bad'     => 'מטא־נתונים: נמצאו תבניות לא קבילות! $1Link$2',	// $1 and $2 are for the link tag (<a href=...> and </a>)
	
	// Output
	'original_wikitext'  => 'תיאור מקורי',
	'new_wikitext'       => 'תיאור חדש',
	'new_filename'       => 'שם קובץ חדש:',
	'output_information' => 'For manual upload, edit the above text (if necessary), save $1the file$2 on your computer, then', // $1 and $2 are for the link tag (<a href=...> and </a>)
	'output_information' => 'לשם העלאה ידנית יש לערוך את הטקסט למעלה (אם זה נחוץ), לשמור את הקובץ $1the file$2 על המחשב שלך ואז', // $1 and $2 are for the link tag (<a href=...> and </a>)
	'upload_it'          => 'להעלות אותו',
	
	// Misc
	'target_wiki'        => 'ויקי היעד',
);

?>