<?php

// TC07 setup: no data to create. It exists so the paired teardown runs, because
// useSuiteFixtures only tears down when a setup loaded. The spec creates its key
// through the UI and deletes it on the happy path; the teardown is what keeps a
// failed journey from leaking a key into the instance.
// Output: JSON {prefix}

return function (array $input): array {
    return ['prefix' => 'TC07-'];
};
