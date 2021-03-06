<?php
namespace JayaCode\Framework\Core\Database;

use JayaCode\Framework\Core\Database\Connector\Connector;
use JayaCode\Framework\Core\Database\Connector\ConnectorMySql;
use JayaCode\Framework\Core\Database\Query\Grammar\Grammar;
use JayaCode\Framework\Core\Database\Query\Grammar\GrammarMySql;
use JayaCode\Framework\Core\Database\Query\Query;
use PDO;

/**
 * Class Database
 * @package JayaCode\Framework\Core\Database
 */
class Database
{
    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Grammar
     */
    protected $grammar;

    /**
     * @var array
     */
    protected $driver = [
        "mysql" => [
            "connector" => ConnectorMySql::class,
            "grammar" => GrammarMySql::class,
        ]
    ];

    /**
     * @var array
     */
    protected $config = [
        "driver" => "mysql",

        "host" => "",
        "username" => "",
        "password" => "",

        "dbname" => "",
        "options" => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    ];

    /**
     * @var string
     */
    protected $model = null;

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $options = isset($config["options"])?
            arr_merge_all($this->config["options"], $config["options"])
            :$this->config["options"];

        $this->config = arr_merge_all($this->config, $config);

        $this->config['options'] = $options;
        $this->initialize();

        $this->createConnection();
    }

    /**
     * @param $config
     * @return static
     */
    public static function create($config)
    {
        return new static($config);
    }

    /**
     *
     */
    protected function initialize()
    {
        $connectorClass = $this->driver[$this->config["driver"]]["connector"];
        $this->connector = new $connectorClass();

        $grammarClass = $this->driver[$this->config["driver"]]["grammar"];
        $this->grammar = new $grammarClass();

        $this->query = new Query();
    }

    /**
     * @return PDO
     */
    public function createConnection()
    {
        $this->pdo = $this->connector->connect($this->config);
        return $this->pdo;
    }

    /**
     * @param $query
     * @param null $params
     * @return $this
     */
    public function sql($query, $params = null)
    {
        $this->query = $this->query->sql($query, $params);
        return $this;
    }

    /**
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->query->setTable($table);
        return $this;
    }

    /**
     * @param null $columns
     * @return $this
     */
    public function select($columns = null)
    {
        $this->query->select($columns);
        return $this;
    }

    /**
     * @param array $columnsVal
     * @param bool $runExecute
     * @return $this|mixed
     */
    public function insert(array $columnsVal, $runExecute = false)
    {
        $this->query->insert($columnsVal);

        if ($runExecute) {
            $status = $this->execute(false);
            $this->clear();

            return $status;
        }

        return $this;
    }

    /**
     * @param array $columnsVal
     * @param null $primaryKey
     * @return mixed
     */
    public function update(array $columnsVal, $primaryKey = null)
    {
        if ($primaryKey) {
            $keyVal = $columnsVal[$primaryKey];
            $columnsVal = arr_exclude($columnsVal, [$primaryKey]);

            $this->query->update($columnsVal)->where($primaryKey, $keyVal);

            $status = $this->execute(false);
            $this->clear();

            return $status;
        }

        $this->query->update($columnsVal);

        return $this;
    }

    /**
     * @param null $primaryKey
     * @param null $primaryKeyVal
     * @return mixed
     */
    public function delete($primaryKey = null, $primaryKeyVal = null)
    {
        if ($primaryKey && $primaryKeyVal) {
            $this->query->delete()->where($primaryKey, $primaryKeyVal);

            $status = $this->execute(false);
            $this->clear();

            return $status;
        }

        $this->query->delete();
        return $this;
    }

    /**
     * @param $query
     * @param string $type
     * @return Query
     */
    public function whereQ($query, $type = "AND")
    {
        $this->query->whereQ($query, $type);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param string $operator
     * @param string $type
     * @return $this
     */
    public function where($column, $value, $operator = "=", $type = "AND")
    {
        $this->query->where($column, $value, $operator, $type);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param string $operator
     * @return $this
     */
    public function andWhere($column, $value, $operator = "=")
    {
        $this->query->andWhere($column, $value, $operator);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param string $operator
     * @return $this
     */
    public function orWhere($column, $value, $operator = "=")
    {
        $this->query->orWhere($column, $value, $operator);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param string $type
     * @return $this
     */
    public function like($column, $value, $type = "AND")
    {
        $this->query->like($column, $value, $type);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @return $this
     */
    public function andLike($column, $value)
    {
        $this->query->andLike($column, $value);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @return $this
     */
    public function orLike($column, $value)
    {
        $this->query->orLike($column, $value);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param string $type
     * @return $this
     */
    public function between($column, $value, $type = "AND")
    {
        $this->query->between($column, $value, $type);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @return $this
     */
    public function andBetween($column, $value)
    {
        $this->query->andBetween($column, $value);
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @return $this
     */
    public function orBetween($column, $value)
    {
        $this->query->orBetween($column, $value);
        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function asc($column)
    {
        $this->query->asc($column);
        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function desc($column)
    {
        $this->query->desc($column);
        return $this;
    }

    /**
     * @param $num
     * @param null $offset
     * @return $this
     */
    public function limit($num, $offset = null)
    {
        $this->query->limit($num, $offset);
        return $this;
    }

    /**
     * @param bool $returnThis
     * @return mixed
     */
    public function execute($returnThis = true)
    {
        $qArr = $this->query->build($this->grammar);

        $this->statement = $this->pdo->prepare($qArr[0]);
        $status = $this->statement->execute($qArr[1]);

        return ($returnThis) ? $this : $status;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function all()
    {
        $this->executeIfNotAlreadyExecutedPreviously();

        if ($this->model) {
            $dataModel = array();
            while ($model = $this->get()) {
                $dataModel[] = $model;
            }

            $this->clear();

            return $dataModel;
        }

        $data = $this->statement->fetchAll();
        $this->clear();
        
        return $data;
    }

    /**
     * @return mixed
     */
    public function first()
    {
        $data = $this->limit(1)->get();
        $this->clear();
        return $data;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function get()
    {
        $this->executeIfNotAlreadyExecutedPreviously();

        if ($this->model) {
            $data = $this->statement->fetch();
            return $data?new $this->model($data, false):$data;
        }

        return $this->statement->fetch();
    }

    /**
     * @throws \Exception
     */
    protected function executeIfNotAlreadyExecutedPreviously()
    {
        if (!$this->statement) {
            $this->execute();
        }
    }

    /**
     * Clear statement PDO and query builder
     */
    public function clear()
    {
        $this->statement = null;
        $this->clearUsingModel();

        $this->query->clear();
        $this->grammar->clear();
    }

    /**
     *
     */
    public function clearUsingModel()
    {
        $this->model = null;
    }

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param string $model
     * @param null $table
     * @throws \Exception
     */
    public function setModel($model, $table = null)
    {
        if (!class_exists($model)) {
            throw new \Exception("class {$model} is not exist");
        }

        if ($table) {
            $this->table($table);
        }
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}
