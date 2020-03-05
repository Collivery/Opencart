<?php

class ModelExtensionShippingCollivery extends Model {

    private $columns = array(
        'order' => array(
            'collivery_from_address_id',
            'collivery_from_contact_id',
            'collivery_to_address_id',
            'collivery_to_contact_id',
            'collivery_town',
            'collivery_suburb',
            'collivery_location_type',
            'collivery_price_data',
            'collivery_service_type_id',
            'waybill_id'
        ),
        'address' => array(
            'collivery_town',
            'collivery_suburb',
            'collivery_location_type',
        )
    );

    public function addColumns() {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->rowQuery($table, $column)) {
                    continue;
                }
                $this->addColumn($table, $column);
            }
        }

    }

    public function dropColumns() {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->rowQuery($table, $column)) {
                    $this->dropColumn($table, $column);
                }
            }
        }
    }

    private function addColumn($table, $column)
    {
        $this->db->query("ALTER TABLE `".DB_PREFIX."{$table}` ADD `{$column}` VARCHAR(255) NULL DEFAULT NULL;");
    }

    private function dropColumn($table, $column)
    {
        $this->db->query("ALTER TABLE `".DB_PREFIX."{$table}` DROP COLUMN `{$column}`;");
    }

    private function rowQuery($table, $column)
    {
        $o= $this->db->query("SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME` = '".DB_PREFIX."{$table}' AND `TABLE_SCHEMA` = '".DB_DATABASE."' AND `COLUMN_NAME` = '{$column}';");
        return $o->num_rows > 0;
    }


}

