<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\Auth\Tokens;

class CartLib
{
    private \TikiLib $tikilib;
    private \TrackerLib $trklib;
    private \UsersLib $userslib;
    private \PaymentLib $paymentlib;
    private \Services_Tracker_Utilities $utilities;

    private int $productTracker;
    private int $productInventoryTypeField;
    private int $productInventoryTotalField;
    private int $productInventoryLessHoldField;

    private int $orderTracker;
    private int $orderUserField;
    private int $orderDateField;
    private int $orderTotalField;
    private int $orderInvoiceField;
    private int $orderWeightField;

    private int $orderitemsTracker;
    private int $orderitemsOrderField;
    private int $orderitemsProductField;
    private int $orderitemsPriceField;
    private int $orderitemsQuantityField;
    private int $orderitemsUserField;
    private int $orderitemsInputtedPriceField;
    private int $orderitemsParentCodeField;


    private bool $inventoryEnabled;
    private bool $bundlesEnabled;

    final public function __construct()
    {
        global $prefs;

        $this->tikilib = TikiLib::lib('tiki');
        $this->userslib = TikiLib::lib('user');
        /** @var TrackerLib trklib */
        $this->trklib  = TikiLib::lib('trk');
        /** @var PaymentLib paymentlib */
        $this->paymentlib = TikiLib::lib('payment');

        $this->utilities = new \Services_Tracker_Utilities();

        $this->productTracker = (int)$prefs['payment_cart_product_tracker'] ?? 0;
        $this->productInventoryTypeField = (int)$prefs['payment_cart_inventory_type_field'] ?? 0;
        $this->productInventoryTotalField = (int)$prefs['payment_cart_inventory_total_field'] ?? 0;
        $this->productInventoryLessHoldField = (int)$prefs['payment_cart_inventory_lesshold_field'] ?? 0;

        $this->orderTracker = (int)$prefs['payment_cart_order_tracker'] ?? 0;
        $this->orderUserField = (int)$prefs['payment_cart_order_user_field'] ?? 0;
        $this->orderDateField = (int)$prefs['payment_cart_order_date_field'] ?? 0;
        $this->orderTotalField = (int)$prefs['payment_cart_order_total_field'] ?? 0;
        $this->orderInvoiceField = (int)$prefs['payment_cart_order_invoice_field'] ?? 0;
        $this->orderWeightField = (int)$prefs['payment_cart_order_weight_field'] ?? 0;

        $this->orderitemsTracker = (int)$prefs['payment_cart_orderitems_tracker'] ?? 0;

        $this->orderitemsOrderField = (int)$prefs['payment_cart_orderitems_order_field'] ?? 0;
        $this->orderitemsProductField = (int)$prefs['payment_cart_orderitems_product_field'] ?? 0;
        $this->orderitemsPriceField = (int)$prefs['payment_cart_orderitems_price_field'] ?? 0;
        $this->orderitemsQuantityField = (int)$prefs['payment_cart_orderitems_quantity_field'] ?? 0;
        $this->orderitemsUserField = (int)$prefs['payment_cart_orderitems_user_field'] ?? 0;
        $this->orderitemsInputtedPriceField = (int)$prefs['payment_cart_orderitems_inputedprice_field'] ?? 0;
        $this->orderitemsParentCodeField = (int)$prefs['payment_cart_orderitems_parentcode_field'] ?? 0;

        $this->inventoryEnabled = $prefs['payment_cart_inventory'] === 'y';
        $this->bundlesEnabled   = $prefs['payment_cart_bundles'] === 'y';
    }


