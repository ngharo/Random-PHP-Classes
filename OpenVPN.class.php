<?php

class OpenVPN {
	private $conn;

	// $management_interface -> "foobar:1234"
	public function __construct($management_interface) {
		$mi = explode(':', $management_interface);

		$this->conn = new Telnet($mi[0], $mi[1]);
		$this->conn->clearBuffer();
		$this->conn->setPrompt('END');
	}

	public function getClients() {
		$clients = array();
		$clients_str = $this->conn->exec("status 2");
		foreach(explode("\n", $clients_str) as $line) {
			if(preg_match('/^CLIENT_LIST/', $line)) {
				$clients[] = new OpenVPNClient(explode(",", $line));
			}
		}

		return $clients;
	}

	public function hasClient($cn) {
		foreach($this->getClients() as $client) {
			if($client->getCN() == $cn) return true;
		}
	}

	public function killClient($cn) {
		if($this->hasClient($cn)) {
			$this->conn->exec("kill $cn");
		}
	}
}

//Real Address,Virtual Address,Bytes Received,Bytes Sent,Connected Since,Connected Since (time_t)
class OpenVPNClient {
	private $common_name;
	private $wan_ip;
	private $vpn_ip;
	private $bytes_received;
	private $bytes_sent;
	private $connected_since;
	private $connected_since_t;

	public function __construct(array $client) {
		array_shift($client);
	
		$this->common_name       = $client[0];
		$this->wan_ip            = $client[1];
		$this->vpn_ip            = $client[2];
		$this->bytes_received    = $client[3];
		$this->bytes_sent        = $client[4];
		$this->connected_since   = $client[5];
		$this->connected_since_t = $client[6];
	}

	public function getCN() {
		return $this->common_name;
	}

	public function getWanIP() {
		return $this->wan_ip;
	}

	public function getIP() {
		return $this->vpn_ip;
	}

	public function getRX() {
		return $this->bytes_received;
	}

	public function getTX() {
		return $this->bytes_sent;
	}
	
	public function getConnectTime($timestamp = true) {
		if($timestamp) return $this->connected_since_t;
		return $this->connected_since;
	}
}

?>
