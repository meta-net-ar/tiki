<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Payment_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('payment_feature', 'wikiplugin_addtocart');
    }

    public function action_addtocart($input)
    {
        $cartlib = TikiLib::lib('cart');

        $params = $input->asArray('params');

        return $cartlib->add_to_cart($params, $input);
    }

    public function action_addalltocart($input)
    {
        $cartlib = TikiLib::lib('cart');

        $items = $input->asArray('items');
        $ret = [];

        foreach ($items as $item) {
            $ret[] = $cartlib->add_to_cart($item['params'], new jitFilter($item));
        }

        return $ret;
    }

    public function action_capture($input)
    {
        $perms = Perms::get();
        if (! $perms->payment_admin) {
            throw new Services_Exception_Denied(tr('Reserved for payment administrators'));
        }

        $next_url = parse_url($input->next->utl());

        if (! isset($next_url['host'])) {
            throw new Services_Exception_Denied(tr('Redirection URL is not valid'));
        }

        if ($next_url['host'] !== $_SERVER['HTTP_HOST']) {
            throw new Services_Exception_Denied(tr('Redirection URL not allowed'));
        }

        $paymentlib = TikiLib::lib('payment');
        $paymentlib->capture_payment($input->paymentId->int());

        $access = TikiLib::lib('access');
        $access->redirect($input->next->url());
    }
}
