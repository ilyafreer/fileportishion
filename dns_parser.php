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
	public $diffDomains = [];
	
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
		$this->domains = $this->fileread($filename);
	}
	
	private function fileread($filename){
		$filerows = [];
		$handle = @fopen($filename, "r");
		if ($handle) {
			while (($buffer = fgets($handle, 4096)) !== false) {
				$filerows[] = trim($buffer);
			}
			// if (!feof($handle)) {
				// echo "Error: unexpected fgets() fail\n";
			// }
			fclose($handle);
		}
		return $filerows;
	}
	
	private function writeDomains($text,$filename){
		$fp = fopen($filename, 'a+');
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
			$this->writeDomains($line,'host.csv');
		}
	}
	public function getServerStatus($newDomains = false){
		foreach($this->domains as $domain){
			try {
				$dns = dns_get_record($domain);
			} catch (ErrorException $ex) {
				$this->writeDomains($domain.';DNS error','statusServers.csv');
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
					$this->writeDomains($domain.';get_headers error','statusServers.csv');
					continue;
				}
				if($line['ip']=='92.63.105.233' || $line['ip']=='212.109.216.129'){
					if($newDomains){
						$this->hostDomains[] = $domain;
					}else{
						$row = $domain.';'.$line['ip'].';'.$line['target'].';'.$status;
						$this->writeDomains($row,'statusServers.csv');
					}
				}
			}
		}
	}
	// сравнивает содержимое двух файлов, и создает массив с доменами которых нет во втором файле
	public function diffDomains($filename1,$filename2){
		$domains1 = $this->fileread($filename1);
		$domains2 = $this->fileread($filename2);
		$this->diffDomains = array_diff($domains1,$domains2);
	}
	
	public function createZoneFiles(){
		foreach($this->diffDomains as $domain){
			$this->createDomainZone($domain);
		}
	}
	
	private function createDomainZone($domain){
		$data = '$TTL 3600'."\n".
				$domain.'.	IN	SOA	les-art-resort.ru. root.example.com. (2017031600 10800 3600 604800 86400)'."\n".
				$domain.'.	IN	NS	ns1.madmdns.ru.'."\n".
				$domain.'.	IN	NS	ns2.madmdns.ru.'."\n".
				$domain.'.	IN	TXT	"v=spf1 ip4:212.109.216.129 a mx ~all"'."\n".
				$domain.'.	IN	MX	10 mail'."\n".
				$domain.'.	IN	MX	20 mail'."\n".
				$domain.'.	IN	A	212.109.216.129'."
www	IN	A	212.109.216.129
ftp	IN	A	212.109.216.129
mail	IN	A	212.109.216.129
smtp	IN	A	212.109.216.129
pop	IN	A	212.109.216.129";
		
		// создаем записи для домена
		$this->writeDomains($data,'zona/'.trim($domain));
	}
}

$checkDomain = new checkDomain();
// $checkDomain->getDomainsFromFile('domains.csv');
// $checkDomain->getDomainsFromBD();
// $checkDomain->getServerStatus();
// $checkDomain->createHostNewServer();
$checkDomain->diffDomains('aqwareli.csv','corei7.txt');
$checkDomain->createZoneFiles();
// $checkDomain->debug($checkDomain->diffDomains);
