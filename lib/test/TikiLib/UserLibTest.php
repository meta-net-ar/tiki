<?php

class UserLibTest extends TikiTestCase
{
    protected function prepareLdapSyncUserDataUserLibMock($user, $name, $email, $country, $setWillBeCalled, $setValues)
    {
        $userLibMock = $this
            ->getMockBuilder('UsersLib')
            ->onlyMethods(['get_user_preference', 'get_user_email', 'set_user_fields'])
            ->getMock();
        $callCount = 0;

        //realName - get_user_preference
        $userLibMock
            ->method('get_user_preference')
            ->willReturnCallback(function ($userArg, $field) use ($user, $name, $country, &$callCount) {
                $this->assertEquals($user, $userArg);
                $this->assertContains($field, ['realName', 'country']);
                if ($field === 'realName') {
                    return $name;
                } elseif ($field === 'country') {
                    return $country;
                }
                return null;
            });
        //email - get_user_email
        $userLibMock
            ->method('get_user_email')
            ->willReturnCallback(function ($userArg) use ($user, $email, &$callCount) {
                $this->assertEquals($user, $userArg);
                return $email;
            });
        if ($setWillBeCalled) {
            // set_user_fields
            $userLibMock
                ->method('set_user_fields')
                ->willReturnCallback(function ($setValuesArg) use ($setValues, &$callCount) {
                    $this->assertEquals($setValues, $setValuesArg);
                    return true;
                });
        }
        return $userLibMock;
    }

    /**
     * @dataProvider dataForLdapSyncUserDataUserWithoutPreferences
     * @param $name
     * @param $email
     * @param $country
     * @param $ldapAttributes
     * @param $setValues
     */
    public function testLdapSyncUserDataUserWithoutPreferences($name, $email, $country, $ldapAttributes, $setValues): void
    {
        global $prefs;
        $prefs['auth_ldap_nameattr'] = 'cn';
        $prefs['auth_ldap_emailattr'] = 'mail';
        $prefs['auth_ldap_countryattr'] = 'c';

        $setWillBeCalled = is_array($setValues) && count($setValues) > 0;

        $user = md5(uniqid(true));
        $setValues['login'] = $user;

        $userLib = $this->prepareLdapSyncUserDataUserLibMock($user, $name, $email, $country, $setWillBeCalled, $setValues);


        $userLib->ldap_sync_user_data($user, $ldapAttributes);
    }

    public static function dataForLdapSyncUserDataUserWithoutPreferences(): array
    {
        return [
            [ // empty values
                'name' => null,
                'email' => null,
                'country' => null,
                'ldapAttributes' => [],
                'setValues' => [],
            ],
            [ // existing values, no attributes from ldap
                'name' => 'Some Name',
                'email' => 'email@example.com',
                'country' => 'XX',
                'ldapAttributes' => [],
                'setValues' => [
                    'realName' => '',
                    'email' => '',
                    'country' => ''
                ],
            ],
            [ // existing values, empty values from ldap
                'name' => 'Some Name',
                'email' => 'email@example.com',
                'country' => 'XX',
                'ldapAttributes' => [
                    'cn' => '',
                    'mail' => '',
                    'c' => ''
                ],
                'setValues' => [
                    'realName' => '',
                    'email' => '',
                    'country' => ''
                ],
            ],
            [ // existing values, new values from ldap
                'name' => 'Some Name',
                'email' => 'email@example.com',
                'country' => 'XX',
                'ldapAttributes' => [
                    'cn' => 'Ldap Name',
                    'mail' => 'ldap@example.com',
                    'c' => 'XY'
                ],
                'setValues' => [
                    'realName' => 'Ldap Name',
                    'email' => 'ldap@example.com',
                    'country' => 'XY'
                ],
            ],
            [ //existing values, new values from ldap, including existing value for multi values attributes
                'name' => 'Some Name',
                'email' => 'email@example.com',
                'country' => 'XX',
                'ldapAttributes' => [
                    'cn' => 'Ldap Name',
                    'mail' => ['ldap@example.com', 'email@example.com'],
                    'c' => 'XY'
                ],
                'setValues' => [
                    'realName' => 'Ldap Name',
                    'email' => 'email@example.com',
                    'country' => 'XY'
                ],
            ],
            [ //existing values, new multi values attributes
                'name' => 'Some Name',
                'email' => 'old_email@example.com',
                'country' => 'XX',
                'ldapAttributes' => [
                    'cn' => ['Ldap Name', 'Other Name'],
                    'mail' => ['ldap@example.com', 'email@example.com'],
                    'c' => ['XY', 'XZ']
                ],
                'setValues' => [
                    'realName' => 'Ldap Name',
                    'email' => 'ldap@example.com',
                    'country' => 'XY'
                ],
            ],
        ];
    }
}
