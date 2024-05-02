<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Search\ContentSource;

use Search_ContentSource_Interface;
use Search_Type_Factory_Interface;
use TikiDb;
use TikiLib;

class CreditSource implements Search_ContentSource_Interface
{
    private $table;

    public function __construct()
    {
        $this->table = TikiDb::get()->table('tiki_credits');
    }

    public function getDocuments()
    {
        return $this->table->fetchColumn('creditId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $credit_types = TikiLib::lib('credits')->getCreditTypes();

        $credit = $this->table->fetchRow(['userId', 'credit_type', 'creation_date', 'expiration_date', 'total_amount', 'used_amount', 'product_id', 'goalId'], [
            'creditId' => $objectId,
        ]);

        if ($credit) {
            $credit_user = TikiLib::lib('user')->get_userid_info($credit['userId']);
            $credit_type = $credit_types[$credit['credit_type']] ?? [
                'display_text' => $credit['credit_type'],
                'unit_text' => '',
                'is_static_level' => 'n',
                'scaling_divisor' => 1,
            ];
            return [
                'title' => $typeFactory->sortable($credit_type['display_text'] . ' ' . $credit_user['login']),
                'creation_date' => $typeFactory->timestamp($credit['creation_date'] ? strtotime($credit['creation_date'] . ' UTC') : null),
                'expiration_date' => $typeFactory->timestamp($credit['expiration_date'] ? strtotime($credit['expiration_date'] . ' UTC') : null),
                'contributors' => $typeFactory->multivalue([$credit_user['login']]),
                'user' => $typeFactory->identifier($credit_user['login']),
                'credit_type' => $typeFactory->identifier($credit['credit_type']),
                'credit_type_text' => $typeFactory->sortable($credit_type['display_text']),
                'credit_type_unit' => $typeFactory->sortable($credit_type['unit_text']),
                'credit_type_static_level' => $typeFactory->identifier($credit_type['is_static_level']),
                'credit_type_scaling_divisor' => $typeFactory->numeric($credit_type['scaling_divisor']),
                'total_amount' => $typeFactory->numeric($credit['total_amount']),
                'used_amount' => $typeFactory->numeric($credit['used_amount']),
                'product_id' => $typeFactory->numeric($credit['product_id']),
                'goal_id' => $typeFactory->numeric($credit['goalId']),
                'allowed_groups' => $typeFactory->multivalue(['Anonymous', 'Registered']),
            ];
        } else {
            return false;
        }
    }

    public function getProvidedFields(): array
    {
        return [
            'title', 'creation_date', 'expiration_date', 'contributors', 'user', 'credit_type',
            'credit_type_text', 'credit_type_unit', 'credit_type_static_level', 'credit_type_scaling_divisor',
            'total_amonut', 'used_amount', 'product_id', 'goal_id'
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'creation_date' => 'timestamp',
            'expiration_date' => 'timestamp',
            'contributors' => 'multivalue',
            'user' => 'identifier',
            'credit_type' => 'identifier',
            'credit_type_text' => 'sortable',
            'credit_type_unit' => 'sortable',
            'credit_type_static_level' => 'identifier',
            'credit_type_scaling_divisor' => 'numeric',
            'total_amount' => 'numeric',
            'used_amount' => 'numeric',
            'product_id' => 'numeric',
            'goal_id' => 'numeric',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'creation_date' => true,
            'user' => true,
        ];
    }
}
