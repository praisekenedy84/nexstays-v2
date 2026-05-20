-- Run once as a PostgreSQL superuser (e.g. postgres) in pgAdmin or psql:
--   psql -U postgres -f database/scripts/setup-local-postgres.sql

CREATE USER nexstay WITH PASSWORD 'nexstay';
CREATE DATABASE nexstay OWNER nexstay;
GRANT ALL PRIVILEGES ON DATABASE nexstay TO nexstay;

\c nexstay
GRANT ALL ON SCHEMA public TO nexstay;
