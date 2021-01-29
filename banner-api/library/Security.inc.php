<?php

class Security {
					
	public function sanitizeForJsonOption($req){
		// Entferne boesartigen MYSQL Code
		$sql_code = array ( 
			'"',
			',',
			';'
		  );
		$replace = array('','','');  
		$content = str_ireplace($sql_code,$replace,$req);
		return $content;
	} // public function sanitizeRequest($req){
	
	public function sanitizeRequestSimple($req){
		// Entferne boesartigen MYSQL Code
		$sql_code = array ( 
			'SELECT', 
			'UPDATE', 
			'DELETE', 
			'INSERT', 
			'VALUES', 
			'FROM', 
			'LEFT', 
			'JOIN', 
			'WHERE', 
			'LIMIT', 
			'ORDER BY', 
			'DESC'
		  );
		$replace = array('','','','','','','','','','','','');  
		$content = str_ireplace($sql_code,$replace,$req);
		//$content = preg_replace('/[[:^print:]]/', "", $content);
		//$content = preg_replace('/[^\p{L}\s]/u','',$content);
		$content = preg_replace('/[^A-Za-z0-9 öäüÜÄÖß_\-\+\&\,\.]/','',$content);
		return filter_var($content, FILTER_SANITIZE_STRING);
	} // public function sanitizeRequest($req){
		
	public function sanitizeRequest($req){
		
		// Entferne boesartigen MYSQL Code
		$sql_code = array ( 
			'SELECT', 
			'UPDATE', 
			'DELETE', 
			'INSERT', 
			'VALUES', 
			'FROM', 
			'LEFT', 
			'JOIN', 
			'WHERE', 
			'LIMIT', 
			'ORDER BY', 
			'DESC'
		  );
		  
		$content = str_ireplace($sql_code,'',$req);
		return filter_var($content, FILTER_SANITIZE_STRING);
	} // public function sanitizeRequest($req){
}
?>