    /**
     * Called when addtocart button clicked. Adds a quantity of a specified product to the cart
     *
     * @param array     $product_info wikiplugin_addtocart params
     * @param jitFilter $input        request input (POST)
     *
     * @return bool                   success
     */
    final public function add_to_cart(array $product_info, jitFilter $input): bool
    {
        global $prefs, $user, $globalperms;

        $quantity = $input->quantity->int();

        if ($input->code->text() !== $product_info['code']) {
            Feedback::error(tra('Cart: Product code mismatch.'));
            return false;
        }

        if (! empty($params['exchangeorderitemid']) && ! empty($params['exchangetoproductid'])) {
            if (
                $input->exchangeorderitemid->int() !== $params['exchangeorderitemid'] ||
                    $input->exchangetoproductid->int() !== $params['exchangetoproductid']
            ) {
                Feedback::error(tra('Cart: Product exchange mismatch.'));
                return false;
            }
        }

        if ($prefs['payment_cart_anonymous'] === 'y' && (! $user || $product_info['forceanon'] == 'y') && empty($_SESSION['shopperinfo'])) {
            // There needs to be a shopperinfo plugin on the page
            Feedback::error(tr('Please enter your shopper information first'));
            return false;
        }

        if ($globalperms->payment_admin && $input->buyonbehalf->text() && $this->userslib->user_exists($input->buyonbehalf->text())) {
            $product_info['onbehalf'] = $input->buyonbehalf->text();
        } else {
            $product_info['onbehalf'] = '';
        }

        // Generate behavior for exchanges
        if (! empty($product_info['exchangeorderitemid']) && ! empty($product_info['exchangetoproductid'])) {
            $product_info['behaviors'][] = [
                'event' => 'complete',
                'behavior' => 'cart_exchange_product',
                'arguments' => [$product_info["exchangeorderitemid"], $product_info["exchangetoproductid"]]
            ];
            if (! isset($product_info['exchangeorderamount']) || ! $product_info['exchangeorderamount']) {
                $product_info['exchangeorderamount'] = 1;
            }
        }

        // Now add product to cart
        return $this->add_product($product_info['code'], $quantity, $product_info);
    }

    //Used for putting new items in the cart, to modify an already existing item in the cart, use update_quantity
    final public function add_product(int $code, int $quantity, array $info, int $parentCode = 0, int $childInputedPrice = 0): bool
    {
        $this->init_cart();

        $current = $this->get_quantity($code);

        if ($parentCode) {
            $this->init_product($code, $info, $parentCode, $quantity, $childInputedPrice);
            if ($this->inventoryEnabled) {
                $currentInventory = $this->get_inventory($code);
                if ($currentInventory < $quantity) {
                    // Abort entire bundle if one of the child products is out of stock
                    $this->update_quantity($parentCode, 0, $info);
                }
            }
            return false;
        } elseif (! $current) {
            $this->init_product($code, $info, $parentCode);
        }

        $current += $quantity;

        $this->add_bundle($code, $quantity, $info);

        $this->update_quantity($code, $current, $info);
        return true;
    }

    final public function get_product_info(int $code): array
    {
        // This function is used by several advanced cart features (e.g. bundled products, associated events)
        global $prefs;
        if (empty($prefs['payment_cart_product_tracker_name'])) {
            return [];
        }

        $array = $this->trklib->get_tracker_item($code);

        $info = [];
        $info['code'] = $code;
        while ($result = current($array)) {
            $key = key($array);
            switch ($key) {
                case $prefs['payment_cart_product_name_fieldname']:
                    $key = "description";
                    break;
                case $prefs['payment_cart_associated_event_fieldname']:
                    $key = "eventcode";
                    break;
                case $prefs['payment_cart_product_classid_fieldname']:
                    $key = "productclass";
                    break;
                case $prefs['payment_cart_products_inbundle_fieldname']:
                    $key = "productsinbundle";
                    break;
                case $prefs['payment_cart_product_price_fieldname']:
                    $key = "price";
                    break;
            }
            $info[$key] = $result;
            next($array);
        }
        return $info;
    }

    final public function add_bundle(int $code, int $quantity, array $info): void
    {
        if (! $this->bundlesEnabled) {
            return;
        }
        $moreInfo = $this->get_product_info($code);
        if (! empty($moreInfo['productsinbundle'])) {
            $products = explode(",", $moreInfo['productsinbundle']);
            foreach ($products as $product) {
                $p = explode(":", $product);
                if (count($p) == 1) {
                    $p[1] = 1; // quantity
                    $p[2] = ''; // inputted price
                } elseif (count($p) == 2) {
                    $p[2] = ''; // inputted price
                }
                list($productId, $productQuantity, $childInputedPrice) = $p;
                if (is_numeric($productId)) {
                    $infoProduct = $this->get_product_info($productId);
                    if ($childInputedPrice == '') {
                        $childInputedPrice = $info['price'] / count($products) / $productQuantity;
                        // default evenly split between products in the bundle (regardless of individual quantities)
                    }
                    $infoProduct['price'] = 0;
                    if (! empty($info['onbehalf'])) {
                        $infoProduct['onbehalf'] = $info['onbehalf'];
                    }
                    $this->add_product($productId, $productQuantity, $infoProduct, $code, $childInputedPrice);
                }
            }
        }
    }

