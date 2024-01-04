<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240104145452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Backups ADD captcha_provider_id INT DEFAULT NULL, ADD first_name VARCHAR(256) DEFAULT NULL, ADD last_name VARCHAR(256) DEFAULT NULL, ADD about VARCHAR(160) DEFAULT NULL, ADD currency VARCHAR(10) DEFAULT \'EUR\' NOT NULL, ADD captcha_provider_api_key VARCHAR(256) DEFAULT NULL, CHANGE backup_data connections JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE Backups ADD CONSTRAINT FK_BECE9856531FBC3 FOREIGN KEY (captcha_provider_id) REFERENCES CaptchaProviders (id)');
        $this->addSql('CREATE INDEX fk_captcha_provider_id ON Backups (captcha_provider_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Backups DROP FOREIGN KEY FK_BECE9856531FBC3');
        $this->addSql('DROP INDEX fk_captcha_provider_id ON Backups');
        $this->addSql('ALTER TABLE Backups DROP captcha_provider_id, DROP first_name, DROP last_name, DROP about, DROP currency, DROP captcha_provider_api_key, CHANGE connections backup_data JSON DEFAULT NULL');
    }
}
