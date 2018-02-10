<?php
namespace Erdemozveren\Oquent\Query;
use DB;
use Oquent\Record;
use Oquent\ID;
use PhpOrient\PhpOrient;
class Select {
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
     * maximum time in milliseconds for the query
     *
     * @var int
     */
    protected $timeout;
    
    /**
     * The columns that should be returned.
     *
     * @var array
     */
    protected $columns;
    
    /**
     * The columns that should be returned on traverse
     *
     * @var array
     */
    protected $tColumns;

    /**
     * The Table|Class|Record which the query is targeting.
     *
     * @var array
     */
    protected $from;

    /**
     * Let Block
     *
     * @var array
     */

    protected $let;
    /**
     * Where clause.
     *
     * @var array
     */
    protected $where;

      /**
     * The groupings for the query.
     *
     * @var array
     */
    protected $groups;

    /**
     * The having constraints for the query.
     *
     * @var array
     */
    protected $havings;

    /**
     * The orderings for the query.
     *
     * @var array
     */
    protected $orders;

    /**
     * The unwind column.
     *
     * @var string
     */
    protected $unwind;

    /**
     * The maximum number of records to return.
     *
     * @var int
     */
    protected $takeLimit=-1;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    protected $offset;

    /**
     * Defines how you want it to fetch results.
     *
     * @var int
     */
    protected $fetchPlan="*:0";
    /**
     * Define Locking Strategy [DEFAULT\RECORD]
     * 
     * @var string
     */
    protected $lock;
    /**
     * Avoid using the cache
     *
     * @var boolean
     */
    protected $nocache=false;
    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select($columns = [''])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }
    public function count(){
        $this->columns=['count(*)'];
        return $this->first()['count'];
    }
    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function traverse($columns = [''])
    {
        $this->tColumns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }
    public function QueryFromObject($query){
    if($query instanceof Select) {
    return "(".$query->toQuery().")";
    }
    return $query;
    }
    /**
    * Set the Table|Class|Record which the query is targeting.
    *
    * @param  string  $target
    * @return $this
    */
    public function from($target)
    {
        $this->from[]=$this->QueryFromObject($target);
        return $this;
    }
    /**
     * LET Block
     *
     * @param string $key
     * @param string|Select $val
     * @return $this
     */
    public function let($key,$val) {
        $this->let[]="$".$key."=".$val;
        return $this;
    }
    /**
     * Where Clasue
     *
     * @param string|Select $columns
     * @param string $operator
     * @param string $value
     * @param string $type
     * @return $this
     */
    public function where($columns,$operator,$value=null,$type='AND') {
    if($columns instanceof Select) {
    $this->where[]=$this->QueryFromObject($columns);
    return $this;
    }
    if(empty($columns)) return "";
    if(func_num_args() == 2||$value===null) {
    $value=$operator;
    $operator="=";
    }
    if(is_string($value)&&!preg_match("/\#\d+:\d+/",$value)) $value="'".addslashes($value)."'";
    if(!empty($this->where)) {
        $this->where[]=$type." ".$columns." ".$operator." ".$value;
    }else {
        $this->where[]=$columns." ".$operator." ".$value;
    }
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
    public function whereLike($column,$value,$type='AND') {
    return $this->where($column,"LIKE",$value,$type);
    }
    public function whereNotLike($column,$value,$type='AND') {
    return $this->where($column,"NOT LIKE",$value,$type);
    }
    /*public function whereIsNull($column) {
        if(empty($column)) return false;
        $this->where[]
        return $this->where($column,"IS NULL","");
    }*/
    public function groupBy($columns) {
    if(empty($columns)) return false; 
    $this->groups[]=$columns;
    return $this;
    }
    public function orderBy($column,$type="ASC") {
    if(empty($column)) return false;
    $this->orders[]=$column." ".$type;
    return $this;
    }
    public function unwind($column) {
    if(empty($column)) return false;
    $this->unwind=$column;
    return $this;
    }
    public function fetchplan($strategy) {
    if(empty($strategy)) return false;
    $this->fetchPlan=$strategy;
    return $this;
    }
    public function skip($int) {
    if(!is_numeric($int)) return false;
    $this->offset=$int;
    return $this;
    }
    public function limit($int) {
    if(!is_numeric($int)) return false;
    $this->takeLimit=$int;
    return $this;
    }
    public function nocache($nocache=true){
    if(empty($nocache)) return false;
    $this->nocache=$nocache;
    return $this;
    }
    public function toQuery() {
    $this->rawQuery="";
    if(!empty($this->tColumns)) {
       $this->rawQuery.="TRAVERSE ".implode(" ",$this->tColumns);
    }else {
       $this->rawQuery.="SELECT ".(empty($this->columns) ? '' : implode(" ",$this->columns));
    }
    if(!empty($this->from)) { 
    $this->rawQuery.=" FROM ".implode(" FROM ",$this->from);
    }
    if(!empty($this->where)) {
        $this->rawQuery.=" WHERE ".implode(" ",$this->where);
    }
    if(!empty($this->let)) {
        $this->rawQuery.=" LET ".implode(",",$this->let);
    }
    if(!empty($this->groups)) {
        $this->rawQuery.=" GROUP BY ".implode(",",$this->where);
    }
    if(!empty($this->orders)) {
        $this->rawQuery.=" ORDER BY ".implode(",",$this->orders);
    }
    if($this->unwind) {
        $this->rawQuery.=" UNWIND ".$this->unwind;
    }
    if(is_numeric($this->offset)) {
        $this->rawQuery.=" SKIP ".$this->offset;
    }
    if($this->lock==true) {
        $this->rawQuery.=" LOCK ".$this->lock;
    }
    if($this->nocache===true) {
        $this->rawQuery.=" NOCACHE";
    }
    return $this->rawQuery;
    }
    public function getConnection() {
        return DB::connection('orientdb')->getClient();
    }
    public function rawRun() {
        if(empty($this->toQuery())) {return false;}
        return $this->getConnection()->query($this->rawQuery,$this->takeLimit);
    }
    public function run() {
        if(empty($this->toQuery())) {return false;}
        return $this->getConnection()->query("SELECT @this.toJSON('rid,version,fetchPlan:$this->fetchPlan') FROM (".$this->rawQuery.")",$this->takeLimit);
    }
    public function get() {
        $res= $this->run();
        foreach($res as $id=>$data) {
        $res[$id]=json_decode($data->getOData()['this'],true);
        }
        return $res;
    }
    public function first() {
        $this->takeLimit=1;
        $res=$this->get();
        return empty($res[0]) ? [] : $res[0];
    }
}