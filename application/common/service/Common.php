<?php
/**
 * Created by PhpStorm.
 * User: gaozh
 * Date: 2018/10/30
 * Time: 14:34
 */

namespace app\common\service;


class Common
{
    //读取excel表数据
    public function importExcel($file = '', $sheet = 0)
    {
        $file = iconv("utf-8", "gb2312", $file);   //转码
        if (empty($file) OR !file_exists($file)) {
            die('file not exists!');
        }
        $excel_list = array('Excel2007', 'Excel5', 'Excel2003XML');
        $excel_list_count = count($excel_list);
        for ($i = 0; $i < $excel_list_count; $i++) {
            $objRead = \PHPExcel_IOFactory::createReader($excel_list[$i]);  //建立reader对象
            if ($objRead->canRead($file)) {
                break;
            } else if ($i == $excel_list_count - 1 && !$objRead->canRead($file)) {
                die(json_encode(['data' => false, 'message' => '文件格式可能出错,请检查或者转换格式吧', 'code' => 500]));
            }
        }
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $obj = $objRead->load($file);                       //建立excel对象
        $currSheet = $obj->getSheet($sheet);                //获取指定的sheet表
        $columnH = $currSheet->getHighestColumn();          //取得最大的列号
        $columnCnt = array_search($columnH, $cellName);
        $rowCnt = $currSheet->getHighestRow();              //获取总行数
        $data = array();
        for ($_row = 1; $_row <= $rowCnt; $_row++) {              //读取内容
            for ($_column = 0; $_column <= $columnCnt; $_column++) {
                $cellId = $cellName[$_column] . $_row;
                $cellValue = $currSheet->getCell($cellId)->getValue();
                if ($cellValue instanceof PHPExcel_RichText) {                    //富文本转换字符串
                    $cellValue = $cellValue->__toString();
                }
                $data[$_row][$cellName[$_column]] = $cellValue;
            }
        }
        return $data;
    }
}