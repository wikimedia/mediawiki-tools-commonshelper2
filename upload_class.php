<?PHP 
//require_once  ( './global_functions.php' ) ;
//require_once  ( './commonshelper2.i18n.php' ) ;

class Upload {
	public $server, $dir, $cookies, $server_dir;
	
	public $url, $new_filename, $desc;
	
	private $socket;
	
	public function __construct( $server, $dir, $server_dir, $url, $new_filename, $desc ) {
		$this->server = $server;
		$this->dir = $dir;
		$this->cookies = '';
		$this->server_dir = $server_dir;
		$this->url = $url;
		$this->new_filename = $new_filename;
		$this->desc = $desc;
		
		if ( ( $this->socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP ) ) === false ) {
			die ( "socket_create() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
	}
	
	public function upload_control( $serialize, $thumbnail ) { ?>		
<form method='post' action='index.php'>
<table style='width:100%'>
<tr>
<td style='width:100%'><?PHP echo msg( 'upload_control_text' ); ?></td>
</tr>
<tr>
<td style='width:100%'>
<h3><?PHP echo msg( 'new_wikitext' ); ?></h3>
<textarea rows='20' cols='125' style='background:#D0E6FF;padding:2px;border:2px solid #DDDDDD;width:100%' name='UploadDescription'><?PHP echo $this->desc; ?></textarea>
</td>
<td nowrap valign='top' style='padding-left:10px'><?PHP echo $thumbnail; ?></td>
</tr>
<tr>
<td style='width:100%'><input type="hidden" name="stage" value="upload" />
<input type="hidden" name="server" value="<?PHP echo $this->server ?>" />
<input type="hidden" name="dir" value="<?PHP echo $this->dir ?>" />
<input type="hidden" name="server_dir" value="<?PHP echo $this->server_dir ?>" />
<input type="hidden" name="url" value="<?PHP echo $this->url ?>" />
<input type="hidden" name="new_filename" value="<?PHP echo $this->new_filename ?>" />
<input type="submit" value="Upload!" /></td>
</tr>
</table>
	<?PHP
	}
	
	public function setDesc( $desc ) {
		$this->desc = $desc;
	}
	
	public function login( $username, $password ) {
		if ( ( socket_connect( $this->socket, $this->server, 80 ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}

		$message = "lgname=".urlencode($username)."&lgpassword=".urlencode($password);
				
		$query = "POST ".$this->server_dir."/api.php?format=php&action=login HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0 (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org) 
Content-Type: application/x-www-form-urlencoded
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";

		if ( ( socket_write( $this->socket, $query ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
		
		$answer = "";
		do {
			if ( ( $buf = socket_read( $this->socket, 99999, PHP_NORMAL_READ ) ) === false ) {
				die ( "socket_read() has problem: Error: " . socket_strerror(socket_last_error()) );
			}
			
			if ($buf == "") break;
			
			$answer .= $buf;
		} while(true);
		
		if ( ( socket_close( $this->socket ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
			
		preg_match ("/^(.*)\r\n\r\n/is",$answer,$match);
		$headers = $match[1];
			
		preg_match ("/\r\n\r\n(.*)$/is",$answer,$match);
		$data = $match[1];
			
		preg_match_all('@Set-Cookie: (.*)=(.*); (expires|path)(.*)@isU',$headers,$match,PREG_SET_ORDER);
		foreach($match as $k=>$v) {
			$this->cookies.=$v[1]."=".$v[2].";";
		}
			
		$debug_file = $this->dir."debug.txt";
		$debug = fopen($debug_file, "w");
		fwrite($debug, $answer);
		fclose($debug);

		var_dump( unserialize( $data ) );
	}
	
	public function upload() {
		$url = $this->url;
		$new_file = $this->new_filename;
		$desc = $this->desc;
	
		if ( ( socket_connect( $this->socket, $this->server, 80 ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}

		$token = $this->get_token();
		
		$file = file_get_contents( $url );
			
		$message = "--commonshelper2
Content-Type: application/x-www-form-urlencoded

ignorewarnings=1&filename=".urlencode($new_file)."&token=".urlencode($token)."&file=".urlencode($filename)."&comment=".urlencode($desc)."
		
--commonshelper2
Content-Type: application/octet-stream
Content-Disposition: form-data; name=\"file\"; filename=\"".$new_file."\"

".$file."
--commonshelper2--";

		$query = "POST ".$this->server_dir."/api.php?format=php&action=upload HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0 (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org)
Content-Type: multipart/form-data; boundary=commonshelper2
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";

		if ( ( socket_write( $this->socket, $query ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
		
		$answer = "";
		do {
			if ( ( $buf = socket_read( $this->socket, 99999, PHP_NORMAL_READ ) ) === false ) {
				die ( "socket_read() has problem: Error: " . socket_strerror(socket_last_error()) );
			}
			
			if ($buf == "") break;
			
			$answer .= $buf;
		} while(true);
		
		if ( ( socket_close( $this->socket ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}

		
		preg_match ("/^(.*)\r\n\r\n/is",$answer,$match);
		$headers = $match[1];
		
		preg_match ("/\r\n\r\n(.*)$/is",$answer,$match);
		$data = $match[1];
		
		preg_match_all('@Set-Cookie: (.*)=(.*); (expires|path)(.*)@isU',$headers,$match,PREG_SET_ORDER);
		foreach($match as $k=>$v) {
			$this->cookies.=$v[1]."=".$v[2].";";
		}
		$debug_file = $this->dir."debug.txt";
		$debug = fopen($debug_file, "w");
		fwrite($debug, $answer);
		fclose($debug);
		
		var_dump( unserialize( $data ) );
	}

	
	private function get_token() {
		if ( ( socket_connect( $this->socket, $this->server, 80 ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
		
		$message = "titles=Main%20Page&prop=info&intoken=edit";
		
		$query = "POST ".$this->server_dir."/api.php?format=php&action=query HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0 (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org)
Content-Type: application/x-www-form-urlencoded
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";

		if ( ( socket_write( $this->socket, $query ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
		
		$answer = "";
		do {
			if ( ( $buf = socket_read( $this->socket, 99999, PHP_NORMAL_READ ) ) === false ) {
				die ( "socket_read() has problem: Error: " . socket_strerror(socket_last_error()) );
			}
			
			if ($buf == "") break;
			
			$answer .= $buf;
		} while(true);
		
		if ( ( socket_close( $this->socket ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
		
		preg_match ("/^(.*)\r\n\r\n/is",$answer,$match);
		$headers = $match[1];
		
		preg_match ("/\r\n\r\n(.*)$/is",$answer,$match);
		$data = $match[1];
		
		preg_match_all('@Set-Cookie: (.*)=(.*); (expires|path)(.*)@isU',$headers,$match,PREG_SET_ORDER);
		foreach($match as $k=>$v) {
			$this->cookies.=$v[1]."=".$v[2].";";
		}
		
		$data = unserialize( $data );
		
		$token = $data["query"]["pages"];
		$token = array_shift($token);
		$token = $token['edittoken'];
		
		var_dump( unserialize( $data ) );
		
		return $token;
	}
}

?>