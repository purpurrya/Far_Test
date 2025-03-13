<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313112110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assignments (id INT AUTO_INCREMENT NOT NULL, machine_id INT DEFAULT NULL, process_id INT DEFAULT NULL, INDEX IDX_308A50DDF6B75B26 (machine_id), INDEX IDX_308A50DD7EC2F574 (process_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE machines (id INT AUTO_INCREMENT NOT NULL, total_memory INT NOT NULL, total_cpu INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE processes (id INT AUTO_INCREMENT NOT NULL, required_memory INT NOT NULL, required_cpu INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE assignments ADD CONSTRAINT FK_308A50DDF6B75B26 FOREIGN KEY (machine_id) REFERENCES machines (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE assignments ADD CONSTRAINT FK_308A50DD7EC2F574 FOREIGN KEY (process_id) REFERENCES processes (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignments DROP FOREIGN KEY FK_308A50DDF6B75B26');
        $this->addSql('ALTER TABLE assignments DROP FOREIGN KEY FK_308A50DD7EC2F574');
        $this->addSql('DROP TABLE assignments');
        $this->addSql('DROP TABLE machines');
        $this->addSql('DROP TABLE processes');
    }
}
