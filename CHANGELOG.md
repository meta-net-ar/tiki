# Tiki Changelog

The full changelog is generated dynamically, It is not stored in the repository to keep the repository clean and avoid committing generated files.

## Generated Changelog

* Latest generated changelog (CI artifact):  
  https://gitlab.com/tikiwiki/tiki/-/jobs/artifacts/master/raw/CHANGELOG.generated.md?job=tiki-package


## Official Release Notes
* Latest release: https://doc.tiki.org/Tiki30

## Generate Locally

Run:

```sh
php src/ci/generate_changelog.php --help
```
This will generate a readable summary of changes.


## Notes

The repository does not store generated changelog files.
  - It is very large (often 1000+ lines) and hard to read
  - It creates unnecessary noise in Git history
  - It is generated and would overwrite any manual edits
The script output is the source used for release notes.