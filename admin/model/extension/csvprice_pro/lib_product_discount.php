<?php

/**
 * ModelExtensionCSVPriceProLibProductDiscount class
 */
class ModelExtensionCSVPriceProLibProductDiscount extends Model
{
    /**
     * @var array
     */
    private static $sharedInstance = array();

    /**
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        if (false === isset(self::$sharedInstance['core_config'])) {
            self::$sharedInstance['core_config'] = $registry->get('CSVPRICEPRO_CORE_CONFIG');
        }
    }

    /**
     * @param $productId
     * @return string
     */
    public function getProductDiscount($productId)
    {
        $result = array();

        if (isset(self::$sharedInstance['core_config']['BASE_PRICE_DISCOUNT']) && self::$sharedInstance['core_config']['BASE_PRICE_DISCOUNT']) {
            $query = $this->db->query('SELECT CONCAT( customer_group_id, \',\', quantity, \',\', priority, \',\', TRUNCATE(price, 2), \',\', TRUNCATE(base_price, 2), \',\', date_start, \',\', date_end) AS p_discount  FROM `' . DB_PREFIX . 'product_discount` WHERE product_id = ' . (int)$productId);
        } else {
            $query = $this->db->query('SELECT CONCAT( customer_group_id, \',\', quantity, \',\', priority, \',\', TRUNCATE(price, 2), \',\', date_start, \',\', date_end) AS p_discount  FROM `' . DB_PREFIX . 'product_discount` WHERE product_id = ' . (int)$productId);
        }

        foreach ($query->rows as $row) {
            $result[] = $row['p_discount'];
        }

        return implode("\n", $result);
    }

    /**
     * @param $productId
     * @param $data
     * @return void
     */
    public function addProductDiscount($productId, $data)
    {
        // Delete old data
        $this->db->query('DELETE FROM `' . DB_PREFIX . 'product_discount` WHERE product_id = \'' . (int)$productId . '\'');

        if (empty($data)) {
            return;
        }

        $discountRows = explode("\n", $data);

        $discountData = array();

        foreach ($discountRows as $row) {
            $discountData[] = explode(',', trim($row));
        }

        if ($discountData) {
            foreach ($discountData as $discount) {

                if (count($discount) < 4) {
                    continue;
                }

                if (isset(self::$sharedInstance['core_config']['BASE_PRICE_DISCOUNT']) && self::$sharedInstance['core_config']['BASE_PRICE_DISCOUNT'] && count($discount) == 7) {
                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_discount`
                        SET `product_id` = \'' . (int)$productId . '\', 
                        `customer_group_id` = \'' . (int)$discount[0] . '\', 
                        `quantity` = \'' . (int)$discount[1] . '\', 
                        `priority` = \'' . (int)$discount[2] . '\', 
                        `price` = \'' . $this->model_extension_csvprice_pro_app_product->validateNumberFloat($discount[3]) . '\', 
                        `base_price` = \'' . $this->model_extension_csvprice_pro_app_product->validateNumberFloat($discount[4]) . '\',
                        `date_start` = \'' . ((isset($discount[5])) ? $this->db->escape($discount[5]) : '') . '\', 
                        `date_end` = \'' . ((isset($discount[6])) ? $this->db->escape($discount[6]) : '') . '\'');
                } else {
                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_discount`
                        SET `product_id` = \'' . (int)$productId . '\', 
                        `customer_group_id` = \'' . (int)$discount[0] . '\', 
                        `quantity` = \'' . (int)$discount[1] . '\', 
                        `priority` = \'' . (int)$discount[2] . '\', 
                        `price` = \'' . $this->model_extension_csvprice_pro_app_product->validateNumberFloat($discount[3]) . '\', 
                        `date_start` = \'' . ((isset($discount[4])) ? $this->db->escape($discount[4]) : '') . '\', 
                        `date_end` = \'' . ((isset($discount[5])) ? $this->db->escape($discount[5]) : '') . '\'');
                }
            }
        }
    }
}