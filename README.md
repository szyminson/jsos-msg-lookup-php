# jsos-msg-lookup
Read JSOS messages straight from your email client!
## Dependencies
* https://github.com/PHPMailer/PHPMailer
* https://simplehtmldom.sourceforge.io/
## Required PHP extensions
* `php-curl`
* `php-imap`
## Usage
1. `$ cp config.example.php config.php`
2. Edit `config.php` to match your credentials.
3. `$ php script.php`
4. Check your [inbox](https://student.pwr.edu.pl) :)
## Additional notes
This is the simplest version of the script. I'm currently working on a new, better one. It will use Composer to manage dependencies and I'm going to create a Dockerfile so you will be able to run it in a container with automated execution (cron job) of the script every minute just to make sure you won't miss any JSOS message. Everything in one command. Stay tuned!
