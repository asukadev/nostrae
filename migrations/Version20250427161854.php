<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427161854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE organizer_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, motivation LONGTEXT NOT NULL, status VARCHAR(30) NOT NULL, requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_72A63856A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE organizer_request ADD CONSTRAINT FK_72A63856A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_3BAE0AA7989D9B62 ON event (slug)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE organizer_request DROP FOREIGN KEY FK_72A63856A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE organizer_request
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_3BAE0AA7989D9B62 ON event
        SQL);
    }
}
