--
-- PostgreSQL database dump
--

-- Dumped from database version 16.1
-- Dumped by pg_dump version 16.2 (Ubuntu 16.2-1.pgdg22.04+1)

-- Started on 2024-03-06 18:41:26 -03

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
-- TOC entry 3417 (class 1262 OID 16389)
-- Name: pix; Type: DATABASE; Schema: -; Owner: -
--

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

--
-- TOC entry 3418 (class 0 OID 0)
-- Dependencies: 4
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'standard public schema';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 218 (class 1259 OID 16422)
-- Name: inter; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inter (
    clientid character varying,
    clientsecret character varying,
    oauthtoken character varying,
    tokencreation timestamp without time zone
);


--
-- TOC entry 215 (class 1259 OID 16390)
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
-- TOC entry 216 (class 1259 OID 16395)
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    key character varying NOT NULL,
    value character varying
);


--
-- TOC entry 217 (class 1259 OID 16404)
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    user_id uuid DEFAULT gen_random_uuid() NOT NULL,
    username character varying NOT NULL,
    user_pwd character varying NOT NULL,
    role character varying(10) DEFAULT USER
);


--
-- TOC entry 3411 (class 0 OID 16422)
-- Dependencies: 218
-- Data for Name: inter; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.inter VALUES (NULL, NULL, NULL, NULL);


--
-- TOC entry 3408 (class 0 OID 16390)
-- Dependencies: 215
-- Data for Name: receivedpix; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 3409 (class 0 OID 16395)
-- Dependencies: 216
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.settings VALUES ('ignoredList', '');
INSERT INTO public.settings VALUES ('ULTIMA_ATUALIZACAO', '');


--
-- TOC entry 3410 (class 0 OID 16404)
-- Dependencies: 217
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users VALUES ('7e67a531-15e7-429e-ac2a-a21024b89464', 'admin', '$2y$10$AVf3DEkvsBgfN5GxnFFHz.yU/QKLWPGnx3YT1I/8UTGU9J95kk8aq', 'admin');


--
-- TOC entry 3260 (class 2606 OID 16401)
-- Name: receivedpix receivedpix_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.receivedpix
    ADD CONSTRAINT receivedpix_pk PRIMARY KEY (idtransacao);


--
-- TOC entry 3262 (class 2606 OID 16403)
-- Name: settings settings_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pk PRIMARY KEY (key);


--
-- TOC entry 3264 (class 2606 OID 16411)
-- Name: users users_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_unique UNIQUE (user_id);


-- Completed on 2024-03-06 18:41:26 -03

--
-- PostgreSQL database dump complete
--

