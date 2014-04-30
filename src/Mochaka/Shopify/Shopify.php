<?php

namespace Mochaka\Shopify;

use Config;
use Guzzle\Http\Client;
use GuzzleHttp\Exception\ClientErrorResponseException;


class Shopify {

    /**
     * Guzzle Client
     *
     * @var object
     */
    protected $client;

    /**
     * __construct to init the guzzle client
     * @param string $domain
     * @param string $key
     * @param string $password
    */
    public function __construct($domain, $key, $password)
    {
        $url = "https://".$key.":".$password."@".$domain."/admin/";
        $this->client = new Client(
            $url,
            ['defaults' => [
                'headers' => ['Content-Type' => 'application/json'],
            ]]
        );
    }

    /**
     * send off the request to Shopify, encoding the data as JSON
     * @param  string $method
     * @param  string $page
     * @param  array  $data
     * @return array
     */
    private function makeRequest($method, $page, $data = array())
    {

        $r =  $this->client->createRequest($method, $page, null, $data);
        if($data)
            $r->setBody(json_encode($data), 'application/json');

        try {
            return $r->send()->json();
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            return ['error'=>$e->getMessage(), 'url'=>$e->getRequest()->getUrl(), 'request'=>$e->getRequest(), 'status'=>$e->getResponse()->getStatusCode(), 'response'=>$e->getResponse()];
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
            return ['error'=>$e->getMessage(), 'url'=>$e->getRequest()->getUrl(), 'request'=>$e->getRequest(), 'status'=>$e->getResponse()->getStatusCode(), 'response'=>$e->getResponse()];
        }

    }

    /**
     * returns a count of products
     * @return array
     */
    public function getProductsCount()
    {
        return $this->makeRequest('GET', 'products/count.json');
    }
    
    /**
     * returns a list of products, depending on the input data
     * @param  array $data
     * @return array
     */
    public function getProducts($data)
    {
        return $this->makeRequest('GET', 'products.json', $data);
    }

    /**
     * returns product information by id
     * @param  int $productId
     * @return array
     */
    public function getProductById($productId)
    {
        return $this->makeRequest('GET', 'products/'.$productId.'.json')['product'];
    }

    /**
     * creates a product on shopify
     * @param  array $data
     * @return array
     */
    public function createProduct($data)
    {
        $d['product'] = (!isset($data['product'])) ? $data : $data['product'];
        return $this->makeRequest('POST', 'products.json', $d);
    }

    /**
     * updates a product by id
     * @param  int $productId
     * @param  array $data
     * @return array
     */
    public function updateProduct($productId, $data)
    {
        $d['product'] = (!isset($data['product'])) ? $data : $data['product'];
        return $this->makeRequest('PUT', 'products/'.$productId.'.json', $d);
    }

    /**
     * Delete's a product from shopify
     * @param  int $productId
     * @return array
     */
    public function deleteProduct($productId)
    {
        return $this->makeRequest('DELETE', 'products/'.$productId.'.json');
    }

    /**
     * search products - see http://docs.shopify.com/api/product#index for search options
     * @param  array $search
     * @return array
     */
    public function searchProduct($search)
    {
        return $this->makeRequest('GET', 'products.json?'.http_build_query($search));
    }

    /**
     * updates a specific variant by id
     * @param  int $variantId
     * @param  array $data
     * @return array
     */
    public function updateVariant($variantId, $data)
    {
        $data['id'] = $variantId;
        $d['variant'] = (!isset($data['variant'])) ? $data : $data['variant'];
        return $this->makeRequest('PUT', 'variants/'.$variantId.'.json', $d);
    }

    /**
     * Delete's a variant from shopify
     * @param  int $productId
     * @param  int $variantId
     * @return array
     */
    public function deleteVariant($productId, $variantId)
    {
        return $this->makeRequest('DELETE', 'products/'.$productId.'/variants/'.$variantId.'.json');
    }

    /**
     * get a list of webhooks
     * @return array
     */
    public function getWebhooks()
    {
        return $this->makeRequest('GET', 'webhooks.json');
    }

    /**
     * create a webhook
     * @param  array $data
     * @return array
     */
    public function createWebhook($data)
    {
        $d['webhook'] = (!isset($data['webhook'])) ? $data : $data['webhook'];
        return $this->makeRequest('POST', 'webhooks.json', $d);
    }

    /**
     * get a list of all customers in shopify
     * @return array
     */
    public function getAllCustomers()
    {
        return $this->makeRequest('GET', 'customers.json');
    }
}
