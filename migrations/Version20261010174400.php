<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010174400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track whether the character banner 50/50 was won or lost.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE banner_target ADD fifty_fifty_result VARCHAR(8) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE banner_target DROP fifty_fifty_result');
    }
}
