<?php

class ControllerExtensionShippingCollivery extends Controller {

    public function index() {

    }

    public function install() {
        $this->load->model('extension/shipping/collivery');
        $this->model_extension_shipping_collivery->addColumns();
    }

    public function uninstall() {
        $this->load->model('extension/shipping/collivery');
        $this->model_extension_shipping_collivery->dropColumns();
    }

}