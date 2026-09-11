<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_Share_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_share');

        $perms = Perms::get();
        if (! $perms->share) {
            throw new Services_Exception_Denied();
        }
    }

    public function action_index()
    {
        global $prefs, $tikiroot, $base_url, $user;

        $tokenlib = TikiLib::lib('authtokens')::build($prefs);
        $globalperms = Perms::get();
        $tokens = $tokenlib->getTokens();
        $tokens = array_filter(
            $tokens,
            function ($row) use ($globalperms, $user) {
                return isset($row['data']) && $this->canViewToken($row, $user, $globalperms);
            }
        );

        foreach ($tokens as $key => $token) {
            $tokens[$key]['data'] = json_decode($token['data'], true);
            $tokens[$key]['groups'] = join(', ', json_decode($token['groups']));
            $tokens[$key]['parameters'] = (array) json_decode($token['parameters']);
            if ($token['timeout'] == -1) {
                $tokens[$key]['expires'] = '';
            } else {
                $tokens[$key]['expires'] = date('c', strtotime($token['creation']) + $token['timeout']);
            }
            $tokens[$key]['entry'] = preg_replace('#^' . preg_quote($tikiroot) . '#', '', $token['entry']);
            $queryParams = http_build_query(array_merge($tokens[$key]['parameters'], ['TOKEN' => $token['token']]));
            $queryParamsWithoutToken = http_build_query($tokens[$key]['parameters']);
            $tokenUrl = $tokenlib->convertToStandardUrl($tokens[$key]['entry'] . '?' . $queryParams);
            $tokens[$key]['token_url'] = $tokenUrl;
            $tokens[$key]['url'] = $base_url . $tokenlib->convertToStandardUrl($tokens[$key]['entry'] . '?' . $queryParamsWithoutToken);
            $tokens[$key]['full_url'] = $base_url . $tokenUrl;
        }
        krsort($tokens);

        return [
            'tokens' => $tokens,
            'cant' => count($tokens),
        ];
    }

    /**
     * The user "perform with checked" but with no action selected
     *
     * @throws Services_Exception
     */
    public function action_no_action()
    {
        Services_Utilities::modalException(tra('No action was selected. Please select an action before clicking OK.'));
    }

    /**
     * The user "perform with checked" action to remove selected shared URL's
     *
     * @param $input JitFilter
     * @return array
     * @throws Services_Exception
     */
    public function action_remove($input)
    {
        global $prefs, $user;

        $util = new Services_Utilities();
        if ($util->notConfirmPost()) {
            $util->setVars($input, [], 'checked');
            if ($util->itemsCount > 0) {
                $message = $util->itemsCount === 1
                    ? tra('Remove the following URL\'s ID?')
                    : tra('Remove the following URL\'s IDs?');

                $help = $util->itemsCount === 1
                    ? tra('By removing the shared URL, the token that allows access to the URL will be deleted. The URL will no longer be accessible.')
                    : tra('By removing the shared URLs, the tokens that allow access to the URLs will be deleted. The URLs will no longer be accessible.');

                return $util->confirm($message, tra('remove'), ['help' => $help]);
            } else {
                Services_Utilities::modalException(tra('No action was selected. Please select an action before clicking OK.'));
            }
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, [], 'items');

            $tokenlib = TikiLib::lib('authtokens')::build($prefs);
            $tokenIDs = $util->items;

            $tokensList = $this->action_index();
            $tokens = $tokensList['tokens'];

            $globalperms = Perms::get();
            $errorIds = [];

            foreach ($tokens as $token) {
                if (in_array($token['tokenId'], $tokenIDs)) {
                    if ($this->canDeleteToken($token, $user, $globalperms)) {
                        $tokenlib->deleteToken($token['tokenId']);
                    } else {
                        $errorIds[] = $token['tokenId'];
                    }
                }
            }

            $deleted = array_diff($util->items, $errorIds);
            $errorCount = count($errorIds);
            if ($errorCount > 0) {
                $message = $errorCount === 1
                    ? tra('An error occurred. You do not have the rights to remove the token:')
                    : tra('An error occurred. You do not have the rights to remove the tokens:');

                $feedback = [
                    'tpl' => 'action',
                    'mes' => $message,
                    'items' => $errorIds,
                ];

                Feedback::error($feedback);
            }

            $deletedCount = count($deleted);
            if ($deletedCount > 0) {
                $message = $deletedCount === 1
                    ? tra('The following token has been removed:')
                    : tra('The following tokens have been removed:');

                $feedback = [
                    'tpl' => 'action',
                    'mes' => $message,
                    'items' => $deleted,
                ];

                Feedback::success($feedback);
            }

            return Services_Utilities::refresh();
        }
    }

    private function canDeleteToken(array $token, ?string $user, $globalperms): bool
    {
        return $globalperms->admin || ($globalperms->remove_share && $user && ($token['user'] ?? null) === $user);
    }

    private function canViewToken(array $token, ?string $user, $globalperms): bool
    {
        return $globalperms->admin || ($user && ($token['user'] ?? null) === $user);
    }
}
