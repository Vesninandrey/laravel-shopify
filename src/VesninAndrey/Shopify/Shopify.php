<?php

namespace VesninAndrey\Shopify;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;

class Shopify
{
    /**
     * Shop domain
     *
     * @var string
     */
    protected $domain;

    /**
     *
     * Api key
     *
     * @var string
     */
    protected $apikey;

    /**
     *
     * Api secret key
     *
     * @var string
     */
    protected $apisecret;

    /**
     * OAuth access_token
     *
     * @var string
     */
    protected $access_token;

    /**
     * Guzzle Client
     *
     * @var object
     */
    protected $client;

    /**
     * __construct to init the guzzle client
     *
     * @param string $config
     * @param string $key
     * @param string $password
     */
    public function __construct( array $config )
    {
        $this->domain = array_get( $config, 'domain' );
        $this->apikey = array_get( $config, 'apikey' );
        $this->apisecret = array_get( $config, 'apisecret' );

        if (null !== array_get( $config, 'access_token' ) && null !== array_get( $config, 'domain' )) {
            $this->setAccessToken( array_get( $config, 'access_token' ) );
        }
    }

    /**
     * Set basic url
     */
    public function setUrl( $url )
    {
        $this->client = new Client( [ 'base_url' => $url ] );

        return $this;
    }

    /**
     * Set domain
     */
    public function setDomain( $domain )
    {
        $this->domain = $domain;

        $this->genUrl();

        return $this;
    }

    private function genUrl()
    {
        if (null !== $this->access_token) {
            $this->setUrl( 'https://' . $this->apikey . ':' . $this->access_token . '@' . $this->domain . '/admin/' );
        } else {
            $this->setUrl( 'https://' . $this->domain . '/admin/' );
        }
    }

    public function setAccessToken( $token )
    {
        $this->access_token = $token;

        $this->genUrl();

        return $this;
    }

    /**
     * Returns a string of the install URL for the app
     * @param array $data
     * @return string
     */
    public function installURL( $data = array() )
    {
        // https://{shop}.myshopify.com/admin/oauth/authorize?client_id={api_key}&scope={scopes}&redirect_uri={redirect_uri}
        return 'https://' . $this->domain . '/admin/oauth/authorize?client_id=' . $this->apikey . '&scope=' . implode( ',', $data['permissions'] ) . ( !empty( $data['redirect'] ) ? '&redirect_uri=' . urlencode( $data['redirect'] ) : '' );
    }

    /**
     * Verifies data returned by shopify
     * @param string $match
     * @param array $input
     * @return bool
     */
    public static function _verifyRequest( $match, array $input, $apisecret )
    {
        if ($match === null) {
            return false;
        }

        if ($apisecret === null) {
            throw new Exception( 'You must set apisecret' );
        }

        /* keys must be in order: code, shop, timestamp */
        ksort( $input );

        $http_query = http_build_query( $input );

        return isset( $input['timestamp'] ) && ( time() - $input['timestamp'] < 3600 ) && (String) $match === hash_hmac( 'sha256', $http_query, $apisecret );
    }

    public function verifyRequest( $match, array $input )
    {
        return static::_verifyRequest( $match, $input, $this->apisecret );
    }

    /**
     * Calls API and returns OAuth Access Token, which will be needed for all future requests
     * @param string $code
     * @return mixed
     * @throws \Exception
     */
    public function getAccessToken( $code = '' )
    {
        $data = [ 'client_id' => $this->apikey, 'client_secret' => $this->apisecret, 'code' => $code ];

        $access_token = $this->makeRequest( 'POST', 'oauth/access_token', $data );

        $this->access_token = array_get($access_token, 'access_token');

        return $this->access_token;
    }

    /**
     * send off the request to Shopify, encoding the data as JSON
     *
     * @param  string $method
     * @param  string $page
     * @param  array $options
     *
     * @return array
     */
    public function makeRequest( $method, $page, $data = [ ] )
    {
        return $this->client->send( $this->client->createRequest( $method, $page, [ 'json' => $data ] ) )->json();
    }

    /**
     * returns a count of products
     *
     * @return array
     */
    public function getProductsCount()
    {
        return $this->makeRequest( 'GET', 'products/count.json' );
    }

    /**
     * returns a list of products, depending on the input data
     *
     * @param  array $data
     *
     * @return array
     */
    public function getProducts( $data )
    {
        return $this->makeRequest( 'GET', 'products.json', $data );
    }

