default:
  just --list

# format sources
format:
  ./vendor/bin/php-cs-fixer fix

# run tests
test:
  ./vendor/bin/phpunit 

# run tests with coverage
coverage:
  export XDEBUG_MODE=coverage
  ./vendor/bin/phpunit --coverage-html reports

# run tests with coverage and open the report
coverage-show:
  export XDEBUG_MODE=coverage
  ./vendor/bin/phpunit --coverage-html reports
  chromium reports/index.html
