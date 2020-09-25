<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHP Model模型类
 * 实现了ORM和ActiveRecords模式
 */
class Model {
    // 操作状态
    const MODEL_INSERT          =   1;      //  插入模型数据
    const MODEL_UPDATE          =   2;      //  更新模型数据
    const MODEL_BOTH            =   3;      //  包含上面两种方式
    const MUST_VALIDATE         =   1;      // 必须验证
    const EXISTS_VALIDATE       =   0;      // 表单存在字段则验证
    const VALUE_VALIDATE        =   2;      // 表单值不为空则验证

    //
    protected $db               =   null;
	//
	private   $_db				=	array();
    //
    protected $pk               =   'id';
    //
    protected $autoinc          =   false;    
    //
    protected $tablePrefix      =   null;
    //
    protected $name             =   '';
    // 数据库名称
    protected $dbName           =   '';
    //数据库配置
    protected $connection       =   '';
    // 数据表名（不包含表前缀）
    protected $tableName        =   '';
    // 实际数据表名（包含表前缀）
    protected $trueTableName    =   '';
    // 最近错误信息
    protected $error            =   '';
    // 字段信息
    protected $fields           =   array();
    // 数据信息
    protected $data             =   array();
    // 查询表达式参数
    protected $options          =   array();
    protected $_validate        =   array();  // 自动验证定义
    protected $_auto            =   array();  // 自动完成定义
    protected $_map             =   array();  // 字段映射定义
    protected $_scope           =   array();  // 命名范围定义
    // 是否自动检测数据表字段信息
    protected $autoCheckFields  =   true;
    // 是否批处理验证
    protected $patchValidate    =   false;
    // 链操作方法列表
    protected $methods          =   array('strict','order','alias','having','group','lock','distinct','auto','filter','validate','result','token','index','force');

    /**
     *
     * @access public
     * @param string $name 模型名称
     * @param string $tablePrefix 表前缀
     * @param mixed $connection 数据库连接信息
     */
    public function __construct($name='',$tablePrefix='',$connection='') {
        //
        $this->_initialize();
        //
        if(!empty($name)) {
            if(strpos($name,'.')) { //
                list($this->dbName,$this->name) = explode('.',$name);
            }else{
                $this->name   =  $name;
            }
        }elseif(empty($this->name)){
            $this->name =   $this->getModelName();
        }
        //
        if(is_null($tablePrefix)) {//
            $this->tablePrefix = '';
        }elseif('' != $tablePrefix) {
            $this->tablePrefix = $tablePrefix;
        }elseif(!isset($this->tablePrefix)){
            $this->tablePrefix = C('DB_PREFIX');
        }

        //
        //
        //
        $this->db(0,empty($this->connection)?$connection:$this->connection,true);
    }

    /**
     *
     * @access protected
     * @return void
     */
    protected function _checkTableInfo() {
        //
        //
        if(empty($this->fields)) {
            //
            if(C('DB_FIELDS_CACHE')) {
                $db   =  $this->dbName?:C('DB_NAME');
                $fields = F('_fields/'.strtolower($db.'.'.$this->tablePrefix.$this->name));
                if($fields) {
                    $this->fields   =   $fields;
                    if(!empty($fields['_pk'])){
                        $this->pk       =   $fields['_pk'];
                    }
                    return ;
                }
            }
            //
            $this->flush();
        }
    }

    /**
     *
     * @access public
     * @return void
     */
    public function flush() {
        //
        $this->db->setModel($this->name);
        $fields =   $this->db->getFields($this->getTableName());
        if(!$fields) { //
            return false;
        }
        $this->fields   =   array_keys($fields);
        unset($this->fields['_pk']);
        foreach ($fields as $key=>$val){
            //
            $type[$key]     =   $val['type'];
            if($val['primary']) {
                  //
                if (isset($this->fields['_pk']) && $this->fields['_pk'] != null) {
                    if (is_string($this->fields['_pk'])) {
                        $this->pk   =   array($this->fields['_pk']);
                        $this->fields['_pk']   =   $this->pk;
                    }
                    $this->pk[]   =   $key;
                    $this->fields['_pk'][]   =   $key;
                } else {
                    $this->pk   =   $key;
                    $this->fields['_pk']   =   $key;
                }
                if($val['autoinc']) $this->autoinc   =   true;
            }
        }
        //
        $this->fields['_type'] =  $type;

        // 2008-3-7
        if(C('DB_FIELDS_CACHE')){
            //
            $db   =  $this->dbName?:C('DB_NAME');
            F('_fields/'.strtolower($db.'.'.$this->tablePrefix.$this->name),$this->fields);
        }
    }

