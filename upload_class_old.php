<?PHP 
require_once 'HTTP/Request2.php';

class Upload {
	public $server, $dir, $cookies, $server_dir;
	
	public $url, $new_filename, $desc;
	
	private $request, $headers, $data;
	private $readed_data = false;
	
	public function __construct( $server, $server_dir, $url, $new_filename, $desc ) {
		xdebug_break();		
		$this->server = $server;
		$this->cookies = array();
		$this->server_dir = $server_dir;
		$this->url = $url;
		$this->new_filename = $new_filename;
		$this->desc = $desc;
		
		$this->request = null;
	}
	
	public function upload_control( $thumbnail ) { 
		global $transfer_user;
	
	?>		
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
<input type="hidden" name="server" value="<?PHP echo $this->server; ?>" />
<input type="hidden" name="server_dir" value="<?PHP echo $this->server_dir; ?>" />
<input type="hidden" name="url" value="<?PHP echo $this->url; ?>" />
<input type="hidden" name="new_filename" value="<?PHP echo $this->new_filename; ?>" />
<input type="hidden" name="transfer_user" value="<?PHP echo $transfer_user; ?>" />
<input type="submit" value="Upload!" /></td>
</tr>
</table>
</form>
	<?PHP
	}
	
	public function setDesc( $desc ) {
		$this->desc = $desc;
	}
	
	public function login( $username, $password ) {
		//$message = "lgname=".urlencode($username)."&lgpassword=".urlencode($password);
				
		/*$query = "POST ".$this->server_dir."/api.php?format=php&action=login HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0 Beta (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org) 
Content-Type: application/x-www-form-urlencoded
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";*/

		$query_url = "http://".$this->server.$this->server_dir."/api.php?format=php&action=login";
		$query_data = array( "lgname" => $username, "lgpassword" => $password );

		$result = $this->query_http( $query_url, $query_data );
		
		var_dump( unserialize( $result['data'] ) );
		
		$login_first = unserialize( $result['data'] );

		$login_token = $login_first["login"]["token"];
		
		echo $login_token;

		//$message = "lgname=".urlencode($username)."&lgpassword=".urlencode($password)."&lgtoken=".urlencode($login_token);
				
		/*$query = "POST ".$this->server_dir."/api.php?format=php&action=login HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0 Beta (jan@toolserver.org)
Content-Type: application/x-www-form-urlencoded
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";*/

		$query_url = "http://".$this->server.$this->server_dir."/api.php?format=php&action=login";
		$query_data = array( "lgname" => $username, "lgpassword" => $password, "lgtoken" => $login_token );

		$result = $this->query_http( $query_url, $query_data );
		
		var_dump( unserialize( $result['data'] ) );
	}
	
	public function upload() {
		$url = $this->url;
		$new_file = $this->new_filename;
		if( !$this->controll_desc() ) { 
			show_error ( msg( 'error_commons_user' ) ) ;
			endthis();
		}
		$desc = $this->desc;

		$token = $this->get_token();
		/*
		$file = file_get_contents( $url );
		$boundary = "commonshelper2".time();

--".$boundary."
Content-Disposition: form-data; name=\"file\"

".urlencode("file")."

		$message = 
"--".$boundary."
Content-Disposition: form-data; name=\"ignorewarnings\"

1
--".$boundary."
Content-Disposition: form-data; name=\"filename\"

".$new_file."
--".$boundary."
Content-Disposition: form-data; name=\"comment\"

".$desc."
--".$boundary."
Content-Disposition: form-data; name=\"token\"

".$token."
--".$boundary."
Content-Type: application/octet-stream
Content-Disposition: form-data; name=\"file\"; filename=\"".$new_file."\"

".$file."
--".$boundary."--";

		$query = "POST ".$this->server_dir."/api.php?format=php&action=upload HTTP/1.1 
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0 Beta (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org)
Content-Type: multipart/form-data; boundary=".$boundary."
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n"; */

		do {
			$temp_name = tempnam ( "/tmp" , "ch" ) ;
			$temp = @fopen ( $temp_name , "w" ) ;
		} while ( $temp === false ) ;
		$temp_dir = $temp_name . "-dir" ;
		mkdir ( $temp_dir ) ;

		// Copy remote file to local file
		$short_file = 'dummy.' . array_pop ( explode ( '.' , $url ) ) ;
		$local_file = $temp_dir . "/" . $short_file ;
		if ( !copy($url, $local_file) ) {
			rmdir ( $temp_dir ) ;
			fclose ( $temp ) ;
			unlink ( $temp_name ) ;
			show_error( msg( 'error_upload_file' ) );
			endthis();
		}

		$query_url = "http://".$this->server.$this->server_dir."/api.php?format=php&action=upload";
		$query_data = array( "ignorewarnings" => "1", "filename" => $new_file, "comment" => $desc,
						"token" => $token );
		$query_upload = array();
		$query_upload[] = array('fieldName' => 'file', 'path' => $local_file, 
						'sendFilename' => $new_file);

		$result = $this->query_http( $query_url, $query_data, $query_upload );
		
		echo $result['data'];
		
		var_dump( unserialize( $result['data'] ) );

		unlink ( $local_file ) ;
		rmdir ( $temp_dir ) ;
		fclose ( $temp ) ;
		unlink ( $temp_name ) ;
	}
	
