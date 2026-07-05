<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use Tiki\Lib\Test\TestHelpers\Tracker\FieldCharacterizationTestCase;

/**
 * Characterization of Tracker_Field_UserSelector (type 'u'; the only
 * AutoSyncable type in the registry).
 *
 * Split behavior: replace_item() has no handleSave() hook for this type, so
 * at the persistence layer it stores exactly what it is given (our fixture's
 * round trip is a plain value-like save). The autoassign logic (Creator /
 * Modifier / "can override" permission gate) lives entirely in
 * getFieldData(), which the fixture's createItem()/updateItem() never calls
 * — it is exercised here by calling getFieldData() directly on bound
 * handlers, with $GLOBALS['user'] and stored state set up per case.
 *
 * Out-of-class behavior pinned: TrackerLib::replace_item() itself contains a
 * realname-to-login substitution special case for 'u' fields with autoassign
 * or "item owner" set, gated on the 'user_selector_realnames_tracker' pref;
 * and TrackerLib::get_local_notifications() reads this field's 'notify'
 * option to build an email-recipient list (the method only computes the
 * list, it does not send mail, so it's safe to call directly).
 *
 * Not covered (waived):
 *  - Free-text realname resolution in extract_users()/find_best_user()
 *    (the "else" branch, not the "Name (login)" paren-form): depends on
 *    get_users_light(), which itself gates on the legacy $tiki_p_list_users /
 *    $tiki_p_admin globals and the 'user_show_realnames' pref — disproportionate
 *    fixture cost for a rarely-used input form. The paren-form path
 *    ("Real Name (login)") is deterministic and is pinned instead.
 *  - importRemoteField()/importRemote()/exportRemote(): external API sync,
 *    same rationale as the conformance-only external-service types.
 *  - getFacets(): iterates the entire real users_users table; structure only
 *    (not exercised — disproportionate for a DB-wide facet map).
 *  - Deep getFilterCollection() apply-condition behavior: needs the full
 *    search stack; only the control-type selection (dropdown vs multiselect)
 *    is pinned via the base testCapabilities().
 *  - The groupIds-restricted branches of renderInput()'s grouped widget
 *    (multiple=2) and getPossibleItemValues() both start from
 *    UsersLib::list_all_groups_with_permission(), which requires a real
 *    'group_view' permission grant — none exists in this bare test DB for
 *    any group, admin included (confirmed directly). Both branches degrade
 *    to their "no restriction" fallback; see
 *    testGetPossibleItemValuesFallsBackToUnrestrictedWithoutGroupViewPermission,
 *    which pins that fallback as current behavior rather than the intended
 *    restriction. Reproducing real permission grants is a permissions-
 *    subsystem fixture outside this session's scope.
 *
 * Known template quirks pinned (not fixed): rendering the grouped widget
 * (multiple=2, see testRenderInputGroupedWidgetRendersWithoutError and its
 * lazyload variant) triggers two pre-existing issues in
 * templates/trackerinput/userselector_grouped.tpl, unrelated to this test:
 * an "Undefined array key showRealname" notice (the template reads
 * $field.showRealname directly, but the option lives nested under
 * options_map — falls back to plain login display, harmless) and a Smarty
 * deprecation for the |implode modifier (Smarty recommends |join instead).
 *
 * Known defects (@group known-defect, excluded from the default suite):
 *  - testRenderInputLockedArmShouldEscapeValue: the autoassign "locked"
 *    render arm (user cannot change the value) builds its visible text with
 *    a plain implode(), not escaped — a stored value containing HTML is
 *    echoed unescaped; only the hidden-input value attribute is escaped.
 *    Confirmed unreachable through getFieldData()'s own input path
 *    (extract_users() drops unknown logins) — this type has no
 *    handleSave()/isValid() gate at all, so a dirty value can only get into
 *    storage the way legacy data, a bulk import, or a direct API write
 *    would put it there.
 *  - testRenderInnerOutputHistoryContextOnlyRemovesMatchingFeedback: the
 *    history-context Feedback cleanup in renderInnerOutput() is meant to
 *    remove only the "User "X" not found" message it just generated, but
 *    `preg_match($regex, $msg) !== false` is true for a plain non-match too
 *    (preg_match returns int 0, not false, when nothing matches) — so it
 *    removes ALL error-type feedback whenever history mode renders. Traced
 *    via git log -L to a 2024 refactor (commit 2dfbd9cb) that replaced a
 *    correct in_array() exact-match with this broken preg_match() check —
 *    a real regression, not a deliberate change.
 *
 * @group integration
 * @group tracker
 */
class UserSelectorTest extends FieldCharacterizationTestCase
{
    private const USER_ALICE = 'charuseralice_ustest';
    private const USER_BOB = 'charuserbob_ustest';
    private const GROUP_NAME = 'CharUserGroupUstest';

    private static $groupId;
    private static $ownershipTrackerId;
    private static $ownershipFieldIds = [];

    protected static function typeCode(): string
    {
        return 'u';
    }

