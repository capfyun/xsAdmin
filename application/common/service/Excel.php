<?php
/**
 * 服务层-Excel
 * @author 夏爽
 */
namespace app\common\service;

class Excel extends Base{
	//PHPExcel实例化对象
	protected $excel;
	//默认配置
	protected $config = [
		'author' => 'qiguo', //作者
	];
	
	/**
	 * 初始化
	 */
	public function __construct(){
		parent::__construct();
		//导入phpExcel核心类
		import('phpexcel.PHPExcel');
	}
	
	/**
	 * 配置
	 * @param array $config
	 * @return $this
	 */
	public function setConfig($config = []){
		$this->config = array_merge($this->config, $config);
		return $this;
	}
	
	/**
	 * 导出excel
	 * @param array $data 导出的数据
	 * @param string $name 文件名
	 * @param array $table 表头显示名称，字段对应的名称如：['username'=>'姓名','password'=>'密码']
	 * @param string $type 导出格式：excel5、excel2007、csv、html
	 */
	public function export($data = [], $name = 'excel', $table = [], $type = 'Excel5'){
		//校验数据
		$first = reset($data);
		if(!is_array($data) || !$first || !is_array($first)){
			$this->error = '没有数据';
			return false;
		}
		
		$excel = new \PHPExcel();
		/* 文件属性 */
		$excel->getProperties()
			->setCreator($this->config['author'])//作者
			->setLastModifiedBy($this->config['author'])//最后一次保存者
			->setTitle('Office 2007 XLSX Test Document')//标题
			->setSubject('Office 2007 XLSX Test Document')//主题
			->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')//备注
			->setKeywords('excel')//关键字
			->setCategory('result file'); //类别
		
		/* sheet配置 */
		$excel->setActiveSheetIndex(0); //设置当前的sheet
		$excel->getActiveSheet()->setTitle($name); //设置sheet的名称
		
		/* 表头 */
		//表头样式
		$excel->getActiveSheet()->getStyle('1')->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_BLUE); //字体蓝色
		//表头内容
		$i = 0;
		foreach($first as $k => $v){
			$excel->getActiveSheet()
				->setCellValue(
					$this->getRowName($i).'1', //位置
					isset($table[$k]) ? $table[$k] : $k, //值
					\PHPExcel_Cell_DataType::TYPE_STRING //格式类型
				);
			$i++;
		}
		
		/* 表内容 */
		foreach($data as $k => $v){
			$row = $k+2; //行号
			$i   = 0;
			foreach($v as $k1 => $v1){
				$excel->getActiveSheet()
					->setCellValueExplicit(
						$this->getRowName($i).$row, //位置
						$v1, //值
						\PHPExcel_Cell_DataType::TYPE_STRING //格式类型
					);
				$i++;
			}
		}
		
		/* 导出 */
		//擦除缓冲区
		ob_end_clean();
		//文件名带日期
		$filename = $name.'-'.date('Ymd-Hi', time());
		//格式
		$type = strtolower($type);
		switch($type){
			case 'html':
				header('Content-Type: text/html');
				$filename .= '.html';
				break;
			case 'csv':
				header('Content-Type: application/vnd.ms-excel');
				$filename .= '.csv';
				break;
			case 'excel2007':
				header('Content-Type: application/vnd.ms-excel');
				$filename .= '.xlsx';
				break;
			case 'excel5':
			default:
				header('Content-Type: application/vnd.ms-excel');
				$type = 'excel5';
				$filename .= '.xls';
		}
		
