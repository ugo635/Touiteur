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
    nb_touites NUMBER(10) DEFAULT 0 NOT NULL,

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