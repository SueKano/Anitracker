<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730140518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade is_rewatching a user_series para marcar los re-visionados de series completadas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_series ADD is_rewatching BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_series DROP is_rewatching');
    }
}