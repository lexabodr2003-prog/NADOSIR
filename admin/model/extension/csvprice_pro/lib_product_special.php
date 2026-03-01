<?php

/**
 * ModelExtensionCSVPriceProLibProductSpecial class
 */
class ModelExtensionCSVPriceProLibProductSpecial extends Model
{
    private static $sharedInstance = array();

    /**
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        // Set core config
        if (false === isset(self::$sharedInstance['core_config'])) {
            self::$sharedInstance['core_config'] = $registry->get('CSVPRICEPRO_CORE_CONFIG');
        }
    }

    /**
     * @param $productId
     * @return string
     */
    public function getProductSpecial($productId)
    {
        $result = array();

        if (isset(self::$sharedInstance['core_config']['BASE_PRICE_SPECIAL']) && self::$sharedInstance['core_config']['BASE_PRICE_SPECIAL']) {
            $query = $this->db->query('SELECT CONCAT( ps.customer_group_id, \',\', ps.priority, \',\', TRUNCATE(ps.price, 2), \',\', TRUNCATE(ps.base_price, 2), \',\', ps.date_start, \',\', ps.date_end) AS p_special FROM `' . DB_PREFIX . 'product_special` ps WHERE ps.product_id = ' . (int)$productId);
        } else {
            $query = $this->db->query('SELECT CONCAT( ps.customer_group_id, \',\', ps.priority, \',\', TRUNCATE(ps.price, 2), \',\', ps.date_start, \',\', ps.date_end) AS p_special FROM `' . DB_PREFIX . 'product_special` ps WHERE ps.product_id = ' . (int)$productId);
        }

        foreach ($query->rows as $row) {
            $result[] = $row['p_special'];
        }

        return implode("\n", $result);
    }

    /**
     * @param $productId
     * @param $data
     * @return void
     */
    public function addProductSpecial($productId, $data)
    {
        // Delete Old Data
        $this->db->query('DELETE FROM `' . DB_PREFIX . 'product_special` WHERE product_id = \'' . (int)$productId . '\'');

        $productSpecialRows = explode("\n", $data);

        if (empty($productSpecialRows)) {
            return;
        }

        $specialData = array();

        foreach ($productSpecialRows as $row) {
            $specialData[] = explode(',', trim($row));
        }

        if ($specialData) {
            foreach ($specialData as $special) {
                if (count($special) < 3) {
                    continue;
                }

                if (isset(self::$sharedInstance['core_config']['BASE_PRICE_SPECIAL']) && self::$sharedInstance['core_config']['BASE_PRICE_SPECIAL'] && count($special) == 6) {
                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_special` SET '
                        . ' `product_id` = \'' . (int)$productId . '\', '
                        . ' `customer_group_id` = \'' . (int)$special[0] . '\', '
                        . ' `priority` = \'' . (int)$special[1] . '\',  '
                        . ' `price` = \'' . $this->model_extension_csvprice_pro_app_product->validateNumberFloat($special[2]) . '\', '
                        . ' `base_price` = \'' . $this->model_extension_csvprice_pro_app_product->validateNumberFloat($special[3]) . '\', '
                        . ' `date_start` = \'' . ((isset($special[4])) ? $this->db->escape($special[4]) : '') . '\', '
                        . ' `date_end` = \'' . ((isset($special[5])) ? $this->db->escape($special[5]) : '') . '\''
                    );
                } else {
                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_special` SET '
                        . ' `product_id` = \'' . (int)$productId . '\',  '
                        . ' `customer_group_id` = \'' . (int)$special[0] . '\', '
                        . ' `priority` = \'' . (int)$special[1] . '\',  '
                        . ' `price` = \'' . $this->model_extension_csvprice_pro_app_product->validateNumberFloat($special[2]) . '\', '
                        . ' `date_start` = \'' . ((isset($special[3])) ? $this->db->escape($special[3]) : '') . '\',  '
                        . ' `date_end` = \'' . ((isset($special[4])) ? $this->db->escape($special[4]) : '') . '\''
                    );
                }
            }
        }
    }
}