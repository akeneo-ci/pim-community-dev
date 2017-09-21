<?php

namespace Pim\Bundle\ApiBundle\tests\integration\Controller\Product;

use Akeneo\Test\Integration\Configuration;
use Doctrine\Common\Collections\Collection;

class SuccessListVariantProductIntegration extends AbstractProductTestCase
{
    /** @var Collection */
    private $products;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        // no locale, no scope, 1 category
        $this->createVariantProduct('apollon_blue_s', [
            'categories' => ['master'],
            'parent' => 'amor',
            'values' => [
                'size' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 's',
                    ],
                ],
            ],
        ]);

        // apollon_blue_m, categorized in 1 tree (master)
        $this->createVariantProduct('apollon_blue_m', [
            'categories' => ['categoryA'],
            'parent' => 'amor',
            'values' => [
                'color' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'blue',
                    ],
                ],
            ],
        ]);

        // apollon_blue_l, categorized in 1 tree (master)
        $this->createVariantProduct('apollon_blue_l', [
            'categories' => ['categoryB', 'categoryC'],
            'parent' => 'amor',
            'values' => [
                'size' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'l',
                    ],
                ],
            ],
        ]);

        // apollon_blue_m & apollon_blue_l, categorized in 2 trees (master and categoryA1)
        $this->createVariantProduct('apollon_blue_xl', [
            'categories' => ['categoryA2', 'categoryA1'],
            'parent' => 'amor',
            'values' => [
                'size' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'xl',
                    ],
                ],
            ],
        ]);

        $this->createVariantProduct('apollon_blue_xxl', [
            'categories' => ['categoryA1'],
            'parent' => 'amor',
            'values' => [
                'size' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'xxl',
                    ],
                ],
            ],
        ]);

        $this->createVariantProduct('apollon_blue_xs', [
            'parent' => 'amor',
            'values' => [
                'size' => [
                    [
                        'locale' => null,
                        'scope' => null,
                        'data' => 'xs',
                    ],
                ],
            ],
        ]);

        $this->products = $this->get('pim_catalog.repository.product')->findAll();
    }

    /**
     * Get all products, whatever locale, scope, category with the default pagination type that is with an offset.
     */
    public function testDefaultPaginationListProductsWithoutParameter()
    {
        $standardizedProducts = $this->getStandardizedProducts();
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products');
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href": "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10"},
        "first" : {"href": "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10"}
    },
    "current_page" : 1,
    "_embedded"    : {
		"items": [
            {$standardizedProducts['apollon_blue_s']},
            {$standardizedProducts['apollon_blue_m']},
            {$standardizedProducts['apollon_blue_l']},
            {$standardizedProducts['apollon_blue_xl']},
            {$standardizedProducts['apollon_blue_xxl']},
            {$standardizedProducts['apollon_blue_xs']}
		]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testDefaultPaginationFirstPageListProductsWithCount()
    {
        $standardizedProducts = $this->getStandardizedProducts();
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?with_count=true&limit=3');
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href": "http://localhost/api/rest/v1/products?page=1&with_count=true&pagination_type=page&limit=3"},
        "first" : {"href": "http://localhost/api/rest/v1/products?page=1&with_count=true&pagination_type=page&limit=3"},
        "next"  : {"href": "http://localhost/api/rest/v1/products?page=2&with_count=true&pagination_type=page&limit=3"}
    },
    "current_page" : 1,
    "items_count"  : 6,
    "_embedded"    : {
		"items": [
            {$standardizedProducts['apollon_blue_s']},
            {$standardizedProducts['apollon_blue_m']},
            {$standardizedProducts['apollon_blue_l']}
		]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testDefaultPaginationLastPageListProductsWithCount()
    {
        $standardizedProducts = $this->getStandardizedProducts();
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?with_count=true&limit=3&page=2');
        $expected = <<<JSON
{
    "_links": {
        "self"     : {"href": "http://localhost/api/rest/v1/products?page=2&with_count=true&pagination_type=page&limit=3"},
        "first"    : {"href": "http://localhost/api/rest/v1/products?page=1&with_count=true&pagination_type=page&limit=3"},
        "previous" : {"href": "http://localhost/api/rest/v1/products?page=1&with_count=true&pagination_type=page&limit=3"},
        "next"     : {"href": "http://localhost/api/rest/v1/products?page=3&with_count=true&pagination_type=page&limit=3"}
    },
    "current_page" : 2,
    "items_count"  : 6,
    "_embedded"    : {
		"items": [
            {$standardizedProducts['apollon_blue_xl']},
            {$standardizedProducts['apollon_blue_xxl']},
            {$standardizedProducts['apollon_blue_xs']}
		]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    /**
     * Scope "ecommerce" has only "en_US" activated locale and it category tree linked is "master"
     * So PV are returned only if:
     *    - scope = "ecommerce"
     *    - locale = "en_US" or null
     * Then only products in "master" tree are returned
     */
    public function testOffsetPaginationListProductsWithEcommerceChannel()
    {
        $standardizedProducts = $this->getStandardizedProducts();
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?scope=ecommerce&pagination_type=page');
        $expected = <<<JSON
{
    "_links"       : {
        "self"  : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&scope=ecommerce"},
        "first" : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&scope=ecommerce"}
    },
    "current_page" : 1,
    "_embedded"    : {
        "items" : [
            {$standardizedProducts['apollon_blue_s']},
            {$standardizedProducts['apollon_blue_m']},
            {$standardizedProducts['apollon_blue_l']},
            {$standardizedProducts['apollon_blue_xl']},
            {$standardizedProducts['apollon_blue_xxl']}
		]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testOffsetPaginationListProductsWithFilteredAttributes()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?attributes=color&pagination_type=page');
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&attributes=color"},
        "first" : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&attributes=color"}
    },
    "current_page" : 1,
    "_embedded"    : {
        "items" : [
            {
                "_links" : {
                    "self" : {"href" : "http://localhost/api/rest/v1/products/apollon_blue_s"}
                },
                "identifier"    : "apollon_blue_s",
                "family"        : "familyA",
                "parent"        : "amor",
                "groups"        : [],
                "variant_group" : null,
                "categories"    : ["master"],
                "enabled"       : true,
                "values"        : {},
                "created"       : "2017-01-23T11:44:25+01:00",
                "updated"       : "2017-01-23T11:44:25+01:00",
                "associations"  : {}
            },
            {
                "_links" : {
                    "self" : {"href" : "http://localhost/api/rest/v1/products/apollon_blue_m"}
                },
                "identifier"    : "apollon_blue_m",
                "family"        : "familyA",
                "parent"        : "amor",
                "groups"        : [],
                "variant_group" : null,
                "categories"    : ["categoryA"],
                "enabled"       : true,
                "values": {
                    "color": [{
                        "locale": null,
                        "scope": null,
                        "data": "blue"
                    }]
                },
                "created"       : "2017-01-23T11:44:25+01:00",
                "updated"       : "2017-01-23T11:44:25+01:00",
                "associations"  : {}
            },
            {
                "_links" : {
                    "self" : {"href" : "http://localhost/api/rest/v1/products/apollon_blue_l"}
                },
                "identifier"    : "apollon_blue_l",
                "family"        : "familyA",
                "parent"        : "amor",
                "groups"        : [],
                "variant_group" : null,
                "categories"    : ["categoryB", "categoryC"],
                "enabled"       : true,
                "values"        : {},
                "created"       : "2017-01-23T11:44:25+01:00",
                "updated"       : "2017-01-23T11:44:25+01:00",
                "associations"  : {}
            },
            {
                "_links" : {
                    "self" : {"href" : "http://localhost/api/rest/v1/products/apollon_blue_xl"}
                },
                "identifier"    : "apollon_blue_xl",
                "family"        : "familyA",
                "parent"        : "amor",
                "groups"        : [],
                "variant_group" : null,
                "categories"    : ["categoryA1", "categoryA2"],
                "enabled"       : true,
                "values"        : {},
                "created"       : "2017-01-23T11:44:25+01:00",
                "updated"       : "2017-01-23T11:44:25+01:00",
                "associations"  : {}
            },
            {
                "_links" : {
                    "self" : {"href" : "http://localhost/api/rest/v1/products/apollon_blue_xxl"}
                },
                "identifier"    : "apollon_blue_xxl",
                "family"        : "familyA",
                "parent"        : "amor",
                "groups"        : [],
                "variant_group" : null,
                "categories"    : ["categoryA1"],
                "enabled"       : true,
                "values"        : {},
                "created"       : "2017-01-23T11:44:25+01:00",
                "updated"       : "2017-01-23T11:44:25+01:00",
                "associations"  : {}
            },
            {
                "_links" : {
                    "self" : {"href" : "http://localhost/api/rest/v1/products/apollon_blue_xs"}
                },
                "identifier"    : "apollon_blue_xs",
                "family"        : "familyA",
                "parent"        : "amor",
                "groups"        : [],
                "variant_group" : null,
                "categories"    : [],
                "enabled"       : true,
                "values"        : [],
                "created"       : "2017-01-23T11:44:25+01:00",
                "updated"       : "2017-01-23T11:44:25+01:00",
                "associations"  : {}
            }
        ]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testOffsetPaginationListProductsWithChannelLocalesAndAttributesParams()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?scope=ecommerce&locales=en_US&attributes=size,a_text_area,a_number_integer&pagination_type=page');
        $expected = <<<JSON
{
  "_links": {
    "self": {
      "href": "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&scope=ecommerce&locales=en_US&attributes=size%2Ca_text_area%2Ca_number_integer"
    },
    "first": {
      "href": "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&scope=ecommerce&locales=en_US&attributes=size%2Ca_text_area%2Ca_number_integer"
    }
  },
  "current_page": 1,
  "_embedded": {
    "items": [
      {
        "_links": {
          "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_s"
          }
        },
        "identifier": "apollon_blue_s",
        "family": "familyA",
        "parent": "amor",
        "groups": [
          
        ],
        "variant_group": null,
        "categories": [
          "master"
        ],
        "enabled": true,
        "values": {
          "size": [
            {
              "locale": null,
              "scope": null,
              "data": "s"
            }
          ],
        "a_text_area": [
        {
          "locale": null,
          "scope": null,
          "data": "a super text area"
        }
        ],
        "a_number_integer": [
        {
          "locale": null,
          "scope": null,
          "data": 12
        }
        ]
        },
        "created": "2017-09-20T17:46:20+02:00",
        "updated": "2017-09-20T17:46:20+02:00",
        "associations": {
          
        }
      },
      {
        "_links": {
          "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_m"
          }
        },
        "identifier": "apollon_blue_m",
        "family": "familyA",
        "parent": "amor",
        "groups": [
          
        ],
        "variant_group": null,
        "categories": [
          "categoryA"
        ],
        "enabled": true,
        "values": {
        "a_text_area": [
        {
          "locale": null,
          "scope": null,
          "data": "a super text area"
        }
        ],
        "a_number_integer": [
        {
          "locale": null,
          "scope": null,
          "data": 12
        }
        ]
        },
        "created": "2017-09-20T17:46:21+02:00",
        "updated": "2017-09-20T17:46:21+02:00",
        "associations": {
          
        }
      },
      {
        "_links": {
          "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_l"
          }
        },
        "identifier": "apollon_blue_l",
        "family": "familyA",
        "parent": "amor",
        "groups": [
          
        ],
        "variant_group": null,
        "categories": [
          "categoryB",
          "categoryC"
        ],
        "enabled": true,
        "values": {
          "size": [
            {
              "locale": null,
              "scope": null,
              "data": "l"
            }
          ],
        "a_text_area": [
        {
          "locale": null,
          "scope": null,
          "data": "a super text area"
        }
        ],
        "a_number_integer": [
        {
          "locale": null,
          "scope": null,
          "data": 12
        }
        ]
        },
        "created": "2017-09-20T17:46:21+02:00",
        "updated": "2017-09-20T17:46:21+02:00",
        "associations": {
          
        }
      },
      {
        "_links": {
          "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_xl"
          }
        },
        "identifier": "apollon_blue_xl",
        "family": "familyA",
        "parent": "amor",
        "groups": [
          
        ],
        "variant_group": null,
        "categories": [
          "categoryA1",
          "categoryA2"
        ],
        "enabled": true,
        "values": {
          "size": [
            {
              "locale": null,
              "scope": null,
              "data": "xl"
            }
          ],
        "a_text_area": [
        {
          "locale": null,
          "scope": null,
          "data": "a super text area"
        }
        ],
        "a_number_integer": [
        {
          "locale": null,
          "scope": null,
          "data": 12
        }
        ]
        },
        "created": "2017-09-20T17:46:21+02:00",
        "updated": "2017-09-20T17:46:21+02:00",
        "associations": {
          
        }
      },
      {
        "_links": {
          "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_xxl"
          }
        },
        "identifier": "apollon_blue_xxl",
        "family": "familyA",
        "parent": "amor",
        "groups": [
          
        ],
        "variant_group": null,
        "categories": [
          "categoryA1"
        ],
        "enabled": true,
        "values": {
          "size": [
            {
              "locale": null,
              "scope": null,
              "data": "xxl"
            }
          ],
        "a_text_area": [
        {
          "locale": null,
          "scope": null,
          "data": "a super text area"
        }
        ],
        "a_number_integer": [
        {
          "locale": null,
          "scope": null,
          "data": 12
        }
        ]
        },
        "created": "2017-09-20T17:46:21+02:00",
        "updated": "2017-09-20T17:46:21+02:00",
        "associations": {
          
        }
      }
    ]
  }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testTheSecondPageOfTheListOfProductsWithOffsetPaginationWithoutCount()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?attributes=size&page=2&limit=2&pagination_type=page&with_count=false');
        $expected = <<<JSON
{
  "_links": {
    "self": {
      "href": "http://localhost/api/rest/v1/products?page=2&with_count=false&pagination_type=page&limit=2&attributes=size"
    },
    "first": {
      "href": "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=2&attributes=size"
    },
    "previous": {
      "href": "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=2&attributes=size"
    },
    "next": {
      "href": "http://localhost/api/rest/v1/products?page=3&with_count=false&pagination_type=page&limit=2&attributes=size"
    }
  },
  "current_page": 2,
  "_embedded": {
    "items": [
      {
        "_links": {
          "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_l"
          }
        },
        "identifier": "apollon_blue_l",
        "family": "familyA",
        "parent": "amor",
        "groups": [
          
        ],
        "variant_group": null,
        "categories": [
          "categoryB",
          "categoryC"
        ],
        "enabled": true,
        "values": {
          "size": [
            {
              "locale": null,
              "scope": null,
              "data": "l"
            }
          ]
        },
        "created": "2017-09-20T17:50:44+02:00",
        "updated": "2017-09-20T17:50:44+02:00",
        "associations": {
          
        }
      },
      {
        "_links": {
          "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_xl"
          }
        },
        "identifier": "apollon_blue_xl",
        "family": "familyA",
        "parent": "amor",
        "groups": [
          
        ],
        "variant_group": null,
        "categories": [
          "categoryA1",
          "categoryA2"
        ],
        "enabled": true,
        "values": {
          "size": [
            {
              "locale": null,
              "scope": null,
              "data": "xl"
            }
          ]
        },
        "created": "2017-09-20T17:50:44+02:00",
        "updated": "2017-09-20T17:50:44+02:00",
        "associations": {
          
        }
      }
    ]
  }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testOutOfRangeProductsList()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?page=2&pagination_type=page&with_count=true');
        $expected = <<<JSON
{
    "_links"       : {
        "self"        : {"href" : "http://localhost/api/rest/v1/products?page=2&with_count=true&pagination_type=page&limit=10"},
        "first"       : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=true&pagination_type=page&limit=10"},
        "previous"    : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=true&pagination_type=page&limit=10"}
    },
    "current_page" : 2,
    "items_count"  : 6,
    "_embedded"    : {
        "items" : []
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testOffsetPaginationListProductsWithSearch()
    {
        $client = $this->createAuthenticatedClient();

        $search = '{"size":[{"operator":"IN","value":["s"]}]}';
        $client->request('GET', 'api/rest/v1/products?pagination_type=page&search=' . $search);
        $searchEncoded = rawurlencode($search);
        $expected = <<<JSON
{
    "_links"       : {
        "self"  : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&search=${searchEncoded}"},
        "first" : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&search=${searchEncoded}"}
    },
    "current_page" : 1,
    "_embedded"    : {
        "items" : [
            {
                "_links" : {
                    "self" : {"href" : "http://localhost/api/rest/v1/products/apollon_blue_s"}
                },
                "identifier"    : "apollon_blue_s",
                "family"        : "familyA",
                "parent"        : "amor",
                "groups"        : [],
                "variant_group" : null,
                "categories"    : ["master"],
                "enabled"       : true,
                "values": {
                "size": [
                  {
                    "locale": null,
                    "scope": null,
                    "data": "s"
                  }
                ],
                "a_price": [
                {
                  "locale": null,
                  "scope": null,
                  "data": [
                    {
                      "amount": "50.00",
                      "currency": "EUR"
                    }
                  ]
                }
                ],
                "a_text_area": [
                {
                  "locale": null,
                  "scope": null,
                  "data": "a super text area"
                }
                ],
                "a_number_integer": [
                {
                  "locale": null,
                  "scope": null,
                  "data": 12
                }
                ]
                },
                "created"       : "2017-01-23T11:44:25+01:00",
                "updated"       : "2017-01-23T11:44:25+01:00",
                "associations"  : {}
            }
        ]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testOffsetPaginationListProductsWithMultiplePQBFilters()
    {
        $client = $this->createAuthenticatedClient();

        $search = '{"categories":[{"operator":"IN", "value":["categoryA"]}], "color":[{"operator":"IN","value":["black"]}]}';
        $client->request('GET', 'api/rest/v1/products?pagination_type=page&search=' . $search);
        $searchEncoded = rawurlencode($search);
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&search=${searchEncoded}"},
        "first" : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&search=${searchEncoded}"}
    },
    "current_page" : 1,
    "_embedded"    : {
        "items" : []
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testListProductsWithCompletenessPQBFilters()
    {
        $client = $this->createAuthenticatedClient();

        $search = '{"completeness":[{"operator":"GREATER THAN ON ALL LOCALES","value":50,"locales":["en_US"],"scope":"ecommerce"}],"categories":[{"operator":"IN", "value":["categoryA"]}], "size":[{"operator":"IN","value":["xl"]}]}';
        $client->request('GET', 'api/rest/v1/products?search=' . $search);
        $searchEncoded = rawurlencode($search);
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&search=${searchEncoded}"},
        "first" : {"href" : "http://localhost/api/rest/v1/products?page=1&with_count=false&pagination_type=page&limit=10&search=${searchEncoded}"}
    },
    "current_page" : 1,
    "_embedded"    : {
        "items" : []
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    /**
     * Get all products, whatever locale, scope, category with a search after pagination
     */
    public function testSearchAfterPaginationListProductsWithoutParameter()
    {
        $standardizedProducts = $this->getStandardizedProducts();
        $client = $this->createAuthenticatedClient();

        $client->request('GET', 'api/rest/v1/products?pagination_type=search_after');
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href": "http://localhost/api/rest/v1/products?pagination_type=search_after&limit=10"},
        "first" : {"href": "http://localhost/api/rest/v1/products?pagination_type=search_after&limit=10"}
    },
    "_embedded" : {
        "items" : [
            {$standardizedProducts['apollon_blue_s']},
            {$standardizedProducts['apollon_blue_m']},
            {$standardizedProducts['apollon_blue_l']},
            {$standardizedProducts['apollon_blue_xl']},
            {$standardizedProducts['apollon_blue_xxl']},
            {$standardizedProducts['apollon_blue_xs']}
        ]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testSearchAfterPaginationListProductsWithNextLink()
    {
        $standardizedProducts = $this->getStandardizedProducts();
        $client = $this->createAuthenticatedClient();

        $id = [
            'apollon_blue_s'  => rawurlencode($this->getEncryptedId('apollon_blue_s')),
            'apollon_blue_m'  => rawurlencode($this->getEncryptedId('apollon_blue_m')),
            'apollon_blue_xl' => rawurlencode($this->getEncryptedId('apollon_blue_xl')),
        ];

        $client->request('GET', sprintf('api/rest/v1/products?pagination_type=search_after&limit=3&search_after=%s', $id['apollon_blue_s']));
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href": "http://localhost/api/rest/v1/products?pagination_type=search_after&limit=3&search_after={$id['apollon_blue_s']}"},
        "first" : {"href": "http://localhost/api/rest/v1/products?pagination_type=search_after&limit=3"},
        "next"  : {"href": "http://localhost/api/rest/v1/products?pagination_type=search_after&limit=3&search_after={$id['apollon_blue_xl']}"}
    },
    "_embedded"    : {
        "items" : [
            {$standardizedProducts['apollon_blue_m']},
            {$standardizedProducts['apollon_blue_l']},
            {$standardizedProducts['apollon_blue_xl']}
        ]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    public function testSearchAfterPaginationLastPageOfTheListOfProducts()
    {
        $standardizedProducts = $this->getStandardizedProducts();
        $client = $this->createAuthenticatedClient();

        $encryptedId = rawurlencode($this->getEncryptedId('apollon_blue_l'));

        $client->request('GET', sprintf('api/rest/v1/products?pagination_type=search_after&limit=4&search_after=%s' , $encryptedId));
        $expected = <<<JSON
{
    "_links": {
        "self"  : {"href": "http://localhost/api/rest/v1/products?pagination_type=search_after&limit=4&search_after={$encryptedId}"},
        "first" : {"href": "http://localhost/api/rest/v1/products?pagination_type=search_after&limit=4"}
    },
    "_embedded"    : {
        "items" : [
            {$standardizedProducts['apollon_blue_xl']},
            {$standardizedProducts['apollon_blue_xxl']},
            {$standardizedProducts['apollon_blue_xs']}
        ]
    }
}
JSON;

        $this->assertListResponse($client->getResponse(), $expected);
    }

    /**
     * @param string $productIdentifier
     */
    private function getEncryptedId($productIdentifier)
    {
        $encrypter = $this->get('pim_api.security.primary_key_encrypter');
        $productRepository = $this->get('pim_catalog.repository.product');

        $product = $productRepository->findOneByIdentifier($productIdentifier);

        return $encrypter->encrypt($product->getId());
    }

    /**
     * @return array
     */
    private function getStandardizedProducts() {
        $standardizedProducts['apollon_blue_s'] = <<<JSON
{
    "_links": {
        "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_s"
        }
    },
  "identifier": "apollon_blue_s",
  "family": "familyA",
  "parent": "amor",
  "groups": [],
  "variant_group": null,
  "categories": [
    "master"
  ],
  "enabled": true,
  "values": {
    "size": [
      {
        "locale": null,
        "scope": null,
        "data": "s"
      }
    ],
    "a_price": [
    {
      "locale": null,
      "scope": null,
      "data": [
        {
          "amount": "50.00",
          "currency": "EUR"
        }
      ]
    }
    ],
    "a_text_area": [
    {
      "locale": null,
      "scope": null,
      "data": "a super text area"
    }
    ],
    "a_number_integer": [
    {
      "locale": null,
      "scope": null,
      "data": 12
    }
    ]
    },
  "created": "2017-09-20T15:37:40+02:00",
  "updated": "2017-09-20T15:37:40+02:00",
  "associations": {}
}
JSON;

        $standardizedProducts['apollon_blue_m'] = <<<JSON
{
    "_links": {
        "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_m"
        }
    },
  "identifier": "apollon_blue_m",
  "family": "familyA",
  "parent": "amor",
  "groups": [],
  "variant_group": null,
  "categories": [
    "categoryA"
  ],
  "enabled": true,
  "values": {
    "color": [
      {
        "locale": null,
        "scope": null,
        "data": "blue"
      }
    ],
    "a_price": [
    {
      "locale": null,
      "scope": null,
      "data": [
        {
          "amount": "50.00",
          "currency": "EUR"
        }
      ]
    }
    ],
    "a_text_area": [
    {
      "locale": null,
      "scope": null,
      "data": "a super text area"
    }
    ],
    "a_number_integer": [
    {
      "locale": null,
      "scope": null,
      "data": 12
    }
    ]
    },
  "created": "2017-09-20T15:37:40+02:00",
  "updated": "2017-09-20T15:37:40+02:00",
  "associations": {}
}
JSON;

        $standardizedProducts['apollon_blue_l'] = <<<JSON
{
    "_links": {
        "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_l"
        }
    },
  "identifier": "apollon_blue_l",
  "family": "familyA",
  "parent": "amor",
  "groups": [],
  "variant_group": null,
  "categories": [
    "categoryB",
    "categoryC"
  ],
  "enabled": true,
  "values": {
    "size": [
      {
        "locale": null,
        "scope": null,
        "data": "l"
      }
    ],
    "a_price": [
    {
      "locale": null,
      "scope": null,
      "data": [
        {
          "amount": "50.00",
          "currency": "EUR"
        }
      ]
    }
    ],
    "a_text_area": [
    {
      "locale": null,
      "scope": null,
      "data": "a super text area"
    }
    ],
    "a_number_integer": [
    {
      "locale": null,
      "scope": null,
      "data": 12
    }
    ]
    },
  "created": "2017-09-20T15:37:40+02:00",
  "updated": "2017-09-20T15:37:40+02:00",
  "associations": {}
}
JSON;

        $standardizedProducts['apollon_blue_xl'] = <<<JSON
{
    "_links": {
        "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_xl"
        }
    },
  "identifier": "apollon_blue_xl",
  "family": "familyA",
  "parent": "amor",
  "groups": [
    
  ],
  "variant_group": null,
  "categories": [
    "categoryA1",
    "categoryA2"
  ],
  "enabled": true,
  "values": {
    "size": [
      {
        "locale": null,
        "scope": null,
        "data": "xl"
      }
    ],
    "a_price": [
    {
      "locale": null,
      "scope": null,
      "data": [
        {
          "amount": "50.00",
          "currency": "EUR"
        }
      ]
    }
    ],
    "a_text_area": [
    {
      "locale": null,
      "scope": null,
      "data": "a super text area"
    }
    ],
    "a_number_integer": [
    {
      "locale": null,
      "scope": null,
      "data": 12
    }
    ]
    },
  "created": "2017-09-20T15:37:40+02:00",
  "updated": "2017-09-20T15:37:40+02:00",
  "associations": {
    
  }
}
JSON;

        $standardizedProducts['apollon_blue_xxl'] = <<<JSON
{
   "_links": {
       "self": {
           "href": "http://localhost/api/rest/v1/products/apollon_blue_xxl"
       }
   },
  "identifier": "apollon_blue_xxl",
  "family": "familyA",
  "parent": "amor",
  "groups": [
    
  ],
  "variant_group": null,
  "categories": [
    "categoryA1"
  ],
  "enabled": true,
  "values": {
    "size": [
      {
        "locale": null,
        "scope": null,
        "data": "xxl"
      }
    ],
    "a_price": [
    {
      "locale": null,
      "scope": null,
      "data": [
        {
          "amount": "50.00",
          "currency": "EUR"
        }
      ]
    }
    ],
    "a_text_area": [
    {
      "locale": null,
      "scope": null,
      "data": "a super text area"
    }
    ],
    "a_number_integer": [
    {
      "locale": null,
      "scope": null,
      "data": 12
    }
    ]
    },
  "created": "2017-09-20T15:37:40+02:00",
  "updated": "2017-09-20T15:37:40+02:00",
  "associations": {
    
  }
}
JSON;

        $standardizedProducts['apollon_blue_xs'] = <<<JSON
{
    "_links": {
        "self": {
            "href": "http://localhost/api/rest/v1/products/apollon_blue_xs"
        }
    },
    "identifier": "apollon_blue_xs",
      "family": "familyA",
      "parent": "amor",
      "groups": [
        
      ],
      "variant_group": null,
      "categories": [
        
      ],
      "enabled": true,
      "values": {
        "size": [
          {
            "locale": null,
            "scope": null,
            "data": "xs"
          }
        ],
        "a_price": [
        {
          "locale": null,
          "scope": null,
          "data": [
            {
              "amount": "50.00",
              "currency": "EUR"
            }
          ]
        }
        ],
        "a_text_area": [
        {
          "locale": null,
          "scope": null,
          "data": "a super text area"
        }
        ],
        "a_number_integer": [
        {
          "locale": null,
          "scope": null,
          "data": 12
        }
        ]
        },
      "created": "2017-09-20T15:37:40+02:00",
      "updated": "2017-09-20T15:37:40+02:00",
      "associations": {
        
      }
    }
JSON;

        return $standardizedProducts;
    }

    /**
     * @return Configuration
     */
    protected function getConfiguration()
    {
        return new Configuration([Configuration::getTechnicalCatalogPath()]);
    }
}
