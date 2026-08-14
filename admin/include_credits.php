<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

function getStartDateFromRequest()
{
    global $tikilib, $smarty;

    // date values
    if (isset($_REQUEST['startDate_Year']) || isset($_REQUEST['endDate_Year'])) {
        $smarty->assign(
            'startDate',
            $tikilib->make_time(0, 0, 0, $_REQUEST['startDate_Month'], $_REQUEST['startDate_Day'], $_REQUEST['startDate_Year'])
        );

        $smarty->assign(
            'endDate',
            $tikilib->make_time(23, 59, 59, $_REQUEST['endDate_Month'], $_REQUEST['endDate_Day'], $_REQUEST['endDate_Year'])
        );

        $start_date = $_REQUEST['startDate_Year'] . '-' . $_REQUEST['startDate_Month'] . '-' . $_REQUEST['startDate_Day'];
        $end_date = $_REQUEST['endDate_Year'] . '-' . $_REQUEST['endDate_Month'] . '-' . $_REQUEST['endDate_Day'] . ' 23:59:59';
    } else {
        $start_date = $tikilib->now - 3600 * 24 * 30;
        $smarty->assign('startDate', $start_date);
        $end_date = date('Y-m-d 23:59:59');
    }

    return [$start_date, $end_date];
}

function userPlansAndCredits()
{
    global $creditslib, $editing, $creditTypes, $smarty;

    $userPlans = [];
    foreach ($creditTypes as $ct => $v) {
        $userPlans[$ct]['nextbegin'] = $creditslib->getNextPlanBegin($editing['userId'], $ct);
        $userPlans[$ct]['currentbegin'] = $creditslib->getLatestPlanBegin($editing['userId'], $ct);
        $userPlans[$ct]['expiry'] = $creditslib->getPlanExpiry($editing['userId'], $ct);
    }

    $credits = $creditslib->getRawCredits($editing['userId']);
    $smarty->assign('credits', $credits);
    $smarty->assign('editing', $editing);

    $smarty->assign('userPlans', $userPlans);

    return $credits;
}

function creditTypes()
{
    global $smarty, $creditslib;

    $creditTypes = $creditslib->getCreditTypes();
    $staticCreditTypes = $creditslib->getCreditTypes(true);
    $smarty->assign('credit_types', $creditTypes);
    $smarty->assign('static_credit_types', $staticCreditTypes);

    return [$creditTypes, $staticCreditTypes];
}

function consumptionData()
{
    global $editing, $req_type, $start_date, $end_date, $smarty, $creditslib;

    $consumption_data = $creditslib->getCreditsUsage($editing['userId'], $req_type, $start_date, $end_date);
    $smarty->assign('consumption_data', $consumption_data);
}
