<?php

/**
 * @property \ModelSettingEvent         $model_setting_event
 * @property \ModelSettingSetting       $model_setting_setting
 * @property \ModelLocalisationGeoZone  $model_localisation_geo_zone
 * @property \ModelLocalisationTaxClass $model_localisation_tax_class
 * @property \Loader                    $load
 * @property \Document                  $document
 * @property \Request                   $request
 * @property \Session                   $session
 * @property \Response                  $response
 * @property \Language                  $language
 * @property \Url                       $url
 * @property \Config                    $config
 * @property \Log                       $log
 * @property \Cart\User                 $user
 * @property \Mds\Collivery             $collivery
 */
class ControllerExtensionShippingMds extends Controller {
    private $error = array();
    protected $accessToken = '';
    protected $userName = null;
    protected $userId = null;

    const VERSION = 'v3';
    const APP_PLUGIN_NAME = 'collivery.net opencart plugin';
    const ENDPOINT_LOGIN = 'login';
    const ENDPOINT_SERVICE_TYPES = 'service_types';
    const ENDPOINT_DEFAULT_ADDRESS = 'default_address';
    const SANDBOX_USERNAME = 'demo@collivery.co.za';
    const SANDBOX_PASSWORD = 'demo';

    const USERNAME = 'shipping_mds_username';
    const PASSWORD = 'shipping_mds_password';

    public function index() {
        $this->load->language('extension/shipping/mds');
        $this->load->model('setting/event');
        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/geo_zone');
        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateSettings()) {
            $this->applyNewSettings($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/shipping/mds', 'user_token=' . $this->session->data['user_token'], 'SSL'));
        }

        $this->setUserApiAccessToken();

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs']   = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('extension/shipping', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/mds', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['action'] = $this->url->link('extension/shipping/mds', 'user_token='.$this->session->data['user_token'], 'SSL');
        $data['cancel'] = $this->url->link('extension/shipping', 'user_token='.$this->session->data['user_token'], 'SSL');

        $data['shipping_mds_status']                    = $this->getSettingValue('shipping_mds_status', 0);
        $data['geo_zones']                              = $this->model_localisation_geo_zone->getGeoZones();
        $data['shipping_mds_username']                  = $this->getSettingValue('shipping_mds_username', self::SANDBOX_USERNAME);
        $data['shipping_mds_password']                  = $this->getSettingValue('shipping_mds_password', self::SANDBOX_PASSWORD);
        $data['shipping_mds_is_demo']                  = $this->isSandBoxAccount();
        $data['shipping_mds_test']                      = $this->getSettingValue('shipping_mds_test');
        $data['shipping_mds_insurance']                 = $this->getSettingValue('shipping_mds_insurance');
        $data['shipping_mds_status']                    = $this->getSettingValue('shipping_mds_status');
        $data['shipping_mds_is_demo']                   = $this->getSettingValue('shipping_mds_is_demo');
        $data['shipping_mds_tax_class_id']              = $this->getSettingValue('shipping_mds_tax_class_id');
        $data['shipping_mds_geo_zone_id']               = $this->getSettingValue('shipping_mds_geo_zone_id');
        $data['shipping_mds_geo_zone_id']               = $this->getSettingValue('shipping_mds_geo_zone_id');
        $data['shipping_mds_geo_zone_id']               = $this->getSettingValue('shipping_mds_geo_zone_id');
        $data['shipping_mds_is_auto_create_waybill']    = $this->getSettingValue('shipping_mds_is_auto_create_waybill', 0);
        $data['shipping_mds_is_auto_create_address']    = $this->getSettingValue('shipping_mds_is_auto_create_address', 0);
        foreach ($this->getServices() as $key => $service) {
            $data['shipping_mds_service_display_name_' . $key] = $this->getSettingValue('shipping_mds_service_display_name_' . $key, $service);
            $data['shipping_mds_service_surcharge_' . $key] = $this->getSettingValue('shipping_mds_service_surcharge_' . $key);
        }

        $data['shipping_mds_rica']                      = $this->getSettingValue('shipping_mds_rica', 0);
        $data['mds_default_address']                     = $this->getDefaultAddress();

        $data['tax_classes']                            =   $this->model_localisation_tax_class->getTaxClasses();

        $data['user_token']                             = $this->request->get['user_token'];

        $data['header']                                 = $this->load->controller('common/header');
        $data['column_left']                            = $this->load->controller('common/column_left');
        $data['footer']                                 = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/mds', $data));

    }

