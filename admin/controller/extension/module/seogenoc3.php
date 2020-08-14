<?php

class ControllerExtensionModuleSeogenoc3 extends Controller
{

    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/seogenoc3');

        $this->document->setTitle(strip_tags($this->language->get('heading_title')));

        $this->load->model('setting/setting');

        $data['user_token'] = $this->session->data['user_token'];

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->request->post['seogenoc3_status'] = isset($this->request->post['seogenoc3_status']) ? 1 : 0;

            if (!isset($this->request->post['seogenoc3']['seogenoc3_overwrite'])) {
                $this->request->post['seogenoc3']['seogenoc3_overwrite'] = 0;
            }

            $this->model_setting_setting->editSetting('seogenoc3', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/seogenoc3', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/seogenoc3', 'user_token=' . $this->session->data['user_token'], true);

        $data['generate'] = $this->url->link('extension/module/seogenoc3/generate', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $this->load->model('design/layout');

        $data['layouts'] = $this->model_design_layout->getLayouts();

        $this->load->model('catalog/category');

        $categories = $this->model_catalog_category_getAllCategories();

        $data['categories'] = $this->getAllCategories($categories);

        if (isset($this->request->post['seogenoc3'])) {
            $data['seogenoc3'] = $this->request->post['seogenoc3'];
        } elseif ($this->config->get('seogenoc3')) {
            $data['seogenoc3'] = $this->config->get('seogenoc3');
        }

        if (!isset($data['seogenoc3']['only_categories'])) {
            $data['seogenoc3']['only_categories'] = array();
        }

        $default_tags = $this->getDefaultTags();
        foreach ($default_tags['seogenoc3'] as $k => $v) {
            if (!isset($data['seogenoc3'][$k])) {
                $data['seogenoc3'][$k] = $v;
            }
        }

        $data['seogenoc3_status'] = $this->config->get('seogenoc3_status');
        if (isset($this->request->post['seogenoc3_status'])) {
            $data['seogenoc3_status'] = $this->request->post['seogenoc3_status'];
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/seogenoc3', $data));
    }


    private function getDefaultTags()
    {
        $seogenoc3_tags = array('seogenoc3_status' => 1,
            'seogenoc3' => array(
                'seogenoc3_overwrite' => 1,
                'categories_template' => $this->language->get('text_categories_tags'),
                'categories_description_template' => $this->language->get('text_categories_description_tags'),
                'categories_meta_description_limit' => 160,
                'products_template' => $this->language->get('text_products_tags'),
                'products_model_template' => $this->language->get('text_products_model_tags'),
                'products_description_template' => $this->language->get('text_products_description_tags'),
                'products_meta_description_limit' => 160,
                'products_img_alt_template' => $this->language->get('text_products_img_alt'),
                'products_img_title_template' => $this->language->get('text_products_img_title'),
                'manufacturers_template' => $this->language->get('text_manufacturers_tags'),
                'manufacturers_description_template' => $this->language->get('text_manufacturers_description_tags'),
                'informations_template' => $this->language->get('text_informations_tags'),
            )
        );

        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "manufacturer_description'");
        if (!$query->num_rows) {
            $this->db->query("CREATE TABLE `" . DB_PREFIX . "manufacturer_description` (
			 `manufacturer_id` int(11) NOT NULL DEFAULT '0',
			 `language_id` int(11) NOT NULL DEFAULT '0',
			 `description` text NOT NULL,
			 `description3` text NOT NULL,
			 `meta_description` varchar(255) NOT NULL,
			 `meta_keyword` varchar(255) NOT NULL,
			 `meta_title` varchar(255) NOT NULL,
			 `meta_h1` varchar(255) NOT NULL,
			 PRIMARY KEY (`manufacturer_id`,`language_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        }

        foreach (
            array(
                "category_description" => "categories",
                "product_description" => "products",
                "manufacturer_description" => "manufacturers",
                "information_description" => "informations",
            ) as $table => $val) {
            $query = $this->db->query("DESC `" . DB_PREFIX . $table . "`");
            $fields = array();
            foreach ($query->rows as $row) {
                $fields[] = $row['Field'];
            }
            foreach (array(
                         "meta_title" => "title",
                         "meta_h1" => "h1",
                         "meta_description" => "meta_description",
                         "meta_keyword" => "meta_keyword"
                     ) as $field_name => $tmpl) {
                if (!in_array($field_name, $fields)) {
                    $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD `" . $field_name . "` varchar(255) NOT NULL");
                }
                $seogenoc3_tags['seogenoc3'][$val . '_' . $tmpl . '_template'] = $this->language->get('text_' . $val . '_' . $tmpl . '_tags');
            }
        }

        $query = $this->db->query("DESC `" . DB_PREFIX . "product_to_category`");
        $fields = array();
        foreach ($query->rows as $row) {
            $fields[] = $row['Field'];
        }
        if (!in_array("main_category", $fields)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "product_to_category` ADD `main_category` tinyint(1) NOT NULL DEFAULT '0'");
        }
        $seogenoc3_tags['seogenoc3']['main_category_exists'] = true;

        $seogenoc3_tags['seogenoc3']['product_tag_table'] = 0;
        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "product_tag'");
        if ($query->num_rows) {
            $seogenoc3_tags['seogenoc3']['product_tag_table'] = 1;
        }
        $seogenoc3_tags['seogenoc3']['products_tag_template'] = $this->language->get('text_products_tag_tags');

        return $seogenoc3_tags;
    }

    public function install()
    {
        $this->load->language('extension/module/seogenoc3');
        $this->load->model('setting/setting');
        $seogenoc3_tags = $this->getDefaultTags();
        $this->model_setting_setting->editSetting('seogenoc3', $seogenoc3_tags);
    }

    public function generate()
    {
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['name']) && $this->validate()) {
            $time_start = microtime(true);
            $base_memory_usage = memory_get_usage();

            $this->load->language('extension/module/seogenoc3');
            $this->load->model('extension/module/seogenoc3');
            $name = $this->request->post['name'];
            if ($name == 'categories') {
                $this->model_module_seogenoc3->generateCategories($this->request->post['seogenoc3']);
            } elseif ($name == 'products') {
                $this->model_module_seogenoc3->generateProducts($this->request->post['seogenoc3']);
            } elseif ($name == 'manufacturers') {
                $this->model_module_seogenoc3->generateManufacturers($this->request->post['seogenoc3']);
            } elseif ($name == 'informations') {
                $this->model_module_seogenoc3->generateInformations($this->request->post['seogenoc3']);
            }

            $this->response->setOutput($this->language->get('text_success_generation') . "</br><b>" . $this->language->get('text_total_execution_time') . "</b> " . (microtime(true) - $time_start) .
                "<br/>" . "<b>" . $this->language->get('text_memory_usage') . "</b> " . number_format((memory_get_usage() - $base_memory_usage) / 1024 / 1024, 2, '.', '') . "Mb");
            $this->saveSettings($this->request->post['seogenoc3']);
            $this->cache->delete('seopro');
        }
    }

    private function getAllCategories($categories, $parent_id = 0, $parent_name = '')
    {
        $output = array();

        if (array_key_exists($parent_id, $categories)) {
            if ($parent_name != '') {
                $parent_name .= $this->language->get('text_separator');
            }

            foreach ($categories[$parent_id] as $category) {
                $output[$category['category_id']] = array(
                    'category_id' => $category['category_id'],
                    'name' => $parent_name . $category['name']
                );

                $output += $this->getAllCategories($categories, $category['category_id'], $parent_name . $category['name']);
            }
        }

        return $output;
    }

    public function model_catalog_category_getAllCategories()
    {
        $category_data = $this->cache->get('category.all.' . $this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'));

        if (!$category_data || !is_array($category_data)) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  ORDER BY c.parent_id, c.sort_order, cd.name");

            $category_data = array();
            foreach ($query->rows as $row) {
                $category_data[$row['parent_id']][$row['category_id']] = $row;
            }

            $this->cache->set('category.all.' . $this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'), $category_data);
        }

        return $category_data;
    }

    private function saveSettings($data)
    {
        $seogenoc3_status = $this->config->get('seogenoc3_status');
        $seogenoc3 = $this->config->get('seogenoc3');
        foreach ($data as $key => $val) {
            if (in_array($key, array_keys($seogenoc3))) {
                $seogenoc3[$key] = $val;
            }
        }
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('seogenoc3', array('seogenoc3' => $seogenoc3, 'seogenoc3_status' => $seogenoc3_status));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/seogenoc3')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}