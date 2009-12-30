<?PHP 

class Upload {
	public $server, $dir, $cookies;
	
	public function __construct( $server, $dir ) {
		$this->server = $server;
		$this->dir = $dir;
		$this->cookies = '';
	}
	
	public function login( $username, $password ) {
		$connect = fsockopen ($this->server, 80, $err_num, $err_str, 10);
		
		$message = "lgname=".urlencode($username)."&lgpassword=".urlencode($password);
		
		if( $connect ) {
			$query = "POST /w/api.php?format=php&action=login HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7) Gecko/20041107 Firefox/1.0
Content-Type: application/x-www-form-urlencoded
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";
			fputs ($connect,$query);
		
			$answer = "";
			while (!feof($connect)) {
				$answer.= fgets($connect);
			}
			fclose($connect);
			
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
			
			return unserialize( $data );
		}
	}
	
	public function upload( $url, $new_file, $desc, $filename ) {
		$connect = fsockopen ($this->server, 80, $err_num, $err_str, 10);
		
		$token = $this->get_token();
		
		if( $connect ) {
			$file = file_get_contents( $url );
			
			$message = "--commonshelper2
Content-Type: application/octet-stream
Content-Disposition: form-data; name=\"file\"; filename=\"".$filename."\"

".$file."
--commonshelper2--";

			$query = "POST /w/api.php?format=php&action=upload&ignorewarnings=1&filename=".urlencode($new_file)."&token=".urlencode($token)."&file=".urlencode($filename)."&comment=".urlencode($desc)." HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7) Gecko/20041107 Firefox/1.0
Content-Type: multipart/form-data; boundary=commonshelper2
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";
			fputs ($connect,$query);
		
			$answer = "";
			while (!feof($connect)) {
				$answer.= fgets($connect);
			}
			fclose($connect);
			
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
		}
	}
	
	private function get_token() {
		$connect = fsockopen ($this->server, 80, $err_num, $err_str, 10);
		
		$message = "titles=Main%20Page&prop=info&intoken=edit";
		
		if( $connect ) {
			$query = "POST /w/api.php?format=php&action=query HTTP/1.1
Host: ".$this->server."
Cookie: ".$this->cookies."
User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7) Gecko/20041107 Firefox/1.0
Content-Type: application/x-www-form-urlencoded
Content-Length: ".strlen($message)."

".$message."
\r\n\r\n";
			fputs ($connect,$query);
		
			$answer = "";
			while (!feof($connect)) {
				$answer.= fgets($connect);
			}
			fclose($connect);
			
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
			
			return $token;
		}
	}
}

?>