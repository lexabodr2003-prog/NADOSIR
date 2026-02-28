<?php

/**
 * ModelExtensionCSVPriceProLibProductOption class
 */
class ModelExtensionCSVPriceProLibProductOption extends Model
{
    private $optionDelimiter = "\n";
    private static $sharedInstance = array();

    /**
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        if (false === isset(self::$sharedInstance['languages'])) {
            $query = $this->db->query('SELECT `language_id` FROM `' . DB_PREFIX . 'language`');

            if ($query->num_rows) {
                foreach ($query->rows as $row) {
                    self::$sharedInstance['languages'][] = $row['language_id'];
                }
            } else {
                self::$sharedInstance['languages'] = array();
            }
        }

        // Set core config
        if (false === isset(self::$sharedInstance['core_config'])) {
            self::$sharedInstance['core_config'] = $registry->get('CSVPRICEPRO_CORE_CONFIG');
        }
    }

    /**
     * @param $profile
     * @return $this
     */
    public function setProfileSettings($profile)
    {
        if (false === isset(self::$sharedInstance['profile'])) {
            self::$sharedInstance['profile'] = $profile;
        }

        return $this;
    }

    /**
     * @param $languageId
     * @return void
     */
    public function setLanguageId($languageId)
    {
        if (false === isset(self::$sharedInstance['language_id'])) {
            self::$sharedInstance['language_id'] = $languageId;
        }
    }

    /**
     * @param $productId
     * @return string
     */
    public function getProductOptions($productId)
    {
        $result = array();

        $queryProductOption = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po "
            . " LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) "
            . " LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) "
            . " WHERE po.product_id = '" . (int)$productId . "'"
            . " AND od.language_id = '" . (int)self::$sharedInstance['language_id'] . "'"
        );

        if (!$queryProductOption->num_rows) {
            return '';
        }

