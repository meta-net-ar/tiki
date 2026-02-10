<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_User_Controller
{
    /**
     * @var UsersLib
     */
    private $lib;

    /**
     * Filters for $input->replaceFilters() used in the Services_Utilities()->setVars method
     *
     * @var array
     */
    private $filters = [
        'checked'           => 'username',
        'items'             => 'username',
        'remove_pages'      => 'word',
        'remove_items'      => 'text',
        'remove_files'      => 'word',
        'ban_users'         => 'word',
        'checked_groups'    => 'groupname',
        'groupremove'       => 'groupname',
        'defaultgroup'      => 'text',
        'add_remove'        => 'word',
        'wikiTpl'           => 'pagename'
    ];

    public function setUp()
    {
        $this->lib = TikiLib::lib('user');
    }

    public function action_list_users($input)
    {
        $groupIds = $input->groupIds->int();
        $offset = $input->offset->int();
        $maxRecords = $input->maxRecords->int();

        $groupFilter = '';

        if (is_array($groupIds)) {
            $table = TikiDb::get()->table('users_groups');
            $groupFilter = $table->fetchColumn(
                'groupName',
                [
                    'id' => $table->in($groupIds),
                ]
            );
        }

        $result = $this->lib->get_users($offset, $maxRecords, 'login_asc', '', '', false, $groupFilter);

        return [
            'result' => $result['data'],
            'count' => $result['count'],
        ];
    }

    /**
     * @param $input JitFilter
     * @return array
     * @throws Services_Exception
     */
    public function action_register($input)
    {
        global $https_mode, $prefs, $user;
        if (! $https_mode && $prefs['https_login'] == 'required') {
            return ['result' => json_encode([tr("secure connection required")])];
        }

        if (TIKI_API) {
            Services_Exception_Denied::checkAuth();
        }

        $name = $input->name->text();
        $pass = $input->pass->text();
        $passAgain = $input->passAgain->text();
        $captcha = $input->asArray('captcha');
        $antibotcode = $input->antibotcode->text();
        $email = $input->email->text();

        if ($prefs['user_unique_email'] == 'y' && TikiLib::lib('user')->get_user_by_email($email)) {
            $errormsg = tra('We were unable to create your account because this email is already in use.');
            throw new Services_Exception($errormsg);
        }

        if ($pass !== $passAgain) {
            throw new Services_Exception(tr('Passwords do not match.'));
        }

        $regResult = TikiLib::lib('registration')->register_new_user(
            [
                'name' => $name,
                'pass' => $pass,
                'passAgain' => $passAgain,
                'captcha' => $captcha,
                'antibotcode' => $antibotcode,
                'email' => $email,
            ]
        );

        if (TIKI_API && empty($regResult)) {
            $regResult = tra('User account created but pending confirmation.');
        }

        return [
            'result' => $regResult,
        ];
    }

    /**
     * Show user info popup
     *
     * @param $input JitFilter (username)
     * @return array
     */
    public function action_info($input)
    {
        global $prefs, $user;

        $tikilib = TikiLib::lib('tiki');
        $sociallib = TikiLib::lib('social');
        $other_user = $input->username->email();

        if (! $this->lib->user_exists($other_user)) {
            throw new Services_Exception_NotFound(tr('User does not exist.'));
        }

        $result = [
            'fullname' => '',
            'gender' => '',
            'starHtml' => '',
            'country' => '',
            'distance' => '',
            'email' => '',
            'lastSeen' => '',
            'avatarHtml' => '',
            'error' => '',
            'shared_groups' => '',
        ];

        if (
            $prefs['feature_community_mouseover'] == 'y' &&
            $this->lib->get_user_preference($user, 'show_mouseover_user_info', 'y') == 'y' ||
            $prefs['feature_friends'] == 'y'
        ) {
            $result['other_user'] = $other_user;
            if (
                $this->lib->user_exists($other_user) &&
                ($tikilib->get_user_preference($other_user, 'user_information', 'public') === 'public' ||
                $user == $other_user ||
                $prefs['feature_friends'] == 'y')
            ) {
                $info = $this->lib->get_user_info($other_user);

                $result['add_friend_button'] = '';
                $result['friendship'] = [];

                if ($prefs['feature_friends'] === 'y' && $user) {
                    $friendship = [];

                    if ($prefs['social_network_type'] === 'friend') {
                        $friend = $this->isFriend($sociallib->listFriends($user), $other_user);
                        if ($friend) {
                            $friendship[] = [
                                'type' => 'friend',
                                'label' => tra('Friend'),
                                'remove' => tra('Remove Friend'),
                            ];
                        } else {
                            $result['add_friend_button'] = tra('Add Friend');
                        }
                    } else {
                        $follower = $this->isFriend($sociallib->listFollowers($user), $other_user);
                        $following = $this->isFriend($sociallib->listFollowers($other_user), $user);

                        if ($follower) {
                            $friendship[] = [
                                'type' => 'follower',
                                'label' => tra('Following you'),
                            ];
                            if ($prefs['social_network_type'] === 'follow_approval') {
                                $friendship[count($friendship) - 1]['remove'] = tra('Remove Follower');
                            }
                        }
                        if ($following) {
                            $friendship[] = [
                                'type' => 'following',
                                'label' => tra('You are following'),
                                'remove' => tra('Stop Following'),
                            ];
                        } else {
                            $result['add_friend_button'] = tra('Follow');
                        }
                    }
                    $incoming = $this->isFriend($sociallib->listIncomingRequests($user), $other_user);
                    if ($incoming) {
                        $friendship[] = [
                            'type' => 'incoming',
                            'label' => tra('Awaiting your approval'),
                            'remove' => tra('Refuse Request'),
                            'add' => tra('Accept &amp; Add'),
                        ];
                        if ($prefs['social_network_type'] === 'follow_approval') {
                            $friendship[count($friendship) - 1]['approve'] = tra('Accept Request');
                        }
                        $result['add_friend_button'] = '';
                    }
                    $outgoing = $this->isFriend($sociallib->listOutgoingRequests($user), $other_user);
                    if ($outgoing) {
                        $friendship[] = [
                            'type' => 'outgoing',
                            'label' => tra('Waiting for approval'),
                            'remove' => tra('Cancel Request'),
                        ];
                        $result['add_friend_button'] = '';
                    }

                    $result['friendship'] = $friendship;

                    if ($user === $other_user) {
                        $result['add_friend_button'] = '';  // can't befriend yourself
                    }
                }

                if ($prefs['feature_community_mouseover_name'] == 'y') {
                    $result['fullname'] = $this->lib->clean_user($other_user);
                } else {
                    $result['fullname'] = $other_user;
                }

                if ($prefs['feature_community_mouseover_gender'] == 'y' && $prefs['feature_community_gender'] == 'y') {
                    $result['gender'] = $this->lib->get_user_preference($other_user, 'gender');
                    if ($result['gender'] == tr('Hidden')) {
                        $result['gender'] = '';
                    }
                }

                if ($prefs['feature_score'] == 'y') {
                    $info['score'] = TikiLib::lib('score')->get_user_score($other_user);
                    if (
                        $prefs['feature_community_mouseover_score'] == 'y' &&
                        ! empty($info['score']) &&
                        $other_user !== 'admin' &&
                        $other_user !== 'system' &&
                        $other_user !== 'Anonymous'
                    ) {
                        $result['starHtml'] = $tikilib->get_star($info['score']);
                    } else {
                        $result['starHtml'] = '';
                    }
                }

                if ($prefs['feature_community_mouseover_country'] == 'y') {
                    $result['country'] = $tikilib->get_user_preference($other_user, 'country', '');
                    if ($result['country'] == tr('Other')) {
                        $result['country'] = '';
                    }
                }

                if ($prefs['feature_community_mouseover_distance'] == 'y') {
                    $distance = TikiLib::lib('userprefs')->get_userdistance($other_user, $user);
                    if ($distance) {
                        $result['distance'] = $distance . ' ' . tra('km');
                    }
                }

                if ($prefs['feature_community_mouseover_email'] == 'y') {
                    $email_isPublic = $tikilib->get_user_preference($other_user, 'email is public');
                    if ($email_isPublic != 'n') {
                        $result['email'] = TikiLib::scrambleEmail($info['email']);
                    //} elseif ($friend) {
                    //  $result['email'] = $info['email']; // should friends see each other's emails whatever the settings? I doubt it (jb)
                    }
                }

                if ($prefs['feature_community_mouseover_lastlogin'] == 'y') {
                    $result['lastSeen'] = $info['currentLogin'] ? $info['currentLogin'] : null;
                }


                if ($prefs['feature_community_mouseover_picture'] == 'y') {
                    $result['avatarHtml'] = $tikilib->get_user_avatar($other_user);
                }

                if ($user !== $other_user) { // should have a new pref?
                    $theirGroups = TikiLib::lib('user')->get_user_groups($other_user);
                    $myGroups = TikiLib::lib('user')->get_user_groups($user);
                    $choiceGroups = TikiLib::lib('user')->get_groups_userchoice();
                    $sharedGroups = array_intersect($theirGroups, $myGroups, $choiceGroups);

                    $result['shared_groups'] = implode(', ', $sharedGroups);
                }
            }
        } else {
            $result['error'] = tra("You cannot see this user's data.");
            if ($user) {
                $result['error'] .= '<br>' .
                    tra('You need to set "Show my information on mouseover".') . '<br>' .
                    '<a href="tiki-user_preferences.php?cookietab=2">' . tra('Click here') . '</a>';
            } else {
                $result['error'] .= '<br>' . tra('You need to log in.');
            }
        }

        return $result;
    }

    /**
     * @param $userlist array 'user' => username
     * @param $user string
     * @return bool
     */
    private function isFriend($userlist, $user)
    {
        foreach ($userlist as $v) {
            if (isset($v['user']) && $v['user'] === $user) {
                return true;
            }
        }
        return false;
    }

    /**
     * Admin user "perform with checked" but with no action selected
     *
     * @throws Services_Exception
     */
    public function action_no_action()
    {
        Services_Utilities::modalException(tra('No action was selected. Please select an action before clicking OK.'));
    }

    /**
     * Admin user "perform with checked" action to remove selected users
     *
     * @param $input JitFilter
     * @return array
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     */
    public function action_remove_users($input)
    {
        global $prefs;

        Services_Exception_Denied::checkGlobal('admin_users');
        $util = new Services_Utilities();
        //first pass - show confirm modal popup
        if ($util->notConfirmPost()) {
            $util->setVars($input, [], 'checked');
            if ($util->itemsCount > 0) {
                if (count($util->items) === 1) {
                    $msg = tra('Delete the following user?');
                } else {
                    $msg = tra('Delete the following users?');
                }

                $trackerIds = [];
                if ($prefs['feature_trackers'] === 'y') {
                    $trackerLib = TikiLib::lib('trk');
                    foreach ($util->items as $aUser) {
                        $userItems = $trackerLib->get_user_items($aUser, false);
                        foreach ($userItems as $userItem) {
                            if (! isset($trackerIds[$userItem['trackerId']])) {
                                $info = $trackerLib->get_tracker($userItem['trackerId']);
                                $trackerIds[$userItem['trackerId']] = [
                                    'name' => $info['name'],
                                    'count' => 1,
                                ];
                            } else {
                                $trackerIds[$userItem['trackerId']]['count']++;
                            }
                        }
                    }
                }

                return [
                    'modal' => '1',
                    'confirmAction' => $input->action->word(),
                    'confirmController' => 'user',
                    'customMsg' => $msg,
                    'confirmButton' => tra('Delete'),
                    'items' => $util->items,
                    //'ticket' => $check['ticket'],
                    'confirm' => 'y',
                    'trackerIds' => $trackerIds,
                ];
            } else {
                Services_Utilities::modalException(tra('No users were selected. Please select one or more users.'));
            }
        //after confirm submit - perform action and return success feedback
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, $this->filters, 'items');
            //delete user
            // maybe delete page as well?
            $remove_pages = ! empty($input['remove_pages']);

            // check for trackers
            $remove_items = $input->asArray('remove_items');

            // file galleries?
            $remove_files = ! empty($input['remove_files']);

            // do the deleting...
            $del = $this->removeUsers($util->items, $remove_pages, $remove_items, $remove_files);

            if ($del) {
                //prepare feedback
                if ($util->itemsCount === 1) {
                    $msg = tra('The following user has been deleted:');
                    $toMsg = tra('Submit form below to ban this user.');
                } else {
                    $msg = tra('The following users have been deleted:');
                    $toMsg = tra('Submit form below to ban these users.');
                }
                $feedback = [
                    'tpl' => 'action',
                    'mes' => $msg,
                    'items' => $util->items,
                ];

                //redirect to banning page if selected
                if ($input['ban_users']) {
                    $feedback['toMsg'] = $toMsg;
                    Feedback::success($feedback);
                    $url = 'tiki-admin_banning.php?mass_ban_ip_users=' . implode('|', $util->items);
                    return Services_Utilities::redirect($url);
                //refresh page
                } else {
                    if (TIKI_API) {
                        return ['feedback' => $feedback];
                    } else {
                        Feedback::success($feedback);
                        return Services_Utilities::refresh();
                    }
                }
            }
        }
    }

    /**
     * Admin user "perform with checked" action to redirect to banning page with users preselected for banning
     *
     * @param $input JitFilter
     * @return array
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     * @throws Services_Exception_Disabled
     */
    public function action_ban_ips($input)
    {
        Services_Exception_Disabled::check('feature_banning');
        Services_Exception_Denied::checkGlobal('admin_banning');
        $util = new Services_Utilities();
        //first pass - show confirm popup
        if ($util->notConfirmPost()) {
            $util->setVars($input, $this->filters, 'checked');
            if (count($util->items) > 0) {
                if ($util->itemsCount === 1) {
                    $msg = tra('Ban the following user\'s IP?');
                    $help = tra('Clicking Ban will redirect you to a form where this user\'s is preselected for IP banning.');
                } else {
                    $msg = tra('Ban the following users\' IPs?');
                    $help = tra('Clicking Ban will redirect you to a form where these users\' are preselected for IP banning.');
                }
                return $util->confirm($msg, tra('Ban'), ['help' => $help]);
            } else {
                Services_Utilities::modalException(tra('No users were selected. Please select one or more users.'));
            }
        //after confirm submit - redirect to banning page with users preselected
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, $this->filters, 'items');
            $url = 'tiki-admin_banning.php?mass_ban_ip_users=' . implode('|', $util->items);
            $feedback = ['mes' => tr('See highlighted section in the form below for users you have selected for banning.')];
            Feedback::note($feedback);
            return Services_Utilities::redirect($url);
        }
    }

    /**
     * Admin user "perform with checked" action to assign user to or remove users from groups
     *
     * @param JitFilter $input
     * @return array
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     */
    public function action_manage_groups($input)
    {
        global $user, $prefs;
        Services_Exception_Denied::checkGlobal('admin_users');
        $util = new Services_Utilities();
        $userlib = TikiLib::lib('user');
        //first pass - show confirm modal popup
        if ($util->notConfirmPost()) {
            $util->setVars($input, $this->filters, 'checked');
            if ($util->itemsCount > 0) {
                //provide redirect if js is not enabled
                $extraFields = [];

                if ($prefs['users_admin_actions_require_validation'] == 'y') {
                    if ($userlib->isAutologin()) {
                        Services_Utilities::modalException($userlib->getAutologinAdminActionError());
                    }
                    $extraFields = [
                        [
                            'label' => tr('Please confirm this operation by typing your password'),
                            'field' => 'input',
                            'type' => 'password',
                            'name' => 'confirmpassword',
                            'placeholder' => tr('Password'),
                            'size' => '60'
                        ]
                    ];
                }

                //remove from group icon clicked for a specific user
                if (isset($input['groupremove'])) {
                    $group = $input['groupremove'];
                    return $util->confirm(
                        tr('Remove the following user from group %0?', $group),
                        tr('Remove'),
                        [
                            'add_remove'    => 'remove',
                            'group'         => $group,
                            'anchor'        => $input->anchor->striptags(),
                            'fields'        => $extraFields
                        ]
                    );
                //selected users to be added or removed from selected groups groups
                } else {
                    $all_groups = $this->lib->list_regular_groups();
                    $isAdmin = false;
                    $userGroups = $userlib->get_user_info($user)['groups'];
                    $selectedUserGroups = TikiLib::lib('tiki')->get_user_groups($util->items[0]);

                    $groupsNames = [];
                    foreach ($userGroups as $group_in) {
                        if ($group_in == 'Admins') {
                            $isAdmin = true;
                        }
                    }
                    if ($isAdmin) {
                        foreach ($all_groups as $group) {
                            $groupsNames[] = $group["groupName"];
                        }
                    } else {
                        $groupsToCheck = array_unique(array_merge($userGroups, $selectedUserGroups));
                        foreach ($all_groups as $group) {
                            foreach ($groupsToCheck as $group_in) {
                                if ($group["groupName"] == $group_in) {
                                    $groupsNames[] = $group["groupName"];
                                }
                            }
                        }
                    }
                    $countgrps = count($all_groups) < 21 ? count($all_groups) : 20;
                    if ($util->itemsCount == 1) {
                        $customMsg = tra('For this user:');
                        $userGroups = TikiLib::lib('tiki')->get_user_groups($util->items[0]);
                    } else {
                        $userGroups = [];
                        $customMsg = tra('For these selected users:');
                        foreach ($util->items as $usr) {
                            $userInfo = $userlib->get_user_info($usr);
                            if (! empty($userInfo['groups'])) {
                                $userGroups = array_merge($userGroups, $userInfo['groups']);
                            }
                        }
                        $userGroups = array_unique($userGroups);
                    }
                    return [
                        'title' => tra('Change group assignments for selected users'),
                        'confirmAction' => $input['action'],
                        'confirmController' => 'user',
                        'customMsg' => $customMsg,
                        'all_groups' => $groupsNames,
                        'countgrps' => $countgrps,
                        'items' => $util->items,
                        'extra' => [
                            'fields' => $extraFields
                        ],
                        'modal' => '1',
                        'userGroups' => json_encode($userGroups),
                    ];
                }
            } else {
                Services_Utilities::modalException(tra('No users were selected. Please select one or more users.'));
            }
        //after confirm submit - perform action and return success feedback
        } elseif ($util->checkCsrf()) {
            if ($prefs['users_admin_actions_require_validation'] == 'y' && ! TIKI_API) {
                if ($userlib->isAutologin()) {
                    Services_Utilities::modalException($userlib->getAutologinAdminActionError());
                }
                $pass = $input->offsetGet('confirmpassword');
                $user = $_SESSION['u_info']['login'] ?? '';
                $ret = $userlib->validate_user($user, $pass);
                if (! $ret[0]) {
                    Services_Utilities::modalException(tra('Invalid password'));
                }
            }

            $util->setVars($input, $this->filters, 'items');

            // default group?
            $defaultGroup = $input['default_group'];
            $groups = [];

            //selected users added or removed from selected groups
            if (isset($input['checked_groups'])) {
                $groups = $input->asArray('checked_groups');
                $add_remove = $input['add_remove'];
            //single user removed from a particular group
            } elseif (! empty($util->extra['add_remove'])) {
                $groups = ! empty($util->extra['group']) ? [$util->extra['group']] : $groups;
                $add_remove = $util->extra['add_remove'];
            }
            if (! empty($util->items) && (! empty($groups) || $defaultGroup)) {
                global $user;
                $userGroups = $this->lib->get_user_groups_inclusion($user);
                $permname = 'group_' . $add_remove . '_member';
                $logslib = TikiLib::lib('logs');
                $groupperm = Perms::get()->$permname;
                $userperm = Perms::get()->group_join;
                foreach ($util->items as $assign_user) {
                    foreach ($groups as $group) {
                        if ($groupperm || (array_key_exists($group, $userGroups) && $userperm)) {
                            if ($add_remove === 'add') {
                                $res = $this->lib->assign_user_to_group($assign_user, $group);
                                if ($res && $res->numRows()) {
                                    $logmsg = sprintf(tra('%s %s assigned to %s %s.'), tra('user'), $assign_user, tra('group'), $group);
                                    $logslib->add_log('adminusers', $logmsg, $user);
                                } else {
                                    $msg = tra('An error occurred. The group assignment failed.');
                                    if (TIKI_API) {
                                        throw new Services_Exception($msg);
                                    }
                                    Feedback::error(['mes' => $msg]);
                                    return Services_Utilities::closeModal();
                                }
                            } elseif ($add_remove === 'remove') {
                                $this->lib->remove_user_from_group($assign_user, $group);
                                $logmsg = sprintf(
                                    tra('%s %s removed from %s %s.'),
                                    tra('user'),
                                    $assign_user,
                                    tra('group'),
                                    $group
                                );
                                $logslib->add_log('adminusers', $logmsg, $user);
                            }
                        } else {
                            if (TIKI_API) {
                                throw new Services_Exception_Denied();
                            }
                            Feedback::error(['mes' => tra('Permission denied')]);
                            return Services_Utilities::closeModal();
                        }
                    }

                    if ($defaultGroup) {
                        $userCurrentGroups = TikiLib::lib('tiki')->get_user_groups($assign_user);
                        if (! in_array($defaultGroup, $userCurrentGroups)) {
                            $msg = tra('The user %0 is not a member of the group %1. Please add the user to the group before setting it as default.');
                            $msg = strtr($msg, [
                            '%0' => $assign_user,
                            '%1' => $defaultGroup,
                            ]);
                            if (TIKI_API) {
                                throw new Services_Exception($msg);
                            }
                            Feedback::error(['mes' => $msg]);
                            return Services_Utilities::closeModal();
                        }
                        $this->lib->set_default_group($assign_user, $defaultGroup);
                    }
                }
                //prepare feedback
                if ($util->itemsCount === 1) {
                    $msg = tra('The following user:');
                    $helper = 'Has';
                } else {
                    $msg = tra('The following users:');
                    $helper = 'Have';
                }
                if ($defaultGroup) {
                    $toMsg = tr('%0 had the following group set as default:', tra($helper));
                    $feedback = [
                        'tpl' => 'action',
                        'mes' => $msg,
                        'items' => $util->items,
                        'toMsg' => $toMsg,
                        'toList' => $defaultGroup,
                    ];
                    if (TIKI_API) {
                        return ['feedback' => $feedback];
                    }
                    Feedback::success($feedback);
                }
                if ($groups) {
                    $verb = $add_remove == 'add' ? 'added to' : 'removed from';
                    $grpcnt = count($groups) === 1 ? 'group' : 'groups';
                    $toMsg = tr('%0 been %1 the following %2:', tra($helper), tra($verb), tra($grpcnt));
                    $feedback = [
                        'tpl' => 'action',
                        'mes' => $msg,
                        'items' => $util->items,
                        'toMsg' => $toMsg,
                        'toList' => $groups,
                    ];
                    if (TIKI_API) {
                        return ['feedback' => $feedback];
                    }
                    Feedback::success($feedback);
                }
                //return to page
                if (! empty($util->extra['anchor'])) {
                    return Services_Utilities::redirect($util->extra['anchor']);
                } else {
                    return Services_Utilities::refresh();
                }
            } else {
                $msg = tra('No groups were selected. Please select one or more groups.');
                if (TIKI_API) {
                    throw new Services_Exception($msg);
                }
                Feedback::error(['mes' => $msg]);
                return Services_Utilities::closeModal();
            }
        }
    }

    /**
     * Admin user "perform with checked" action to assign the default group for a user or users
     *
     * @param JitFilter $input
     * @return array
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     */
    public function action_default_groups($input)
    {
        Services_Exception_Denied::checkGlobal('admin_users');
        Services_Exception_Denied::checkGlobal('group_add_member');
        $util = new Services_Utilities();
        //first pass - show confirm modal popup
        if ($util->notConfirmPost()) {
            $util->setVars($input, $this->filters, 'checked');
            if ($util->itemsCount > 0) {
                $all_groups = $this->lib->list_all_groups();
                $all_groups = array_combine($all_groups, $all_groups);
                return [
                    'FORWARD' => [
                        'controller' => 'access',
                        'action' => 'confirm_select',
                        'title' => tra('Set default group for selected users'),
                        'confirmAction' => $input['action'],
                        'confirmController' => 'user',
                        'customMsg' => tra('For these selected users:'),
                        'toList' => $all_groups,
                        'toMsg' => tra('Make this the default group:'),
                        'items' => $util->items,
                        'modal' => '1',
                    ]
                ];
            } else {
                Services_Utilities::modalException(tra('No users were selected. Please select one or more users.'));
            }
        //after confirm submit - perform action and return success feedback
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, $this->filters, 'items');
            $groups = isset($input['checked_groups']) ? $input->asArray('checked_groups')
                : $input->asArray('toId');
            if (! empty($util->items) && ! empty($groups)) {
                //perform action
                global $user;
                $logslib = TikiLib::lib('logs');
                $userGroups = $this->lib->get_user_groups_inclusion($user);
                $groupperm = Perms::get()->group_add_member;
                $userperm = Perms::get()->group_join;
                foreach ($util->items as $assign_user) {
                    foreach ($groups as $group) {
                        if ($groupperm || (array_key_exists($group, $userGroups) && $userperm)) {
                            $this->lib->set_default_group($assign_user, $group);
                            $logmsg = sprintf(
                                tra('group %s set as the default group for user %s.'),
                                $group,
                                $assign_user
                            );
                            $logslib->add_log('adminusers', $logmsg, $user);
                        }
                    }
                }
                //prepare feedback
                $msg = $util->itemsCount === 1 ? tra('For the following user:') : tra('For the following users:');
                $toMsg = tra('The following group has been set as the default group:');
                $feedback = [
                    'tpl' => 'action',
                    'mes' => $msg,
                    'items' => $util->items,
                    'toMsg' => $toMsg,
                    'toList' => $groups,
                ];
                Feedback::success($feedback);
                //return to page
                return Services_Utilities::refresh();
            } else {
                Feedback::error(['mes' => tra('No groups were selected. Please select one or more groups.')]);
                return Services_Utilities::closeModal();
            }
        }
    }

    /**
     * Admin user "perform with checked" action to email a wiki page to a user
     *
     * @param $input JitFilter
     * @return array
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     * @throws Services_Exception_Disabled
     */
    public function action_email_wikipage($input)
    {
        Services_Exception_Disabled::check('feature_wiki');
        Services_Exception_Denied::checkGlobal('admin_users');
        $util = new Services_Utilities();
        //first pass - show confirm modal popup
        if ($util->notConfirmPost()) {
            $util->setVars($input, $this->filters, 'checked');
            if ($util->itemsCount > 0) {
                return [
                    'title' => tra('Send wiki page content by email to selected users'),
                    'confirmAction' => $input['action'],
                    'confirmController' => 'user',
                    'customMsg' => tra('For these selected users:'),
                    'items' => $util->items,
                    'modal' => '1',
                ];
            } else {
                Services_Utilities::modalException(tra('No users were selected. Please select one or more users.'));
            }
        //after confirm submit - perform action and return success feedback
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, $this->filters, 'items');
            $wikiTpl = $input['wikiTpl'];
            $tikilib = TikiLib::lib('tiki');
            $pageinfo = $tikilib->get_page_info($wikiTpl);
            if (! $pageinfo) {
                if (TIKI_API) {
                    throw new Services_Exception_NotFound();
                }
                Feedback::error(tra('Page not found'));
                return Services_Utilities::closeModal();
            }
            if (empty($pageinfo['description'])) {
                $msg = tra('The page does not have a description, which is mandatory to perform this action.');
                if (TIKI_API) {
                    throw new Services_Exception($msg);
                }
                Feedback::error($msg);
                return Services_Utilities::closeModal();
            }
            $bcc = $input['bcc'];
            include_once('lib/webmail/tikimaillib.php');
            $mail = new TikiMail();
            if (! empty($bcc)) {
                if (! validate_email($bcc)) {
                    Feedback::error(tra('Invalid bcc email address'));
                    return Services_Utilities::closeModal();
                }
                $mail->setBcc($bcc);
                $bccmsg = tr('and blind copied (bcc) to %0', $bcc);
            }
            global $user;
            $smarty = TikiLib::lib('smarty');

            $logslib = TikiLib::lib('logs');
            foreach ($util->items as $mail_user) {
                $smarty->assign_by_ref('user', $mail_user);
                $mail->setUser($mail_user);
                $mail->setSubject($pageinfo['description']);
                $text = $smarty->fetch('wiki:' . $wikiTpl);
                if (empty($text)) {
                    $msg = tra('The template page has no text or the text cannot be extracted.');
                    if (TIKI_API) {
                        throw new Services_Exception($msg);
                    }
                    Feedback::error($msg);
                    return Services_Utilities::closeModal();
                }
                $mail->setHtml($text);
                if (! $mail->send($this->lib->get_user_email($mail_user))) {
                    $errormsg = tra('Unable to send mail');
                    if (Perms::get()->admin) {
                        $mailerrors = print_r($mail->errors, true);
                        $errormsg .= $mailerrors;
                    }
                    if (TIKI_API) {
                        throw new Services_Exception($errormsg);
                    }
                    Feedback::error($errormsg);
                    return Services_Utilities::closeModal();
                } else {
                    if (! empty($bcc)) {
                        $logmsg = sprintf(tra('Mail sent to user %s'), $mail_user);
                    }
                        $logmsg = ! empty($bccmsg) ? $logmsg . ' ' . $bccmsg : $logmsg;
                    if (! empty($msg)) {
                        $logslib->add_log('adminusers', $logmsg, $user);
                    }
                }
                $smarty->assign_by_ref('user', $user);
            }
            //prepare feedback
            $msg = $util->itemsCount === 1 ? tr('The page %0 has been emailed to the following user:', $wikiTpl)
                : tr('The page %0 has been emailed to the following users:', $wikiTpl);
            $toMsg = ! empty($bcc) ? tr('And blind copied to %0.', $bcc) : '';
            $feedback = [
                'tpl' => 'action',
                'mes' => $msg,
                'items' => $util->items,
                'toMsg' => $toMsg,
            ];
            if (TIKI_API) {
                return ['feedback' => $feedback];
            }
            Feedback::success($feedback);
            //return to page
            return Services_Utilities::refresh();
        }
    }

    public function action_send_message($input)
    {
        global $user;
        $userlib = TikiLib::lib('user');
        if (TIKI_API) {
            $userwatch = $input->to->text();
        } else {
            $userwatch = $input->userwatch->text();
        }
        //ensures a user was selected to send a message to.
        if (empty($userwatch)) {
            if (TIKI_API) {
                throw new Services_Exception_NotFound();
            }
            Feedback::error(tra('No user was selected.'));
            return Services_Utilities::closeModal();
        }
        //sets default priority for the message to 3 if no priority was given
        if (! empty($input->priority->text())) {
            $priority = $input->priority->text();
        } else {
            $priority = 3;
        }
        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            if (
                empty($input->subject->text()) &&
                empty($input->body->text())
            ) {
                $msg = tra('Message not sent - no subject or body.');
                if (TIKI_API) {
                    throw new Services_Exception($msg);
                }
                Feedback::error($msg);
            } else {
                //if message is successfully sent
                if (
                    TikiLib::lib('message')->post_message(
                        $userwatch,
                        $user,
                        $input->to->text(),
                        '',
                        $input->subject->text(),
                        $input->body->text(),
                        $priority,
                        '',
                        isset($input->replytome) ? 'y' : '',
                        isset($input->bccme) ? 'y' : ''
                    )
                ) {
                    $message = tr(
                        'Your message was successfully sent to %0,',
                        $userlib->clean_user($userwatch)
                    );
                    if (TIKI_API) {
                        return ['feedback' => $message];
                    }
                    Feedback::success($message);
                } else {
                    $msg = tra('An error occurred, please check your mail settings and try again.');
                    if (TIKI_API) {
                        throw new Services_Exception($msg);
                    }
                    Feedback::error($msg);
                }
            }
            return Services_Utilities::closeModal();
        } else {
            return [
                'title' => tra("Send Me a Message"),
                'userwatch' => $userwatch,
                'priority' => $priority,
            ];
        }
    }

    public function action_get_message_count($input)
    {
        global $user;

        $sinceDate = null;
        if ($input->sinceDate->int()) {
            $sinceDate = $input->sinceDate->int();
        }

        $unread = null;
        if ($input->unread->bool()) {
            $unread = $input->unread->bool();
        }
        $messagelib = TikiLib::lib("message");
        $count = (int) $messagelib->count_messages($user, 'messages', $unread, $sinceDate);
        if (TIKI_API) {
            return ['count' => $count];
        }
        return $count;
    }

    public function action_invite_tempuser($input)
    {
        Services_Exception_Denied::checkGlobal('admin_users');
        $emails = $input->tempuser_emails->text();
        $groups = $input->tempuser_groups->text();
        $expiry = $input->tempuser_expiry->int();
        $prefix = $input->tempuser_prefix->text();
        $path = $input->tempuser_path->text();
        if (empty($prefix)) {
            $prefix = 'guest';
        }
        if (empty($path)) {
            $path = 'index.php';
        }

        $groups = explode(',', $groups);
        $emails = explode(',', $emails);
        $groups = array_map('trim', $groups);
        $emails = array_map('trim', $emails);
        if ($expiry > 0) {
            $expiry = $expiry * 3600 * 24; //translate day input to seconds
        } elseif ($expiry != -1) {
            Feedback::error(tra('Please specify validity period'));
            Services_Utilities::sendFeedback();
        }

        foreach ($groups as $grp) {
            if (! TikiLib::lib('user')->group_exists($grp)) {
                Feedback::error(tr('The group %0 does not exist', $grp));
                Services_Utilities::sendFeedback();
            }
        }

        TikiLib::lib('user')->invite_tempuser($emails, $groups, $expiry, $prefix, $path);

        Feedback::success(tra('Your invite has been sent.'));
        Services_Utilities::sendFeedback();
    }

    /**
     * @param $input JitFilter
     * @return array|bool
     * @throws Services_Exception
     * @throws SmartyException
     */
    public function action_upload_avatar($input)
    {
        global $user;
        $userwatch = $input->user->none();

        if (! $userwatch) {
            $errormsg = tra('You must set a user for whom to set an avatar.');
            throw new Services_Exception($errormsg);
        }

        if ($user != $userwatch && Perms::get()->admin != 'y') {
            $errormsg = tra('You do not have the permission to change the avatar.');
            throw new Services_Exception($errormsg);
        }
        TikiLib::lib('access')->check_feature('feature_userPreferences');
        TikiLib::lib('access')->check_user($user);
        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            if ($input->isAvatar->bool()) {
                $avatarLibName = $input->avatarLibName->text();
                $avatarName = $input->avatarName->text();
                $avatarType = $input->avatarType->text();
                TikiLib::lib('userprefs')->set_user_avatar($userwatch, $avatarType, $avatarLibName, $avatarName, null, null, null, false);
                return true;
            }
            if (empty($_FILES['userfile']['name'])) {
                $errormsg = tra('You must select an avatar to upload.');
                throw new Services_Exception($errormsg, 400);
            }
            $name = $_FILES['userfile']['name'];
            /**
             * @var $avatarlib AvatarLib
             */
            $avatarlib = TikiLib::lib('avatar');
            $avatarlib->set_avatar_from_url($_FILES['userfile']['tmp_name'], $userwatch, $name);
            return true;
        } else {
            return [
                "title" => tra("Upload Avatar"),
                "userwatch" => $userwatch,
            ];
        }
    }

    public function actionLocalTimezoneSync($input)
    {
        global $user, $tikilib;
        $access = TikiLib::lib('access');
        $clientTz = $input->client_timezone->text();
        $userPreferenceTz = $tikilib->get_user_preference($user, 'display_timezone', '');
        $action = $input->timezone_action->text();

        if ($action === 'never') {
            if ($tikilib->set_preference('user_localtimezonesync', 'n')) {
                $tikilib->set_user_preference($user, 'localtimezonesync', 'n');
            }
            $access->redirect($_SERVER['HTTP_REFERER']);
            return [];
        }

        if (! $this->isValidTimezone($clientTz)) {
            Feedback::error(tr("Invalid detected timezone."));
            $access->redirect($_SERVER['HTTP_REFERER']);
            return [];
        }

        // Determine effective timezone deterministically
        $effectiveTz = '';

        // Temporary session timezone (highest priority)
        if (! empty($_SESSION['temp_timezone']) && $this->isValidTimezone($_SESSION['temp_timezone'])) {
            $effectiveTz = $_SESSION['temp_timezone'];
        } elseif (! empty($userPreferenceTz) && $this->isValidTimezone($userPreferenceTz)) { // User preference
            $effectiveTz = $userPreferenceTz;
        } else { // Fallback to detected/system timezone
            $effectiveTz = $clientTz;
        }

        if ($action === 'switch') {
            $tikilib->set_user_preference($user, 'display_timezone', $clientTz);

            unset($_SESSION['temp_timezone']);

            $userPreferenceTz = $clientTz;
            $effectiveTz = $clientTz;
        } elseif ($action === 'temporary') {
            $_SESSION["temp_timezone"] = $clientTz;
            $effectiveTz = $clientTz;
        }

        // Determine if the client timezone and effective (user/system) timezone are different.
        // If they differ by name, we check if they are functionally equivalent
        // meaning their UTC offsets are the same throughout the year.
        //
        // This avoids showing misleading "timezone synchronization" notification when the
        // timezones are actually the same in behavior but have different names (e.g. "America/Toronto" vs "EST").
        //
        // This logic improves UX by not bothering users with unnecessary alerts
        // when the functional result is the same
        $different = false;
        // In detect mode, effective timezone always follows client
        if (empty($userPreferenceTz)) {
            $effectiveTz = $clientTz;
        }
        // $clientTz is already validated above
        if (! empty($clientTz)) {
            if ($clientTz !== $effectiveTz) {
                $different = true;
                try {
                    $tzClient = new DateTimeZone($clientTz);
                    $tzEffective = new DateTimeZone($effectiveTz);
                    $year = date('Y');
                    $functionallySame = true;

                    for ($month = 1; $month <= 12; $month++) {
                        $dateUTC = new DateTime("$year-$month-15 12:00:00", new DateTimeZone('UTC'));
                        $offsetClient = $tzClient->getOffset($dateUTC);
                        $offsetEffective = $tzEffective->getOffset($dateUTC);

                        if ($offsetClient !== $offsetEffective) {
                            $functionallySame = false;
                            break;
                        }
                    }
                    if ($functionallySame) {
                        $different = false;
                    }
                } catch (Exception $e) {
                    Feedback::error("TimezoneSync Error: " . $e->getMessage());
                    $different = false;
                }
            }
        }

        $result = [
            'different'      => $different,
            'preferedTimezone' => $userPreferenceTz,
            'clientTimezone'   => $clientTz,
            'effectiveTimezone' => $effectiveTz,
        ];

        if ($action === 'switch' || $action === 'temporary') {
            return $this->redirectAndReturn($result);
        }

        return $result;
    }

    private function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }

    private function redirectAndReturn($data = []): array
    {
        header("Location:" . $_SERVER['HTTP_REFERER']);
        return $data;
    }

    private function removeUsers(array $users, $page = false, $trackerIds = [], $files = false)
    {
        global $user;
        foreach ($users as $deleteuser) {
            if ($deleteuser != 'admin') {
                // remove the user's objects, wiki page first
                if ($page) {
                    global $prefs;
                    $page = $prefs['feature_wiki_userpage_prefix'] . $deleteuser;
                    Services_Exception_Denied::checkObject('remove', 'wiki page', $page);
                    $tikilib = TikiLib::lib('tiki');
                    if ($tikilib->page_exists($page)) {
                        $res = $tikilib->remove_all_versions($page);
                        if ($res !== true) {
                            Feedback::error(tr('An error occurred. User page for %0 could not be deleted', $deleteuser));
                            Services_Utilities::closeModal();
                            return false;
                        }
                    }
                }

                // then tracker items "owner" by the user
                if (! empty($trackerIds)) {
                    $trklib = TikiLib::lib('trk');

                    $items = $trklib->get_user_items($deleteuser, false);

                    foreach ($items as $item) {
                        if (in_array($item['trackerId'], $trackerIds)) {
                            $trklib->remove_tracker_item($item['itemId'], true);
                        }
                    }
                }

                // then tracker items "owner" by the user
                if ($files) {
                    $filegallib = TikiLib::lib('filegal');

                    $galleryId = $filegallib->get_user_file_gallery($deleteuser);

                    if ($galleryId) {
                        $filegallib->remove_file_gallery($galleryId);
                    }
                }

                // and finally remove the actual user (and other associated data)
                $res = $this->lib->remove_user($deleteuser);
                if ($res === true) {
                    $logslib = TikiLib::lib('logs');
                    $logslib->add_log('adminusers', sprintf(tra('Deleted account %s'), $deleteuser), $user);
                } else {
                    Feedback::error(tr('An error occurred. User %0 could not be deleted', $deleteuser));
                    Services_Utilities::closeModal();
                    return false;
                }
            }
        }
        return true;
    }


    public function action_set_user_lock_status($input)
    {
        Services_Exception_Denied::checkGlobal('admin_users');
        $util = new Services_Utilities();
        // a < action attribute 'lock' has two possible value 'lock' or 'unlock'
        $status_to_set = $input['lock'];
        //first pass - show confirm modal popup
        if ($util->notConfirmPost()) {
            $util->setVars($input, [], 'checked');
            if ($util->itemsCount > 0) {
                // Determine the action text
                $actionText = $status_to_set === 'lock' ? tr('Lock') : tr('Unlock');

                // Build the message based on the number of users
                if (count($util->items) === 1) {
                    $msg = tr('%0 the following user?', $actionText);
                } else {
                    $msg = tr('%0 the following users?', $actionText);
                }
                return $util->confirm($msg, $actionText);
            } else {
                // No users selected exception
                Services_Utilities::modalException(tra('No users were selected. Please select one or more users.'));
            }
        //after confirm submit - perform action and return success feedback
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, $this->filters, 'items');
            // lock or unlock the user
            $lock_status_updated = $this->updateUserLockStatus($util->items, $status_to_set);

            if ($lock_status_updated) {
                // Determine the action text
                $actionText = $status_to_set === 'lock' ? tr('locked') : tr('unlocked');

                // Prepare feedback
                if ($util->itemsCount === 1) {
                    $msg = tr('The following user has been %0:', ucfirst($actionText));
                    $toMsg = tra('Submit form below to ban this user.');
                } else {
                    $msg = tr('The following users have been %0:', ucfirst($actionText));
                    $toMsg = tra('Submit form below to ban these users.');
                }
                $feedback = [
                    'tpl' => 'action',
                    'mes' => $msg,
                    'items' => $util->items,
                ];

                //redirect to banning page if selected
                if ($input['ban_users']) {
                    $feedback['toMsg'] = $toMsg;
                    Feedback::success($feedback);
                    $url = 'tiki-admin_banning.php?mass_ban_ip_users=' . implode('|', $util->items);
                    return Services_Utilities::redirect($url);
                //refresh page
                } else {
                    if (TIKI_API) {
                        return ['feedback' => $feedback];
                    } else {
                        Feedback::success($feedback);
                        return Services_Utilities::refresh();
                    }
                }
            }
        }
    }

    /**
     * Admin user "perform with checked" action to lock selected users
     *
     * @param $input JitFilter
     * @return array
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     */
    public function actionLockUsers($input)
    {
        // Set the lock parameter and delegate to action_set_user_lock_status
        $input['lock'] = 'lock';
        return $this->action_set_user_lock_status($input);
    }

    /**
     * Admin user "perform with checked" action to unlock selected users
     *
     * @param $input JitFilter
     * @return array
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     */
    public function actionUnlockUsers($input)
    {
        // Set the lock parameter and delegate to action_set_user_lock_status
        $input['lock'] = 'unlock';
        return $this->action_set_user_lock_status($input);
    }

    private function updateUserLockStatus($users, $newLockStatus)
    {
        global $user;
        foreach ($users as $user_to_lock) {
            if ($user_to_lock != 'admin') {
                $res = $this->lib->update_user_lock_status($user_to_lock, $newLockStatus);
                if ($res !== true) {
                    Feedback::error(tr('An error occurred. User %0 lock status could not be updated', $user_to_lock));
                    Services_Utilities::closeModal();
                    return false;
                }
            }
        }
        return true;
    }

    public function actionValidateUser($input)
    {
        $username = $input->username->text();
        $password = $input->password->text();
        $userlib = TikiLib::lib('user');
        $ret = $userlib->validate_user($username, $password);
        return $ret[0];
    }

    public function action_save_column_prefs($input)
    {
        global $user;
        $columns = $input->prefs->value();
        $tracker_name = $input->trackerName->value();
        TikiLib::lib('tiki')->set_user_preference($user, 'trk_items_column_pref', $columns);
        return tr('Your column preference for tracker "%0" is saved.', $tracker_name);
    }

    public function actionReset2FA($input)
    {
        Services_Exception_Denied::checkGlobal('admin_users');

        $user = $input->user->text();
        if (empty($user)) {
            throw new Services_Exception(tra('No user was specified'));
        }

        TikiLib::lib('user')->update_2_factor_secret($user, '');
        return ['success' => true, 'message' => tra('2FA secret reset successfully')];
    }

    public function actionToggle2FA($input)
    {
        Services_Exception_Denied::checkGlobal('admin_users');

        $user = $input->user->text();
        if (empty($user)) {
            throw new Services_Exception(tra('No user was specified'));
        }

        $tikilib = TikiLib::lib('tiki');

        $usersDisabled2FA = $tikilib->get_preference('twoFactorAuthExcludedUsers', expectArray: true);
        $usersEnabled2FA = $tikilib->get_preference('twoFactorAuthIncludedUsers', expectArray: true);

        if ($input->enable->bool()) {
            $usersEnabled2FA = array_unique(array_merge($usersEnabled2FA, [$user]));
            $usersDisabled2FA = array_diff($usersDisabled2FA, [$user]);
        } else {
            $usersDisabled2FA = array_unique(array_merge($usersDisabled2FA, [$user]));
            $usersEnabled2FA = array_diff($usersEnabled2FA, [$user]);
        }

        $tikilib->set_preference('twoFactorAuthExcludedUsers', $usersDisabled2FA);
        $tikilib->set_preference('twoFactorAuthIncludedUsers', $usersEnabled2FA);

        return ['success' => true];
    }
}
