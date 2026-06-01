<?php

declare(strict_types=1);

namespace Humblee\Controller;

use Humblee\Foundation\Core;
use Humblee\Model\Crypto;

/**
 * Read files out of the /storage folder and send to browser
 *
 * URI should look like /media/{id}/{filename.ext}
 * where {id} is the row ID in the media table
 */
class Media {

	private object|false $file;
	private string $filelocation = '';

	public function __construct()
	{
		$_uri_parts = Core::getURIparts();
		if(isset($_uri_parts[1]) && is_numeric($_uri_parts[1]))
		{
		    $this->file = \ORM::for_table(_table_media)->find_one($_uri_parts[1]);
		}
		else
		{
		    $this->file = false;
		}

		if(!$this->file)
        {
            header('HTTP/1.1 404 Not Found');
            $content = [];
			$template_view = Core::view(_app_server_path."application/views/404.php", ['content' => $content]);
			echo Core::view(_app_server_path.'application/views/templates/template.php', ['content' => $content, 'template_view' => $template_view]);
			exit();
        }

        if($this->file->required_role != 0 && !Core::auth($this->file->required_role))
        {
            header('HTTP/1.1 403 Forbidden');
			exit("<h1>403 Forbidden</h1>You do not have permission to view this file");
        }

        $this->filelocation = _app_server_path.'storage/'.$this->file->filepath;

        if(!file_exists($this->filelocation))
        {
            header('HTTP/1.1 500 Internal Server Error');
			exit("<h1>500 Internal Server Error</h1>The file system could not find the requested resource");
        }
	}

	private function setHeaders(bool $force_download = false): void
	{
	    $cacheControl = ($this->file->required_role != 0) ? 'private' : 'public';

	    header('Content-Type: ' . $this->file->type);
	    header('Cache-Control: '. $cacheControl);
	    header('Content-Length: ' . filesize($this->filelocation));

	    if($force_download)
	    {
	        header('Content-Disposition: attachment; filename='. $this->file->name);
	    }
	}

    public function index(): void
    {
        if($this->file->encrypted == 1)
        {
            $encrypted_content = file_get_contents($this->filelocation);

            if($encrypted_content === false)
            {
                header('HTTP/1.1 500 Internal Server Error');
    			exit("<h1>500 Internal Server Error</h1>The file system could not read the requested resource");
            }

            $crypto    = new Crypto;
            $decrypted = $crypto->decrypt($encrypted_content);

            if($decrypted === false)
            {
                header('HTTP/1.1 500 Internal Server Error');
                exit("<h1>500 Internal Server Error</h1>Could not decrypt the requested resource");
            }

            $cacheControl = ($this->file->required_role != 0) ? 'private' : 'public';
            header('Content-Type: ' . $this->file->type);
            header('Cache-Control: ' . $cacheControl);
            header('Content-Length: ' . strlen($decrypted));
            echo $decrypted;
        }
        else
        {
            $this->setHeaders();
            readfile($this->filelocation);
        }
    }

}
