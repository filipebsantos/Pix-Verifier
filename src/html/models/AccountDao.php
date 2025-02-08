<?php

require_once(__DIR__ . "/Dao.php");

class AccountDAO extends DAO
{

     /**
     * Return a list with all accounts available in database.
     * 
     * @return array
     */
    public function fetchAccounts() : array
    {
        try{
            $stmt = $this->database->query(
                "SELECT
                    ba.accountid,
                    ba.accountname,
                    ba.bankid,
                    b.bankname,
                    ba.branchnumber,
                    ba.accountnumber
                FROM
                    bankaccount AS ba
                JOIN
                    bank AS b
                ON
                    ba.bankid = b.bankid
                "
            );

            return $stmt->fetchAll();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
    }

    /**
     * Create a new bank account.
     * 
     * @param int $bankId Bank unique id
     * @param string $branchNumber
     * @param string $accountNumber
     * @param string $accountName
     * 
     * @return bool 
     * 
     * @throws DAOException
     */
    public function newAccount(int $bankId, string $branchNumber, string $accountNumber, string $accountName) : bool
    {
        //Check if account already exists
        $stmt = $this->database->prepare("SELECT accountid 
                                            FROM bankaccount 
                                            WHERE accountname = :accountName
                                            OR accountnumber = :accountNumber");
        $stmt->bindValue(":accountName", $accountName);
        $stmt->bindValue(":accountNumber", $accountNumber);
        
        try{
            $stmt->execute();
            $dbReturn = $stmt->fetch();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }

        if($dbReturn) {
            throw new DAOException("Duplicated record", 11);
        }

        //Save new account
        $stmt = $this->database->prepare("INSERT INTO bankaccount (bankid, branchnumber, accountnumber, accountname) 
                                                VALUES (:bankId, :branchNumber, :accountNumber, :accountName)");
        $stmt->bindValue(":bankId", $bankId, PDO::PARAM_INT);
        $stmt->bindValue(":branchNumber", $branchNumber, PDO::PARAM_STR);
        $stmt->bindValue(":accountNumber", $accountNumber, PDO::PARAM_STR);
        $stmt->bindValue(":accountName", $accountName, PDO::PARAM_STR);

        try{
            return $stmt->execute();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
    }

    /**
     * Delete bank account from database
     * 
     * @param int $accountId
     * @return bool 
     * 
     * @throws DAOException Case statement fail thowrs an exception
     */
    public function deleteAccount(int $accountId) : bool
    {
        $stmt = $this->database->prepare("DELETE FROM bankaccount WHERE accountid = :accountid");
        $stmt->bindValue(":accountid", $accountId, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
    }

    public function fetchAccountDetail(int $accountId)
    {
        $stmt = $this->database->prepare("SELECT
                                            accountname,
                                            clientid,
                                            clientsecret,
                                            certfile,
                                            certkeyfile,
                                            ignoredsenders
                                        FROM bankaccount
                                        WHERE accountid = :accountid");
        
        $stmt->bindValue(":accountid", $accountId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $dbReturn = $stmt->fetch();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }

        if (count($dbReturn) > 0){
            return $dbReturn;
        } else {
            return false;
        }
    }

    // public function updateAccountDetail(int $accountId, string $clientId, string $clientSecret, string $certFile, string $certKeyFile, string $ignoredSenders)
    // {
    //     $stmt = $this->database->prepare("UPDATE bankaccount SET
    //                                         clientid = :clientid,
    //                                         clientsecret = :clientsecret,
    //                                         certfile = :certfile,
    //                                         certkeyfile = :certkeyfile,
    //                                         ignoredsenders = :ignoredsenders
    //                                     WHERE accountid = :accountid");
    //     $stmt->bindValue(":accountid", $accountId, PDO::PARAM_INT);
    //     $stmt->bindValue(":clientid", $clientId, PDO::PARAM_STR);
    //     $stmt->bindValue(":clientsecret", $clientSecret, PDO::PARAM_STR);
    //     $stmt->bindValue(":certfile", $certFile, PDO::PARAM_STR);
    //     $stmt->bindValue(":certkeyfile", $certKeyFile, PDO::PARAM_STR);
    //     $stmt->bindValue(":ignoredsenders", $ignoredSenders, PDO::PARAM_STR);

    //     try {
    //         return $stmt->execute();
    //     } catch (PDOException $pdoError) {
    //         LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
    //         throw new DAOException("Database error", 10);
    //     }
    // }
    public function updateAccountDetail(int $accountId, string $clientId, string $clientSecret, ?string $certFile, ?string $certKeyFile, string $ignoredSenders)
    {
        // Create an array without optional arguments
        $options = [
            "clientid" => $clientId,
            "clientsecret" => $clientSecret,
            "ignoredsenders" => $ignoredSenders
        ];

        // Add cert's file name if not empty
        if (!empty($certFile)) {
            $options["certfile"] = $certFile;
        }

        // Add cert's key file name if not empty
        if (!empty($certKeyFile)) {
            $options["certkeyfile"] = $certKeyFile;
        }

        // Mount query parts dinamically
        $queryParts = [];
        foreach ($options as $column => $value) {
            $queryParts[] = "{$column} = :{$column}";
        }
        $sqlClause = implode(', ', $queryParts);

        $stmt = $this->database->prepare("UPDATE bankaccount SET {$sqlClause} WHERE accountid = :accountid");

        // Create bind parameters
        foreach($options as $column => $value) {
            $stmt->bindValue(":{$column}", $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(":accountid", $accountId, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
    }
}
?>