    protected static function createSupportFixtures(): void
    {
        $userlib = \TikiLib::lib('user');

        // Defensive removal first: idempotent against a prior crashed run
        // leaving these fixed-name fixtures behind (same pattern as
        // MembershipTest).
        $userlib->remove_user(self::USER_ALICE);
        $userlib->remove_user(self::USER_BOB);
        $userlib->remove_group(self::GROUP_NAME);

        $userlib->add_user(self::USER_ALICE, 'P4ssw0rd!x', 'alice@example.test');
        $userlib->add_user(self::USER_BOB, 'P4ssw0rd!x', 'bob@example.test');
        $userlib->set_user_preference(self::USER_ALICE, 'realName', 'Alice A');

        $userlib->add_group(self::GROUP_NAME, '', '', 0, 0, '', '', 0, '', 0, 0, 'n', 45);
        $userlib->assign_user_to_group(self::USER_ALICE, self::GROUP_NAME);
        self::$groupId = (int) \TikiDb::get()->table('users_groups')->fetchOne('id', ['groupName' => self::GROUP_NAME]);

        self::$ownershipTrackerId = self::$fixtures->createTracker(
            'Characterization u ownership ' . uniqid(),
            '',
            ['userCanTakeOwnership' => 'y']
        );
        self::$ownershipFieldIds = self::$fixtures->addFields(self::$ownershipTrackerId, [[
            'name' => 'Owner field',
            'permName' => 'charUserOwnership',
            'type' => 'u',
            'options' => ['autoassign' => 1],
        ]]);
    }

    public static function tearDownAfterClass(): void
    {
        $userlib = \TikiLib::lib('user');
        $userlib->remove_user(self::USER_ALICE);
        $userlib->remove_user(self::USER_BOB);
        $userlib->remove_group(self::GROUP_NAME);
        parent::tearDownAfterClass();
    }

    protected static function extraPrefs(): array
    {
        // 'user_show_realnames' gates clean_user()/smarty_modifier_username()'s
        // actual realName substitution — the field's own 'showRealname' option
        // description says as much ("Requires global setting..."). Without it,
        // realName is never looked up (an unset $realname would even trigger an
        // undefined-variable warning in clean_user()).
        return [
            'user_selector_realnames_tracker' => 'y',
            'user_show_realnames' => 'y',
        ];
    }

    protected static function fieldSpecs(): array
    {
        return [
            [
                'name' => 'User selector field',
                'permName' => 'charUser',
                'type' => 'u',
            ],
            [
                'name' => 'User selector multiple',
                'permName' => 'charUserMulti',
                'type' => 'u',
                'options' => ['multiple' => 1],
            ],
            [
                'name' => 'User selector creator autoassign',
                'permName' => 'charUserCreator',
                'type' => 'u',
                'options' => ['autoassign' => 1],
            ],
            [
                'name' => 'User selector modifier autoassign',
                'permName' => 'charUserModifier',
                'type' => 'u',
                'options' => ['autoassign' => 2],
            ],
            [
                'name' => 'User selector modifier autoassign multiple',
                'permName' => 'charUserModifierMulti',
                'type' => 'u',
                'options' => ['autoassign' => 2, 'multiple' => 1],
            ],
            [
                'name' => 'User selector creator autoassign with override group',
                'permName' => 'charUserCanChange',
                'type' => 'u',
                'options' => ['autoassign' => 1, 'canChangeGroupIds' => [self::$groupId ?? -1]],
            ],
            [
                'name' => 'User selector realname display',
                'permName' => 'charUserRealname',
                'type' => 'u',
                'options' => ['showRealname' => 1],
            ],
            [
                'name' => 'User selector grouped',
                'permName' => 'charUserGrouped',
                'type' => 'u',
                'options' => ['multiple' => 2, 'lazyload' => 0, 'groupIds' => [self::$groupId ?? -1]],
            ],
            [
                'name' => 'User selector grouped lazyload',
                'permName' => 'charUserGroupedLazy',
                'type' => 'u',
                'options' => ['multiple' => 2, 'lazyload' => 1],
            ],
            [
                'name' => 'User selector notify',
                'permName' => 'charUserNotify',
                'type' => 'u',
                'options' => ['notify' => 1],
            ],
            [
                'name' => 'User selector creator autoassign with realname',
                'permName' => 'charUserCreatorRealname',
                'type' => 'u',
                'options' => ['autoassign' => 1, 'showRealname' => 1],
            ],
        ];
    }

    public static function inputCaseProvider(): array
    {
        return [
            'single known user submitted' => [
                ['{{ins}}' => self::USER_ALICE],
                ['value' => self::USER_ALICE],
            ],
            'absent input falls back to stored (false when nothing stored yet, not "")' => [
                [],
                ['value' => false],
            ],
            'unknown username is dropped silently (Feedback::error, not fatal)' => [
                ['{{ins}}' => 'no_such_user_at_all'],
                ['value' => ''],
            ],
            '"0" username is dropped without even an error (falsy check)' => [
                ['{{ins}}' => ['0']],
                ['value' => ''],
            ],
            'array input on a non-multiple field uses only the first resolved user' => [
                ['{{ins}}' => [self::USER_ALICE, self::USER_BOB]],
                ['value' => self::USER_ALICE],
            ],
        ];
    }

