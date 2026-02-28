<?php

/**
 * ModelExtensionCSVPriceProLibProductAttribute class
 */
class ModelExtensionCSVPriceProLibProductAttribute extends Model
{
    private $attributeDelimiter = '|';
    private $multiLanguagesStatus = false;
    private static $sharedInstance = array();

    /**
     * @param $product_id
     * @return string
     */
    public function getProductAttribute($product_id)
    {
        $result = array();

        if ($this->multiLanguagesStatus && self::$sharedInstance['languages_count'] > 1) {
            $field = ', patt.attribute_id';
        } else {
            $field = '';
        }

        $query = $this->db->query('SELECT CONCAT_WS(\'' . $this->attributeDelimiter . '\'' . $field . ', attgd.name, attd.name, patt.text) AS p_attribute FROM `' . DB_PREFIX . 'product_attribute` patt LEFT JOIN `' . DB_PREFIX . 'attribute_description` attd ON (attd.attribute_id = patt.attribute_id)  LEFT JOIN `' . DB_PREFIX . 'attribute` att ON (attd.attribute_id = att.attribute_id) LEFT JOIN `' . DB_PREFIX . 'attribute_group_description` attgd ON (attgd.attribute_group_id = att.attribute_group_id) WHERE patt.product_id = ' . (int)$product_id . ' AND attgd.language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' AND attd.language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' AND patt.language_id = \'' . (int)self::$sharedInstance['language_id'] . '\'');

        foreach ($query->rows as $row) {
            $result[] = html_entity_decode($row['p_attribute'], ENT_QUOTES, 'UTF-8');
        }

        return implode("\n", $result);
    }

