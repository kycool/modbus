<?php
/**
 *
 * @license PhpModbus license
 * @category Phpmodbus
 * @package Phpmodbus
 * @version 版本号更新2.0
 *
 */

/**
 * PhpType
 *
 * 一系列把接受的数据转换为PHP类型的数据
 *
 * @package Phpmodbus
 *
 */
class PhpType {

    /**
     * bytes2float
     *
     *
     * @param array $values
     * @param bool $endianness
     * @return float
     */
    public static function bytes2float($values, $endianness = 0) {
        $data = array();
        $real = 0;
        $data = self::checkData($values);
        $real = self::combineBytes($data, $endianness);
        return (float) self::real2float($real);
    }

    /**
     * bytes2signedInt
     *
     * @param array $values
     * @param bool $endianness
     * @return int
     */
    public static function bytes2signedInt($values, $endianness = 0) {
        $data = array();
        $int = 0;
        $data = self::checkData($values);
        $int = self::combineBytes($data, $endianness);
        if ((count($values) == 2) && ((0x8000 & $int) > 0)) {
            $int = 0xFFFF8000 | $int;
        }
        return (int) self::dword2signedInt($int);
    }

    /**
     * bytes2unsignedInt
     *
     * @param array $values
     * @param bool $endianness
     * @return int|float
     */
    public static function bytes2unsignedInt($values, $endianness = 0) {
        $data = array();
        $int = 0;
        $data = self::checkData($values);
        $int = self::combineBytes($data, $endianness);
        return self::dword2unsignedInt($int);
    }

    /**
     * bytes2string
     *
     * @param array $values
     * @param bool $endianness
     * @return string
     */
    public static function bytes2string($values, $endianness = 0) {
        $str = "";
        for($i=0;$i<count($values);$i+=2) {
            if ($endianness) {
                if($values[$i] != 0)
                    $str .= chr($values[$i]);
                else
                    break;
                if($values[$i+1] != 0)
                    $str .= chr($values[$i+1]);
                else
                    break;
            }
            else {
                if($values[$i+1] != 0)
                    $str .= chr($values[$i+1]);
                else
                    break;
                if($values[$i] != 0)
                    $str .= chr($values[$i]);
                else
                    break;
            }
        }
        return $str;
    }

    /**
     * real2float
     *
     * @param value value in IEC REAL 
     * @return float float value
     */
    private static function real2float($value) {
        $ulong = pack("L", $value);
        $float = unpack("f", $ulong);
        return $float[1];
    }

    /**
     * dword2signedInt
     *
     * @param int $value
     * @return int
     */
    private static function dword2signedInt($value) {
        if ((0x80000000 & $value) != 0) {
            return -(0x7FFFFFFF & ~$value)-1;
        } else {
            return (0x7FFFFFFF & $value);
        }
    }

    /**
     * dword2signedInt
     *
     * @param int $value
     * @return int|float
     */
    private static function dword2unsignedInt($value) {
        if ((0x80000000 & $value) != 0) {
            return ((float) (0x7FFFFFFF & $value)) + 2147483648;
        } else {
            return (int) (0x7FFFFFFF & $value);
        }
    }

    /**
     * checkData
     *
     * @param int $data
     * @return int
     */
    private static function checkData($data) {
        if (!is_array($data) ||
                count($data)<2 ||
                count($data)>4 ||
                count($data)==3) {
            throw new Exception('The input data should be an array of 2 or 4 bytes.');
        }
        if (count($data) == 2) {
            $data[2] = 0;
            $data[3] = 0;
        }
        if (!is_numeric($data[0]) ||
                !is_numeric($data[1]) ||
                !is_numeric($data[2]) ||
                !is_numeric($data[3])) {
            throw new Exception('Data are not numeric or the array keys are not indexed by 0,1,2 and 3');
        }

        return $data;
    }

    /**
     * combineBytes
     *
     * @param int $data
     * @param bool $endianness
     * @return int
     */
    private static function combineBytes($data, $endianness) {
        $value = 0;
        // Combine bytes
        if ($endianness == 0)
            $value = (($data[3] & 0xFF)<<16) |
                    (($data[2] & 0xFF)<<24) |
                    (($data[1] & 0xFF)) |
                    (($data[0] & 0xFF)<<8);
        else
            $value = (($data[3] & 0xFF)<<24) |
                    (($data[2] & 0xFF)<<16) |
                    (($data[1] & 0xFF)<<8) |
                    (($data[0] & 0xFF));

        return $value;
    }
}
?>
