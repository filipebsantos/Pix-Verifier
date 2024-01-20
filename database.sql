--
-- PostgreSQL database dump
--

-- Dumped from database version 16.0 (Debian 16.0-1.pgdg110+1)
-- Dumped by pg_dump version 16.1 (Ubuntu 16.1-1.pgdg22.04+1)

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

--
-- TOC entry 3335 (class 1262 OID 23561)
-- Name: pix; Type: DATABASE; Schema: -; Owner: -
--

CREATE DATABASE pix WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'en_US.utf8';


\connect pix

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

--
-- TOC entry 4 (class 2615 OID 2200)
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA public;


--
-- TOC entry 3336 (class 0 OID 0)
-- Dependencies: 4
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'standard public schema';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 216 (class 1259 OID 31320)
-- Name: receivedpix; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.receivedpix (
    e2eid character varying NOT NULL,
    datainclusao timestamp without time zone NOT NULL,
    valor numeric(7,2) NOT NULL,
    nomepagador character varying NOT NULL,
    descricaopix character varying,
    cpfcnpjpagador character varying NOT NULL,
    nomeempresapagador character varying NOT NULL,
    idtransacao character varying NOT NULL
);


--
-- TOC entry 215 (class 1259 OID 28160)
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    key character varying NOT NULL,
    value character varying
);


--
-- TOC entry 3186 (class 2606 OID 32564)
-- Name: receivedpix receivedpix_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.receivedpix
    ADD CONSTRAINT receivedpix_pk PRIMARY KEY (idtransacao);


--
-- TOC entry 3184 (class 2606 OID 28166)
-- Name: settings settings_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pk PRIMARY KEY (key);


-- Completed on 2024-01-08 19:47:47 -03

--
-- PostgreSQL database dump complete
--

