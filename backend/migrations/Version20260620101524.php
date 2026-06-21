<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260620101524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade unaccent + f_unaccent immutable y reindexa los trgm de romaji/english para búsqueda insensible a acentos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS unaccent');

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION f_unaccent(text)
              RETURNS text
              LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
              SELECT unaccent('public.unaccent', $1)
            $func$
            SQL);

        $this->addSql('DROP INDEX series_romaji_trgm');
        $this->addSql('DROP INDEX series_english_trgm');
        $this->addSql('CREATE INDEX series_romaji_trgm ON series USING GIN (f_unaccent(LOWER(romaji_name)) gin_trgm_ops)');
        $this->addSql("CREATE INDEX series_english_trgm ON series USING GIN (f_unaccent(LOWER(COALESCE(english_name, ''))) gin_trgm_ops)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX series_romaji_trgm');
        $this->addSql('DROP INDEX series_english_trgm');
        $this->addSql('DROP FUNCTION IF EXISTS f_unaccent(text)');
        $this->addSql('DROP EXTENSION IF EXISTS unaccent');
        $this->addSql('CREATE INDEX series_romaji_trgm ON series USING GIN (LOWER(romaji_name) gin_trgm_ops)');
        $this->addSql("CREATE INDEX series_english_trgm ON series USING GIN (LOWER(COALESCE(english_name, '')) gin_trgm_ops)");
    }
}
