<?php

return [
    "1" => [
        "categId" => 1,
        "name" => "Test Category One",
        "description" => "Description One",
        "parentId" => 0,
        "rootId" => 0,
        "hits" => 0,
        "tplGroupContainerId" => 0,
        "tplGroupPattern" => "",
        "num_roles" => 0,
        "objects" => 0,
        "children" => [],
        "descendants" => [],
        "tepath" => [
            "1" => "Test Category One"
        ],
        "categpath" => "Test Category One",
        "relativePathString" => "Test Category One"
    ],
    "2" => [
        "categId" => 2,
        "name" => "Test Category Two",
        "description" => "Description Two",
        "parentId" => 0,
        "rootId" => 0,
        "hits" => 0,
        "tplGroupContainerId" => 0,
        "tplGroupPattern" => "",
        "num_roles" => 0,
        "objects" => 0,
        "children" => [
            3
        ],
        "descendants" => [
            3,
            4,
            5
        ],
        "tepath" => [
            "2" => "Test Category Two"
        ],
        "categpath" => "Test Category Two",
        "relativePathString" => "Test Category Two"
    ],
    "3" => [
        "categId" => 3,
        "name" => "Test Category Three",
        "description" => "Description Three",
        "parentId" => 2,
        "rootId" => 2,
        "hits" => 0,
        "tplGroupContainerId" => 0,
        "tplGroupPattern" => "",
        "num_roles" => 0,
        "objects" => 0,
        "children" => [
            4
        ],
        "descendants" => [
            4,
            5
        ],
        "tepath" => [
            "2" => "Test Category Two",
            "3" => "Test Category Three"
        ],
        "categpath" => "Test Category Two::Test Category Three",
        "relativePathString" => "Test Category Two::Test Category Three"
    ],
    "4" => [
        "categId" => 4,
        "name" => "Test Category Four",
        "description" => "Description Four",
        "parentId" => 3,
        "rootId" => 2,
        "hits" => 0,
        "tplGroupContainerId" => 0,
        "tplGroupPattern" => "",
        "num_roles" => 0,
        "objects" => 0,
        "children" => [
            5
        ],
        "descendants" => [
            5
        ],
        "tepath" => [
            "2" => "Test Category Two",
            "3" => "Test Category Three",
            "4" => "Test Category Four"
        ],
        "categpath" => "Test Category Two::Test Category Three::Test Category Four",
        "relativePathString" => "Test Category Two::Test Category Three::Test Category Four"
    ],
    "5" => [
        "categId" => 5,
        "name" => "Test Category Five",
        "description" => "Description Five",
        "parentId" => 4,
        "rootId" => 2,
        "hits" => 0,
        "tplGroupContainerId" => 0,
        "tplGroupPattern" => "",
        "num_roles" => 0,
        "objects" => 0,
        "children" => [],
        "descendants" => [],
        "tepath" => [
            "2" => "Test Category Two",
            "3" => "Test Category Three",
            "4" => "Test Category Four",
            "5" => "Test Category Five"
        ],
        "categpath" => "Test Category Two::Test Category Three::Test Category Four::Test Category Five",
        "relativePathString" => "Test Category Two::Test Category Three::Test Category Four::Test Category Five"
    ]
];
