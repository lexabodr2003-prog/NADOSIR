<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsAdminMenu extends Controller {

    public function index(&$route, &$data) {

        $this->language->load('octemplates/oct_deals');

        $oct_deals = [];

        if ($this->user->hasPermission('access', 'extension/theme/oct_deals')) {
            $oct_deals[] = array(
                'name'	   => $this->language->get('text_oct_deals'),
                'href'     => $this->url->link('extension/theme/oct_deals', 'user_token=' . $this->session->data['user_token']. '&store_id=0', true),
                'children' => []
            );
        }

        $blogs = [];

        if ($this->user->hasPermission('access', 'octemplates/blog/oct_blogcategory')) {
            $blogs[] = [
                'name'	   => $this->language->get('text_oct_blogcategory'),
                'href'     => $this->url->link('octemplates/blog/oct_blogcategory', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/blog/oct_blogarticle')) {
            $blogs[] = [
                'name'	   => $this->language->get('text_oct_blogarticle'),
                'href'     => $this->url->link('octemplates/blog/oct_blogarticle', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/blog/oct_blogcomments')) {
            $blogs[] = [
                'name'	   => $this->language->get('text_oct_blogcomments'),
                'href'     => $this->url->link('octemplates/blog/oct_blogcomments', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/blog/oct_blogsettings')) {
            $blogs[] = [
                'name'	   => $this->language->get('text_oct_blogsettings'),
                'href'     => $this->url->link('octemplates/blog/oct_blogsettings', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if (!empty($blogs)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_oct_blog'),
                'href'     => '',
                'children' => $blogs
            ];
        }

        $designs = [];

        if ($this->user->hasPermission('access', 'octemplates/design/oct_banner_plus')) {
            $designs[] = [
                'name'	   => $this->language->get('text_oct_banner_plus'),
                'href'     => $this->url->link('octemplates/design/oct_banner_plus', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/design/oct_slideshow_plus')) {
            $designs[] = [
                'name'	   => $this->language->get('text_oct_slideshow_plus'),
                'href'     => $this->url->link('octemplates/design/oct_slideshow_plus', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if (!empty($designs)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_banner'),
                'href'     => '',
                'children' => $designs
            ];
        }
        
        $sreviews = [];

        if ($this->user->hasPermission('access', 'octemplates/module/oct_sreview_subject')) {
            $sreviews[] = [
                'name'	   => $this->language->get('text_oct_sreview_subject'),
                'href'     => $this->url->link('octemplates/module/oct_sreview_subject', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_sreview_reviews')) {
            $sreviews[] = [
                'name'	   => $this->language->get('text_oct_sreview_reviews'),
                'href'     => $this->url->link('octemplates/module/oct_sreview_reviews', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_sreview_setting')) {
            $sreviews[] = [
                'name'	   => $this->language->get('text_oct_sreview_setting'),
                'href'     => $this->url->link('octemplates/module/oct_sreview_setting', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if (!empty($sreviews)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_oct_sreview'),
                'href'     => '',
                'children' => $sreviews
            ];
        }

        $product_tabs = [];

        if ($this->user->hasPermission('access', 'octemplates/module/oct_product_tabs')) {
            $product_tabs[] = [
                'name'	   => $this->language->get('text_oct_product_tabs_list'),
                'href'     => $this->url->link('octemplates/module/oct_product_tabs', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_product_tabs_settings')) {
            $product_tabs[] = [
                'name'	   => $this->language->get('text_oct_product_tabs_setting'),
                'href'     => $this->url->link('octemplates/module/oct_product_tabs_settings', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if (!empty($product_tabs)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_oct_product_tabs'),
                'href'     => '',
                'children' => $product_tabs
            ];
        }

        $modules = [];

        if ($this->user->hasPermission('access', 'octemplates/module/oct_sms_notify')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_sms_notify'),
                'href'     => $this->url->link('octemplates/module/oct_sms_notify', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_otp_login')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_otp_login'),
                'href'     => $this->url->link('octemplates/module/oct_otp_login', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_review_reminder')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_review_reminder'),
                'href'     => $this->url->link('octemplates/module/oct_review_reminder', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_smart_checkout')) {
            $modules[] = [
                'name'	   => $this->language->get('text_menu_oct_smart_checkout'),
                'href'     => $this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_abandoned_cart')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_abandoned_cart'),
                'href'     => $this->url->link('octemplates/module/oct_abandoned_cart', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_smart_register')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_smart_register'),
                'href'     => $this->url->link('octemplates/module/oct_smart_register', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_product_set')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_product_sets'),
                'href'     => $this->url->link('octemplates/module/oct_product_set', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_popup_view')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_popup_view'),
                'href'     => $this->url->link('octemplates/module/oct_popup_view', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_popup_purchase')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_popup_purchase'),
                'href'     => $this->url->link('octemplates/module/oct_popup_purchase', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_stock_notifier')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_stock_notifier'),
                'href'     => $this->url->link('octemplates/module/oct_stock_notifier', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_popup_call_phone')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_popup_call_phone'),
                'href'     => $this->url->link('octemplates/module/oct_popup_call_phone', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_popup_found_cheaper')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_popup_found_cheaper_menu'),
                'href'     => $this->url->link('octemplates/module/oct_popup_found_cheaper', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_policy')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_policy'),
                'href'     => $this->url->link('octemplates/module/oct_policy', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_information_bar')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_information_bar'),
                'href'     => $this->url->link('octemplates/module/oct_information_bar', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_subscribe')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_subscribe'),
                'href'     => $this->url->link('octemplates/module/oct_subscribe', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_404_page')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_404_page'),
                'href'     => $this->url->link('octemplates/module/oct_404_page', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if ($this->user->hasPermission('access', 'octemplates/module/oct_product_main_image_option')) {
            $modules[] = [
                'name'	   => $this->language->get('text_oct_product_main_image_option'),
                'href'     => $this->url->link('octemplates/module/oct_product_main_image_option', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if (!empty($modules)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_module'),
                'href'     => '',
                'children' => $modules
            ];
        }

        if (!empty($blog)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_oct_blog'),
                'href'     => '',
                'children' => $blog
            ];
        }

        $faqs = [];

        if ($this->user->hasPermission('access', 'octemplates/faq/oct_product_faq')) {
            $faqs[] = [
                'name'	   => $this->language->get('text_oct_product_faq'),
                'href'     => $this->url->link('octemplates/faq/oct_product_faq', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if (!empty($faqs)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_oct_faq'),
                'href'     => '',
                'children' => $faqs
            ];
        }

        $stickers = [];

        if ($this->user->hasPermission('access', 'octemplates/stickers/oct_stickers_settings')) {
            $stickers[] = [
                'name'	   => $this->language->get('text_oct_stickers_settings_menu'),
                'href'     => $this->url->link('octemplates/stickers/oct_stickers_settings', 'user_token=' . $this->session->data['user_token'], true),
                'children' => []
            ];
        }

        if (!empty($stickers)) {
            $oct_deals[] = [
                'name'	   => $this->language->get('text_oct_stickers_main'),
                'href'     => '',
                'children' => $stickers
            ];
        }

        if (!empty($oct_deals)) {

            $first_menu_item = $data['menus'][0];

            $data['menus'][0] = [
                'id'       => 'menu-oct_deals',
                'icon'     => 'fa fa-shield fa-fw',
                'name'     => $this->language->get('text_octemplates'),
                'href'     => '',
                'children' => $oct_deals
            ];

            array_unshift($data['menus'], $first_menu_item);
        }
        
    }
}