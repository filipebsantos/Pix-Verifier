# Esse script utiliza a API disponibilizada pelo Banco Inter para buscar por
# todas as transferencias recebidas via Pix e salva em um banco de dados PostgreSQL.
# 
# Desenvolvido por Filipe Bezerra dos Santos(filipebezerrasantos@gmail.com)
# https://filipebezerra.dev.br
# https://github.com/filipebsantos
#
import requests
import psycopg2
from datetime import datetime
import time
from decimal import Decimal
import logging
from logging.handlers import RotatingFileHandler
import os

logFileName = "pix-service.log"
logFilePath = "./logs/" + logFileName

# Configurar o handler RotatingFileHandler
handler = RotatingFileHandler(logFilePath, maxBytes=100000, backupCount=5, encoding='utf-8')

# Configurar o formato do log
formatter = logging.Formatter('%(asctime)s: %(message)s', datefmt='%d/%m/%Y %H:%M:%S')
handler.setFormatter(formatter)

# Configurar o logger
logger = logging.getLogger()
logger.addHandler(handler)
logger.setLevel(logging.INFO)

#PostgreSQL
dbhost = os.environ.get("DB_HOST", "")
dbname = os.environ.get("DB_NAME", "")
dbuser = os.environ.get("DB_USER", "")
dbpass = os.environ.get("DB_PASS", "")

tokenOauth = None
tokenCreatedAt = None
savedPix = []
ignoredSender = []
scope = "extrato.read"


# Verifica certificados
cert_path = "./certs/inter/inter_cert.crt"
key_path = "./certs/inter/inter_cert.key"

check_cert_file = os.path.isfile(cert_path)
check_cert_key = os.path.isfile(key_path)

if check_cert_file is False or check_cert_key is False:
    logging.info("Certificados não foram encontrados. Interrompendo execução...")
    exit()

# Abre conexão com o banco de dados
logging.info("Abrindo conexão com o banco de dados.")
try:
    conn = psycopg2.connect(host=dbhost, database=dbname, user=dbuser, password=dbpass)
    logging.info("Conectado ao banco de dados.")
except psycopg2.Error as error:
    logging.info("Não foi possível conectar ao banco de dados.")
    logging.error("Erro: %s", error)
    exit()

# Dados de acesso API
with conn.cursor() as cursor:
    sqlQuery = "SELECT clientid, clientsecret FROM inter"
    cursor.execute(sqlQuery)
    result = cursor.fetchone()

    if result and all(field is not None for field in result):
        client_id, client_secret = result
        logging.info("Dados de configuração da API carregados!")
    else:
        logging.info("Faltam dados para uso da API. Interrompendo execução...")
        exit()

# Baixa a lista de remententes ignorados
logging.info("Baixando lista de remententes ignorados.")
with conn.cursor() as cursor:
    sqlQuery = "SELECT value FROM settings WHERE key = 'ignoredList'"
    cursor.execute(sqlQuery)
    result = cursor.fetchone()

    if result is not None:
        ignoredSender = result[0].split(",")

# Salva os ids das transações em memória
logging.info(f"Baixando transações já salvas em {datetime.now().strftime('%d/%m/%Y')}.")
with conn.cursor() as cursor:
    sqlQuery = "SELECT idtransacao FROM receivedpix WHERE DATE_TRUNC('day', datainclusao) = %s"
    cursor.execute(sqlQuery, (datetime.now().strftime("%Y-%m-%d"),))
    result = cursor.fetchall()

    for transaction in result:
        savedPix.append(transaction[0])

