<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;
use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\FunctionHandlerStaticFacadeTrait;

/**
 * @param $params
 *               - fieldName: name attribute for the input element
 *               - data: array of items to display in the list. Format: [value => label, ...]
 *               - defaultSelected: array of items to be selected by default. Format: [value, ...]
 *               - sourceListTitle: title of the source list
 *               - targetListTitle: title of the target list
 *               - filterable: boolean. Whether or not to enable filtering
 *               - filterPlaceholder: placeholder text for the filter input
 *               - ordering: boolean. Whether or not to enable ordering of items via drag and drop
 *               - cardinalityParam: string representation of the validation parameter applicable to the cardinality of the selectable items
 *               - validationMessage: the validation message to display
 *
 * @param $smarty
 *
 * @return string
 * @throws Exception
 */
class JsTransferList extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'jstransfer_list';
    }

    public function handle($params, Template $template)
    {
        $defaultParams = [
            'filterPlaceholder' => tr('Enter keyword'),
            'sourceListTitle' => tr('List'),
            'targetListTitle' => tr('Selected'),
            'defaultSelected' => [],
        ];
        $params = array_merge($defaultParams, $params);
        $params['defaultSelected'] = array_map('strval', $params['defaultSelected']);
        $language = TikiLib::lib('tiki')->get_language();
        $id = uniqid('transfer-list-');

        $minItems = '';
        $maxItems = '';

        if ($params['cardinalityParam']) {
            parse_str($params['cardinalityParam'], $cardinality);
            $minItems = $cardinality['minimum'] ?? '';
            $maxItems = $cardinality['maximum'] ?? '';
        }

        $headerlib = TikiLib::lib('header');
        $headerlib->add_js_module("import '@vue-widgets/el-transfer';");

        $headerlib->add_js_module(<<<JS
            import('@tiki/ui-utils').then(({ handleTransferList }) => {
                handleTransferList('{$id}', '{$params['fieldName']}');
            });
        JS);

        return "
        <el-transfer language=" . json_encode($language) . " data='" . json_encode($params["data"]) . "' id='{$id}' field-name='{$params['fieldName']}' filterable=" . json_encode((bool) $params["filterable"]) . " default-value='" . json_encode($params["defaultSelected"]) . "' source-list-title=" . json_encode(tr($params["sourceListTitle"])) . " target-list-title=" . json_encode(tr($params["targetListTitle"])) . " filter-placeholder=" . json_encode(tr($params["filterPlaceholder"])) . " ordering=" . json_encode((bool) $params["ordering"]) . " min-items='$minItems' max-items='$maxItems' helper-text='{$params['validationMessage']}' show-edit='{$params['showEdit']}'>
        </el-transfer>";
    }
}
