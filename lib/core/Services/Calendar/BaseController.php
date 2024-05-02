<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_Calendar_BaseController
{
    protected $daynamesPlural;
    protected $monthnames;
    protected $daynames;

    public function setUp(): void
    {
        Services_Exception_Disabled::check('feature_calendar');

        $this->daynamesPlural = [
            'SU' => tra('Sundays'),
            'MO' => tra('Mondays'),
            'TU' => tra('Tuesdays'),
            'WE' => tra('Wednesdays'),
            'TH' => tra('Thursdays'),
            'FR' => tra('Fridays'),
            'SA' => tra('Saturdays'),
        ];
        $this->monthnames = [
            '',
            tra('January'),
            tra('February'),
            tra('March'),
            tra('April'),
            tra('May'),
            tra('June'),
            tra('July'),
            tra('August'),
            tra('September'),
            tra('October'),
            tra('November'),
            tra('December'),
        ];
        $this->daynames = [
            'SU' => tra('Sunday'),
            'MO' => tra('Monday'),
            'TU' => tra('Tuesday'),
            'WE' => tra('Wednesday'),
            'TH' => tra('Thursday'),
            'FR' => tra('Friday'),
            'SA' => tra('Saturday'),
        ];
    }

    protected function createRecurrenceFromInput(JitFilter $input): CalRecurrence
    {
        $displayTimezone = TikiLib::lib('tiki')->get_display_timezone();
        $recurrence = new CalRecurrence(
            ! empty($input->recurrenceId->int()) ? $input->recurrenceId->int() : -1
        );
        switch ($input->recurrenceType->word()) {
            case "daily":
                $recurrence->setDaily(true);
                $recurrence->setWeekly(false);
                $recurrence->setMonthly(false);
                $recurrence->setYearly(false);
                $recurrence->setDays($input->days->int());
                break;
            case "weekly":
                $recurrence->setDaily(false);
                $recurrence->setWeekly(true);
                $recurrence->setMonthly(false);
                $recurrence->setYearly(false);
                $recurrence->setWeeks($input->weeks->int());
                $recurrence->setWeekdays(implode(',', $input->asArray('weekdays')));
                break;
            case "monthly":
                $recurrence->setDaily(false);
                $recurrence->setWeekly(false);
                $recurrence->setMonthly(true);
                $recurrence->setYearly(false);
                $recurrence->setMonths($input->months->int());
                $recurrence->setMonthlyType($input->recurrenceTypeMonthy->word());
                if ($input->recurrenceTypeMonthy->word() === 'weekday') {
                    $monthlyWeekdayValue = $input->monthlyWeekNumber->raw() . $input->monthlyWeekday->word();
                    $recurrence->setMonthlyWeekdayValue($monthlyWeekdayValue);
                } elseif ($input->recurrenceTypeMonthy->word() === 'firstlastweekday') {
                    $monthlyFirstlastWeekdayValue = $input->monthlyFirstLastWeekNumber->int();
                    $recurrence->setMonthlyFirstlastWeekdayValue($monthlyFirstlastWeekdayValue);
                } else {
                    $recurrence->setDayOfMonth(implode(',', $input->asArray('dayOfMonth')));
                }
                break;
            case "yearly":
                $recurrence->setDaily(false);
                $recurrence->setWeekly(false);
                $recurrence->setMonthly(false);
                $recurrence->setYearly(true);
                $recurrence->setYears($input->years->int());
                $recurrence->setYearlyType($input->recurrenceTypeYearly->word());
                if ($input->recurrenceTypeYearly->word() === 'weekday') {
                    $yearlyWeekdayValue = $input->yearlyWeekNumber->raw() . $input->yearlyWeekday->word();
                    $recurrence->setYearlyWeekdayValue($yearlyWeekdayValue);
                    $recurrence->setYearlyWeekMonth($input->yearlyWeekMonth->int());
                } elseif ($input->recurrenceTypeYearly->word() === 'firstlastweekday') {
                    $yearlyFirstlastWeekdayValue = $input->yearlyFirstLastWeekNumber->int();
                    $recurrence->setYearlyFirstlastWeekdayValue($yearlyFirstlastWeekdayValue);
                    $recurrence->setYearlyWeekMonth($input->yearlyWeekMonth->int());
                } else {
                    $recurrence->setDateOfYear(
                        str_pad($input->yearlyMonth->word(), 2, '0', STR_PAD_LEFT) .
                        str_pad($input->yearlyDay->word(), 2, '0', STR_PAD_LEFT)
                    );
                }
                break;
        }
        // Start/End periods adjusted from browser's timezone to UTC
        $startPeriod = $input->startPeriod->int();
        if (empty($startPeriod)) {
            // startPeriod does not exist when using the old non-jscalendar time selector with 3 dropdowns - n.b. this might not be the case anymore with the new UI
            $startPeriod = mktime(
                0,
                0,
                0,
                $input->startPeriod_Month->int(),
                $input->startPeriod_Day->int(),
                $input->startPeriod_Year->int()
            );
        } else {
            $startPeriod = TikiDate::convertWithTimezone($input->asArray(), $startPeriod);
        }
        $recurrence->setStartPeriod(TikiDate::getStartDay($startPeriod, 'UTC'));
        if ($input->endType->word() === "dt") {
            $endPeriod = $input->endPeriod->int();
            $endPeriod = TikiDate::convertWithTimezone($input->asArray(), $endPeriod);
            $server_offset = TikiDate::tzServerOffset($displayTimezone, $endPeriod);
            $endPeriod -= $server_offset;
            $recurrence->setEndPeriod($endPeriod);
            $recurrence->setNbRecurrences(0);
        } else {
            $nbRecurrences = $input->nbRecurrences->int() ?? 1;
            if ($input->recurrenceType->word() === 'weekly') {
                $nbRecurrences = $nbRecurrences * count($input->asArray('weekdays'));
            }
            $recurrence->setNbRecurrences($nbRecurrences);
            $recurrence->setEndPeriod(0);
        }
        return $recurrence;
    }

    /**
     * @param array $calitem
     *
     * @return array
     */
    protected function processParticipants(array $calitem): array
    {
        if (isset($calitem['organizers'])  && is_string($calitem['organizers'])) {
            $calitem['organizers'] = preg_split('/\s*,\s*/', $calitem['organizers']);
        }
        if (isset($calitem['organizers']) && array_key_exists('organizers', $calitem)) {
            $calitem['organizers'] = array_filter($calitem['organizers']);
        }

        // process participants
        if (! empty($calitem['participant_roles'])) {
            $participants = [];
            foreach ($calitem['participant_roles'] as $username => $role) {
                $email = TikiLib::lib('user')->get_user_email($username);
                if (! $email) {
                    $email = $username;
                }
                $participants[] = [
                    'username' => $username,
                    'email'    => $email,
                    'role'     => $role,
                    'partstat' => $calitem['participant_partstat'][$username] ?? '',
                ];
            }
            $calitem['participants'] = $participants;
            unset($calitem['participant_roles'], $calitem['participant_partstat']);
        } else {
            $calitem['participants'] = [];
        }

        return $calitem;
    }
}
