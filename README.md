# jsos-msg-lookup
Read JSOS messages straight from your email client!
## Dependencies
* https://github.com/PHPMailer/PHPMailer
* https://simplehtmldom.sourceforge.io/
## Required PHP extensions
* `php-curl`
* `php-imap`
## Usage
### Manually
1. `$ cp config.example.php config.php`
2. Edit `config.php` to match your credentials.
3. `$ php script.php`
4. Check your [inbox](https://student.pwr.edu.pl) :)
### Docker
This docker container will run the script every minute automatically. All you have to do is to start it with this command:
```
docker run -d -e "jsosu=pwrXXXXXX" -e "jsosp=jsospass" -e "smailu=XXXXXX@student.pwr.edu.pl" -e "smailp=smailpass" --rm --name jml szyminson/jsos-msg-lookup:v1.1.0
```
You can optionally build the container on your own using this repo's Dockerfile.
#### Note
If you prefer to store your credentials in `config.php` instead of using env variables - you can simply mount your config file to the container under `/var/jml/config.php` path. 
## Additional notes
This is the simplest version of the script. I'm currently working on a new, better one. It will be an object oriented Composer project. Stay tuned!
