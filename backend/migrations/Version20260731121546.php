<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731121546 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_series ADD score INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user_series ADD imported_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("ALTER TABLE series ADD tags JSON DEFAULT '[]' NOT NULL");
        $this->addSql("ALTER TABLE series ADD studios JSON DEFAULT '[]' NOT NULL");

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_series DROP score');
        $this->addSql('ALTER TABLE user_series DROP imported_at');
        $this->addSql('ALTER TABLE series DROP tags');
        $this->addSql('ALTER TABLE series DROP studios');
    }
}
