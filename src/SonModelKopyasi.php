<?php
namespace Erdemozveren\Oquent;
use DB;
use Oquent\Record;
use Oquent\ID;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Oquent\Query;
use Carbon\Carbon;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Queue\QueueableEntity as QueueableEntity;

use Erdemozveren\Oquent\Concerns\HasAttributes;
use Erdemozveren\Oquent\Concerns\HidesAttributes;
use Erdemozveren\Oquent\Concerns\GuardsAttributes;
use Erdemozveren\Oquent\Concerns\HasTimestamps;
use Erdemozveren\Oquent\Concerns\HasEvents;
use Erdemozveren\Oquent\Concerns\HasGlobalScopes;

class Model extends GenericUser implements QueueableEntity
{
   use HasAttributes,
        HidesAttributes,
        HasTimestamps,
        HasEvents,
        HasGlobalScopes;
    /**
     * @Rid
     *
     * @var integer
     */
    protected $rid;
    /**
     * @Version
     *
     * @var integer
     */
    protected $version;
    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    protected $exists = false;
    /**
     * Key name
     */
    protected $keyName="@rid";
    /**
     * Vertex Class
     *
     * @var string
     */
    protected $table;
    /**
     * Extend Class
     *
     * @var string
     */
    protected $oExtends;
    /**
     * The number of models to return for pagination
     *
     * @var integer
     */
    protected $perPage = 15;
    /**
     * Current page
     *
     * @var integer
     */
    protected $currentPage = 1;
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';
    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';
    /**
     * Name of 
     *
     * @var array
     */
    protected $edgeIn=[];
    /**
     * Indicate is model booted
     *
     * @var boolean
     */
    protected $booted=false;
    /**
     * Indicates if the model was inserted during the current request lifecycle.
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    private $query;

    protected $connectionName='orientdb';

    public function __construct(array $_data=[]) {
        $this->bootModel($_data);
    }
    public function bootModel(array $_data=[]) {

        if(!empty($_data)) {
            $this->original=$_data;
            $this->attributes=$_data;
            $this->rid=$_data["@rid"];
            $this->version=$_data["@version"];
            $this->exists=true;
        }
        if(!$this->table) {
        $this->table=class_basename($this)."s";
        }
        $this->query=new Query;
        $this->booted=true;
    }
    public function getKeyName(){
        return $this->keyName;
    }
    public function getAuthIdentifierName() {
        return '@rid';
    }
    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return array
     */
    public function save(array $attributes=[],array $options=['touch'=>true]) {
        if($this->exists==true) {
            return $this->performUpdate($attributes,$options);
        }else {
            return $this->performInsert($attributes,$options);
        }
    }
    public function update(array $attributes=[],array $options=['touch'=>true]) {
            return $this->performUpdate($attributes,$options);
    }
    
    public function delete() 
    {
        if(!$this->exists) return false;
        return $this->newQuery()->delete();
    }
    
    public function performInsert(array $attributes=[],array $options=[]){
        if((empty($this->attributes) && empty($attributes))||$this->isExists()) return false;
        $this->bootIfNotBooted();
        if($options['touch']===true) {
            $this->updateTimestamps();
        }
        $query=new Query($this);
        $query->class($this->table);
        $attributes= array_merge($this->attributes, $attributes);
        return $query->save($attributes);
    }

    public function performUpdate(array $attributes=[],array $options=[]) {
        if((empty($this->getChanges())&&empty($attributes))||!$this->isExists()) return false;
        $this->bootIfNotBooted();
        if($options['touch']===true) {
            $this->updateTimestamps();
        }
        $query=new Query($this);
        $query->class($this->getRid());
        $this->syncChanges();
        $attributes= array_merge($this->getChanges(), $attributes);
        return $query->update($attributes);
    }

    public static function find($rid) {
    if(empty($rid))  return null;
    $rid.="";
    if($rid[0]!="#") $rid="#".$rid;
    $f=(new static)->newQuery()->class($rid)->first();
    if($f) return $f;
    return new static();
    }
    public static function all() {
    $f=(new static)->newQuery()->get();
    return $f;
    }
     /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        /*if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }*/
        return $this->newQuery()->$method(...$parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
    public function newQuery() {
        return (new Query($this))->from($this->table)->class($this->table);
    }

    public function isExists() {
        return $this->exists;
    }
    public function getRid() {
        return $this->rid;
    }
    public function getVersion() {
        return $this->version;
    }

    public function bootIfNotBooted(){
        if($this->booted===false){
        $this->bootModel();
        }
    }
    public static function getLabel() {

    }

    public function __get( $key )
    {
        return $this->getAttribute($key);
    }

    public function __set( $key, $value )
    {
        $this->setAttribute($key,$value);
    }
    /**
     * Set Attribute
     *
     * @param string $key
     * @param [type] $value
     * @return void
     */
    public function setAttribute($key,$value) {
        if(method_exists($this,'set'.studly_case($key).'Attribute')) {
            $this->{'set'.studly_case($key).'Attribute'}($value);
        }else {
        if(is_object($value)) {
            $value=(string)$value;
        }
        if($key=="rid") {
            $this->rid=$value;
            $key="@".$key;
            $this->exists=true;
        }else {
            $this->attributes[$key]=$value;
        }
        }
    }
    public function fillAttributes($_data) {
        if(is_array($this->attributes) && !$this->isUnguarded()) {
            foreach($this->attributes as $key=>$value) {
                foreach($this->fillable as $fkey=>$fval) {
                    if($fkey!==$key) throw new MassAssignmentException();
                }
            }
        }
        $this->attributes=$_data;
    }
    /**
     * Get Attribute
     *
     * @param string $key
     * @return mix
     *//*
    public function getAttribute($key) {
        if(method_exists($this,'get'.studly_case($key).'Attribute')) {
        return $this->{'get'.studly_case($key).'Attribute'}($value);
        }
        return isset($this->attributes[$key]) ? $this->attributes[$key] : $this->$key;
    }
    public function getAttributes($showhiddens=false) {
        if($showhiddens==false) {
            if(count($this->hidden)!=0) {
                $return=$this->attributes;
                foreach($return as $key=>$value) {
                    foreach($this->hidden as $hkey) {
                        if($hkey==$key) unset($return[$key]);
                    }
                }
            return $return;
            }
            return $this->attributes;
        }
        return $this->attributes;
    }*/
    public function __toString(){
        return json_encode($this->getArrayableAttributes());
    }
    /**
     * When a model is being unserialized, check if it needs to be booted.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->bootIfNotBooted();
    }
    public function getConnectionName(){
        return $this->connectionName;
    }
    protected function getDateFormat()
    {
        return $this->dateFormat;
    }
    // Queueable Implemention
    /**
     * Get the queueable identity for the entity.
     *
     * @return mixed
     */
    public function getQueueableId()
    {
        return $this->rid;
    }

    /**
     * Get the queueable connection for the entity.
     *
     * @return mixed
     */
    public function getQueueableConnection()
    {
        return $this->getConnectionName();
    }
}