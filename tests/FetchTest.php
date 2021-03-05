<?php

namespace Spartan\Rest\Test;

use PHPUnit\Framework\TestCase;
use Spartan\Db\Adapter\Propel\Propel2;
use Spartan\Rest\Domain\Resource\Publisher;

class FetchTest extends TestCase
{
    public function setup(): void
    {
        \Spartan\Db\Adapter\Propel\Propel2::connect();
    }

    public function testComplexFetch()
    {
        $payload = <<<JSON
{
	"attr": ["id", "name", "country.name"],
	"args": {
		"_paginate": [1, 1]
	},
	"books": {
		"attr": ["title"],
		"args": {
			"release_year": 2014
		},
		"author_books": {
		    "author": {
		        "attr": ["name"]
		    }
		}
	}
}
JSON;

        $response = <<<JSON
  [
    {
      "id": 1,
      "name": "No Starch Press",
      "country": {
        "name": "United States"
      },
      "books": [
        {
          "title": "Eloquent JavaScript, Second Edition",
          "author_books": [
            {
              "author": {
                "name": "Marijn Haverbeke"
              }
            }
          ]
        }
      ]
    }
  ]
JSON;

        $this->assertSame(
            json_decode($response, true),
            (new Publisher())->search(json_decode($payload, true))
        );
    }
}
