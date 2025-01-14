# **Instalação no Linux**

## **Docker Engine**

Abra o terminal do Linux e digite o seguinte comando:

`#!shell sudo curl -fsSL https://get.docker.com -o get-docker.sh | sh get-docker.sh`

Esse script irá configurar os repositórios do Docker, baixar e habilitar o serviço de forma automática. Recomendo reiniciar o computador após a instalação para garantir que tudo deu certo.

Para conferir que tudo realmente deu certo, digite o comando `docker --version`, se a instalação ocorreu sem problemas você terá uma saida parecida com isso no seu terminal:

``` console title="Terminal Linux"
filipe@pop-os:~$ docker --version
Docker version 27.5.0, build a187fa5
```

## **Preparando os arquivos**

### **Docker Compose**

Primeiramente crie um novo diretório para organizar os arquivos do Pix Verifier nele. Entre na pasta e crie o `docker-compose.yaml`:

``` console title="Terminal Linux"
filipe@pop-os:~$ mkdir pix-verifier
filipe@pop-os:~$ cd pix-verifier
filipe@pop-os:~/pix-verifier$ touch docker-compose.yaml
```
Usando o editor de texto de sua preferência cole o conteúdo abaixo dentro do `docker-compose.yaml` que acabou de criar.

``` yaml title="docker-compose.yaml" linenums="1"
services:
  postgres:
    container_name: postgres
    image: postgres:16-alpine3.19
    hostname: postgres16
    restart: unless-stopped
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./sql:/docker-entrypoint-initdb.d
    environment:
      - TZ=America/Fortaleza
      - POSTGRES_USER=dbadmin
      - POSTGRES_PASSWORD=SENHA_BANCO_DE_DADOS
    ports:
      - "5432:5432"
    networks:
      - pixverifier_network

  pix-verifier:
    image: filipebezerrasantos/pix-verifier:v2.0
    hostname: pix-verifier
    container_name: pix-verifier
    restart: unless-stopped
    environment:
      - TZ=America/Fortaleza
      - DB_HOST=postgres
      - DB_NAME=pixverifier
      - DB_USER=dbadmin
      - DB_PASS=SENHA_BANCO_DE_DADOS
    volumes:
      - pixverifier_certs:/var/www/services/certs
    ports:
      - "80:80"
    networks:
      - pixverifier_network

volumes:
  postgres_data:
    name: postgres_data
  pixverifier_certs:
    name: pixverifier_certs

networks:
  pixverifier_network:
    name: pixverifier_network
```

Nesse arquivos estamos criando um serviço com dois containers, o **postgres** que é o nosso banco de dados, e o **pix-verifier**, que é o sistema em si.

Para melhorar a segurança, sugiro que altere as linhas abaixo no serviço do **postgres**:

``` yaml title="docker-compose.yaml >> postgres" linenums="10" hl_lines="4"
    environment:
        - TZ=America/Fortaleza
        - POSTGRES_USER=dbadmin
        - POSTGRES_PASSWORD=SENHA_BANCO_DE_DADOS
```

Crie uma senha segura para o Postgres, e coloque esse mesma senha nas variáveis do **pix-service**:

``` yaml title="docker-compose.yaml >> pix-service" linenums="24" hl_lines="6"
    environment:
      - TZ=America/Fortaleza
      - DB_HOST=postgres
      - DB_NAME=pixverifier
      - DB_USER=dbadmin
      - DB_PASS=SENHA_BANCO_DE_DADOS
```

Com isso finalizamos a configuração dos serviços.

---

### **Schema do banco de dados**

No mesmo diretório onde criou o `docker-compose.yaml`, crie o sub-diretório `sql` e dentro dela crie um arquivo chamado `database.sql` e cole o conteúdo abaixo:

``` console title="Terminal Linux"
filipe@pop-os:~/pix-verifier$ mkdir sql
filipe@pop-os:~/pix-verifier$ cd sql
filipe@pop-os:~/pix-verifier/sql$ touch database.sql
```

