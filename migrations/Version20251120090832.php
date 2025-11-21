<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120090832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bis_item (id INT AUTO_INCREMENT NOT NULL, bis_list_id INT NOT NULL, slot VARCHAR(255) DEFAULT NULL, item_id INT NOT NULL, item_name VARCHAR(255) DEFAULT NULL, INDEX IDX_E068ACEEAB276620 (bis_list_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bis_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, character_class VARCHAR(255) NOT NULL, specialization VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bis_item ADD CONSTRAINT FK_E068ACEEAB276620 FOREIGN KEY (bis_list_id) REFERENCES bis_list (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bis_item DROP FOREIGN KEY FK_E068ACEEAB276620');
        $this->addSql('DROP TABLE bis_item');
        $this->addSql('DROP TABLE bis_list');
    }
}
