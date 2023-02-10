<?php

require_once __DIR__ . '/classes/HTMLTemplateDeliverySlipSupplier.php';
use Symfony\Component\HttpFoundation\RequestStack;

class Mail_Supplier_Order extends \Module {

    function __construct()
    {
        $this->name = 'mail_supplier_order';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->displayName = $this->l('Envoi de commande fournisseur');
        $this->description = $this->l('Permet d\'envoyer les commandes fournisseur par mail');
        parent::__construct();
    }


    public function install() {

        if (file_exists($this->getLocalPath().'sql/install.php')) {
            require_once($this->getLocalPath().'sql/install.php');
        }

        return
            parent::install() &&
            $this->registerHook('displayAdminOrderMainBottom') &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('actionSupplierFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateSupplierFormHandler') &&
            $this->registerHook('actionAfterUpdateSupplierFormHandler')
        ;
    }


    public function getContent() {
        if (Tools::getValue('action') == 'downloadDeliverySlip') {
            $order = new Order(Tools::getValue('mso_id_order'));
            $invoices = [];
            foreach ($order->getInvoicesCollection() as $invoice) {
                $invoice->id_supplier_filter = (int)Tools::getValue('mso_id_supplier');
                $invoices[] = $invoice;
            }

            $pdf = new PDF($invoices, 'DeliverySlipSupplier', $this->context->smarty);
            $pdf->render();
        }

        if (Tools::isSubmit('submitSendMail')) {

            $subject = Tools::getValue('mso_subject');
            $content = Tools::getValue('mso_content');
            $to = Tools::getValue('mso_email');
            $supplier = new Supplier((int)Tools::getValue('mso_id_supplier'));


            if (!ValidateCore::isMailSubject($subject)) {
                $this->context->controller->errors[] = $this->l('Invalid subject');
            }

            if (!ValidateCore::isCleanHtml($content)) {
                $this->context->controller->errors[] = $this->l('Invalid content');
            }

            if (!ValidateCore::isEmail($to)) {
                $this->context->controller->errors[] = $this->l('Invalid email');
            }

            if (!ValidateCore::isLoadedObject($supplier)) {
                $this->context->controller->errors[] = $this->l('Invalid supplier');
            }

            if (!count($this->context->controller->errors)) {

                $order = new Order(Tools::getValue('mso_id_order'));
                $invoices = [];
                foreach ($order->getInvoicesCollection() as $invoice) {
                    $invoice->id_supplier_filter = $supplier->id;
                    $invoices[] = $invoice;
                }
                $pdf = new PDF($invoices, 'DeliverySlipSupplier', $this->context->smarty);
                $file_attachement = [
                    'content' => $pdf->render(false),
                    'name' => Configuration::get('PS_DELIVERY_PREFIX', $order->id_lang, null, $order->id_shop) . sprintf('%06d', $order->delivery_number) . '.pdf',
                    'mime' => 'application/pdf'
                ];


                if (!@MailCore::send(
                    $this->context->language->id,
                    'mail_supplier_order',
                    $subject,
                    ['{content}' => $content],
                    'contact@adilis.fr',//$to,
                    $supplier->name,
                    null,
                    null,
                    $file_attachement,
                    null,
                    _PS_MODULE_DIR_.$this->name.'/mails/'
                )) {
                    $this->context->controller->errors[] = $this->l('An error occurred while sending the email.');
                }

            }
        }

        $this->context->controller->informations[] = sprintf(
            $this->l('You can use the following tags to personalize your email : %s'),
            implode(', ', [
                '{CARRIER_NAME}',
                '{CUSTOMER_ADDRESS}',
                '{CUSTOMER_EMAIL}',
                '{CUSTOMER_NAME}',
                '{CUSTOMER_PHONE}',
                '{INVOICE_NUMBER}',
                '{ORDER_DATE}',
                '{ORDER_ID}',
                '{ORDER_INFO}',
                '{ORDER_REF}',
                '{PRODUCTS}',
                '{SUPPLIER_ADDRESS}',
                '{SUPPLIER_NAME}',
                '{SUPPLIER_PHONE}'
        ]));


        if (\Tools::isSubmit('submit'.$this->name.'Module')) {

            $subjects = $contents = [];
            foreach (\Language::getLanguages(false) as $lang) {
                $subjects[$lang['id_lang']] = \Tools::getValue('MSO_DEFAULT_SUBJECT_'.$lang['id_lang']);
                $contents[$lang['id_lang']] = \Tools::getValue('MSO_DEFAULT_CONTENT_'.$lang['id_lang']);
            }
            Configuration::updateValue('MSO_DEFAULT_SUBJECT', $subjects);
            Configuration::updateValue('MSO_DEFAULT_CONTENT', $contents, true);

            if (!count($this->context->controller->errors)) {
                $redirect_after = $this->context->link->getAdminLink('AdminModules', true);
                $redirect_after .= '&conf=4&configure='.$this->name.'&module_name='.$this->name;
                \Tools::redirectAdmin($redirect_after);
            }
        }

        return $this->renderForm();
    }

