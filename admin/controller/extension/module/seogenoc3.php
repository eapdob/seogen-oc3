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
            $this->request->post['seogenoc3_status'] = (isset($this->request->post['seogenoc3_status']) && $this->request->post['seogenoc3_status'] == 1) ? 1 : 0;

//            $this->request->post['seogenoc3']['seogenoc3_overwrite'] = (isset($this->request->post['seogenoc3_overwrite']) && $this->request->post['seogenoc3_overwrite'] == 1) ? 1 : 0;

            $this->model_setting_setting->editSetting('seogenoc3', $this->request->post);
            //$this->model_setting_setting->editSetting('seogenoc3_status', $this->request->post['seogenoc3_status']);

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

        // languages
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        // profiles
        $data['profiles'] = $this->getProfiles();

        // profile's actions
        $data['action_profile_add'] = 'index.php?route=extension/module/seogenoc3/addProfile&user_token=' . $this->request->get['user_token'];
        $data['action_profile_get'] = 'index.php?route=extension/module/seogenoc3/getProfile&user_token=' . $this->request->get['user_token'];
        $data['action_profile_del'] = 'index.php?route=extension/module/seogenoc3/deleteProfile&user_token=' . $this->request->get['user_token'];

        // categories
        $categories = $this->model_catalog_category_getAllCategories();
        $data['categories'] = $this->getAllCategories($categories);

        // manufacturers
        $data['manufacturers'] = $this->model_catalog_category_getAllManufacturers();

        // seogenoc3 config
        if (isset($this->request->post['seogenoc3'])) {
            $data['seogenoc3'] = $this->request->post['seogenoc3'];
        } elseif ($this->config->get('seogenoc3')) {
            $data['seogenoc3'] = $this->config->get('seogenoc3');
        }

        // seogeonoc3 status
        if (isset($this->request->post['seogenoc3_status'])) {
            $data['seogenoc3_status'] = $this->request->post['seogenoc3_status'];
        } elseif ($this->config->get('seogenoc3_status')) {
            $data['seogenoc3_status'] = $this->config->get('seogenoc3_status');
        }

        // seogenoc3 categories
        if (!isset($data['seogenoc3']['only_categories'])) {
            $data['seogenoc3']['only_categories'] = array();
        }

        // seogenoc3 manufacturers
        if (!isset($data['seogenoc3']['only_manufacturers'])) {
            $data['seogenoc3']['only_manufacturers'] = array();
        }

        $this->load->model('design/layout');
        $data['layouts'] = $this->model_design_layout->getLayouts();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/seogenoc3', $data));
    }

    public function install()
    {
        $this->load->language('extension/module/seogenoc3');

        $this->load->model('setting/setting');

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

        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "seogen_profile'");
        if (!$query->num_rows) {
            $this->db->query("CREATE TABLE `" . DB_PREFIX . "seogen_profile` (" .
                " `profile_id` int(11) NOT NULL AUTO_INCREMENT," .
                " `name` varchar(255) NOT NULL," .
                " `data` text NOT NULL," .
                " PRIMARY KEY (`profile_id`)" .
                " ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        }

        $seogenoc3 = $this->getDefaultTags();

        $this->model_setting_setting->editSetting('seogenoc3', $seogenoc3);

//        $this->load->model('setting/event');
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/product/addProduct/after', 'extension/module/seogenoc3/eventGenProductAdd');
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/product/editProduct/after', 'extension/module/seogenoc3/eventGenProductEdit');
//
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/category/addCategory/after', 'extension/module/seogenoc3/eventGenCategoryAdd');
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/category/editCategory/after', 'extension/module/seogenoc3/eventGenCategoryEdit');
//
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/manufacturer/addManufacturer/after', 'extension/module/seogenoc3/eventGenManufacturerAdd');
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/manufacturer/editManufacturer/after', 'extension/module/seogenoc3/eventGenManufacturerEdit');
//
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/information/addInformation/after', 'extension/module/seogenoc3/eventGenInformationAdd');
//        $this->model_setting_event->addEvent('seogenoc3', 'admin/model/catalog/information/editInformation/after', 'extension/module/seogenoc3/eventGenInformationEdit');
    }

    public function uninstall()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEvent('seogenoc3');
    }

    private function getDefaultTags()
    {
        $seogenoc3_tags = array(
            'seogenoc3_status' => 1,
            'seogenoc3' => array(
//                'seogenoc3_overwrite' => 1,
                'categories_template' => $this->language->get('text_categories_tags'),
                'categories_meta_h1_template' => $this->language->get('text_categories_meta_h1_tags'),
                'categories_meta_title_template' => $this->language->get('text_categories_meta_title_tags'),
                'categories_meta_keyword_template' => $this->language->get('text_categories_meta_keyword_tags'),
                'categories_meta_description_template' => $this->language->get('text_categories_meta_description_tags'),
                'categories_description_template' => $this->language->get('text_categories_description_tags'),
                'categories_meta_description_limit' => 160,
                'products_template' => $this->language->get('text_products_tags'),
                'products_meta_h1_template' => $this->language->get('text_products_meta_h1_tags'),
                'products_meta_title_template' => $this->language->get('text_products_meta_title_tags'),
                'products_meta_keyword_template' => $this->language->get('text_products_meta_keyword_tags'),
                'products_meta_description_template' => $this->language->get('text_products_meta_description_tags'),
                'products_description_template' => $this->language->get('text_products_description_tags'),
                'products_meta_description_limit' => 160,
                'products_model_template' => $this->language->get('text_products_model_tags'),
                'products_tag_template' => $this->language->get('text_products_tag_tags'),
                'manufacturers_template' => $this->language->get('text_manufacturers_tags'),
                'manufacturers_meta_h1_template' => $this->language->get('text_manufacturers_meta_h1_tags'),
                'manufacturers_meta_title_template' => $this->language->get('text_manufacturers_meta_title_tags'),
                'manufacturers_meta_keyword_template' => $this->language->get('text_manufacturers_meta_keyword_tags'),
                'manufacturers_meta_description_template' => $this->language->get('text_manufacturers_meta_description_tags'),
                'manufacturers_description_template' => $this->language->get('text_manufacturers_description_tags'),
                'manufacturers_meta_description_limit' => 160,
                'informations_template' => $this->language->get('text_informations_tags'),
                'informations_meta_h1_template' => $this->language->get('text_informations_meta_h1_tags'),
                'informations_meta_title_template' => $this->language->get('text_informations_meta_title_tags'),
                'informations_meta_keyword_template' => $this->language->get('text_informations_meta_keyword_tags'),
                'informations_meta_description_template' => $this->language->get('text_informations_meta_description_tags'),
                'informations_description_template' => $this->language->get('text_informations_description_tags'),
                'informations_meta_description_limit' => 160,
            )
        );

        foreach ($this->getTables() as $table => $val) {
            $query = $this->db->query("DESC `" . DB_PREFIX . $table . "`");
            $fields = array();
            foreach ($query->rows as $row) {
                $fields[] = $row['Field'];
            }
            foreach ($this->getFields() as $field_name => $tmpl) {
                if (!in_array($field_name, $fields)) {
                    $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD `" . $field_name . "` varchar(255) NOT NULL");
                }
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

        return $seogenoc3_tags;
    }

    private function getTables()
    {
        return array(
            "category_description" => "categories",
            "product_description" => "products",
            "manufacturer_description" => "manufacturers",
            "information_description" => "informations",
        );
    }

    private function getFields()
    {
        return array(
            "meta_title" => "meta_title",
            "meta_h1" => "meta_h1",
            "meta_description" => "meta_description",
            "meta_keyword" => "meta_keyword");
    }

    private function saveSettings($data)
    {
        //$seogenoc3_status = (int)$data['seogenoc3_status'];
        //$seogenoc3 = $data['seogenoc3'];
        //$this->load->model('setting/setting');
        //$this->model_setting_setting->editSetting('seogenoc3', array('seogenoc3' => $seogenoc3, 'seogenoc3_status' => $seogenoc3_status));

        $seogenoc3_status = $this->config->get('seogenoc3_status');
        $seogenoc3 = $this->config->get('seogenoc3');
        foreach ($data as $key => $val) {
            if ($key == 'seogenoc3') {
                foreach ($val as $k => $v) {
                    if (in_array($k, array_keys($seogenoc3))) {
                        $seogenoc3[$k] = $v;
                    } else {
                        $seogenoc3[$k] = $v;
                    }
                }
            }
        }
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('seogenoc3', array('seogenoc3' => $seogenoc3, 'seogenoc3_status' => $seogenoc3_status));
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
                $this->model_extension_module_seogenoc3->generateCategories($this->request->post['seogenoc3']);
            } elseif ($name == 'products') {
                $this->model_extension_module_seogenoc3->generateProducts($this->request->post['seogenoc3']);
            } elseif ($name == 'manufacturers') {
                $this->model_extension_module_seogenoc3->generateManufacturers($this->request->post['seogenoc3']);
            } elseif ($name == 'informations') {
                $this->model_extension_module_seogenoc3->generateInformations($this->request->post['seogenoc3']);
            }

            $this->response->setOutput($this->language->get('text_success_generation') . "</br><b>" . $this->language->get('text_total_execution_time') . "</b> " . (microtime(true) - $time_start) .
                "<br/>" . "<b>" . $this->language->get('text_memory_usage') . "</b> " . number_format((memory_get_usage() - $base_memory_usage) / 1024 / 1024, 2, '.', '') . "Mb");
            $this->saveSettings($this->request->post);
            $this->cache->delete('seopro');
        }
    }

    private function model_catalog_category_getAllCategories()
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

    private function model_catalog_category_getAllManufacturers()
    {
        $manufacturer_data = $this->cache->get('manufacturer.all.' . $this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'));

        if (!$manufacturer_data || !is_array($manufacturer_data)) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer m ORDER BY m.name");

            $manufacturer_data = array();
            foreach ($query->rows as $row) {
                $manufacturer_data[] = $row;
            }

            $this->cache->set('manufacturer.all.' . $this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'), $manufacturer_data);
        }

        return $manufacturer_data;
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

    public function getProfile()
    {
        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['profile_id']) && $this->request->post['profile_id'] != '') {
            $profileId = (int)$this->request->post['profile_id'];

            $this->load->language('extension/module/seogenoc3');
            $this->load->model('extension/module/seogenoc3');

            $profile = $this->model_extension_module_seogenoc3->getProfile($profileId);

            if ($profile) {
                $json['profile'] = $profile;
                $json['result'] = 'success';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addProfile()
    {
        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['data']) && $this->request->post['data'] != '') {
            $decoded = urldecode(base64_decode($this->request->post['data']));
            $profileName = '';

            if ($decoded) {
                $decodedDelimiter = explode('&', $decoded);
                $decodedDelimiterEq = array();
                foreach ($decodedDelimiter as $decDel) {
                    $decodedExp = explode('=', $decDel);
                    if (isset($decodedExp[0]) && isset($decodedExp[1])) {
                        if ($decodedExp[0] === 'seogenoc3_profile_name') {
                            $profileName = $decodedExp[1];
                        }
                        if (!in_array($decodedExp[0], array('seogenoc3[main_category_exists]', 'seogenoc3_status', 'seogenoc3_profile_name'))) {
                            $key = str_replace(array('seogenoc3[', '[', ']', '[]'), '', $decodedExp[0]);

                            if ($key === 'only_categories' || $key === 'only_manufacturers') {
                                $decodedDelimiterEq[$key][] = str_replace('  ', '', $decodedExp[1]);
                            } else {
                                $decodedDelimiterEq[$key] = str_replace('  ', '', $decodedExp[1]);
                            }
                        }
                    }
                }

                $this->load->language('extension/module/seogenoc3');
                $this->load->model('extension/module/seogenoc3');

                $profileId = $this->model_extension_module_seogenoc3->addProfile($profileName, serialize($decodedDelimiterEq));

                if ($profileId > 0) {
                    $json['profile_id'] = $profileId;
                    $json['result'] = 'success';
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteProfile()
    {
        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['profile_id']) && $this->request->post['profile_id'] != '') {
            $profileId = (int)$this->request->post['profile_id'];

            $this->load->language('extension/module/seogenoc3');
            $this->load->model('extension/module/seogenoc3');

            $data = $this->model_extension_module_seogenoc3->getProfile($profileId);

            if ($data) {
                $res = $this->model_extension_module_seogenoc3->deleteProfile($profileId);

                if ($res->num_rows > 0) {
                    $json['result'] = 'success';
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getProfiles()
    {
        $this->load->model('extension/module/seogenoc3');
        $profiles = $this->model_extension_module_seogenoc3->getProfiles();
        return $profiles;
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/seogenoc3')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

//    public function eventGenCategoryAdd(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_category_id = $data[0];
//
//            if ($output_category_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyCategory($output_category_id);
//            }
//        }
//    }
//
//    public function eventGenCategoryEdit(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_category_id = $data[0];
//
//            if ($output_category_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyCategory($output_category_id);
//            }
//        }
//    }
//
//    public function eventGenProductAdd(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_product_id = $data[0];
//
//            if ($output_product_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyProduct($output_product_id);
//
//                return $output_product_id;
//            }
//        }
//
//        return false;
//    }
//
//    public function eventGenProductEdit(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_product_id = $data[0];
//
//            if ($output_product_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyProduct($output_product_id);
//            }
//        }
//    }
//
//    public function eventGenManufacturerAdd(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_manufacturer_id = $data[0];
//
//            if ($output_manufacturer_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyManufacturer($output_manufacturer_id);
//
//                return $output_manufacturer_id;
//            }
//        }
//
//        return false;
//    }
//
//    public function eventGenManufacturerEdit(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_manufacturer_id = $data[0];
//
//            if ($output_manufacturer_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyManufacturer($output_manufacturer_id);
//            }
//        }
//    }
//
//    public function eventGenInformationAdd(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_information_id = $data[0];
//
//            if ($output_information_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyInformation($output_information_id);
//
//                return $output_information_id;
//            }
//        }
//
//        return false;
//    }
//
//    public function eventGenInformationEdit(&$route, &$data, &$output)
//    {
//        if ($this->config->get('seogenoc3_status') && $this->config->get('seogenoc3_status') == 1 && isset($data[0])) {
//            $output_information_id = $data[0];
//
//            if ($output_information_id) {
//                $this->load->language('extension/module/seogenoc3');
//                $this->load->model('extension/module/seogenoc3');
//                $this->model_extension_module_seogenoc3->urlifyInformation($output_information_id);
//            }
//        }
//    }
}