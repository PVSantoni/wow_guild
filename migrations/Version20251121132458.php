<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121132458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `character` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, character_name VARCHAR(255) NOT NULL, character_realm_slug VARCHAR(255) NOT NULL, character_region VARCHAR(255) NOT NULL, INDEX IDX_937AB034A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB034A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD active_character_id INT DEFAULT NULL, DROP character_name, DROP character_realm_slug, DROP character_region');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649F06CD998 FOREIGN KEY (active_character_id) REFERENCES `character` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F06CD998 ON user (active_character_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649F06CD998');
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB034A76ED395');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP INDEX UNIQ_8D93D649F06CD998 ON user');
        $this->addSql('ALTER TABLE user ADD character_name VARCHAR(255) DEFAULT NULL, ADD character_realm_slug VARCHAR(255) DEFAULT NULL, ADD character_region VARCHAR(20) DEFAULT NULL, DROP active_character_id');
    }
}