    final public function get_tracker_value_custom(string $trackerName, string $fieldName, ?int $itemId): ?string
    {
        $value = $this->tikilib->getOne(
            "SELECT tiki_tracker_item_fields.value
            FROM tiki_tracker_item_fields
            LEFT JOIN tiki_tracker_fields ON tiki_tracker_fields.fieldId = tiki_tracker_item_fields.fieldId
            LEFT JOIN tiki_trackers ON tiki_trackers.trackerId = tiki_tracker_fields.trackerId
            LEFT JOIN tiki_tracker_items ON tiki_tracker_items.itemId = tiki_tracker_item_fields.itemId
            WHERE tiki_trackers.name = ? AND
            tiki_tracker_fields.name = ? AND
            tiki_tracker_item_fields.itemId = ?",
            [$trackerName, $fieldName, $itemId]
        );

        return $value;
    }

    final public function get_total(): float
    {
        $this->init_cart();

        $total = 0.0;

        foreach ($_SESSION['cart'] as $info) {
            $total += (float) $info['quantity'] * (float) $info['price'];
        }
        if ($total < 0.0) {
            $total = 0.0;
        }

        return $total;
    }

    final public function get_total_padded(): string
    {
        return number_format($this->get_total(), 2, '.', '');
    }

    final public function get_quantity(int $code): int
    {
        $this->init_cart();

        if (isset($_SESSION['cart'][ $code ])) {
            return $_SESSION['cart'][ $code ]['quantity'];
        } else {
            return 0;
        }
    }

    final public function get_hash(int $code): string
    {
        $this->init_cart();

        if (isset($_SESSION['cart'][ $code ])) {
            return $_SESSION['cart'][ $code ]['hash'];
        } else {
            return '';
        }
    }

    final public function generate_item_description(array $item, int $parentCode = 0): string
    {
        $wiki = '';

        if ($item['href']) {
            $label = "[{$item['href']}|{$item['description']}]";
        } else {
            $label = $item['description'];
        }
        if (! empty($item['onbehalf'])) {
            $label .= " " . tra('for') . " " . $item['onbehalf'];
        }
        if ($parentCode) {
            $label = tra('Bundled Product') . ' - ' . $label;
            if ($item['quantity'] > 1) {
                $label .= ' (x' . $item['quantity'] . ')';
            }
            $item['quantity'] = ' ';
            $item['price'] = ' ';
        }
        $wiki .= "{$item['code']}|{$label}|{$item['quantity']}|{$item['price']}\n";
        return $wiki;
    }

    final public function get_description(): string
    {
        $id_label = tra('ID');
        $product_label = tra('Product');
        $quantity_label = tra('Quantity');
        $price_label = tra('Unit Price');

        $wiki = "||__{$id_label}__|__{$product_label}__|__{$quantity_label}__|__{$price_label}__\n";

        foreach ($this->get_content() as $item) {
            $wiki .= $this->generate_item_description($item);
            if ($bundledProducts = $this->get_bundled_products($item['code'])) {
                foreach ($bundledProducts as $b) {
                    $wiki .= $this->generate_item_description($b, $item['code']);
                }
            }
        }

        $wiki .= "||\n";

        return $wiki;
    }

    final public function get_total_weight(): int
    {
        $this->init_cart();

        $total = 0;

        foreach ($_SESSION['cart'] as $info) {
            if (! empty($info['weight'])) {
                $total += (int)$info['quantity'] * (float)$info['weight'];
            }
        }

        return $total;
    }

    final public function get_count(): int
    {
        $this->init_cart();

        $total = 0;

        foreach ($_SESSION['cart'] as $info) {
            if (! empty($info['quantity'])) {
                $total += (int)$info['quantity'];
            }
        }

        return $total;
    }

    final public function product_in_cart(int $code): bool
    {
        return isset($_SESSION['cart'][$code]);
    }

