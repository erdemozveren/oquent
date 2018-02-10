<?php
namespace Erdemozveren\Oquent;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use PhpOrient\PhpOrient;
class Connection extends IlluminateConnection {    
     /**
     * The Orientdb Doctrine client connection
     */
    protected $orient;
    /**
     * Open Database Object
     *
     * @var [type]
     */
    protected $clusterMap;
    /**
     * The Orientdb database transaction
     *
     * @var
     */
    protected $transaction;
    /**
     * Default connection configuration parameters
     *
     * @var array
     */
    protected $defaults = array(
        'host' => 'localhost',
        'port' => 2424,
        'database' => 'demo',
        'username' => null,
        'password' => null
    );
    /**
     * The driver name
     *
     * @var string
     */
    protected $driverName = 'orientdb';
    /**
     * Create a new database connection instance
     *
     * @param array $config The database connection configuration
     */
    public function __construct(array $config = array()) {
        $this->config = $config;
        // activate and set the database client connection
        $this->orient = $this->createConnection();
    }
    /**
     * Create a new Orientdb client
     *
     * @return
     */
    public function createConnection() {
        // below code is used to create connection usinf Orientdb
        $client = new PhpOrient($this->config['host'],$this->config['port']);
        $client->username = $this->config['username'];
        $client->password = $this->config['password'];
        if(Cache::has('odb_session_token')) {
            $client->setSessionToken(Cache::get('odb_session_token'));

        }else {
            $client->setSessionToken(true);
            Cache::forever('odb_session_token',$client->getSessionToken());
        }
        if($client->connect()){
        $this->clusterMap= $client->dbOpen( $this->config['database'], $this->config['username'], $this->config['password']);
        }
         return $client;
    }
    
    public function make(array $config, $name = null)
	{
        $this->createConnection();
        return $this;
	}
    /**
     * Get the currenty active database client
     *
     * @return
     */
    public function getClient() {
        return $this->orient;
    }
    /**
     * Set the client responsible for the
     * database communication
     *
     * @param PhpOrient $client
     */
    public function setClient(PhpOrient $client) {
        $this->orient = $client;
    }
    /**
     * Get the connection host
     *
     * @return string
     */
    public function getHost() {
        return $this->getConfig('host', $this->defaults['host']);
    }
    /**
     * Get the connection port
     *
     * @return int|string
     */
    public function getPort() {
        return $this->getConfig('port', $this->defaults['port']);
    }
    /**
     * Get the connection username
     * @return int|string
     */
    public function getUsername() {
        return $this->getConfig('username', $this->defaults['username']);
    }
    /**
     * Get the connection password
     * @return int|strings
     */
    public function getPassword() {
        return $this->getConfig('password', $this->defaults['password']);
    }
    
    /**
     * Get the database name
     * @return strings
     */
    public function getDatabase() {
        return $this->getConfig('database', $this->defaults['database']);
    }
    /**
     * Get the  driver name.
     *
     * @return string
     */
    public function getDriverName() {
        return $this->driverName;
    }
    public function table($name){
        return "Şelam";
    }
    public function class($name) {
        return $this->table($name);
    }
}