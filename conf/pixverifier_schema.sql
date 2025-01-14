--
-- PostgreSQL database dump
--

-- Dumped from database version 16.3
-- Dumped by pg_dump version 16.3

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

--
-- Name: bank; Type: TABLE; Schema: public; Owner: dbadmin
--

CREATE TABLE public.bank (
    bankid integer NOT NULL,
    bankname character varying(100) NOT NULL
);


ALTER TABLE public.bank OWNER TO dbadmin;

--
-- Name: bankaccount; Type: TABLE; Schema: public; Owner: dbadmin
--

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

--
-- Name: bankaccount_accountid_seq; Type: SEQUENCE; Schema: public; Owner: dbadmin
--

CREATE SEQUENCE public.bankaccount_accountid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bankaccount_accountid_seq OWNER TO dbadmin;

--
-- Name: bankaccount_accountid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: dbadmin
--

ALTER SEQUENCE public.bankaccount_accountid_seq OWNED BY public.bankaccount.accountid;


--
-- Name: receivedpix; Type: TABLE; Schema: public; Owner: dbadmin
--

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

--
-- Name: bankaccount accountid; Type: DEFAULT; Schema: public; Owner: dbadmin
--

ALTER TABLE ONLY public.bankaccount ALTER COLUMN accountid SET DEFAULT nextval('public.bankaccount_accountid_seq'::regclass);


--
-- Name: bank bank_pk; Type: CONSTRAINT; Schema: public; Owner: dbadmin
--

ALTER TABLE ONLY public.bank
    ADD CONSTRAINT bank_pk PRIMARY KEY (bankid);


--
-- Name: bankaccount bankaccount_pk; Type: CONSTRAINT; Schema: public; Owner: dbadmin
--

ALTER TABLE ONLY public.bankaccount
    ADD CONSTRAINT bankaccount_pk PRIMARY KEY (accountid);


--
-- Name: receivedpix receivedpix_pk; Type: CONSTRAINT; Schema: public; Owner: dbadmin
--

ALTER TABLE ONLY public.receivedpix
    ADD CONSTRAINT receivedpix_pk PRIMARY KEY (e2eid);


--
-- Name: receivedpix_accountid_idx; Type: INDEX; Schema: public; Owner: dbadmin
--

CREATE INDEX receivedpix_accountid_idx ON public.receivedpix USING btree (accountid);


--
-- Name: bankaccount bankaccount_bank_fk; Type: FK CONSTRAINT; Schema: public; Owner: dbadmin
--

ALTER TABLE ONLY public.bankaccount
    ADD CONSTRAINT bankaccount_bank_fk FOREIGN KEY (bankid) REFERENCES public.bank(bankid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: receivedpix receivedpix_bankaccount_fk; Type: FK CONSTRAINT; Schema: public; Owner: dbadmin
--

ALTER TABLE ONLY public.receivedpix
    ADD CONSTRAINT receivedpix_bankaccount_fk FOREIGN KEY (accountid) REFERENCES public.bankaccount(accountid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

