<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade has_declared_total_episodes para distinguir el total que declara AniList del deducido del calendario';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series ADD has_declared_total_episodes BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series DROP has_declared_total_episodes');
    }
}
