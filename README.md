Typeform -> IP.Board Importer
=============================
This script automatically posts moderator applications to a forum section for group review.

Installation
------------
1. Clone the repo.
2. `composer install`
3. `cp config.sample.php config.php`
4. Fill in your API keys and other values in `config.php`. The file's comments contain detailed instructions.
5. Ensure the user the script is running as has permission to write in the repo root.
6. Run it with `php typeform-ipb-import.php`!

Automatically post new apps (recommended!)
------------------------------------------
1. `crontab -e` as the user you're running the script as
2. Add the following line: `* * * * * /path/to/php /path/to/repo/root/typeform-ipb-import/typeform-ipb-import.php`
3. Save the new crontab!