    public function install() {
        $this->load->model('extension/shipping/mds');
        $this->model_extension_shipping_mds->addColumns();
        $this->model_extension_shipping_mds->addCustomFields();
    }

    public function uninstall(){
        $this->load->model('extension/shipping/mds');
        $this->model_extension_shipping_mds->dropColumns();
        $this->model_extension_shipping_mds->dropCustomFields();

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

    protected function applyNewSettings($new_settings) {

        $this->load->model('setting/setting');

        $old_settings = $this->model_setting_setting->getSetting('shipping_mds');

        $new_settings = array_merge($old_settings, $new_settings);

        $this->model_setting_setting->editSetting('shipping_mds', $new_settings, $this->store_id);

        foreach ($new_settings as $key => $value) {
            $this->setting->set($key, $value);
        }
    }

    protected function getSettingValue($key, $default = null, $checkbox = true) {
        if ($checkbox) {
            if ($this->request->server['REQUEST_METHOD'] == 'POST'
                && isset($this->request->post[$key])
                && !empty($this->request->post[$key])) {
                return $this->request->post[$key];
            }
        }
        if ($this->config->has($key)) {
            return $this->config->get($key);
        }
        return $default;
    }

    protected function validateSettings() {
        $this->validatePermission();

        if (empty($this->request->post['shipping_mds_username'])) {
            $this->error['username'] = $this->language->get('error_username');
        }
        if (empty($this->request->post['shipping_mds_password'])) {
            $this->error['password'] = $this->language->get('error_password');
        }
        return !$this->error;
    }

    protected function validatePermission()
    {
        if (!$this->user->hasPermission('modify', 'shipping/mds')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    protected function isSandBoxAccount()
    {
        return $this->userName == self::SANDBOX_USERNAME;
    }

    protected function attempt()
    {
        return $this->makeApiRequest('post',self::ENDPOINT_LOGIN, array(
            'email' =>  $this->getSettingValue(self::USERNAME, self::SANDBOX_USERNAME),
            'password' => $this->getSettingValue(self::PASSWORD, self::SANDBOX_PASSWORD)
        ));
    }

    protected function setUserApiAccessToken()
    {
        // TODO: try to get data from cache
        if ($result = $this->attempt()) {
            $this->accessToken =  $result->api_token;
            $this->userName =  $result->email_address;
            $this->userId =  $result->id;
        }
    }

    protected function token()
    {
        return $this->accessToken;
    }

    protected function getDefaultAddress()
    {
        if ($services = $this->get(self::ENDPOINT_DEFAULT_ADDRESS)) {
            return $services;
        }
    }

    protected function getServices()
    {
        $result = array();
        if (count($services = $this->get(self::ENDPOINT_SERVICE_TYPES))) {
            foreach ($services as $index => $service) {
                $result[$service->id] = $service->text;
            }
        }
        return $result;
    }

    private function get($endpoint, $data =array())
    {
        return $this->makeApiRequest('get', $endpoint,
            array_merge(array('api_token' => $this->token()), $data));
    }

    private function makeApiRequest($type, $endpoint, $data)
    {
        $http = $this->httpRequest();

        $http->setHeader('Content-type', 'application/json')
            ->setHeader('X-App-Name', self::APP_PLUGIN_NAME)
            ->setHeader('X-App-Version', self::VERSION)
            ->setHeader('X-App-Host', $_SERVER['HTTP_HOST'])
            ->setHeader('X-App-Lang', 'php');

        // parse json
        $http->setJsonDecoder();

        switch ($type) {
            case 'post':
                $result = $http->post($endpoint, $data);
                break;
            default:
                $result = $http->get($endpoint, $data);
                break;
        }

        if ($http->isCurlError()) {
            $this->error['warning'] = $http->getCurlErrorMessage();
        }

        if ($http->isHttpError() && $result !== null) {
            $this->error['warning'] =  $result->error->message;
        }

        if (empty($result)) {
            $this->error['warning'] =  'An unknown error occurred. Please try again';
        }

        if (isset($result->data)) {
            return $result->data;
        }
    }

    private function httpRequest()
    {
        $this->load->library('collivery');
        return $this->collivery->requestApi();
    }


}
