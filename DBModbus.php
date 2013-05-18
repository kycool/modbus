<?php
	set_time_limit(0);
	/**
	*获取累计发电量
	*
	*通过判断时间，如果满一年则之前的累计值加上当前年份总发电量并重置累计发电量
	*
	*@param  int  年发电量【总发电量】
	*@return  int   $result  累计发电量
	*/
	function  get_telec($yearelec){
		$time = localtime();
		$result = array();
		$ack = file_get_contents("time.txt");//读文件
		$pre_elec =  file_get_contents('totalelec.txt');
		if($time[4] == 0){//判断是否是1月
			if($time[3] == 1){//判断是否是1号
				if($ack == 1){//如果是一月一号对累加发电量进行重新计算 并把标志设为0
					//echo "累计数据计算第一次";
					$pre_elec = $yearelec;
					$file1 = fopen('totalelec.txt','w+');
					fwrite($file1, $pre_elec);
					fclose($file1);

					$file2 = fopen('time.txt','w+');
					fwrite($file2, "0");
					fclose($file2);
				}
				//判断是否是1月2号0点  时间浮动范围设5分钟  把标志设为1
			}else if(($time[3] == 2) && ($time[2] == 0)  && ($time[1] <6)){
					$file3 = fopen('time.txt','w+');
					fwrite($file3, "1");
					fcolse($file3);
			}
		}
		$result['allelec'] = $pre_elec;
		$result['forset'] = get_forset($result['allelec']);
		$result['oil']= get_oil($result['allelec']);
		$result['coo'] = get_co2($result['allelec']);
		return  $result;
	}

	/**
	*获取二氧化碳减排量
	*@param  int  $tleec 累计发电量
	*@return  int  $co2 二氧化碳减排量
	*/
	function  get_co2($telec){
		$co2 = $telec * 1.008;
		return round($co2, 1);
	}

	/**
	*获取森林砍伐面积
	*@param  int  $telec   累计发电量
	*@return  int  $forset   森林砍伐面积
	*/
	function  get_forset($telec){
		$forset = $telec * 1.94;
		return round($forset,1);
	}

	/**
	*获取石油减少量
	*@param  int  $telec  累计发电量
	*@return  int  $oiltotal 石油减少量
	*/
	function  get_oil($telec){
		$oiltotal = $telec * 2250 / 9250;
		return  round($oiltotal,1);
	}
	
	/**
	 *此方法把通过modbus协议获取的数组转换成十进制
	 *
	 *@api
	 *@param  mixed[]  $array  array 从modbus主机获取的数据
	 *@return  int  result  转换后的数据
	 */
	function  kycool_bridge($array){
		$bridge = array();
		$back = array();
		$tag = 0;
		$hex = "0x";

		for($i = 0, $len = count($array);$i < $len; $i++){
			if($i % 2 == 1){
				if($array[$i] <16){
					$bridge[$i] = $tag.dechex($array[$i]);
				}else{
					$bridge[$i] = dechex($array[$i]);
				}
			}else{
				$bridge[$i] = dechex($array[$i]);
			}
		}
		
		$back[] = hexdec($hex.$bridge[0].$bridge[1]);
		$back[] = hexdec($hex.$bridge[2].$bridge[3]);

		if($back[0] == 0){
			return $back[1];
		}else{
			return $back[0].$back[1];
		}
	}

	/**
	*把十进制数据转换为16进制数据，如果转换后十六进制数据不足两位则前面补0
	*@param  $data  十进制数据
	*@return  String  $midd  两位的16进制字符串
	*/
	function  hex_Data($data){
		$tag = 0;
		$midd = dechex($data);
		if(strlen($midd) == 1){
			return $tag.$midd;
		}else{
			return $midd;
		}
	}

	/**
	*对16进制的字符转换为二进制数据，不足四位的左边补0
	*@param  $data  16进制字符
	*@return  String  返回四位二进制数据
	*/
	function  get_Bindata($data){
		$result = decbin(hexdec($data));
		switch(strlen($result)){
			case 1:	return "000".$result;
						break;
			case 2:
						return "00".$result;
						break;
			case 3:
						return "0".$result;
						break;
			case 4:
						return $result;
						break;
			default:
						break;
		}
	}

	/**
	* 通过phpmodbus返回的数组，把数组转换为浮点型的数据
	*@param  {Array}  $data  modbus主机返回的数组
	*@return  float   result  浮点型数据
	*/
	function  db_Modbus_Floatdata($data){
		$bin_data = '';
		$tag   =  0;		//符号位
		$drift = 127;    //偏移量
		$index_data = '';//指数数据
		$index_value = 0;//指数十进制数据
		$miss_data = '';//尾数数据
		$miss_value = 0;//M值

		$mk = 0;
		/**
		*高位
		*/
		$big_one = hex_Data($data[0]);
		$big_second = hex_Data($data[1]);
		/**
		*低位
		*/
		$small_one = hex_Data($data[2]);
		$small_second = hex_Data($data[3]);

		$hex_string = $big_one.$big_second.$small_one.$small_second;

		$hex_array = str_split($hex_string);
		
		for($i = 0, $len = count($hex_array); $i < $len; $i++){
			$bin_data .= get_Bindata($hex_array[$i]);
		}
		
		//这个数组是把二进制数据字符串转换为数组
		$global_array = str_split($bin_data);

		//符号位
		$tag = $global_array[0];

		//指数位数组
		$index_data = array_slice($global_array, 1, 8);

		//尾数位数组
		$miss_data = array_slice($global_array, 9, 23);

		//得到十进制指数数值
		$index_value = bindec(implode($index_data));
		
		//得到M指
		for($j = 0, $miss_len = count($miss_data); $j < $miss_len; $j ++){
			$mk = $j + 1;
			$miss_value += $miss_data[$j] * pow(2, -$mk);
		}

		$sum = pow(-1, $tag) * (1 + $miss_value) * pow(2, $index_value-127);	
		return round($sum,2);
	}
	
	require_once dirname(__FILE__) . '/Phpmodbus/ModbusMaster.php';


	/**
	 *此函数通过modbus协议获取寄存器中的数据
	 *
	 *@return  void
	 */
	function  kycool_data(){
			$total_elec = 0;
			$forset_cut = 0;
			$oil_save = 0;
			$temp = 0;
			$modbus = new ModbusMaster("192.168.1.1", "TCP");
			try {
				//这里是FC3块读取
				//readMultipleRegisters  函数  parm1：设备地址  param2：ID地址   param3：查询的偏移量

				$day_elec = $modbus->readMultipleRegisters(1, 1052, 2);//1052
				$year_elec = $modbus->readMultipleRegisters(1, 1054, 2);//1054
				//$co2_value = $modbus->readMultipleRegisters(1, 1058, 2);//1058
				$glva = $modbus->readMultipleRegisters(1, 1026, 2);//1026
				$glvb = $modbus->readMultipleRegisters(1, 1028, 2);//1028
				$glvc = $modbus->readMultipleRegisters(1, 1030, 2);//1030

				$temperature = $modbus->readMultipleRegisters(206, 1000, 2);//1000
				$humidity = $modbus->readMultipleRegisters(206, 1002, 2);//1002

				$sun_total = $modbus->readMultipleRegisters(203, 1000, 2);//1000

				//断开本次连接
				$modbus->close_read();
			}
			catch (Exception $e) {
				echo $modbus;
				echo $e;
				exit;
			}
			
			//把数据转换为浮点型
			$fday_elec = db_Modbus_Floatdata($day_elec);
			$fyear_elec = db_Modbus_Floatdata($year_elec);
			//$fco2_value = db_Modbus_Floatdata($co2_value);

			$fglva = db_Modbus_Floatdata($glva);
			$fglvb = db_Modbus_Floatdata($glvb);
			$fglvc = db_Modbus_Floatdata($glvc);

			$fglv = $fglva + $fglvb + $fglvc;

			$ftemperature= db_Modbus_Floatdata($temperature);
			$fhumidity = db_Modbus_Floatdata($humidity);

			$fsun_total = db_Modbus_Floatdata($sun_total);

			//把浮点型数据用json_encode函数进行转换

			$fday_elec_json = json_encode($fday_elec);   //每日发电量
			$fyear_elec_json = json_encode($fyear_elec);//年发电量
			//$fco2_value_json = json_encode($fco2_value);//二氧化碳减少量
			$fglv_json = json_encode($fglv);//输出功率


			$ftemperature_json = json_encode($ftemperature);//温度
			$fhumidity_json = json_encode($fhumidity);//湿度

			$fsun_total_json = json_encode($fsun_total);//光照量
			
			$temp = get_telec($fyear_elec_json);
			$total_elec_json = json_encode($temp['allelec']);//累计发电量
			$forset_cut_json = json_encode($temp['forset']);//森林减少开发面积
			$oil_save_json = json_encode($temp['oil']);//石油削减量
			$fco2_value_json = json_encode($temp['coo']);//二氧化碳减少量


			echo 'var modbusdata = {day_elec:'.$fday_elec_json.',year_elec:'.$fyear_elec_json.',co2:'.$fco2_value_json.',fglv:'.$fglv_json.',temperature:'.$ftemperature_json.',humidity:'.$fhumidity_json.',sun_total:'.$fsun_total_json.',total_elec:'.$total_elec_json.',forset_cut:'.$forset_cut_json.',oil_save:'.$oil_save_json.'};db_write_modbus(modbusdata);';
	}
	 kycool_data();
?>