    /**
     * returns product information by id
     *
     * @param  int $productId
     *
     * @return array
     */
    public function getProductById( $productId )
    {
        return $this->makeRequest( 'GET', 'products/' . $productId . '.json' )['product'];
    }

    /**
     * creates a product on shopify
     *
     * @param  array $data
     *
     * @return array
     */
    public function createProduct( $data )
    {
        $d['product'] = ( !isset( $data['product'] ) ) ? $data : $data['product'];
        return $this->makeRequest( 'POST', 'products.json', $d );
    }

    /**
     * updates a product by id
     *
     * @param  int $productId
     * @param  array $data
     *
     * @return array
     */
    public function updateProduct( $productId, $data )
    {
        $d['product'] = ( !isset( $data['product'] ) ) ? $data : $data['product'];
        return $this->makeRequest( 'PUT', 'products/' . $productId . '.json', $d );
    }

    /**
     * Delete's a product from shopify
     *
     * @param  int $productId
     *
     * @return array
     */
    public function deleteProduct( $productId )
    {
        return $this->makeRequest( 'DELETE', 'products/' . $productId . '.json' );
    }

    /**
     * updates a specific variant by id
     *
     * @param  int $variantId
     * @param  array $data
     *
     * @return array
     */
    public function updateVariant( $variantId, $data )
    {
        $data['id'] = $variantId;
        $d['variant'] = ( !isset( $data['variant'] ) ) ? $data : $data['variant'];
        return $this->makeRequest( 'PUT', 'variants/' . $variantId . '.json', $d );
    }

    /**
     * creates a variant for the specific shopify id
     *
     * @param $shopifyId
     * @param $data
     *
     * @return array
     */
    public function createVariant( $shopifyId, $data )
    {
        $d['variant'] = ( !isset( $data['variant'] ) ) ? $data : $data['variant'];
        return $this->makeRequest( 'POST', 'products/' . $shopifyId . '/variants.json', $d );
    }

    /**
     * Delete's a variant from shopify
     *
     * @param  int $productId
     * @param  int $variantId
     *
     * @return array
     */
    public function deleteVariant( $productId, $variantId )
    {
        return $this->makeRequest( 'DELETE', 'products/' . $productId . '/variants/' . $variantId . '.json' );
    }

    /**
     * get a list of webhooks
     *
     * @return array
     */
    public function getWebhooks()
    {
        return $this->makeRequest( 'GET', 'webhooks.json' );
    }

    /**
     * create a webhook
     *
     * @param  array $data
     *
     * @return array
     */
    public function createWebhook( $data )
    {
        $d['webhook'] = ( !isset( $data['webhook'] ) ) ? $data : $data['webhook'];
        return $this->makeRequest( 'POST', 'webhooks.json', $d );
    }

    /**
     * update a webhook
     *
     * @param  array $data
     *
     * @return array
     */
    public function updateWebhook( $webhookId, $data )
    {
        $d['webhook'] = ( !isset( $data['webhook'] ) ) ? $data : $data['webhook'];
        return $this->makeRequest( 'PUT', 'webhooks/'.$webhookId.'.json', $d );
    }

    /**
     * update a webhook
     *
     * @param  array $data
     *
     * @return array
     */
    public function deleteWebhook( $webhookId )
    {
        return $this->makeRequest( 'DELETE', 'webhooks/'.$webhookId.'.json' );
    }

    /**
     * get a list of all customers in shopify
     *
     * @return array
     */
    public function getAllCustomers()
    {
        return $this->makeRequest( 'GET', 'customers.json' );
    }

    /**
     * creates an order on shopify
     *
     * @param $data
     *
     * @return array
     */
    public function createOrder( $data )
    {
        $d['order'] = ( !isset( $data['order'] ) ) ? $data : $data['order'];
        return $this->makeRequest( 'POST', 'orders.json', $d );
    }

    /**
     * Receives a list of orders
     *
     * @param $data
     *
     * @return array
     */
    public function getOrders( $data = [ ] )
    {
        return $this->makeRequest( 'GET', 'orders.json', $data );
    }

    /**
     * Receives a single order
     *
     * @param int $id
     *
     * @return array
     */
    public function getOrder( $id )
    {
        return $this->makeRequest( 'GET', 'orders/' . $id . '.json' );
    }

    public function getShop()
    {
        return $this->makeRequest( 'GET', '/admin/shop.json' );
    }
}