     //Used for adjusting already added items in the cart
    final public function update_quantity(int $code, int $quantity, array $info = ['exchangetoproductid' => 0, 'exchangeorderamount' => 0]): void
    {
        $currentQuantity = $this->get_quantity($code);
        if ($this->inventoryEnabled) {
            // Prevent going below 0 inventory
            $currentInventory = $this->get_inventory($code);
            if ($quantity - $currentQuantity > $currentInventory) {
                if ($currentQuantity == 0) {
                    unset($_SESSION['cart'][ $code ]);
                }

                Feedback::error(tra('There is insufficient inventory to meet your request'));
            }

            if ($currentQuantity > 0) {
                if ($this->unhold_inventory($code, $currentQuantity)) {
                    $this->remove_from_onhold_list($code);
                }
                if ($info['exchangetoproductid'] && $info['exchangeorderamount']) {
                    if ($this->unhold_inventory($info['exchangetoproductid'], $info['exchangeorderamount'])) {
                        $this->remove_from_onhold_list('XC' . $info['exchangetoproductid']);
                    }
                }
            }
            if ($quantity > 0) {
                $currentInventory = $this->get_inventory($code);
                if ($quantity > $currentInventory) {
                    $quantity = $currentInventory;
                }
                if ($this->hold_inventory($code, $quantity)) {
                    $this->add_to_onhold_list($code, $quantity);
                }

                if ($info['exchangetoproductid'] && $info['exchangeorderamount']) {
                    if ($this->hold_inventory($info['exchangetoproductid'], $info['exchangeorderamount'])) {
                                        $this->add_to_onhold_list('XC' . $info['exchangetoproductid'], $info['exchangeorderamount']);
                    }
                }
            }
        }
        $this->init_cart();

        if (isset($_SESSION['cart'][ $code ]) && $quantity != 0) {
            $_SESSION['cart'][ $code ]['quantity'] = abs($quantity);
        } else {
            unset($_SESSION['cart'][ $code ]);
        }
    }