while True:
    if tokenOauth is None or tokenCreatedAt is None:
        # Busca pelo token no banco dados
        logging.info("Buscando pelo Token OAuth no banco de dados...")
        try:
            with conn.cursor() as cursor:    
                sqlQuery = "SELECT oauthtoken, tokencreation FROM inter"
                cursor.execute(sqlQuery)

                # Pega o resultado da consulta
                result = cursor.fetchone()
        except psycopg2.Error as error:
            logging.error("Erro: %s", error)

        # Se foi retordo algo da consulta, salva os valores e verifica o tempo decorrido desde a criação do token
        if result and all(field is not None for field in result):
            logging.info("Token OAuth carregado!")
            tokenOauth = result[0]
            tokenCreatedAt = result[1]
    
    if tokenCreatedAt is not None:
        tempoDecorrido = datetime.now() - tokenCreatedAt
        minutosDecorridos = int(tempoDecorrido.total_seconds() / 60)
    else:
        minutosDecorridos = None

    # Se passou mais de 55 minutos, gera um novo token OAuth
    if minutosDecorridos is None or minutosDecorridos > 55:
        
        data = {
            "client_id": client_id,
            "client_secret": client_secret,
            "scope": scope,
            "grant_type": "client_credentials"
        }

        url = "https://cdpj.partners.bancointer.com.br/oauth/v2/token"

        headers = {
            "Content-Type": "application/x-www-form-urlencoded"
        }

        logging.info("Requisitando novo token OAuth.")
        # Enviar a solicitação POST com certificado e chave
        try:
            response = requests.post(url, data=data, headers=headers, cert=(cert_path, key_path))
            response.raise_for_status()

        except requests.exceptions.HTTPError as httpError:
            if "500" in str(httpError):
                logging.info("Servidor retornou erro 500. Aguardando 30s antes de uma nova tentativa")
                time.sleep(30)
                continue

        except requests.exceptions.RequestException as reqError:
            logging.info("Erro ao processar requisição.")
            logging.error(reqError)

        # Salva o token oAuth
        tokenOauth = response.json().get("access_token")
        tokenCreatedAt = datetime.now()
        
        try:
            with conn.cursor() as cursor:    
                sqlQuery = "UPDATE inter SET oauthtoken = %s, tokencreation = %s"
                cursor.execute(sqlQuery, (tokenOauth, str(tokenCreatedAt)))
                conn.commit()
            logging.info("Token OAuth salvo.")
        except psycopg2.Error as error:
            logging.info("Não foi possível salvar o token no banco de dados.")
            logging.error("Erro: %s", error)
            exit()

    ultimaPagina = False
    pagina = 0

    while ultimaPagina is False:
        url = "https://cdpj.partners.bancointer.com.br/banking/v2/extrato/completo"
        
        headers = {
            "Authorization": f"Bearer {tokenOauth}",
        }

        filtros = {
            "pagina": pagina,
            "tipoOperacao": "C",
            "tipoTransacao": "PIX",
            "dataInicio": datetime.now().strftime("%Y-%m-%d"),
            "dataFim": datetime.now().strftime("%Y-%m-%d")
        }

        try:
            response = requests.get(url, headers=headers, params=filtros ,cert=(cert_path, key_path))
            response.raise_for_status()

        except requests.exceptions.HTTPError as httpError:
            if "500" in str(httpError):
                logging.info("Servidor retornou erro 500. Aguardando 30s antes de uma nova consulta")
                time.sleep(30)
                continue

        except requests.exceptions.RequestException as reqError:
            logging.info("Erro ao processar requisição.")
            logging.error(reqError)
        
        ultimaPagina = response.json().get("ultimaPagina")
        numeroDeElementos = response.json().get("numeroDeElementos")

        # Se não há elementos na página, ultima página é True
        if numeroDeElementos > 0:
            commit_required = False
            for receivedPix in response.json().get("transacoes"): 
                # Verifica se a transação não está salva na lista e não é da lista de ignorados          
                if receivedPix['idTransacao'] not in savedPix and receivedPix["detalhes"]["cpfCnpjPagador"] not in ignoredSender:
                    try:
                        with conn.cursor() as cursor:
                            sqlQuery = "SELECT idtransacao FROM receivedpix WHERE idtransacao = %s"
                            cursor.execute(sqlQuery, (receivedPix['idTransacao'],))
                            idBanco = cursor.fetchone()

                            if not idBanco:
                                sqlQuery = "INSERT INTO receivedpix (e2eid, datainclusao, valor, nomepagador, descricaopix, cpfcnpjpagador, nomeempresapagador, idtransacao) VALUES(%s,%s,%s,%s,%s,%s,%s,%s)"
                                cursor.execute(sqlQuery, (receivedPix["detalhes"]["endToEndId"], receivedPix["dataInclusao"], Decimal(receivedPix["valor"]), receivedPix["detalhes"]["nomePagador"], receivedPix["detalhes"]["descricaoPix"], receivedPix["detalhes"]["cpfCnpjPagador"], receivedPix["detalhes"]["nomeEmpresaPagador"], receivedPix["idTransacao"]))
                                logging.info("Salvando transação %s", receivedPix['idTransacao'])
                                commit_required = True
                                savedPix.append(receivedPix['idTransacao'])
                    except psycopg2.Error as error:
                        logging.info("Erro: %s", error)
            # Comita as transações no banco de dados
            if commit_required:
                logging.info("Comitando novas transações no banco.")
                conn.commit()
        else:
            ultimaPagina = True
        
        # Atualiza o timestamp da ultima consulta
        try:
            with conn.cursor() as cursor:
                lastUpdate = datetime.now()
                sqlQuery = "UPDATE settings SET value = %s WHERE key = 'ULTIMA_ATUALIZACAO'"
                cursor.execute(sqlQuery, (str(lastUpdate),))
                conn.commit()
        except psycopg2.Error as error:
            logging.info("Erro: %s", error)

        if ultimaPagina is not True:
            pagina += 1
    time.sleep(10)

handler.close()