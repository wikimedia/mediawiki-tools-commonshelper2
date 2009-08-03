#!/usr/bin/perl
# Upload script by Erik Mˆller - moeller AT scireview DOT de - public domain
# Developed for the Wikimedia Commons
#
# Note: Before usage, create an account on http://commons.wikimedia.org/ 
# following this naming schema: "File Upload Bot (Username)", for example,
# File Upload Bot (Eloquence). This way, these bots can be easily 
# identified and associated with a specific user.
#
# Set the username and password below:

$username = "File Upload Bot (Magnus Manske)";

open FILE , "../upload_bot_key.txt" ;
$password = <FILE>;
chomp $password ;
close FILE ;

# Then run the script on the command line using
#
# $ perl upload.pl dirname
#
# where dirname/ is the name of a directory containing the files to
# be uploaded, and a file named files.txt in the following format
#
# What you write                Explanation
#----------------------------------------------------------------------------
# @{{GFDL}} [[Category:Dog]]    This text is appended to every description.
# ∞Dog photo by Eloquence       This text is used when no description exists.
# >Dog01.jpg                    Name of a file in the specified directory.
# German shepherd dog           Description (can be multi-line).
# >Dog02.jpg                    File without a description (use default)
#
# The "@" and "∞" lines are optional, and must be in one line. They can
# occur multiple times in a single file and are only valid until they
# are changed. As a consequence, description lines cannot start with "@"
# or "∞".
#
# Don't edit below unless you know what you're doing.
#
# Altered by Magnus Manske (2009)

# We need these libraries. They should be part of a standard Perl
# distribution.
use LWP::Simple;
use LWP::UserAgent;
use HTTP::Request;
use HTTP::Response;
use HTTP::Cookies;
use Encode qw(encode);
use warnings;
use utf8;

$ignore_login_error=0;
$docstring="Please read upload.pl for documentation.\n";
my $dir=$ARGV[0] or die "Syntax: perl upload.pl directory\n$docstring";

# Make Unix style path
$dir=~s|\\|/|gi;

# Remove trailing slashes
$sep=$/; $/="/"; chomp($dir); $/=$sep;

# Now try to get the list of files
open(META,"<$dir/meta.txt") 
  or die "Could not find file list at $dir/files.txt.\n$docstring";

my $local_file = <META> ;
my $new_file_name = <META> ;
my $description = '' ;
while ( <META> ) {
	$description .= $_ ;
}
close META ;

# Post-process
$new_file_name =~ tr/ /_/ ;

=cut
$standard_text[0]="";
$default_text[0]="";
$stx=0; $dtx=0;
while(<FILELIST>) {
        $line=$_;
        chomp($line);
        if($line=~m/^@/) {
                $line=~s/^@//;
                $standard_text[$stx]=$line;             
                $stx++;
                $stw=1;
        }
        elsif($line=~m/^∞/) {
                $line=~s/^∞//;
                $default_text[$dtx]=$line;
                $dtx++;         
                $dtw=1;
        }
        elsif($line=~m/^>/) {
                $line=~s/^>//;               

                # New file, but last one doesn't have a description yet -
                # add current default.
                if($currentfile) {
                        # If there's been a change of the default or standard
                        # text, we need to apply the old text to the previous
                        # file, not the new one.
                        $dx= $dtw? $dtx-2 : $dtx -1;
                        $sx= $stw? $stx-2 : $stx -1;                    
                        if(!$desc_added) {                      
                                $file{$currentfile}.="\n".$default_text[$dx];
                        }
                        $file{$currentfile}.="\n\n".$standard_text[$sx];                      
                }                               
                # Abort the whole batch if this file doesn't exist.
                if(!-e "$dir/$line") {
                        die "Could not find $dir/$line. Uploading no files.\n"
                
                }               
                $currentfile=$line;
                $desc_added=0;          
                $dtw=0;$stw=0;
        }else {
                # If this is a header comment,
                # we just ignore it. Otherwise
                # it's a file description.              
                if($currentfile) {
                        $file{$currentfile}.="\n".$line;
                        $desc_added=1;
                }
        }
}