        foreach ($queryProductOption->rows as $rowProductOption) {
            if ($rowProductOption['type'] === 'select'
                || $rowProductOption['type'] === 'radio'
                || $rowProductOption['type'] === 'checkbox'
                || $rowProductOption['type'] === 'image'
            ) {
                $queryProductOptionValue = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov "
                    . " LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) "
                    . " LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) "
                    . " WHERE pov.product_option_id = '" . (int)$rowProductOption['product_option_id'] . "' "
                    . " AND ovd.language_id = '" . (int)self::$sharedInstance['language_id'] . "' "
                    . " ORDER BY ov.sort_order"
                );

                foreach ($queryProductOptionValue->rows as $rowProductOptionValue) {

                    if ($rowProductOptionValue['image']) {
                        $rowProductOptionValue['image'] = '|' . $rowProductOptionValue['image'];
                    }

                    $result[] = $rowProductOption['type'] . '|'
                        . $rowProductOption['name'] . '|'
                        . $rowProductOptionValue['name'] . '|'
                        . $rowProductOption['required'] . '|'
                        . $rowProductOptionValue['quantity'] . '|'
                        . $rowProductOptionValue['subtract'] . '|'
                        . $rowProductOptionValue['price_prefix'] . '|'
                        . $rowProductOptionValue['price'] . '|'
                        . $rowProductOptionValue['points_prefix'] . '|'
                        . $rowProductOptionValue['points'] . '|'
                        . $rowProductOptionValue['weight_prefix'] . '|'
                        . $rowProductOptionValue['weight']
                        . $rowProductOptionValue['image'];
                }

            } else {
                if (isset($rowProductOption['value']) && $rowProductOption['value']) {
                    $value = '|' . $rowProductOption['value'];
                } else {
                    $value = '';
                }
                $result[] = $rowProductOption['type'] . '|'
                    . $rowProductOption['name'] . '|'
                    . $rowProductOption['required']
                    . $value;
            }
        }

        $str = implode($this->optionDelimiter, $result);

        return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param $productId
     * @param $data
     * @return void
     */
    public function addProductOptions($productId, $data)
    {
        // Delete all old product options
        $this->db->query('DELETE FROM `' . DB_PREFIX . 'product_option` WHERE  product_id = \'' . (int)$productId . '\'');
        $this->db->query('DELETE FROM `' . DB_PREFIX . 'product_option_value` WHERE  product_id = \'' . (int)$productId . '\'');

        $options = explode($this->optionDelimiter, trim($data));

        if (empty($options)) {
            return;
        }

        // Options type
        $optionTypeSelect = array('select', 'radio', 'checkbox', 'image');
        $optionTypeDatetime = array('date', 'time', 'datetime');
        $optionTypeText = array('text', 'textarea');
        $optionTypeFile = array('file');

        $optionTypeExists = array_merge($optionTypeSelect, $optionTypeDatetime, $optionTypeText, $optionTypeFile);

        foreach ($options as $rowOption) {

            $rowOption = trim($rowOption, " \n\r\t");

            if (empty($rowOption)) {
                continue;
            }

            $option = explode('|', $rowOption);

            if (empty($option) || count($option) < 2) {
                continue;
            }

            // Exists option type
            if (in_array($option[0], $optionTypeExists)) {

                list($optionId, $optionValueId) = $this->getOptionValueId($option);

                // Image
                if (isset($option[12])) {
                    $this->db->query('UPDATE `' . DB_PREFIX . 'option_value` SET image = \'' . $this->db->escape($option[12]) . '\' WHERE option_value_id = \'' . (int)$optionValueId . '\' AND option_id = \'' . $optionId . '\'');
                }

                if (in_array($option[0], $optionTypeSelect)) {
                    /**
                     * Add new product option
                     *
                     * [0] = type
                     * [1] = option_name
                     * [2] = value_name
                     * [3] = required
                     * [4] = quantity
                     * [5] = subtract
                     * [6] = price_prefix
                     * [7] = price
                     * [8] = points_prefix
                     * [9] = points
                     * [10] = weight_prefix
                     * [11] = weight
                     * [12] = image
                     */
                    if (!isset($option[3])) {
                        $option[3] = 0;
                    }

                    $productOptionId = $this->getProductOptionID($productId, $optionId, $option[3]);

                    $option = $this->validateProductOption($option);

                    // Points
                    if (!isset($option[8])) {
                        $option[8] = self::$sharedInstance['profile']['option_points_prefix'];
                        $option[9] = self::$sharedInstance['profile']['option_points_default'];
                    }

                    // Weight
                    if (!isset($option[10])) {
                        $option[10] = self::$sharedInstance['profile']['option_weight_prefix'];
                        $option[11] = self::$sharedInstance['profile']['option_weight_default'];
                    }

                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_option_value` SET '
                        . ' `product_option_id` = \'' . (int)$productOptionId . '\', '
                        . ' `product_id` = \'' . (int)$productId . '\', '
                        . ' `option_id` = \'' . (int)$optionId . '\', '
                        . ' `option_value_id` = \'' . (int)$optionValueId . '\', '
                        . ' `quantity` = \'' . (int)$option[4] . '\', '
                        . ' `subtract` = \'' . (int)$option[5] . '\', '
                        . ' `price_prefix` = \'' . trim($option[6]) . '\', '
                        . ' `price` = \'' . $option[7] . '\', '
                        . ' `points_prefix` = \'' . trim($option[8]) . '\', '
                        . ' `points` = \'' . $option[9] . '\', '
                        . ' `weight_prefix` = \'' . trim($option[10]) . '\', '
                        . ' `weight` = \'' . $option[11] . '\''
                    );

                } elseif (in_array($option[0], $optionTypeDatetime)
                    || in_array($option[0], $optionTypeText)
                    || in_array($option[0], $optionTypeFile)
                ) {
                    if (!isset($option[3])) {
                        $option[3] = '';
                    }

                    $productOptionId = $this->getProductOptionID($productId, $optionId, $option[2]);

                    if ($option[0] !== 'file') {
                        $this->db->query('UPDATE `' . DB_PREFIX . 'product_option` SET `value` = \'' . $this->db->escape($option[3]) . '\' WHERE product_option_id = \'' . (int)$productOptionId . '\';');
                    }
                }

            } else { // FORMAT: option_name|option_value_name|price|image (links)

                $getOptionValueId = $this->getOptionValueId(array(
                        self::$sharedInstance['profile']['option_type'],
                        $option[0],
                        $option[1])
                );

                list($optionId, $optionValueId) = $getOptionValueId;

                $productOptionId = $this->getProductOptionID($productId, $optionId, self::$sharedInstance['profile']['option_required']);

                // Type Select
                if (in_array(self::$sharedInstance['profile']['option_type'], $optionTypeSelect)) {

                    // Price
                    if (!isset($option[2])) {
                        $price = 0;
                    } else {
                        $price = $option[2];
                    }

                    // Image
                    if (isset($option[3])) {
                        $this->db->query('UPDATE `' . DB_PREFIX . 'option_value` SET image = \'' . $this->db->escape($option[3]) . '\' WHERE option_value_id = \'' . (int)$optionValueId . '\' AND option_id = \'' . $optionId . '\'');
                    }

                    // Links
                    if (isset($option[4]) && self::$sharedInstance['core_config']['PRODUCT_OPTION_LINKS']) {
                        $this->db->query('UPDATE `' . DB_PREFIX . 'option_value_description` SET `links` = \'' . $this->db->escape($option[4]) . '\' WHERE option_value_id = \'' . (int)$optionValueId . '\' AND option_id = \'' . $optionId . '\'');
                    }

                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_option_value` SET '
                        . ' `product_option_id` = \'' . (int)$productOptionId . '\', '
                        . ' `product_id` = \'' . (int)$productId . '\', '
                        . ' `option_id` = \'' . (int)$optionId . '\', '
                        . ' `option_value_id` = \'' . (int)$optionValueId . '\', '
                        . ' `quantity` = \'' . (int)self::$sharedInstance['profile']['option_quantity'] . '\', '
                        . ' `subtract` = \'' . (int)self::$sharedInstance['profile']['option_subtract_stock'] . '\', '
                        . ' `price_prefix` = \'' . self::$sharedInstance['profile']['option_price_prefix'] . '\', '
                        . ' `price` = \'' . $price . '\', '
                        . ' `points_prefix` = \'' . self::$sharedInstance['profile']['option_points_prefix'] . '\', '
                        . ' `points` = \'' . self::$sharedInstance['profile']['option_points_default'] . '\', '
                        . ' `weight_prefix` = \'' . self::$sharedInstance['profile']['option_weight_prefix'] . '\', '
                        . ' `weight` = \'' . self::$sharedInstance['profile']['option_weight_default'] . '\''
                    );

                    // Type Date & Text
                } elseif (in_array(self::$sharedInstance['profile']['option_type'], $optionTypeDatetime)
                    || in_array(self::$sharedInstance['profile']['option_type'], $optionTypeText)
                ) {
                    // Option Value
                    if (isset($option[3])) {
                        $this->db->query('UPDATE `' . DB_PREFIX . 'product_option` SET `value` = \'' . $this->db->escape($option[3]) . '\' WHERE product_option_id = \'' . (int)$productOptionId . '\'');
                    }

                    // Plugin links support
                    if (isset($option[4]) && self::$sharedInstance['core_config']['PRODUCT_OPTION_LINKS']) {
                        $this->db->query('UPDATE `' . DB_PREFIX . 'option_value_description` SET `links` = \'' . $this->db->escape($option[4]) . '\' WHERE option_value_id = \'' . (int)$optionValueId . '\' AND option_id = \'' . $optionId . '\'');
                    }
                }
            }
        }
    }

    /**
     * @param $productId
     * @param $optionId
     * @param $required
     * @return mixed
     */
    private function getProductOptionID($productId, $optionId, $required)
    {
        $query = $this->db->query('SELECT `product_option_id` FROM `' . DB_PREFIX . 'product_option` WHERE `product_id` = \'' . (int)$productId . '\' AND `option_id` = \'' . (int)$optionId . '\' LIMIT 1');

        if ($query->num_rows) {
            $productOptionId = $query->row['product_option_id'];
        } else {
            // Add new product option
            $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_option` SET `product_id` = \'' . (int)$productId . '\', `option_id` = \'' . (int)$optionId . '\', `required` = \'' . $required . '\'');
            $productOptionId = $this->db->getLastId();
        }

        return $productOptionId;
    }

    /**
     * @param $option
     * @return array
     */
    private function getOptionValueId($option)
    {
        // fix HTML Special Chars
        $option[1] = htmlspecialchars($option[1], ENT_QUOTES, 'UTF-8');
        $option[2] = htmlspecialchars($option[2], ENT_QUOTES, 'UTF-8');

        $query = $this->db->query('SELECT option_id FROM `' . DB_PREFIX . 'option_description` WHERE LOWER(name) = LOWER(\'' . $this->db->escape($option[1]) . '\') AND language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' LIMIT 1');

        if ($query->num_rows > 0 and isset($query->row['option_id'])) {
            $optionId = $query->row['option_id'];
        } else {
            // Add new option group
            $this->db->query('INSERT INTO `' . DB_PREFIX . 'option` SET type = \'' . $option[0] . '\', sort_order = 0');
            $optionId = $this->db->getLastId();

            // For all languages
            foreach (self::$sharedInstance['languages'] as $languageId) {
                $this->db->query('INSERT INTO `' . DB_PREFIX . 'option_description` SET language_id = \'' . (int)$languageId . '\', option_id = \'' . $optionId . '\', name = \'' . $this->db->escape($option[1]) . '\'');
            }
        }

        $query = $this->db->query('SELECT option_value_id FROM `' . DB_PREFIX . 'option_value_description` WHERE LOWER(name) = LOWER(\'' . $this->db->escape($option[2]) . '\') AND option_id = \'' . $optionId . '\' AND language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' LIMIT 1');

        if (isset($query->row['option_value_id'])) {
            $optionValueId = $query->row['option_value_id'];
        } else {
            // Check image
            if (isset($option[12]) && !empty($option[12])) {
                $image = ', image= \'' . trim($option[12]) . '\'';
            } else {
                $image = '';
            }

            $this->db->query('INSERT INTO `' . DB_PREFIX . 'option_value` SET option_id = \'' . $optionId . '\'' . $image . ', sort_order = 0');

            $optionValueId = $this->db->getLastId();

            // For all languages.
            foreach (self::$sharedInstance['languages'] as $languageId) {
                $this->db->query('INSERT INTO `' . DB_PREFIX . 'option_value_description` SET language_id = \'' . (int)$languageId . '\', option_value_id = \'' . $optionValueId . '\', option_id = \'' . $optionId . '\', name = \'' . $this->db->escape($option[2]) . '\'');
            }
        }

        return array($optionId, $optionValueId);
    }

    /**
     * @param $option
     * @return mixed
     */
    private function validateProductOption($option)
    {
        // quantity
        $option[4] = (isset($option[4])) ? (int)$option[4] : (int)self::$sharedInstance['profile']['option_quantity'];

        // subtract
        $option[5] = (isset($option[5])) ? (int)$option[5] : (int)self::$sharedInstance['profile']['option_subtract_stock'];

        // price_prefix
        $option[6] = (isset($option[6])) ? trim($option[6]) : self::$sharedInstance['profile']['option_price_prefix'];

        // price
        if (isset($option[7])) {
            $option[7] = $this->model_extension_csvprice_pro_app_product->validateNumberFloat($option[7]);
        } else {
            $option[7] = 0;
        }

        //  points_prefix
        $option[8] = (isset($option[8])) ? trim($option[8]) : self::$sharedInstance['profile']['option_points_prefix'];

        // points
        $option[9] = (isset($option[9])) ? (int)$option[9] : (int)self::$sharedInstance['profile']['option_points_default'];

        // weight_prefix
        $option[10] = (isset($option[10])) ? trim($option[10]) : self::$sharedInstance['profile']['option_weight_prefix'];

        // weight
        if (isset($option[11])) {
            $option[11] = $this->model_extension_csvprice_pro_app_product->validateNumberFloat($option[11]);
        } else {
            $option[11] = 0;
        }

        return $option;
    }
}