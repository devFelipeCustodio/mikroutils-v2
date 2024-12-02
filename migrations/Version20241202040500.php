<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241202040500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_session RENAME COLUMN user_id TO user_id_id');
        $this->addSql('ALTER TABLE user_session ADD CONSTRAINT FK_8849CBDE9D86650F FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8849CBDE9D86650F ON user_session (user_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_session DROP CONSTRAINT FK_8849CBDE9D86650F');
        $this->addSql('DROP INDEX IDX_8849CBDE9D86650F');
        $this->addSql('ALTER TABLE user_session RENAME COLUMN user_id_id TO user_id');
    }
}