    /**
     * @param $product_id
     * @param $data
     * @return mixed
     */
    public function updateProductAttribute($product_id, $data)
    {
        $this->db->query('DELETE FROM `' . DB_PREFIX . 'product_attribute` WHERE `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\' AND `product_id` = \'' . (int)$product_id . '\'');

        if (!empty($data)) {
            $attributes = explode("\n", $data);
        } else {
            $attributes = false;
        }

        if (is_array($attributes) && !empty($attributes)) {

            $attributes = array_unique($attributes, SORT_STRING);

            // Added in v2.2.2a
            foreach ($attributes as $attributeData) {
                // Added in v 4.1.12.3
                $attributeData = trim($attributeData, " \n\r\t");

                // Empty data
                if (empty($attributeData)) {
                    continue;
                }

                $attribute = explode($this->attributeDelimiter, $attributeData);

                if (is_array($attribute) && 3 === count($attribute)) {

                    $attribute[0] = trim($attribute[0]);
                    $attribute[1] = trim($attribute[1]);

                    // Check isset product attribute
                    if (!isset(self::$sharedInstance['attribute_cache'][mb_strtolower($attribute[0] . $attribute[1])])) {
                        $attributeId = $this->addAttribute($attribute[0], $attribute[1]);
                    } else {
                        $attributeId = self::$sharedInstance['attribute_cache'][mb_strtolower($attribute[0] . $attribute[1])];
                    }

                    // Add new product attribute
                    $this->db->query('DELETE FROM  `' . DB_PREFIX . 'product_attribute` WHERE `attribute_id` = \'' . (int)$attributeId . '\' AND `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\' AND `product_id` = \'' . (int)$product_id . '\'');
                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_attribute` SET `product_id` = \'' . (int)$product_id . '\', `attribute_id` = \'' . (int)$attributeId . '\', `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\', `text` = \'' . $this->db->escape($attribute[2]) . '\'');

                } elseif (count($attribute) == 4) {
                    // Added in 3.2.0beta
                    $attributeId = trim($attribute[0], " \n\t\r");
                    $attribute[1] = trim($attribute[1], " \n\t\r");
                    $attribute[2] = trim($attribute[2], " \n\t\r");

                    $this->updateAttribute($attributeId, $attribute[1], $attribute[2]);

                    // Add new product attribute
                    $this->db->query('DELETE FROM  `' . DB_PREFIX . 'product_attribute` WHERE `attribute_id` = \'' . (int)$attributeId . '\' AND `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\' AND `product_id` = \'' . (int)$product_id . '\'');
                    $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_attribute` SET `product_id` = \'' . (int)$product_id . '\', `attribute_id` = \'' . (int)$attributeId . '\', `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\', `text` = \'' . $this->db->escape($attribute[3]) . '\'');
                }
            }
        }
        // Add this your code for update a custom filters

        return $product_id;
    }

    /**
     * @param $groupName
     * @param $attributeName
     * @return mixed
     */
    private function addAttribute($groupName, $attributeName)
    {
        $groupName = htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8');
        $attributeName = htmlspecialchars($attributeName, ENT_QUOTES, 'UTF-8');

        $query = $this->db->query('SELECT attribute_group_id FROM `' . DB_PREFIX . 'attribute_group_description` WHERE LOWER(name) = LOWER(\'' . $this->db->escape($groupName) . '\') AND language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' LIMIT 1');

        if (isset($query->row['attribute_group_id'])) {
            $attributeGroupId = $query->row['attribute_group_id'];
        } else {
            $this->db->query('INSERT INTO `' . DB_PREFIX . 'attribute_group` SET sort_order = 1');

            $attributeGroupId = $this->db->getLastId();

            $this->db->query('INSERT INTO `' . DB_PREFIX . 'attribute_group_description` SET `attribute_group_id` = ' . (int)$attributeGroupId . ', `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\',	`name` = \'' . $this->db->escape($groupName) . '\'');
        }

        $query = $this->db->query('SELECT ad.attribute_id FROM `' . DB_PREFIX . 'attribute_description` ad LEFT JOIN `' . DB_PREFIX . 'attribute` a ON (ad.attribute_id = a.attribute_id) WHERE LOWER(ad.name) = LOWER(\'' . $this->db->escape($attributeName) . '\') AND ad.language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' AND a.attribute_group_id = \'' . (int)$attributeGroupId . '\' LIMIT 1');

        if (isset($query->row['attribute_id'])) {
            $attributeId = $query->row['attribute_id'];
        } else {
            $this->db->query('INSERT INTO `' . DB_PREFIX . 'attribute` SET sort_order = 1, attribute_group_id = ' . (int)$attributeGroupId);

            $attributeId = $this->db->getLastId();

            $this->db->query('INSERT INTO `' . DB_PREFIX . 'attribute_description` SET `attribute_id` = ' . (int)$attributeId . ', `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\', `name` = \'' . $this->db->escape($attributeName) . '\'');
        }

        return $attributeId;
    }

    /**
     * @param $attributeId
     * @param $groupName
     * @param $attributeName
     * @return void
     */
    private function updateAttribute($attributeId, $groupName, $attributeName)
    {
        $query = $this->db->query('SELECT attribute_group_id FROM `' . DB_PREFIX . 'attribute` WHERE attribute_id = \'' . (int)$attributeId . '\' LIMIT 1');

        if (isset($query->row['attribute_group_id'])) {
            $attributeGroupId = $query->row['attribute_group_id'];
        } else {
            return;
        }

        $groupName = htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8');
        $attributeName = htmlspecialchars($attributeName, ENT_QUOTES, 'UTF-8');

        $this->db->query('DELETE FROM `' . DB_PREFIX . 'attribute_description` WHERE `attribute_id` = \'' . (int)$attributeId . '\' AND `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\';');
        $this->db->query('INSERT INTO `' . DB_PREFIX . 'attribute_description` SET `attribute_id` = ' . (int)$attributeId . ', `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\', `name` = \'' . $this->db->escape($attributeName) . '\';');

        $this->db->query('DELETE FROM `' . DB_PREFIX . 'attribute_group_description` WHERE `attribute_group_id` = \'' . (int)$attributeGroupId . '\' AND `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\';');
        $this->db->query('INSERT INTO `' . DB_PREFIX . 'attribute_group_description` SET `attribute_group_id` = ' . (int)$attributeGroupId . ', `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\', `name` = \'' . $this->db->escape($groupName) . '\';');

        return $attributeId;
    }

    /**
     * @param $languageId
     * @return $this
     */
    public function setLanguageId($languageId)
    {
        if (false === isset(self::$sharedInstance['language_id'])) {
            self::$sharedInstance['language_id'] = $languageId;
            $this->setLanguagesCount();
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function setLanguagesCount()
    {
        $result = $this->db->query("SELECT COUNT(`language_id`) AS languages_count FROM `" . DB_PREFIX . "language` WHERE `status` = 1");

        if ($result->num_rows) {
            self::$sharedInstance['languages_count'] = $result->row['languages_count'];
        } else {
            self::$sharedInstance['languages_count'] = 1;
        }

        return $this;
    }
}
