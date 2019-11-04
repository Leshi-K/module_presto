<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

include_once(_PS_MODULE_DIR_.'leshthreebaners/Baners.php');

class LeshThreeBaners extends Module implements WidgetInterface
{

    protected $templateFile;
    public $className;

    public function __construct()
    {
        $this->name = 'leshthreebaners';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Leshi';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->secure_key = Tools::encrypt($this->name);
        $this->_html = '';
        $this->_htmlm = '';

        parent::__construct();

        $this->displayName = "3 banners header";
        $this->description = "Show 3 baners in head";
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:leshthreebaners/views/templates/hook/baner.tpl';
    }

    /**
     * @see Module::install()
     */
    public function install()
    {
        return parent::install() && $this->registerHook('displayHeader') && $this->registerHook('displayTopColumn1') && $this->createTables();
    }

    /**
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        /* Deletes Module */
        if (parent::uninstall()) {
            /* Deletes tables */
            $res = $this->deleteTables();

            return (bool)$res;
        }

        return false;
    }

    protected function createTables()
    {

        $res = (bool)Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'leshthreebaner` (
                `id_leshthreebaner` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                `position` int(10) unsigned NOT NULL DEFAULT \'0\',
                PRIMARY KEY (`id_leshthreebaner`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
        ');

        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'leshthreebaner_lang` (
              `id_leshthreebaner` int(10) unsigned NOT NULL,
              `id_lang` int(10) unsigned NOT NULL,
              `description` text NOT NULL,
              `url` varchar(255) NOT NULL,
              `image` varchar(255) NOT NULL,
              PRIMARY KEY (`id_leshthreebaner`,`id_lang`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
        ');

        return $res;
    }

    protected function deleteTables()
    {
        $res = Db::getInstance()->execute('
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'leshthreebaner`;
        ');

        $res &= Db::getInstance()->execute('
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'leshthreebaner_lang`;
        ');

        return $res;
    }

	public function renderWidget($hookName = null, array $configuration = [])
    {

        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch($this->templateFile);
    }

	public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $baners = $this->getBaners(true);

		foreach($baners as &$baner){

			$baner['description'] = html_entity_decode($baner['description']);
		}

        return array("baners" => $baners);
    }

	public function getBaners($active = null) {

        $this->context = Context::getContext();
        $id_lang = $this->context->language->id;

        $baners = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT ltb.`id_leshthreebaner`, ltb.`position`, ltb.`active`, ltbl.`url`, ltbl.`description`, ltbl.`image`
            FROM '._DB_PREFIX_.'leshthreebaner ltb
            LEFT JOIN '._DB_PREFIX_.'leshthreebaner_lang ltbl ON (ltb.id_leshthreebaner = ltbl.id_leshthreebaner)
            WHERE ltbl.id_lang = '.(int)$id_lang.
            ($active ? ' AND ltb.`active` = 1' : ' ').'
            ORDER BY ltb.position'
        );

        foreach ($baners as &$baner) {
            $baner['image_url'] = $this->context->link->getMediaLink(_MODULE_DIR_.'leshthreebaners/images/'.$baner['image']);
        }

        return $baners;
    }

    public function headerHTML()
    {
        if (Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != $this->name)
            return;

        $this->context->controller->addJqueryUI('ui.sortable');
        /* Style & js for fieldset 'slides configuration' */
        $html = '<script type="text/javascript">
            $(function() {
                var $mySlides = $("#items");
                $mySlides.sortable({
                    opacity: 0.6,
                    cursor: "move",
                    update: function() {
                        var order = $(this).sortable("serialize") + "&action=updateItemsPosition";
                        $.post("'.$this->context->shop->physical_uri.$this->context->shop->virtual_uri.'modules/'.$this->name.'/ajax_'.$this->name.'.php?secure_key='.$this->secure_key.'", order);
                        }


                    });
                $mySlides.hover(function() {
                    $(this).css("cursor","move");
                    },
                    function() {
                    $(this).css("cursor","auto");
                });
            });
        </script>';

        return $html;
    }

    protected function _postValidation()
    {
        $errors = array();

        /* Validation for Slider configuration */
        if (Tools::isSubmit('submitSlider')) {
            if (!Validate::isInt(Tools::getValue('POSSLIDESHOW_SPEED'))) {
                $errors[] = $this->l('Invalid values');
            }
        } elseif (Tools::isSubmit('changeStatus')) {
            if (!Validate::isInt(Tools::getValue('id_slide'))) {
                $errors[] = $this->l('Invalid slide');
            }
        } elseif (Tools::isSubmit('submitSlide')) {
            /* Checks state (active) */
            if (!Validate::isInt(Tools::getValue('active_slide')) || (Tools::getValue('active_slide') != 0 && Tools::getValue('active_slide') != 1)) {
                $errors[] = $this->l('Invalid slide state.');
            }
            /* Checks position */
            if (!Validate::isInt(Tools::getValue('position')) || (Tools::getValue('position') < 0)) {
                $errors[] = $this->l('Invalid slide position.');
            }
            /* If edit : checks id_slide */
            if (Tools::isSubmit('id_slide')) {
                if (!Validate::isInt(Tools::getValue('id_slide')) && !$this->slideExists(Tools::getValue('id_slide'))) {
                    $errors[] = $this->l('Invalid slide ID');
                }
            }
            /* Checks title/url/legend/description/image */
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 255) {
                    $errors[] = $this->l('The URL is too long.');
                }
                if (Tools::strlen(Tools::getValue('description_' . $language['id_lang'])) > 4000) {
                    $errors[] = $this->l('The description is too long.');
                }
                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 0 && !Validate::isUrl(Tools::getValue('url_' . $language['id_lang']))) {
                    $errors[] = $this->l('The URL format is not correct.');
                }
                if (Tools::getValue('image_' . $language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_' . $language['id_lang']))) {
                    $errors[] = $this->l('Invalid filename.');
                }
                if (Tools::getValue('image_old_' . $language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_old_' . $language['id_lang']))) {
                    $errors[] = $this->l('Invalid filename.');
                }
            }

            /* Checks title/url/legend/description for default lang */
            $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
            if (Tools::strlen(Tools::getValue('url_' . $id_lang_default)) == 0) {
                $errors[] = $this->l('The URL is not set.');
            }

            if (Tools::getValue('image_old_'.$id_lang_default) && !Validate::isFileName(Tools::getValue('image_old_'.$id_lang_default))) {
                $errors[] = $this->l('The image is not set.');
            }

        } elseif (Tools::isSubmit('delete_id_slide') && (!Validate::isInt(Tools::getValue('delete_id_slide')) || !$this->slideExists((int)Tools::getValue('delete_id_slide')))) {
            $errors[] = $this->l('Invalid slide ID');
        }

        /* Display errors if needed */
        if (count($errors)) {
            $this->_html .= $this->displayError(implode('<br />', $errors));

            return false;
        }

        /* Returns if validation is ok */

        return true;
    }

    public function clearCache()
    {
        $this->_clearCache($this->templateFile);
    }

    protected function _postProcess()
    {
        $errors = array();
        $shop_context = Shop::getContext();

        /* Processes Slider */
        if (Tools::isSubmit('submitPosProductCates')) {
            $shop_groups_list = array();
            $shops = Shop::getContextListShopID();

            $this->clearCache();

            if (!$res)
                $errors[] = $this->displayError('The configuration could not be updated.');
            else
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true).'&conf=6&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);

        } elseif (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_item')) {

            $item = new Baners((int)Tools::getValue('id_item'));
            if ($item->active == 0)
                $item->active = 1;
            else
                $item->active = 0;
            $res = $item->update();
            $this->clearCache();
            $this->_html .= ($res ? $this->displayConfirmation('Configuration updated') : $this->displayError('The configuration could not be updated.'));

        } elseif (Tools::isSubmit('submitPosProductCatesItem')) {



            if (Tools::getValue('id_item')) {
                $item = new Baners((int)Tools::getValue('id_item'));
                if (!Validate::isLoadedObject($item)) {
                    $this->_html .= $this->displayError('Invalid item ID');
                    return false;
                }
            } else {
                $item = new Baners();
            }


            $item->active = (int)Tools::getValue('active_slide');

            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                $item->url[$language['id_lang']] = Tools::getValue('url_'.$language['id_lang']);
                $item->description[$language['id_lang']] = htmlentities(Tools::getValue('description_'.$language['id_lang']));

                /* Uploads image and sets slide */
                $type = Tools::strtolower(Tools::substr(strrchr($_FILES['image_'.$language['id_lang']]['name'], '.'), 1));
                $imagesize = @getimagesize($_FILES['image_'.$language['id_lang']]['tmp_name']);
                if (isset($_FILES['image_'.$language['id_lang']]) &&
                    isset($_FILES['image_'.$language['id_lang']]['tmp_name']) &&
                    !empty($_FILES['image_'.$language['id_lang']]['tmp_name']) &&
                    !empty($imagesize) &&
                    in_array(
                        Tools::strtolower(Tools::substr(strrchr($imagesize['mime'], '/'), 1)), array(
                            'jpg',
                            'gif',
                            'jpeg',
                            'png'
                        )
                    ) &&
                    in_array($type, array('jpg', 'gif', 'jpeg', 'png'))
                ) {
                    $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                    $salt = sha1(microtime());
                    if ($error = ImageManager::validateUpload($_FILES['image_'.$language['id_lang']])) {
                        $errors[] = $error;
                    } elseif (!$temp_name || !move_uploaded_file($_FILES['image_'.$language['id_lang']]['tmp_name'], $temp_name)) {
                        return false;
                    } elseif (!ImageManager::resize($temp_name, dirname(__FILE__).'/images/'.$salt.'_'.$_FILES['image_'.$language['id_lang']]['name'], null, null, $type)) {
                        $errors[] = $this->displayError($this->l('An error occurred during the image upload process.'));
                    }
                    if (isset($temp_name)) {
                        @unlink($temp_name);
                    }
                    $item->image[$language['id_lang']] = $salt.'_'.$_FILES['image_'.$language['id_lang']]['name'];
                } elseif (Tools::getValue('image_old_'.$language['id_lang']) != '') {
                    $item->image[$language['id_lang']] = Tools::getValue('image_old_' . $language['id_lang']);
                }
            }

            /* Processes if no errors  */
            if (!$errors)
            {
                /* Adds */
                if (!Tools::getValue('id_item'))
                {
                    /* Sets position */
                    $position = $this->getNextPosition();
                    $item->position = $position;
                    if (!$item->add())
                        $errors[] = $this->displayError('The item could not be added.');
                }
                /* Update */
                elseif (!$item->update())
                    $errors[] = $this->displayError('The item could not be updated.');
                $this->clearCache();
            }
        } elseif (Tools::isSubmit('delete_id_item')) {
            $item = new Baners((int)Tools::getValue('delete_id_item'));
            $res = $item->delete();
            $this->clearCache();
            if (!$res)
                $this->_html .= $this->displayError('Could not delete.');
            else
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true).'&conf=1&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);
        }

        /* Display errors if needed */
        if (count($errors))
            $this->_html .= $this->displayError(implode('<br />', $errors));
        elseif (Tools::isSubmit('submitPosProductCatesItem') && Tools::getValue('id_item'))
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true).'&conf=4&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);
        elseif (Tools::isSubmit('submitPosProductCatesItem'))
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true).'&conf=3&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);
    }

    public function renderList()
    {

        $items = $this->getBaners();
        foreach ($items as $key => $item)
        {
            $items[$key]['status'] = $this->displayStatus($item['id_leshthreebaner'], $item['active']);

            $items[$key]['is_shared'] = false;

        }

        $this->context->smarty->assign(
            array(
                'link' => $this->context->link,
                'items' => $items,
                'image_baseurl' => $this->_path.'images/'
            )
        );

        return $this->display(__FILE__, 'list.tpl');
    }

    public function renderAddForm() {
        $id_item = Tools::getValue('id_item');

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => 'Slide information',
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'file_lang',
                        'label' => 'Image/Thumbnail',
                        'name' => 'image',
                        'required' => true,
                        'lang' => true,
                        'desc' => sprintf('Maximum image size: %s.', ini_get('upload_max_filesize'))
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Description',
                        'name' => 'description',
                        'autoload_rte' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Url',
                        'name' => 'url',
                        'autoload_rte' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => 'Enabled',
                        'name' => 'active_slide',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Yes'
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'No'
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => 'Save',
                )
            ),
        );

        if (Tools::isSubmit('id_item') && $this->slideExists((int)Tools::getValue('id_item')))
        {
            $slide = new Baners((int)Tools::getValue('id_item'));
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_item');
            $fields_form['form']['images'] = $slide->image;

            $has_picture = true;

            foreach (Language::getLanguages(false) as $lang)
                if (!isset($baner->image[$lang['id_lang']]))
                    $has_picture &= false;

            if ($has_picture)
                $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'has_picture');

        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->className = $this->className;
        $helper->submit_action = 'submitPosProductCatesItem';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->tpl_vars = array(
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => array(
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code
            ),
            'fields_value' => $this->getAddFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'image_baseurl' => $this->_path.'images/'
        );


        $helper->override_folder = '/';

        $languages = Language::getLanguages(false);

        return $helper->generateForm(array($fields_form));
    }

    public function getNextPosition()
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('SELECT MAX(`position`) AS `next_position` FROM `'._DB_PREFIX_.'leshthreebaner`');

        return (++$row['next_position']);
    }

    public function getContent()
    {
        $this->_html .= $this->headerHTML();

        /* Validate & process */
        if (Tools::isSubmit('submitPosProductCatesItem') || Tools::isSubmit('delete_id_item') ||
            Tools::isSubmit('submitPosProductCates') ||
            Tools::isSubmit('changeStatus')
        )
        {
            if ($this->_postValidation())
            {
                $this->_postProcess();
                $this->_html .= $this->renderList();
            }
            else
                $this->_html .= $this->renderAddForm();

            $this->clearCache();
        }
        elseif (Tools::isSubmit('addItem') || (Tools::isSubmit('id_item') && $this->slideExists((int)Tools::getValue('id_item'))))
        {
            $this->_html .= $this->renderAddForm();

        }
        else // Default viewport
        {

            if (Shop::getContext() != Shop::CONTEXT_GROUP && Shop::getContext() != Shop::CONTEXT_ALL)
                $this->_html .= $this->renderList();
        }

        return $this->_html;
    }

    public function hookdisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path.'/css/poslistcategories.css', 'all');
    }

    public function displayStatus($id_item, $active)
    {
        $title = ((int)$active == 0 ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = ((int)$active == 0 ? 'icon-remove' : 'icon-check');
        $class = ((int)$active == 0 ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn '.$class.'" href="'.AdminController::$currentIndex.
            '&configure='.$this->name.'
                &token='.Tools::getAdminTokenLite('AdminModules').'
                &changeStatus&id_item='.(int)$id_item.'" title="'.$title.'"><i class="'.$icon.'"></i> '.$title.'</a>';

        return $html;
    }

    public function slideExists($id_item)
    {
        $req = 'SELECT `id_leshthreebaner` AS `id_item` FROM `'._DB_PREFIX_.'leshthreebaner` WHERE `id_leshthreebaner` = '.(int)$id_item;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return ($row);
    }

    public function getAddFieldsValues()
    {
        $fields = array();

        if (Tools::isSubmit('id_item') && $this->slideExists((int)Tools::getValue('id_item'))) {
            $baner = new Baners((int)Tools::getValue('id_item'));
            $fields['id_item'] = (int)Tools::getValue('id_item', $baner->id);
        } else {
            $baner = new Baners();
        }

        $fields['active_slide'] = Tools::getValue('active_slide', $baner->active);
        $fields['has_picture'] = true;

        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $fields['image'][$lang['id_lang']] = Tools::getValue('image_'.(int)$lang['id_lang']);
            $fields['description'][$lang['id_lang']] = html_entity_decode(Tools::getValue('description_'.(int)$lang['id_lang'], $baner->description[$lang['id_lang']]));
            $fields['description'][$lang['id_lang']] = str_replace('/pos_venezo/',__PS_BASE_URI__,$fields['description'][$lang['id_lang']]);
            $fields['url'][$lang['id_lang']] = Tools::getValue('url_'.(int)$lang['id_lang'], $baner->url[$lang['id_lang']]);
        }

        return $fields;
    }

}