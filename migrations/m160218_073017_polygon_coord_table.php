<?php

use yii\db\Migration;

class m160218_073017_polygon_coord_table extends Migration
{
    public function up()
    {
        $this->createTable(
            '{{%polygon_coord}}',
            [
                'id' => $this->primaryKey(),
                'polygon_id' => $this->integer(),
                'lat' => $this->float(),
                'lon' => $this->float(),
            ]
        );
    }

    public function down()
    {
        echo "m160218_073017_polygon_coord_table cannot be reverted.\n";

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
