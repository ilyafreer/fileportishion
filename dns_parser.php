<?php


set_time_limit (1000);
function exceptions_error_handler($severity, $message, $filename, $lineno) { 
    throw new ErrorException($message, 0, $severity, $filename, $lineno); 
}
set_error_handler('exceptions_error_handler');


class checkDomain{
	
	private $db;
	public $domains;
	public $hostDomains = [];
	
	public function __construct($bd = false){
		if($bd){
			$this->bdConnect();	
		}
	}
	
	private function bdConnect(){
		$dsn = 'mysql:dbname=fuflo;host=127.0.0.1';
		$user = 'root';
		$password = '';

		try {
			$this->db = new PDO($dsn, $user, $password);
		} catch (PDOException $e) {
			echo 'Подключение не удалось: ' . $e->getMessage();die;
		}
	}
	
	public function getDomainsFromFile($filename){
		$handle = @fopen($filename, "r");
		if ($handle) {
			while (($buffer = fgets($handle, 4096)) !== false) {
				$this->domains[] = trim($buffer);
			}
			// if (!feof($handle)) {
				// echo "Error: unexpected fgets() fail\n";
			// }
			fclose($handle);
		}
	}
	
	private function writeDomains($text,$filename){
		$fp = fopen($filename.'.csv', 'a+');
		fwrite($fp,$text."\n");			
		fclose($fp);
	}
	
	public function debug($data){
		echo '<pre>';var_dump($data);echo '</pre>';
	}
	
	
	public function getDomainsFromBD(){
		if(!$this->db){
			die('no connect');
		}
		$sql = 'SELECT domain FROM b__domains ORDER BY id';
		foreach ($this->db->query($sql) as $row) {
			if(strpos($row['domain'], '_')){
				continue;
			}
			if(!strpos($row['domain'], '.ru') && !strpos($row['domain'], '.рф')){
				continue;
			}
			// если каша, то разгребаем и берем первый
			if($pos = strpos($row['domain'], ';')){
				$row['domain'] = substr($row['domain'],0,$pos);
			}
			$this->domains[] = $row['domain'];
		}
	}
	public function createHostNewServer(){
		foreach($this->hostDomains as $domain){
			$line = '212.109.216.129 '.$domain.' www.'.$domain;
			$this->writeDomains($line,'host');
		}
	}
	public function getServerStatus($newDomains = false){
		foreach($this->domains as $domain){
			try {
				$dns = dns_get_record($domain);
			} catch (ErrorException $ex) {
				$this->writeDomains($domain.';DNS error','statusServers');
				continue;
			}
			if(!empty($dns)){
				$line['target'] = '';
				$line['mname'] = '';
				foreach($dns as $item){
						if(isset($item['target']) && !empty($item['target'])){
							$line['target'] = $item['target'];
						}
						if(isset($item['ip']) && !empty($item['ip'])){
							$line['ip'] = $item['ip'];
						}
						if(isset($item['mname']) && !empty($item['mname'])){
							$line['mname'] = $item['mname'];
						}			
				}
				try {
					$status = get_headers('http://'.$domain)[0];
				} catch (ErrorException $ex) {
					$this->writeDomains($domain.';get_headers error','statusServers');
					continue;
				}
				if($line['ip']=='92.63.105.233' || $line['ip']=='212.109.216.129'){
					if($newDomains){
						$this->hostDomains[] = $domain;
					}else{
						$row = $domain.';'.$line['ip'].';'.$line['target'].';'.$status;
						$this->writeDomains($row,'statusServersCorei7');
					}
				}
			}
		}
	}
	
}

$checkDomain = new checkDomain();
$checkDomain->getDomainsFromFile('domains.csv');
// $checkDomain->getDomainsFromBD();
$checkDomain->getServerStatus();
// $checkDomain->createHostNewServer();
