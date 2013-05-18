<?php
/**
 * @license PhpModbus license 
 * @category Phpmodbus
 * @tutorial Phpmodbus.pkg 
 * @package Phpmodbus 
 * @version $id$
 *  
 */

require_once dirname(__FILE__) . '/ModbusMaster.php'; 

/**
 * ModbusMasterUdp
 *
 * 这个类使用UDP协议处理基于modbus协议的通信
 *  
 * 继承抽象函数列表:
 *   - FC  1: 读线圈
 *   - FC  3: 读多个寄存器
 *   - FC 15: 写多个线圈
 *   - FC 16: 写多个寄存器
 *   - FC 23: 读写寄存器
 *   
 * @author Jan Krakora
 * @internal kycool update
 * @copyright  Copyright (c) 2004, 2012 Jan Krakora
 * @package Phpmodbus  
 * @version 版本号2.0
 */
class ModbusMasterUdp extends ModbusMaster {
  
  /**
   * ModbusMasterUdp
   *
   * 构造函数. 
   *     
   * @param String $host  Modbus UDP 设备的IP地址. 例如g. "192.168.1.1".
   */         
  function ModbusMasterUdp($host){
    $this->host = $host;
    $this->socket_protocol = "UDP";    
  }
}
