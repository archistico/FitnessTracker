<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial fitness domain tables: default user, gym profile, equipment, gym equipment and exercises.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, is_default BOOLEAN NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE gym_profile (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, app_user_id INTEGER NOT NULL, name VARCHAR(120) NOT NULL, notes CLOB DEFAULT NULL, CONSTRAINT FK_8D0A042B3B565112 FOREIGN KEY (app_user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8D0A042B3B565112 ON gym_profile (app_user_id)');
        $this->addSql('CREATE TABLE equipment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL, type VARCHAR(255) NOT NULL, description CLOB NOT NULL, usage_instructions CLOB DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, is_machine BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_193BF271989D9B62 ON equipment (slug)');
        $this->addSql('CREATE TABLE gym_equipment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, gym_profile_id INTEGER NOT NULL, equipment_id INTEGER NOT NULL, is_available BOOLEAN NOT NULL, notes CLOB DEFAULT NULL, CONSTRAINT FK_40D0CFC9D520C4E1 FOREIGN KEY (gym_profile_id) REFERENCES gym_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_40D0CFC9517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_40D0CFC9D520C4E1 ON gym_equipment (gym_profile_id)');
        $this->addSql('CREATE INDEX IDX_40D0CFC9517FE9FE ON gym_equipment (equipment_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_gym_equipment ON gym_equipment (gym_profile_id, equipment_id)');
        $this->addSql('CREATE TABLE exercise (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, default_equipment_id INTEGER DEFAULT NULL, name VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, description CLOB NOT NULL, execution_instructions CLOB DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, primary_muscles CLOB NOT NULL --(DC2Type:json)
        , secondary_muscles CLOB NOT NULL --(DC2Type:json)
        , tracking_mode VARCHAR(255) NOT NULL, exercise_type VARCHAR(255) NOT NULL, secondary_equipment_notes CLOB DEFAULT NULL, default_increment_kg DOUBLE PRECISION NOT NULL, is_fundamental BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_15E9E7AD87849E95 FOREIGN KEY (default_equipment_id) REFERENCES equipment (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_15E9E7AD87849E95 ON exercise (default_equipment_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_15E9E7AD989D9B62 ON exercise (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE exercise');
        $this->addSql('DROP TABLE gym_equipment');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE gym_profile');
        $this->addSql('DROP TABLE app_user');
    }
}
