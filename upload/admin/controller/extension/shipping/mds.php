<?php

use Cart\User;
use Mds\Collivery;

/**
 * @property \ModelSettingEvent $model_setting_event
 * @property \ModelSettingSetting $model_setting_setting
 * @property \ModelLocalisationGeoZone $model_localisation_geo_zone
 * @property \ModelLocalisationTaxClass $model_localisation_tax_class
 * @property \Loader $load
 * @property \Document $document
 * @property \Request $request
 * @property \Session $session
 * @property \Response $response
 * @property \Language $language
 * @property \Url $url
 * @property \Config $config
 * @property \Log $log
 * @property User $user
 * @property ModelExtensionShippingMds $model_extension_shipping_mds
 * @property Collivery $collivery
 */
class ControllerExtensionShippingMds extends Controller
{
    private $error = [];

    public function index()
    {
        $this->load->language('extension/shipping/mds');
        $this->load->model('setting/event');
        $this->load->model('setting/setting');
        $this->document->setTitle($this->language->get('heading_title'));


        if (isset($this->request->post['clear_cache'])) {
            $this->collivery->clearCache();
            $this->session->data['success'] = 'MDS cache cleared.';
            $this->response->redirect(
                $this->url->link('extension/shipping/mds', 'user_token='.$this->session->data['user_token'])
            );

            return;
        }

        if (isset($this->request->post['download_error_logs'])) {
            $zipFile = $this->collivery->compressBacktraceFiles();

            if ( ! $zipFile) {
                $this->session->data['error'] = 'Could not store zip file on your server. No access.';

                $this->response->redirect(
                    $this->url->link('extension/shipping/mds', 'user_token='.$this->session->data['user_token'])
                );

                return;
            }

            $this->response->addHeader('Content-Type: application/zip');
            $this->response->addHeader('Content-Disposition: attachment; filename='.basename($zipFile));
            $this->response->addHeader("Content-Length: ".filesize($zipFile));

            $this->response->setOutput(file_get_contents($zipFile));

            return;
        }

        if (strtoupper($this->request->server['REQUEST_METHOD']) === 'POST') {
            $this->model_setting_setting->editSetting('shipping_mds', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'])
            );
        }

        $data             = $this->language->all();
        $services         = $this->collivery->getServices();
        $data['services'] = $services;

        foreach ($services as $key => $service) {
            if (isset($this->request->post['shipping_mds_service_display_name_'.$key])) {
                $data['shipping_mds_service_display_name_'.$key] = $this->request->post['shipping_mds_service_display_name_'.$key];
            } else {
                if ($this->config->get('shipping_mds_service_display_name_'.$key) == "") {
                    $data['shipping_mds_service_display_name_'.$key] = $service;
                } else {
                    $data['shipping_mds_service_display_name_'.$key] = $this->config->get(
                        'shipping_mds_service_display_name_'.$key
                    );
                }
            }
            if (isset($this->request->post['shipping_mds_service_surcharge_'.$key])) {
                $data['shipping_mds_service_surcharge_'.$key] = $this->request->post['shipping_mds_service_surcharge_'.$key];
            } else {
                $data['shipping_mds_service_surcharge_'.$key] = $this->config->get(
                    'shipping_mds_service_surcharge_'.$key
                );
            }
        }
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif ($this->collivery->authenticate() && $this->collivery->isAuthError()) {
            $data['error_warning'] = 'Incorrect Username Or Password For Collivery.net Plugin';
        } else {
            $data['error_warning'] = '';
        }
        if (isset($this->error['key'])) {
            $data['error_key'] = $this->error['key'];
        } else {
            $data['error_key'] = '';
        }
        if (isset($this->error['markup'])) {
            $data['error_markup'] = $this->error['markup'];
        } else {
            $data['error_markup'] = '';
        }
        if (isset($this->error['username'])) {
            $data['error_username'] = $this->error['username'];
        } else {
            $data['error_username'] = '';
        }
        if (isset($this->error['password'])) {
            $data['error_password'] = $this->error['password'];
        } else {
            $data['error_password'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
        }

        if (isset($this->session->data['error'])) {
            $data['error_warning'] .= $this->session->data['error'];
        }

        $data['mdsErrors'] = '';

        $data['breadcrumbs']   = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], 'SSL'),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link(
                'marketplace/extension',
                'user_token='.$this->session->data['user_token'].'&type=shipping',
                true
            ),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/mds', 'user_token='.$this->session->data['user_token'], 'SSL'),
        ];
        $data['action'] = $this->url->link(
            'extension/shipping/mds',
            'user_token='.$this->session->data['user_token'],
            true
        );
        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token='.$this->session->data['user_token'].'&type=shipping',
            true
        );

        if (isset($this->request->post['shipping_mds_username'])) {
            $data['shipping_mds_username'] = $this->request->post['shipping_mds_username'];
        } else {
            $data['shipping_mds_username'] = $this->config->get('shipping_mds_username');
        }
        if (isset($this->request->post['shipping_mds_password'])) {
            $data['shipping_mds_password'] = $this->request->post['shipping_mds_password'];
        } else {
            $data['shipping_mds_password'] = $this->config->get('shipping_mds_password');
        }
        if (isset($this->request->post['shipping_mds_markup'])) {
            $data['shipping_mds_markup'] = $this->request->post['shipping_mds_markup'];
        } else {
            $data['shipping_mds_markup'] = $this->config->get('shipping_mds_markup');
        }
        if (isset($this->request->post['shipping_mds_test'])) {
            $data['shipping_mds_test'] = $this->request->post['shipping_mds_test'];
        } else {
            $data['shipping_mds_test'] = $this->config->get('shipping_mds_test');
        }
        if (isset($this->request->post['shipping_mds_insurance'])) {
            $data['shipping_mds_insurance'] = $this->request->post['shipping_mds_insurance'];
        } else {
            $data['shipping_mds_insurance'] = $this->config->get('shipping_mds_insurance');
        }
        if (isset($this->request->post['shipping_mds_status'])) {
            $data['shipping_mds_status'] = $this->request->post['shipping_mds_status'];
        } else {
            $data['shipping_mds_status'] = $this->config->get('shipping_mds_status');
        }

        if (isset($this->request->post['shipping_mds_is_demo'])) {
            $data['shipping_mds_is_demo'] = $this->request->post['shipping_mds_is_demo'];
        } else {
            $data['shipping_mds_is_demo'] = $this->config->get('shipping_mds_is_demo');
        }
        if (isset($this->request->post['shipping_mds_tax_class_id'])) {
            $data['shipping_mds_tax_class_id'] = $this->request->post['shipping_mds_tax_class_id'];
        } else {
            $data['shipping_mds_tax_class_id'] = $this->config->get('shipping_mds_tax_class_id');
        }

        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        if (isset($this->request->post['shipping_mds_geo_zone_id'])) {
            $data['shipping_mds_geo_zone_id'] = $this->request->post['shipping_mds_geo_zone_id'];
        } else {
            $data['shipping_mds_geo_zone_id'] = $this->config->get('shipping_mds_geo_zone_id');
        }
        if (isset($this->request->post['shipping_mds_geo_zone_id'])) {
            $data['shipping_mds_geo_zone_id'] = $this->request->post['shipping_mds_geo_zone_id'];
        } else {
            $data['shipping_mds_geo_zone_id'] = $this->config->get('shipping_mds_geo_zone_id');
        }

        $data['shipping_mds_is_auto_create_waybill'] = $this->config->get('shipping_mds_is_auto_create_waybill');
        $data['shipping_mds_is_auto_create_address'] = $this->config->get('shipping_mds_is_auto_create_address');


        $data['default_collivery_from_addresses'] = $this->collivery->getAddresses();
        $data['default_address_id']               = $this->collivery->getDefaultAddressId();
        $data['user_token']                       = $this->request->get['user_token'];

        $this->load->model('localisation/geo_zone');
        $data['geo_zones']   = $this->model_localisation_geo_zone->getGeoZones();
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/shipping/mds', $data));
    }

    protected function validate()
    {
        if ( ! $this->user->hasPermission('modify', 'shipping/mds')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        $input = $this->request->post;
        if ( ! $input['shipping_mds_username']) {
            $this->error['username'] = $this->language->get('error_username');
        }
        if ( ! $input['shipping_mds_password']) {
            $this->error['password'] = $this->language->get('error_password');
        }
        if ($input['shipping_mds_username'] !== $this->config->get('shipping_mds_username')
            || $input['shipping_mds_password'] !== $this->config->get('shipping_mds_password')) {

            $mdsErrors = $this->collivery->getErrors();
            if ($mdsErrors) {
                $this->error['warning'] = $this->language->get('error_login');
            }
        }

        return ! $this->error;
    }

    // In the case of a re-upload or version upgrade of the extension
    // Event listener for model/setting/extension/addExtensionInstall/before
    public function refreshInstall(&$route, array $args)
    {
        $uploadedFile = $args[0];

        if ($uploadedFile !== 'collivery.ocmod.zip') {
            return;
        }

        // In case of non-BC changes in data structures
        // (note that `$this->collivery` is not available at this point)
        require_once DIR_SYSTEM.'library/mds/Collivery.php';
        (new Collivery($this->registry))->clearCache();


        // Because OpenCart doesn't allow any better way of clearing cache for rendered templates
        // but the `.twig` file might have changed in our upload
        // The best choice is to re-implement Template\Twig::render()
        // And use the deprecated Twig_Environment::getCacheFilename()
        try {
            // include and register Twig auto-loader
            include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');
            \Twig_Autoloader::register();

            $loader = new \Twig_Loader_Filesystem(DIR_TEMPLATE);
            $twig = new \Twig_Environment($loader, ['cache' => DIR_CACHE]);
            $file = $twig->getCacheFilename('extension/shipping/mds.twig');
            if ($file && file_exists($file)) {
                @unlink($file);
            }
        } catch (\Exception $e) {}
    }

    public function install()
    {

        $errors = '';
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $errors .= 'MDS Collivery requires PHP 5.4 in order to run. Please upgrade before installing.'.PHP_EOL;
        }
        if ( ! extension_loaded('soap')) {
            $errors .= 'MDS Collivery requires SOAP to be enabled on the server. Please make sure its enabled before installing.'.PHP_EOL;
        }

        if ($errors) {
            $this->log->write($errors);
            $div = '<div class="col-md-12 alert alert-danger">
                       '.$errors.'
                   </div>';
            die($div);
        }

        $this->load->model('extension/shipping/mds');
        $this->model_extension_shipping_mds->addColumns();
        $this->model_extension_shipping_mds->addCustomFields();

        // Make sure we rerun this install if a new version is uploaded
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('mds_refresh', 'admin/model/setting/extension/addExtensionInstall/before', 'extension/shipping/mds/refreshInstall');
    }

    public function uninstall()
    {
        $this->load->model('extension/shipping/mds');
        $this->model_extension_shipping_mds->dropColumns();
        $this->model_extension_shipping_mds->dropCustomFields();

        $this->load->model('setting/event');
        $this->model_setting_event->deleteEvent('mds_refresh');

        // $this->deleteAssocFiles(); NEEED WORK
    }

    private function deleteAssocFiles()
    {
        $files = [
            DIR_APPLICATION.'controller/extension/shipping/mds.php',
            DIR_CATALOG.'model/extension/shipping/mds.php',
            DIR_LANGUAGE.'en-gb/extension/shipping/mds.php',
            DIR_SYSTEM.'library/mds/Collivery.php',
            DIR_TEMPLATE.'extension/shipping/mds.twig',
        ];
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
