# Creating a Dummylander Release

When releasing a new version:

 * Update version number to
   * `src/backend/lib/global_consts.php`
   * `test/backend/lib/TestHelpers.php`
 * Run `make clean && make build && make serve`
 * Make one GET to create default page
 * `md5sum dist/data/content.json` and place the MD5 to `test/backend/int/createfiles_test.php`
 * Run `make test-integration`
 * Commit following files as "Build X.X", where X.X is next version number
   * `dist/index.php`
   * `src/backend/lib/global_consts.php`
   * `test/backend/int/createfiles_test.php`
   * `test/backend/lib/TestHelpers.php`
 * `git push`
 * Create a pull request from working branch to `master` (GitHub UI)
 * Make sure the tests pass (GitHub UI)
 * Merge pull request (GitHub UI)

