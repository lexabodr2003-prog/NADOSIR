<?php /** @noinspection SqlResolve */

/**
 * ModelExtensionCSVPriceProLibProductFilter class
 * @property $db
 */
class ModelExtensionCSVPriceProLibProductFilter extends Model
{
    private static $sharedInstance = array();

    /**
     * @param $productId
     * @return string
     */
    public function getProductFilters($productId)
    {
        $result = array();

        $query = $this->db->query("SELECT CONCAT(fgd.name, '|', fd.name) AS p_filters "
            . " FROM `" . DB_PREFIX . "product_filter` pf  "
            . " LEFT JOIN `" . DB_PREFIX . "filter_description` fd ON (pf.filter_id = fd.filter_id AND fd.language_id = '" . (int)self::$sharedInstance['language_id'] . "') "
            . " LEFT JOIN `" . DB_PREFIX . "filter_group_description` fgd ON (fd.filter_group_id = fgd.filter_group_id AND fgd.language_id = '" . (int)self::$sharedInstance['language_id'] . "') "
            . " WHERE pf.product_id = " . (int)$productId
        );

        foreach ($query->rows as $row) {
            $result[] = html_entity_decode($row['p_filters'], ENT_QUOTES, 'UTF-8');
        }

        return implode("\n", $result);
    }

    /**
     * @param $categoryId
     * @return string
     */
    public function getCategoryFilters($categoryId)
    {
        $result = array();

        $query = $this->db->query("SELECT CONCAT(fgd.name, '|', fd.name) AS c_filters "
            . " FROM `" . DB_PREFIX . "category_filter` cf "
            . " LEFT JOIN `" . DB_PREFIX . "filter_description` fd ON (cf.filter_id = fd.filter_id AND fd.language_id = '" . (int)self::$sharedInstance['language_id'] . "') "
            . " LEFT JOIN `" . DB_PREFIX . "filter_group_description` fgd ON (fd.filter_group_id = fgd.filter_group_id AND fgd.language_id = '" . (int)self::$sharedInstance['language_id'] . "') "
            . " WHERE cf.category_id = " . (int)$categoryId
        );

        foreach ($query->rows as $row) {
            $result[] = html_entity_decode($row['c_filters'], ENT_QUOTES, 'UTF-8');
        }

        return implode("\n", $result);
    }

    /**
     * @param $productId
     * @param $filters
     * @param $languageId
     * @return void
     */
    public function addProductFilters($productId, $filters, $languageId)
    {
        self::$sharedInstance['language_id'] = $languageId;

        // Delete old product filters
        $this->db->query('DELETE FROM `' . DB_PREFIX . 'product_filter` WHERE  `product_id` = \'' . (int)$productId . '\'');

        if (empty($filters)) {
            return;
        }

        $arr = explode("\n", $filters);

        if ($arr) {
            foreach ($arr as $item) {
                $filter = explode('|', $item);

                if (empty($filter) || count($filter) < 2) {
                    continue;
                }

                $filterId = $this->getFilterIdByName($filter[1], $filter[0]);
                $this->db->query('INSERT INTO `' . DB_PREFIX . 'product_filter` SET  `product_id` = \'' . (int)$productId . '\', `filter_id` = \'' . (int)$filterId . '\' ON DUPLICATE KEY UPDATE filter_id = \'' . (int)$filterId . '\'');
            }
        }
    }

    /**
     * @param $category_id
     * @param $filters
     * @param $languageId
     * @return void
     */
    public function addCategoryFilters($category_id, $filters, $languageId)
    {
        self::$sharedInstance['language_id'] = $languageId;

        // Delete old category filters
        $this->db->query('DELETE FROM `' . DB_PREFIX . 'category_filter` WHERE  `category_id` = \'' . (int)$category_id . '\'');

        if (empty($filters)) {
            return;
        }

        $arr = explode("\n", $filters);

        if ($arr) {
            foreach ($arr as $item) {
                $filter = explode('|', $item);

                if (empty($filter) || count($filter) < 2) {
                    continue;
                }

                $filterId = $this->getFilterIdByName($filter[1], $filter[0]);

                $this->db->query('INSERT INTO `' . DB_PREFIX . 'category_filter` SET  `category_id` = \'' . (int)$category_id . '\', `filter_id` = \'' . (int)$filterId . '\'');
            }
        }
    }

    /**
     * @param $filterName
     * @param $groupName
     * @return mixed
     */
    private function getFilterIdByName($filterName, $groupName)
    {
        $filterGroupId = $this->getFilterGroupIdByName($groupName);

        $query = $this->db->query('SELECT `filter_id` FROM `' . DB_PREFIX . 'filter_description` WHERE LOWER(name) = LOWER(\'' . $this->db->escape(htmlspecialchars(trim($filterName), ENT_QUOTES, 'UTF-8')) . '\') AND language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' AND filter_group_id = \'' . $filterGroupId . '\' LIMIT 1');

        if ($query->num_rows) {
            return $query->row['filter_id'];
        }

        return $this->addFilter($filterGroupId, $filterName);
    }

    /**
     * @param $groupName
     * @return mixed
     */
    private function getFilterGroupIdByName($groupName)
    {
        $query = $this->db->query('SELECT `filter_group_id` FROM `' . DB_PREFIX . 'filter_group_description` WHERE LOWER(name) = LOWER(\'' . $this->db->escape(htmlspecialchars(trim($groupName), ENT_QUOTES, 'UTF-8')) . '\') AND language_id = \'' . (int)self::$sharedInstance['language_id'] . '\' LIMIT 1');

        if ($query->num_rows) {
            return $query->row['filter_group_id'];
        }

        return $this->addFilterGroup($groupName);
    }

    /**
     * @param $groupName
     * @param $sortOrder
     * @return mixed
     */
    private function addFilterGroup($groupName, $sortOrder = 0)
    {
        $this->db->query('INSERT INTO `' . DB_PREFIX . 'filter_group` SET `sort_order` = ' . $sortOrder);

        $filterGroupId = $this->db->getLastId();

        $this->db->query('INSERT INTO `' . DB_PREFIX . 'filter_group_description` SET `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\', `filter_group_id` = \'' . $filterGroupId . '\',  `name` = \'' . $this->db->escape(htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8')) . '\'');

        return $filterGroupId;
    }

    /**
     * @param $filterGroupId
     * @param $filterName
     * @param $sortOrder
     * @return mixed
     */
    private function addFilter($filterGroupId, $filterName, $sortOrder = 0)
    {
        $this->db->query('INSERT INTO `' . DB_PREFIX . 'filter` SET `filter_group_id` = ' . $filterGroupId . ',  `sort_order` = ' . $sortOrder);

        $filterId = $this->db->getLastId();

        $this->db->query('INSERT INTO `' . DB_PREFIX . 'filter_description` SET `language_id` = \'' . (int)self::$sharedInstance['language_id'] . '\', `filter_id` = \'' . $filterId . '\', `filter_group_id` = \'' . $filterGroupId . '\',  `name` = \'' . $this->db->escape(htmlspecialchars($filterName, ENT_QUOTES, 'UTF-8')) . '\'');

        return $filterId;
    }

    /**
     * @param $languageId
     * @return $this
     */
    public function setLanguageId($languageId)
    {
        if (false === isset(self::$sharedInstance['language_id'])) {
            self::$sharedInstance['language_id'] = $languageId;
        }

        return $this;
    }
}
