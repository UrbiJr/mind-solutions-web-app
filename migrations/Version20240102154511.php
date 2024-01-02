<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240102154511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE InventoryItems (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, viagogo_event_id VARCHAR(256) DEFAULT NULL, viagogo_category_id VARCHAR(256) DEFAULT NULL, name VARCHAR(256) DEFAULT NULL, event_date DATETIME DEFAULT NULL, purchase_date DATETIME DEFAULT NULL, country VARCHAR(256) DEFAULT NULL, city VARCHAR(256) DEFAULT NULL, location VARCHAR(256) DEFAULT NULL, section VARCHAR(256) DEFAULT NULL, `row` VARCHAR(256) DEFAULT NULL, seatFrom VARCHAR(10) DEFAULT NULL, seatTo VARCHAR(10) DEFAULT NULL, ticketType VARCHAR(256) DEFAULT NULL, ticketGenre VARCHAR(256) DEFAULT NULL, retailer VARCHAR(256) DEFAULT NULL, individual_ticket_cost JSON DEFAULT NULL, order_number VARCHAR(256) DEFAULT NULL, order_email VARCHAR(256) DEFAULT NULL, status VARCHAR(256) DEFAULT NULL, sale_end_date DATETIME DEFAULT NULL, your_price_per_ticket JSON DEFAULT NULL, total_payout JSON DEFAULT NULL, quantity INT DEFAULT NULL, quantity_remain INT DEFAULT NULL, date_last_modified DATETIME DEFAULT NULL, platform VARCHAR(256) DEFAULT NULL, sale_date DATETIME DEFAULT NULL, sale_id VARCHAR(256) DEFAULT NULL, listing_id VARCHAR(256) DEFAULT NULL, restrictions JSON DEFAULT NULL, ticket_details JSON DEFAULT NULL, INDEX fk_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE InventoryValues (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(64) DEFAULT NULL, timestamp DATETIME DEFAULT NULL, value INT DEFAULT NULL, INDEX fk_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE InventoryItems ADD CONSTRAINT FK_FA3801A4A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)');
        $this->addSql('ALTER TABLE InventoryValues ADD CONSTRAINT FK_E36A30D2A76ED395 FOREIGN KEY (user_id) REFERENCES Users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE InventoryItems DROP FOREIGN KEY FK_FA3801A4A76ED395');
        $this->addSql('ALTER TABLE InventoryValues DROP FOREIGN KEY FK_E36A30D2A76ED395');
        $this->addSql('DROP TABLE InventoryItems');
        $this->addSql('DROP TABLE InventoryValues');
    }
}
