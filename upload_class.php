<?PHP 

class Upload {
	public $server, $dir, $cookies, $server_dir;
	
	private $socket;
	
	public function __construct( $server, $dir, $server_dir ) {
		$this->server = $server;
		$this->dir = $dir;
		$this->cookies = '';
		$this->server_dir = $server_dir;
		
		if ( ( $this->socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP ) ) === false ) {
			die ( "socket_create() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
	}
	
	public function login( $username, $password ) {
		if ( ( socket_connect( $this->socket, $this->server, 80 ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}

		$message = "lgname=".urlencode($username)."&lgpassword=".urlencode($password);
				
		$query = "POST ".$this->server_dir."/api.php?format=php&action=login HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0alpha (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org) 
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
	
	public function upload( $url, $new_file, $desc, $filename ) {
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
Content-Disposition: form-data; name=\"file\"; filename=\"".$filename."\"

".$file."
--commonshelper2--";

		$query = "POST ".$this->server_dir."/api.php?format=php&action=upload HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0alpha (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org)
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
User-Agent: CommonsHelper2/1.0alpha (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org)
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