    /**
     * Called from modules/mod-func-cart.php on post
     * @return int
     * @throws Exception
     */
    final public function requestPayment(): int
    {
        global $prefs, $user;

        $total = $this->get_total();

        if ($total > 0) {
            // if anonymous shopping to set pref as to which shopperinfo to show in description
            if (empty($user) && $prefs['payment_cart_anonymous'] === 'y') {
                $shopperinfo_descvar = 'email'; // TODO: make this a pref
                if (! empty($_SESSION['shopperinfo'][$shopperinfo_descvar])) {
                    $shopperinfo_desc = $_SESSION['shopperinfo'][$shopperinfo_descvar];
                    $description = tra($prefs['payment_cart_heading']) . " ($shopperinfo_desc)";
                } else {
                    $description = tra($prefs['payment_cart_heading']);
                }
            } else {
                $description = tra($prefs['payment_cart_heading']) . " ($user)";
            }
            $invoice = $this->paymentlib->request_payment($description, $total, $prefs['payment_default_delay'], $this->get_description());
            foreach ($this->get_behaviors() as $behavior) {
                $this->paymentlib->register_behavior($invoice, $behavior['event'], $behavior['behavior'], $behavior['arguments']);
            }
        } else {
            $invoice = 0;
            foreach ($this->get_behaviors() as $behavior) {
                if ($behavior['event'] == 'complete') {
                    $name = $behavior['behavior'];
                    $file = __DIR__ . "/behavior/$name.php";
                    $function = 'payment_behavior_' . $name;
                    require_once $file;
                    call_user_func_array($function, $behavior['arguments']);
                }
            }
        }

        if ($prefs['payment_system'] == 'ilp') {
            $ilpinvoicepayment = TikiLib::lib('ilpinvoicepayment');
            if ($ilpinvoicepayment->isEnabled() && isset($invoice)) {
                $ilpinvoicepayment->createInvoice($invoice, $user, $total);
            }
        }

        // Handle anonymous user (not logged in) shopping that require only email
        if (! $user || isset($_SESSION['forceanon']) && $_SESSION['forceanon'] == 'y') {
            if (! empty($_SESSION['shopperinfo'])) { // should also check for pref that this anonymous shopping feature is on
                // First create shopper info in shopper tracker
                global $record_profile_items_created;
                $record_profile_items_created = [];
                if (! empty($_SESSION['shopperinfoprofile'])) {
                    $shopper_profile_name = $_SESSION['shopperinfoprofile'];
                } else {
                    $shopper_profile_name = $prefs['payment_cart_anonshopper_profile'];
                }
                $shopperprofile = Tiki_Profile::fromDb($shopper_profile_name);
                $profileinstaller = new Tiki_Profile_Installer();
                $profileinstaller->forget($shopperprofile); // profile can be installed multiple times
                $profileinstaller->setUserData($_SESSION['shopperinfo']);
                $profileinstaller->install($shopperprofile);
                // Then set user to shopper ID
                $cartuser = $record_profile_items_created[0];
                $record_profile_items_created = [];
            } else {
                $this->empty_cart();
                return $invoice;
            }
        } else {
            $cartuser = $user;
        }

        $orderId = 0;

        if ($user && $prefs['payment_cart_orders'] == 'y' || ! $user && $prefs['payment_cart_anonymous'] == 'y') {
            $definition = Tracker_Definition::get($this->orderTracker);
            $orderId = $this->utilities->insertItem($definition, [
                'status' => 'p',
                'fields' => [
                    $this->orderUserField       => $cartuser,
                    $this->orderDateField       => $this->tikilib->now,
                    $this->orderTotalField      => $total,
                    $this->orderInvoiceField      => $invoice,
                    $this->orderWeightField      => $this->get_total_weight(),
                ],
            ]);
        }

        $content = $this->get_content();

        foreach ($content as $info) {
            $process_info = $this->processItem($invoice, $total, $info, $cartuser, $orderId);
        }
        $email_template_ids = [];

        if (isset($process_info['product_classes']) && is_array($process_info['product_classes'])) {
            $product_classes = array_unique($process_info['product_classes']);
        } else {
            $product_classes = [];
        }

        foreach ($product_classes as $pc) {
            if ($email_template_id = $this->get_tracker_value_custom($prefs['payment_cart_productclasses_tracker_name'], 'Email Template ID', $pc)) {
                $email_template_ids[] = $email_template_id;
            }
        }
        if (! empty($record_profile_items_created)) {
            if ($total > 0) {
                $this->paymentlib->register_behavior($invoice, 'complete', 'record_cart_order', [ $record_profile_items_created ]);
                $this->paymentlib->register_behavior($invoice, 'cancel', 'cancel_cart_order', [ $record_profile_items_created ]);
                if ($user) {
                    $this->paymentlib->register_behavior($invoice, 'complete', 'cart_send_confirm_email', [ $user, $email_template_ids ]);
                }
            } else {
                require_once('lib/payment/behavior/record_cart_order.php');
                payment_behavior_record_cart_order($record_profile_items_created);
                if ($user) {
                    require_once('lib/payment/behavior/cart_send_confirm_email.php');
                    payment_behavior_cart_send_confirm_email($user, $email_template_ids);
                }
            }
        }

        if (! $user || isset($_SESSION['forceanon']) && $_SESSION['forceanon'] == 'y') {
            $shopperurl = 'tiki-index.php?page=' . $prefs['payment_cart_anon_reviewpage'] . '&shopper=' . (int)$cartuser;
            global $tikiroot, $prefs;
            $shopperurl = $this->tikilib->httpPrefix(true) . $tikiroot . $shopperurl;
            $tokenlib = Tokens::build($prefs);
            $shopperurl = $tokenlib->includeToken($shopperurl, [$prefs['payment_cart_anon_group'], 'Anonymous']);

            if (! empty($_SESSION['shopperinfo']['email'])) {
                require_once('lib/webmail/tikimaillib.php');
                $smarty = TikiLib::lib('smarty');
                $smarty->assign('shopperurl', $shopperurl);
                $smarty->assign('email_template_ids', $email_template_ids);
                $mail_subject = $smarty->fetch('mail/cart_order_received_anon_subject.tpl');
                $mail_data = $smarty->fetch('mail/cart_order_received_anon.tpl');
                $mail = new TikiMail();
                $mail->setSubject($mail_subject);
                if ($mail_data == strip_tags($mail_data)) {
                    $mail->setText($mail_data);
                } else {
                    $mail->setHtml($mail_data);
                }
                $mail->send($_SESSION['shopperinfo']['email']); // the field to use probably needs to be configurable as well
            }
        }

        $this->empty_cart();
        return $invoice;
    }

