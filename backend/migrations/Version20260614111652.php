<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260614111652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE series ADD is_adult BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE series ADD id_mal INT DEFAULT NULL');
        $this->addSql("ALTER TABLE users ADD roles JSON DEFAULT '[]' NOT NULL");
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL PRIMARY KEY, sess_data BYTEA NOT NULL, sess_lifetime INTEGER NOT NULL,sess_time INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX sess_lifetime_idx ON sessions (sess_lifetime)');
        $this->addSql('ALTER TABLE user_episode_watch DROP CONSTRAINT fk_858f1122a76ed395');
        $this->addSql('ALTER TABLE user_episode_watch ADD CONSTRAINT FK_858F1122A76ED395 FOREIGN KEY (user_id) REFERENCES "users" (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE series DROP is_adult');
        $this->addSql('ALTER TABLE series DROP id_mal');
        $this->addSql('ALTER TABLE "users" DROP roles');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('ALTER TABLE user_episode_watch DROP CONSTRAINT FK_858F1122A76ED395');
        $this->addSql('ALTER TABLE user_episode_watch ADD CONSTRAINT fk_858f1122a76ed395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
