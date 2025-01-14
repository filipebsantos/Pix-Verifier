<?php

require_once(__DIR__ . "/Dao.php");

class TransactionsDAO extends DAO
{
    /**
     * Fetch from database a list with transactions received.
     * 
     * @param DataTime $startDate The start date from where transactions should be fetched.
     * @param DateTime $endDate The end data until transactions should be fetched.
     * @param int $accountID Account unique identifier.
     * @param int $page Used to pagination, this function return 10 itens at time.
     * 
     * @return array A list with 10 last transactions
     */
    public function fetchTransactionList(string $startDate, string $endDate, int $accountID, int $page = 1) : array
    {
        $offset = ($page - 1) * self::ITENS_PER_PAGE;

        $stmt = $this->database->prepare(
            "WITH TotalCount AS (
                 SELECT COUNT(*) AS totalrecords 
                 FROM receivedpix
                 WHERE accountid = :accountid 
                 AND DATE_TRUNC('day', date) >= :startDate
                 AND DATE_TRUNC('day', date) <= :endDate
             )
             SELECT e2eid, date, value, payer, (SELECT totalrecords FROM TotalCount) AS totalrecords
             FROM receivedpix
             WHERE accountid = :accountid 
             AND DATE_TRUNC('day', date) >= :startDate
             AND DATE_TRUNC('day', date) <= :endDate
             ORDER BY date DESC
             LIMIT :itensPerPage OFFSET :offset"
        );

        $stmt->bindValue(':accountid', $accountID, PDO::PARAM_INT);
        $stmt->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->bindValue(':itensPerPage', self::ITENS_PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        try{
            $stmt->execute();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
        
        $transactions = $stmt->fetchAll();
        $totalPages = count($transactions) > 0 ? ceil($transactions[0]['totalrecords'] / self::ITENS_PER_PAGE) : 1;

        foreach ($transactions as &$transaction) {
            unset($transaction['totalrecords']);
        }

        return [
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'transactions' => $transactions
        ];
    }

    /**
     * Fetch data from a single Pix transaction.
     * 
     * @param string $e2eid The unique Pix idenfier
     * 
     * @return array A list with transaction data
     */
    public function fetchPixTransaction(string $e2eid) : array
    {
        $stmt = $this->database->prepare("SELECT * FROM receivedpix WHERE e2eid = :e2eid");
        $stmt->bindValue(':e2eid', $e2eid, PDO::PARAM_STR);

        try{
            $stmt->execute();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }
        
        return $stmt->fetch();
    }

    /**
     * Filter transactions by sender for an account id.
     * 
     * @param int $accountid The account id
     * @param string $payerdoc Sender document
     * 
     * @return array A list with the number of pages and the 10 last transactions
     */
    public function filterTransactionBySender(int $accountid, string $payerdoc, int $page = 1) : array
    {
        $offset = ($page - 1) * self::ITENS_PER_PAGE;

        $stmt = $this->database->prepare(
            "WITH TotalCount AS (
                 SELECT COUNT(*) AS totalrecords 
                 FROM receivedpix
                 WHERE accountid = :accountid 
                 AND payerdoc = :payerdoc
             )
             SELECT e2eid, date, value, payer, (SELECT totalrecords FROM TotalCount) AS totalrecords
             FROM receivedpix
             WHERE accountid = :accountid 
             AND payerdoc = :payerdoc
             ORDER BY date DESC
             LIMIT :itensPerPage OFFSET :offset"
        );

        $stmt->bindValue(":accountid", $accountid, PDO::PARAM_INT);
        $stmt->bindValue(":payerdoc", $payerdoc, PDO::PARAM_STR);
        $stmt->bindValue(":itensPerPage", self::ITENS_PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        try{
            $stmt->execute();
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }

        $transactions = $stmt->fetchAll();
        $totalPages = count($transactions) > 0 ? ceil($transactions[0]['totalrecords'] / self::ITENS_PER_PAGE) : 1;

        foreach ($transactions as &$transaction) {
            unset($transaction['totalrecords']);
        }

        return [
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'transactions' => $transactions
        ];
    }

    /**
     * This function bring the last transactions after a period time informed.
     * 
     * @param int $accountId The account id
     * @param string $timestamp Timestamp string 'YYYY-MM-DD H:m:s'
     * 
     * @return array A list with transactions 
     */
    public function fetchLastTransactions(int $accountId, string $timestamp) 
    {
        $stmt = $this->database->prepare("WITH TotalCount AS (
                                            SELECT COUNT(*) AS totalrecords 
                                            FROM receivedpix
                                            WHERE accountid = :accountid 
                                            AND DATE_TRUNC('day', date) = :date
                                        )SELECT 
                                            e2eid, 
                                            date, 
                                            value,
                                            payer,
                                            (SELECT totalrecords FROM TotalCount) AS totalrecords
                                        FROM receivedpix
                                        WHERE accountid = :accountid
                                        AND DATE_TRUNC('day', date) = DATE_TRUNC('day', :date)
                                        AND date > :timestamp
                                        ORDER BY date DESC");
        
        $stmt->bindValue(":accountid", $accountId, PDO::PARAM_INT);
        $stmt->bindValue(":timestamp", $timestamp, PDO::PARAM_STR);
        $stmt->bindValue(":date", date_format(date_create($timestamp), 'Y-m-d'), PDO::PARAM_STR);

        try {
            $stmt->execute();
            $transactions = $stmt->fetchAll();

            $totalPages = count($transactions) > 0 ? ceil($transactions[0]['totalrecords'] / self::ITENS_PER_PAGE) : 1;

            foreach ($transactions as &$transaction) {
                unset($transaction['totalrecords']);
            }

            return [
                'qty' => count($transactions),
                'totalPages' => $totalPages,
                'transactions' => $transactions
            ];
        } catch (PDOException $pdoError) {
            LogHandler::stdlog($pdoError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
            throw new DAOException("Database error", 10);
        }

    }
}
?>