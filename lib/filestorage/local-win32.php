<?php
/**
 * for local filestore, we only have to map the paths
 */
class OC_Filestorage_Local extends OC_Filestorage_Common {
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
		$files = array('.', '..');
		try {
			$dir = $this->FS->getFolder($this->buildPath($path));

			foreach ($dir->SubFolders() as $v) {
				$files[] = $v->Name;
			}
			foreach ($dir->Files as $v) {
				$files[] = $v->Name;
			}
		}
		catch (\Exception $e) {
			$files = array();
		}

		OC_FakeDirStream::$dirs['local-win32'.$path] = $files;
		return opendir('fakedir://local-win32'.$path);
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
		$fullPath = $this->shortPath($path);
		$statResult = stat($fullPath);

		if ($statResult['size'] < 0) {
			$f = $this->FS->GetFile($fullPath);

			$statResult['size'] = $f->Size;
			$statResult[7] = $f->Size;
		}
		return $statResult;
	}
	public function filetype($path) {
		$filetype=filetype($this->shortPath($path));
		if($filetype=='link') {
			$filetype=filetype(realpath($this->buildPath($path)));
		}
		return $filetype;
	}
	public function filesize($path) {
		if($this->is_dir($path)) {
			return 0;
		}else{
			$fullPath = $this->shortPath($path);
			$f = $this->FS->GetFile($fullPath);

			return $f->Size;
		}
	}
	public function isReadable($path) {
		//
		// we cannot use php's is_readable because some files don't work
		//
		$path = $this->buildPath($path);

		if ($this->FS->FileExists($path)) {
			try {
				//Constant 	Value 	Description
				//ForReading 	1 	Open a file for reading only. You can't write to this file.
				//ForAppending 	8 	Open a file and write to the end of the file.
				$fileStream = $this->FS->OpenTextFile($path, 1);
				$fileStream->Close();
				return true;
			}
			catch (com_exception $e) {
				return false;
			}
		}
		if ($this->FS->FolderExists($path)) {
			//
			// TODO: test is we can enumerate the folder contents
			//
			return true;
		}
		return false;
	}

	public function isUpdatable($path) {
		return is_writable($this->shortPath($path));
	}
	public function file_exists($path) {
		return file_exists($this->shortPath($path));
	}
	public function filectime($path) {
		return filectime($this->shortPath($path));
	}
	public function filemtime($path) {
		return filemtime($this->shortPath($path));
	}
	public function touch($path, $mtime=null) {
		// sets the modification time of the file to the given value.
		// If mtime is nil the current time is set.
		// note that the access time of the file always changes to the current time.
		if(!is_null($mtime)) {
			$result=touch( $this->shortPath($path), $mtime );
		}else{
			$result=touch( $this->shortPath($path));
		}
		if( $result ) {
			clearstatcache( true, $this->shortPath($path) );
		}

		return $result;
	}
	public function file_get_contents($path) {
		$contents = file_get_contents($this->shortPath($path));
		if ($contents === false) {
			//
			// TODO: add binary support using ADODB.Stream
			//       http://www.motobit.com/tips/detpg_read-write-binary-files/
			//
			$fileStream = $this->FS->OpenTextFile($this->shortPath($path), 1);
			$contents = $fileStream->ReadAll();
			$fileStream->Close();
		}
		return $contents;
	}

	public function file_put_contents($path, $data) {//trigger_error("$path = ".var_export($path, 1));
		$result = file_put_contents($this->buildPath($path), $data);
		if ($result === false) {
			//
			// TODO: add binary support using ADODB.Stream
			//       http://www.motobit.com/tips/detpg_read-write-binary-files/
			//
			$fileStream = $this->FS->CreateTextFile ($this->shortPath($path), true, true);
			$fileStream->Write($data);
			$fileStream->Close();
		}
		return $result;
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
		switch ($m = substr($mode, 0, 1))
		{
			case 'x': $mode[0] = 'w';
			case 'w':
			case 'a':
				try {
					$this->FS->CreateTextFile($this->buildPath($path), false)->Close();
				} catch (com_exception $e) {
					if ('x' === $m) {
						return false;
					}
				}
		}

		return fopen($this->shortPath($path), $mode);
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
		if (!file_exists($dir)) {
			return true;
		}
		if (!is_dir($dir) || is_link($dir)) {
			return unlink($dir);
		}
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

	protected function shortPath($path){
		$path = $this->buildPath($path);

		try
		{
			if ($this->FS->FileExists($path)) {
				return $this->FS->GetFile($path)->ShortPath;
			}
			if ($this->FS->FolderExists($path)) {
				return $this->FS->GetFolder($path)->ShortPath;
			}
		}
		catch (com_exception $e){

		}

		return $path;
	}
}
