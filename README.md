# PHP-STV

Parse vote results of RFCs using a Single Transferable Vote
on wiki.php.net and determine the winning candidate(s). 

## Details

Uses Droop quota formula. When quota has not been reached, the candidate(s)
with the fewest votes are eliminated. No tie-breaking logic is implemented.

## Requirements

PHP 8.0+ with DOM extension.

## CLI usage

Set the appropriate parameters in **stv.php**, then run the script with `php stv.php`.

## Development

Before editing the code, run `composer install`. As part of development, run tests
with `composer test`, and perform static analysis with `composer analyze`.

## License

BSD 3-Clause
