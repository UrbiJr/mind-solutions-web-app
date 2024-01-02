<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240102163620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE InventoryItems ADD seat_from VARCHAR(10) DEFAULT NULL, ADD seat_to VARCHAR(10) DEFAULT NULL, ADD ticket_type VARCHAR(256) DEFAULT NULL, ADD ticket_genre VARCHAR(256) DEFAULT NULL, DROP seatFrom, DROP seatTo, DROP ticketType, DROP ticketGenre');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE InventoryItems ADD seatFrom VARCHAR(10) DEFAULT NULL, ADD seatTo VARCHAR(10) DEFAULT NULL, ADD ticketType VARCHAR(256) DEFAULT NULL, ADD ticketGenre VARCHAR(256) DEFAULT NULL, DROP seat_from, DROP seat_to, DROP ticket_type, DROP ticket_genre');
    }
}
