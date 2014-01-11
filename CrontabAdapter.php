<?php
/**
 * Can retrieve and write crontab data using the "crontab" command line.
 * @author francepronet
 *
 */
class CrontabAdapter {
	
	private $userName;
	
	private $useSudo;
	
	/**
	 * Instantiate an crontabAdapter
	 * @param String $userName Optional Specifie the crontab user where the adapter will try to read (by default user = runtime user)
	 * @param Boolean $useSudo Tell Adapter use sudo command to connect to crontab of another user than the runtime user.
	 * 	
	 * About sudo :
	 * If you want to work with the crontab of another user of the runtime user, you can allowing the runtime user to edit 
	 * another user crontab by adding this kind of line in your sudoers using 'visudo'
	 * For example : user www-data want to edit waylon crontab:
	 * 
	 * www-data        ALL=(waylon) NOPASSWD: /usr/bin/crontab 
	 * 
	 * Will tell sudo user www-data can execute /usr/bin/crontab as user waylon without typing any password (required in our case) 
	 */
	public function __construct($userName = null, $useSudo = false) {
		if($userName) $this->userName = $userName;
		$this->useSudo = $useSudo;
	}
	
	/**
	 * Read the crontab and return 
	 * raw data
	 * @return String $output the crontab raw data 
	 */	
	public function readCrontab() {
			
		$crontabCommandLine = (isset($this->userName) && $this->useSudo) 
			? sprintf('sudo -n -u %s crontab -l', $this->userName)
			: ($this->userName ? sprintf('crontab -u %s -l', $this->userName) : 'crontab -l') 
		;
			
		exec($crontabCommandLine . ' 2>&1', $output, $exitCode);
				
		/* exec error handling */
		if($exitCode !== 0) {
			
			/* Special case : the crontab is empty throw bad exit code but access is ok */
			if(!preg_match('/^no crontab for .+$/', $output[0])) {
				throw new DomainException('Error when trying to read crontab : ' . implode(' ', $output));
			}
			else {
				$output = '';
			}				
		}
		else {
			$output = implode("\n", $output);
		}
		
		return $output;
		
	}
	
	/**
	 * Write the raw crontab data to the crontab.
	 * @param String $crontabRawData
	 */
	public function writeCrontab($crontabRawData) {
		
		$crontabRawData = escapeshellarg($crontabRawData);
		
		$crontabCommandLine = (isset($this->userName) && $this->useSudo)
			? sprintf('echo %s | sudo -n -u %s crontab -',  $crontabRawData, $this->userName)
			: ($this->userName 
				? sprintf('echo %s | crontab -u %s -', $crontabRawData, $this->userName) 
				: sprintf('echo %s | crontab -', $crontabRawData)
			)
		;
		
		exec($crontabCommandLine . ' 2>&1', $output, $exitCode);		
		
		/* exec error handling */
		if($exitCode !== 0) {
				
			/* Special case : the crontab is empty throw bad exit code but access is ok */
			if(!preg_match('/^no crontab for .+$/', $output[0])) {
				throw new DomainException('Error when trying to write crontab : ' . implode(' ', $output));
			}
			else {
				$output = '';
			}
		}		
		
	}
	
}