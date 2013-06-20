# How to contribute

## Coding convention

This tool follows [PSR-2 Coding standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md).
## Testing

### Development workflow

My development workflow make use of grunt watch plugin, that will run phpunit automatically after each file save. Grunt is also used to generate the test coverage.

### Unit Tests

All pull request should not break existing tests. You're more than welcome to write additional tests. There is a grunt task to run the tests :

    grunt phpunit

Without grunt, just run :

    phpunit tests

Or you can also run tests after each file edition :

	grunt watch

### Test coverage

Use the coverage grunt task to generate code coverage.

    grunt coverage

Without grunt, use :

	phpunit tests


The coverage reports is in build/coverage/index.html.
