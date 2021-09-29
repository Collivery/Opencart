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
    private $isDemo = false;
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

            if ( ! $zipFile || ! file_exists($zipFile)) {

                if (! is_dir(basename($zipFile))) {
                    $this->session->data['success'] = 'No Collivery error logs written yet.';
                } else {
                    $this->session->data['error'] = 'Could not store zip file on your server. No access.';
                }

                $this->response->redirect(
                    $this->url->link('extension/shipping/mds', 'user_token='.$this->session->data['user_token'])
                );

                return;
            }

            $this->response->addHeader('Content-Type: application/zip');
            $this->response->addHeader('Content-Disposition: attachment; filename='.basename($zipFile));

            $this->response->setOutput(file_get_contents($zipFile));

            return;
        }

        if (strtoupper($this->request->server['REQUEST_METHOD']) === 'POST') {
            $this->model_setting_setting->editSetting('shipping_mds', $this->request->post);

            $defaultAddressId = isset($this->request->post['shipping_mds_default_address_id'])
                ? $this->request->post['shipping_mds_default_address_id']
                : false;
            if (is_numeric($defaultAddressId)) {
                // Cache the default address id in a context that's not only our OC settings
                $this->collivery->setDefaultAddressId($defaultAddressId);
            }

            $this->response->redirect(
                $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'])
            );

            return;
        }

        // Fetch all the view data from our lang files as well as the stored settings
        $data = array_merge(
            $this->language->all(),
            $this->model_setting_setting->getSetting('shipping_mds')
        );

        $data['user_token'] = $this->request->get['user_token'];
        $data['addresses']  = $this->collivery->getAddresses();
        $services           = $this->collivery->getServices();
        $data['services']   = $services;


        // Fill some data on first page load (if no config is saved)
        foreach ($services as $key => $service) {
            if ( ! isset($data['shipping_mds_service_display_name_'.$key]) ) {
                $data['shipping_mds_service_display_name_'.$key] = $service;
            }
        }
        if ( ! isset($data['shipping_mds_username']) ) {
           $data['shipping_mds_username'] = Collivery::$demoAccount['user_email'];
           $this->isDemo =true;
        }
        if ( ! isset($data['shipping_mds_password']) ) {
           $data['shipping_mds_password'] = Collivery::$demoAccount['user_password'];
        }
        if ( ! isset($data['shipping_mds_default_address_id']) ) {
           $data['shipping_mds_default_address_id'] = $this->collivery->getDefaultAddressId();
        }
        if ( ! isset($data['shipping_mds_is_auto_accept_waybill']) ) {
            $data['shipping_mds_is_auto_accept_waybill'] = 0;
        }
        if ( ! isset($data['shipping_mds_is_auto_create_waybill']) ) {
            $data['shipping_mds_is_auto_create_waybill'] = 0;
        }
        if ( ! isset($data['shipping_mds_is_demo']) ) {
            $data['shipping_mds_is_demo'] = $this->isDemo;
        }
        if ( ! isset($data['shipping_mds_rica']) ) {
            $data['shipping_mds_rica'] = 0;
        }
        if ( ! isset($data['shipping_mds_cover']) ) {
            $data['shipping_mds_cover'] =0;
        }

        // Set inside `$this->validate()`
        foreach (['warning', 'key', 'markup', 'username', 'password'] as $key) {
            if (isset($this->error[$key])) {
                $data["error_{$key}"] = $this->error[$key];
            } else {
                $data["error_{$key}"] = '';
            }
        }

        // Render session messages to view
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            $this->session->data['success'] = null;
        }

        if (isset($this->session->data['error'])) {
            $data['error_warning'] .= $this->session->data['error'];
            $this->session->data['error'] = null;
        }

        $data['breadcrumbs']   = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], true),
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
            'href' => $this->url->link('shipping/mds', 'user_token='.$this->session->data['user_token'], true),
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

        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

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

        $warning = '';
        if ($input['shipping_mds_username'] !== $this->config->get('shipping_mds_username')
            || $input['shipping_mds_password'] !== $this->config->get('shipping_mds_password')) {

            $mdsErrors = $this->collivery->getErrors();
            if ($mdsErrors) {
                $warning = $this->language->get('error_login');
            }
        }

        if ($this->collivery->authenticate() && $this->collivery->isAuthError()) {
            $warning .= 'Incorrect Username Or Password For Collivery.net Plugin';
        }

        $this->error['warning'] = $warning;

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
