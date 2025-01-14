"""
Copyright (c) 2024 Filipe Bezerra dos Santos
Website: https://filipebezerra.dev.br
Github: https://github.com/filipebsantos/Pix-Verifier

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
"""
import psycopg2
import psycopg2._psycopg
import psycopg2.pool
import logging
from datetime import datetime
from typing import List, Dict, Any, Union

logger = logging.getLogger("DAO")

class DAOException(Exception):

    def __init__(self, message : str, error_code : int) -> None:
        super().__init__(message)
        self.error_code = error_code

    def __str__(self):
        return f"{self.args[0]} [{self.error_code}]"

class DAO:
    """
    This class is responsible for persisting data and interacting with PostgreSQL.

    Methods
    -------
    __init__()
        Initializes the connection with the PostgreSQL connection pool.

    get_connection()
        Obtains a connection from the pool.

    release_connection()
        Releases a connection back to the pool.

    close_all_connections()
        Closes all connections in the pool.

    listAccounts()
        Lists all bank accounts registered in the database.

    getAccount()
        Fetches a specific account from the database.

    savePixTransaction()
        Saves a received Pix transaction in the database.

    saveManyPixTransaction()
        Saves multiple Pix transactions at once in the database.

    fetchAllReceivedPixIdToday()
        Returns all Pix IDs received today for a specific account.
    
    saveToken()
        Save an OAuth token and its expiration time in the database.
    """

    def __init__(self, db_config: dict) -> None:
        """
        Initialize the DAO class with a connection pool to PostgreSQL.

        Parameters
        ----------
        db_config : dict
            Configuration parameters to connect to PostgreSQL
        """
        self.db_config = db_config
        try:
            logger.debug(f"[__init__] Creating PostgreSQL connection pool...")
            self.pool = psycopg2.pool.ThreadedConnectionPool(1, 10, **self.db_config)
        except (psycopg2.Error, psycopg2.pool.PoolError) as sqlError:
            logger.critical(f"Error while creating PostgreSQL connection pool: {sqlError}")
            raise DAOException(f"Can't create connection pool: {sqlError}", 1)
    
    def get_connection(self):
        """Get a connection from the pool."""
        try:
            return self.pool.getconn()
        except psycopg2.Error as e:
            logger.critical(f"Error obtaining connection from pool: {e}")
            raise DAOException(f"Connection pool error: {e}", 1)

    def release_connection(self, conn):
        """Release a connection back to the pool."""
        try:
            self.pool.putconn(conn)
        except psycopg2.Error as e:
            logger.critical(f"Error releasing connection back to pool: {e}")
            raise DAOException(f"Connection pool error: {e}", 1)

    def close_all_connections(self):
        """Close all connections in the pool."""
        try:
            self.pool.closeall()
        except psycopg2.Error as e:
            logger.critical(f"Error closing all connections: {e}")
            raise DAOException(f"Error closing all connections: {e}", 1)
        
    def listAccounts(self) -> List[Dict[str, Any]]:
        """
        List all bank accounts registered in the database.
        
        Return
        ------
        List[Dict[str, Any]]
            A list of dictionaries containing account details.
        
        Exceptions
        ----------
        DAOException[Error Code 1]:
            Raised when a database error occurs.
        """
        dbConn = self.get_connection()
        dbCursor = dbConn.cursor()
        try:
            logger.debug("Fetching all accounts available in database")
            dbCursor.execute(
                """
                SELECT
                    ba.accountname,
                    ba.bankid,
                    ba.branchnumber,
                    ba.accountnumber
                FROM
                    bankaccount as ba
                """
            )

            queryReturn = dbCursor.fetchall()
            logger.debug(f"Query Result: {queryReturn}")

            accountList = []
            if not queryReturn:
                return accountList
            else:
                for row in queryReturn:
                    account = {
                        'accountname': row[0],
                        'bankid': row[1],
                        'branchnumber': row[2],
                        'accountnumber': row[3]
                    }
                    accountList.append(account)
                
                return accountList
            
        except psycopg2.Error as dbError:
                logger.critical(f"[listAccount] {dbError}")
                raise DAOException(f"Database error: {dbError}", 1)
        finally:
            dbCursor.close()
            self.release_connection(dbConn)

    def getAccount(self, bankID: int, branchNumber: str, accountNumber: str) -> Union[Dict[str, Any], bool]:
        """
        Fetch a specific bank account from the database based on the provided bank ID, branch number, and account number.
        
        Parameters
        ----------
        bankID : int
            The unique identifier of the bank.
        branchNumber : str
            The branch number of the bank.
        accountNumber : str
            The account number.
        
        Return
        ------
        Union[Dict[str, Any], bool]
            A dictionary with account data if found, otherwise False.
        
        Exceptions
        ----------
        DAOException[Error Code 1]:
            Raised when a database error occurs.
        """
        dbConn = self.get_connection()
        dbCursor = dbConn.cursor()
        try:
            logger.debug(f"Fetching account detail for branch: {branchNumber} and account {accountNumber}")
            dbCursor.execute(
                """
                SELECT 
                    ba.accountid,
                    ba.accountname, 
                    ba.bankid, 
                    b.bankname, 
                    ba.branchnumber, 
                    ba.accountnumber, 
                    ba.clientid, 
                    ba.clientsecret,
                    ba.certfile,
                    ba.certkeyfile,
                    ba.accesstoken,
                    ba.tokenexpireat,
                    ba.ignoredsenders
                FROM 
                    bankaccount as ba 
                JOIN 
                    bank as b 
                ON 
                    ba.bankid = b.bankid 
                WHERE 
                    ba.bankid = %s AND 
                    ba.branchnumber = %s AND 
                    ba.accountnumber = %s
                """, 
                (bankID, branchNumber, accountNumber)
            )
            queryReturn = dbCursor.fetchone()
            logger.debug(f"Query Return: {queryReturn}")

            if not queryReturn:
                return False
            else:
                accountData = {
                    'accountid': queryReturn[0],
                    'accountname': queryReturn[1],
                    'bankid': queryReturn[2],
                    'bankname': queryReturn[3],
                    'branchnumber': queryReturn[4],
                    'accountnumber': queryReturn[5],
                    'clientid': queryReturn[6],
                    'clientsecret': queryReturn[7],
                    'certfile': queryReturn[8],
                    'certkeyfile': queryReturn[9],
                    'accesstoken': queryReturn[10], 
                    'tokenexpireat': queryReturn[11], 
                    'ignoredsenders': queryReturn[12] 
                }
                return accountData
                
        except psycopg2.Error as sqlError:
            logger.critical(f"[getAccount] {sqlError}")
            raise DAOException(f"Database error: {sqlError}", 1)
        finally:
            dbCursor.close()
            self.release_connection(dbConn)

    def savePixTransaction(self, accountID : int, e2eid : str, date : str, value : float, payer : str, payerDoc : str, description : str, payerBank : str, bankTransaction=None) -> bool:
        """    
        Save a received Pix transaction in the database.
    
        Parameters
        ----------
        accountID : int
            The unique identifier of the bank account.
        e2eid : str
            The unique Pix transaction identifier.
        date : str
            The date of the transaction.
        value : float
            The value of the transaction.
        payer : str
            The name of the payer.
        payerDoc : str
            The document number of the payer.
        description : str
            The description provided by the payer.
        payerBank : str
            The bank of the payer.
        bankTransaction : str, optional
            The bank transaction identifier on the recipient’s bank.
        
        Return
        ------
        bool
            True if the transaction was saved successfully, otherwise False.
        
        Exceptions
        ----------
        DAOException[Error Code 1]:
            Raised when a database error occurs.
        """
        dbConn = self.get_connection()
        dbCursor = dbConn.cursor()
        try:
            logger.debug(f"Saving Pix ID '{e2eid}' to account id '{accountID}'")
            dbCursor.execute(
                """
                INSERT INTO receivedpix
                    (
                        e2eid,
                        date,
                        value,
                        payer,
                        payerdoc,
                        description,
                        payerbank,
                        banktransaction,
                        accountid
                    )
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
                """,
                (e2eid, date, value, payer, payerDoc, description, payerBank, bankTransaction, accountID)
            )
            
            dbConn.commit()
            logger.debug(f"Transaction '{e2eid}' saved!")
            return True

        except psycopg2.Error as sqlError:
            logger.critical(f"[savePixTransaction] {sqlError}")
            raise DAOException(f"Database error: {sqlError}", 1)
        finally:
            dbCursor.close()
            self.release_connection(dbConn)
    
    def saveManyPixTransaction(self, dataList : list) -> bool:
        """
        Save multiple Pix transactions in the database in a batch.
        
        Parameters
        ----------
        dataList : list
            A list of dictionaries containing Pix transaction data.
        
        Return
        ------
        bool
            True if all transactions were saved successfully, otherwise False.
        
        Exceptions
        ----------
        DAOException[Error Code 1]:
            Raised when a database error occurs.
        """
        # Convert dictionaty to tuple list, expect by executemany()
        tupleList = [
            (
                item['e2eid'],
                item['date'],
                item['value'],
                item['payer'],
                item['payerdoc'],
                item['description'],
                item['payerbank'],
                item['banktransaction'],
                item['accountid']
            )
            for item in dataList
        ]
        dbConn = self.get_connection()
        dbCursor = dbConn.cursor()
        try:
            logger.debug(f"Saving {len(dataList)} Pix transactions")
            dbCursor.executemany("INSERT INTO receivedpix VALUES(%s,%s,%s,%s,%s,%s,%s,%s,%s)", tupleList)
            dbConn.commit()
            logger.debug("Transactions saved!")
            return True
        except psycopg2.Error as sqlError:
            logger.critical(f"[saveManyPixTransaction] {sqlError}")
            raise DAOException(f"Database error: {sqlError}", 1)
        finally:
            dbCursor.close()
            self.release_connection(dbConn)

    def fetchAllReceivedPixIdToday(self, accountID: int) -> Union[List[str], Exception]:
        """
        Fetch all Pix transaction IDs received today for a specific account.
        
        Parameters
        ----------
        accountID : int
            The unique identifier of the bank account.
        
        Return
        ------
        List[str]
            A list of Pix transaction IDs received today.
        
        Exceptions
        ----------
        DAOException[Error Code 1]:
            Raised when a database error occurs.
        """
        dbConn = self.get_connection()
        dbCursor = dbConn.cursor()
        try:
            logger.debug(f"Fetching all saved Pix to account ID {accountID} received on {datetime.now().strftime('%Y-%m-%d')}")
            dbCursor.execute(
                """
                SELECT
                    e2eid
                FROM
                    receivedpix
                WHERE
                    accountID = %s
                    AND DATE(date) = CURRENT_DATE
                """,
                (accountID,)
            )
            queryReturn = dbCursor.fetchall()
            logger.debug(f"Query Result: {queryReturn}")

            if not queryReturn:
                return []
            else:
                return [row[0] for row in queryReturn]

        except psycopg2.Error as sqlError:
            logger.critical(f"[fetchAllReceivedPixIdToday] {sqlError}")
            raise DAOException(f"Database error: {sqlError}", 1)
        finally:
            dbCursor.close()
            self.release_connection(dbConn)

    def saveToken(self, bankID: int, accountID: int, token : datetime, expireAt : int) -> bool:
        """
        Save an OAuth token and its expiration time in the database.
        
        Parameters
        ----------
        bankID : int
            The unique identifier of the bank.
        accountID : int
            The unique identifier of the bank account.
        token : str
            The OAuth token.
        timestamp : int
            The Unix timestamp when the token was issued.
        
        Return
        ------
        bool
            True if the token was saved successfully, otherwise False.
        
        Exceptions
        ----------
        DAOException[Error Code 1]:
            Raised when a database error occurs.
        """
        dbConn = self.get_connection()
        dbCursor = dbConn.cursor()

        try:
            logger.debug(f"Saving access to account ID #{accountID}")
            dbCursor.execute(
                """
                UPDATE bankaccount SET
                    accesstoken = %s,
                    tokenexpireat = %s
                WHERE
                    accountid = %s
                """,
                (token, expireAt, accountID)
            )

            dbConn.commit()
            logger.debug(f"New token for account ID #{accountID} saved and expires at {expireAt}")
            return True
        except psycopg2.Error as sqlError:
            logger.critical(f"[saveToken] {sqlError}")
            raise DAOException(f"Databse error: {sqlError}", 1)
        finally:
            dbCursor.close()
            self.release_connection(dbConn)
        