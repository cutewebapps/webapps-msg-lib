<?php

interface Mail_Storage_Folder_Interface
{
    /**
     * get root folder or given folder
     *
     * @param string $rootFolder get folder structure for given folder, else root
     * @return Mail_Storage_Folder root or wanted folder
     */
    public function getFolders($rootFolder = null);

    /**
     * select given folder
     *
     * folder must be selectable!
     *
     * @param Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @return null
     * @throws Mail_Storage_Exception
     */
    public function selectFolder($globalName);


    /**
     * get Mail_Storage_Folder instance for current folder
     *
     * @return Mail_Storage_Folder instance of current folder
     * @throws Mail_Storage_Exception
     */
    public function getCurrentFolder();
}
