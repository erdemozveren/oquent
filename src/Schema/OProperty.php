<?php
namespace Erdemozveren\Oquent\Schema;
use DB;
use PhpOrient\PhpOrient;
class OProperty {
     /**
     * The database connection instance.
     *
     * @var PhpOrient
     */
    public $connection;

    /**
     * Raw Query String
     *
     * @var string
     */
    public $rawQuery;
    /**
     * Property Name.
     *
     * @var string
     */
    protected $name;
    /**
     * Class Name.
     *
     * @var string
     */
    protected $class;
    /**
     * If Not Exists
     *
     * @var bool
     */
    protected $ine=true;
    /**
     * Defines the super-class you want to extend with this class 
     *
     * @var string
     */
    protected $type;
    /**
     * list of the data types for standard properties
     *
     * @var array
     */
    protected $typeList=['BOOLEAN','INTEGER','DOUBLE','SHORT','LONG','FLOAT','DATE','STRING','BINARY','DATETIME','LINK','EMBEDDED','BYTE','DECIMAL','LINKBAG'];
    /**
     * Link Type
     *
     * @var string
     */
    protected $linktype;
    /**
     * Link Type
     *
     * @var array
     */
    protected $linktypeList=['EMBEDDEDLIST','LINKLIST','EMBEDDEDSET','LINKSET','EMBEDDEDMAP','LINKMAP'];
    /**
     * Defines whether the class is abstract. For abstract classes, you cannot create instances of the class.
     *
     * @var boolean
     */
    protected $abstract=false;

    function __set($key,$value) {
        switch($key) {
            case "name":
            if(is_string($value)) {$this->name=$value;}
            break;
            case "ine":
            if(is_bool($value)) {$this->ine=$value;}
            break;
            case "extend":if(is_string($value)) {$this->extend=$value;}
            break;
            case "clusterIds":
            if(is_array($value)) {$this->clusterIds=$value;}
            break;
            case "totalCluster":
            if(is_numeric($value)) {$this->totalCluster=$value;}
            break;
            default:break;
        }
    }
    public function toQuery() {
        if(empty($this->name)) return false;
        $this->rawQuery="CREATE CLASS ".$this->name;
        if($this->ine===true) {
            $this->rawQuery.=" IF NOT EXISTS";
        }
        if(!empty($this->extend)) {
            $this->rawQuery.=" EXTENDS ".$this->extend;
        }
        if(count($this->clusterIds)!=0) {
            $this->rawQuery.=" CLUSTER ".implode(",",$this->extend);
        }
        if(is_numeric($this->totalCluster)) {
            $this->rawQuery.=" CLUSTERS ".$this->totalCluster;
        }
        if($this->abstract===true) {
            $this->rawQuery.=" ABSTRACT";
        }
        return $this->rawQuery;        
    }
    public function getConnection() {
        return DB::connection('orientdb')->getClient();
    }
    public function create() {
        if($this->toQuery()===false) return false;
        return $this->getConnection()->command($this->rawQuery);
    }
}