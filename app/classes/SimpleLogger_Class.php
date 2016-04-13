<?php
class SimpleLogger {
	private $_logPath;
	
	public function __construct($logPath) {
		$this->_logPath = $logPath;
	}
	
	public function Log($logText) {
		umask(0022);
		if(file_exists($this->_logPath)) {
			error_log(date('(Ymd H:i:s) ') . $logText . "\n", 3, $this->_logPath);
		}
	}
}
?>
