<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240104121228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Backups (id INT AUTO_INCREMENT NOT NULL, user INT DEFAULT NULL, timestamp DATETIME DEFAULT NULL, backup_data JSON DEFAULT NULL, INDEX user_fk (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Backups ADD CONSTRAINT FK_BECE9858D93D649 FOREIGN KEY (user) REFERENCES Users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Backups DROP FOREIGN KEY FK_BECE9858D93D649');
        $this->addSql('DROP TABLE Backups');
    }
}
