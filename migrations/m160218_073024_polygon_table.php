<?php

use yii\db\Migration;

class m160218_073024_polygon_table extends Migration
{
    public function up()
    {
        $this->createTable(
            '{{%polygon}}',
            [
                'id' => $this->primaryKey(),
                'zip' => $this->integer(),
                'type' => "ENUM('outer', 'inner')",
                'maxLon' => $this->float(),
                'maxLat' => $this->float(),
                'minLon' => $this->float(),
                'minLat' => $this->float(),
            ]
        );
    }

    public function down()
    {
        echo "m160218_073024_polygon_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
