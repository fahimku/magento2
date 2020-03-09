<?php

namespace Transfluent\Translate\Helper;

class Api extends \Magento\Framework\App\Helper\AbstractHelper {
    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $_store_manager;

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const PRODUCTION_HOST = 'https://transfluent.com/v2/';
    const DEV_HOST_CONFIG_FILE = 'dev_host.config';

    static $API_URL;
    static $DEV_MODE = false;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $store_manager
    ) {
        $this->_store_manager = $store_manager;
        parent::__construct($context);
        $this->setApiUrl(self::getApiUrl());
    }

    private function setApiUrl($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            self::$API_URL = $url;
        }

        if (self::PRODUCTION_HOST != $url) {
            $this->setDevMode(true);
        }
    }

    private function setDevMode($mode) {
        if (true === $mode || false === $mode) {
            self::$DEV_MODE = $mode;
        }
    }

    public static function getApiUrl() {
        $API_URL = self::PRODUCTION_HOST;
        $dev_host_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::DEV_HOST_CONFIG_FILE;
        if (is_file($dev_host_file)) {
            $API_URL = trim(file_get_contents($dev_host_file));
        }
        return $API_URL;
    }

    public function CreateCategoryQuote($source_store, $source_language, $target_store, $target_language, $level, $collision_strategy, $category_ids, $translate_fields = null) {
        $extension_callback_endpoint = $this->_urlBuilder->getUrl('transfluenttranslate/');
        $store_endpoint = $this->_store_manager->getStore($target_store)->getUrl('transfluenttranslate/'); // returns URL with ?___store=[STORE_CODE]
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        /** @var Transfluent\Translate\Helper\Token $token_helper */
        $token_helper = $objectManager->get('Transfluent\Translate\Helper\Token');
        $version = $productMetadata->getVersion();
        $extension_version = '2.0.0';
        $payload = array(
            'magento_ver' => $version,
            'magento_url' => $extension_callback_endpoint,
            'magento_store_url' => $store_endpoint,
            'extension_ver' => $extension_version,
            'source_store' => $source_store,
            'source_language' => $source_language,
            'target_store' => $target_store,
            'target_language' => $target_language,
            'level' => $level,
            'collision' => $collision_strategy,
            'category_ids' => '[' . implode(",", $category_ids) . ']',
            'token' => $token_helper->GetToken(),
            'hash' => md5($token_helper->GetToken())
        );
        if (!is_null($translate_fields)) {
            $payload['translate_fields'] = $translate_fields;
        }
        return $this->CallApi('magento/quote', self::HTTP_POST, $payload);
    }

    public function GetQuote($quote_id) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var Transfluent\Translate\Helper\Token $token_helper */
        $token_helper = $objectManager->get('Transfluent\Translate\Helper\Token');
        $payload = array(
            'id' => $quote_id,
            'token' => $token_helper->GetToken()
        );
        return $this->CallApi('magento/quote', self::HTTP_GET, $payload);
    }

    public function CreateCmsContentQuote($source_store, $source_language, $target_store, $target_language, $level, $collision_strategy, $cms_page_ids, $cms_block_ids) {
        $extension_callback_endpoint = $this->_urlBuilder->getUrl('transfluenttranslate/');
        $store_endpoint = $this->_store_manager->getStore($target_store)->getUrl('transfluenttranslate/'); // returns URL with ?___store=[STORE_CODE]
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        /** @var Transfluent\Translate\Helper\Token $token_helper */
        $token_helper = $objectManager->get('Transfluent\Translate\Helper\Token');
        $version = $productMetadata->getVersion();
        $extension_version = '2.0.0';
        $payload = array(
            'magento_ver' => $version,
            'magento_url' => $extension_callback_endpoint,
            'magento_store_url' => $store_endpoint,
            'extension_ver' => $extension_version,
            'source_store' => $source_store,
            'source_language' => $source_language,
            'target_store' => $target_store,
            'target_language' => $target_language,
            'level' => $level,
            'collision' => $collision_strategy,
            'cms_page_ids' => '[' . $cms_page_ids . ']',
            'cms_block_ids' => '[' . $cms_block_ids . ']',
            'token' => $token_helper->GetToken(),
            'hash' => md5($token_helper->GetToken())
        );
        return $this->CallApi('magento/quote', self::HTTP_POST, $payload);
    }

    public function OrderCmsQuote($quote_id, $instructions) {
        return $this->OrderCategoryQuote($quote_id, $instructions);
    }

    public function OrderCategoryQuote($quote_id, $instructions) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var Transfluent\Translate\Helper\Token $token_helper */
        $token_helper = $objectManager->get('Transfluent\Translate\Helper\Token');
        $payload = array(
            'id' => $quote_id,
            'token' => $token_helper->GetToken(),
            'order' => true,
            'instructions' => $instructions,
            'method' => 'PUT',
            '__fork' => 1
        );
        return $this->CallApi('magento/quote', self::HTTP_POST, $payload);
    }

    public function UpdateCategoryQuote($quote_id, $translate_fields) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var Transfluent\Translate\Helper\Token $token_helper */
        $token_helper = $objectManager->get('Transfluent\Translate\Helper\Token');
        $payload = array(
            'id' => $quote_id,
            'token' => $token_helper->GetToken(),
            'translate_fields' => implode(",", $translate_fields),
            'method' => 'PUT',
            '__fork' => 1
        );
        return $this->CallApi('magento/quote', self::HTTP_POST, $payload);
    }

    private function CallApi($method_name, $method = self::HTTP_GET, $payload = array()) {
        return $this->Request($method_name, $method, $payload);
    }

    private function UriFromMethod($method_name) {
        return strtolower(preg_replace("/(?!^)([A-Z]{1}[a-z0-9]{1,})/", '/$1', $method_name)) . '/';
    }

    private function Request($method_name, $method = self::HTTP_GET, $payload = array()) {
        $uri = $this->UriFromMethod($method_name);

        $curl_handle = curl_init(self::$API_URL . $uri);
        if (!$curl_handle) {
            throw new \Exception('Could not initialize cURL!');
        }
        switch (strtoupper($method)) {
            case self::HTTP_GET:
                $url = self::$API_URL . $uri . '?';
                $url_parameters = array();
                foreach ($payload AS $key => $value) {
                    $url_parameters[] = $key . '=' . urlencode($value);
                }
                $url .= implode("&", $url_parameters);
                curl_setopt($curl_handle, CURLOPT_URL, $url);
                break;
            case self::HTTP_POST:
                curl_setopt($curl_handle, CURLOPT_POST, TRUE);
                if (!empty($payload)) {
                    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $payload);
                }
                break;
            default:
                throw new \Exception('Unsupported request method.');
        }
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        if (self::$DEV_MODE) {
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl_handle);
        $info = curl_getinfo($curl_handle);
        curl_close($curl_handle);

        if (!$response) {
            throw new \Exception('Failed to connect with Transfluent\'s API. cURL error: ' . curl_error($curl_handle));
        }
        // !isset($info['http_code']) || $info['http_code'] != 200
        try {
            $response_obj = json_decode($response, true);
        } catch (\Exception $e) {
            if ($info['http_code'] == 500) {
                throw new \Exception('The order could not be processed. Please try again!');
            }
            if (self::$DEV_MODE) {
                error_log('API sent invalid JSON response: ' . $response . ', info: ' . print_r($info, true));
            }
            throw $e;
        }

        return $response_obj;
    }
}