	private function get_token() {
		//$message = "titles=Main%20Page&prop=info&intoken=edit";
		
		/*$query = "POST ".$this->server_dir."/api.php?format=php&action=query HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: CommonsHelper2/1.0 Beta (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org)
Content-Type: application/x-www-form-urlencoded
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";*/

		$query_url = "http://".$this->server.$this->server_dir."/api.php?format=php&action=query";
		$query_data = array( "titles" => "Main Page", "prop" => "info", "intoken" => "edit" );

		$result = $this->query_http( $query_url, $query_data );
		
		$data = unserialize( $result['data'] );
		
		$token = $data["query"]["pages"];
		$token = array_shift($token);
		$token = $token['edittoken'];
		
		return $token;
	}
	
	private function query_http( $url, $post = array(), $upload = array() ) {
		$this->request = new HTTP_Request2($url, HTTP_Request2::METHOD_POST,
			array('follow_redirects' => true, 'strict_redirects' => true));
			
		$this->request->setHeader('user-agent', 'CommonsHelper2/1.0 Beta ' .
						 '(http://toolserver.org/~commonshelper2/index.php jan@toolserver.org) ' .
                         'PHP/' . phpversion());
			
		$this->request->addPostParameter($post);
		
		foreach($upload as $value) {
			$this->request->addUpload($value['fieldName'], $value['path'],
				$value['sendFilename']);
		}
		
		foreach($this->cookies as $k => $v) {
			$this->request->addCookie($v['name'], $v['value']);
		}
		
		$response = $this->request->send();
		
		$headers = $response->getHeader();
		$data = $response->getBody();
		$this->cookies = array_merge($this->cookies, $response->getCookies());
		/*
		$this->data = "";
		
		curl_setopt($this->curl, CURLOPT_URL, $url);
		
		curl_setopt( $this->curl, CURLOPT_USERAGENT, 
		"CommonsHelper2/1.0 Beta (http://toolserver.org/~commonshelper2/index.php jan@toolserver.org)" );
		
		curl_setopt( $this->curl, CURLOPT_WRITEFUNCTION, array( &$this, 'query_http_data' ) );
			
		curl_setopt_array( $this->curl, $options );
		echo "Hier ";
		curl_exec( $this->curl );
		echo "Hier 3";
		echo $this->data;
		if( $exit ) curl_exit( $this->curl );
		
		while( !$this->readed_data ) continue;
		
		$this->readed_data = false;
		*/
		return array( 'headers' => $header, 'data' => $data );
	}
	
	function query_http_data( $curl, $data ) {
		$this->data .= $data;
		$len = strlen( $data );
		$this->readed_data = true;
		echo "Hier 2";
		return $len;
	}
	
	private function query_http_local( $query ) {
		if ( ( $this->socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP ) ) === false ) {
			die ( "socket_create() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
		
		if ( ( socket_connect( $this->socket, "localhost", 80 ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}

		if ( ( socket_write( $this->socket, $query ) ) === false ) {
			die ( "socket_connect() has problem: Error: " . socket_strerror(socket_last_error()) );
		}
		
		$answer = "";
		do {
			if ( ( $buf = socket_read( $this->socket, 99999 ) ) === false ) {
				die ( "socket_read() has problem: Error: " . socket_strerror(socket_last_error()) );
			}
	
			$answer .= $buf;
		} while( $buf != "" );
		
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
		
		return array( 'headers' => $headers, 'data' => $data );
	}
	
	private function controll_desc() {
		global $transfer_user;
		if( preg_match( "/".$transfer_user."/i", $this->desc ) ) return true;
		else return false;
	}
}

?>