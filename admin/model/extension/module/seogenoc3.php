<?php

class ModelModuleSeogenoc3 extends Model
{
    private $keywords = false;
    private $manufacturer_desc = false;

    public function __construct($registry)
    {
        parent::__construct($registry);

        require_once(DIR_SYSTEM . 'library/urlify.php');

        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "manufacturer_description'");
        $this->manufacturer_desc = $query->num_rows;
    }

    private function loadKeywords()
    {
        $this->keywords = array();
        $query = $this->db->query("SELECT `query`, LOWER(`keyword`) as 'keyword' FROM " . DB_PREFIX . "seo_url");
        foreach ($query->rows as $row) {
            $this->keywords[$row['query']] = $row['keyword'];
        }
        return $query;
    }

    public function urlifyCategory($category_id)
    {
        $category = $this->getCategories($category_id);
        $this->generateCategory($category[0], $this->config->get('seogenoc3'));
    }

    public function urlifyProduct($product_id)
    {
        $seogenoc3 = $this->config->get('seogenoc3');

        $product = $this->getProducts($seogenoc3, $product_id);
        if (count($product)) {
            $this->generateProduct($product[0], $seogenoc3);
        }
    }

    public function urlifyManufacturer($manufacturer_id)
    {
        $manufacturer = $this->getManufacturers($manufacturer_id);
        $this->generateManufacturer($manufacturer[0], $this->config->get('seogenoc3'));
    }

    public function urlifyInformation($information_id)
    {
        $information = $this->getInformations($information_id);
        $this->generateInformation($information[0], $this->config->get('seogenoc3'));
    }

    public function generateCategories($data)
    {
        if (!empty($data['categories_template'])) {
            if (isset($data['categories_overwrite'])) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` LIKE ('category_id=%');");
            }
            $this->loadKeywords();
        }

        foreach ($this->getCategories() as $category) {
            $this->generateCategory($category, $data);
        }
    }

    public function generateProducts($data)
    {
        if (!empty($data['products_template'])) {
            if (isset($data['products_overwrite'])) {
                if (isset($data['only_categories']) && count($data['only_categories'])) {
                    $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` IN " .
                        "(SELECT concat('product_id=', product_id) FROM `" . DB_PREFIX . "product_to_category` WHERE category_id IN (" . implode(",", $data['only_categories']) . ") )");
                } else {
                    $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` LIKE ('product_id=%');");
                }
            }

            $this->loadKeywords();
        }

        foreach ($this->getProducts($data) as $product) {
            $this->generateProduct($product, $data);
        }
    }

    public function generateManufacturers($data)
    {
        if (!empty($data['manufacturers_template'])) {
            if (isset($data['manufacturers_overwrite'])) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` LIKE ('manufacturer_id=%');");
            }
            $this->loadKeywords();
        }

        foreach ($this->getManufacturers() as $manufacturer) {
            $this->generateManufacturer($manufacturer, $data);
        }
    }

    public function generateInformations($data)
    {
        if (!empty($data['informations_template'])) {
            if (isset($data['informations_overwrite'])) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` LIKE ('information_id=%');");
            }
            $this->loadKeywords();
        }
        foreach ($this->getInformations() as $information) {
            $this->generateInformation($information, $data);
        }
    }

    private function generateCategory($category, $data)
    {
        $language_id = (isset($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id'));

        $tags = array(
            '[category_name]' => $category['name'],
            '[category_description]' => strip_tags(html_entity_decode($category['description'], ENT_QUOTES, 'UTF-8')),
        );

        if (!empty($data['categories_template']) && (isset($data['categories_overwrite']) || is_null($category['keyword']))) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query`='category_id=" . (int)$category['category_id'] . "'");
            $keyword = $this->urlify($data['categories_template'], $tags);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `query`='category_id=" . (int)$category['category_id'] . "', keyword='" . $this->db->escape($keyword) . "'");
        }


        $updates = array();
        if (isset($category['meta_h1']) && (isset($data['categories_h1_overwrite']) || (strlen(trim($category['meta_h1']))) == 0)) {
            $h1 = trim(strtr($data['categories_h1_template'], $tags));
            $updates[] = "`meta_h1`='" . $this->db->escape($h1) . "'";
        }
        if (isset($category['meta_title']) && (isset($data['categories_title_overwrite']) || (strlen(trim($category['meta_title']))) == 0)) {
            $categories_title_template = $data['categories_title_template'];
            if (isset($data['categories_use_expressions'])) {
                $categories_title_template = $this->parseTemplate($categories_title_template);
            }
            $title = trim(strtr($categories_title_template, $tags));

            $updates[] = "`meta_title`='" . $this->db->escape($title) . "'";
        }
        if (isset($category['meta_keyword']) && (isset($data['categories_meta_keyword_overwrite']) || (strlen(trim($category['meta_keyword']))) == 0)) {
            $meta_keyword = trim(strtr($data['categories_meta_keyword_template'], $tags));
            $updates[] = "`meta_keyword`='" . $this->db->escape($meta_keyword) . "'";
        }
        if (isset($category['meta_description']) && (isset($data['categories_meta_description_overwrite']) || (strlen(trim($category['meta_description']))) == 0)) {
            $categories_meta_description_template = $data['categories_meta_description_template'];

            if (isset($data['categories_use_expressions'])) {
                $categories_meta_description_template = $this->parseTemplate($categories_meta_description_template);
            }
            $meta_description = trim(strtr($categories_meta_description_template, $tags));
            if (isset($data['categories_meta_description_limit']) && (int)$data['categories_meta_description_limit']) {
                $meta_description = mb_substr($meta_description, 0, (int)$data['categories_meta_description_limit']);
            }

            $updates[] = "`meta_description`='" . $this->db->escape($meta_description) . "'";
        }
        if (isset($category['description']) && (isset($data['categories_description_overwrite']) || (strlen(trim($category['description']))) == 0)) {
            $categories_description_template = $data['categories_description_template'];

            if (isset($data['categories_use_expressions'])) {
                $categories_description_template = $this->parseTemplate($categories_description_template);
            }
            $description = trim(strtr($categories_description_template, $tags));
            $updates[] = "`description`='" . $this->db->escape($description) . "'";
        }

        if (count($updates)) {
            $this->db->query("UPDATE `" . DB_PREFIX . "category_description`" .
                " SET " . implode(", ", $updates) .
                " WHERE category_id='" . (int)$category['category_id'] . "' AND language_id='" . $language_id . "'");
        }
    }

    private function generateProduct($product, $data)
    {
        $language_id = (isset($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id'));

        $tags = array(
            '[product_id]' => $product['product_id'],
            '[product_name]' => $product['name'],
            '[product_description]' => strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')),
            '[model_name]' => $product['model'],
            '[manufacturer_name]' => $product['manufacturer'],
            '[category_name]' => $product['category'],
            '[sku]' => $product['sku'],
            '[price]' => $this->currency->format($product['price']),
        );
        if (!empty($data['products_template']) && (isset($data['products_overwrite']) || is_null($product['keyword']))) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query`='product_id=" . (int)$product['product_id'] . "'");
            $keyword = $this->urlify($data['products_template'], $tags);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `query`='product_id=" . (int)$product['product_id'] . "', keyword='" . $this->db->escape($keyword) . "'");
        }
        $updates = array();
        if (isset($product['meta_h1']) && (isset($data['products_h1_overwrite']) || (strlen(trim($product['meta_h1']))) == 0)) {
            $h1 = trim(strtr($data['products_h1_template'], $tags));
            $updates[] = "`meta_h1`='" . $this->db->escape($h1) . "'";
        }
        if (isset($product['meta_title']) && (isset($data['products_title_overwrite']) || (strlen(trim($product['meta_title']))) == 0)) {
            $products_title_template = $data['products_title_template'];

            if (isset($data['products_use_expressions'])) {
                $products_title_template = $this->parseTemplate($products_title_template);
            }

            $title = trim(strtr($products_title_template, $tags));
            $updates[] = "`meta_title`='" . $this->db->escape($title) . "'";
        }
        if (isset($product['meta_keyword']) && (isset($data['products_meta_keyword_overwrite']) || (strlen(trim($product['meta_keyword']))) == 0)) {
            $meta_keyword = trim(strtr($data['products_meta_keyword_template'], $tags));

            $updates[] = "`meta_keyword`='" . $this->db->escape($meta_keyword) . "'";
        }
        if (isset($product['meta_description']) && (isset($data['products_meta_description_overwrite']) || (strlen(trim($product['meta_description']))) == 0)) {
            $products_meta_description_template = $data['products_meta_description_template'];
            if (isset($data['products_use_expressions'])) {
                $products_meta_description_template = $this->parseTemplate($products_meta_description_template);
            }

            $meta_description = trim(strtr($products_meta_description_template, $tags));
            if (isset($data['products_meta_description_limit']) && (int)$data['products_meta_description_limit']) {
                $meta_description = mb_substr($meta_description, 0, (int)$data['products_meta_description_limit']);
            }
            $updates[] = "`meta_description`='" . $this->db->escape($meta_description) . "'";
        }

        if (isset($product['description']) && (isset($data['products_description_overwrite']) || (strlen(trim($product['description']))) == 0)) {
            $products_description_template = $data['products_description_template'];
            if (isset($data['products_use_expressions'])) {
                $products_description_template = $this->parseTemplate($products_description_template);
            }
            $description = trim(strtr($products_description_template, $tags));
            $updates[] = "`description`='" . $this->db->escape($description) . "'";
        }

        if (isset($product['tag']) && (isset($data['products_tag_overwrite']) || (strlen(trim($product['tag']))) == 0)) {
            $products_tag_template = $data['products_tag_template'];
            if (isset($data['products_use_expressions'])) {
                $products_tag_template = $this->parseTemplate($products_tag_template);
            }
            $tag = strtr($products_tag_template, $tags);
            $tags = array_filter(array_unique(array_map('trim', explode(",", $tag))));
            $tag = "";
            if ($tags) {
                $tag = implode(",", $tags);
            }

            if ($data['product_tag_table']) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "product_tag`" .
                    " WHERE product_id='" . (int)$product['product_id'] . "' AND language_id='" . (int)$this->config->get('config_language_id') . "'");
                foreach ($tags as $tag) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "product_tag`" .
                        " SET `tag`='" . $this->db->escape($tag) . "', product_id='" . (int)$product['product_id'] . "', language_id='" . (int)$this->config->get('config_language_id') . "'");
                }
            } else {
                $updates[] = "`tag`='" . $this->db->escape($tag) . "'";
            }
        }

        if (isset($product['model']) && (isset($data['products_model_overwrite']) || (strlen(trim($product['model']))) == 0)) {
            $products_model_template = trim(strtr($data['products_model_template'], $tags));
            $this->db->query("UPDATE `" . DB_PREFIX . "product`" .
                " SET `model`='" . $this->db->escape($products_model_template) . "' WHERE product_id='" . (int)$product['product_id'] . "'");
        }

        if (count($updates)) {
            $this->db->query("UPDATE `" . DB_PREFIX . "product_description`" .
                " SET " . implode(", ", $updates) .
                " WHERE product_id='" . (int)$product['product_id'] . "' AND language_id='" . $language_id . "'");
        }
    }

    private function generateManufacturer($manufacturer, $data)
    {
        $language_id = (isset($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id'));

        $tags = array('[manufacturer_name]' => $manufacturer['name']);
        if (!empty($data['manufacturers_template']) && (isset($data['manufacturers_overwrite']) || is_null($manufacturer['keyword']))) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query`='manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "'");
            $keyword = $this->urlify($data['manufacturers_template'], $tags);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `query`='manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', keyword='" . $this->db->escape($keyword) . "'");
        }

        if ($this->manufacturer_desc) {
            $updates = array();
            if (isset($manufacturer['meta_h1']) && (isset($data['manufacturers_h1_overwrite']) || (strlen(trim($manufacturer['meta_h1']))) == 0)) {
                $h1 = trim(strtr($data['manufacturers_h1_template'], $tags));
                $updates[] = "`meta_h1`='" . $this->db->escape($h1) . "'";
            }
            if (isset($manufacturer['meta_title']) && (isset($data['manufacturers_title_overwrite']) || (strlen(trim($manufacturer['meta_title']))) == 0)) {

                $manufacturers_title_template = $data['manufacturers_title_template'];
                if (isset($data['manufacturers_use_expressions'])) {
                    $manufacturers_title_template = $this->parseTemplate($manufacturers_title_template);
                }

                $manufacturers_title_template = trim(strtr($manufacturers_title_template, $tags));
                $updates[] = "`meta_title`='" . $this->db->escape($manufacturers_title_template) . "'";
            }
            if (isset($manufacturer['meta_keyword']) && (isset($data['manufacturers_meta_keyword_overwrite']) || (strlen(trim($manufacturer['meta_keyword']))) == 0)) {
                $meta_keyword = trim(strtr($data['manufacturers_meta_keyword_template'], $tags));
                $updates[] = "`meta_keyword`='" . $this->db->escape($meta_keyword) . "'";
            }
            if (isset($manufacturer['meta_description']) && (isset($data['manufacturers_meta_description_overwrite']) || (strlen(trim($manufacturer['meta_description']))) == 0)) {
                $manufacturers_meta_description_template = $data['manufacturers_meta_description_template'];
                if (isset($data['manufacturers_use_expressions'])) {
                    $manufacturers_meta_description_template = $this->parseTemplate($manufacturers_meta_description_template);
                }
                $meta_description = trim(strtr($manufacturers_meta_description_template, $tags));
                $updates[] = "`meta_description`='" . $this->db->escape($meta_description) . "'";
            }
            if (isset($manufacturer['description']) && (isset($data['manufacturers_description_overwrite']) || (strlen(trim($manufacturer['description']))) == 0)) {
                $manufacturers_description_template = $data['manufacturers_description_template'];
                if (isset($data['manufacturers_use_expressions'])) {
                    $manufacturers_description_template = $this->parseTemplate($manufacturers_description_template);
                }

                $description = trim(strtr($manufacturers_description_template, $tags));
                $updates[] = "`description`='" . $this->db->escape($description) . "'";
            }

            if (count($updates)) {
                $this->db->query("UPDATE `" . DB_PREFIX . "manufacturer_description`" .
                    " SET " . implode(", ", $updates) .
                    " WHERE manufacturer_id='" . (int)$manufacturer['manufacturer_id'] . "' AND language_id='" . $language_id . "'");
            }
        }
    }

    public function generateInformation($information, $data)
    {
        $language_id = (isset($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id'));

        $tags = array('[information_title]' => $information['title']);
        if (!empty($data['informations_template']) && (isset($data['informations_overwrite']) || is_null($information['keyword']))) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query`='information_id=" . (int)$information['information_id'] . "'");
            $keyword = $this->urlify($data['informations_template'], $tags);
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `query`='information_id=" . (int)$information['information_id'] . "', keyword='" . $this->db->escape($keyword) . "'");
        }

        $updates = array();
        if (isset($information['meta_h1']) && (isset($data['informations_h1_overwrite']) || (strlen(trim($information['meta_h1']))) == 0)) {
            $h1 = trim(strtr($data['informations_h1_template'], $tags));
            $updates[] = "`meta_h1`='" . $this->db->escape($h1) . "'";
        }
        if (isset($information['meta_title']) && (isset($data['informations_title_overwrite']) || (strlen(trim($information['meta_title']))) == 0)) {
            $title = trim(strtr($data['informations_title_template'], $tags));
            $updates[] = "`meta_title`='" . $this->db->escape($title) . "'";
        }
        if (isset($information['meta_keyword']) && (isset($data['informations_meta_keyword_overwrite']) || (strlen(trim($information['meta_keyword']))) == 0)) {
            $meta_keyword = trim(strtr($data['informations_meta_keyword_template'], $tags));
            $updates[] = "`meta_keyword`='" . $this->db->escape($meta_keyword) . "'";
        }
        if (isset($information['meta_description']) && (isset($data['informations_meta_description_overwrite']) || (strlen(trim($information['meta_description']))) == 0)) {
            $meta_description = trim(strtr($data['informations_meta_description_template'], $tags));
            $updates[] = "`meta_description`='" . $this->db->escape($meta_description) . "'";
        }

        if (count($updates)) {
            $this->db->query("UPDATE `" . DB_PREFIX . "information_description`" .
                " SET " . implode(", ", $updates) .
                " WHERE information_id='" . (int)$information['information_id'] . "' AND language_id='" . $language_id . "'");
        }
    }

    private function getCategories($category_id = false)
    {
        $query = $this->db->query("SELECT cd.*, u.keyword FROM " . DB_PREFIX . "category_description cd" .
            " LEFT JOIN " . DB_PREFIX . "seo_url u ON (CONCAT('category_id=', cd.category_id) = u.query)" .
            " WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'" .
            ($category_id ? " AND cd.category_id='" . (int)$category_id . "'" : "") .
            " ORDER BY cd.category_id");
        return $query->rows;
    }

    private function getProducts($seogen, $product_id = false)
    {
        $seogenoc3 = $this->config->get('seogenoc3');
        $seogen['main_category_exists'] = $seogenoc3['main_category_exists'];

        $only_categories = false;
        if (isset($seogen['only_categories']) && count($seogen['only_categories'])) {
            $only_categories = implode(",", $seogen['only_categories']);
        }

        $query = $this->db->query("SELECT pd.*, m.name as 'manufacturer', p.model as 'model', p.sku, p.price, " .
            ($seogen['product_tag_table'] ? "coalesce(pt.tag,'') as 'tag', " : "pd.tag as 'tag', ") .
            ($seogen['main_category_exists'] ?
                "(SELECT cd.name FROM `" . DB_PREFIX . "category_description` cd " .
                " LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c ON (cd.category_id = p2c.category_id)" .
                " WHERE p2c.product_id = p.product_id" .
                " AND cd.language_id ='" . (int)$this->config->get('config_language_id') . "'" .
                " ORDER BY p2c.main_category='1' DESC LIMIT 1) AS 'category'" : "'' as 'category'") .
            " FROM `" . DB_PREFIX . "product` p" .
            " INNER JOIN `" . DB_PREFIX . "product_description` pd ON ( pd.product_id = p.product_id )" .
            " LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON ( m.manufacturer_id = p.manufacturer_id )" .
            ($only_categories ? " LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.product_id=p.product_id)" : "") .
            ($seogen['product_tag_table'] ? " LEFT JOIN `" . DB_PREFIX . "product_tag` pt ON (pt.product_id=p.product_id AND pt.language_id ='" . (int)$this->config->get('config_language_id') . "')" : "") .
            " WHERE pd.language_id ='" . (int)$this->config->get('config_language_id') . "'" .
            ($only_categories ? " AND p2c.category_id IN (" . $only_categories . ")" : "") .
            ($product_id ? " AND p.product_id='" . (int)$product_id . "'" : "") .
            " ORDER BY p.product_id");
        if ($product_id) {
            $query_keyword = $this->db->query("SELECT `keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `query`='product_id=" . $query->rows[0]['product_id'] . "' LIMIT 1");
            $query->rows[0]['keyword'] = $query_keyword->num_rows ? $query_keyword->row['keyword'] : null;
        } else if ($this->keywords !== false) {
            foreach ($query->rows as &$row) {
                $row['keyword'] = isset($this->keywords["product_id=" . $row['product_id']]) ? $this->keywords["product_id=" . $row['product_id']] : null;
            }
        }

        return $query->rows;
    }

    private function getManufacturers($manufacturer_id = false)
    {
        if ($this->manufacturer_desc) {
            $query = $this->db->query("SELECT md.*, u.keyword, m.name" .
                " FROM `" . DB_PREFIX . "manufacturer` m" .
                " LEFT JOIN `" . DB_PREFIX . "manufacturer_description` md ON (m.manufacturer_id=md.manufacturer_id)" .
                " LEFT JOIN " . DB_PREFIX . "seo_url u ON (CONCAT('manufacturer_id=', m.manufacturer_id) = u.query)" .
                " WHERE md.language_id='" . (int)$this->config->get('config_language_id') . "'" .
                ($manufacturer_id ? " AND m.manufacturer_id='" . (int)$manufacturer_id . "'" : "") .
                " ORDER BY m.manufacturer_id");
        } else {
            $query = $this->db->query("SELECT manufacturer_id, name, u.keyword" .
                " FROM `" . DB_PREFIX . "manufacturer` m" .
                " LEFT JOIN " . DB_PREFIX . "seo_url u ON (CONCAT('manufacturer_id=', m.manufacturer_id) = u.query)" .
                ($manufacturer_id ? " WHERE m.manufacturer_id='" . (int)$manufacturer_id . "'" : "") .
                " ORDER BY m.manufacturer_id");
        }
        return $query->rows;
    }

    private function getInformations($information_id = false)
    {
        $query = $this->db->query("SELECT id.*, u.keyword FROM " . DB_PREFIX . "information_description id" .
            " LEFT JOIN " . DB_PREFIX . "seo_url u ON (CONCAT('information_id=', id.information_id) = u.query)" .
            " WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'" .
            ($information_id ? " AND id.information_id='" . (int)$information_id . "'" : "") .
            " ORDER BY id.information_id");
        return $query->rows;
    }


    private function checkDuplicate(&$keyword)
    {
        $counter = 0;
        $k = $keyword;
        if ($this->keywords !== false) {
            while (in_array($keyword, $this->keywords)) {
                $keyword = $k . '-' . ++$counter;
            }
            $this->keywords[] = $keyword;
        } else {
            do {
                $query = $this->db->query("SELECT seo_url_id FROM " . DB_PREFIX . "seo_url WHERE keyword ='" . $this->db->escape($keyword) . "'");
                if ($query->num_rows > 0) {
                    $keyword = $k . '-' . ++$counter;
                }
            } while ($query->num_rows > 0);
        }
    }

    private function urlify($template, $tags)
    {
        $keyword = strtr($template, $tags);
        $keyword = trim(html_entity_decode($keyword, ENT_QUOTES, "UTF-8"));
        $urlify = URLify::filter($keyword);
        $this->checkDuplicate($urlify);
        return $urlify;
    }

    private function parseTemplate($template)
    {
        while (preg_match('/\\{rand:(.*?)\\}/', $template, $matches)) {
            $arr = explode(",", $matches[1]);
            $rand = array_rand($arr);
            $template = str_replace($matches[0], trim($arr[$rand]), $template);
        }
        return $template;
    }
}