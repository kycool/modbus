<?php
/**
 * Phpmodbus Copyright (c) 2004, 2010 Jan Krakora, WAGO Kontakttechnik GmbH & Co. KG (http://www.wago.com)
 *
 * This source file is subject to the "PhpModbus license" that is bundled
 * with this package in the file license.txt.
 *
 * @author Jan Krakora
 * @copyright Copyright (c) 2004, 2010 Jan Krakora, WAGO Kontakttechnik GmbH & Co. KG (http://www.wago.com)
 * @license PhpModbus license
 * @category Phpmodbus
 * @package Phpmodbus
 * @ingternal kycool update
 * @version 更新版本 版本号2.0
 */

/**
 * IecType
 *
 * 这个类包含了一系列相应的把PHP数据类型转换为IEC-1131 数据类型
 *
 * @author Jan Krakora  
 * @package Phpmodbus
 * @internal  kycool update
 */
class IecType {

    /**
     * iecBYTE
     *
     * 转换数据为 IEC-1131 BYTE 类型
     *
     * @param value value 范围【0-255】
     * @return value IEC BYTE 类型
     *
     */
    public static function iecBYTE($value) {
        return chr($value & 0xFF);
    }

    /**
     * iecINT
     *
     * 转换数据为 to IEC-1131 INT 数据类型
     *
     * @param value value 
     * @return value IEC-1131 INT 类型
     *
     */
    public static function iecINT($value) {
        return self::iecBYTE(($value >> 8) & 0x00FF) .
                self::iecBYTE(($value & 0x00FF));
    }

    /**
     * iecDINT
     *
     * 转换数据为 IEC-1131 DINT 数据类型
     *
     * @param value value 
     * @param value endianness 
     * @return value IEC-1131 DINT类型
     *
     */
    public static function iecDINT($value, $endianness = 0) {
        return self::endianness($value, $endianness);
    }

    /**
     * iecREAL
     *
     * 把数据转换为 IEC-1131 real 数据类型
     *
     * @param value value 
     * @param value endianness 
     * @return value IEC-1131 real 类型
     */
    public static function iecREAL($value, $endianness = 0) {
        $real = self::float2iecReal($value);
        return self::endianness($real, $endianness);
    }

    /**
     * float2iecReal
     *
     * 浮点型数据转换为 IEC-1131 数据格式
     *
     *
     * @param float value 
     * @return value IEC 
     */
    private static function float2iecReal($value) {
        $float = pack("f", $value);
        $w = unpack("L", $float);
        return $w[1];
    }

    /**
     *endianness
     * 
     * 字节顺序
     *
     * @param int $value
     * @param bool $endianness
     * @return int
     */
    private static function endianness($value, $endianness = 0) {
        if ($endianness == 0)
            return
                    self::iecBYTE(($value >> 8) & 0x000000FF) .
                    self::iecBYTE(($value & 0x000000FF)) .
                    self::iecBYTE(($value >> 24) & 0x000000FF) .
                    self::iecBYTE(($value >> 16) & 0x000000FF);
        else
            return
                    self::iecBYTE(($value >> 24) & 0x000000FF) .
                    self::iecBYTE(($value >> 16) & 0x000000FF) .
                    self::iecBYTE(($value >> 8) & 0x000000FF) .
                    self::iecBYTE(($value & 0x000000FF));
    }

}

?>
