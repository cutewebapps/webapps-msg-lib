<?php


class Mail_Storage_Folder_Mbox extends Mail_Storage_Mbox implements Mail_Storage_Folder_Interface
{
    /**
     * Mail_Storage_Folder root folder for folder structure
     * @var Mail_Storage_Folder
     */
    protected $_rootFolder;

    /**
     * rootdir of folder structure
     * @var string
     */
    protected $_rootdir;

    /**
     * name of current folder
     * @var string
     */
    protected $_currentFolder;

    /**
     * Create instance with parameters
     *
     * Disallowed parameters are:
     *   - filename use Mail_Storage_Mbox for a single file
     * Supported parameters are:
     *   - dirname rootdir of mbox structure
     *   - folder intial selected folder, default is 'INBOX'
     *
     * @param  $params array mail reader specific parameters
     * @throws Mail_Storage_Exception
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            $params = (object)$params;
        }

        if (isset($params->filename)) {
            /**
             * @see Mail_Storage_Exception
             */
            throw new Mail_Storage_Exception('use Mail_Storage_Mbox for a single file');
        }

        if (!isset($params->dirname) || !is_dir($params->dirname)) {
            /**
             * @see Mail_Storage_Exception
             */
            throw new Mail_Storage_Exception('no valid dirname given in params');
        }

        $this->_rootdir = rtrim($params->dirname, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->_buildFolderTree($this->_rootdir);
        $this->selectFolder(!empty($params->folder) ? $params->folder : 'INBOX');
        $this->_has['top']      = true;
        $this->_has['uniqueid'] = false;
    }

    /**
     * find all subfolders and mbox files for folder structure
     *
     * Result is save in Mail_Storage_Folder instances with the root in $this->_rootFolder.
     * $parentFolder and $parentGlobalName are only used internally for recursion.
     *
     * @param string $currentDir call with root dir, also used for recursion.
     * @param Mail_Storage_Folder|null $parentFolder used for recursion
     * @param string $parentGlobalName used for rescursion
     * @return null
     * @throws Mail_Storage_Exception
     */
    protected function _buildFolderTree($currentDir, $parentFolder = null, $parentGlobalName = '')
    {
        if (!$parentFolder) {
            $this->_rootFolder = new Mail_Storage_Folder('/', '/', false);
            $parentFolder = $this->_rootFolder;
        }

        $dh = @opendir($currentDir);
        if (!$dh) {
            /**
             * @see Mail_Storage_Exception
             */
            throw new Mail_Storage_Exception("can't read dir $currentDir");
        }
        while (($entry = readdir($dh)) !== false) {
            // ignore hidden files for mbox
            if ($entry[0] == '.') {
                continue;
            }
            $absoluteEntry = $currentDir . $entry;
            $globalName = $parentGlobalName . DIRECTORY_SEPARATOR . $entry;
            if (is_file($absoluteEntry) && $this->_isMboxFile($absoluteEntry)) {
                $parentFolder->$entry = new Mail_Storage_Folder($entry, $globalName);
                continue;
            }
            if (!is_dir($absoluteEntry) /* || $entry == '.' || $entry == '..' */) {
                continue;
            }
            $folder = new Mail_Storage_Folder($entry, $globalName, false);
            $parentFolder->$entry = $folder;
            $this->_buildFolderTree($absoluteEntry . DIRECTORY_SEPARATOR, $folder, $globalName);
        }

        closedir($dh);
    }

    /**
     * get root folder or given folder
     *
     * @param string $rootFolder get folder structure for given folder, else root
     * @return Mail_Storage_Folder root or wanted folder
     * @throws Mail_Storage_Exception
     */
    public function getFolders($rootFolder = null)
    {
        if (!$rootFolder) {
            return $this->_rootFolder;
        }

        $currentFolder = $this->_rootFolder;
        $subname = trim($rootFolder, DIRECTORY_SEPARATOR);
        while ($currentFolder) {
            @list($entry, $subname) = @explode(DIRECTORY_SEPARATOR, $subname, 2);
            $currentFolder = $currentFolder->$entry;
            if (!$subname) {
                break;
            }
        }

        if ($currentFolder->getGlobalName() != DIRECTORY_SEPARATOR . trim($rootFolder, DIRECTORY_SEPARATOR)) {
            /**
             * @see Mail_Storage_Exception
             */
            throw new Mail_Storage_Exception("folder $rootFolder not found");
        }
        return $currentFolder;
    }

    /**
     * select given folder
     *
     * folder must be selectable!
     *
     * @param Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @return null
     * @throws Mail_Storage_Exception
     */
    public function selectFolder($globalName)
    {
        $this->_currentFolder = (string)$globalName;

        // getting folder from folder tree for validation
        $folder = $this->getFolders($this->_currentFolder);

        try {
            $this->_openMboxFile($this->_rootdir . $folder->getGlobalName());
        } catch(Mail_Storage_Exception $e) {
            // check what went wrong
            if (!$folder->isSelectable()) {
                /**
                 * @see Mail_Storage_Exception
                 */
                throw new Mail_Storage_Exception("{$this->_currentFolder} is not selectable", 0, $e);
            }
            // seems like file has vanished; rebuilding folder tree - but it's still an exception
            $this->_buildFolderTree($this->_rootdir);
            /**
             * @see Mail_Storage_Exception
             */
            throw new Mail_Storage_Exception('seems like the mbox file has vanished, I\'ve rebuild the ' .
                                                         'folder tree, search for an other folder and try again', 0, $e);
        }
    }

    /**
     * get Mail_Storage_Folder instance for current folder
     *
     * @return Mail_Storage_Folder instance of current folder
     * @throws Mail_Storage_Exception
     */
    public function getCurrentFolder()
    {
        return $this->_currentFolder;
    }

    /**
     * magic method for serialize()
     *
     * with this method you can cache the mbox class
     *
     * @return array name of variables
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), array('_currentFolder', '_rootFolder', '_rootdir'));
    }

    /**
     * magic method for unserialize()
     *
     * with this method you can cache the mbox class
     *
     * @return null
     */
    public function __wakeup()
    {
        // if cache is stall selectFolder() rebuilds the tree on error
        parent::__wakeup();
    }
}
