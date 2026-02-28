<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsUserAccount extends Controller {

    public function adminFront(&$route, &$data, &$output) {

        if (is_string($output) && !empty($output)) {

            if (isset($this->request->post['module_account_download_view'])) {
                $data['module_account_download_view'] = $this->request->post['module_account_download_view'];
            } else {
                $data['module_account_download_view'] = $this->config->get('module_account_download_view');
            }
    
            if (isset($this->request->post['module_account_recurring_view'])) {
                $data['module_account_recurring_view'] = $this->request->post['module_account_recurring_view'];
            } else {
                $data['module_account_recurring_view'] = $this->config->get('module_account_recurring_view');
            }
    
            if (isset($this->request->post['module_account_reward_view'])) {
                $data['module_account_reward_view'] = $this->request->post['module_account_reward_view'];
            } else {
                $data['module_account_reward_view'] = $this->config->get('module_account_reward_view');
            }
    
            if (isset($this->request->post['module_account_return_view'])) {
                $data['module_account_return_view'] = $this->request->post['module_account_return_view'];
            } else {
                $data['module_account_return_view'] = $this->config->get('module_account_return_view');
            }
    
            if (isset($this->request->post['module_account_transaction_view'])) {
                $data['module_account_transaction_view'] = $this->request->post['module_account_transaction_view'];
            } else {
                $data['module_account_transaction_view'] = $this->config->get('module_account_transaction_view');
            }
    
            if (isset($this->request->post['module_account_newsletter_view'])) {
                $data['module_account_newsletter_view'] = $this->request->post['module_account_newsletter_view'];
            } else {
                $data['module_account_newsletter_view'] = $this->config->get('module_account_newsletter_view');
            }
    
            if (isset($this->request->post['module_account_affiliate_view'])) {
                $data['module_account_affiliate_view'] = $this->request->post['module_account_affiliate_view'];
            } else {
                $data['module_account_affiliate_view'] = $this->config->get('module_account_affiliate_view');
            }

            if (isset($this->request->post['module_account_payment_address_view'])) {
                $data['module_account_payment_address_view'] = $this->request->post['module_account_payment_address_view'];
            } else {
                $data['module_account_payment_address_view'] = $this->config->get('module_account_payment_address_view');
            } 
            
            $search = '</form>';
            $add = $this->load->view('octemplates/events/user_account_settings', $data);
            $pos = strpos($output, $search);

            if ($pos !== false) {
                $pos2 = strpos($output, $search, $pos + strlen($search));
                if ($pos2 !== false) {
                    $output = substr_replace($output, $add . $search, $pos2, strlen($search));
                } else {
                    $output = substr_replace($output, $add . $search, $pos, strlen($search));
                }
            }
        }
    }
}