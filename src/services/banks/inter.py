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
from typing import List, Dict, Any, Union
from datetime import datetime, timedelta
from pathlib import Path
import time
import logging
import requests
import urllib3.exceptions

logger = logging.getLogger("Inter")

class InterException(Exception):

    def __init__(self, message : str, error_code : int) -> None:
        super().__init__(message)
        self.error_code = error_code

    def __str__(self):
        return f"{self.args[0]} [{self.error_code}]"

class Inter:
    """
    A class to handle Pix transactions with the Inter Bank API.

    Attributes
    ----------
    bankID : int
        Fixed value representing Inter Bank (77)
    accountID : int
        The unique account identifier
    clientID : str
        Client ID key provided by the bank
    clientSecret : str
        Client secret provided by the bank
    certPath : Path
        Path to the certificate file
    certKeyPath : Path
        Path to the private key of the certificate
    productionEnvironment : bool
        If True, uses the production environment, otherwise uses the sandbox
    ignoredSender : List
        A list of CPF or CNPJ senders to ignore when receiving Pix transactions
    savedPix : List
        A list of Pix transaction identifiers saved during the current day
    bearerToken : str or None
        OAuth token used for API requests
    bearerTokenExpiration : datetime or None
        Expiration time of the OAuth token

    Methods
    -------
    __init__(accountID: int, clientID: str, clientSecret: str, certFile: str, certKeyFile: str, productionEnvironment: bool = True) -> None
        Initializes the Inter object with the account details and certificate paths.

    requestOAuthToken() -> Union[Dict[str, str], bool]
        Requests an OAuth token to be used for authorization in API requests.

    queryStatementPix() -> Union[List[Dict[str, Any]], bool]
        Fetches all Pix transactions received on the current day that are not from ignored senders or already saved.
    """

    def __init__(self, accountID :int, clientID : str, clientSecret : str, certFile : str, certKeyFile : str, productionEnvironment: bool = True) -> None:
        """
        Initializes the Inter object with the account details and certificate paths.

        Parameters
        ----------
        accountID : int
            The unique account identifier
        clientID : str
            Client ID key provided by the bank
        clientSecret : str
            Client secret provided by the bank
        certFile : str
            Name of the certificate file
        certKeyFile: str
            Name of the certificate key file
        productionEnvironment : bool, optional
            If True, uses the production environment, otherwise uses the sandbox (default is True)
        """
        base_path = Path(__file__).resolve().parents[2]

        self.bankID = 77
        self.accountID = accountID
        self.clientID = clientID
        self.clientSecret = clientSecret
        self.certPath = base_path / 'services/certs/inter' / certFile
        self.certKeyPath = base_path / 'services/certs/inter' / certKeyFile
        self.productionEnvironment = productionEnvironment
        self.ignoredSender = []
        self.savedPix = []
        self.bearerToken = None
        self.bearerTokenExpiration = None
        
    def requestOAuthToken(self) -> Union[Dict[str, str], bool]:
        """
        Requests an OAuth token to be used for authorization in API requests.

        Returns
        -------
        Union[Dict[str, str], bool]
            A dictionary with token information if successful, or False if not. Saves the token in the object attributes.

        Raises
        ------
        InterException
            Raised when there is an error in the token request process.

        Error Code 1
            API returned a 40x or 503 error
        Error Code 2
            An unexpected error occurred
        Error Code 3
            Network error, could not establish a connection
        """
        requestData = {
            'client_id': self.clientID,
            'client_secret': self.clientSecret,
            'scope' : 'extrato.read',
            'grant_type': 'client_credentials'
        }
        requestHeader = {
            "Content-Type": "application/x-www-form-urlencoded"
        }

        if self.productionEnvironment == True:
            logger.debug("Using production environment to fetch access token")
            url = 'https://cdpj.partners.bancointer.com.br/oauth/v2/token'
        else:
            logger.debug("Using sandbox environment to fetch access token")
            url = 'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token'

        try:
            requestToken = requests.post(url, data=requestData, headers=requestHeader, cert=(self.certPath, self.certKeyPath), timeout=10)
            requestToken.raise_for_status()
        
        except requests.Timeout:
                raise InterException(f"[queryStatementPix] Timeout reached", 3)
        except requests.exceptions.HTTPError as httpError:
            httpCode = httpError.response.status_code
            if (httpCode == 400):
                logger.error("[requestOAuthToken] Request with invalid format")
            elif (httpCode == 401):
                logger.error("[requestOAuthToken] Invalid client_id or client_secret")
            elif (httpCode == 403):
                logger.error("[requestOAuthToken] Request from authenticated participant that violates some authorization rule")
            elif (httpCode == 404):
                logger.error("[requestOAuthToken] Requested resource not found")
            elif (httpCode == 503):
                logger.error("[requestOAuthToken] Service Unavailable")
            else:
                logger.critical(f"[requestOAuthToken][{httpCode}] {httpError.response}")
            raise InterException("API returned an error, please verify logs", 1)
        
        except requests.exceptions.RequestException as requestError:
            logger.critical(f"[requestOAuthToken] Request Error -> {requestError.response}")
            raise InterException("An unexpected error occurred, please verify logs", 2)
        
        except (requests.exceptions.ConnectionError, urllib3.exceptions.NewConnectionError, urllib3.exceptions.MaxRetryError) as connError:
            logger.critical(f"[requestOAuthToken] Network-related error: {str(connError)}")
            raise InterException("Network Error, can't estabilish connnection", 3)
        
        self.bearerToken = requestToken.json().get('access_token')
        self.bearerTokenExpiration = datetime.now() + timedelta(seconds=requestToken.json().get('expires_in'))

        logger.debug(f"Token [{self.bearerToken}] received to account ID #{self.accountID} and expire at {self.bearerTokenExpiration}")
        return {
            'access_token': requestToken.json().get('access_token'),
            'expires_in': self.bearerTokenExpiration.strftime("%Y-%m-%d %H:%M:%S.%f")
        }
    
    def queryStatementPix(self) -> Union[List[Dict[str, Any]], bool]:
        """
        Fetches all Pix transactions received on the current day that are not from ignored senders or already saved.

        Returns
        -------
        Union[List[Dict[str, Any]], bool]
            A list of transaction dictionaries if successful, or False if there are no transactions.

        Raises
        ------
        InterException
            Raised when there is an error in fetching Pix transactions.

        Error Code 1
            API returned a 40x or 503 error
        Error Code 2
            An unexpected error occurred
        Error Code 3
            Network error, could not establish a connection
        Error Code 4
            Failed to process API response
        Error Code 5
            Authorization token not provided
        Error Code 6
            Request timed out
        """
        if self.bearerToken is None:
            logger.error("[queryStatementPix] Missing authorization token")
            raise InterException("Missing authorization token", 5)
        
        lastPage = False
        page = 0
        receivedPixList = []
        
        while lastPage is False:
            if self.productionEnvironment == True:
                logger.debug("Using production environment to fetch received pix")
                url = 'https://cdpj.partners.bancointer.com.br/banking/v2/extrato/completo'
            else:
                logger.debug("Using sandbox environment to fetch received pix")
                url = 'https://cdpj-sandbox.partners.uatinter.co/banking/v2/extrato/completo'
            
            requestParams = {
                "pagina": page,
                "tipoOperacao": "C",
                "tipoTransacao": "PIX",
                "dataInicio": datetime.now().strftime("%Y-%m-%d"),
                "dataFim": datetime.now().strftime("%Y-%m-%d")
            }

            requestHeader = {
                "Authorization": f"Bearer {self.bearerToken}",
            }

            try:
                logger.debug(f"Requesting page #{page} from API")
                requestPix = requests.get(url, headers=requestHeader, params=requestParams, cert=(self.certPath, self.certKeyPath), timeout=10)
                requestPix.raise_for_status()
                logger.debug(f"Requisition time elapsed: {int(requestPix.elapsed.microseconds / 1000)}ms")
            
            except requests.Timeout:
                raise InterException(f"[queryStatementPix] Timeout reached", 6)
            except requests.exceptions.HTTPError as httpError:
                httpCode = httpError.response.status_code
                if (httpCode == 400):
                    logger.error('[queryStatementPix] Request with invalid format')
                elif (httpCode == 403):
                    logger.error('[queryStatementPix] Request from authenticated participant that violates some authorization rule')
                elif (httpCode == 404):
                    logger.error('[queryStatementPix] Requested resource not found')
                elif (httpCode == 503):
                    logger.error('[queryStatementPix] Service Unavailable')
                else:
                    logger.critical(f"[queryStatementPix][{httpCode}] {httpError.response}")
                raise InterException("API returned an error, please verify logs", 1)
            
            except requests.exceptions.RequestException as requestError:
                logger.critical(f"[queryStatementPix] Request Error -> {str(requestError)}")
                raise InterException("An unexpected error occurred, please verify logs", 2)
            
            except (requests.exceptions.ConnectionError, urllib3.exceptions.NewConnectionError, urllib3.exceptions.MaxRetryError) as connError:
                logger.critical(f"[queryStatementPix] Network-related error: {str(connError)}")
                raise InterException("Network Error, can't estabilish connnection", 3)
            
            lastPage = requestPix.json().get('ultimaPagina')
            if (requestPix.json().get('numeroDeElementos') > 0):
                try:
                    for receivedPix in requestPix.json().get('transacoes', []):
                        try:
                            if receivedPix['detalhes']['endToEndId'] not in self.savedPix and receivedPix['detalhes']['cpfCnpjPagador'] not in self.ignoredSender:
                                receivedPixItem = {
                                    'e2eid': receivedPix['detalhes']['endToEndId'],
                                    'date': receivedPix['dataInclusao'],
                                    'value': float(receivedPix['valor']),
                                    'payer': receivedPix['detalhes']['nomePagador'],
                                    'payerdoc': receivedPix['detalhes']['cpfCnpjPagador'],
                                    'description': receivedPix['detalhes']['descricaoPix'],
                                    'payerbank': receivedPix['detalhes']['nomeEmpresaPagador'],
                                    'banktransaction': receivedPix['idTransacao'],
                                    'accountid': self.accountID
                                }
                                logger.debug(f"Pix Received: {receivedPixItem}")
                                receivedPixList.append(receivedPixItem)
                                self.savedPix.append(receivedPix['detalhes']['endToEndId'])
                            else:
                                logger.debug(f"Pix ID {receivedPix['detalhes']['endToEndId']} from sender {receivedPix['detalhes']['cpfCnpjPagador']} not processed.")
                        except KeyError as e:
                            logger.error(f"Missing key in receivedPix['detalhes']: {e} - Full entry: {receivedPix}")
                except Exception as exception:
                    logger.error(f"Error while processing API response: {exception}", exc_info=True)
                    raise InterException("Failed while processing API response", 4)
            else:
                lastPage = True
            
            if lastPage is False:
                page += 1
                logger.debug("Preparing to request next page of received pix...")
                time.sleep(10)
        
        logger.debug(f"Returning list of received pix in {datetime.now().strftime('%Y-%m-%d')}: {receivedPixList}")
        return receivedPixList
    