# Last file needs to be processed, too
if($currentfile) {              
        $dx= $dtw? $dtx-2 : $dtx -1;
        $sx= $stw? $stx-2 : $stx -1;
        if(!$desc_added) {
                $file{$currentfile}.="\n".$default_text[$dx];
        }
        $file{$currentfile}.="\n\n".$standard_text[$sx];      
}                               
=cut






my $browser=LWP::UserAgent->new();
  my @ns_headers = (
   'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7) Gecko/20041107 Firefox/1.0',
   'Accept' => 'image/gif, image/x-xbitmap, image/jpeg, 
        image/pjpeg, image/png, */*',
   'Accept-Charset' => 'iso-8859-1,*,utf-8',
   'Accept-Language' => 'en-US',
  );

$browser->cookie_jar( {} );

$response=$browser->post("http://commons.wikimedia.org/w/index.php?title=Special:Userlogin&action=submitlogin",
@ns_headers, Content=>[wpName=>$username,wpPassword=>$password,wpRemember=>"1",wpLoginAttempt=>"Log in"]);

# After logging in, we should be redirected to another page. 
# If we aren't, something is wrong.
#
if($response->code!=302 && !$ignore_login_error) {
        print 
"We weren't able to login. This could have the following causes:

* The username ($username) or password may be incorrect.
  Solution: Edit upload.pl and change them.
* The Wikimedia Commons software has been upgraded.
  Solution: Go to http://commons.wikimedia.org/wiki/Commons:File_upload_service
  and get a new version of the upload script.
* You are trying to hack this script for other wikis. The wiki you
  are uploading to has cookie check disabled.
  Solution: Try setting \$ignore_login_error to 1.

Regardless, we will now try to write the output from the server to 
$dir/debug.txt....\n\n";
        open(DEBUG,">$dir/debug.txt") or die "Could not write file.\n";
        print DEBUG $response->as_string;
        print 
"This seems to have worked. Take a look at the file for further information or
send it to moeller AT scireview DOT de if you need help debugging the script.\n";
        close(DEBUG);
        exit 1;
}




my $response ;
do {
	$response=$browser->post("http://commons.wikimedia.org/wiki/Special:Upload",
		@ns_headers,Content_Type=>'form-data',Content=>
		[
				wpUploadFile=>[$local_file],
				wpDestFile => $new_file_name,
				wpUploadDescription=>$description,
				wpUploadAffirm=>"1",
				wpUpload=>"Upload file",
				wpIgnoreWarning=>"1"
		]);
} while ( $response->code!=302 && $response->code!=200 ) ;


=cut
foreach $key(keys(%file)) {
        print "Uploading $key to the Wikimedia Commons. Description:\n";      
        print $file{$key}."\n" . "-" x 75 . "\n";
        uploadfile:
        $eckey=encode('utf8',$key);
        if($eckey ne $key) {
                symlink("$key","$dir/$eckey");
        }
        $response=$browser->post("http://commons.wikimedia.org/wiki/Special:Upload",
        @ns_headers,Content_Type=>'form-data',Content=>
        [
                wpUploadFile=>["$dir/$eckey"],
                wpUploadDescription=>encode('utf8',$file{$key}),
                wpUploadAffirm=>"1",
                wpUpload=>"Upload file",
                wpIgnoreWarning=>"1"
        ]);
        push @responses,$response->as_string;
        if($response->code!=302 && $response->code!=200) {
                print "Upload failed! Will try again. Output was:\n";
                print $response->as_string;
                goto uploadfile;
        } else {
                print "Uploaded successfully.\n";
        }               
}
=cut


print "Everything seems to be OK. Log will be written to $dir/debug.txt.\n";
open(DEBUG,">$dir/debug.txt") or die "Could not write file.\n";
print DEBUG @responses;
