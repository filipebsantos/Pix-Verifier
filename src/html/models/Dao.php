<?php
require(__DIR__ . '/../includes/inc.config.php');
require_once(__DIR__. '/LogHandler.php');

class DAOException extends Exception
{
    public function __construct(string $msg, int $errorCode)
    {
        parent::__construct($msg, $errorCode);
    }
}

/**
 * This class implements the Data Object Access pattern and only this class interact with DBMS.
 * 
 * @method array fetchAccounts()
 * @method array fetchTransactionList()
 */
class DAO
{
    protected $database;
    protected const ITENS_PER_PAGE = 10;

    /**
     * Instanciate the PostgreSQL connection. The connection data should be available in /includes/inc.config.php
     * 
     */
    public function __construct()
    {
        try {
            $this->database = new PDO('pgsql:host=' . db_server . ';port=5432;dbname=' . db_name . ';user=' . db_user . ';password=' . db_pass . '');
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
    }

    /**
     * Fetch user data from database
     * 
     * @param string $user Username
     * 
     * @return mixed An array with the recordset or false if not found
     * 
     * @throws DAOException Raises an error if database connection or query fail
     */
    public function getUserData(string $user) : mixed
    {
        $stmt = $this->database->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(":username", $user);

        try {
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
    }
}

