CREATE TABLE users (
    id bigserial NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0),
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0),
    updated_at timestamp(0),
    CONSTRAINT users_pkey PRIMARY KEY ("id")
);

ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email);

CREATE TABLE password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0),
    CONSTRAINT password_reset_tokens_pkey PRIMARY KEY ("email")
);

CREATE TABLE failed_jobs (
    id bigserial NOT NULL,
    "uuid" character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT failed_jobs_pkey PRIMARY KEY ("id")
);

ALTER TABLE failed_jobs ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);

CREATE TABLE personal_access_tokens (
    id bigserial NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0),
    expires_at timestamp(0),
    created_at timestamp(0),
    updated_at timestamp(0),
    CONSTRAINT personal_access_tokens_pkey PRIMARY KEY ("id")
);

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens USING btree (tokenable_type, tokenable_id);

ALTER TABLE personal_access_tokens ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);
