<?php
function rejectEmpty($element){return !empty($element);}

class imapAccessWrapper implements AjxpWrapper {
	
	var $ih;
	var $host;
	var $port;
	var $username;
	var $password;
	var $path;
	// stuff for dir reading
	var $dir;
	var $pos;
	// stuff for file reading
	var $data;
	var $gotbody;
	var $size;
	var $time;
	
	var $fragment;
	var $mailbox;
	
	var $mailboxes;
	
	private static $currentStream;
	private static $currentRef;
	private static $currentCount;
	
	public static function closeStreamFunc(){
		if(self::$currentStream){
			imap_close(self::$currentStream);
		}
	}
	
	public static function getCurrentDirCount(){
		return self::$currentCount;
	}
		
	function stream_open($path, $mode, $options, &$opened_path) {
		// parse URL
		$parts = parse_url($path);		
		$this->path = substr($parts["path"], 1);
		//$this->mailbox = "INBOX";
		$pathParts = explode("/", $this->path);
		$pathParts = array_filter($pathParts, "rejectEmpty");
		if(count($pathParts) > 1){
			$this->path = array_pop($pathParts);
			$this->mailbox = implode("/", $pathParts);
		}else if(count($pathParts) == 1){
			$this->mailbox = implode("/", $pathParts);
			$this->path = "";
		}else{
			$this->mailbox = "";
			$this->path = "";
		}
		$this->fragment = $parts["fragment"];
		if (empty ( $this->path ) && $mode !== 'np') {
			return false;
		}
		if (!empty($this->mailbox)){
			$this->mailbox = str_replace("__delim__", "/", $this->mailbox);
		}
		
		// open IMAP connection
		if(self::$currentStream != null){
			$this->ih = self::$currentStream;
			// Rewind everything
			$this->dir_rewinddir();
			$this->stream_seek(0);
		}else{
			$this->repositoryId = $parts["host"];
			$repository = ConfService::getRepositoryById($this->repositoryId);		
			$ssl = $repository->getOption("SSL") == "true" ? true: false ;
			$pop3 = $repository->getOption("BOX_TYPE") == "pop3" ? true : false;
			$this->host = $repository->getOption("HOST");
			$this->port = $repository->getOption("PORT");
			$this->username = $repository->getOption("USER");
			$this->password = $repository->getOption("PASS");
			$server = "{". $this->host . ":" . $this->port . "/".($pop3?"pop3/":"").($ssl?"ssl/novalidate-cert":"")."}".$this->mailbox;
			self::$currentRef = $server;
			//AJXP_Logger::debug("Opening stream ".$server." with mailbox '".$this->mailbox."'");
			$this->ih = imap_open ( $server , $this->username, $this->password, (empty($this->mailbox)?OP_HALFOPEN:NULL), 1);
			self::$currentStream = $this->ih;
			if(!empty($this->mailbox)){
				register_shutdown_function(array("imapAccessWrapper", "closeStreamFunc"));
			}
		}
		if ($this->ih) {
			if (! empty ( $this->path )) {
				list ( $stats, ) = imap_fetch_overview ( $this->ih, $this->path );
				$this->size = $stats->size;
				$this->time = strtotime ( $stats->date );
			}
			return true;
		} else {
			return false;
		}
	}
	
	function stream_close() {
		if(empty($this->mailbox)){
			self::$currentStream = null;
			imap_close ( $this->ih );
		}
	}
	
	/* Smart reader, at first it only downloads the header to memory, but if a read request is made
       beyond the header, we download the rest of the body */
	function stream_read($count) {
		// smart... only download the header WHEN data is requested
		if (empty ( $this->data )) {
			$this->pos = 0;
			$this->gotbody = false;
			$this->data = imap_fetchheader ( $this->ih, $this->path );
		}
		// only download the body once we read past the header
		if ($this->gotbody == false && ($this->pos + $count > strlen ( $this->data )) && $this->fragment != "header") {
			$this->gotbody = true;
			$this->data .= imap_body ( $this->ih, $this->path );
			$this->size = strlen ( $this->data );
		}
		if ($this->pos >= $this->size) {
			return false;
		} else {
			$d = substr ( $this->data, $this->pos, $count );
			if ($this->pos + $count > strlen ( $this->data )) {
				$this->pos = strlen ( $this->data );
			} else {
				$this->pos = $this->pos + $count;
			}
			return $d;
		}
	}
	
	/* Can't write to POP3 */
	function stream_write($data) {
		return false;
	}
	
	function stream_eof() {
		if ($this->pos == $this->size) {
			return true;
		} else {
			return false;
		}
	}
	
	function stream_tell() {
		return $this->pos;
	}
	
