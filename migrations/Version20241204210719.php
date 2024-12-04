<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204210719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE client_export_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE client_search_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE log_search_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_session_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE client_export (id INT NOT NULL, user_id INT DEFAULT NULL, hosts TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_172A5D27A76ED395 ON client_export (user_id)');
        $this->addSql('COMMENT ON COLUMN client_export.hosts IS \'(DC2Type:simple_array)\'');
        $this->addSql('COMMENT ON COLUMN client_export.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE client_search (id INT NOT NULL, user_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, hosts TEXT NOT NULL, query VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E1569014A76ED395 ON client_search (user_id)');
        $this->addSql('COMMENT ON COLUMN client_search.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN client_search.hosts IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE log_search (id INT NOT NULL, user_id INT NOT NULL, query VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, hosts TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A10A8805A76ED395 ON log_search (user_id)');
        $this->addSql('COMMENT ON COLUMN log_search.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN log_search.hosts IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, allowed_host_ids TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON "user" (username)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".allowed_host_ids IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE user_session (id INT NOT NULL, user_id INT NOT NULL, user_agent VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ip VARCHAR(255) NOT NULL, session_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8849CBDEA76ED395 ON user_session (user_id)');
        $this->addSql('COMMENT ON COLUMN user_session.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL, sess_data BYTEA NOT NULL, sess_lifetime INT NOT NULL, sess_time INT NOT NULL, PRIMARY KEY(sess_id))');
        $this->addSql('CREATE INDEX sess_lifetime_idx ON sessions (sess_lifetime)');
        $this->addSql('ALTER TABLE client_export ADD CONSTRAINT FK_172A5D27A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_search ADD CONSTRAINT FK_E1569014A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE log_search ADD CONSTRAINT FK_A10A8805A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_session ADD CONSTRAINT FK_8849CBDEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE client_export_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE client_search_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE log_search_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE user_session_id_seq CASCADE');
        $this->addSql('ALTER TABLE client_export DROP CONSTRAINT FK_172A5D27A76ED395');
        $this->addSql('ALTER TABLE client_search DROP CONSTRAINT FK_E1569014A76ED395');
        $this->addSql('ALTER TABLE log_search DROP CONSTRAINT FK_A10A8805A76ED395');
        $this->addSql('ALTER TABLE user_session DROP CONSTRAINT FK_8849CBDEA76ED395');
        $this->addSql('DROP TABLE client_export');
        $this->addSql('DROP TABLE client_search');
        $this->addSql('DROP TABLE log_search');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_session');
        $this->addSql('DROP TABLE sessions');
    }
}
