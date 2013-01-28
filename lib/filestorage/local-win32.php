<?php
/**
 * for local filestore, we only have to map the paths
 */
class OC_Filestorage_Local extends OC_Filestorage_Common{
	protected $datadir;
    private $FS;

    public function __construct($arguments) {
        $this->FS = new COM('Scripting.FileSystemObject', null, CP_UTF8);
		$this->datadir=$arguments['datadir'];
		if(substr($this->datadir, -1)!=='/') {
			$this->datadir.='/';
		}
	}
	public function mkdir($path) {
        try {
            $this->FS->CreateFolder($this->buildPath($path));
        } catch (com_exception $e) {
            return false;
        }
        return true;
	}
	public function rmdir($path) {
        try {
            $this->FS->DeleteFolder($this->buildPath($path));
        } catch (com_exception $e) {
            return false;
        }
        return true;
	}
	public function opendir($path) {
        return new OC_Windows_Directory($this->buildPath($path), $this->FS);
	}
    public function readdir($dir) {
        return $dir->read();
    }
	public function is_dir($path) {
		if(substr($path, -1)=='/') {
			$path=substr($path, 0, -1);
		}
		return is_dir($this->buildPath($path));
	}
	public function is_file($path) {
		return is_file($this->buildPath($path));
	}
	public function stat($path) {
		$fullPath = $this->buildPath($path);
		$statResult = stat($fullPath);

		if ($statResult['size'] < 0) {
            $f = $this->FS->GetFile($fullPath);

			$statResult['size'] = $f->Size;
			$statResult[7] = $f->Size;
		}
		return $statResult;
	}
	public function filetype($path) {
		$filetype=filetype($this->buildPath($path));
		if($filetype=='link') {
			$filetype=filetype(realpath($this->buildPath($path)));
		}
		return $filetype;
	}
	public function filesize($path) {
		if($this->is_dir($path)) {
			return 0;
		}else{
			$fullPath = $this->buildPath($path);
            $f = $this->FS->GetFile($fullPath);

            return $f->Size;
		}
	}
	public function isReadable($path) {
		return is_readable($this->buildPath($path));
	}
	public function isUpdatable($path) {
		return is_writable($this->buildPath($path));
	}
	public function file_exists($path) {
		return file_exists($this->buildPath($path));
	}
	public function filectime($path) {
		return filectime($this->buildPath($path));
	}
	public function filemtime($path) {
		return filemtime($this->buildPath($path));
	}
	public function touch($path, $mtime=null) {
		// sets the modification time of the file to the given value.
		// If mtime is nil the current time is set.
		// note that the access time of the file always changes to the current time.
		if(!is_null($mtime)) {
			$result=touch( $this->buildPath($path), $mtime );
		}else{
			$result=touch( $this->buildPath($path));
		}
		if( $result ) {
			clearstatcache( true, $this->buildPath($path) );
		}

		return $result;
	}
	public function file_get_contents($path) {
		return file_get_contents($this->buildPath($path));
	}
	public function file_put_contents($path, $data) {//trigger_error("$path = ".var_export($path, 1));
		return file_put_contents($this->buildPath($path), $data);
	}
	public function unlink($path) {
		return $this->delTree($path);
	}
	public function rename($path1, $path2) {
		if (!$this->isUpdatable($path1)) {
			OC_Log::write('core', 'unable to rename, file is not writable : '.$path1, OC_Log::ERROR);
			return false;
		}
		if(! $this->file_exists($path1)) {
			OC_Log::write('core', 'unable to rename, file does not exists : '.$path1, OC_Log::ERROR);
			return false;
		}

		if($return=rename($this->buildPath($path1), $this->buildPath($path2))) {
		}
		return $return;
	}
	public function copy($path1, $path2) {
		if($this->is_dir($path2)) {
			if(!$this->file_exists($path2)) {
				$this->mkdir($path2);
			}
			$source=substr($path1, strrpos($path1, '/')+1);
			$path2.=$source;
		}
		return copy($this->buildPath($path1), $this->buildPath($path2));
	}
	public function fopen($path, $mode) {
		if($return=fopen($this->buildPath($path), $mode)) {
			switch($mode) {
				case 'r':
					break;
				case 'r+':
				case 'w+':
				case 'x+':
				case 'a+':
					break;
				case 'w':
				case 'x':
				case 'a':
					break;
			}
		}
		return $return;
	}

	public function getMimeType($path) {
		if($this->isReadable($path)) {
			return OC_Helper::getMimeType($this->buildPath($path));
		}else{
			return false;
		}
	}

	private function delTree($dir) {
		$dirRelative=$dir;
		$dir=$this->buildPath($dir);
		if (!file_exists($dir)) return true;
		if (!is_dir($dir) || is_link($dir)) return unlink($dir);
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') continue;
			if(is_file($dir.'/'.$item)) {
				if(unlink($dir.'/'.$item)) {
				}
			}elseif(is_dir($dir.'/'.$item)) {
				if (!$this->delTree($dirRelative. "/" . $item)) {
					return false;
				};
			}
		}
		if($return=rmdir($dir)) {
		}
		return $return;
	}

	public function hash($path, $type, $raw=false) {
		return hash_file($type, $this->buildPath($path), $raw);
	}

	public function free_space($path) {
		return @disk_free_space($this->buildPath($path));
	}

	public function search($query) {
		return $this->searchInDir($query);
	}
	public function getLocalFile($path) {
		return $this->buildPath($path);
	}
	public function getLocalFolder($path) {
		return $this->buildPath($path);
	}

	protected function searchInDir($query, $dir='') {
		$files=array();
		foreach (scandir($this->buildPath($dir)) as $item) {
			if ($item == '.' || $item == '..') continue;
			if(strstr(strtolower($item), strtolower($query))!==false) {
				$files[]=$dir.'/'.$item;
			}
			if(is_dir($this->buildPath($dir).'/'.$item)) {
				$files=array_merge($files, $this->searchInDir($query, $dir.'/'.$item));
			}
		}
		return $files;
	}

	/**
	 * check if a file or folder has been updated since $time
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		return $this->filemtime($path)>$time;
	}

    protected function buildPath($path) {
        if(strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }
        return realpath($this->datadir) . DIRECTORY_SEPARATOR . $path;
    }
}

//str_replace('/', '\\', $this->datadir)

class OC_Windows_Directory extends \Directory
{
    public $path, $handle, $fs;

    protected $children = array();

    function __construct($path, $fs)
    {
        $this->path = $path;
        $this->handle = $this;
        $this->fs = $fs;
        $this->children = $this->listContent();

        if (!$this->children)
        {
            $this->children = scandir($path);
            $this->children || $this->children = array();
        }
    }

    function read()
    {
        $c = each($this->children);
        if ($c)
            return $c['value'];
        return false;
//        return (list(, $c) = each($this->children)) ? $c : false;
    }

    function rewind()
    {
        reset($this->children);
    }

    function close()
    {
        unset($this->path, $this->handle, $this->children);
    }

    private function listContent() {
        try
        {
            $f = array('.', '..');

            $dir = $this->fs->getFolder($this->path);

            foreach ($dir->SubFolders() as $v) {
                $f[] = $v->Name;
            }
            foreach ($dir->Files as $v) {
                $f[] = $v->Name;
            }
        }
        catch (\Exception $f)
        {
            $f = array();
        }

        unset($dir);

        return $f;
    }
}
