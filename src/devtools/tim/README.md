# Tiki Instance Manager Script (`tim/`)

TIM is a toolkit for provisioning and operating disposable
Tiki instances. It assumes a dedicated Unix host with Apache, MySQL, Git,
system-level directories, and suitable privileges. Configure `tim/tim.conf` and
install the components in the locations expected by `tim/tim-common` before use.

- `tim/tim` is the main dispatcher. It supports `create`, `destroy`, `snapshot`,
  `debug`, `info`, `update`, and `reset` commands.
- `tim/tim-create` creates an instance from a Git repository and branch.
- `tim/tim-snapshot` creates compressed database and filesystem snapshots.
- `tim/tim-cron` refreshes cached Git branches and archives. It performs hard
  resets and cleans inside the configured `GIT_CACHE` checkouts.
- `tim/tim-ssh` is a restricted-login-shell wrapper around `tim`.
- `tim/tim-common` contains shared configuration, logging, and instance helper
  functions and is sourced by the executable scripts.
- `tim/tim.conf` is the configuration template for users, groups, web root, Git
  cache, lock directory, and installation prefix.
- `tim/apache.conf` is the corresponding Apache configuration template.

Show the main command's built-in usage with:

```bash
bash src/devtools/tim/tim -h
bash src/devtools/tim/tim-create -h
bash src/devtools/tim/tim-snapshot -h
```

# More Tiki developer scripts
Please check out the [here](../README.md)

