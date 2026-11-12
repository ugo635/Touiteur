DROP TABLE touite_tag CASCADE CONSTRAINTS;
DROP TABLE abonnement_tag CASCADE CONSTRAINTS;
DROP TABLE abonnement_utilisateur CASCADE CONSTRAINTS;
DROP TABLE touite CASCADE CONSTRAINTS;
DROP TABLE tag CASCADE CONSTRAINTS;
DROP TABLE utilisateur CASCADE CONSTRAINTS;

CREATE TABLE utilisateur (
    username VARCHAR2(100) NOT NULL,
    nom VARCHAR2(100) NOT NULL,
    prenom VARCHAR2(100) NOT NULL,
    email VARCHAR2(255) NOT NULL,
    mdp VARCHAR2(255) NOT NULL,
    nb_touites NUMBER(10) DEFAULT 0 NOT NULL,
    date_creation DATE NOT NULL,

    CONSTRAINT pk_utilisateur PRIMARY KEY (username),
    CONSTRAINT uq_utilisateur_email UNIQUE (email),
    CONSTRAINT chk_nb_touites CHECK (nb_touites >= 0)
);

CREATE TABLE tag (
    id_tag NUMBER GENERATED ALWAYS AS IDENTITY,
    libelle VARCHAR2(100) NOT NULL,
    description VARCHAR2(500),

    CONSTRAINT pk_tag PRIMARY KEY (id_tag),
    CONSTRAINT uq_tag_libelle UNIQUE (libelle)
);

CREATE TABLE touite (
    id_touite NUMBER GENERATED ALWAYS AS IDENTITY,
    username VARCHAR2(100) NOT NULL,
    message VARCHAR2(235) NOT NULL,
    date_touite DATE DEFAULT SYSDATE NOT NULL,

    CONSTRAINT pk_touite PRIMARY KEY (id_touite),
    CONSTRAINT fk_touite_utilisateur
        FOREIGN KEY (username) REFERENCES utilisateur(username)
);

CREATE TABLE touite_tag (
    id_touite NUMBER NOT NULL,
    id_tag NUMBER NOT NULL,

    CONSTRAINT pk_touite_tag PRIMARY KEY (id_touite, id_tag),
    CONSTRAINT fk_tt_touite
        FOREIGN KEY (id_touite) REFERENCES touite(id_touite),
    CONSTRAINT fk_tt_tag
        FOREIGN KEY (id_tag) REFERENCES tag(id_tag)
);

CREATE TABLE abonnement_utilisateur (
    abonne VARCHAR2(100) NOT NULL,
    suivi VARCHAR2(100) NOT NULL,

    CONSTRAINT pk_abonnement_utilisateur PRIMARY KEY (abonne, suivi),
    CONSTRAINT fk_au_abonne
        FOREIGN KEY (abonne) REFERENCES utilisateur(username),
    CONSTRAINT fk_au_suivi
        FOREIGN KEY (suivi) REFERENCES utilisateur(username),
    CONSTRAINT chk_pas_soi_meme CHECK (abonne != suivi)
);

CREATE TABLE abonnement_tag (
    username VARCHAR2(100) NOT NULL,
    id_tag NUMBER NOT NULL,

    CONSTRAINT pk_abonnement_tag PRIMARY KEY (username, id_tag),
    CONSTRAINT fk_at_utilisateur
        FOREIGN KEY (username) REFERENCES utilisateur(username),
    CONSTRAINT fk_at_tag
        FOREIGN KEY (id_tag) REFERENCES tag(id_tag)
);

CREATE TABLE likes (
    username  VARCHAR2(100) NOT NULL,
    id_touite NUMBER NOT NULL,
    vote      NUMBER(3) NOT NULL,
    CONSTRAINT pk_likes PRIMARY KEY (username, id_touite),
    CONSTRAINT fk_likes_u FOREIGN KEY (username) REFERENCES utilisateur(username),
    CONSTRAINT fk_likes_t FOREIGN KEY (id_touite) REFERENCES touite(id_touite)
);

-- 1. Mettre a jour le nombre de touites d un utilisateur
CREATE OR REPLACE TRIGGER trg_touite_inc_nb
AFTER INSERT ON touite
FOR EACH ROW
BEGIN
    UPDATE utilisateur
    SET nb_touites = nb_touites + 1
    WHERE username = :NEW.username;
END;
/

-- 2. Empecher de se suivre soi-meme ou de suivre un utilisateur avec le meme email
CREATE OR REPLACE TRIGGER trg_verif_abonnement
BEFORE INSERT OR UPDATE ON abonnement_utilisateur
FOR EACH ROW
DECLARE
    v_email_abonne utilisateur.email%TYPE;
    v_email_suivi  utilisateur.email%TYPE;
BEGIN
    IF :NEW.abonne = :NEW.suivi THEN
        RAISE_APPLICATION_ERROR(-20001, 'Interdiction de s''abonner a son propre compte.');
    END IF;

    SELECT email INTO v_email_abonne FROM utilisateur WHERE username = :NEW.abonne;
    SELECT email INTO v_email_suivi  FROM utilisateur WHERE username = :NEW.suivi;

    IF v_email_abonne = v_email_suivi THEN
        RAISE_APPLICATION_ERROR(-20002, 'Interdiction de suivre un utilisateur partageant la meme adresse email.');
    END IF;
END;
/


-- 3. Empecher la modification de l auteur d un touite
CREATE OR REPLACE TRIGGER trg_touite_verif_auteur
BEFORE UPDATE OF username ON touite
FOR EACH ROW
BEGIN
    IF :NEW.username != :OLD.username THEN
        RAISE_APPLICATION_ERROR(-20003, 'Modification de l''auteur interdite : un touite conserve son createur d''origine.');
    END IF;
END;
/

-- 4. Limiter un utilisateur a 50 touites par jour
CREATE OR REPLACE TRIGGER trg_limite_touites_jour
BEFORE INSERT ON touite
FOR EACH ROW
DECLARE
    v_nb_jour NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_nb_jour
    FROM touite
    WHERE username = :NEW.username
      AND TRUNC(date_touite) = TRUNC(SYSDATE);

    IF v_nb_jour >= 50 THEN
        RAISE_APPLICATION_ERROR(-20004, 'Limite atteinte : vous ne pouvez pas publier plus de 50 touites par jour.');
    END IF;
END;
/