    public function stream_seek($offset , $whence = SEEK_SET){
    	switch ($whence) {
			case SEEK_SET :
				$this->pos = $offset;
				break;
			case SEEK_CUR :
				$this->pos = $this->pos + $offset;
				break;
			case SEEK_END :
				$this->pos = $this->size + $offset;
				break;
		}
	}
	
	function stream_stat() {
		$keys = array('dev' => 0, 'ino' => 0, 'mode' => 33216, 'nlink' => 0, 'uid' => 0, 'gid' => 0, 'rdev' => 0, 'size' => $this->size, 'atime' => $this->time, 'mtime' => $this->time, 'ctime' => $this->time, 'blksize' => 0, 'blocks' => 0 );
		return $keys;
	}
		
	function dir_opendir($path, $options) {
		$st = '';
		$stream = $this->stream_open ( $path, 'np', $options, $st ); 
		if (!$stream) {
			return false;
		}
		if(empty($this->mailbox)){
			// We are browsing root, we want the list of mailboxes
			$this->mailboxes = imap_getmailboxes($this->ih, self::$currentRef, "*");
			$this->dir = count($this->mailboxes)-1;
		}else{
			// We are in a mailbox, we want the messages number
			$this->dir = imap_num_msg ( $this->ih );
		}
		self::$currentCount = $this->dir;
		$this->pos = $this->dir;			
		$this->stream_close ();
		return true;			
	}
	
	function dir_closedir() {
		// do nothing.
		// $this->stream_close();
		$this->mailboxes = null;
	}
	
	function url_stat($path, $flags) {
		$emptyString = '';
		if ($this->stream_open ( $path, 'np', $flags, $emptyString)) {
			if(!empty($this->path)){
				// Mail
				$stats = array();
				list ( $stats, ) = imap_fetch_overview ( $this->ih, $this->path );
				$time = strtotime ( $stats->date );
				$keys = array(
					'dev' => 0, 
					'ino' => 0, 
					'mode' => 33216, 
					'nlink' => 0, 
					'uid' => 0, 
					'gid' => 0, 
					'rdev' => 0, 
					'size' => $stats->size, 
					'atime' => $time, 
					'mtime' => $time, 
					'ctime' => $time,
					'blksize' => 0, 
					'blocks' => 0 );
			}else{
				// BOX
				$keys = array(
					'dev' => 0, 
					'ino' => 0, 
					'mode' => 33216 | 0040000, 
					'nlink' => 0, 
					'uid' => 0, 
					'gid' => 0, 
					'rdev' => 0,
					'size' => 0, 
					'atime' => 0, 
					'mtime' => 0, 
					'ctime' => 0, 
					'blksize' => 0, 
					'blocks' => 0 
				);
			}
			$this->stream_close ();
			return $keys;
		} else {
			return false;
		}
	}
	
	function dir_readdir() {
		if($this->mailboxes){
			if($this->pos == 0) return false;
			else{
				$obj = $this->mailboxes[$this->pos];
				$this->pos --;
				$x = $obj->name;
				$x = str_replace(self::$currentRef, "", $x);
				$x = str_replace($obj->delimiter, "__delim__", $x);
			}
		}else{
			if ($this->pos <= 1) {
				return false;
			} else {
				$x = $this->pos;
				$this->pos --;
			}			
		}
		return $x;
	}
	
	function dir_rewinddir() {
		$this->pos = 1;
	}
	
	/* Delete an email from the mailbox */
	function unlink($path) {
		$st='';
		if ($this->stream_open ( $path, '', '', $st )) {
			imap_delete ( $this->ih, $this->path );
			$this->stream_close ();
			return true;
		} else {
			return false;
		}
	}

	
	/**
	 * Get a "usable" reference to a file : the real file or a tmp copy.
	 *
	 * @param unknown_type $path
	 */
    public static function getRealFSReference($path){
    	return $path;
    }
    
    /**
     * Read a file (by chunks) and copy the data directly inside the given stream.
     *
     * @param unknown_type $path
     * @param unknown_type $stream
     */
    public static function copyFileInStream($path, $stream){
    	//return $path;
    }
    
    /**
     * Chmod implementation for this type of access.
     *
     * @param unknown_type $path
     * @param unknown_type $chmodValue
     */
    public static function changeMode($path, $chmodValue){
    	
    }
	
    /**
     * Enter description here...
     *
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
     */
    public function mkdir($path , $mode , $options){
    	
    }

    /**
     * Enter description here...
     *
     * @param string $path_from
     * @param string $path_to
     * @return bool
     */
    public function rename($path_from , $path_to){
    	
    }

    /**
     * Enter description here...
     *
     * @param string $path
     * @param int $options
     * @return bool
     */
    public function rmdir($path , $options){
    	
    }


    /**
     * Enter description here...
     *
     * @return bool
     */
    public function stream_flush(){
    	
    }
	
	
}

?>