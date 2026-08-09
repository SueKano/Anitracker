<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade portrait_mirror_url para servir las portadas desde R2 en vez de AniList';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series ADD portrait_mirror_url TEXT DEFAULT NULL');
        $this->addSql("ALTER TABLE series ADD airing_schedule JSON DEFAULT '[]' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series DROP portrait_mirror_url');
        $this->addSql('ALTER TABLE series DROP airing_schedule');

    }
}
