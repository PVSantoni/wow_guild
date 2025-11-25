<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125104401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE character_class (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, api_key VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialization (id INT AUTO_INCREMENT NOT NULL, character_class_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_9ED9F26AB201E281 (character_class_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE specialization ADD CONSTRAINT FK_9ED9F26AB201E281 FOREIGN KEY (character_class_id) REFERENCES character_class (id)');
        $this->addSql('ALTER TABLE inscription ADD specialization_id INT DEFAULT NULL, DROP specialisation');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6FA846217 FOREIGN KEY (specialization_id) REFERENCES specialization (id)');
        $this->addSql('CREATE INDEX IDX_5E90F6D6FA846217 ON inscription (specialization_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6FA846217');
        $this->addSql('ALTER TABLE specialization DROP FOREIGN KEY FK_9ED9F26AB201E281');
        $this->addSql('DROP TABLE character_class');
        $this->addSql('DROP TABLE specialization');
        $this->addSql('DROP INDEX IDX_5E90F6D6FA846217 ON inscription');
        $this->addSql('ALTER TABLE inscription ADD specialisation VARCHAR(255) DEFAULT NULL, DROP specialization_id');
    }
}
