<?php
namespace Erdemozveren\Oquent;
use DB;
use Oquent\Record;
use Oquent\ID;
use Oquent\QueryException;
use Oquent\Model;
use PhpOrient\PhpOrient;
use Illuminate\Pagination\LengthAwarePaginator;
class Query {
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
    protected $columns=[];
    
    /**
     * The Table|Class|Record which the query is from.
     *
     * @var array
     */
    protected $from=[];

    /**
     * The Table|Class|Record which the query is targeting.
     *
     * @var string
     */
    protected $class;

    /**
     * Data in Array Form
     *
     * @var array
     */
    protected $data=[
        'content'=>[],
        'merge'=>[],
        'add'=>[],
        'increment'=>[],
        'put'=>[],
        'set'=>[],
        'remove'=>[],
    ];

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
     * Update or Insert
     *
     * @var bool
     */
    protected $upsert=false;

    /**
     * The maximum number of records to return.
     *
     * @var int
     */
    protected $take;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    protected $offset;
    /**
     * Return 
     *
     * @var string
     */
    protected $return;
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
     * Batch (Chunk) Size
     * 
     * @var int
     */
    protected $batchSize;
    /**
     * Operators
     *
     * @param  array  $Boperators
     * @param  array  $Aoperators
     */
    protected $Boperators=[
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>','&', '|', '^', '<<', '>>'
    ];
    protected $Aoperators=[
        'BETWEEN','IN','LIKE','NOT LIKE','IS NULL','IS NOT NULL','INSTANCEOF','MATCHES'
    ];
    /**
     * Model/Edge Object for reference
     *
     * @var [type]
     */
    protected $object;

    /**
     * Indicate query type (CRUD)
     *
     * @var string
     */
    protected $queryType="select";


    public function __construct($fromObject=null) {
        if(is_object($fromObject)) {
            $this->object=$fromObject;
        }
    }

