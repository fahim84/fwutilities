<?php defined('BASEPATH') OR exit('No direct script access allowed');

error_reporting(-1);
ini_set('display_errors', 1);
class Backup extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function database()
    {
        $dbhost = $this->db->hostname;
        $dbuser = $this->db->username;
        $dbpass = $this->db->password;
        $dbname = $this->db->database;

        $content = "<?php \n";

        $content .= '$dbhost = "'.$dbhost.'"; '." \n";
        $content .= '$dbuser = "'.$dbuser.'"; '." \n";
        $content .= '$dbpass = "'.$dbpass.'"; '." \n";
        $content .= '$dbname = "'.$dbname.'"; '." \n";

        $fp = fopen('./uploads/'. "dbconfig.php","w+");

        fwrite($fp,$content);

        fclose($fp);

        $backup_file = 'database.sql';

        $command = "mysqldump -u $dbuser -p$dbpass $dbname > $backup_file";

        my_var_dump('running command: '.$command);
        system($command);

        $download_link = base_url().$backup_file;

        # Now email this backup file
        $this->load->library('email');

        $this->email->clear(TRUE);
        $this->email->set_mailtype("html");
        $this->email->from(SYSTEM_EMAIL, SYSTEM_NAME);
        $this->email->to(SYSTEM_EMAIL);

        $hostname = gethostname();
        $environment = ENVIRONMENT;
        $datetime = date('r e');
        # prepare message here
        $message =	"The database backup file is attached.
                    <br>Date: $datetime
                    <br>Host: $hostname
                    <br>Environment: $environment
					<br>$download_link
					<br>Thanks
					<br>".__FILE__;
        $this->email->subject(SYSTEM_NAME." database backup");
        $this->email->message($message);

        //$this->email->attach("./$backup_file");
        $this->email->send();
    }

    public function files_zip()
    {
        $backup_file = 'site_backup.zip';
        $skip_files_and_directories[] = 'cache';
        $skip_files_and_directories[] = 'cgi-bin';
        $skip_files_and_directories[] = 'ci_sessions';
        $skip_files_and_directories[] = 'error.log';
        //$skip_files_and_directories[] = 'uploads';
        //$skip_files_and_directories[] = 'vendor';
        //$skip_files_and_directories[] = 'system';
        $skip_files_and_directories[] = $backup_file;
        if ($handle = opendir('.')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    //my_var_dump($entry);
                    if( ! in_array($entry,$skip_files_and_directories))
                    {
                        $backup_files_and_directories[] = $entry;
                    }
                }
            }
            closedir($handle);
        }
        my_var_dump($backup_files_and_directories);


        $command = "zip -r $backup_file ".implode(' ',$backup_files_and_directories);
        my_var_dump($command);
        system($command);

        $download_link = base_url().$backup_file;

        # Now email this backup file
        $this->load->library('email');

        $this->email->clear(TRUE);
        $this->email->set_mailtype("html");
        $this->email->from(SYSTEM_EMAIL, SYSTEM_NAME);
        $this->email->to(SYSTEM_EMAIL);

        $hostname = gethostname();
        $environment = ENVIRONMENT;
        $datetime = date('r e');
        # prepare message here
        $message =	"The zip backup file is attached.
                    <br>Date: $datetime
                    <br>Host: $hostname
                    <br>Environment: $environment
					<br>$download_link
					<br>Thanks
					<br>".__FILE__;
        $this->email->subject(SYSTEM_NAME." zip backup");
        $this->email->message($message);

        //$this->email->attach("./$backup_file");
        $this->email->send();
    }

    public function clean_database()
    {
        $tables = [];

        foreach($tables as $table)
        {
            $this->db->query("DELETE FROM `$table`");
            my_var_dump($this->db->last_query());
            $this->db->query("ALTER TABLE `$table` AUTO_INCREMENT=1");
            my_var_dump($this->db->last_query());
        }
    }

}