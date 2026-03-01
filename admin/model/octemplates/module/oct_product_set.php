<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */
class ModelOctemplatesModuleOctProductSet extends Model {

    public function getProductSets($filter_data = array()) {
        $language_id = $this->config->get('config_language_id');

        $sql = "SELECT DISTINCT ops.* FROM " . DB_PREFIX . "oct_product_set ops";
    
        if (!empty($filter_data['filter_product_name'])) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "oct_product_set_relation psr ON (ops.product_set_id = psr.product_set_id)";
            $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (psr.product_id = p.product_id)";
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "')";
        }
    
        if (!empty($filter_data['filter_category_name'])) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "oct_product_set_to_category opstc ON (ops.product_set_id = opstc.product_set_id)";
            $sql .= " LEFT JOIN " . DB_PREFIX . "category_description cd ON (opstc.category_id = cd.category_id AND cd.language_id = '" . $language_id . "')";
        }
    
        if (!empty($filter_data['filter_manufacturer_name'])) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "oct_product_set_to_manufacturer opstm ON (ops.product_set_id = opstm.product_set_id)";
            $sql .= " LEFT JOIN " . DB_PREFIX . "manufacturer m ON (opstm.manufacturer_id = m.manufacturer_id)";
        }
    
        $where = [];
    
        if (!empty($filter_data['filter_set_name'])) {
            $where[] = "ops.name LIKE '%" . $this->db->escape($filter_data['filter_set_name']) . "%'";
        }
    
        if (!empty($filter_data['filter_product_name'])) {
            $where[] = "pd.name LIKE '%" . $this->db->escape($filter_data['filter_product_name']) . "%'";
        }
    
        if (!empty($filter_data['filter_category_name'])) {
            $where[] = "cd.name LIKE '%" . $this->db->escape($filter_data['filter_category_name']) . "%'";
        }
    
        if (!empty($filter_data['filter_manufacturer_name'])) {
            $where[] = "m.name LIKE '%" . $this->db->escape($filter_data['filter_manufacturer_name']) . "%'";
        }
    
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
    
        $sql .= " ORDER BY ops.date_added DESC";
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
    
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
    
        $query = $this->db->query($sql);
        $product_sets = $query->rows;
    
        foreach ($product_sets as &$product_set) {
            $product_set_id = $product_set['product_set_id'];
    
            $query = $this->db->query("SELECT pd.name, p.image, p.product_id, psr.sort_order
                FROM " . DB_PREFIX . "oct_product_set_relation psr
                LEFT JOIN " . DB_PREFIX . "product p ON psr.product_id = p.product_id
                LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id AND pd.language_id = '" . (int)$language_id . "'
                WHERE psr.product_set_id = '" . (int)$product_set_id . "'
                ORDER BY psr.sort_order ASC");
    
            $products = $query->rows;
    
            $product_set['products'] = array_map(function ($product) {
                return [
                    'name' => $product['name'],
                    'image' => $product['image'],
                    'product_id' => $product['product_id'],
                    'sort_order' => $product['sort_order']
                ];
            }, $products);
        }
    
        return $product_sets;
    }

    public function getAllProductSetData($product_set_id) {

        $language_id = $this->config->get('config_language_id');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "oct_product_set ps WHERE ps.product_set_id = '" . (int)$product_set_id . "'");
        $product_set = $query->row;

        // Get related product ids
        $query = $this->db->query("SELECT psr.product_id, pd.name, psr.discount_type, psr.discount_value, psr.product_quantity, psr.sort_order FROM " . DB_PREFIX . "oct_product_set_relation psr LEFT JOIN " . DB_PREFIX . "product p ON psr.product_id = p.product_id LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id AND pd.language_id = '" . (int)$language_id . "' WHERE psr.product_set_id = '" . (int)$product_set_id . "' ORDER BY psr.product_id");
        $product_set['related_product_ids'] = $query->rows;

        // Get category ids
        $query = $this->db->query("SELECT pstc.category_id, cd.name FROM " . DB_PREFIX . "oct_product_set_to_category pstc LEFT JOIN " . DB_PREFIX . "category_description cd ON pstc.category_id = cd.category_id AND cd.language_id = '" . (int)$language_id . "' WHERE pstc.product_set_id = '" . (int)$product_set_id . "' ORDER BY pstc.category_id");
        $product_set['category_ids'] = $query->rows;

        // Get manufacturer ids
        $query = $this->db->query("SELECT pstm.manufacturer_id, m.name FROM " . DB_PREFIX . "oct_product_set_to_manufacturer pstm LEFT JOIN " . DB_PREFIX . "manufacturer m ON pstm.manufacturer_id = m.manufacturer_id WHERE pstm.product_set_id = '" . (int)$product_set_id . "' ORDER BY pstm.manufacturer_id");
        $product_set['manufacturer_ids'] = $query->rows;

        // Get product ids
        $query = $this->db->query("SELECT pstp.product_id, ppd.name FROM " . DB_PREFIX . "oct_product_set_to_product pstp LEFT JOIN " . DB_PREFIX . "product_description ppd ON pstp.product_id = ppd.product_id AND ppd.language_id = '" . (int)$language_id . "' WHERE pstp.product_set_id = '" . (int)$product_set_id . "' ORDER BY pstp.product_id");
        $product_set['product_ids'] = $query->rows;

        // Get store ids
        $query = $this->db->query("SELECT psts.store_id FROM " . DB_PREFIX . "oct_product_set_to_store psts WHERE psts.product_set_id = '" . (int)$product_set_id . "'");
        $product_set['store_ids'] = $query->rows;

        // Get user group ids
        $query = $this->db->query("SELECT pstug.user_group_id FROM " . DB_PREFIX . "oct_product_set_to_user_group pstug WHERE pstug.product_set_id = '" . (int)$product_set_id . "'");
        $product_set['user_group_ids'] = $query->rows;

        $result = array();
        $related_products = array();
        
        foreach ($product_set['related_product_ids'] as $product) {
            $related_products[] = [
                'product_id' => $product['product_id'],
                'product_name' => $product['name'],
                'discount_type' => $product['discount_type'],
                'discount_value' => $product['discount_value'],
                'product_quantity' => $product['product_quantity'],
                'sort_order' => $product['sort_order']
            ];
        }

        $categories = [];
        foreach ($product_set['category_ids'] as $category) {
            $categories[] = [
                'category_id' => $category['category_id'],
                'category_name' => $category['name']
            ];
        }

        $manufacturers = [];
        foreach ($product_set['manufacturer_ids'] as $manufacturer) {
            $manufacturers[] = [
                'manufacturer_id' => $manufacturer['manufacturer_id'],
                'manufacturer_name' => $manufacturer['name']
            ];
        }

        $products_show_in = [];
        foreach ($product_set['product_ids'] as $product) {
            $products_show_in[] = [
                'product_id' => $product['product_id'],
                'product_name' => $product['name']
            ];
        }

        $store_ids = array_column($product_set['store_ids'], 'store_id');
        $user_group_ids = array_column($product_set['user_group_ids'], 'user_group_id');

        $result = [
            'product_set_id' => $product_set['product_set_id'],
            'name' => $product_set['name'],
            'sort_order' => $product_set['sort_order'],
            'status' => $product_set['status'],
            'date_added' => $product_set['date_added'],
            'date_start' => $product_set['date_start'],
            'date_end' => $product_set['date_end'],
            'related_products' => $related_products,
            'products_show_in' => $products_show_in,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'store_ids' => $store_ids,
            'user_group_ids' => $user_group_ids
        ];

        return $result;
    }

    public function getTotalProductSets($filter_data = array()) {
        $sql = "SELECT COUNT(DISTINCT ps.product_set_id) AS total FROM " . DB_PREFIX . "oct_product_set ps";
    
        if (!empty($filter_data['filter_product_name']) || !empty($filter_data['filter_category_name']) || !empty($filter_data['filter_manufacturer_name'])) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "oct_product_set_to_product p2p ON (ps.product_set_id = p2p.product_set_id)";
            $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2p.product_id = p.product_id)";
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";
            
            if (!empty($filter_data['filter_category_name'])) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
                $sql .= " LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id)";
            }
            if (!empty($filter_data['filter_manufacturer_name'])) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)";
            }
        }
    
        $where = [];
    
        if (!empty($filter_data['filter_set_name'])) {
            $where[] = "ps.name LIKE '%" . $this->db->escape($filter_data['filter_set_name']) . "%'";
        }
        if (!empty($filter_data['filter_product_name'])) {
            $where[] = "pd.name LIKE '%" . $this->db->escape($filter_data['filter_product_name']) . "%'";
        }
        if (!empty($filter_data['filter_category_name'])) {
            $where[] = "cd.name LIKE '%" . $this->db->escape($filter_data['filter_category_name']) . "%'";
        }
        if (!empty($filter_data['filter_manufacturer_name'])) {
            $where[] = "m.name LIKE '%" . $this->db->escape($filter_data['filter_manufacturer_name']) . "%'";
        }
    
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
    
        $query = $this->db->query($sql);
    
        return $query->row['total'];
    }

    public function checkSet($product_set_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "oct_product_set
            WHERE product_set_id = '" . (int)$product_set_id . "'
        ");

        return $query->num_rows;
    }

    public function addProductSet($data = array()) {

        $status = isset($data['status']) && $data['status'] == "on" ? 1 : 0;

        // oct_product_set
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "oct_product_set
            SET
                name = '" . $this->db->escape($data['name']) . "',
                sort_order = '" . (int)$data['sort_order'] . "',
                status = '" . (int)$status . "',
                date_added = NOW(),
                date_start = '" . $this->db->escape($data['date_start']) . "',
                date_end = '" . $this->db->escape($data['date_end']) . "'
        ");

        $data['set_id'] = $this->db->getLastId();
        
        $this->addRelations($data);
    }

    public function deleteSet($data = array()) {

        if (isset($data['delete']) && $data['delete'] == 1) {
            $this->db->query("
                DELETE FROM " . DB_PREFIX . "oct_product_set
                WHERE product_set_id = '" . (int)$data['set_id'] . "'
            ");
        }

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "oct_product_set_relation
            WHERE product_set_id = '" . (int)$data['set_id'] . "'
        ");

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "oct_product_set_to_category
            WHERE product_set_id = '" . (int)$data['set_id'] . "'
        ");

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "oct_product_set_to_manufacturer
            WHERE product_set_id = '" . (int)$data['set_id'] . "'
        ");

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "oct_product_set_to_product
            WHERE product_set_id = '" . (int)$data['set_id'] . "'
        ");

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "oct_product_set_to_store
            WHERE product_set_id = '" . (int)$data['set_id'] . "'
        ");

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "oct_product_set_to_user_group
            WHERE product_set_id = '" . (int)$data['set_id'] . "'
        ");
    }

    public function editProductSet($product_set_id, $data = array()) {
        $status = isset($this->request->post['status']) && $this->request->post['status'] == "on" ? 1 : 0;
        
        $this->db->query("
            UPDATE " . DB_PREFIX . "oct_product_set
            SET
                name = '" . $this->db->escape($this->request->post['name']) . "',
                sort_order = '" . (int)$this->request->post['sort_order'] . "',
                status = '" . (int)$status . "',
                date_start = '" . $this->db->escape($this->request->post['date_start']) . "',
                date_end = '" . $this->db->escape($this->request->post['date_end']) . "'
            WHERE product_set_id = '" . (int)$product_set_id . "'
        ");

        $data['delete'] = 0;
        $data['set_id'] = $product_set_id;

        $this->deleteSet($data);
        $this->addRelations($data);
    }

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "oct_product_set (
                `product_set_id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT '0',
                `status` TINYINT(1) NOT NULL DEFAULT '1',
                `date_added` DATETIME NOT NULL,
                `date_start` DATETIME NOT NULL,
                `date_end` DATETIME NOT NULL,
                PRIMARY KEY (`product_set_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS  " . DB_PREFIX . "oct_product_set_relation (
                `product_set_relation_id` INT(11) NOT NULL AUTO_INCREMENT,
                `product_set_id` INT(11) NOT NULL,
                `product_id` INT(11) NOT NULL,
                `product_quantity` INT(11) NOT NULL,
                `discount_type` ENUM('percentage','fixed') NOT NULL,
                `discount_value` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
                `sort_order` INT(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`product_set_relation_id`),
                INDEX `idx_product_set_id` (`product_set_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "oct_product_set_to_store (
                `product_set_id` INT(11) NOT NULL,
                `store_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_set_id`, `store_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "oct_product_set_to_user_group (
                `product_set_id` INT(11) NOT NULL,
                `user_group_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_set_id`, `user_group_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "oct_product_set_to_category (
                `product_set_id` INT(11) NOT NULL,
                `category_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_set_id`, `category_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "oct_product_set_to_manufacturer (
                `product_set_id` INT(11) NOT NULL,
                `manufacturer_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_set_id`, `manufacturer_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "oct_product_set_to_product (
                `product_set_id` INT(11) NOT NULL,
                `product_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_set_id`, `product_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_product_set`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_product_set_relation`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_product_set_to_store`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_product_set_to_user_group`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_product_set_to_category`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_product_set_to_manufacturer`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_product_set_to_product`");
    }

    public function checkIfExistMainTable() {
        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "oct_product_set'");

        return $query->num_rows;
    }

    private function addRelations($data = array()) {

        $product_set_id = $data['set_id'];

        // oct_product_set_relation
        if (isset($data['products_to_show'])) {
            foreach ($data['products_to_show'] as $product_id => $product) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "oct_product_set_relation
                    SET
                        product_set_id = '" . (int)$product_set_id . "',
                        product_id = '" . (int)$product_id . "',
                        product_quantity = '" . (int)($product['product_quantity'] <= 0 ? 1 : $product['product_quantity']) . "',
                        discount_type = '" . $this->db->escape($product['discount_type']) . "',
                        discount_value = '" . (float)$product['discount_value'] . "',
                        sort_order = '" . (int)$product['sort_order'] . "'
                ");
            }
        }

        // oct_product_set_to_category
        if (isset($data['categories'])) {
            foreach ($data['categories'] as $category) {
                $category_id = $category['category_id'];
                $this->db->query("
                INSERT INTO " . DB_PREFIX . "oct_product_set_to_category
                SET
                    product_set_id = '" . (int)$product_set_id . "',
                    category_id = '" . (int)$category_id . "'
                ");
            }
        }

        // oct_product_set_to_manufacturer
        if (isset($data['manufacturers'])) {
            foreach ($data['manufacturers'] as $manufacturer) {
                $manufacturer_id = $manufacturer['manufacturer_id'];
                $this->db->query("
                INSERT INTO " . DB_PREFIX . "oct_product_set_to_manufacturer
                SET
                    product_set_id = '" . (int)$product_set_id . "',
                    manufacturer_id = '" . (int)$manufacturer_id . "'
                ");
            }
        }

        // oct_product_set_to_product
        if (isset($data['products_show_in'])) {
            foreach ($data['products_show_in'] as $product_id => $product) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "oct_product_set_to_product
                    SET
                        product_set_id = '" . (int)$product_set_id . "',
                        product_id = '" . (int)$product_id . "'
                ");
            }
        }

        // oct_product_set_to_store
        if (isset($data['store_id'])) {
            foreach ($data['store_id'] as $store_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "oct_product_set_to_store
                    SET
                        product_set_id = '" . (int)$product_set_id . "',
                        store_id = '" . (int)$store_id . "'
                ");
            }
        }

        // oct_product_set_to_user_group
        if (isset($data['customer_group_id'])) {
            foreach ($data['customer_group_id'] as $user_group_id) {
                $this->db->query("
                INSERT INTO " . DB_PREFIX . "oct_product_set_to_user_group
                SET
                    product_set_id = '" . (int)$product_set_id . "',
                    user_group_id = '" . (int)$user_group_id . "'
                ");
            }
        }
    }
}