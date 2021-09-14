<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210913121912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trick CHANGE videos videos VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE FULLTEXT INDEX IDX_D8F0A91E5E237E066DE44026E7C3D1BB ON trick (name, description, overview)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_D8F0A91E5E237E066DE44026E7C3D1BB ON trick');
        $this->addSql('ALTER TABLE trick CHANGE videos videos LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\'');
    }
}