``` sql title="database.sql" linenums="1"
SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

CREATE TABLE public.bank (
    bankid integer NOT NULL,
    bankname character varying(100) NOT NULL
);


ALTER TABLE public.bank OWNER TO dbadmin;

CREATE TABLE public.bankaccount (
    accountid integer NOT NULL,
    accountname character varying(30) NOT NULL,
    bankid integer NOT NULL,
    branchnumber character varying(10) NOT NULL,
    accountnumber character varying(20) NOT NULL,
    clientid character varying(100),
    clientsecret character varying(100),
    certfile character varying(100),
    certkeyfile character varying(100),
    accesstoken character varying(100),
    tokenexpireat timestamp without time zone,
    ignoredsenders text
);


ALTER TABLE public.bankaccount OWNER TO dbadmin;

CREATE SEQUENCE public.bankaccount_accountid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bankaccount_accountid_seq OWNER TO dbadmin;

ALTER SEQUENCE public.bankaccount_accountid_seq OWNED BY public.bankaccount.accountid;

CREATE TABLE public.receivedpix (
    e2eid character varying(100) NOT NULL,
    date timestamp without time zone NOT NULL,
    value numeric(15,2) NOT NULL,
    payer character varying(100) NOT NULL,
    payerdoc character varying(100) NOT NULL,
    description character varying(140),
    payerbank character varying(100) NOT NULL,
    banktransaction text,
    accountid integer NOT NULL
);


ALTER TABLE public.receivedpix OWNER TO dbadmin;

CREATE TABLE public.users (
    user_id integer NOT NULL,
    username character varying(20) NOT NULL,
    pwd character varying(64) NOT NULL,
    useraccess integer NOT NULL
);


ALTER TABLE public.users OWNER TO dbadmin;

CREATE SEQUENCE public.users_user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_user_id_seq OWNER TO dbadmin;

ALTER SEQUENCE public.users_user_id_seq OWNED BY public.users.user_id;

ALTER TABLE ONLY public.bankaccount ALTER COLUMN accountid SET DEFAULT nextval('public.bankaccount_accountid_seq'::regclass);

ALTER TABLE ONLY public.users ALTER COLUMN user_id SET DEFAULT nextval('public.users_user_id_seq'::regclass);

ALTER TABLE ONLY public.bank
    ADD CONSTRAINT bank_pk PRIMARY KEY (bankid);

ALTER TABLE ONLY public.bankaccount
    ADD CONSTRAINT bankaccount_pk PRIMARY KEY (accountid);

ALTER TABLE ONLY public.receivedpix
    ADD CONSTRAINT receivedpix_pk PRIMARY KEY (e2eid);

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_id_pk UNIQUE (user_id);

CREATE INDEX receivedpix_accountid_idx ON public.receivedpix USING btree (accountid);

ALTER TABLE ONLY public.bankaccount
    ADD CONSTRAINT bankaccount_bank_fk FOREIGN KEY (bankid) REFERENCES public.bank(bankid) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE ONLY public.receivedpix
    ADD CONSTRAINT receivedpix_bankaccount_fk FOREIGN KEY (accountid) REFERENCES public.bankaccount(accountid) ON UPDATE CASCADE ON DELETE CASCADE;

INSERT INTO public.users (username, pwd, useraccess) 
VALUES ('admin', '$2y$10$Ve1qGNP9X.7nThu4LXOwVuWc57jN3PhvYl.b/ABFEuw8nE3LPSDVe', 2);

```

Esse arquivo vai criar as tabelas necessárias no banco dados que o Pix Verifier precisa para funcionar. Ao final sua estrutura de diretórios deve estar mais ou menos assim:

```
pix-verifier/
├─ sql/
│  ├─ database.sql
├─ docker-compose.yaml
```

---

## **Iniciando o serviço**

Ainda dentro do diretório `pix-verifier` digite o comando `docker compose up -d` para inicar os containers.

``` console title="Terminal Linux"
filipe@pop-os:~/pix-verifier$ docker compose up -d
```

O Docker vai baixar as imagens necessárias e inicar os container, quando esse processo terminal, abra seu navegador e digite `http://localhost` se aparecer a página inicial do Pix Verifier significa que os containers subiram corretamente.