    /**
     * The "Name (login)" paren-form lookup only fires when realnames_check is
     * true (pref 'user_selector_realnames_tracker' == 'y' AND the field's own
     * 'showRealname' option is on) — the base field under test has
     * showRealname off, so this needs the dedicated charUserRealname field.
     */
    public function testParenFormResolvesToLoginWhenRealnamesCheckEnabled(): void
    {
        $handler = $this->handlerWithoutItem('charUserRealname');
        $data = $handler->getFieldData([
            $handler->getInsertId() => 'Some Real Name (' . self::USER_BOB . ')',
        ]);
        $this->assertSame(self::USER_BOB, $data['value']);
    }

    public static function roundTripCaseProvider(): array
    {
        return [
            'single user' => [self::USER_ALICE, self::USER_ALICE, self::USER_ALICE],
            'empty' => ['', '', ''],
        ];
    }

    public static function storedFallbackCaseProvider(): array
    {
        return [
            'stored value shown back when input absent' => [
                self::USER_BOB,
                ['value' => self::USER_BOB],
            ],
        ];
    }

    /**
     * No handleSave() override, so the persistence layer has no create-vs-
     * update distinction — this row confirms update ≡ create (a plain
     * overwrite of the raw value). The interesting create-vs-update behavior
     * of this type lives in getFieldData()'s Creator autoassign (keyed on
     * getItemId(), not $oldValue) and is pinned by the dedicated autoassign
     * tests above.
     */
    public static function updateCaseProvider(): array
    {
        return [
            'update overwrites the raw value exactly like creation' => [
                self::USER_ALICE, self::USER_BOB, self::USER_BOB, self::USER_BOB,
            ],
        ];
    }

    public static function renderCaseProvider(): array
    {
        return [
            'single user renders in list box' => [
                self::USER_ALICE,
                self::USER_ALICE,
                // getHTMLFieldName() always appends '[]' for type 'u', even
                // when 'multiple' is off (pins current behavior).
                ['name="{{ins}}[]"'],
                [self::USER_ALICE],
            ],
        ];
    }

    // --- multiple=1 (CSV) specific behavior -------------------------------

    public function testMultipleCsvStorageAndBlankSentinelPoisonsIt(): void
    {
        $handler = $this->handlerWithoutItem('charUserMulti');

        $data = $handler->getFieldData($this->resolveRequestData(
            ['{{ins}}' => [self::USER_ALICE, self::USER_BOB]],
            $handler
        ));
        $this->assertSame(self::USER_ALICE . ',' . self::USER_BOB, $data['value']);

        // '-Blank (no data)-' does not error and does not get dropped like a
        // plain unknown username would: extract_users() resolves it to a
        // literal null array entry (pins current behavior), which
        // str_putcsv() then renders as a dangling trailing comma.
        $dirty = $handler->getFieldData($this->resolveRequestData(
            ['{{ins}}' => [self::USER_ALICE, '-Blank (no data)-']],
            $handler
        ));
        $this->assertSame(self::USER_ALICE . ',', $dirty['value']);
    }