    final public function processItem(
        int $invoice,
        float $total,
        array $info,
        string $cartuser,
        int $orderId = 0,
        int $parentQuantity = 0,
        int $parentCode = 0
    ): array {
        global $user, $prefs, $record_profile_items_created;

        if ($bundledProducts = $this->get_bundled_products($info['code'])) {
            foreach ($bundledProducts as $i) {
                $this->processItem($invoice, $total, $i, $cartuser, $orderId, $info['quantity'], $info['code']);
            }
        }
        if ($parentQuantity) {
            $info['quantity'] = $info['quantity'] * $parentQuantity;
        }
        $product_classes = [];
        if (isset($info['productclass']) && $info['productclass']) {
            $product_classes[] = $info['productclass'];
        }
        if (! empty($info['onbehalf'])) {
            $itemuser = $info['onbehalf'];
        } elseif (! $user || isset($_SESSION['forceanon']) && $_SESSION['forceanon'] == 'y') {
            $itemuser = $cartuser;
        } else {
            $itemuser = $user;
        }
        if (
            $orderId && $user && $prefs['payment_cart_orders'] == 'y' ||
            ($orderId && ! $user && $prefs['payment_cart_anonymous'] == 'y')
        ) {
            $definition = Tracker_Definition::get($this->orderitemsTracker);
            $this->utilities->insertItem($definition, [
                'status' => 'p',
                'fields' => [
                    $this->orderitemsOrderField         => $orderId,
                    $this->orderitemsProductField       => $info['code'],
                    $this->orderitemsPriceField         => $info['price'],
                    $this->orderitemsQuantityField      => $info['quantity'],
                    $this->orderitemsUserField          => $itemuser,
                    $this->orderitemsInputtedPriceField => $info['inputedprice'],
                    $this->orderitemsParentCodeField    => $parentCode,
                ],
            ]);
        }

        $this->change_inventory($info['code'], -1 * $info['quantity'], false);
        if (
            (isset($info['exchangetoproductid']) && $info['exchangetoproductid'])
            && (isset($info['exchangeorderamount']) && $info['exchangeorderamount'])
        ) {
            $this->change_inventory($info['exchangetoproductid'], -1 * $info['exchangeorderamount'], false);
        }
        if ($total > 0) {
            $this->paymentlib->register_behavior($invoice, 'cancel', 'replace_inventory', [ $info['code'], $info['quantity'] ]);
            if (
                (isset($info['exchangetoproductid']) && $info['exchangetoproductid'])
                && (isset($info['exchangeorderamount']) && $info['exchangeorderamount'])
            ) {
                $this->paymentlib->register_behavior($invoice, 'cancel', 'replace_inventory', [ $info['exchangetoproductid'], $info['exchangeorderamount'] ]);
            }
        }

        $ret = ['product_classes' => $product_classes];
        return $ret;
    }

    final public function empty_cart(): void
    {
        $this->clear_onhold_list();
        $_SESSION['cart'] = [];
    }

    private function get_behaviors(): array
    {
        $behaviors = [];

        foreach ($this->get_content() as $item) {
            if (isset($item['behaviors'])) {
                foreach ($item['behaviors'] as $behavior) {
                    for ($i = 0; $item['quantity'] > $i; ++$i) {
                        $behaviors[] = $behavior;
                    }
                }
            }
        }

        return $behaviors;
    }