    /**
     *
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($name,$value) {
        //
        $this->data[$name]  =   $value;
    }

    /**
     *
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name) {
        return isset($this->data[$name])?$this->data[$name]:null;
    }

    /**
     *
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     *
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset($name) {
        unset($this->data[$name]);
    }

    /**
     *
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method,$args) {
        if(in_array(strtolower($method),$this->methods,true)) {
            //
            $this->options[strtolower($method)] =   $args[0];
            return $this;
        }elseif(in_array(strtolower($method),array('count','sum','min','max','avg'),true)){
            //
            $field =  isset($args[0])?$args[0]:'*';
            return $this->getField(strtoupper($method).'('.$field.') AS tp_'.$method);
        }elseif(strtolower(substr($method,0,5))=='getby') {
            //
            $field   =   parse_name(substr($method,5));
            $where[$field] =  $args[0];
            return $this->where($where)->find();
        }elseif(strtolower(substr($method,0,10))=='getfieldby') {
            //
            $name   =   parse_name(substr($method,10));
            $where[$name] =$args[0];
            return $this->where($where)->getField($args[1]);
        }elseif(isset($this->_scope[$method])){//
            return $this->scope($method,$args[0]);
        }else{
            E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }
    //
    protected function _initialize() {}

    /**
     *
     * @access protected
     * @param mixed $data 要操作的数据
     * @return boolean
     */
     protected function _facade($data) {

        //
        if(!empty($this->fields)) {
            if(!empty($this->options['field'])) {
                $fields =   $this->options['field'];
                unset($this->options['field']);
                if(is_string($fields)) {
                    $fields =   explode(',',$fields);
                }    
            }else{
                $fields =   $this->fields;
            }        
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields,true)){
                    if(!empty($this->options['strict'])){
                        E(L('_DATA_TYPE_INVALID_').':['.$key.'=>'.$val.']');
                    }
                    unset($data[$key]);
                }elseif(is_scalar($val)) {
                    //
                    $this->_parseType($data,$key);
                }
            }
        }
       
        //
        if(!empty($this->options['filter'])) {
            $data = array_map($this->options['filter'],$data);
            unset($this->options['filter']);
        }
        $this->_before_write($data);
        return $data;
     }

    //
    protected function _before_write(&$data) {}

    /**
     *
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @param boolean $replace 是否replace
     * @return mixed
     */
    public function add($data='',$options=array(),$replace=false) {
        if(empty($data)) {
            //
            if(!empty($this->data)) {
                $data           =   $this->data;
                //
                $this->data     = array();
            }else{
                $this->error    = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        //
        $data       =   $this->_facade($data);
        //
        $options    =   $this->_parseOptions($options);
        if(false === $this->_before_insert($data,$options)) {
            return false;
        }
        //
        $result = $this->db->insert($data,$options,$replace);
        if(false !== $result && is_numeric($result)) {
            $pk     =   $this->getPk();
              //
            if (is_array($pk)) return $result;
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                //
                $data[$pk]  = $insertId;
                if(false === $this->_after_insert($data,$options)) {
                    return false;
                }
                return $insertId;
            }
            if(false === $this->_after_insert($data,$options)) {
                return false;
            }
        }
        return $result;
    }
    //
    protected function _before_insert(&$data,$options) {}
    //
    protected function _after_insert($data,$options) {}

    public function addAll($dataList,$options=array(),$replace=false){
        if(empty($dataList)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        //
        foreach ($dataList as $key=>$data){
            $dataList[$key] = $this->_facade($data);
        }
        //
        $options =  $this->_parseOptions($options);
        //
        $result = $this->db->insertAll($dataList,$options,$replace);
        if(false !== $result ) {
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                return $insertId;
            }
        }
        return $result;
    }

    /**
     *
     * @access public
     * @param string $fields 要插入的数据表字段名
     * @param string $table 要插入的数据表名
     * @param array $options 表达式
     * @return boolean
     */
    public function selectAdd($fields='',$table='',$options=array()) {
        //
        $options =  $this->_parseOptions($options);
        //
        if(false === $result = $this->db->selectInsert($fields?:$options['field'],$table?:$this->getTableName(),$options)){
            //
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
            //
            return $result;
        }
    }

    /**
     *
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return boolean
     */
    public function save($data='',$options=array()) {
        if(empty($data)) {
            //
            if(!empty($this->data)) {
                $data           =   $this->data;
                //
                $this->data     =   array();
            }else{
                $this->error    =   L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        //
        $data       =   $this->_facade($data);
        if(empty($data)){
            //
            $this->error    =   L('_DATA_TYPE_INVALID_');
            return false;
        }
        //
        $options    =   $this->_parseOptions($options);
        $pk         =   $this->getPk();
        if(!isset($options['where']) ) {
            //
            if (is_string($pk) && isset($data[$pk])) {
                $where[$pk]     =   $data[$pk];
                unset($data[$pk]);
            } elseif (is_array($pk)) {
                //
                foreach ($pk as $field) {
                    if(isset($data[$field])) {
                        $where[$field]      =   $data[$field];
                    } else {
                           //
                        $this->error        =   L('_OPERATION_WRONG_');
                        return false;
                    }
                    unset($data[$field]);
                }
            }
            if(!isset($where)){
                //
                $this->error        =   L('_OPERATION_WRONG_');
                return false;
            }else{
                $options['where']   =   $where;
            }
        }

        if(is_array($options['where']) && isset($options['where'][$pk])){
            $pkValue    =   $options['where'][$pk];
        }
        if(false === $this->_before_update($data,$options)) {
            return false;
        }
        $result     =   $this->db->update($data,$options);
        if(false !== $result && is_numeric($result)) {
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            $this->_after_update($data,$options);
        }
        return $result;
    }
    //
    protected function _before_update(&$data,$options) {}
    //
    protected function _after_update($data,$options) {}

    /**
     *
     * @access public
     * @param mixed $options 表达式
     * @return mixed
     */
    public function delete($options=array()) {
        $pk   =  $this->getPk();
        if(empty($options) && empty($this->options['where'])) {
            //
            if(!empty($this->data) && isset($this->data[$pk]))
                return $this->delete($this->data[$pk]);
            else
                return false;
        }
        if(is_numeric($options)  || is_string($options)) {
            //
            if(strpos($options,',')) {
                $where[$pk]     =  array('IN', $options);
            }else{
                $where[$pk]     =  $options;
            }
            $options            =  array();
            $options['where']   =  $where;
        }
        //
        if (is_array($options) && (count($options) > 0) && is_array($pk)) {
            $count = 0;
            foreach (array_keys($options) as $key) {
                if (is_int($key)) $count++; 
            } 
            if ($count == count($pk)) {
                $i = 0;
                foreach ($pk as $field) {
                    $where[$field] = $options[$i];
                    unset($options[$i++]);
                }
                $options['where']  =  $where;
            } else {
                return false;
            }
        }
        //
        $options =  $this->_parseOptions($options);
        if(empty($options['where'])){
            //
            return false;
        }        
        if(is_array($options['where']) && isset($options['where'][$pk])){
            $pkValue            =  $options['where'][$pk];
        }

        if(false === $this->_before_delete($options)) {
            return false;
        }        
        $result  =    $this->db->delete($options);
        if(false !== $result && is_numeric($result)) {
            $data = array();
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            $this->_after_delete($data,$options);
        }
        //
        return $result;
    }
    //
    protected function _before_delete($options) {}    
    //
    protected function _after_delete($data,$options) {}

    /**
     *
     * @access public
     * @param array $options 表达式参数
     * @return mixed
     */
    public function select($options=array()) {
        $pk   =  $this->getPk();
        if(is_string($options) || is_numeric($options)) {
            //
            if(strpos($options,',')) {
                $where[$pk]     =  array('IN',$options);
            }else{
                $where[$pk]     =  $options;
            }
            $options            =  array();
            $options['where']   =  $where;
        }elseif (is_array($options) && (count($options) > 0) && is_array($pk)) {
            //
            $count = 0;
            foreach (array_keys($options) as $key) {
                if (is_int($key)) $count++; 
            } 
            if ($count == count($pk)) {
                $i = 0;
                foreach ($pk as $field) {
                    $where[$field] = $options[$i];
                    unset($options[$i++]);
                }
                $options['where']  =  $where;
            } else {
                return false;
            }
        } elseif(false === $options){ //
        	$options['fetch_sql'] = true;
        }
        //
        $options    =  $this->_parseOptions($options);
        //
        if(isset($options['cache'])){
            $cache  =   $options['cache'];
            $key    =   is_string($cache['key'])?$cache['key']:md5(serialize($options));
            $data   =   S($key,'',$cache);
            if(false !== $data){
                return $data;
            }
        }        
        $resultSet  = $this->db->select($options);
        if(false === $resultSet) {
            return false;
        }
        if(!empty($resultSet)) { //
            if(is_string($resultSet)){
                return $resultSet;
            }

            $resultSet  =   array_map(array($this,'_read_data'),$resultSet);
            $this->_after_select($resultSet,$options);
            if(isset($options['index'])){ //
                $index  =   explode(',',$options['index']);
                foreach ($resultSet as $result){
                    $_key   =  $result[$index[0]];
                    if(isset($index[1]) && isset($result[$index[1]])){
                        $cols[$_key] =  $result[$index[1]];
                    }else{
                        $cols[$_key] =  $result;
                    }
                }
                $resultSet  =   $cols;
            }
        }

        if(isset($cache)){
            S($key,$resultSet,$cache);
        }
        return $resultSet;
    }
    //
    protected function _after_select(&$resultSet,$options) {}

    /**
     *
     * @access public
     * @return string
     */
    public function buildSql() {
        return  '( '.$this->fetchSql(true)->select().' )';
    }

    /**
     *
     * @access protected
     * @param array $options 表达式参数
     * @return array
     */
    protected function _parseOptions($options=array()) {
        if(is_array($options))
            $options =  array_merge($this->options,$options);

        if(!isset($options['table'])){
            //
            $options['table']   =   $this->getTableName();
            $fields             =   $this->fields;
        }else{
            //
            $fields             =   $this->getDbFields();
        }

        //
        if(!empty($options['alias'])) {
            $options['table']  .=   ' '.$options['alias'];
        }
        //
        $options['model']       =   $this->name;

        //
        if(isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join'])) {
            //
            foreach ($options['where'] as $key=>$val){
                $key            =   trim($key);
                if(in_array($key,$fields,true)){
                    if(is_scalar($val)) {
                        $this->_parseType($options['where'],$key);
                    }
                }elseif(!is_numeric($key) && '_' != substr($key,0,1) && false === strpos($key,'.') && false === strpos($key,'(') && false === strpos($key,'|') && false === strpos($key,'&')){
                    if(!empty($this->options['strict'])){
                        E(L('_ERROR_QUERY_EXPRESS_').':['.$key.'=>'.$val.']');
                    } 
                    unset($options['where'][$key]);
                }
            }
        }
        //
        $this->options  =   array();
        //
        $this->_options_filter($options);
        return $options;
    }
    //
    protected function _options_filter(&$options) {}

    /**
     *
     * @access protected
     * @param mixed $data 数据
     * @param string $key 字段名
     * @return void
     */
    protected function _parseType(&$data,$key) {
        if(!isset($this->options['bind'][':'.$key]) && isset($this->fields['_type'][$key])){
            $fieldType = strtolower($this->fields['_type'][$key]);
            if(false !== strpos($fieldType,'enum')){
                //
            }elseif(false === strpos($fieldType,'bigint') && false !== strpos($fieldType,'int')) {
                $data[$key]   =  intval($data[$key]);
            }elseif(false !== strpos($fieldType,'float') || false !== strpos($fieldType,'double')){
                $data[$key]   =  floatval($data[$key]);
            }elseif(false !== strpos($fieldType,'bool')){
                $data[$key]   =  (bool)$data[$key];
            }
        }
    }

    /**
     *
     * @access protected
     * @param array $data 当前数据
     * @return array
     */
    protected function _read_data($data) {
        //
        if(!empty($this->_map) && C('READ_DATA_MAP')) {
            foreach ($this->_map as $key=>$val){
                if(isset($data[$val])) {
                    $data[$key] =   $data[$val];
                    unset($data[$val]);
                }
            }
        }
        return $data;
    }

    /**
     *
     * @access public
     * @param mixed $options 表达式参数
     * @return mixed
     */
    public function find($options=array()) {
        if(is_numeric($options) || is_string($options)) {
            $where[$this->getPk()]  =   $options;
            $options                =   array();
            $options['where']       =   $where;
        }
        //
        $pk  =  $this->getPk();
        if (is_array($options) && (count($options) > 0) && is_array($pk)) {
            //
            $count = 0;
            foreach (array_keys($options) as $key) {
                if (is_int($key)) $count++; 
            } 
            if ($count == count($pk)) {
                $i = 0;
                foreach ($pk as $field) {
                    $where[$field] = $options[$i];
                    unset($options[$i++]);
                }
                $options['where']  =  $where;
            } else {
                return false;
            }
        }
        //
        $options['limit']   =   1;
        //
        $options            =   $this->_parseOptions($options);
        //
        if(isset($options['cache'])){
            $cache  =   $options['cache'];
            $key    =   is_string($cache['key'])?$cache['key']:md5(serialize($options));
            $data   =   S($key,'',$cache);
            if(false !== $data){
                $this->data     =   $data;
                return $data;
            }
        }
        $resultSet          =   $this->db->select($options);
        if(false === $resultSet) {
            return false;
        }
        if(empty($resultSet)) {//
            return null;
        }
        if(is_string($resultSet)){
            return $resultSet;
        }

        //
        $data   =   $this->_read_data($resultSet[0]);
        $this->_after_find($data,$options);
        if(!empty($this->options['result'])) {
            return $this->returnResult($data,$this->options['result']);
        }
        $this->data     =   $data;
        if(isset($cache)){
            S($key,$data,$cache);
        }
        return $this->data;
    }
    //
    protected function _after_find(&$result,$options) {}

    protected function returnResult($data,$type=''){
        if ($type){
            if(is_callable($type)){
                return call_user_func($type,$data);
            }
            switch (strtolower($type)){
                case 'json':
                    return json_encode($data);
                case 'xml':
                    return xml_encode($data);
            }
        }
        return $data;
    }

    /**
     *
     * @access public
     * @param array $data 当前数据
     * @param integer $type 类型 0 写入 1 读取
     * @return array
     */
    public function parseFieldsMap($data,$type=1) {
        // 检查字段映射
        if(!empty($this->_map)) {
            foreach ($this->_map as $key=>$val){
                if($type==1) { // 读取
                    if(isset($data[$val])) {
                        $data[$key] =   $data[$val];
                        unset($data[$val]);
                    }
                }else{
                    if(isset($data[$key])) {
                        $data[$val] =   $data[$key];
                        unset($data[$key]);
                    }
                }
            }
        }
        return $data;
    }

    /**
     *
     * @access public
     * @param string|array $field  字段名
     * @param string $value  字段值
     * @return boolean
     */
    public function setField($field,$value='') {
        if(is_array($field)) {
            $data           =   $field;
        }else{
            $data[$field]   =   $value;
        }
        return $this->save($data);
    }

    /**
     *
     * @access public
     * @param string $field  字段名
     * @param integer $step  增长值
     * @param integer $lazyTime  延时时间(s)
     * @return boolean
     */
    public function setInc($field,$step=1,$lazyTime=0) {
        if($lazyTime>0) {//
            $condition 	=  	$this->options['where'];
            $guid 		=  	md5($this->name.'_'.$field.'_'.serialize($condition));
            $step 		= 	$this->lazyWrite($guid,$step,$lazyTime);
            if(empty($step)) {
            	return true; //
            }elseif($step < 0) {
            	$step 	=	'-'.$step;
            }
        }
        return $this->setField($field,array('exp',$field.'+'.$step));
    }

    /**
     *
     * @access public
     * @param string $field  字段名
     * @param integer $step  减少值
     * @param integer $lazyTime  延时时间(s)
     * @return boolean
     */
    public function setDec($field,$step=1,$lazyTime=0) {
        if($lazyTime>0) {//
            $condition  =  	$this->options['where'];
            $guid 		=  	md5($this->name.'_'.$field.'_'.serialize($condition));
            $step 		= 	$this->lazyWrite($guid,-$step,$lazyTime);
            if(empty($step)) {
            	return true; //
            }elseif($step > 0) {
            	$step 	=	'-'.$step;
            }
        }
        return $this->setField($field,array('exp',$field.'-'.$step));
    }

    /**
     *
     *
     * @access public
     * @param string $guid  写入标识
     * @param integer $step  写入步进值
     * @param integer $lazyTime  延时时间(s)
     * @return false|integer
     */
    protected function lazyWrite($guid,$step,$lazyTime) {
        if(false !== ($value = S($guid))) { //
            if(NOW_TIME > S($guid.'_time')+$lazyTime) {
                //
                S($guid,NULL);
                S($guid.'_time',NULL);
                return $value+$step;
            }else{
                //
                S($guid,$value+$step);
                return false;
            }
        }else{ //
            S($guid,$step);
            //
            S($guid.'_time',NOW_TIME);
            return false;
        }
    }

    /**
     *
     * @access public
     * @param string $field  字段名
     * @param string $spea  字段数据间隔符号 NULL返回数组
     * @return mixed
     */
    public function getField($field,$sepa=null) {
        $options['field']       =   $field;
        $options                =   $this->_parseOptions($options);
        //
        if(isset($options['cache'])){
            $cache  =   $options['cache'];
            $key    =   is_string($cache['key'])?$cache['key']:md5($sepa.serialize($options));
            $data   =   S($key,'',$cache);
            if(false !== $data){
                return $data;
            }
        }        
        $field                  =   trim($field);
        if(strpos($field,',') && false !== $sepa) { // 多字段
            if(!isset($options['limit'])){
                $options['limit']   =   is_numeric($sepa)?$sepa:'';
            }
            $resultSet          =   $this->db->select($options);
            if(!empty($resultSet)) {
		        if(is_string($resultSet)){
		            return $resultSet;
		        }            	
                $_field         =   explode(',', $field);
                $field          =   array_keys($resultSet[0]);
                $key1           =   array_shift($field);
                $key2           =   array_shift($field);
                $cols           =   array();
                $count          =   count($_field);
                foreach ($resultSet as $result){
                    $name   =  $result[$key1];
                    if(2==$count) {
                        $cols[$name]   =  $result[$key2];
                    }else{
                        $cols[$name]   =  is_string($sepa)?implode($sepa,array_slice($result,1)):$result;
                    }
                }
                if(isset($cache)){
                    S($key,$cols,$cache);
                }
                return $cols;
            }
        }else{   //
            //
            if(true !== $sepa) {//
                $options['limit']   =   is_numeric($sepa)?$sepa:1;
            }
            $result = $this->db->select($options);
            if(!empty($result)) {
		        if(is_string($result)){
		            return $result;
		        }            	
                if(true !== $sepa && 1==$options['limit']) {
                    $data   =   reset($result[0]);
                    if(isset($cache)){
                        S($key,$data,$cache);
                    }            
                    return $data;
                }
                foreach ($result as $val){
                    $array[]    =   $val[$field];
                }
                if(isset($cache)){
                    S($key,$array,$cache);
                }                
                return $array;
            }
        }
        return null;
    }

    /**
     *
     * @access public
     * @param mixed $data 创建数据
     * @param string $type 状态
     * @return mixed
     */
     public function create($data='',$type='') {
        //
        if(empty($data)) {
            $data   =   I('post.');
        }elseif(is_object($data)){
            $data   =   get_object_vars($data);
        }
        //
        if(empty($data) || !is_array($data)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }

        //
        $type = $type?:(!empty($data[$this->getPk()])?self::MODEL_UPDATE:self::MODEL_INSERT);

        //
		$data =	$this->parseFieldsMap($data,0);

        //
        if(isset($this->options['field'])) { // $this->field('field1,field2...')->create()
            $fields =   $this->options['field'];
            unset($this->options['field']);
        }elseif($type == self::MODEL_INSERT && isset($this->insertFields)) {
            $fields =   $this->insertFields;
        }elseif($type == self::MODEL_UPDATE && isset($this->updateFields)) {
            $fields =   $this->updateFields;
        }
        if(isset($fields)) {
            if(is_string($fields)) {
                $fields =   explode(',',$fields);
            }
            //
            if(C('TOKEN_ON'))   $fields[] = C('TOKEN_NAME', null, '__hash__');
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }
            }
        }

        //
        if(!$this->autoValidation($data,$type)) return false;

        //
        if(!$this->autoCheckToken($data)) {
            $this->error = L('_TOKEN_ERROR_');
            return false;
        }

        //
        if($this->autoCheckFields) { //
            $fields =   $this->getDbFields();
            foreach ($data as $key=>$val){
                if(!in_array($key,$fields)) {
                    unset($data[$key]);
                }elseif(MAGIC_QUOTES_GPC && is_string($val)){
                    $data[$key] =   stripslashes($val);
                }
            }
        }

        //
        $this->autoOperation($data,$type);
        //
        $this->data =   $data;
        //
        return $data;
     }

    //
    // TODO  ajax无刷新多次提交暂不能满足
    public function autoCheckToken($data) {
        //
        if(isset($this->options['token']) && !$this->options['token']) return true;
        if(C('TOKEN_ON')){
            $name   = C('TOKEN_NAME', null, '__hash__');
            if(!isset($data[$name]) || !isset($_SESSION[$name])) { //
                return false;
            }

            //
            list($key,$value)  =  explode('_',$data[$name]);
            if(isset($_SESSION[$name][$key]) && $value && $_SESSION[$name][$key] === $value) { //
                unset($_SESSION[$name][$key]); //
                return true;
            }
            //
            if(C('TOKEN_RESET')) unset($_SESSION[$name][$key]);
            return false;
        }
        return true;
    }

    /**
     *
     * @access public
     * @param string $value  要验证的数据
     * @param string $rule 验证规则
     * @return boolean
     */
    public function regex($value,$rule) {
        $validate = array(
            'require'   =>  '/\S+/',
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency'  =>  '/^\d+(\.\d+)?$/',
            'number'    =>  '/^\d+$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
        );
        //
        if(isset($validate[strtolower($rule)]))
            $rule       =   $validate[strtolower($rule)];
        return preg_match($rule,$value)===1;
    }

    /**
     *
     * @access public
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return mixed
     */
    private function autoOperation(&$data,$type) {
    	if(false === $this->options['auto']){
    		//
    		return $data;
    	}
        if(!empty($this->options['auto'])) {
            $_auto   =   $this->options['auto'];
            unset($this->options['auto']);
        }elseif(!empty($this->_auto)){
            $_auto   =   $this->_auto;
        }
        //
        if(isset($_auto)) {
            foreach ($_auto as $auto){
                //
                //
                if(empty($auto[2])) $auto[2] =  self::MODEL_INSERT; //
                if( $type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    if(empty($auto[3])) $auto[3] =  'string';
                    switch(trim($auto[3])) {
                        case 'function':    //
                        case 'callback': //
                            $args = isset($auto[4])?(array)$auto[4]:array();
                            if(isset($data[$auto[0]])) {
                                array_unshift($args,$data[$auto[0]]);
                            }
                            if('function'==$auto[3]) {
                                $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                            }else{
                                $data[$auto[0]]  =  call_user_func_array(array(&$this,$auto[1]), $args);
                            }
                            break;
                        case 'field':    //
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore': //
                            if($auto[1]===$data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default: //
                            $data[$auto[0]] = $auto[1];
                    }
                    if(isset($data[$auto[0]]) && false === $data[$auto[0]] )   unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }

    /**
     *
     * @access protected
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return boolean
     */
    protected function autoValidation($data,$type) {
    	if(false === $this->options['validate'] ){
    		//
    		return true;
    	}
        if(!empty($this->options['validate'])) {
            $_validate   =   $this->options['validate'];
            unset($this->options['validate']);
        }elseif(!empty($this->_validate)){
            $_validate   =   $this->_validate;
        }
        //
        if(isset($_validate)) { //
            if($this->patchValidate) { //
                $this->error = array();
            }
            foreach($_validate as $key=>$val) {
                //
                //
                //
                if(empty($val[5]) || ( $val[5]== self::MODEL_BOTH && $type < 3 ) || $val[5]== $type ) {
                    if(0==strpos($val[2],'{%') && strpos($val[2],'}'))
                        //
                        $val[2]  =  L(substr($val[2],2,-1));
                    $val[3]  =  isset($val[3])?$val[3]:self::EXISTS_VALIDATE;
                    $val[4]  =  isset($val[4])?$val[4]:'regex';
                    //
                    switch($val[3]) {
                        case self::MUST_VALIDATE:   //
                            if(false === $this->_validationField($data,$val)) 
                                return false;
                            break;
                        case self::VALUE_VALIDATE:    //
                            if('' != trim($data[$val[0]]))
                                if(false === $this->_validationField($data,$val)) 
                                    return false;
                            break;
                        default:    //
                            if(isset($data[$val[0]]))
                                if(false === $this->_validationField($data,$val)) 
                                    return false;
                    }
                }
            }
            //
            if(!empty($this->error)) return false;
        }
        return true;
    }

    /**
     *
     *
     * @access protected
     * @param array $data 创建数据
     * @param array $val 验证因子
     * @return boolean
     */
    protected function _validationField($data,$val) {
        if($this->patchValidate && isset($this->error[$val[0]]))
            return ; //
        if(false === $this->_validationFieldItem($data,$val)){
            if($this->patchValidate) {
                $this->error[$val[0]]   =   $val[2];
            }else{
                $this->error            =   $val[2];
                return false;
            }
        }
        return ;
    }

    /**
     *
     * @access protected
     * @param array $data 创建数据
     * @param array $val 验证因子
     * @return boolean
     */
    protected function _validationFieldItem($data,$val) {
        switch(strtolower(trim($val[4]))) {
            case 'function'://
            case 'callback'://
                $args = isset($val[6])?(array)$val[6]:array();
                if(is_string($val[0]) && strpos($val[0], ','))
                    $val[0] = explode(',', $val[0]);
                if(is_array($val[0])){
                    //
                    foreach($val[0] as $field)
                        $_data[$field] = $data[$field];
                    array_unshift($args, $_data);
                }else{
                    array_unshift($args, $data[$val[0]]);
                }
                if('function'==$val[4]) {
                    return call_user_func_array($val[1], $args);
                }else{
                    return call_user_func_array(array(&$this, $val[1]), $args);
                }
            case 'confirm': //
                return $data[$val[0]] == $data[$val[1]];
            case 'unique': //
                if(is_string($val[0]) && strpos($val[0],','))
                    $val[0]  =  explode(',',$val[0]);
                $map = array();
                if(is_array($val[0])) {
                    //
                    foreach ($val[0] as $field)
                        $map[$field]   =  $data[$field];
                }else{
                    $map[$val[0]] = $data[$val[0]];
                }
                $pk =   $this->getPk();
                if(!empty($data[$pk]) && is_string($pk)) { //
                    $map[$pk] = array('neq',$data[$pk]);
                }
                if($this->where($map)->find())   return false;
                return true;
            default:  //
                return $this->check($data[$val[0]],$val[1],$val[4]);
        }
    }

    /**
     *
     * @access public
     * @param string $value 验证数据
     * @param mixed $rule 验证表达式
     * @param string $type 验证方式 默认为正则验证
     * @return boolean
     */
    public function check($value,$rule,$type='regex'){
        $type   =   strtolower(trim($type));
        switch($type) {
            case 'in': //
            case 'notin':
                $range   = is_array($rule)? $rule : explode(',',$rule);
                return $type == 'in' ? in_array($value ,$range) : !in_array($value ,$range);
            case 'between': //
            case 'notbetween': //
                if (is_array($rule)){
                    $min    =    $rule[0];
                    $max    =    $rule[1];
                }else{
                    list($min,$max)   =  explode(',',$rule);
                }
                return $type == 'between' ? $value>=$min && $value<=$max : $value<$min || $value>$max;
            case 'equal': //
            case 'notequal': //
                return $type == 'equal' ? $value == $rule : $value != $rule;
            case 'length': //
                $length  =  mb_strlen($value,'utf-8'); //
                if(strpos($rule,',')) { //
                    list($min,$max)   =  explode(',',$rule);
                    return $length >= $min && $length <= $max;
                }else{//
                    return $length == $rule;
                }
            case 'expire':
                list($start,$end)   =  explode(',',$rule);
                if(!is_numeric($start)) $start   =  strtotime($start);
                if(!is_numeric($end)) $end   =  strtotime($end);
                return NOW_TIME >= $start && NOW_TIME <= $end;
            case 'ip_allow': //
                return in_array(get_client_ip(),explode(',',$rule));
            case 'ip_deny': //
                return !in_array(get_client_ip(),explode(',',$rule));
            case 'regex':
            default:    //
                //
                return $this->regex($value,$rule);
        }
    }

    /**
     *
     * @access public
     * @param string $sql  SQL指令
     * @param mixed $parse  是否需要解析SQL
     * @return array
     */
    public function procedure($sql, $parse = false) {
        return $this->db->procedure($sql, $parse);
    }

    /**
     *
     * @access public
     * @param string $sql  SQL指令
     * @param mixed $parse  是否需要解析SQL
     * @return mixed
     */
    public function query($sql,$parse=false) {
        if(!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql  =   $this->parseSql($sql,$parse);
        return $this->db->query($sql);
    }

    /**
     *
     * @access public
     * @param string $sql  SQL指令
     * @param mixed $parse  是否需要解析SQL
     * @return false | integer
     */
    public function execute($sql,$parse=false) {
        if(!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql  =   $this->parseSql($sql,$parse);
        return $this->db->execute($sql);
    }

    /**
     *
     * @access public
     * @param string $sql  SQL指令
     * @param boolean $parse  是否需要解析SQL
     * @return string
     */
    protected function parseSql($sql,$parse) {
        //
        if(true === $parse) {
            $options =  $this->_parseOptions();
            $sql    =   $this->db->parseSql($sql,$options);
        }elseif(is_array($parse)){ // SQL预处理
            $parse  =   array_map(array($this->db,'escapeString'),$parse);
            $sql    =   vsprintf($sql,$parse);
        }else{
            $sql    =   strtr($sql,array('__TABLE__'=>$this->getTableName(),'__PREFIX__'=>$this->tablePrefix));
            $prefix =   $this->tablePrefix;
            $sql    =   preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $sql);
        }
        $this->db->setModel($this->name);
        return $sql;
    }

    /**
     *
     * @access public
     * @param integer $linkNum  连接序号
     * @param mixed $config  数据库连接信息
     * @param boolean $force 强制重新连接
     * @return Model
     */
    public function db($linkNum='',$config='',$force=false) {
        if('' === $linkNum && $this->db) {
            return $this->db;
        }

        if(!isset($this->_db[$linkNum]) || $force ) {
            //
            if(!empty($config) && is_string($config) && false === strpos($config,'/')) { //
                $config  =  C($config);
            }
            $this->_db[$linkNum]            =    Db::getInstance($config);
        }elseif(NULL === $config){
            $this->_db[$linkNum]->close(); //
            unset($this->_db[$linkNum]);
            return ;
        }

        //
        $this->db   =    $this->_db[$linkNum];
        $this->_after_db();
        //
        if(!empty($this->name) && $this->autoCheckFields)    $this->_checkTableInfo();
        return $this;
    }
    //
    protected function _after_db() {}

    /**
     *
     * @access public
     * @return string
     */
    public function getModelName() {
        if(empty($this->name)){
            $name = substr(get_class($this),0,-strlen(C('DEFAULT_M_LAYER')));
            if ( $pos = strrpos($name,'\\') ) {//
                $this->name = substr($name,$pos+1);
            }else{
                $this->name = $name;
            }
        }
        return $this->name;
    }

    /**
     *
     * @access public
     * @return string
     */
    public function getTableName() {
        if(empty($this->trueTableName)) {
            $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if(!empty($this->tableName)) {
                $tableName .= $this->tableName;
            }else{
                $tableName .= parse_name($this->name);
            }
            $this->trueTableName    =   strtolower($tableName);
        }
        return (!empty($this->dbName)?$this->dbName.'.':'').$this->trueTableName;
    }

    /**
     *
     * @access public
     * @return void
     */
    public function startTrans() {
        $this->commit();
        $this->db->startTrans();
        return ;
    }

    /**
     *
     * @access public
     * @return boolean
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     *
     * @access public
     * @return boolean
     */
    public function rollback() {
        return $this->db->rollback();
    }

    /**
     *
     * @access public
     * @return string
     */
    public function getError(){
        return $this->error;
    }

    /**
     *
     * @access public
     * @return string
     */
    public function getDbError() {
        return $this->db->getError();
    }

    /**
     *
     * @access public
     * @return string
     */
    public function getLastInsID() {
        return $this->db->getLastInsID();
    }

    /**
     *
     * @access public
     * @return string
     */
    public function getLastSql() {
        return $this->db->getLastSql($this->name);
    }
    //
    public function _sql(){
        return $this->getLastSql();
    }

    /**
     *
     * @access public
     * @return string
     */
    public function getPk() {
        return $this->pk;
    }

    /**
     *
     * @access public
     * @return array
     */
    public function getDbFields(){
        if(isset($this->options['table'])) {//
            if(is_array($this->options['table'])){
                $table  =   key($this->options['table']);
            }else{
                $table  =   $this->options['table'];
                if(strpos($table,')')){
                    //
                    return false;
                }
            }
            $fields     =   $this->db->getFields($table);
            return  $fields ? array_keys($fields) : false;
        }
        if($this->fields) {
            $fields     =  $this->fields;
            unset($fields['_type'],$fields['_pk']);
            return $fields;
        }
        return false;
    }

    /**
     *
     * @access public
     * @param mixed $data 数据
     * @return Model
     */
    public function data($data=''){
        if('' === $data && !empty($this->data)) {
            return $this->data;
        }
        if(is_object($data)){
            $data   =   get_object_vars($data);
        }elseif(is_string($data)){
            parse_str($data,$data);
        }elseif(!is_array($data)){
            E(L('_DATA_TYPE_INVALID_'));
        }
        $this->data = $data;
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $table
     * @return Model
     */
    public function table($table) {
        $prefix =   $this->tablePrefix;
        if(is_array($table)) {
            $this->options['table'] =   $table;
        }elseif(!empty($table)) {
            //
            $table  = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $table);
            $this->options['table'] =   $table;
        }
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $using
     * @return Model
     */
    public function using($using){
        $prefix =   $this->tablePrefix;
        if(is_array($using)) {
            $this->options['using'] =   $using;
        }elseif(!empty($using)) {
            //
            $using  = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $using);
            $this->options['using'] =   $using;
        }
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $join
     * @param string $type JOIN类型
     * @return Model
     */
    public function join($join,$type='INNER') {
        $prefix =   $this->tablePrefix;
        if(is_array($join)) {
            foreach ($join as $key=>&$_join){
                $_join  =   preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $_join);
                $_join  =   false !== stripos($_join,'JOIN')? $_join : $type.' JOIN ' .$_join;
            }
            $this->options['join']      =   $join;
        }elseif(!empty($join)) {
            //
            $join  = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $join);
            $this->options['join'][]    =   false !== stripos($join,'JOIN')? $join : $type.' JOIN '.$join;
        }
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $union
     * @param boolean $all
     * @return Model
     */
    public function union($union,$all=false) {
        if(empty($union)) return $this;
        if($all) {
            $this->options['union']['_all']  =   true;
        }
        if(is_object($union)) {
            $union   =  get_object_vars($union);
        }
        //
        if(is_string($union) ) {
            $prefix =   $this->tablePrefix;
            //
            $options  = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function($match) use($prefix){ return $prefix.strtolower($match[1]);}, $union);
        }elseif(is_array($union)){
            if(isset($union[0])) {
                $this->options['union']  =  array_merge($this->options['union'],$union);
                return $this;
            }else{
                $options =  $union;
            }
        }else{
            E(L('_DATA_TYPE_INVALID_'));
        }
        $this->options['union'][]  =   $options;
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $key
     * @param integer $expire
     * @param string $type
     * @return Model
     */
    public function cache($key=true,$expire=null,$type=''){
        //
        if(is_numeric($key) && is_null($expire)){
            $expire = $key;
            $key    = true;
        }
        if(false !== $key)
            $this->options['cache']  =  array('key'=>$key,'expire'=>$expire,'type'=>$type);
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $field
     * @param boolean $except 是否排除
     * @return Model
     */
    public function field($field,$except=false){
        if(true === $field) {//
            $fields     =  $this->getDbFields();
            $field      =  $fields?:'*';
        }elseif($except) {//
            if(is_string($field)) {
                $field  =  explode(',',$field);
            }
            $fields     =  $this->getDbFields();
            $field      =  $fields?array_diff($fields,$field):$field;
        }
        $this->options['field']   =   $field;
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $scope 命名范围名称 支持多个 和直接定义
     * @param array $args 参数
     * @return Model
     */
    public function scope($scope='',$args=NULL){
        if('' === $scope) {
            if(isset($this->_scope['default'])) {
                //
                $options    =   $this->_scope['default'];
            }else{
                return $this;
            }
        }elseif(is_string($scope)){ //
            $scopes         =   explode(',',$scope);
            $options        =   array();
            foreach ($scopes as $name){
                if(!isset($this->_scope[$name])) continue;
                $options    =   array_merge($options,$this->_scope[$name]);
            }
            if(!empty($args) && is_array($args)) {
                $options    =   array_merge($options,$args);
            }
        }elseif(is_array($scope)){ //
            $options        =   $scope;
        }
        
        if(is_array($options) && !empty($options)){
            $this->options  =   array_merge($this->options,array_change_key_case($options));
        }
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $where 条件表达式
     * @param mixed $parse 预处理参数
     * @return Model
     */
    public function where($where,$parse=null){
        if(!is_null($parse) && is_string($where)) {
            if(!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $parse = array_map(array($this->db,'escapeString'),$parse);
            $where =   vsprintf($where,$parse);
        }elseif(is_object($where)){
            $where  =   get_object_vars($where);
        }
        if(is_string($where) && '' != $where){
            $map    =   array();
            $map['_string']   =   $where;
            $where  =   $map;
        }        
        if(isset($this->options['where'])){
            $this->options['where'] =   array_merge($this->options['where'],$where);
        }else{
            $this->options['where'] =   $where;
        }
        
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $offset 起始位置
     * @param mixed $length 查询数量
     * @return Model
     */
    public function limit($offset,$length=null){
        if(is_null($length) && strpos($offset,',')){
            list($offset,$length)   =   explode(',',$offset);
        }
        $this->options['limit']     =   intval($offset).( $length? ','.intval($length) : '' );
        return $this;
    }

    /**
     *
     * @access public
     * @param mixed $page 页数
     * @param mixed $listRows 每页数量
     * @return Model
     */
    public function page($page,$listRows=null){
        if(is_null($listRows) && strpos($page,',')){
            list($page,$listRows)   =   explode(',',$page);
        }
        $this->options['page']      =   array(intval($page),intval($listRows));
        return $this;
    }

    /**
     *
     * @access public
     * @param string $comment 注释
     * @return Model
     */
    public function comment($comment){
        $this->options['comment'] =   $comment;
        return $this;
    }

    /**
     *
     * @access public
     * @param boolean $fetch 是否返回sql
     * @return Model
     */
    public function fetchSql($fetch=true){
        $this->options['fetch_sql'] =   $fetch;
        return $this;
    }

    /**
     *
     * @access public
     * @param string $key  参数名
     * @param mixed $value  绑定的变量及绑定参数
     * @return Model
     */
    public function bind($key,$value=false) {
        if(is_array($key)){
            $this->options['bind'] =    $key;
        }else{
            $num =  func_num_args();
            if($num>2){
                $params =   func_get_args();
                array_shift($params);
                $this->options['bind'][$key] =  $params;
            }else{
                $this->options['bind'][$key] =  $value;
            }        
        }
        return $this;
    }

    /**
     *
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return Model
     */
    public function setProperty($name,$value) {
        if(property_exists($this,$name))
            $this->$name = $value;
        return $this;
    }

}