    public function testMultipleCsvRoundTrip(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, [
            'charUserMulti' => self::USER_ALICE . ',' . self::USER_BOB,
        ]);
        $raw = \TikiLib::lib('trk')->get_tracker_item($itemId);
        $fieldId = self::$fieldIds['charUserMulti'];
        $this->assertSame(self::USER_ALICE . ',' . self::USER_BOB, $raw[$fieldId]);
    }

    // --- autoassign: Creator ------------------------------------------------

    public function testCreatorAutoassignOnNewItemUsesCurrentUser(): void
    {
        $handler = $this->handlerWithoutItem('charUserCreator');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $data = $handler->getFieldData([$handler->getInsertId() => 'ignored']);
            $this->assertSame(self::USER_BOB, $data['value']);
        });
    }

    public function testCreatorAutoassignOnExistingItemKeepsOriginalCreatorIgnoringRequest(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserCreator' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserCreator');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $data = $handler->getFieldData([$handler->getInsertId() => self::USER_BOB]);
            $this->assertSame(self::USER_ALICE, $data['value']);
        });
    }

    public function testCreatorAutoassignTakesOwnershipWhenTrackerAllowsItAndValueIsEmpty(): void
    {
        $itemId = self::$fixtures->createItem(self::$ownershipTrackerId, ['charUserOwnership' => '']);
        $handler = $this->handlerForItem($itemId, 'charUserOwnership');
        $this->withGlobalUser(self::USER_ALICE, function () use ($handler) {
            $data = $handler->getFieldData([$handler->getInsertId() => 'ignored']);
            $this->assertSame(self::USER_ALICE, $data['value']);
        });
    }

    // --- autoassign: Modifier -----------------------------------------------

    public function testModifierAutoassignAlwaysUsesCurrentUserSingle(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserModifier' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserModifier');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $data = $handler->getFieldData([$handler->getInsertId() => 'ignored']);
            $this->assertSame(self::USER_BOB, $data['value']);
        });
    }

    public function testModifierAutoassignMultipleAppendsCurrentUserIfAbsent(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserModifierMulti' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserModifierMulti');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $data = $handler->getFieldData([$handler->getInsertId() => 'ignored']);
            $this->assertSame(self::USER_ALICE . ',' . self::USER_BOB, $data['value']);
        });
    }

    public function testModifierAutoassignMultipleDoesNotDuplicateCurrentUser(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, [
            'charUserModifierMulti' => self::USER_ALICE . ',' . self::USER_BOB,
        ]);
        $handler = $this->handlerForItem($itemId, 'charUserModifierMulti');
        $this->withGlobalUser(self::USER_ALICE, function () use ($handler) {
            $data = $handler->getFieldData([$handler->getInsertId() => 'ignored']);
            $this->assertSame(self::USER_ALICE . ',' . self::USER_BOB, $data['value']);
        });
    }

    // --- canChangeGroupIds permission override ------------------------------

    public function testCanChangeGroupIdsOverridesAutoassignForMembers(): void
    {
        $handler = $this->handlerWithoutItem('charUserCanChange');
        $this->withGlobalUser(self::USER_ALICE, function () use ($handler) {
            // Alice is in the override group: request value is honored as if autoassign were None.
            $data = $handler->getFieldData([$handler->getInsertId() => self::USER_BOB]);
            $this->assertSame(self::USER_BOB, $data['value']);
        });
    }

    public function testCanChangeGroupIdsDoesNotOverrideForNonMembers(): void
    {
        $handler = $this->handlerWithoutItem('charUserCanChange');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            // Bob is NOT in the override group: falls through to autoassign (Creator, new item -> current user).
            $data = $handler->getFieldData([$handler->getInsertId() => self::USER_ALICE]);
            $this->assertSame(self::USER_BOB, $data['value']);
        });
    }

    // --- getAutoSyncInlineEditFieldData() (AutoSyncable) --------------------

    public function testAutoSyncInlineEditFieldData(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserModifier' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserModifier');
        $ins = $handler->getInsertId();

        $this->assertSame([], $handler->getAutoSyncInlineEditFieldData([]), 'no "edit" key -> no-op');
        $this->assertSame(
            [],
            $handler->getAutoSyncInlineEditFieldData(['edit' => 'inline', $ins => self::USER_BOB]),
            'ins_id present in request -> no-op (field was submitted normally)'
        );

        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $this->assertSame(
                ['value' => self::USER_BOB],
                $handler->getAutoSyncInlineEditFieldData(['edit' => 'inline']),
                'inline edit with the field not submitted -> recompute autoassign value'
            );
        });
    }

    // --- handleClone() -------------------------------------------------------

    public function testHandleCloneStrictKeepsValueUnchanged(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserModifier' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserModifier');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $this->assertSame(['value' => self::USER_ALICE], $handler->handleClone(true));
        });
    }

    public function testHandleCloneNonStrictReassignsForAutoassign(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserModifier' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserModifier');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $this->assertSame(['value' => self::USER_BOB], $handler->handleClone(false));
        });
    }

    public function testHandleCloneNonStrictLeavesNonAutoassignedValueAlone(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $this->assertSame(['value' => self::USER_ALICE], $handler->handleClone(false));
        });
    }

    // --- render: locked autoassign arm (no override) ------------------------

    /**
     * Known defect: the "locked" render arm (autoassign on, current user
     * cannot override) builds its visible text with a plain implode() of the
     * stored users, not escaped — only the hidden-input value attribute is
     * escaped via htmlspecialchars(). A stored value containing HTML is
     * echoed unescaped into the page.
     *
     * Intended behavior: the visible text must be HTML-escaped exactly like
     * the hidden input already is.
     *
     * Current (wrong) behavior: it is not — this assertion fails today.
     *
     * How found: characterizing the locked arm (autoassign Creator, no
     * override permission) requires a stored value, and this type has no
     * handleSave()/isValid() gate at all (confirmed by grepping the class and
     * AbstractItemField), so createItem() can store anything verbatim — the
     * same way legacy data, a bulk import, or an API write could. Storing
     * '<script>alert(1)</script>' through that path and rendering the locked
     * arm reproduces the gap directly; no services-path/UI difference to
     * account for since there is no validation layer to diverge from.
     *
     * @group known-defect
     */
    public function testRenderInputLockedArmShouldEscapeValue(): void
    {
        $dirty = '<script>alert(1)</script>';
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserCreator' => $dirty]);
        $handler = $this->renderHandlerForItem($itemId, 'charUserCreator');

        $this->withGlobalUser(self::USER_BOB, function () use ($handler, $dirty) {
            $html = $handler->renderInput();
            $this->assertStringNotContainsString($dirty, $html, 'stored value must not appear unescaped in the rendered HTML');
            $this->assertStringContainsString(htmlspecialchars($dirty), $html, 'visible text must be HTML-escaped');
        });
    }

    public function testRenderInputLockedArmMultipleValuesHiddenInputEscaped(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, [
            'charUserCreator' => self::USER_ALICE . ',' . self::USER_BOB,
        ]);
        $handler = $this->renderHandlerForItem($itemId, 'charUserCreator');

        $this->withGlobalUser('someone_else_' . uniqid(), function () use ($handler) {
            $html = $handler->renderInput();
            $this->assertStringContainsString(
                htmlspecialchars(self::USER_ALICE . ',' . self::USER_BOB),
                $html,
                'multi-value hidden input is CSV-escaped'
            );
        });
    }

    // --- render: showRealname + realnames pref, grouped widget --------------

    public function testRenderInputRealnameDisplaysUsernameSuffix(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserRealname' => self::USER_ALICE]);
        $handler = $this->renderHandlerForItem($itemId, 'charUserRealname');
        $html = $handler->renderInput();
        $this->assertStringContainsString(self::USER_ALICE, $html);
    }

    public function testRenderInnerOutputRealnameUsesDisplayName(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserRealname' => self::USER_ALICE]);
        // renderInnerOutput() reads getConfiguration('value'), which is only
        // populated on a handler prepared the production way (renderHandlerForItem
        // merges getFieldData() into the definition) — a plain handlerForItem()
        // handler has no 'value' key in its definition and renders as empty.
        $handler = $this->renderHandlerForItem($itemId, 'charUserRealname');
        $method = new \ReflectionMethod($handler, 'renderInnerOutput');
        $csv = $method->invoke($handler, ['list_mode' => 'csv']);
        $this->assertSame('Alice A', $csv, 'showRealname swaps login for the realName preference (pins current behavior)');
    }

    public function testRenderInnerOutputEmptyValueReturnsEmptyString(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => '']);
        $handler = $this->renderHandlerForItem($itemId, 'charUser');
        $method = new \ReflectionMethod($handler, 'renderInnerOutput');
        $this->assertSame('', $method->invoke($handler, ['list_mode' => 'csv']));
    }

    /**
     * Known defect: the history-context Feedback cleanup in
     * renderInnerOutput() is supposed to remove only the specific
     * "User "X" not found" error it just generated, leaving any other
     * queued feedback alone. Instead it removes ALL error-type feedback
     * whenever history mode renders — because the match check is
     * `preg_match($regex, $msg) !== false`, and preg_match() returns 0 (int)
     * on a plain non-match, never `false` (that's reserved for a regex
     * engine error) — so the condition is true for essentially every call.
     *
     * Intended behavior: only feedback matching the "user not found" pattern
     * is removed; unrelated errors survive.
     *
     * Current (wrong) behavior: every error-type feedback is removed
     * regardless of content — this assertion fails today.
     *
     * How found: git log -L on this code showed the 2023 version used exact
     * in_array() matching (correct, narrow); a 2024 refactor
     * (commit 2dfbd9cb, "Tracker history: Removed feedback error for
     * deleted/updated users") switched to preg_match() but got the
     * false-vs-zero comparison wrong — a real regression, not a deliberate
     * behavior change.
     *
     * @group known-defect
     */
    public function testRenderInnerOutputHistoryContextOnlyRemovesMatchingFeedback(): void
    {
        $previousSession = $_SESSION['tikifeedback'] ?? null;
        unset($_SESSION['tikifeedback']);
        \Feedback::error(tr('User "%0" not found', 'ghost_user'));
        \Feedback::error('unrelated error');

        try {
            $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
            $handler = $this->renderHandlerForItem($itemId, 'charUser');
            $method = new \ReflectionMethod($handler, 'renderInnerOutput');
            $method->invoke($handler, ['list_mode' => 'csv', 'history' => true]);

            $remainingMessages = array_column($_SESSION['tikifeedback'] ?? [], 'mes');
            $this->assertNotContains(['User "ghost_user" not found'], $remainingMessages);
            $this->assertContains(['unrelated error'], $remainingMessages);
        } finally {
            if ($previousSession === null) {
                unset($_SESSION['tikifeedback']);
            } else {
                $_SESSION['tikifeedback'] = $previousSession;
            }
        }
    }

    public function testRenderInputGroupedWidgetRendersWithoutError(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserGrouped' => self::USER_ALICE]);
        $handler = $this->renderHandlerForItem($itemId, 'charUserGrouped');
        $html = $handler->renderInput();
        $this->assertIsString($html);
    }

    public function testRenderInputGroupedLazyloadWidgetRendersWithoutError(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserGroupedLazy' => self::USER_ALICE]);
        $handler = $this->renderHandlerForItem($itemId, 'charUserGroupedLazy');
        $html = $handler->renderInput();
        $this->assertIsString($html);
    }

    // --- indexing: identifier vs multivalue ----------------------------------

    public function testGetDocumentPartIdentifierForSingleMultivalueForMultiple(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $typeFactory = new \Search_Type_Factory_Direct();
        $part = $handler->getDocumentPart($typeFactory);
        $this->assertSame(self::USER_ALICE, $part[$handler->getBaseKey()]->getValue());

        $itemId2 = self::$fixtures->createItem(self::$trackerId, [
            'charUserMulti' => self::USER_ALICE . ',' . self::USER_BOB,
        ]);
        $handler2 = $this->handlerForItem($itemId2, 'charUserMulti');
        $part2 = $handler2->getDocumentPart($typeFactory);
        $this->assertContains(self::USER_ALICE, $part2[$handler2->getBaseKey()]->getValue());
        $this->assertContains(self::USER_BOB, $part2[$handler2->getBaseKey()]->getValue());
    }

    // --- checkGroupsExist() ---------------------------------------------------

    public function testCheckGroupsExistFiltersOutBogusIds(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserGrouped' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserGrouped');
        $result = $handler->checkGroupsExist([self::$groupId, 999999999]);
        $this->assertSame([self::$groupId], $result);
    }

    // --- out-of-class: replace_item() realname substitution ------------------

    public function testReplaceItemDoesNotSubstituteRealnameWithoutListUsersPermission(): void
    {
        // Exercises TrackerLib::replace_item() directly (not through the
        // fixture helper) because the substitution reads options_map off the
        // raw field array assembled the same way the production save path does.
        //
        // The substitution's realname lookup goes through
        // UsersLib::get_users_light(), which itself silently returns [] unless
        // $tiki_p_list_users == 'y' or $tiki_p_admin == 'y' — legacy
        // permission-cache globals normally set by tiki-setup.php's permission
        // bootstrap for a real logged-in admin, but absent in this harness.
        // Result: with neither global set, the "realname -> login" substitution
        // documented in the code is a silent no-op (pins current behavior).
        $itemId = $this->saveCharUserCreatorWithRawValue('Alice A');
        $raw = \TikiLib::lib('trk')->get_tracker_item($itemId);
        $this->assertSame(
            'Alice A',
            $raw[self::$fieldIds['charUserCreator']],
            'no admin/list-users permission -> realname is NOT substituted (pins current behavior)'
        );
        \TikiLib::lib('trk')->remove_tracker_item($itemId);
    }

    public function testReplaceItemSubstitutesRealnameForLoginGivenListUsersPermission(): void
    {
        // Same save, but with the legacy permission-cache global set the way a
        // real admin session would have it -> the documented substitution
        // actually fires.
        global $tiki_p_admin;
        $previous = $tiki_p_admin ?? null;
        $tiki_p_admin = 'y';
        try {
            $itemId = $this->saveCharUserCreatorWithRawValue('Alice A');
        } finally {
            $tiki_p_admin = $previous;
        }

        $raw = \TikiLib::lib('trk')->get_tracker_item($itemId);
        $this->assertSame(
            self::USER_ALICE,
            $raw[self::$fieldIds['charUserCreator']],
            'realname "Alice A" is substituted for the matching login given list-users permission'
        );
        \TikiLib::lib('trk')->remove_tracker_item($itemId);
    }

    private function saveCharUserCreatorWithRawValue(string $value): int
    {
        $definition = \Tracker_Definition::get(self::$trackerId, false);
        $data = [];
        foreach ($definition->getFields() as $field) {
            $data[] = array_merge($field, ['value' => $field['permName'] === 'charUserCreator' ? $value : '']);
        }
        $itemId = \TikiLib::lib('trk')->replace_item(self::$trackerId, 0, ['data' => $data], 'o');
        $this->assertGreaterThan(0, $itemId);
        return (int) $itemId;
    }

    // --- out-of-class: get_local_notifications() ------------------------------

    public function testGetLocalNotificationsPicksUpNotifyOption(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserNotify' => self::USER_ALICE]);
        $emails = \TikiLib::lib('trk')->get_local_notifications($itemId);
        $this->assertNotEmpty(array_filter($emails, function ($e) {
            return $e['email'] === 'alice@example.test';
        }));
    }

    public function testCapabilities(): void
    {
        // Overridden: the base testCapabilities() asserts an exact
        // getPossibleItemValues() match, but this type enumerates the whole
        // real users table (beyond our two fixture users) — assert our
        // fixture users are present instead of an exact map.
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUser');

        $this->assertInstanceOf(\Tracker\Tabular\Schema::class, $handler->getTabularSchema());
        $this->assertNotEmpty($handler->getTabularSchema()->getColumns());

        $this->assertNotNull($handler->getFilterCollection());

        $possible = $handler->getPossibleItemValues();
        $this->assertArrayHasKey(self::USER_ALICE, $possible);
        $this->assertArrayHasKey(self::USER_BOB, $possible);
        $this->assertFalse($handler->canHaveMultipleValues());

        $multiHandler = $this->handlerForItem($itemId, 'charUserMulti');
        $this->assertTrue($multiHandler->canHaveMultipleValues());
    }

    // --- addValue()/removeValue() (ItemFieldInterface; used by search bulk
    // field-modify actions and FieldController) -----------------------------

    public function testAddValueAppendsUserIfAbsent(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $this->assertSame(self::USER_ALICE . ',' . self::USER_BOB, $handler->addValue(self::USER_BOB));
    }

    public function testAddValueDoesNotDuplicate(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $this->assertSame(self::USER_ALICE, $handler->addValue(self::USER_ALICE));
    }

    public function testAddValueOnEmptyStartsFresh(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => '']);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $this->assertSame(self::USER_ALICE, $handler->addValue(self::USER_ALICE));
    }

    public function testRemoveValueDropsUser(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, [
            'charUser' => self::USER_ALICE . ',' . self::USER_BOB,
        ]);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $this->assertSame(self::USER_ALICE, $handler->removeValue(self::USER_BOB));
    }

    public function testRemoveValueOnEmptyIsNoop(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => '']);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $this->assertSame('', $handler->removeValue(self::USER_BOB));
    }

    // --- rendering an empty/unset value, and autoassign auto-filling it -----

    public function testRenderInputEmptyStoredValueRendersBlank(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => '']);
        $handler = $this->renderHandlerForItem($itemId, 'charUser');
        $html = $handler->renderInput();
        $this->assertIsString($html);
    }

    public function testRenderInputCreatorAutoassignEmptyStoredValueFillsInCurrentUser(): void
    {
        // No override permission -> locked arm; empty stored value + autoassign
        // Creator -> renderInput() fills $value with the current user before
        // the locked/unlocked split, so the "empty" case still shows someone.
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserCreator' => '']);
        $handler = $this->renderHandlerForItem($itemId, 'charUserCreator');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $html = $handler->renderInput();
            $this->assertStringContainsString(self::USER_BOB, $html);
        });
    }

    public function testRenderInputLockedArmRealnameBranch(): void
    {
        // Locked arm + showRealname=1: the visible text is built from
        // smarty_modifier_username() instead of the raw login.
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserCreatorRealname' => self::USER_ALICE]);
        $handler = $this->renderHandlerForItem($itemId, 'charUserCreatorRealname');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $html = $handler->renderInput();
            // realName preference is set for alice ("Alice A"); login still
            // appears in parens per smarty_modifier_username()'s own format.
            $this->assertStringContainsString('Alice A', $html);
        });
    }

    // --- handleClone() on a multiple-value autoassigned field ---------------

    public function testHandleCloneNonStrictMultipleAppendsCurrentUserIfAbsent(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserModifierMulti' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserModifierMulti');
        $this->withGlobalUser(self::USER_BOB, function () use ($handler) {
            $this->assertSame(
                ['value' => self::USER_ALICE . ',' . self::USER_BOB],
                $handler->handleClone(false)
            );
        });
    }

    // --- getLabel() (facet render callback) ---------------------------------

    public function testGetLabelUsesRealnameRegardlessOfThisFieldsShowRealnameOption(): void
    {
        // getLabel() passes $this->getOption('showRealname') to clean_user() as
        // "force_check_realnames", but clean_user() checks the REALNAME
        // regardless once the global 'user_show_realnames' pref is 'y' (which
        // this test class sets) — so a user who has a realName preference set
        // gets it here even on a field with showRealname off (pins current
        // behavior; the field-level option only matters when the global pref
        // is off).
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $this->assertSame('Alice A', $handler->getLabel(self::USER_ALICE));
        $this->assertSame(self::USER_BOB, $handler->getLabel(self::USER_BOB), 'no realName set for bob -> falls back to login');
    }

    // --- getProvidedFieldTypes()/getGlobalFields() ---------------------------

    public function testProvidedFieldTypesAndGlobalFields(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUser' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUser');
        $baseKey = $handler->getBaseKey();

        $this->assertSame(
            [$baseKey => 'identifier', "{$baseKey}_text" => 'sortable', "{$baseKey}_unstemmed" => 'simpletext'],
            $handler->getProvidedFieldTypes()
        );
        $this->assertSame([$baseKey => true], $handler->getGlobalFields(), 'showRealname off -> no extra _text global field');

        $realnameHandler = $this->handlerForItem($itemId, 'charUserRealname');
        // Field is different from $itemId's own value for charUserRealname
        // (not set on this item) but getGlobalFields() only depends on options.
        $realnameBaseKey = $realnameHandler->getBaseKey();
        $this->assertSame(
            [$realnameBaseKey => true, "{$realnameBaseKey}_text" => true],
            $realnameHandler->getGlobalFields(),
            'showRealname on -> adds the _text global field'
        );

        $multiHandler = $this->handlerForItem($itemId, 'charUserMulti');
        $this->assertSame('multivalue', $multiHandler->getProvidedFieldTypes()[$multiHandler->getBaseKey()]);
    }

    public function testGetDocumentPartRealnameBranch(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserRealname' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserRealname');
        $part = $handler->getDocumentPart(new \Search_Type_Factory_Direct());
        $this->assertSame('Alice A', $part[$handler->getBaseKey() . '_text']->getValue());
    }

    // --- getPossibleItemValues() restricted to groupIds ----------------------

    /**
     * Investigated as a suspicious value: expected groupIds to restrict
     * getPossibleItemValues() to the allowed group's members. Instead, even
     * with $GLOBALS['user'] set to 'admin', list_all_groups_with_permission()
     * (which every group must pass through before the groupIds filter is
     * ever applied) returns empty in this bare test DB — no group has a
     * 'group_view' permission grant recorded at all, admin included. Code
     * falls through to "$groups is empty -> list everyone unrestricted".
     * Confirmed via TikiLib::lib('user')->list_all_groups_with_permission()
     * directly: empty regardless of acting user. This is a real permission-
     * grants gap in the harness, not a groupIds bug — pinning the fallback
     * as current behavior rather than the intended restriction, since
     * reproducing real 'group_view' grants is a permissions-subsystem
     * fixture outside this session's scope.
     */
    public function testGetPossibleItemValuesFallsBackToUnrestrictedWithoutGroupViewPermission(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, ['charUserGrouped' => self::USER_ALICE]);
        $handler = $this->handlerForItem($itemId, 'charUserGrouped');
        $possible = $handler->getPossibleItemValues();
        $this->assertArrayHasKey(self::USER_ALICE, $possible);
        $this->assertArrayHasKey(
            self::USER_BOB,
            $possible,
            'bob is NOT a member of the configured groupIds group, but appears anyway: '
            . 'groupIds silently has no effect without any group_view permission grant (pins current behavior)'
        );
    }

    // --- getTabularSchema() render/parse transforms --------------------------

    public function testTabularSchemaColumnTransforms(): void
    {
        $itemId = self::$fixtures->createItem(self::$trackerId, [
            'charUserMulti' => self::USER_ALICE . ',' . self::USER_BOB,
        ]);
        $handler = $this->handlerForItem($itemId, 'charUserMulti');
        $permName = 'charUserMulti';
        $columns = $handler->getTabularSchema()->getColumns();

        $find = function (string $mode) use ($columns, $permName) {
            foreach ($columns as $column) {
                if ($column->is($permName, $mode)) {
                    return $column;
                }
            }
            $this->fail("no tabular column found for mode '$mode'");
        };

        // 'userlink'/'username-itemlink'/'realname-itemlink' wrap each user in
        // an HTML link (smarty_modifier_userlink()/smarty_function_object_link())
        // whose href embeds a real, non-deterministic DB userId — assert
        // substrings, not an exact match. 'userlink' also swaps in the
        // realName (global 'user_show_realnames' pref is on for this class).
        $userlinkHtml = $find('userlink')->render(self::USER_ALICE . ',' . self::USER_BOB);
        $this->assertStringContainsString('Alice A', $userlinkHtml);
        $this->assertStringContainsString(self::USER_BOB, $userlinkHtml);
        $this->assertStringContainsString('class="userlink', $userlinkHtml);

        $this->assertSame(
            'Alice A, ' . self::USER_BOB,
            $find('realname')->render(self::USER_ALICE . ',' . self::USER_BOB)
        );

        $usernameItemlinkHtml = $find('username-itemlink')->render(
            self::USER_ALICE . ',' . self::USER_BOB,
            ['itemId' => $itemId]
        );
        $this->assertStringContainsString(self::USER_ALICE, $usernameItemlinkHtml, 'plain login as link title');
        $this->assertStringContainsString(self::USER_BOB, $usernameItemlinkHtml);

        $realnameItemlinkHtml = $find('realname-itemlink')->render(
            self::USER_ALICE . ',' . self::USER_BOB,
            ['itemId' => $itemId]
        );
        $this->assertStringContainsString('Alice A', $realnameItemlinkHtml, 'realname as link title');
        $this->assertStringContainsString(self::USER_BOB, $realnameItemlinkHtml);

        $usernameColumn = $find('username');
        $this->assertSame('a,b', $usernameColumn->render(['a', 'b']), 'array value is comma-joined');
        $this->assertSame('plain', $usernameColumn->render('plain'), 'scalar value passes through unchanged');

        $info = [];
        $usernameColumn->parseInto($info, self::USER_BOB);
        $this->assertSame([$permName => self::USER_BOB], $info['fields']);
    }

    private function withGlobalUser(string $user, callable $fn): void
    {
        $previous = $GLOBALS['user'] ?? null;
        $GLOBALS['user'] = $user;
        try {
            $fn();
        } finally {
            if ($previous === null) {
                unset($GLOBALS['user']);
            } else {
                $GLOBALS['user'] = $previous;
            }
        }
    }
}
