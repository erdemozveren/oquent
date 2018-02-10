<?php
namespace Erdemozveren\Oquent\Query;
use DB;
use Oquent\Record;
use Oquent\ID;
use PhpOrient\PhpOrient;
class Update {
     /**
     * The database connection instance.
     *
     * @var PhpOrient
     */
    protected $connection;

    /**
     * Raw Query String
     *
     * @var string
     */
    protected $rawQuery;
    /**
     * The Class|Record to update.
     *
     * @var string
     */
    protected $class;
    /**
     * Data in Array Form
     *
     * @var array
     */
    protected $data=[];
    /**
     * Add
     *
     * @var array
     */
    protected $data_add=[];
    /**
     * Add
     *
     * @var array
     */
    protected $data_remove=[];
    /**
     * Data in Array Form
     *
     * @var array
     */
    protected $data_increment=[];
    /**
     * Defines an expression to return instead of the number of inserted records
     *
     * @var string
     */
    protected $return;

    /**
     * Where clause.
     *
     * @var array
     */
    protected $where;
    /**
     * Lock lock the record between the load and update
     *
     * @var string
     */
    protected $lock;
    /**
     * Maximum record size to update
     *
     * @var int
     */
    protected $limit;
    /**
     * Update Type
     *
     * @var string
     */
    protected $updateType="MERGE";

    public function class($to){
        if(empty($to)) return false;
        $this->class=$to;
        return $this;
    }

    public function content($data) {
        if(empty($data)) return false;
        $this->updateType="CONTENT";
        $this->data=$data;
        return $this;
    }
    public function merge($data) {
        if(empty($data)) return false;
        $this->updateType="MERGE";
        $this->data=$data;
        return $this;
    }

    public function increment($column,$num=1) {
        if(empty($column)||!is_numeric($num)) return false;
        $this->data_increment[]=$column."=".$num;
        return $this;
    }
    
    public function add($column,$value="") {
        if(empty($column)) return false;
        $this->data_add[]=$column." = ".$value;
        return $this;
    }
    public function put($column,$value="") {
        if(empty($column)) return false;
        $this->data_put[]=$column." = ".$value;
        return $this;
    }

    public function remove($column,$value=null) {
        if(empty($column)) return false;
        $this->data_remove[]=$column.($value==null ? " = ".$value : "");
        return $this;
    }

    
    public function return($expression) {
        if(empty($expression)) return false;
        $this->return=$expression;
    }
     /**
     * Where Clasue
     *
     * @param string|Query $columns
     * @param string $operator
     * @param string $value
     * @param string $type
     * @return $this
     */
    public function where($columns,$operator,$value=null,$type='AND') {
    if($columns instanceof Query) {
    $this->where[]=$this->QueryFromObject($columns);
    return $this;
    }
    if(func_num_args() == 2) {
    $value=$operator;
    $operator="=";
    }
    if(is_string($value)) $value="'".$value."'";
    if(!empty($this->where)) {
        $q=$type." ".$columns." ".$operator." ".$value;
    }else {
        $q=$columns." ".$operator." ".$value;
    }
    $this->where[]=$q;
    return $this;
    }
    public function orWhere($column, $operator = null, $value = null){
        if(empty($column)) return false; 
        if(func_num_args() == 2) {
        $value=$operator;
        $operator="=";
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    public function __get( $key )
    {
        return $this->data[$key];
    }

    public function __set( $key, $value )
    {
        $this->data[ $key ] = $value;
    }

    public function toQuery() {
        $this->rawQuery="";
        if(empty($this->class)) return false;
        $this->rawQuery.="UPDATE ".$this->class;
        if(!empty($this->data_add)) {
            $this->rawQuery.=" ADD ".implode(",",$this->data_add);
        }
        if(!empty($this->data_increment)) {
            $this->rawQuery.=" INCREMENT ".implode(",",$this->data_increment);
        }
        if(!empty($this->data_remove)) {
            $this->rawQuery.=" REMOVE ".implode(",",$this->data_remove);
        }
        if(!empty($this->data_put)) {
            $this->rawQuery.=" PUT ".implode(",",$this->data_put);
        }
        if(!empty($this->data)) {
            $this->rawQuery.=" ".$this->updateType." ".json_encode($this->data);
        }
        if(!empty($this->return)) {
            $this->rawQuery.=" ".$this->return;
        }
        if(is_numeric($this->limit)){
            $this->rawQuery.=" LIMIT ".$this->limit;
        }
        if(!empty($this->where)) {
            $this->rawQuery.=" WHERE ".implode(" ",$this->where);
        }
    return $this->rawQuery;
    }
    public function getConnection() {
        return DB::connection('orientdb')->getClient();
    }
    public function run() {
        if($this->toQuery()) {return false;}
        return $this->getConnection()->command($this->rawQuery);
    }
    public function save() {
        return $this->run();
    }
}