    public function getObject() {
        return $this->object ? $this->object : null;
    }
    public function setModel($object) {
        $this->object=$object;
        return $this;
    }
    public function select($columns = [''])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    public function addSelect($column) {
        $this->columns[]=$column;
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
   /*public function traverse($columns = [''])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }*/

    public function content($data) {
        if(!is_array($data)) throw new QueryException("Wrong Paramater Type on ".__FUNCTION__);
        $this->data["content"]=$data;
        $this->data["merge"]=[];
        return $this;
    }
    public function merge($data) {
        if(!is_array($data)) throw new QueryException("Wrong Paramater Type on ".__FUNCTION__);
        $this->data["merge"]=$data;
        $this->data["content"]=[];
        return $this;
    }
    public function increment($column,$value=1) {
        if(empty($column)||!is_numeric($value)) return false;
        $this->data["increment"][]=compact('column','value');
        return $this;
    }
    
    public function add($column,$value="") {
        if(empty($column)) return false;
        $value=$this->escapeInput($value);
        $this->data["add"][]=compact('column','value');
        return $this;
    }
    public function put($column,$value="") {
        if(empty($column)) return false;
        $value=$this->escapeInput($value);
        $this->data["put"][]=compact('column','value');
        return $this;
    }
    public function set($column,$value="") {
        if(empty($column)) return false;
        $value=$this->escapeInput($value);
        $this->data["set"][]=compact('column','value');
        return $this;
    }

    public function remove($column,$value=null) {
        if(empty($column)) return false;
        $value=$this->escapeInput($value);
        $this->data["remove"][]=compact('column','value');
        return $this;
    }
    public function QueryFromSelect($query,$brackets=true){
    if(empty($query)) return "";
    if($query instanceof Query) {
    if($brackets==true) {
    return "(".$query->toSql(false).")";
    }
    return $query->toSql(false);
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
        $this->from[]=$this->QueryFromSelect($target);
        return $this;
    }
    public function class($target) {
        $this->class=$this->QueryFromSelect($target);
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
    public function checkId($value) {
        return preg_match("/\#?-?\d+:-?\d+/",$value) ? true : false;
    }
    public function escapeInput($value) {
        if(is_string($value)&&!$this->checkId($value)) return "'".addslashes($value)."'";
        if(is_null($value)) return 'NULL';
        return $value;

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
    public function where($column,$operator=null,$value=null,$bool='AND') {
    if($column instanceof Query) {
    $this->where[]=["bool"=>is_null($operator) ? 'AND':'OR',"where"=>$column->getWheres()];
    return $this;
    }else if(is_callable($column)){
    //$this->where[]=$column(new static)->toSql();
    $subQuery=new static;
    $column($subQuery);
    $this->where[]=$subQuery->toSql(false);
    return $this;
    }else if(is_callable($operator)&&!empty($column)) {
    $subQuery=new static;
    $operator($subQuery);
    $this->where[]=["bool"=>is_null($operator) ? 'AND':'OR',"where"=>$subQuery->getWheres()];
    return $this;
    }
    if(empty($column)||(empty($operator)&&!is_null($operator))) return "";
    $type="basic";
    if(in_array(strtoupper($operator),$this->Aoperators)){
    $type=$operator;
    if(is_array($value)) {
    $value=array_map([$this,'escapeInput'],$value);
    if(stripos($operator,"between")!==false) {
        if(count($value)==2) {
        $value=$value[0]." AND ".$value[1];
        }else {
            throw new QueryException("Unvalid operator using on ".__FUNCTION__);
        }
    }else if(stripos($operator,"in")!==false){
        $value="[".implode(",",$value)."]";
    }else{
        throw new QueryException("Unvalid operator using on ".__FUNCTION__);
    }
    }
    }else {
    if(func_num_args() == 2&&$value===null) {
    $value=$this->escapeInput($operator);
    $operator="=";
    }else {
    $value=$this->escapeInput($value);
    }
    }
    $operator=strtoupper($operator);
    $this->where[]=compact('type','column','operator','value','bool');
    return $this;
    }

    public function orWhere($column,$operator=null,$value=null){
        if(func_num_args()==2){
            $value=$operator;
            $operator="=";
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    public function getWheres(){
        return $this->where;
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
    public function return($expression) {
        $expression=strtoupper($expression);
        if($expression=="BEFORE"||$expression=="AFTER"){
        $this->return=$expression." @this.toJSON('rid,version')";
        }else if($expression==="COUNT"){
        $this->return=$expression;
        }else {
            throw new Queryexception("Unvalid Paramater Use on ".__FUNCTION__);
        }
        return $this;
    }
    public function orderBy($column,$type="ASC") {
    if(empty($column)) return false;
    $this->orders[]=$column." ".$type;
    return $this;
    }
    public function timeout($int) {
        if(is_numeric($int)&&$int>0) {
        $this->timeout=$int;
        }
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
    public function take($int) {
    if(!is_numeric($int)) return false;
    $this->take=$int;
    return $this;
    }
    public function lock() {
    $this->lockType="RECORD";
    }
    public function noLock() {
    $this->lockType="DEFAULT";
    }
    public function nocache($nocache=true){
    if(empty($nocache)) return false;
    $this->nocache=$nocache;
    return $this;
    }
    public function fromCamelCase($str){
        return explode("_",snake_case($str));
    }
    public function dataToObject($data,$class=null) {
        if(empty($data)) return null;
        if(empty($class)&&!empty($this->object)) {$class=$this->object;}
        $res=[];
        foreach($data as $key=>$record) {
            $res[$key]= new $class(json_decode($record->getOData()["this"],true));
        }
        return $res;
    }
    public function __call($method,$args) {
        if(substr($method,0,5)=="where"||substr($method,0,7)=="orWhere") {
            $isOr=substr($method,0,2)=="or";
            $method=$this->fromCamelCase($method);
            unset($method[0]);
            if($method[1]=="where") {
                unset($method[1]);
            }
            $method=strtoupper(implode(" ",$method));
            if(in_array($method,$this->Aoperators)) {
                    $param[0]=$args[0];
                    $param[1]=$method;
                    $param[2]=isset($args[1]) ? $args[1] : null;
                    $param[3]=$isOr==true ? 'OR':'AND';
               return $this->where(...$param);
            }
        }
        throw new QueryException("Unknown method :".$method);
    }
    public static function __callStatic($method,$args) {
        return (new static)->{$method}(...$args);
    }
    public function createEdge($from,$to) {

    }
    public function compileSelect() {
        if($this->queryType==="select") {
            return rtrim("SELECT ".implode(" ",$this->columns));
         }
         return "";
    }
    public function compileData() {
        $return="";
        if(is_array($this->data)){
            foreach($this->data as $key=>$inData) {
                if($key=="content"||$key=="merge") continue;
                $putKey=true;
                foreach($inData as $data)
                {
                if(!empty($return)) $return.=" "; 
                $return.=($putKey==true ? $key:",")." ".$data["column"]." = ".$data["value"];
                $putKey=false;
                }
            }
            if(!empty($this->data["merge"])){
                $return.="MERGE ".json_encode($this->data["merge"]);
            }else if(!empty($this->data["content"])) {
                $return.="CONTENT ".json_encode($this->data["content"]);
            }
            return $return;
        }
        throw new Queryexception("Unvalid Data Type on ".__FUNCTION__);
    }
    public function compileUpdate() {
        if($this->queryType==="update"&&!empty($this->class)) {
            return "UPDATE ".$this->class;
         }
         return "";
    }
    public function compileLock() {
        if($this->queryType!="select"&&!empty($this->lock)) {
            return "LOCK ".$this->lock;
        }
        return "";
    }
    public function compileDelete(){
        if($this->queryType==="delete") {
            if(!empty($this->class)) {
            if(empty($where)&&!empty($this->object)) {
                return "DELETE VERTEX ".$this->object->getRid();
            }
            return "DELETE VERTEX ".$this->class;
            }else if(!empty($this->from[0])){
            return "DELETE VERTEX ".$this->from[0];
            }
        }
        return "";
    }
    public function compileInsert() {
        if($this->queryType==="insert") {
            return "INSERT INTO ".$this->class;
         }
         return "";
    }
    public function compileReturn(){
        return $this->return ? "RETURN ".$this->return : "";
    }
    public function compipleTraverse() {
        if($this->queryType==="traverse"&&!empty($this->columns)) {
            return rtrim("TRAVERSE ".implode(" ",$this->columns));
         }
         return "TRAVERSE";
    }
    public function compileFrom() {
        if($this->checkId($this->class)) {
            return "FROM ".$this->class;
        }else if(!empty($this->from)) {
            return rtrim("FROM ".implode(" FROM ",$this->from));
        }
        return "";
    }
    public function compileWhere($subWhere=false) {
        if(!empty($this->where)||is_array($subWhere)) {
            $retWhere="";
            $wheres=(is_array($subWhere)) ? $subWhere : $this->where;

            foreach ($wheres as $clause) {
            if(is_string($clause)) {$retWhere.="(".$clause.")";continue;}
            if(isset($clause["where"])) {$retWhere.=" ".$clause["bool"]." (".$this->compileWhere($clause["where"]).")";continue;}
//            if(is_array($clause)){$retWhere.=" ".$this->compileWhere($clause);continue;}
            if(!empty($retWhere)) {$retWhere.=" ".$clause["bool"];}
  
            $retWhere.=" ".$clause["column"]." ".$clause["operator"]." ".$clause["value"];
            }
        if($subWhere==true){
        return trim($retWhere);
        }else {
        return "WHERE ".trim($retWhere);
        }
        }
        return "";
    }
    public function compileSkip() {
        return is_numeric($this->offset) ? "SKIP ".$this->offset : "";
    }
    public function compileLimit() {
        return is_numeric($this->take) ? "LIMIT ".$this->take : "";
    }
    public function compileTimeout() {
        return is_numeric($this->timeout) ? "TIMEOUT ".$this->timeout : "";
    }
    public function toSql($selectJson=true) {
    $this->rawQuery="";
    if($this->queryType=="select") {
    $this->rawQuery.=$this->compileSelect();
    $this->rawQuery.=rtrim(" ".$this->compileFrom());
    $this->rawQuery.=rtrim(" ".$this->compileWhere());
    $this->rawQuery.=rtrim(" ".$this->compileSkip());
    $this->rawQuery.=rtrim(" ".$this->compileLimit());
  
    if($selectJson==true){
    $this->rawQuery="SELECT @this.toJSON('rid,version,fetchPlan:".$this->fetchPlan."') FROM (".$this->rawQuery.")";
    }
    }else if($this->queryType=="update") {
    $this->rawQuery.=$this->compileUpdate();
    $this->rawQuery.=rtrim(" ".$this->compileData());
    $this->rawQuery.=rtrim(" ".$this->compileReturn());
    $this->rawQuery.=rtrim(" ".$this->compileWhere());
    $this->rawQuery.=rtrim(" ".$this->compileLimit());
    } else if($this->queryType=="insert") {
    $this->rawQuery.=$this->compileInsert();
    $this->rawQuery.=rtrim(" ".$this->compileData());
    $this->rawQuery.=rtrim(" ".$this->compileReturn());
    $this->rawQuery.=rtrim(" ".$this->compileFrom());
    }else  if($this->queryType=="delete") {
        $this->rawQuery.=$this->compileDelete();
        $this->rawQuery.=rtrim(" ".$this->compileLock());
        $this->rawQuery.=rtrim(" ".$this->compileReturn());
        $this->rawQuery.=rtrim(" ".$this->compileWhere());
        $this->rawQuery.=rtrim(" ".$this->compileLimit());
        $this->rawQuery.=rtrim(" ".$this->compileTimeout());
    }
/*
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
    }*/
    $this->rawQuery=trim($this->rawQuery);
    return $this->rawQuery;
    }
    public function getConnection() {
        return DB::connection('orientdb')->getClient();
    }
    public function execute($selectJson=true) {
        if(empty($this->toSql($selectJson))) {return false;}
        if($this->queryType=="select") {
        return $this->getConnection()->query($this->rawQuery);
        }else {
        return $this->getConnection()->command($this->rawQuery);
        }
    }
    public function get() {
        $this->queryType="select";
        $res= $this->execute();
        if($this->object) {
            $res=$this->dataToObject($res);
        }else {
        foreach($res as $id=>$data) {
        $res[$id]=json_decode($data->getOData()['this'],true);
        }
        }
        return (empty($res) ? null:$res);
    }
    public function first() {
        $this->take=1;
        $res=$this->get();
        return (empty($res[0]) ? null : $res[0]);
    }
    public function update(array $data=[],$merge=true) {
        if(!is_bool($merge)) throw new QueryException("Wrong Paramater Type on ".__FUNCTION__);
        if(!empty($data)){
            if($merge==true) {
                $this->merge($data);
            }else {
                $this->content($data);
            }
        }else if(!empty($this->data)){
            if($merge==true) {
                $this->merge($this->data);
            }else {
                $this->content($this->data);
            } 
        }
        $this->queryType="update";
        if(empty($this->return)){
            $this->return("after");
        }
        $result=$this->execute();
        $modelClass=$this->object;
        if($result instanceof \PhpOrient\Protocols\Binary\Data\Record) {
        $modelClass=new $modelClass(json_decode($result->getOData()["value"],true));
        return $modelClass;
        }else if(is_array($result)&&count($result)!=0) {
            if($modelClass) {
            foreach($result as $key=>$record) {$result[$key]= new $modelClass(json_decode($record->getOData()["value"],true));}
            }else {
            foreach($result as $key=>$record) {$result[$key]= json_decode($record->getOData()["value"],true);}
            }
            return $result;
        }else if(!empty($result["result"])) {
            return (int)$result["result"];
        }
        return 0;
        //if(!empty($result["result"]) ? $result->getOData()["result"] : ( !empty($result->getOData()["value"]) ? json_decode($result->getOData()["value"],true) :0);
    }
    public function save(array $data=[]) {
        if(!empty($data)){
            $this->content($data);

        }else if(!empty($this->data["content"])) {
            $this->content($this->data["content"]);
        }
        $this->queryType="insert";
        $modelClass=$this->object;
        $result=$this->execute();
        if($result instanceof \PhpOrient\Protocols\Binary\Data\Record) {
            $result=$result->jsonSerialize();
            $data["@rid"]=(string)$result["rid"];
            $data["@version"]=$result["version"];
            $data= array_merge($data,$result["oData"]);
            if($modelClass) {
            $modelClass= new $modelClass($data);
            $modelClass->wasRecentlyCreated=true;
            return $modelClass;
            }else {
            return $data;
            }
            }else if(is_array($result)&&count($result)!=0) {
                if($modelClass) {
                foreach($result as $key=>$record) {$result[$key]= new $modelClass($record->getOData());$result[$key]->wasRecentlyCreated=true;}
                }else {
                foreach($result as $key=>$record) {$result[$key]= $record->getOData();}
                }
                return $result;
            }
        return $result;
    }

    public function delete() {
//        $this->class="#12:3046";
        $this->queryType="delete";
        if(!empty($this->class)||!empty($this->from)) {
                return (int)$this->execute()->getOData()["result"]; 
        }
        return false;
    }
    public function getCountForPagination(){
        $count = clone $this;
        return $count->select("count(*)")->execute(false)[0]->getOData()["count"];
    }
    public function forPage($page=1,$perPage){
        if(empty($perPage)&&$this->object) $perPage=$this->object->getPerPage();
        if($this->checkId($page)) {
            $this->where("@rid",">","#".$page);
        }else {
        if(!is_numeric($page)||($page-1)<=0) $page=1;
        $this->skip(($page-1)*$perPage);
        }
        $this->take($perPage);
        return $this;
    }
    /**
     * Paginate the given query.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
       /* if($page==null) {
            $page = request($pageName,1);
        }*/
        $page = $page?:LengthAwarePaginator::resolveCurrentPage($pageName);
        $perPage = $perPage ?: $this->object->getPerPage();
        $results = ($total = $this->getCountForPagination())
                                    ? $this->forPage($page, $perPage)->select($columns)->setModel(null)->get()
                                    : $this->object->newCollection();
        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

}