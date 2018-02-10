<?php
namespace Erdemozveren\Oquent\Query;
use DB;
use Oquent\Record;
use Oquent\ID;
use PhpOrient\PhpOrient;
use Oquent\Query\Select as Select;
class Insert {
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
     * The Class|Table to insert.
     *
     * @var array
     */
    protected $oClass;
    /**
     * Data in Array Form
     *
     * @var array
     */
    protected $data=[];

    /**
     * Defines an expression to return instead of the number of inserted records
     *
     * @var string
     */
    protected $return;

    /**
     * From Query
     *
     * @var Select
     */
    protected $from;
    /**
     * set Class
     *
     * @param string $c
     * @return void
     */
    public function class(string $c){
        if(empty($c)) return false;
        $this->oClass=$c;
        return $this;
    }
    /**
     * Data to Insert
     *
     * @param array $data
     * @return $this
     */
    public function insert(array $data) {
        if(empty($data)) return false;
        $this->data=$data;
        return $this;
    }
    
    public function return($expression) {
        $this->return=$expression;
    }
    /**
     * Insert from Another Query
     *
     * @param string|Select $query
     * @return $this
     */
    public function fromQuery($query) {
        if(empty($query)||empty($this->data)) return false;
        $this->from=$query;
        return $this;
    }

    public function __get( $key )
    {
        return $this->data[$key];
    }

    public function __set( $key, $value )
    {
        $this->data[ $key ] = $value;
    }
    /**
     * Make Query
     *
     * @return string
     */
    public function toQuery() {
        $this->rawQuery="";
        if(empty($this->oClass)) return false;
        $this->rawQuery.="INSERT INTO ".$this->oClass;
        if(!empty($this->data)) {
            $this->rawQuery.=" CONTENT ".json_encode($this->data);
        }
        if(!empty($this->return)) {
            $this->rawQuery.=" RETURN ".$this->return;
        }
        if(!empty($this->from)) {
            if($this->from instanceof Select) {
                $this->rawQuery.=" FROM ".$this->from->toQuery();
            }else {
                $this->rawQuery.=" FROM ".$this->from;
            }
        }
    return $this->rawQuery;
    }
    /**
     * Get Connection
     *
     * @return PhpOrient
     */
    public function getConnection() {
        return DB::connection('orientdb')->getClient();
    }
    /**
     * Run Query
     *
     * @return array
     */
    public function run() {
        if($this->toQuery()===false) {return false;}
        $res=$this->getConnection()->command($this->rawQuery);
        if($res) {
        $res=$res->jsonSerialize();
        $res['oData']["@rid"]=$res['rid']->jsonSerialize();
        }
        return $res['oData'];
    }
    /**
     * Alias for run function
     */
    public function save() {
        return $this->run();
    }
}