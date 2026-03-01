<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctProductSet extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('octemplates/module/oct_product_set');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('localisation/language');
        $this->load->model('octemplates/module/oct_product_set');

        $oct_product_set_info = $this->model_octemplates_module_oct_product_set->checkIfExistMainTable();

        if (!$oct_product_set_info) {
            $this->response->redirect($this->url->link('octemplates/module/oct_product_set/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getList();
    }

    public function add() {
        $this->load->language('octemplates/module/oct_product_set');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('octemplates/module/oct_product_set');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_octemplates_module_oct_product_set->addProductSet($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('octemplates/module/oct_product_set');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('octemplates/module/oct_product_set');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_octemplates_module_oct_product_set->editProductSet((int)$this->request->get['set_id'], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    protected function getList() {

        $this->load->model('tool/image');

        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_deals.css');

        $data = array();
        $data['product_sets'] = array();
        $data['filter_action'] = $this->url->link('octemplates/module/oct_product_set/index', 'user_token=' . $this->session->data['user_token'], true);

        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = min(100, max(1, (int)$this->config->get('config_limit_admin')));
        $start = ($page - 1) * $limit;

        $filter_data = array(
            'filter_set_name'       => isset($this->request->get['filter_set_name']) ? $this->request->get['filter_set_name'] : null,
            'filter_product_name'   => isset($this->request->get['filter_product_name']) ? $this->request->get['filter_product_name'] : null,
            'filter_category_name'  => isset($this->request->get['filter_category_name']) ? $this->request->get['filter_category_name'] : null,
            'filter_manufacturer_name' => isset($this->request->get['filter_manufacturer_name']) ? $this->request->get['filter_manufacturer_name'] : null,
            'start'                 => $start,
            'limit'                 => $limit
        );

        $data['filter_set_name'] = isset($this->request->get['filter_set_name']) ? htmlspecialchars($this->request->get['filter_set_name'], ENT_QUOTES, 'UTF-8') : '';
        $data['filter_product_name'] = isset($this->request->get['filter_product_name']) ? htmlspecialchars($this->request->get['filter_product_name'], ENT_QUOTES, 'UTF-8') : '';
        $data['filter_category_name'] = isset($this->request->get['filter_category_name']) ? htmlspecialchars($this->request->get['filter_category_name'], ENT_QUOTES, 'UTF-8') : '';
        $data['filter_manufacturer_name'] = isset($this->request->get['filter_manufacturer_name']) ? htmlspecialchars($this->request->get['filter_manufacturer_name'], ENT_QUOTES, 'UTF-8') : '';

        $url = '';

        if (isset($this->request->get['filter_set_name'])) {
            $url .= '&filter_set_name=' . urlencode(html_entity_decode($this->request->get['filter_set_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_product_name'])) {
            $url .= '&filter_product_name=' . urlencode(html_entity_decode($this->request->get['filter_product_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_category_name'])) {
            $url .= '&filter_category_name=' . urlencode(html_entity_decode($this->request->get['filter_category_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_manufacturer_name'])) {
            $url .= '&filter_manufacturer_name=' . urlencode(html_entity_decode($this->request->get['filter_manufacturer_name'], ENT_QUOTES, 'UTF-8'));
        }

        $totalProductSets = $this->model_octemplates_module_oct_product_set->getTotalProductSets($filter_data);

        $pagination = new Pagination();
        $pagination->total = $totalProductSets;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($totalProductSets) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($totalProductSets - $limit)) ? $totalProductSets : ((($page - 1) * $limit) + $limit), $totalProductSets, ceil($totalProductSets / $limit));

        $results = $this->model_octemplates_module_oct_product_set->getProductSets($filter_data);

        foreach ($results as $result) {
            $products = [];
            
            foreach ($result['products'] as $product) {
                $image = !empty($product['image']) ? $product['image'] : 'no_image.png';
                $cachedImage = $this->model_tool_image->resize($image, 40, 40);
        
                $products[] = [
                    'name' => htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'),
                    'image' => $cachedImage,
                    'sort_order' => (int)$product['sort_order'],
                    'edit' => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . (int)$product['product_id'], true)
                ];
            }
        
            $data['product_sets'][] = [
                'set_id'      => (int)$result['product_set_id'],
                'name'        => htmlspecialchars($result['name'], ENT_QUOTES, 'UTF-8'),
                'status'      => (int)$result['status'],
                'sort_order'  => (int)$result['sort_order'],
                'date_added'  => htmlspecialchars($result['date_added'], ENT_QUOTES, 'UTF-8'),
                'products'    => $products,
                'edit'        => $this->url->link('octemplates/module/oct_product_set/edit', 'user_token=' . $this->session->data['user_token'] . '&set_id=' . (int)$result['product_set_id'], true)
            ];
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $url = '';

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['user_token'] = $this->session->data['user_token'];
        $data['add'] = $this->url->link('octemplates/module/oct_product_set/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['heading_title'] = $this->language->get('heading_title');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/oct_product_set_list', $data));
    }

    protected function getForm() {

        $this->load->model('setting/store');
        $this->load->model('octemplates/module/oct_product_set');

        $data = array();

        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_deals.css');

        $data['action'] = $this->url->link('octemplates/module/oct_product_set/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'], true);
    
        if (isset($this->request->get['set_id'])) {
            $product_set_info = $this->model_octemplates_module_oct_product_set->getAllProductSetData((int)$this->request->get['set_id']);
            $data['action'] = $this->url->link('octemplates/module/oct_product_set/edit', 'user_token=' . $this->session->data['user_token'] . '&set_id=' . (int)$this->request->get['set_id'], true);
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['error_name'] = $this->error['name'] ?? '';
        $data['error_products_to_show'] = $this->error['products_to_show'] ?? '';
        $data['error_sort_order'] = $this->error['sort_order'] ?? '';
        $data['error_customer_group_id'] = $this->error['customer_group_id'] ?? '';
        $data['error_store_id'] = $this->error['store_id'] ?? '';
        $data['error_product_show_in'] = $this->error['products_show_in'] ?? '';
        $data['error_discount_product_value'] = $this->error['products_to_show_discount'] ?? '';

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $url = '';
    
        if (isset($this->request->post['name'])) {
            $data['name'] = htmlspecialchars($this->request->post['name'], ENT_QUOTES, 'UTF-8');
        } elseif (!empty($product_set_info)) {
            $data['name'] = htmlspecialchars($product_set_info['name'], ENT_QUOTES, 'UTF-8');
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['products_to_show'])) {
            $data['products_to_show'] = $this->request->post['products_to_show'];
        } elseif (!empty($product_set_info)) {
            $data['products_to_show'] = $product_set_info['related_products'];
        } else {
            $data['products_to_show'] = array();
        }

        if (isset($this->request->post['products_show_in'])) {
            $data['products_show_in'] = $this->request->post['products_show_in'];
        } elseif (!empty($product_set_info)) {
            $data['products_show_in'] = $product_set_info['products_show_in'];
        } else {
            $data['products_show_in'] = array();
        }

        if (isset($this->request->post['categories'])) {
            $data['categories'] = $this->request->post['categories'];
        } elseif (!empty($product_set_info)) {
            $data['categories'] = $product_set_info['categories'];
        } else {
            $data['categories'] = array();
        }

        if (isset($this->request->post['manufacturers'])) {
            $data['manufacturers'] = $this->request->post['manufacturers'];
        } elseif (!empty($product_set_info)) {
            $data['manufacturers'] = $product_set_info['manufacturers'];
        } else {
            $data['manufacturers'] = array();
        }
    
        if (isset($this->request->post['sort_order'])) {
            $data['sort_order'] = min(9999, max(0, (int)$this->request->post['sort_order']));
        } elseif (!empty($product_set_info)) {
            $data['sort_order'] = (int)$product_set_info['sort_order'];
        } else {
            $data['sort_order'] = 0;
        }
    
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($product_set_info)) {
            $data['status'] = (isset($product_set_info['status']) && $product_set_info['status']) ? "on" : "off";
        } else {
            $data['status'] = 1;
        }
    
        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
        if (isset($this->request->post['customer_group_id'])) {
            $data['selected_customer_groups'] = $this->request->post['customer_group_id'];
        } elseif (!empty($product_set_info)) {
            $data['selected_customer_groups'] = $product_set_info['user_group_ids'];
        } else {
            $data['selected_customer_groups'] = array();
            $customer_groups = $this->model_customer_customer_group->getCustomerGroups();
            foreach ($customer_groups as $group) {
                $data['selected_customer_groups'][] = $group['customer_group_id'];
            }
        }
    
        if (isset($this->request->post['store_id'])) {
            $data['store_id'] = $this->request->post['store_id'];
        } elseif (!empty($product_set_info)) {
            $data['store_id'] = $product_set_info['store_ids'];
        } else {
            $data['store_id'] = array();
            $data['store_id'][0] = 0;
            $stores = $this->model_setting_store->getStores();
            foreach ($stores as $store) {
                $data['store_id'][] = $store['store_id'];
            }
        }

        if (isset($this->request->post['date_start'])) {
            $data['date_start'] = $this->request->post['date_start'];
        } elseif (!empty($product_set_info)) {
            $data['date_start'] = str_replace('0000-00-00 00:00:00', '', $product_set_info['date_start']);
        } else {
            $data['date_start'] = '';
        }
        
        if (isset($this->request->post['date_end'])) {
            $data['date_end'] = $this->request->post['date_end'];
        } elseif (!empty($product_set_info)) {
            $data['date_end'] = str_replace('0000-00-00 00:00:00', '', $product_set_info['date_end']);
        } else {
            $data['date_end'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['stores'] = array();
        $data['stores'][] = array(
            'store_id' => 0,
            'name' => $this->config->get('config_name')
        );

        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $data['stores'][] = array(
                'store_id' => $store['store_id'],
                'name' => $store['name']
            );
        }

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['heading_title'] = $this->language->get('heading_title');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
    
        $this->response->setOutput($this->load->view('octemplates/module/oct_product_set_form', $data));
    }
    
    public function deleteSelected() {
        $this->load->language('octemplates/module/oct_product_set');

        if (!$this->checkModifyPermission(true)) {
            return;
        }

        $json = [];

        $this->load->model('octemplates/module/oct_product_set');

        $info = $this->model_octemplates_module_oct_product_set->checkSet((int) $this->request->get['delete']);

        if ($info) {
            $data['delete'] = true;
            $data['set_id'] = (int) $this->request->get['delete'];
            $this->model_octemplates_module_oct_product_set->deleteSet($data);
            $this->session->data['success'] = $this->language->get('text_success_deleted');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteAllSelected() {
        $this->load->language('octemplates/module/oct_product_set');

        if (!$this->checkModifyPermission(true)) {
            return;
        }

        $json = [];

        $this->load->model('octemplates/module/oct_product_set');

        if (isset($this->request->request['selected'])) {
            foreach ($this->request->request['selected'] as $subscription_id) {
                $info = $this->model_octemplates_module_oct_product_set->checkSet((int) $subscription_id);

                if ($info) {
                    $data['delete'] = true;
                    $data['set_id'] = (int) $subscription_id;
                    $this->model_octemplates_module_oct_product_set->deleteSet($data);
                    $this->session->data['success'] = $this->language->get('text_success_deleted');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install() {
        $this->load->language('octemplates/module/oct_product_set');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('user/user_group');
        $this->load->model('octemplates/module/oct_product_set');
        $this->model_octemplates_module_oct_product_set->install();

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_product_set');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_product_set');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/total/oct_product_set');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/total/oct_product_set');

        $this->session->data['success'] = $this->language->get('text_success_install');

        $this->response->redirect($this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall() {        
        $this->load->language('octemplates/module/oct_product_set');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('user/user_group');
        $this->load->model('octemplates/module/oct_product_set');

        $this->model_octemplates_module_oct_product_set->uninstall();

        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_product_set');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_product_set');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/total/oct_product_set');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/total/oct_product_set');

        $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_product_set')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 2) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['products_to_show']) || count($this->request->post['products_to_show']) < 2) {
            $this->error['products_to_show'] = $this->language->get('error_products_to_show');
        } else {
            foreach ($this->request->post['products_to_show'] as $id => $product) {
                if (!is_numeric($product['discount_value']) || $product['discount_value'] < 0 || $product['discount_value'] > 100) {
                    $this->error['products_to_show_discount'][(int)$product['product_id']]['discount_value'] = $this->language->get('error_discount_value');
                } 
            }
        }

        if (!is_numeric($this->request->post['sort_order']) || $this->request->post['sort_order'] < 0 || $this->request->post['sort_order'] > 9999) {
            $this->error['sort_order'] = $this->language->get('error_sort_order_text');
        }

        if (empty($this->request->post['customer_group_id'])) {
            $this->error['customer_group_id'] = $this->language->get('error_c_group_id_text');
        }

        if (empty($this->request->post['store_id'])) {
            $this->error['store_id'] = $this->language->get('error_store_id_text');
        }

        if ($this->error) {
            $this->error['warning'] = $this->language->get('error_warning_text');
        }

        return !$this->error;
    }

    private function checkModifyPermission($json_response = false) {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_product_set')) {
            $error_message = $this->language->get('error_permission');
    
            if ($json_response) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(['error' => $error_message]));
            } else {
                $this->session->data['error'] = $error_message;
                $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            }
            return false;
        }
        return true;
    }
}