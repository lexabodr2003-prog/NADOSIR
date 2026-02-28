<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctAbandonedCart extends Model {

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_abandoned_cart` (
            `abandoned_cart_id` INT(11) NOT NULL AUTO_INCREMENT,
            `cookie_token` VARCHAR(64) DEFAULT NULL,
            `cookie_signature` VARCHAR(128) DEFAULT NULL,
            `customer_id` INT(11) DEFAULT '0',
            `firstname` VARCHAR(100) DEFAULT NULL,
            `lastname` VARCHAR(100) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `store_id` INT(11) DEFAULT '0',
            `store_name` VARCHAR(255) DEFAULT NULL,
            `language_id` INT(11) DEFAULT '0',
            `cart_data` MEDIUMTEXT DEFAULT NULL,
            `status` VARCHAR(50) DEFAULT 'active',
            `reminder_count` INT(1) NOT NULL DEFAULT 0,
            `last_reminder` DATETIME DEFAULT NULL,
            `coupon_code` VARCHAR(50) DEFAULT NULL,
            `ip_added` VARCHAR(40) DEFAULT NULL,
            `ip_changed` VARCHAR(40) DEFAULT NULL,
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`abandoned_cart_id`),
            INDEX `idx_cookie` (`cookie_token`, `cookie_signature`),
            INDEX `idx_customer` (`customer_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_store` (`store_id`, `language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_abandoned_cart_short_link` (
            `short_id` VARCHAR(8) NOT NULL,
            `raw_token` VARCHAR(32) NOT NULL,
            `signature` VARCHAR(32) NOT NULL,
            `abandoned_cart_id` INT(11) DEFAULT NULL,
            `clicks` INT(11) NOT NULL DEFAULT 0,
            `date_created` DATETIME NOT NULL,
            `date_expires` DATETIME NOT NULL,
            `store_id` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`short_id`),
            INDEX `idx_expires` (`date_expires`),
            INDEX `idx_abandoned_cart` (`abandoned_cart_id`),
            INDEX `idx_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_auth_rate_limit` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `ip` VARCHAR(45) NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `timestamp` INT(11) NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_ip_action` (`ip`, `action`),
            INDEX `idx_timestamp` (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_abandoned_cart`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_abandoned_cart_short_link`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_auth_rate_limit`;");
    }

    public function getAbandonedCarts($data = []) {

        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart` WHERE 1=1";
    
        if (!empty($data['filter_firstname'])) {
            $sql .= " AND firstname LIKE '%" . $this->db->escape($data['filter_firstname']) . "%'";
        }
    
        if (!empty($data['filter_lastname'])) {
            $sql .= " AND lastname LIKE '%" . $this->db->escape($data['filter_lastname']) . "%'";
        }
    
        if (!empty($data['filter_email'])) {
            $sql .= " AND email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }
    
        if (!empty($data['filter_phone'])) {
            $sql .= " AND phone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }
    
        if (!empty($data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($data['filter_status']) . "'";
        }
    
        if (!empty($data['filter_ip_added'])) {
            $sql .= " AND ip_added LIKE '%" . $this->db->escape($data['filter_ip_added']) . "%'";
        }
    
        if (!empty($data['filter_ip_changed'])) {
            $sql .= " AND ip_changed LIKE '%" . $this->db->escape($data['filter_ip_changed']) . "%'";
        }
    
        if (!empty($data['filter_date_added_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($data['filter_date_added_start']) . "'";
        }
        if (!empty($data['filter_date_added_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($data['filter_date_added_end']) . "'";
        }
    
        if (!empty($data['filter_date_modified_start'])) {
            $sql .= " AND DATE(date_modified) >= '" . $this->db->escape($data['filter_date_modified_start']) . "'";
        }
        if (!empty($data['filter_date_modified_end'])) {
            $sql .= " AND DATE(date_modified) <= '" . $this->db->escape($data['filter_date_modified_end']) . "'";
        }
    
        $sql .= " ORDER BY date_added DESC";
    
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = $this->config->get('config_limit_admin');
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
    
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalAbandonedCarts($data = []) {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_abandoned_cart` WHERE 1=1";
    
        if (!empty($data['filter_firstname'])) {
            $sql .= " AND firstname LIKE '%" . $this->db->escape($data['filter_firstname']) . "%'";
        }
    
        if (!empty($data['filter_lastname'])) {
            $sql .= " AND lastname LIKE '%" . $this->db->escape($data['filter_lastname']) . "%'";
        }
    
        if (!empty($data['filter_email'])) {
            $sql .= " AND email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }
    
        if (!empty($data['filter_phone'])) {
            $sql .= " AND phone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }
    
        if (!empty($data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($data['filter_status']) . "'";
        }
    
        if (!empty($data['filter_ip_added'])) {
            $sql .= " AND ip_added LIKE '%" . $this->db->escape($data['filter_ip_added']) . "%'";
        }
    
        if (!empty($data['filter_ip_changed'])) {
            $sql .= " AND ip_changed LIKE '%" . $this->db->escape($data['filter_ip_changed']) . "%'";
        }
    
        if (!empty($data['filter_date_added_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($data['filter_date_added_start']) . "'";
        }
        if (!empty($data['filter_date_added_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($data['filter_date_added_end']) . "'";
        }
    
        if (!empty($data['filter_date_modified_start'])) {
            $sql .= " AND DATE(date_modified) >= '" . $this->db->escape($data['filter_date_modified_start']) . "'";
        }
        if (!empty($data['filter_date_modified_end'])) {
            $sql .= " AND DATE(date_modified) <= '" . $this->db->escape($data['filter_date_modified_end']) . "'";
        }
    
        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    } 

    public function convertCart($cart_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "oct_abandoned_cart` 
                          SET `status` = 'converted', `date_modified` = NOW() 
                          WHERE `abandoned_cart_id` = " . (int)$cart_id . " AND `status` = 'active'");
        $affected = $this->db->countAffected();
        if ($affected <= 0) {
            $this->log->write('No rows affected while converting cart_id: ' . (int)$cart_id);
        }
        return $affected > 0;
    }

    public function deleteCart($cart_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_abandoned_cart` 
                          WHERE `abandoned_cart_id` = " . (int)$cart_id);
        $affected = $this->db->countAffected();
        if ($affected <= 0) {
            $this->log->write('No rows affected while deleting cart_id: ' . (int)$cart_id);
        }
        return $affected > 0;
    }

    public function getAbandonedCartDetails($cart_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart` 
                                   WHERE `abandoned_cart_id` = " . (int)$cart_id . " LIMIT 1");

        if ($query->num_rows) {
            return $query->row;
        }
        $this->log->write('Abandoned cart details not found for cart_id: ' . (int)$cart_id);
        return false;
    }

    public function getCartProducts($cart_id) {
        $query = $this->db->query("SELECT `cart_data` FROM `" . DB_PREFIX . "oct_abandoned_cart` 
                                   WHERE `abandoned_cart_id` = " . (int)$cart_id . " LIMIT 1");

        if ($query->num_rows) {
            $cart_data = json_decode($query->row['cart_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log->write('Error decoding cart_data for abandoned_cart_id: ' . (int)$cart_id . ' - ' . json_last_error_msg());
            }
            if (!is_array($cart_data)) {
                return [];
            }
            $products = [];

            foreach ($cart_data as $item) {
                $product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;

                $product_query = $this->db->query("SELECT `name`, `price` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $product_id . " AND `status` = 1 LIMIT 1");

                if ($product_query->num_rows) {
                    $products[] = [
                        'product_id' => $product_id,
                        'name'       => $product_query->row['name'],
                        'quantity'   => $quantity,
                        'price'      => $product_query->row['price']
                    ];
                } else {
                    $this->log->write('Product not found or inactive for product_id: ' . $product_id . ' in cart_id: ' . (int)$cart_id);
                }
            }
            return $products;
        }
        return [];
    }  

    public function createShortLink($raw_token, $signature, $abandoned_cart_id = null) {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        $ttl_days = (int)($module_settings['cookie_lifetime'] ?? 10);

        $short_id = $this->generateShortId();

        $store_id = (int)$this->config->get('config_store_id');

        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "oct_abandoned_cart_short_link`
            SET short_id = '" . $this->db->escape($short_id) . "',
                raw_token = '" . $this->db->escape($raw_token) . "',
                signature = '" . $this->db->escape($signature) . "',
                abandoned_cart_id = " . (int)$abandoned_cart_id . ",
                clicks = 0,
                date_created = NOW(),
                date_expires = DATE_ADD(NOW(), INTERVAL " . (int)$ttl_days . " DAY),
                store_id = " . (int)$store_id . "
        ");

        return $short_id;
    }

    public function getOrCreateShortLink($raw_token, $signature, $abandoned_cart_id = null) {
        $query = $this->db->query("SELECT short_id FROM `" . DB_PREFIX . "oct_abandoned_cart_short_link`
            WHERE raw_token = '" . $this->db->escape($raw_token) . "'
            AND signature = '" . $this->db->escape($signature) . "'
            AND date_expires > NOW()
            LIMIT 1");

        if ($query->num_rows) {
            return $query->row['short_id'];
        }

        return $this->createShortLink($raw_token, $signature, $abandoned_cart_id);
    }

    public function getRestoreDataByShortId($short_id) {
        $query = $this->db->query("SELECT raw_token, signature, abandoned_cart_id
            FROM `" . DB_PREFIX . "oct_abandoned_cart_short_link`
            WHERE short_id = '" . $this->db->escape($short_id) . "'
            AND date_expires > NOW()
            LIMIT 1");

        return $query->num_rows ? $query->row : false;
    }

    public function incrementClickCount($short_id) {
        $this->db->query("
            UPDATE `" . DB_PREFIX . "oct_abandoned_cart_short_link`
            SET clicks = clicks + 1
            WHERE short_id = '" . $this->db->escape($short_id) . "'
        ");
    }

    public function deleteExpiredShortLinks() {
        $sql = "
            DELETE FROM `" . DB_PREFIX . "oct_abandoned_cart_short_link`
            WHERE date_expires < NOW()
        ";
        $this->db->query($sql);
    }

    private function generateShortId() {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars_length = strlen($chars);

        do {
            $short_id = '';
            for ($i = 0; $i < 7; $i++) {
                try {
                    $random_index = random_int(0, $chars_length - 1);
                } catch (Exception $e) {
                    $random_index = mt_rand(0, $chars_length - 1);
                }
                $short_id .= $chars[$random_index];
            }

            $query = $this->db->query("SELECT COUNT(*) AS total 
                FROM `" . DB_PREFIX . "oct_abandoned_cart_short_link` 
                WHERE short_id = '" . $this->db->escape($short_id) . "'");
        } while ($query->row['total'] > 0);

        return $short_id;
    }    

}