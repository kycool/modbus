<?php

require_once dirname(__FILE__) . '/IecType.php';
require_once dirname(__FILE__) . '/PhpType.php'; 


class ModbusMaster {
  private $sock;
  public $host = "192.168.1.1";
  public $port = "502";  
  public $client = "";
  public $client_port = "502";
  public $status;
  public $timeout_sec = 5; // 5秒超时
  public $endianness = 0; 
  public $socket_protocol = "TCP"; // Socket 协议 (TCP, UDP)
  
       
  function ModbusMaster($host, $protocol){
    $this->socket_protocol = $protocol;
    $this->host = $host;
  }
	

  function  __toString() {
      return "<pre>" . $this->status . "</pre>";
  }


  private function connect(){
    if ($this->socket_protocol == "TCP"){ 
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);      
    } elseif ($this->socket_protocol == "UDP"){
        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    } else {
        throw new Exception("Unknown socket protocol, should be 'TCP' or 'UDP'");
    }
    if (strlen($this->client)>0){
        $result = socket_bind($this->sock, $this->client, $this->client_port);
        if ($result === false) {
            throw new Exception("socket_bind() failed.</br>Reason: ($result)".
                socket_strerror(socket_last_error($this->sock)));
        } else {
            $this->status .= "Bound\n";
        }
    }
    // 连接socket
    $result = @socket_connect($this->sock, $this->host, $this->port);
    if ($result === false) {
        throw new Exception("socket_connect() failed.</br>Reason: ($result)".
            socket_strerror(socket_last_error($this->sock)));
    } else {
        return true;        
    }    
  }

  private function disconnect(){    
    socket_close($this->sock);
  }

  private function send($packet){
    socket_write($this->sock, $packet, strlen($packet));  
  }

  private function rec(){
    socket_set_nonblock($this->sock);
    $readsocks[] = $this->sock;     
    $writesocks = NULL;
    $exceptsocks = NULL;
    $rec = "";
    $lastAccess = time();
    while (socket_select($readsocks, 
            $writesocks, 
            $exceptsocks,
            0, 300000) !== FALSE) {
              if (in_array($this->sock, $readsocks)) {
                  while (@socket_recv($this->sock, $rec, 2000, 0)) {
                      return $rec;
                  }
                  $lastAccess = time();
              } else {             
                  if (time()-$lastAccess >= $this->timeout_sec) {
                      throw new Exception( "Watchdog time expired [ " .
                        $this->timeout_sec . " sec]!!! Connection to " . 
                        $this->host . " is not established.");
                  }
              }
              $readsocks[] = $this->sock;
    }
  } 
  
  private function responseCode($packet){    
    if((ord($packet[7]) & 0x80) > 0) {
      $failure_code = ord($packet[8]);
      $failures = array(
        0x01 => "ILLEGAL FUNCTION",
        0x02 => "ILLEGAL DATA ADDRESS",
        0x03 => "ILLEGAL DATA VALUE",
        0x04 => "SLAVE DEVICE FAILURE",
        0x05 => "ACKNOWLEDGE",
        0x06 => "SLAVE DEVICE BUSY",
        0x08 => "MEMORY PARITY ERROR",
        0x0A => "GATEWAY PATH UNAVAILABLE",
        0x0B => "GATEWAY TARGET DEVICE FAILED TO RESPOND");
      if(key_exists($failure_code, $failures)) {
        $failure_str = $failures[$failure_code];
      } else {
        $failure_str = "UNDEFINED FAILURE CODE";
      }
      throw new Exception("Modbus response error code: $failure_code ($failure_str)");
    } else {
      return true;
    }    
  }
  

  function readMultipleRegisters($unitId, $reference, $quantity){
    $this->connect();   
    $packet = $this->readMultipleRegistersPacketBuilder($unitId, $reference, $quantity);
    $this->send($packet);
    $rpacket = $this->rec();  
    $receivedData = $this->readMultipleRegistersParser($rpacket);
    return $receivedData;
  }

  /**
  *断开套接字连接
  *@return  void
  */
  function  close_read(){
	  $this->disconnect();
	  $this->status .= "readMultipleRegisters: DONE\n";
  }

  /**
   * fc3
   *
   *
   * @param int $unitId
   * @param int $reference
   * @param int $quantity
   * @return false|Array
   */
  function fc3($unitId, $reference, $quantity){
    return $this->readMultipleRegisters($unitId, $reference, $quantity);
  }  
 
  /**
   * readMultipleRegistersPacketBuilder
   *
   *
   * @param int $unitId
   * @param int $reference
   * @param int $quantity
   * @return string
   */
  private function readMultipleRegistersPacketBuilder($unitId, $reference, $quantity){
    $dataLen = 0;
    $buffer1 = "";
    // 创建 body
    $buffer2 = "";
    $buffer2 .= iecType::iecBYTE(3);             // FC 3 = 3(0x03)
    // 创建 body - 读区域    
    $buffer2 .= iecType::iecINT($reference);  // refnumber = 12288      
    $buffer2 .= iecType::iecINT($quantity);       // quantity
    $dataLen += 5;
    // 创建 header
    $buffer3 = '';
	$buffer3 .= iecType::iecINT(0);   // transaction ID
    $buffer3 .= iecType::iecINT(0);               // protocol ID
    $buffer3 .= iecType::iecINT($dataLen + 1);    // lenght
    $buffer3 .= iecType::iecBYTE($unitId);        //unit ID
    // 返回包的 string
    return $buffer3. $buffer2. $buffer1;
  }
  
  /**
   * readMultipleRegistersParser
   *
   * FC 3 回应分析器
   *
   * @param string $packet
   * @return array
   */
  private function readMultipleRegistersParser($packet){   
    $data = array();
    // 验证 Response code
    $this->responseCode($packet);
    // 取得
    for($i=0;$i<ord($packet[8]);$i++){
      $data[$i] = ord($packet[9+$i]);
    }    
    return $data;
  }
  
  /**
   * byte2hex
   *
   * 解析数据并得到16进制的格式
   *
   * @param char $value
   * @return string
   */
  private function byte2hex($value){
    $h = dechex(($value >> 4) & 0x0F);
    $l = dechex($value & 0x0F);
    return "$h$l";
  }

  /**
   * printPacket
   *
   * 用16进制打印包
   *
   * @param string $packet
   * @return string
   */
  private function printPacket($packet){
    $str = "";   
    $str .= "Packet: "; 
    for($i=0;$i<strlen($packet);$i++){
      $str .= $this->byte2hex(ord($packet[$i]));
    }
    $str .= "\n";
    return $str;
  }
}
