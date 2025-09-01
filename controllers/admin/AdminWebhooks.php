<?php
/**
 * PrestaShop Webhooks
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file docs/licenses/LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @author    Experto PrestaShop <https://www.youtube.com/@ExpertoPrestaShop>
 * @copyright since 2009 Experto PrestaShop
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Admin controller for webhooks management
 */
class AdminWebhooksController extends ModuleAdminController
{
    /**
     * Constructor for the AdminWebhooksController
     */
    public function __construct()
    {
        $this->table = 'webhook';
        $this->className = 'Webhook';
        $this->context = Context::getContext();
        $this->lang = false;
        $this->allow_export = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->bulk_actions = [
            'delete' => ['text' => $this->module->l('Delete selected', 'AdminWebhooks'), 'confirm' => $this->module->l('Delete selected items?', 'AdminWebhooks')],
            'enableSelection' => ['text' => $this->module->l('Enable selection', 'AdminWebhooks')],
            'disableSelection' => ['text' => $this->module->l('Disable selection', 'AdminWebhooks')],
        ];

        $resources = WebserviceRequest::getResources();
        $resources = array_filter($resources, function ($resource) {
            return !empty($resource['class']);
        });
        $list = array_column($resources, 'class');
        $entities = array_combine($list, $list);
        $actions = [
            'add' => $this->module->l('Created', 'AdminWebhooks'),
            'update' => $this->module->l('Updated', 'AdminWebhooks'),
            'delete' => $this->module->l('Deleted', 'AdminWebhooks'),
        ];

        $this->fields_list = [
            'action' => [
                'title' => $this->module->l('Action', 'AdminWebhooks'),
                'type' => 'select',
                'list' => $actions,
                'filter_key' => 'a!action',
                'filter_type' => 'string',
            ],
            'entity' => [
                'title' => $this->module->l('Entity', 'AdminWebhooks'),
                'type' => 'select',
                'list' => $entities,
                'filter_key' => 'a!entity',
                'filter_type' => 'string',
            ],
            'url' => [
                'title' => $this->module->l('URL', 'AdminWebhooks'),
                'align' => 'left',
                'orderby' => false,
            ],
            'description' => [
                'title' => $this->module->l('Description', 'AdminWebhooks'),
                'align' => 'left',
                'orderby' => false,
            ],
            'active' => [
                'title' => $this->module->l('Enabled', 'AdminWebhooks'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'width' => 32,
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Webhook', 'AdminWebhooks'),
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->module->l('Action', 'AdminWebhooks'),
                    'name' => 'action',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => 'add', 'name' => $this->module->l('Created', 'AdminWebhooks')],
                            ['id' => 'update', 'name' => $this->module->l('Updated', 'AdminWebhooks')],
                            ['id' => 'delete', 'name' => $this->module->l('Deleted', 'AdminWebhooks')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Entity', 'AdminWebhooks'),
                    'name' => 'entity',
                    'required' => true,
                    'options' => [
                        'query' => $resources,
                        'id' => 'class',
                        'name' => 'class',
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('URL', 'AdminWebhooks'),
                    'name' => 'url',
                    'size' => 100,
                    'required' => true,
                    'desc' => $this->module->l('Remote URL.', 'AdminWebhooks'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description', 'AdminWebhooks'),
                    'name' => 'description',
                    'rows' => 3,
                    'cols' => 110,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Status', 'AdminWebhooks'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->module->l('Enabled', 'AdminWebhooks'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->module->l('Disabled', 'AdminWebhooks'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save', 'AdminWebhooks'),
            ],
        ];

        $this->actions_available[] = 'test';
        $this->addRowAction('test');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    /**
     * Initialize the page header toolbar
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if ($this->display != 'edit' && $this->display != 'add') {
            $this->page_header_toolbar_btn['new_webhook'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->module->l('Add new', 'AdminWebhooks'),
                'icon' => 'process-icon-new',
            ];

            $this->page_header_toolbar_btn['export_json'] = [
                'href' => self::$currentIndex . '&action=exportJson&token=' . $this->token,
                'desc' => $this->module->l('Export JSON', 'AdminWebhooks'),
                'icon' => 'process-icon-download',
            ];

            $this->page_header_toolbar_btn['import_json'] = [
                'href' => self::$currentIndex . '&action=importJsonForm&token=' . $this->token,
                'desc' => $this->module->l('Import JSON', 'AdminWebhooks'),
                'icon' => 'process-icon-upload',
            ];

            $this->page_header_toolbar_btn['delete_all'] = [
                'href' => self::$currentIndex . '&action=deleteAll&token=' . $this->token,
                'desc' => $this->module->l('Delete All', 'AdminWebhooks'),
                'icon' => 'process-icon-delete',
            ];
        }
    }

    /**
     * Display test link for a webhook
     */
    public function displayTestLink($token, $id, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_default.tpl');
        if (!array_key_exists('Test', self::$cache_lang)) {
            self::$cache_lang['Test'] = $this->module->l('Test', 'AdminWebhooks');
        }

        $tpl->assign([
            'href' => $this->context->link->getAdminLink('AdminWebhooks') . '&' . $this->identifier . '=' . $id . '&action=test',
            'action' => self::$cache_lang['Test'],
            'id' => $id,
        ]);

        return $tpl->fetch();
    }

    /**
     * Process webhook test
     */
    public function processTest()
    {
        if ($id = (int) Tools::getValue('id_webhook')) {
            $webhook = new Webhook($id);

            $code = Ps_Webhooks::executeUrl($webhook->url, $webhook->action, $webhook->entity, new $webhook->entity, true);
            $message = 'HTTP ' . $code;
            if (version_compare(_PS_VERSION_, '9.0', '<')) {
                $message = '<b>' . $message . '</b>';
            }

            if ($code >= 200 && $code < 300) {
                $this->confirmations[] = $this->module->l('Webhook connection tested', 'AdminWebhooks') . ': ' . $message;
                return true;
            } else {
                $this->errors[] = $this->module->l('Is not possible to connect to this URL', 'AdminWebhooks') . ': ' . $message;
                return false;
            }
        }

        $this->errors[] = $this->module->l('Is not possible to test the connection to this URL', 'AdminWebhooks');
        return false;
    }

    /**
     * Extra: Export, Import and Delete All
     */
    public function processExportJson()
    {
        $webhooks = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'webhook');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="webhooks.json"');
        echo json_encode($webhooks, JSON_PRETTY_PRINT);
        exit;
    }

    public function processDeleteAll()
    {
        Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'webhook');
        $this->confirmations[] = $this->module->l('All webhooks deleted.', 'AdminWebhooks');
    }

    public function processImportJson()
    {
        if (!isset($_FILES['import_json']) || $_FILES['import_json']['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->module->l('No file uploaded or invalid file.', 'AdminWebhooks');
            return false;
        }

        $data = file_get_contents($_FILES['import_json']['tmp_name']);
        $webhooks = json_decode($data, true);

        if (!is_array($webhooks)) {
            $this->errors[] = $this->module->l('Invalid JSON format.', 'AdminWebhooks');
            return false;
        }

        foreach ($webhooks as $w) {
            $webhook = new Webhook();
            $webhook->action = pSQL($w['action']);
            $webhook->entity = pSQL($w['entity']);
            $webhook->url = pSQL($w['url']);
            $webhook->description = isset($w['description']) ? pSQL($w['description']) : '';
            $webhook->active = (int)$w['active'];
            $webhook->add();
        }

        $this->confirmations[] = $this->module->l('Webhooks imported successfully.', 'AdminWebhooks');
        return true;
    }

    public function processImportJsonForm()
    {
        $this->content .= '
        <form method="post" enctype="multipart/form-data" action="'.self::$currentIndex.'&token='.$this->token.'&action=importJson">
            <div class="panel">
                <h3>'.$this->module->l('Import Webhooks JSON', 'AdminWebhooks').'</h3>
                <div class="form-group">
                    <input type="file" name="import_json" accept=".json" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    '.$this->module->l('Upload and Import', 'AdminWebhooks').'
                </button>
            </div>
        </form>';
    }

    /**
     * Before delete hook
     */
    protected function beforeDelete($object)
    {
        $this->module->unregisterHook($object->getHookName());
        return true;
    }

    /**
     * After add hook
     */
    protected function afterAdd($object)
    {
        $this->module->registerHook($object->getHookName());
        return true;
    }

    /**
     * After update hook
     */
    protected function afterUpdate($object)
    {
        $this->module->registerHook($object->getHookName());
        return true;
    }
}
