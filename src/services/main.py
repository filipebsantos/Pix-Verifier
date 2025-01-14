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
import logging_config
import logging
import threading
import time
import sys
import os
from dao import DAO, DAOException
from banks.inter import Inter, InterException
from datetime import datetime

# Configuração do logging
logging_config.setup_logging()
logger = logging.getLogger('Main')

db_config = {
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASS'),
    'host': os.getenv('DB_HOST'),
    'port': '5432',
    'database': os.getenv('DB_NAME')
}

dao = DAO(db_config)

# Fetching accounts available from database
logger.info("Obtendo lista de contas disponíveis")
availableAccounts = dao.listAccounts()
logger.info(f"{len(availableAccounts)} conta(s) carregada(s)")

# Create objects
logger.info("Instanciando contas em memória...")
interAccounts = {}
for account in availableAccounts:
    # Load account data
    try:
        inter = dao.getAccount(account['bankid'], account['branchnumber'], account['accountnumber'])
    except IndentationError as InterError:
        logger.error(f"Inter Error: {InterError}")

    if all([inter['clientid'], inter['clientsecret'], inter['certfile'], inter['certkeyfile']]):
        interAcc = Inter(
            inter['accountid'],
            inter['clientid'],
            inter['clientsecret'],
            inter['certfile'],
            inter['certkeyfile']
        )

        # Load ignored senders
        interAcc.ignoredSender = inter['ignoredsenders'].split(';')
        logger.debug(f"[{account['accountname']}] Ignored senders loaded: {interAcc.ignoredSender}")

        try:       
            # Load processed transactions today
            interAcc.savedPix = dao.fetchAllReceivedPixIdToday(interAcc.accountID)

            # Load access token
            if inter['accesstoken'] is not None and inter['tokenexpireat'] is not None:
                # Check if token is expired
                if datetime.now() > inter['tokenexpireat']:
                    logger.debug(f"[{account['accountname']}] Token expired, requesting a new one...")
                    interAcc.requestOAuthToken()
                    dao.saveToken(interAcc.bankID, interAcc.accountID, interAcc.bearerToken, interAcc.bearerTokenExpiration)
                else:
                    interAcc.bearerToken = inter['accesstoken']
                    interAcc.bearerTokenExpiration = inter['tokenexpireat']
                    logger.debug(f"[{account['accountname']}] Token loaded")
            else:
                logger.debug(f"[{account['accountname']}] Token not available, requesting a new one...")
                interAcc.requestOAuthToken()
                dao.saveToken(interAcc.bankID, interAcc.accountID, interAcc.bearerToken, interAcc.bearerTokenExpiration)

            logger.debug(f"Object for account '{account['accountname']}' created with success!")
            interAccounts[account['accountname']] = interAcc

        except DAOException as daoError:
            logger.error("Erro")

        except InterException as interError:
            logger.error(f"[{account['accountname']}]: {interError}")
    else:
        logger.debug(f"Object for account '{account['accountname']}' wasn't created.")

# Function to be executed to monitore Pix transactions received
def pixMonitoring(interAcc, accountName):
    
    while True:
        # First check if token still valid
        if datetime.now() > interAcc.bearerTokenExpiration:
            logger.info(f"[{accountName}] Token expirou, requisitando novo token...")
            try:
                interAcc.requestOAuthToken()
                dao.saveToken(interAcc.bankID, interAcc.accountID, interAcc.bearerToken, interAcc.bearerTokenExpiration)
                logger.info(f"[{accountName}] Novo token obtido e salvo com sucesso!")
            except DAOException as daoError:
                logger.error(f"[{accountName}]: {daoError}")
            except InterException as interError:
                logger.error(f"[{accountName}]: {interError}")

        # Fetch received Pix
        try:
            receivedPix = interAcc.queryStatementPix()

            # Save transactions in data base
            if len(receivedPix) > 1:
                dao.saveManyPixTransaction(receivedPix)
                logger.info(f"[{accountName}] {len(receivedPix)} transações salvas no banco de dados")
            elif len(receivedPix) == 1:
                dao.savePixTransaction(interAcc.accountID, receivedPix[0]['e2eid'], receivedPix[0]['date'], receivedPix[0]['value'], receivedPix[0]['payer'],
                                       receivedPix[0]['payerdoc'], receivedPix[0]['description'], receivedPix[0]['payerbank'], receivedPix[0]['banktransaction'])
                logger.info(f"[{accountName}] Transação {receivedPix[0]['e2eid']} salva no banco dados")
                
        except InterException as InterError:
            logger.debug(f"[{accountName}][InterError] {InterError}")
            if InterError.error_code == 3:
                logger.warning(f"[{accountName}] Erro de rede. Aguardando 30s antes de uma nova tentativa")
                time.sleep(30)
                continue
            if InterError.error_code == 5:
                logger.error(f"[{accountName}] Authorization token não disponível")
                continue
            if InterError.error_code == 6:
                logger.error(f"[{accountName}] A requisição excedeu o tempo limite, uma nova requisição será feita em 10s")
                time.sleep(10)
                continue
        except DAOException as DaoError:
            logger.debug(f"[{accountName}][DAOError] {DaoError}")

        time.sleep(10)

# Add created objecs to run in separated threads
if not interAccounts:
    logger.warning("Não há contas carregadas para monitorar. Encerrando...")
    sys.exit()
else:
    logger.info("Iniciando monitoramento...")
    threads = []
    for accountName, interAcc in interAccounts.items():
        thread = threading.Thread(target=pixMonitoring, args=(interAcc, accountName), name=accountName)
        threads.append(thread)
        logger.debug(f"Starting thread to account {accountName}")
        thread.start()

    # List active threads
    for thread in threads:
        print(f"Thread ativa: {thread.name}")