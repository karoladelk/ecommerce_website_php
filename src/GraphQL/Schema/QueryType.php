<?php

namespace App\GraphQL\Schema;

use App\GraphQL\Queries\OrderQuery;
use GraphQL\Type\Definition\ObjectType;

use App\GraphQL\Queries\ProductQuery;

class QueryType extends ObjectType
{
    public function __construct()
    {

        $productQueries = new ProductQuery();
        $orderQueries = new OrderQuery();
        $config = [
            'name' => 'Query',
            'fields' => [
                'products' => $productQueries->getProducts(),
                'categories' => $productQueries->getCategories(),
                'productById' => $productQueries->getProductById(),
                'getOrders' => $orderQueries->getOrders(),
                'getOrderById' => $orderQueries->getOrderById(),
            ],
        ];
        parent::__construct($config);
    }
}
