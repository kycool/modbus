<?php
/**
 *  
 * @license PhpModbus license 
 * @category Phpmodbus
 * @tutorial Phpmodbus.pkg 
 * @package Phpmodbus 
 * @version  版本号2.0
 *  
 */

require_once dirname(__FILE__) . '/ModbusMaster.php'; 

/**
 * ModbusMasterTcp
 *
 *  这个类使用TCP处理MODBUS，继承ModbusMaster类
 * 
 *  抽象方法列表：
 *   - FC  1: 读线圈
 *   - FC  3: 读取多个寄存器
 *   - FC 15: 写多个线圈
 *   - FC 16: 写多个寄存器
 *   - FC 23: 读写寄存器
 *   
 * @author Jan Krakora
 * @internal kycool update
 * @copyright  Copyright (c) 2004, 2012 Jan Krakora
 * @package Phpmodbus  
 *
 */
class ModbusMasterTcp extends ModbusMaster {
  /**
   * ModbusMasterTcp
   *
   * 构造函数. 
   *     
   * @param String $host Modbus TCP 设备的IP地址. 例如 "192.168.1.1".
   */         
  function ModbusMasterTcp($host){
    $this->host = $host;
    $this->socket_protocol = "TCP";
  }
}