		header('Content-Disposition: attachment;filename="'.$filename.'"');
		header('Cache-Control: max-age=0');
		$writer = \PHPExcel_IOFactory::createWriter($excel, $type); //保存的格式
		$writer->save('php://output'); //这里生成excel后会弹出下载
		exit;
	}
	
	/**
	 * table方式导出
	 * @param array $data 导出的数据
	 * @param string $name 文件名
	 * @param array $table 表头显示名称，字段对应的名称如：['username'=>'姓名','password'=>'密码']
	 */
	public function table($data = [], $name = 'excel', $table = []){
		//校验数据
		$first = reset($data);
		if(!is_array($data) || !$first || !is_array($first)){
			$this->error = '没有数据';
			return false;
		}
		
		/* 表头 */
		$content = '<table width="500" border="1">';
		$content .= '<tr>';
		foreach($first as $k => $v){
			$content .= '<td style="text-align:left;font-size:12px;" width="*">'.(isset($table[$k]) ? $table[$k] : $k).'</td>';
		}
		$content .= '</tr>';
		
		/* 表内容 */
		foreach($data as $k => $v){
			$content .= '<tr>';
			foreach($v as $k1 => $v1){
				$content .= '<td style="text-align:left;font-size:12px;">'.$v1.' </td>';
			}
			$content .= '</tr>';
		}
		$content .= '</table>';
		
		/* 导出 */
		//擦除缓冲区
		ob_end_clean();
		//文件名
		$filename = $name.'-'.date('Ymd-Hi', time()).'.xls';
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Type: application/force-download');
		header('Content-Disposition: attachment; filename='.$filename);
		header('Expires:0');
		header('Pragma:public');
		echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$content.'</html>';
		exit();
	}
	
	/**
	 * csv方式导出
	 * @param array $data 导出的数据
	 * @param string $name 文件名
	 * @param array $table 表头显示名称，字段对应的名称如：['username'=>'姓名','password'=>'密码']
	 */
	public function csv($data = [], $name = 'excel', $table = []){
		//校验数据
		$first = reset($data);
		if(!is_array($data) || !$first || !is_array($first)){
			$this->error = '没有数据';
			return false;
		}
		
		/* 表头 */
		$header = [];
		foreach($first as $k => $v){
			$header[] = isset($table[$k]) ? $table[$k] : $k;
		}
		
		//擦除缓冲区
		ob_clean();
		//文件句柄
		$fp = fopen('php://output', 'a');
		fputcsv($fp, $header);
		
		/* 表内容 */
		foreach($data as $k => $v){
			fputcsv($fp, $v);
		}
		
		/* 导出 */
		//文件名
		$filename = $name.'-'.date('Ymd-Hi', time()).'.csv';
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Type: application/force-download');
		header('Content-Disposition:filename='.$filename);
		//输出缓冲区内容
		ob_flush();
		exit ();
	}
	
	/**
	 * 导入excel
	 * @param string $path excel文件路径
	 * @return array|bool 成功则返回数据
	 */
	public function import($path = ''){
		//校验文件类型
		if(!(new \PHPExcel_Reader_Excel2007())->canRead($path) && !(new \PHPExcel_Reader_Excel5())->canRead($path))
			return false;
		/* 导入文件 */
		$excel  = \PHPExcel_IOFactory::load($path);
		$sheet  = $excel->getSheet(0); //设置当前的sheet
		$row    = $sheet->getHighestRow(); //总行数
		$column = $sheet->getHighestColumn(); //总列数
		$column = $this->getRowNumber($column); //转为数值
		/* 获取数据 */
		$header = []; //表头键
		$data   = []; //数据
		for($i = 1; $i<=$row; $i++){
			for($ii=0; $ii<$column; $ii++){
				if($i==1){
					//读取表头
					$header[$ii] =$excel->getActiveSheet()->getCell($this->getRowName($ii).$i)->getValue();
				}else{
					//读取数据
					$data[$i][$header[$ii]] = $excel->getActiveSheet()->getCell($this->getRowName($ii).$i)->getValue();
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * 数字转字母列号
	 * @param int $key 序号（从0开始）
	 * @return string
	 */
	private function getRowName($key = 0){
		$value = ['', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'A'];
		
		return $value[floor($key/26)%26].$value[$key%26+1];
	}
	
	/**
	 * 字母列号转回数值
	 * @param string $key 字符列号
	 * @return int
	 */
	private function getRowNumber($key = ''){
		$value = ['', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'A'];
		
		return mb_strlen($key)==1 ? array_search($key,$value) : array_search(mb_substr($key,0,1),$value)*26+array_search(mb_substr($key,1,1),$value)-1;
	}
	
}