    private function renderForm() {
        $helper = new \HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name.'Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex .= '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'fields_value' => array(
                'MSO_DEFAULT_SUBJECT' => \ConfigurationCore::getConfigInMultipleLangs('MSO_DEFAULT_SUBJECT'),
                'MSO_DEFAULT_CONTENT' => \Configuration::getConfigInMultipleLangs('MSO_DEFAULT_CONTENT'),
            )
        );

        return $helper->generateForm(array(
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Default email configuration'),
                        'icon' => 'icon-cogs'
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'name' => 'MSO_DEFAULT_SUBJECT',
                            'id' => 'MSO_DEFAULT_SUBJECT',
                            'label' => $this->l('Mail subject'),
                            'required' => true,
                            'lang' => true,
                        ],
                        [
                            'type' => 'textarea',
                            'name' => 'MSO_DEFAULT_CONTENT',
                            'id' => 'MSO_DEFAULT_CONTENT',
                            'label' => $this->l('Mail content'),
                            'required' => true,
                            'lang' => true,
                            'class' => 'autoload_rte'
                        ]
                    ],
                    'submit' => [
                        'title' => $this->l('Save')
                    ]
                ]
            ]
        ));
    }
    public function hookDisplayAdminOrderMainBottom($params) {
        $order = new \Order((int)$params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        $address = new Address($order->id_address_delivery);
        $customer = new Address($order->id_customer);
        $products = $order->getProducts();
        $suppliers = Db::getInstance()->executeS('
            SELECT DISTINCT s.id_supplier, s.name, sm.email
            FROM '._DB_PREFIX_.'supplier_mail sm
            INNER JOIN '._DB_PREFIX_.'supplier s ON sm.id_supplier = s.id_supplier
            WHERE sm.active_mail = 1
        ');

        foreach($suppliers as $i => $supplier)  {
            $suppliers[$i]['products'] = [];
            $suppliers[$i]['products_count'] = 0;
            foreach($products as $key => $product) {
                if ($product['id_supplier'] == $supplier['id_supplier']) {
                    $suppliers[$i]['products'][] = $product;
                    $suppliers[$i]['products_count'] += $product['product_quantity'];
                    unset($products[$key]);
                }
            }
            if ($suppliers[$i]['products_count'] == 0) {
                unset($suppliers[$i]);
            } else {
                $suppliers[$i]['mail_subject'] = Configuration::get('MSO_DEFAULT_SUBJECT', $order->id_lang);
                $suppliers[$i]['mail_content'] = Configuration::get('MSO_DEFAULT_CONTENT', $order->id_lang);

                $suppliers[$i]['mail_content'] = str_replace(
                    [
                        '{CARRIER_NAME}',
                        '{CUSTOMER_ADDRESS}',
                        '{CUSTOMER_EMAIL}',
                        '{CUSTOMER_NAME}',
                        '{CUSTOMER_PHONE}',
                        '{INVOICE_NUMBER}',
                        '{ORDER_DATE}',
                        '{ORDER_ID}',
                        '{ORDER_INFO}',
                        '{ORDER_REF}',
                        '{PRODUCTS}'
                    ], [
                        '',
                        AddressFormat::generateAddress($address, ['avoid' => []], '<br/>'),
                        $customer->email,
                        $customer->firstname . ' ' . $customer->lastname,
                        $address->mobile_phone ?: $address->phone,
                        $order->invoice_number,
                        Tools::displayDate($order->date_add, $order->id_lang),
                        $order->id,
                        $order->note,
                        $order->reference,
                        $this->getProductsList($suppliers[$i]['products']),
                    ], $suppliers[$i]['mail_content']);
            }
        }

        $iso = $this->context->language->iso_code;
        $this->context->smarty->assign(array(
            'suppliers' => $suppliers,
            'id_order' => $order->id,
            'iso' => file_exists(_PS_CORE_DIR_ . '/js/tiny_mce/langs/' . $iso . '.js') ? $iso : 'en',
            'path_css' => _THEME_CSS_DIR_,
            'ad' => __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_),
            'module_config_url' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
        ));

		return $this->context->smarty->fetch($this->getLocalPath().'views/templates/hook/admin_order_main_bottom.tpl');
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'AdminOrders') {
            $this->context->controller->addJS(_PS_JS_DIR_ . 'tiny_mce/tiny_mce.js');
            $this->context->controller->addJS(_PS_JS_DIR_ . 'admin/tinymce.inc.js');
            $this->context->controller->addCSS($this->getLocalPath(). 'views/css/admin.css');
        }
    }

    public function hookActionSupplierFormBuilderModifier($params)
    {
        /** @var \Symfony\Component\Form\FormBuilder $formBuilder */
        $formBuilder = $params['form_builder'];

        $formBuilder->add(
            'active_mail',
            \PrestaShopBundle\Form\Admin\Type\SwitchType::class,
            [
                'label' => $this->l('Activer les mails pour ce fournisseur'), //Label du champ
                'choices' => [
                    $this->l('No') => 0,
                    $this->l('Yes') => 1,
                ],
            ]
        );

        $formBuilder->add(
            'email',
            \Symfony\Component\Form\Extension\Core\Type\TextType::class,
            [
                'label' => $this->l('Email du service commande'), //Label du champ
                'required' => false
            ]
        );

        $params['data']['active_mail'] = false;
        $params['data']['email'] = '';

        $supplier_params = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'supplier_mail WHERE id_supplier = '.(int)$params['id']);
        if ($supplier_params) {
            $params['data']['active_mail'] = (bool)$supplier_params['active_mail'];
            $params['data']['email'] = $supplier_params['email'];
        }

        $formBuilder->setData($params['data']);
    }

    public function hookActionAfterCreateSupplierFormHandler(array $params)
    {
        $this->updateSupplierData($params['form_data'], $params);
    }

    public function hookActionAfterUpdateSupplierFormHandler(array $params)
    {
        $this->updateSupplierData($params['form_data'], $params);
    }

    protected function updateSupplierData(array $data, $params)
    {
        $datas = array(
            'active_mail' => (int)$data['active_mail'],
            'id_supplier' => (int)$params['id'],
            'email' => pSQL($data['email']),
        );

        Db::getInstance()->insert('supplier_mail', $datas, false, true, Db::REPLACE);
    }

    private function getProductsList($products)
    {
        $this->context->smarty->assign(array(
            'products' => $products,
        ));
        return $this->context->smarty->fetch($this->getLocalPath().'mails/_partials/product_list.tpl');
    }
}