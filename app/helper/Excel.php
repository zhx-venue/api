<?php

namespace app\helper;

class Excel
{
    /**
     * 导出数据
     * @param array $data 表数据
     * 单页数据：[
     *  ['name' => '姓名', 'gender' => '性别'],
     *  ['name' => 'shophy', 'gender' => '男'],
     *  ['name' => 'cathy', 'gender' => '女']
     * ]
     * 
     * 多页数据：[
     *  'sheet1' => [
     *      ['name' => '姓名', 'gender' => '性别'],
     *      ['name' => 'shophy', 'gender' => '男'],
     *      ['name' => 'cathy', 'gender' => '女']
     *  ],
     *  'sheet2' => [ ... ]
     * ]
     * @param string $fileName 文件名，空时使用随机文件名
     */
    public static function export($data, $fileName='')
    {
        $sheetIndex = 0;
        $objPHPExcel = new \PHPExcel();

        isset($data[0]) ? ($newData[] = &$data) : ($newData = &$data);
        foreach ($newData as $_key => $_data) {
            if (!is_array($_data))  continue;

            // 表头必须是一位数组
            if (!(is_array($_data[0]) && count($_data[0]) == count($_data[0], 1)))  continue;

            $index = 0;
            $header = [];
            $sheetIndex > 0 && $objPHPExcel->createSheet();
            $currentSheet = $objPHPExcel->getSheet($sheetIndex++);
            is_int($_key) || $currentSheet->setTitle($_key);
            foreach ($_data as $_value) {
                ++$index;
                if (empty($header)) {
                    foreach ($_value as $_k => $_val) {
                        $header[] = $_k;
                        $columnIndex = \PHPExcel_Cell::stringFromColumnIndex(count($header)-1);
                        // $currentSheet->getColumnDimension($columnIndex)->setAutoSize(true);
                        $currentSheet->getStyle($columnIndex)->getAlignment()->setWrapText(true);
                        $currentSheet->setCellValueExplicit($columnIndex.$index, strval($_val), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                } else {
                    foreach ($header as $_k => $_val) {
                        $columnIndex = \PHPExcel_Cell::stringFromColumnIndex($_k);
                        // $currentSheet->getColumnDimension($columnIndex)->setAutoSize(true);
                        $currentSheet->getStyle($columnIndex)->getAlignment()->setWrapText(true);
                        $currentSheet->setCellValueExplicit($columnIndex.$index, isset($_value[$_val]) ? strval($_value[$_val]) : '', \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                }
            }
        }

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . urlencode(empty($fileName) ? (uniqid().'.xlsx') : $fileName));
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET');
        header('Access-Control-Request-Headers: *');
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Expose-Headers:Content-Disposition');
        
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save('php://output');
        exit;
    }
}
