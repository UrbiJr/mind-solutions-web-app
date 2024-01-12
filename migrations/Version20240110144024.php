<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240110144024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Backups (id INT AUTO_INCREMENT NOT NULL, user INT DEFAULT NULL, captcha_provider_id INT DEFAULT NULL, timestamp DATETIME DEFAULT NULL, first_name VARCHAR(256) DEFAULT NULL, last_name VARCHAR(256) DEFAULT NULL, connections JSON DEFAULT NULL, about VARCHAR(160) DEFAULT NULL, currency VARCHAR(10) DEFAULT \'EUR\' NOT NULL, captcha_provider_api_key VARCHAR(256) DEFAULT NULL, INDEX fk_captcha_provider_id (captcha_provider_id), INDEX user_fk (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE CaptchaProviders (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE InventoryItems (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, viagogo_event_id VARCHAR(256) DEFAULT NULL, viagogo_category_id VARCHAR(256) DEFAULT NULL, name VARCHAR(256) DEFAULT NULL, event_date DATETIME DEFAULT NULL, purchase_date DATETIME DEFAULT NULL, country VARCHAR(256) DEFAULT NULL, city VARCHAR(256) DEFAULT NULL, location VARCHAR(256) DEFAULT NULL, section VARCHAR(256) DEFAULT NULL, `row` VARCHAR(256) DEFAULT NULL, seat_from VARCHAR(10) DEFAULT NULL, seat_to VARCHAR(10) DEFAULT NULL, floor_seats INT DEFAULT NULL, ticket_type VARCHAR(256) DEFAULT NULL, ticket_genre VARCHAR(256) DEFAULT NULL, retailer VARCHAR(256) DEFAULT NULL, individual_ticket_cost JSON DEFAULT NULL, order_number VARCHAR(256) DEFAULT NULL, order_email VARCHAR(256) DEFAULT NULL, status VARCHAR(256) DEFAULT NULL, sale_end_date DATETIME DEFAULT NULL, your_price_per_ticket JSON DEFAULT NULL, total_payout JSON DEFAULT NULL, quantity_remain INT DEFAULT NULL, date_last_modified DATETIME DEFAULT NULL, platform VARCHAR(256) DEFAULT NULL, sale_date DATETIME DEFAULT NULL, sale_id VARCHAR(256) DEFAULT NULL, listing_id VARCHAR(256) DEFAULT NULL, restrictions JSON DEFAULT NULL, ticket_details JSON DEFAULT NULL, INDEX fk_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE InventoryValues (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(64) DEFAULT NULL, timestamp DATETIME DEFAULT NULL, value INT DEFAULT NULL, INDEX fk_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Releases (id INT AUTO_INCREMENT NOT NULL, author INT DEFAULT NULL, country_code VARCHAR(4) DEFAULT NULL, city VARCHAR(256) DEFAULT NULL, location VARCHAR(256) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, event_date DATETIME DEFAULT NULL, release_date DATETIME DEFAULT NULL, retailer VARCHAR(256) DEFAULT NULL, early_link VARCHAR(256) DEFAULT NULL, comments VARCHAR(512) DEFAULT NULL, INDEX create_from_fk (author), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE SectionLists (id INT AUTO_INCREMENT NOT NULL, event_id VARCHAR(255) NOT NULL, sections JSON DEFAULT NULL, UNIQUE INDEX unique_event_id (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Users (id INT AUTO_INCREMENT NOT NULL, captcha_provider_id INT DEFAULT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, first_name VARCHAR(256) DEFAULT NULL, last_name VARCHAR(256) DEFAULT NULL, username VARCHAR(256) NOT NULL, password VARCHAR(256) NOT NULL, secret_code VARCHAR(256) DEFAULT NULL, license_key VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, discord_id VARCHAR(20) DEFAULT NULL, discord_username VARCHAR(32) DEFAULT NULL, whop_manage_url VARCHAR(256) DEFAULT NULL, image_url VARCHAR(256) DEFAULT NULL, connections JSON DEFAULT NULL, about VARCHAR(160) DEFAULT NULL, currency VARCHAR(10) DEFAULT \'EUR\' NOT NULL, captcha_provider_api_key VARCHAR(256) DEFAULT NULL, roles JSON NOT NULL, INDEX fk_captcha_provider_id (captcha_provider_id), UNIQUE INDEX username (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Backups ADD CONSTRAINT FK_BECE9858D93D649 FOREIGN KEY (user) REFERENCES Users (id)');
        $this->addSql('ALTER TABLE Backups ADD CONSTRAINT FK_BECE9856531FBC3 FOREIGN KEY (captcha_provider_id) REFERENCES CaptchaProviders (id)');
        $this->addSql('ALTER TABLE InventoryItems ADD CONSTRAINT FK_FA3801A4A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)');
        $this->addSql('ALTER TABLE InventoryValues ADD CONSTRAINT FK_E36A30D2A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)');
        $this->addSql('ALTER TABLE Releases ADD CONSTRAINT FK_81E08687BDAFD8C8 FOREIGN KEY (author) REFERENCES Users (id)');
        $this->addSql('ALTER TABLE Users ADD CONSTRAINT FK_D5428AED6531FBC3 FOREIGN KEY (captcha_provider_id) REFERENCES CaptchaProviders (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Backups DROP FOREIGN KEY FK_BECE9858D93D649');
        $this->addSql('ALTER TABLE Backups DROP FOREIGN KEY FK_BECE9856531FBC3');
        $this->addSql('ALTER TABLE InventoryItems DROP FOREIGN KEY FK_FA3801A4A76ED395');
        $this->addSql('ALTER TABLE InventoryValues DROP FOREIGN KEY FK_E36A30D2A76ED395');
        $this->addSql('ALTER TABLE Releases DROP FOREIGN KEY FK_81E08687BDAFD8C8');
        $this->addSql('ALTER TABLE Users DROP FOREIGN KEY FK_D5428AED6531FBC3');
        $this->addSql('DROP TABLE Backups');
        $this->addSql('DROP TABLE CaptchaProviders');
        $this->addSql('DROP TABLE InventoryItems');
        $this->addSql('DROP TABLE InventoryValues');
        $this->addSql('DROP TABLE Releases');
        $this->addSql('DROP TABLE SectionLists');
        $this->addSql('DROP TABLE Users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