    private function init_cart(): void
    {
        if (! isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    private function init_product(int $code, array $info, int $parentCode = 0, int $quantity = 0, int $childInputedPrice = 0): void
    {

        if (! isset($_SESSION['cart'][ $code ]) ||  ! isset($_SESSION['cart'][ $parentCode ][ 'bundledproducts' ][ $code ])) {
            $info['hash'] = md5($code . time());
            $info['code'] = $code;
            $info['quantity'] = $quantity;
            $info['price'] = number_format($info['price'], 2, '.', '');
            $info['inputedprice'] = number_format(abs($childInputedPrice), 2, '.', '');

            if (! isset($info['href'])) {
                $info['href'] = null;
            }

            if (! $parentCode) {
                $_SESSION['cart'][ $code ] = $info;
            } else {
                 $_SESSION['cart'][ $parentCode ][ 'bundledproducts' ][ $code ] = $info;
            }
        }
    }

    final public function get_bundled_products(int $parentCode): ?array
    {
        $cart = $this->get_content();
        if (isset($cart[$parentCode]['bundledproducts'])) {
            return $cart[$parentCode]['bundledproducts'];
        } else {
            return null;
        }
    }

    final public function get_content(): array
    {
        $this->init_cart();

        return $_SESSION['cart'];
    }

    final public function get_inventory_type(int $productId): string
    {
        return $this->trklib->get_item_value($this->productTracker, $productId, $this->productInventoryTypeField);
    }

    final public function get_inventory(int $productId, bool $less_hold = true): int
    {
        $inventoryType = $this->get_inventory_type($productId);
        if ($inventoryType == 'none') {
            return 999999999;
        }
        if ($inventoryType == 'shared') {
            // TODO: shared inventory feature not yet exist
            return 0;
        }
        if ($less_hold) {
            $this->expire_onhold_list($productId);
            $inventoryFieldId = $this->productInventoryLessHoldField;
        } else {
            $inventoryFieldId = $this->productInventoryTotalField;
        }

        return (int) $this->trklib->get_item_value($this->productTracker, $productId, $inventoryFieldId);
    }

    final public function change_inventory(int $productId, int $amount = 1, bool $changeLessHold = true): bool
    {
        if (! $this->inventoryEnabled) {
            return false;
        }
        $inventoryType = $this->get_inventory_type($productId);
        if ($inventoryType == 'none') {
            return false;
        }
        $currentTotal = $this->get_inventory($productId, false);
        $newTotal = max(0, $currentTotal + $amount);
        $this->set_inventory($productId, $newTotal, false);
        if ($changeLessHold) {
            $currentLessHold = $this->get_inventory($productId);
            $newLessHold = max(0, $currentLessHold + $amount);
            $this->set_inventory($productId, $newLessHold, true);
        }
        return true;
    }

    final public function hold_inventory(int $productId, int $amount = 1): bool
    {
        if ($bundledProducts = $this->get_bundled_products($productId)) {
            foreach ($bundledProducts as $b) {
                $this->hold_inventory($b['code'], $amount * $b['quantity']);
            }
        }
        $inventoryType = $this->get_inventory_type($productId);
        if ($inventoryType == 'none') {
            return false;
        }
        $currentLessHold = $this->get_inventory($productId);
        $newLessHold = max(0, $currentLessHold - $amount);
        $this->set_inventory($productId, $newLessHold, true);
        return true;
    }

    final public function unhold_inventory(int $productId, int $amount = 1): bool
    {
        if ($bundledProducts = $this->get_bundled_products($productId)) {
            foreach ($bundledProducts as $b) {
                $this->unhold_inventory($b['code'], $amount * $b['quantity']);
            }
        }
        $inventoryType = $this->get_inventory_type($productId);
        if ($inventoryType == 'none') {
            return false;
        }
        $currentLessHold = $this->get_inventory($productId);
        $currentTotal = $this->get_inventory($productId, false);
        $newLessHold = min($currentTotal, $currentLessHold + $amount);
        $this->set_inventory($productId, $newLessHold, true);
        return true;
    }

    private function set_inventory(int $productId, int $amount, bool $less_hold = true): bool
    {
        global $prefs;
        $inventoryType = $this->get_inventory_type($productId);
        if ($inventoryType == 'none') {
            return false;
        }
        if ($inventoryType == 'shared') {
            // TODO: shared inventory feature not existing yet
            return false;
        }

        if ($less_hold) {
            $inventoryFieldId = $this->productInventoryLessHoldField;
        } else {
            $inventoryFieldId = $this->productInventoryTotalField;
        }

        $this->modify_tracker_item(
            $this->productTracker,
            $productId,
            [
                ['fieldId' => $inventoryFieldId, 'value' => $amount]
            ]
        );

        return true;
    }

    private function modify_tracker_item(int $trackerId, int $itemId, array $trackerFields): bool
    {
        $tracker_fields_info = $this->trklib->list_tracker_fields($trackerId);
        $fieldTypes = [];
        foreach ($tracker_fields_info['data'] as $t) {
            $fieldTypes[$t['fieldId']] = $t['type'];
            $fieldOptionsArray[$t['fieldId']] = $t['options_array'];
        }
        foreach ($trackerFields as &$h) {
            $h['type'] = $fieldTypes[$h['fieldId']];
            $h['options_array'] = $fieldOptionsArray[$h['fieldId']];
        }
        foreach ($trackerFields as $v) {
            $ins_fields["data"][] = ['options_array' => $v['options_array'], 'type' => $v['type'], 'fieldId' => $v['fieldId'], 'value' => $v['value']];
        }
        $this->trklib->replace_item($trackerId, $itemId, $ins_fields);
        return true;
    }

    private function clear_onhold_list(): bool
    {
        $hashes = [];
        foreach ($this->get_content() as $item) {
            $hashes[] = $item['hash'];
        }
        if (empty($hashes)) {
            return false;
        }
        $mid = implode(',', array_fill(0, count($hashes), '?'));
        $query = "delete from `tiki_cart_inventory_hold` where `hash` in ($mid)";
        $this->tikilib->query($query, $hashes);
        return true;
    }

    private function expire_onhold_list(int $productId): bool
    {
        global $prefs;

        $expiry = $prefs['payment_cart_inventoryhold_expiry'] * 60;
        $hash = $this->get_hash($productId);
        $query = "select sum(`quantity`) from `tiki_cart_inventory_hold` where `productId` = ? and `timeHeld` < ?";
        $bindvars = [$productId, $this->tikilib->now - $expiry];
        if ($hash) {
            $query .= " and `hash` != ?";
            $bindvars[] = $hash;
        }
        $quantity = $this->tikilib->getOne($query, $bindvars);
        $query = "delete from `tiki_cart_inventory_hold` where `productId` = ? and `timeHeld` < ?";
        if ($hash) {
            $query .= " and `hash` != ?";
        }
        $this->tikilib->query($query, $bindvars);
        if ($quantity > 0) {
            $this->unhold_inventory($productId, $quantity);
        }
        return true;
    }

    final public function extend_onhold_list(): bool
    {
        global $prefs;
        $extend = $prefs['payment_cart_inventoryhold_expiry'] * 60;
        $hashes = [];
        foreach ($this->get_content() as $item) {
            $hashes[] = $item['hash'];
        }
        if (empty($hashes)) {
            return false;
        }
        $mid = implode(',', array_fill(0, count($hashes), '?'));
        $query = "select min(`timeHeld`) from `tiki_cart_inventory_hold` where `hash` in ($mid)";
        $earliest = $this->tikilib->getOne($query, $hashes);
        if ($earliest > $this->tikilib->now - $extend) {
            return false;
        }
        $query = "update `tiki_cart_inventory_hold` set `timeHeld` = ? where `hash` in ($mid)";
        $bindvars = array_merge([$this->tikilib->now], $hashes);
        $this->tikilib->query($query, $bindvars);
        return true;
    }

    private function remove_from_onhold_list(int $code): bool
    {
        $hash = $this->get_hash($code);
        $query = "delete from `tiki_cart_inventory_hold` where `hash` = ?";
        $this->tikilib->query($query, $hash);
        return true;
    }

    private function add_to_onhold_list(int $code, int $quantity): bool
    {
        $hash = $this->get_hash($code);
        $query = "insert into `tiki_cart_inventory_hold` (`productId`, `quantity`, `timeHeld`, `hash`) values (?,?,?,?)";
        $bindvars = [$code, $quantity, $this->tikilib->now, $hash];
        $this->tikilib->query($query, $bindvars);
        return true;
    }

    final public function get_missing_user_information_fields(int $product_class_id, string $type = 'required'): array
    {
        global $user, $prefs;
        if ($type == 'required') {
            $fields_str = $this->get_tracker_value_custom($prefs['payment_cart_productclasses_tracker_name'], 'Required Field IDs', $product_class_id);
        } elseif ($type == 'postpurchase') {
            $fields_str = $this->get_tracker_value_custom($prefs['payment_cart_productclasses_tracker_name'], 'Postpurchase Field IDs', $product_class_id);
        }
        $fields = explode(',', str_replace(' ', '', $fields_str));
        $tocheck = [];
        $missing = [];
        foreach ($fields as $f) {
            if (empty($f)) {
                continue;
            }
            $trackerId = $this->trklib->getOne('select `trackerId` from `tiki_tracker_fields` where `fieldId` = ?', $f);
            $tocheck[$trackerId][] = $f;
        }
        foreach ($tocheck as $trackerId => $flds) {
            $definition = Tracker_Definition::get($trackerId);
            if ($fieldId = $definition->getUserField()) {
                $item = $this->trklib->get_item($trackerId, $fieldId, $user);
                foreach ($flds as $f) {
                    if (! isset($item[$f]) || ! $item[$f]) {
                        $missing[$trackerId][] = $f;
                    }
                }
            } else {
                $missing = $tocheck;
            }
        }
        return $missing;
    }

    final public function get_missing_user_information_form(int $product_class_id, string $type = 'required'): string
    {
        global $prefs;
        if ($type == 'required') {
            return $this->get_tracker_value_custom($prefs['payment_cart_productclasses_tracker_name'], 'Associated Required Form', $product_class_id);
        } else {
            return $this->get_tracker_value_custom($prefs['payment_cart_productclasses_tracker_name'], 'Associated Postpurchase Form', $product_class_id);
        }
    }

    final public function skip_user_information_form_if_not_missing(int $product_class_id): bool
    {
        global $prefs;
        if ($this->get_tracker_value_custom($prefs['payment_cart_productclasses_tracker_name'], 'Skip Required Form if Filled', $product_class_id) == 'Yes') {
            return true;
        } else {
            return false;
        }
    }
}
